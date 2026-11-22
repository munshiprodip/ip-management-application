<?php

declare(strict_types=1);

namespace App\Actions\AddressSpace;

use App\Domain\AddressSpace\Enums\IpAllocationStatus;
use App\Domain\AddressSpace\Enums\IpClassification;
use App\Domain\AddressSpace\Enums\IpConnectivityStatus;
use App\Domain\AddressSpace\ValueObjects\IpAddress as IpAddressVO;
use App\Models\AuditEvent;
use App\Models\DeviceInterface;
use App\Models\IpAddress;
use App\Models\IpAssignment;
use App\Models\Subnet;
use App\Models\User;
use App\Services\AddressSpace\IpAllocationService;
use App\Services\AddressSpace\PoolValidationService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AllocateIpAction
{
    public function __construct(
        protected IpAllocationService $allocationService,
        protected PoolValidationService $poolValidation
    ) {}

    /**
     * @param array{
     *     subnet_id: int,
     *     ip_address?: ?string,
     *     interface_id?: ?int,
     *     classification?: string|IpClassification,
     *     hostname?: ?string,
     *     description?: ?string,
     *     idempotency_key?: ?string
     * } $data
     */
    public function execute(array $data, ?User $actor = null): IpAddress
    {
        $subnet = Subnet::findOrFail($data['subnet_id']);

        if (! $subnet->isAssignable()) {
            throw ValidationException::withMessages([
                'subnet_id' => "Subnet {$subnet->cidr} is not configured for direct address allocation.",
            ]);
        }

        // 1. Idempotency Check
        $idempotencyKey = !empty($data['idempotency_key']) ? trim($data['idempotency_key']) : null;
        if ($idempotencyKey !== null) {
            $existingAssignment = IpAssignment::where('idempotency_key', $idempotencyKey)
                ->whereNull('released_at')
                ->first();
            if ($existingAssignment) {
                return $existingAssignment->ipAddress;
            }
        }

        // 2. Candidate Resolution (Auto vs Explicit)
        $prefix = $subnet->toIpPrefix();
        if (!empty($data['ip_address'])) {
            $ipVO = IpAddressVO::parse($data['ip_address']);
            if (! $prefix->containsIp($ipVO)) {
                throw ValidationException::withMessages([
                    'ip_address' => "Address {$data['ip_address']} is outside subnet boundary {$subnet->cidr}.",
                ]);
            }
        } else {
            $ipVO = $this->allocationService->findNextFreeCandidate($subnet);
            if (! $ipVO) {
                throw ValidationException::withMessages([
                    'subnet_id' => "No available free IP addresses remaining in subnet {$subnet->cidr}.",
                ]);
            }
        }

        // 3. Invariant T-009: Reject if in excluded range or non-static pool without override
        if ($this->poolValidation->isExcludedOrDynamic($subnet, $ipVO)) {
            throw ValidationException::withMessages([
                'ip_address' => "Address {$ipVO->toNormalizedString()} falls within an excluded static range or dynamic DHCP pool.",
            ]);
        }

        // 4. Invariant T-010: Bounded retry transaction with row locking
        $maxRetries = 3;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;
            try {
                return DB::transaction(function () use ($subnet, $ipVO, $data, $actor, $idempotencyKey) {
                    // Lock existing address record or create new claim
                    $addressHex = $ipVO->toHex();

                    // Lock existing row if present (T-010 concurrency lock)
                    $ipRecord = IpAddress::where('vrf_id', $subnet->vrf_id)
                        ->where('ip_version', $subnet->ip_version)
                        ->where('address_hex', $addressHex)
                        ->lockForUpdate()
                        ->first();

                    if (! $ipRecord) {
                        $ipRecord = IpAddress::create([
                            'vrf_id' => $subnet->vrf_id,
                            'subnet_id' => $subnet->id,
                            'ip_version' => $subnet->ip_version,
                            'address_hex' => $addressHex,
                            'address_text' => $ipVO->toNormalizedString(),
                            'allocation_status' => IpAllocationStatus::UNASSIGNED,
                            'connectivity_status' => IpConnectivityStatus::UNOBSERVED,
                            'classification' => $data['classification'] ?? IpClassification::STATIC_HOST,
                            'hostname' => !empty($data['hostname']) ? trim($data['hostname']) : null,
                            'description' => $data['description'] ?? null,
                        ]);
                    }

                    // Invariant T-008: Reject if already allocated, even if currently observed offline
                    if ($ipRecord->allocation_status === IpAllocationStatus::ASSIGNED) {
                        throw ValidationException::withMessages([
                            'ip_address' => "Address {$ipRecord->address_text} is already authoritatively assigned (Status: Assigned).",
                        ]);
                    }

                    if ($ipRecord->isQuarantined()) {
                        throw ValidationException::withMessages([
                            'ip_address' => "Address {$ipRecord->address_text} is currently held in post-release quarantine until {$ipRecord->quarantined_until?->toIso8601String()}.",
                        ]);
                    }

                    if ($ipRecord->allocation_status === IpAllocationStatus::BLOCKED) {
                        throw ValidationException::withMessages([
                            'ip_address' => "Address {$ipRecord->address_text} is administratively blocked.",
                        ]);
                    }

                    // Optional Interface linkage
                    $interfaceId = !empty($data['interface_id']) ? (int) $data['interface_id'] : null;
                    if ($interfaceId !== null) {
                        DeviceInterface::findOrFail($interfaceId);
                    }

                    // Append immutable assignment history
                    $assignment = IpAssignment::create([
                        'ip_address_id' => $ipRecord->id,
                        'interface_id' => $interfaceId,
                        'assigned_at' => now(),
                        'assignment_method' => !empty($data['ip_address']) ? 'manual_static' : 'auto_first_free',
                        'assigned_by_user_id' => $actor?->id ?? auth()->id(),
                        'idempotency_key' => $idempotencyKey,
                    ]);

                    // Authoritative update on IP record
                    $ipRecord->update([
                        'current_assignment_id' => $assignment->id,
                        'allocation_status' => IpAllocationStatus::ASSIGNED,
                        'classification' => $data['classification'] ?? $ipRecord->classification,
                        'hostname' => !empty($data['hostname']) ? trim($data['hostname']) : $ipRecord->hostname,
                        'description' => $data['description'] ?? $ipRecord->description,
                        'quarantined_until' => null,
                        'quarantine_reason' => null,
                    ]);

                    // Invariant T-011: Structured audit trail
                    AuditEvent::record(
                        eventType: 'ip.assigned',
                        reason: "Assigned {$ipRecord->address_text} in VRF #{$subnet->vrf_id} to interface #{$interfaceId}",
                        subject: $ipRecord,
                        after: $ipRecord->toArray(),
                        actor: $actor
                    );

                    return $ipRecord;
                });
            } catch (ValidationException $ve) {
                throw $ve;
            } catch (Exception $e) {
                if ($attempt >= $maxRetries) {
                    throw ValidationException::withMessages([
                        'ip_address' => "Failed to allocate IP address due to concurrency conflict: {$e->getMessage()}",
                    ]);
                }
                usleep(rand(50000, 150000)); // 50-150ms backoff jitter
            }
        }

        throw ValidationException::withMessages([
            'ip_address' => 'Concurrent allocation failure; retry limit exceeded.',
        ]);
    }
}
