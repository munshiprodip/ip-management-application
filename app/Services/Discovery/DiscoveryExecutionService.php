<?php

declare(strict_types=1);

namespace App\Services\Discovery;

use App\Domain\AddressSpace\Enums\IpAllocationStatus;
use App\Domain\AddressSpace\Enums\IpConnectivityStatus;
use App\Domain\AddressSpace\ValueObjects\IpAddress as IpAddressVO;
use App\Domain\Discovery\Enums\DiscoverySourceType;
use App\Domain\Discovery\Enums\JobStatus;
use App\Models\AuditEvent;
use App\Models\DiscoveryAgent;
use App\Models\DiscoveryJob;
use App\Models\IpAddress;
use App\Models\IpObservation;
use App\Models\Subnet;
use App\Models\User;
use App\Services\Discovery\Contracts\DiscoveryCollectorInterface;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class DiscoveryExecutionService
{
    public function __construct(
        protected DiscoveryCollectorInterface $collector,
        protected ReconciliationEngineService $reconciliationEngine
    ) {}

    /**
     * Executes safe, read-only discovery against a target subnet.
     * Enforces Gate T-018, Gate T-019, Gate T-020, and Gate T-028.
     */
    public function executeScan(DiscoveryAgent $agent, Subnet $subnet, ?User $requestedBy = null, ?DiscoverySourceType $sourceType = null): DiscoveryJob
    {
        // 1. T-019: Unauthorized scan target enforcement
        if (! $agent->isAllowedSubnet($subnet)) {
            throw new AuthorizationException(sprintf(
                'Unauthorized scan target: Agent %s is not permitted to discover subnet %s.',
                $agent->code,
                $subnet->cidr
            ));
        }

        // 2. T-019: Clinical Restricted Zone Protection
        // Scanning clinical/medical VLANs is forbidden by default without explicit safety authorization
        if ($this->isMedicalRestricted($subnet)) {
            // Live sweeps on medical restricted equipment are blocked
            if (config('ipam.discovery_enabled') && ($sourceType === DiscoverySourceType::ICMP || $sourceType === DiscoverySourceType::SNMP)) {
                throw new DomainException(sprintf(
                    'Clinical Safety Protection: Active network sweep on clinical medical subnet %s is prohibited by safety policy.',
                    $subnet->cidr
                ));
            }
        }

        // 3. T-028: Check Agent health
        if (! $agent->enabled) {
            throw new DomainException("Cannot execute scan: Discovery agent {$agent->code} is disabled.");
        }

        $sourceType = $sourceType ?? DiscoverySourceType::FIXTURE;

        return DB::transaction(function () use ($agent, $subnet, $requestedBy, $sourceType) {
            // Create scan job
            $job = DiscoveryJob::create([
                'agent_id' => $agent->id,
                'subnet_id' => $subnet->id,
                'requested_by' => $requestedBy?->id,
                'source_type' => $sourceType,
                'status' => JobStatus::RUNNING,
                'started_at' => now(),
            ]);

            // Execute read-only evidence collection
            $rawObservations = $this->collector->collect($job);

            $observedAddresses = [];
            $ingestedCount = 0;

            foreach ($rawObservations as $obs) {
                $ipVo = IpAddressVO::parse($obs['ip_address']);
                $addressHex = $ipVo->toHex();
                $observedAddresses[] = $addressHex;

                // Lookup existing authoritative record (if present)
                $matchedIp = IpAddress::where('vrf_id', $subnet->vrf_id)
                    ->where('ip_version', $ipVo->version())
                    ->where('address_hex', $addressHex)
                    ->first();

                // Append observation evidence
                $observation = IpObservation::create([
                    'discovery_job_id' => $job->id,
                    'vrf_id' => $subnet->vrf_id,
                    'subnet_id' => $subnet->id,
                    'ip_address_id' => $matchedIp?->id,
                    'ip_version' => $ipVo->version(),
                    'address_hex' => $addressHex,
                    'address_text' => $obs['ip_address'],
                    'observed_mac' => $obs['mac_address'] ?? null,
                    'hostname' => $obs['hostname'] ?? null,
                    'source_type' => $obs['source_type'] ?? $sourceType->value,
                    'confidence' => $obs['confidence'] ?? 100,
                    'observed_at' => now(),
                    'payload_json' => $obs['payload'] ?? null,
                ]);

                // T-018: Ingestion updates ONLY observational connectivity status, NEVER authoritative allocation
                if ($matchedIp !== null) {
                    $matchedIp->update([
                        'connectivity_status' => IpConnectivityStatus::OBSERVED_ONLINE,
                        'last_reconciled_at' => now(),
                    ]);
                }

                $ingestedCount++;
            }

            // T-020: Silent / Offline devices are NEVER automatically freed
            // Update connectivity status of unobserved allocated IPs to observed_offline, but retain allocation_status
            $unobservedAllocated = IpAddress::where('subnet_id', $subnet->id)
                ->where('allocation_status', IpAllocationStatus::ASSIGNED)
                ->whereNotIn('address_hex', $observedAddresses)
                ->get();

            foreach ($unobservedAllocated as $silentIp) {
                // Address remains strictly ASSIGNED! Only observed connectivity changes
                $silentIp->update([
                    'connectivity_status' => IpConnectivityStatus::OBSERVED_OFFLINE,
                    'last_reconciled_at' => now(),
                ]);
            }

            // Mark job completed
            $job->update([
                'status' => JobStatus::COMPLETED,
                'finished_at' => now(),
                'metrics_json' => [
                    'observations_count' => $ingestedCount,
                    'unobserved_assigned_count' => $unobservedAllocated->count(),
                    'duration_ms' => now()->diffInMilliseconds($job->started_at),
                ],
            ]);

            // Run reconciliation engine on collected evidence
            $this->reconciliationEngine->reconcileSubnet($subnet, $job);

            // Record audit trail
            AuditEvent::record(
                eventType: 'discovery.completed',
                reason: sprintf('Completed read-only discovery scan on %s via agent %s (%d observations)', $subnet->cidr, $agent->code, $ingestedCount),
                actor: $requestedBy,
                subject: $job,
                after: [
                    'agent_code' => $agent->code,
                    'subnet_cidr' => $subnet->cidr,
                    'observations' => $ingestedCount,
                ]
            );

            return $job->fresh();
        });
    }

    /**
     * Determines whether the subnet belongs to a clinical restricted zone.
     */
    protected function isMedicalRestricted(Subnet $subnet): bool
    {
        $hasMedicalVlan = $subnet->vlans()
            ->where(function ($q) {
                $q->where('is_medical_restricted', true)
                    ->orWhereIn('security_zone', ['clinical', 'biomedical']);
            })
            ->exists();

        return $hasMedicalVlan;
    }
}
