<?php

declare(strict_types=1);

namespace App\Actions\AddressSpace;

use App\Domain\AddressSpace\Enums\SubnetStatus;
use App\Domain\AddressSpace\Enums\SubnetType;
use App\Models\AuditEvent;
use App\Models\Subnet;
use App\Services\AddressSpace\SubnetHierarchyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

        if ($parentId !== $subnet->parent_id) {
            if ($parentId !== null) {
                $this->hierarchyService->validateNoCycle($subnet, $parentId);
            }
            $this->hierarchyService->validateHierarchy($subnet->toIpPrefix(), $parentId, $subnet->id);
        }

        $newType = isset($data['subnet_type'])
            ? ($data['subnet_type'] instanceof SubnetType ? $data['subnet_type'] : SubnetType::from($data['subnet_type']))
            : $subnet->subnet_type;

        if ($subnet->children()->exists() && $newType !== SubnetType::AGGREGATE) {
            throw ValidationException::withMessages([
                'subnet_type' => "Subnet {$subnet->cidr} has child subnets and must remain an Aggregate Container.",
            ]);
        }

        if ($subnet->ipAddresses()->where('allocation_status', 'assigned')->exists() && $newType === SubnetType::AGGREGATE) {
            throw ValidationException::withMessages([
                'subnet_type' => "Subnet {$subnet->cidr} has active assigned IP addresses and cannot be converted to an Aggregate Container.",
            ]);
        }

        return DB::transaction(function () use ($subnet, $data, $parentId, $newType) {
            $before = $subnet->toArray();

            $updatePayload = array_merge($data, [
                'parent_id' => $parentId,
                'vlan_id' => array_key_exists('vlan_id', $data) && !empty($data['vlan_id']) ? (int) $data['vlan_id'] : null,
                'subnet_type' => $newType,
                'allocation_enabled' => ($newType === SubnetType::AGGREGATE) ? false : ($data['allocation_enabled'] ?? $subnet->allocation_enabled),
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
