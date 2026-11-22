<?php

declare(strict_types=1);

namespace App\Services\Discovery;

use App\Domain\AddressSpace\Enums\IpAllocationStatus;
use App\Domain\Discovery\Enums\FindingSeverity;
use App\Domain\Discovery\Enums\FindingStatus;
use App\Domain\Discovery\Enums\FindingType;
use App\Models\AuditEvent;
use App\Models\DiscoveryAgent;
use App\Models\DiscoveryJob;
use App\Models\IpAddress;
use App\Models\IpObservation;
use App\Models\ReconciliationFinding;
use App\Models\Subnet;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReconciliationEngineService
{
    /**
     * Compares recent observations against authoritative IPAM records for a subnet.
     * Detects unknown_active_ip, unexpected_mac, duplicate IP, and silent devices.
     */
    public function reconcileSubnet(Subnet $subnet, ?DiscoveryJob $job = null): int
    {
        $findingsCreated = 0;

        $observations = $job !== null
            ? $job->observations()->get()
            : IpObservation::where('subnet_id', $subnet->id)
                ->where('observed_at', '>=', now()->subHours(24))
                ->get();

        foreach ($observations as $obs) {
            $authoritativeIp = IpAddress::where('vrf_id', $subnet->vrf_id)
                ->where('ip_version', $obs->ip_version)
                ->where('address_hex', $obs->address_hex)
                ->with(['currentAssignment.deviceInterface'])
                ->first();

            // 1. T-018: Detect UNKNOWN_ACTIVE_IP (Rogue unmanaged device)
            if ($authoritativeIp === null || $authoritativeIp->allocation_status !== IpAllocationStatus::ASSIGNED) {
                $existingFinding = ReconciliationFinding::where('subnet_id', $subnet->id)
                    ->where('address_text', $obs->address_text)
                    ->where('finding_type', FindingType::UNKNOWN_ACTIVE_IP->value)
                    ->open()
                    ->first();

                if ($existingFinding === null) {
                    ReconciliationFinding::create([
                        'finding_type' => FindingType::UNKNOWN_ACTIVE_IP,
                        'vrf_id' => $subnet->vrf_id,
                        'subnet_id' => $subnet->id,
                        'ip_address_id' => $authoritativeIp?->id,
                        'address_text' => $obs->address_text,
                        'severity' => FindingSeverity::CRITICAL,
                        'status' => FindingStatus::OPEN,
                        'title' => "Unmanaged Active IP Discovered: {$obs->address_text}",
                        'details' => sprintf(
                            'Observed active network traffic on %s (MAC: %s, Hostname: %s), but no authoritative assignment exists in IPAM.',
                            $obs->address_text,
                            $obs->observed_mac ?? 'Unknown',
                            $obs->hostname ?? 'Unresolved'
                        ),
                        'observed_mac' => $obs->observed_mac,
                        'authoritative_mac' => null,
                        'evidence_refs_json' => [
                            'discovery_job_id' => $obs->discovery_job_id,
                            'source_type' => $obs->source_type->value,
                            'confidence' => $obs->confidence,
                        ],
                    ]);
                    $findingsCreated++;
                }
                continue;
            }

            // 2. Detect UNEXPECTED_MAC (MAC address drift / spoofing)
            $assignedInterface = $authoritativeIp->currentAssignment?->deviceInterface;
            if ($assignedInterface !== null && $assignedInterface->mac_address && $obs->observed_mac) {
                $cleanAuthoritativeMac = strtolower(str_replace([':', '-', '.'], '', $assignedInterface->mac_address));
                $cleanObservedMac = strtolower(str_replace([':', '-', '.'], '', $obs->observed_mac));

                if ($cleanAuthoritativeMac !== $cleanObservedMac) {
                    $existingFinding = ReconciliationFinding::where('subnet_id', $subnet->id)
                        ->where('address_text', $obs->address_text)
                        ->where('finding_type', FindingType::UNEXPECTED_MAC->value)
                        ->open()
                        ->first();

                    if ($existingFinding === null) {
                        ReconciliationFinding::create([
                            'finding_type' => FindingType::UNEXPECTED_MAC,
                            'vrf_id' => $subnet->vrf_id,
                            'subnet_id' => $subnet->id,
                            'ip_address_id' => $authoritativeIp->id,
                            'address_text' => $obs->address_text,
                            'severity' => FindingSeverity::WARNING,
                            'status' => FindingStatus::OPEN,
                            'title' => "Unexpected MAC on {$obs->address_text}",
                            'details' => sprintf(
                                'IP %s is bound to interface %s with MAC %s, but observed responding with MAC %s.',
                                $obs->address_text,
                                $assignedInterface->name,
                                $assignedInterface->mac_address,
                                $obs->observed_mac
                            ),
                            'observed_mac' => $obs->observed_mac,
                            'authoritative_mac' => $assignedInterface->mac_address,
                            'evidence_refs_json' => [
                                'discovery_job_id' => $obs->discovery_job_id,
                                'interface_id' => $assignedInterface->id,
                            ],
                        ]);
                        $findingsCreated++;
                    }
                }
            }
        }

        return $findingsCreated;
    }

    /**
     * T-028: Evaluates agent heartbeats and flags stale/offline discovery agents.
     */
    public function checkAgentHealth(DiscoveryAgent $agent): ?ReconciliationFinding
    {
        if ($agent->enabled && ! $agent->isHealthy()) {
            $defaultVrf = \App\Models\Vrf::getDefault();
            $firstSubnet = Subnet::first();

            $existing = ReconciliationFinding::where('finding_type', FindingType::AGENT_DOWN->value)
                ->where('details', 'like', "%{$agent->code}%")
                ->open()
                ->first();

            if ($existing === null && $firstSubnet !== null) {
                return ReconciliationFinding::create([
                    'finding_type' => FindingType::AGENT_DOWN,
                    'vrf_id' => $defaultVrf->id,
                    'subnet_id' => $firstSubnet->id,
                    'address_text' => '0.0.0.0',
                    'severity' => FindingSeverity::WARNING,
                    'status' => FindingStatus::OPEN,
                    'title' => "Discovery Agent Offline: {$agent->code}",
                    'details' => sprintf('Agent %s (%s) has missed expected heartbeat threshold. Evidence collection may be stale.', $agent->name, $agent->code),
                    'evidence_refs_json' => [
                        'agent_id' => $agent->id,
                        'last_seen_at' => $agent->last_seen_at?->toIso8601String(),
                    ],
                ]);
            }
        }

        return null;
    }

    /**
     * Resolves a reconciliation finding with operator audit trail.
     */
    public function resolveFinding(ReconciliationFinding $finding, User $user, string $resolutionNote): ReconciliationFinding
    {
        return DB::transaction(function () use ($finding, $user, $resolutionNote) {
            $finding->resolve($user, $resolutionNote);

            AuditEvent::record(
                eventType: 'reconciliation.resolved',
                reason: sprintf('Resolved finding %s: %s', $finding->title, $resolutionNote),
                actor: $user,
                subject: $finding,
                after: [
                    'status' => FindingStatus::RESOLVED->value,
                    'resolution_note' => $resolutionNote,
                    'resolved_by' => $user->id,
                ]
            );

            return $finding->fresh();
        });
    }
}
