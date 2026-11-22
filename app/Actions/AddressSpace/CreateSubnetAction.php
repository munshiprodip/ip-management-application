<?php

declare(strict_types=1);

namespace App\Actions\AddressSpace;

use App\Domain\AddressSpace\Enums\SubnetStatus;
use App\Domain\AddressSpace\Enums\SubnetType;
use App\Domain\AddressSpace\ValueObjects\IpPrefix;
use App\Models\AuditEvent;
use App\Models\Subnet;
use App\Services\AddressSpace\SubnetHierarchyService;
use Illuminate\Support\Facades\DB;

class CreateSubnetAction
{
    public function __construct(
        protected SubnetHierarchyService $hierarchyService
    ) {}

    /**
     * @param array{vrf_id: int, cidr: string, name: string, parent_id?: ?int, site_id?: ?int, department_id?: ?int, vlan_id?: ?int, subnet_type?: string|SubnetType, status?: string|SubnetStatus, description?: ?string, allocation_enabled?: bool, scan_enabled?: bool} $data
     */
    public function execute(array $data): Subnet
    {
        // 1. Canonicalize CIDR (T-001: Zero host bits)
        $prefix = IpPrefix::parse($data['cidr']);
        $canonicalCidr = $prefix->cidr();

        $vrfId = (int) $data['vrf_id'];
        $parentId = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;

        // 2. Validate hierarchy, parent containment, and sibling non-overlap (T-002, T-003, T-004)
        $this->hierarchyService->validateHierarchy($prefix, $vrfId, $parentId);

        return DB::transaction(function () use ($data, $prefix, $canonicalCidr, $vrfId, $parentId) {
            $subnet = Subnet::create([
                'vrf_id' => $vrfId,
                'parent_id' => $parentId,
                'site_id' => $data['site_id'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'vlan_id' => $data['vlan_id'] ?? null,
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'cidr' => $canonicalCidr,
                'ip_version' => $prefix->version(),
                'network_hex' => $prefix->networkHex(),
                'start_hex' => $prefix->startHex(),
                'end_hex' => $prefix->endHex(),
                'prefix_length' => $prefix->prefixLength(),
                'subnet_type' => $data['subnet_type'] ?? SubnetType::ASSIGNABLE,
                'status' => $data['status'] ?? SubnetStatus::ACTIVE,
                'allocation_enabled' => $data['allocation_enabled'] ?? true,
                'scan_enabled' => $data['scan_enabled'] ?? false, // Safe default: discovery disabled
            ]);

            // Link VLAN if provided
            if (!empty($data['vlan_id'])) {
                $subnet->vlans()->syncWithoutDetaching([
                    $data['vlan_id'] => ['association_type' => 'primary'],
                ]);
            }

            AuditEvent::record(
                eventType: 'subnet.created',
                reason: "Created subnet {$subnet->cidr} ({$subnet->name}) in VRF #{$subnet->vrf_id}",
                subject: $subnet,
                after: $subnet->toArray()
            );

            return $subnet;
        });
    }
}

