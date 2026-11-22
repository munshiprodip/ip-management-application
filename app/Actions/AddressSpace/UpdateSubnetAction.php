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

class UpdateSubnetAction
{
    public function __construct(
        protected SubnetHierarchyService $hierarchyService
    ) {}

    /**
     * @param array{name?: string, description?: ?string, parent_id?: ?int, site_id?: ?int, department_id?: ?int, vlan_id?: ?int, subnet_type?: string|SubnetType, status?: string|SubnetStatus, allocation_enabled?: bool, scan_enabled?: bool} $data
     */
    public function execute(Subnet $subnet, array $data): Subnet
    {
        $parentId = array_key_exists('parent_id', $data)
            ? (!empty($data['parent_id']) ? (int) $data['parent_id'] : null)
            : $subnet->parent_id;

        // If parent is changed, validate hierarchy and acyclic rule (T-005)
        if ($parentId !== $subnet->parent_id && $parentId !== null) {
            $this->hierarchyService->validateNoCycle($subnet, $parentId);
            $this->hierarchyService->validateHierarchy($subnet->toIpPrefix(), $subnet->vrf_id, $parentId, $subnet->id);
        }

        return DB::transaction(function () use ($subnet, $data, $parentId) {
            $before = $subnet->toArray();

            $subnet->update(array_merge($data, [
                'parent_id' => $parentId,
            ]));

            if (array_key_exists('vlan_id', $data)) {
                if (!empty($data['vlan_id'])) {
                    $subnet->vlans()->sync([
                        $data['vlan_id'] => ['association_type' => 'primary'],
                    ]);
                } else {
                    $subnet->vlans()->detach();
                }
            }

            $after = $subnet->fresh()->toArray();

            AuditEvent::record(
                eventType: 'subnet.updated',
                reason: "Updated subnet {$subnet->cidr} ({$subnet->name})",
                subject: $subnet,
                before: $before,
                after: $after
            );

            return $subnet;
        });
    }
}
