<?php

declare(strict_types=1);

namespace App\Actions\AddressSpace;

use App\Domain\AddressSpace\Enums\SubnetStatus;
use App\Domain\AddressSpace\Enums\SubnetType;
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
     * @param array{name?: string, description?: ?string, parent_id?: ?int, vlan_id?: ?int, subnet_type?: string|SubnetType, status?: string|SubnetStatus, allocation_enabled?: bool, gateway?: ?string, subnet_mask?: ?string} $data
     */
    public function execute(Subnet $subnet, array $data): Subnet
    {
        $parentId = array_key_exists('parent_id', $data)
            ? (!empty($data['parent_id']) ? (int) $data['parent_id'] : null)
            : $subnet->parent_id;

        if ($parentId !== $subnet->parent_id && $parentId !== null) {
            $this->hierarchyService->validateNoCycle($subnet, $parentId);
            $this->hierarchyService->validateHierarchy($subnet->toIpPrefix(), $parentId, $subnet->id);
        }

        return DB::transaction(function () use ($subnet, $data, $parentId) {
            $before = $subnet->toArray();

            $updatePayload = array_merge($data, [
                'parent_id' => $parentId,
                'vlan_id' => array_key_exists('vlan_id', $data) && !empty($data['vlan_id']) ? (int) $data['vlan_id'] : null,
            ]);

            if (array_key_exists('gateway', $data)) {
                $updatePayload['gateway'] = !empty($data['gateway']) ? trim($data['gateway']) : null;
            }

            if (array_key_exists('subnet_mask', $data)) {
                $updatePayload['subnet_mask'] = !empty($data['subnet_mask']) ? trim($data['subnet_mask']) : null;
            }

            if (array_key_exists('site_id', $data)) {
                $updatePayload['site_id'] = !empty($data['site_id']) ? (int) $data['site_id'] : null;
            }

            $subnet->update($updatePayload);

            // If site_id changed on parent, cascade to immediate children without a site
            if (isset($updatePayload['site_id'])) {
                $subnet->children()->whereNull('site_id')->update(['site_id' => $updatePayload['site_id']]);
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
