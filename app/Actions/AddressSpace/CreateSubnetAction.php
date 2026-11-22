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
     * @param array{cidr: string, name: string, parent_id?: ?int, vlan_id?: ?int, subnet_type?: string|SubnetType, status?: string|SubnetStatus, description?: ?string, allocation_enabled?: bool, gateway?: ?string, subnet_mask?: ?string} $data
     */
    public function execute(array $data): Subnet
    {
        $prefix = IpPrefix::parse($data['cidr']);
        $canonicalCidr = $prefix->cidr();
        $parentId = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;
        $siteId = !empty($data['site_id']) ? (int) $data['site_id'] : null;

        if ($siteId === null && $parentId !== null) {
            $parent = Subnet::find($parentId);
            if ($parent && $parent->site_id) {
                $siteId = $parent->site_id;
            }
        }

        $this->hierarchyService->validateHierarchy($prefix, $parentId);

        return DB::transaction(function () use ($data, $prefix, $canonicalCidr, $parentId, $siteId) {
            $subnet = Subnet::create([
                'site_id' => $siteId,
                'parent_id' => $parentId,
                'vlan_id' => !empty($data['vlan_id']) ? (int) $data['vlan_id'] : null,
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'cidr' => $canonicalCidr,
                'gateway' => !empty($data['gateway']) ? trim($data['gateway']) : null,
                'subnet_mask' => !empty($data['subnet_mask']) ? trim($data['subnet_mask']) : null,
                'ip_version' => $prefix->version(),
                'network_hex' => $prefix->networkHex(),
                'start_hex' => $prefix->startHex(),
                'end_hex' => $prefix->endHex(),
                'prefix_length' => $prefix->prefixLength(),
                'subnet_type' => $data['subnet_type'] ?? SubnetType::ASSIGNABLE,
                'status' => $data['status'] ?? SubnetStatus::ACTIVE,
                'allocation_enabled' => $data['allocation_enabled'] ?? true,
            ]);

            AuditEvent::record(
                eventType: 'subnet.created',
                reason: "Created subnet {$subnet->cidr} ({$subnet->name})",
                subject: $subnet,
                after: $subnet->toArray()
            );

            return $subnet;
        });
    }
}
