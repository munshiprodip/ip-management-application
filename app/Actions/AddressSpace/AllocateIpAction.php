<?php

declare(strict_types=1);

namespace App\Actions\AddressSpace;

use App\Domain\AddressSpace\Enums\HostType;
use App\Domain\AddressSpace\Enums\IpAllocationStatus;
use App\Domain\AddressSpace\Enums\IpClassification;
use App\Domain\AddressSpace\ValueObjects\IpAddress as IpAddressVO;
use App\Models\AuditEvent;
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
     *     classification?: string|IpClassification,
     *     host_type?: ?string|HostType,
     *     hostname?: ?string,
     *     mac_address?: ?string,
     *     assigned_to?: ?string,
     *     description?: ?string,
     *     idempotency_key?: ?string,
     *     force_unquarantine?: ?bool
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

        // Resolve Host Type if provided (supports both Enum and dynamic DB slug)
        $hostType = null;
        if (!empty($data['host_type'])) {
            if ($data['host_type'] instanceof HostType) {
                $hostType = $data['host_type']->value;
            } elseif (is_string($data['host_type'])) {
                $hostType = trim($data['host_type']);
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

        // 3. Reject if in excluded range or dynamic pool
        if ($this->poolValidation->isExcludedOrDynamic($subnet, $ipVO)) {
            throw ValidationException::withMessages([
                'ip_address' => "Address {$ipVO->toNormalizedString()} falls within an excluded range or dynamic pool.",
            ]);
        }

        // 4. Bounded retry transaction with row locking
        $maxRetries = 3;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;
            try {
                return DB::transaction(function () use ($subnet, $ipVO, $data, $actor, $idempotencyKey, $hostType) {
                    $addressHex = $ipVO->toHex();

                    $ipRecord = IpAddress::where('subnet_id', $subnet->id)
                        ->where('address_hex', $addressHex)
                        ->lockForUpdate()
                        ->first();

                    if (! $ipRecord) {
                        $ipRecord = IpAddress::create([
                            'subnet_id' => $subnet->id,
                            'ip_version' => $subnet->ip_version,
                            'address_hex' => $addressHex,
                            'address_text' => $ipVO->toNormalizedString(),
                            'allocation_status' => IpAllocationStatus::UNASSIGNED,
                            'classification' => $data['classification'] ?? IpClassification::STATIC_HOST,
                            'host_type' => $hostType,
                            'hostname' => !empty($data['hostname']) ? trim($data['hostname']) : null,
                            'mac_address' => !empty($data['mac_address']) ? strtoupper(trim($data['mac_address'])) : null,
                            'assigned_to' => !empty($data['assigned_to']) ? trim($data['assigned_to']) : null,
                            'description' => $data['description'] ?? null,
                        ]);
                    }

                    if ($ipRecord->allocation_status === IpAllocationStatus::ASSIGNED) {
                        throw ValidationException::withMessages([
                            'ip_address' => "Address {$ipRecord->address_text} is already assigned.",
                        ]);
                    }

                    if ($ipRecord->allocation_status === IpAllocationStatus::RESERVED) {
                        throw ValidationException::withMessages([
                            'ip_address' => "Address {$ipRecord->address_text} is currently reserved.",
                        ]);
                    }

                    if ($ipRecord->isQuarantined()) {
                        if (!empty($data['force_unquarantine'])) {
                            $ipRecord->assignments()->whereNull('released_at')->update([
                                'released_at' => now(),
                                'release_reason' => 'Quarantine lifted for reallocation',
                            ]);
                        } else {
                            throw ValidationException::withMessages([
                                'ip_address' => "Address {$ipRecord->address_text} is currently held in post-release quarantine until {$ipRecord->quarantined_until?->toIso8601String()}.",
                            ]);
                        }
                    }

                    // Create assignment record
                    $assignment = IpAssignment::create([
                        'ip_address_id' => $ipRecord->id,
                        'assigned_to' => !empty($data['assigned_to']) ? trim($data['assigned_to']) : null,
                        'host_type' => $hostType ?? $ipRecord->host_type,
                        'hostname' => !empty($data['hostname']) ? trim($data['hostname']) : null,
                        'mac_address' => !empty($data['mac_address']) ? strtoupper(trim($data['mac_address'])) : null,
                        'assigned_at' => now(),
                        'assignment_method' => !empty($data['ip_address']) ? 'manual_static' : 'auto_first_free',
                        'assigned_by_user_id' => $actor?->id ?? auth()->id(),
                        'idempotency_key' => $idempotencyKey,
                        'notes' => $data['description'] ?? null,
                    ]);

                    // Update IP record
                    $ipRecord->update([
                        'allocation_status' => IpAllocationStatus::ASSIGNED,
                        'classification' => $data['classification'] ?? $ipRecord->classification,
                        'host_type' => $hostType ?? $ipRecord->host_type,
                        'hostname' => !empty($data['hostname']) ? trim($data['hostname']) : $ipRecord->hostname,
                        'mac_address' => !empty($data['mac_address']) ? strtoupper(trim($data['mac_address'])) : $ipRecord->mac_address,
                        'assigned_to' => !empty($data['assigned_to']) ? trim($data['assigned_to']) : $ipRecord->assigned_to,
                        'description' => $data['description'] ?? $ipRecord->description,
                        'assigned_at' => now(),
                        'quarantined_until' => null,
                        'quarantine_reason' => null,
                    ]);

                    AuditEvent::record(
                        eventType: 'ip.assigned',
                        reason: "Assigned {$ipRecord->address_text} to " . ($ipRecord->hostname ?? $ipRecord->assigned_to ?? 'Host'),
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
                        'ip_address' => "Failed to allocate IP address: {$e->getMessage()}",
                    ]);
                }
                usleep(rand(50000, 150000));
            }
        }

        throw ValidationException::withMessages([
            'ip_address' => 'Concurrent allocation failure; retry limit exceeded.',
        ]);
    }
}
