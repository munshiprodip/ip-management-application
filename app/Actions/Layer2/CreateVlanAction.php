<?php

declare(strict_types=1);

namespace App\Actions\Layer2;

use App\Domain\Layer2\Enums\VlanSecurityZone;
use App\Domain\Layer2\Enums\VlanStatus;
use App\Models\AuditEvent;
use App\Models\Vlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateVlanAction
{
    /**
     * @param array{l2_domain_id: int, vlan_id: int, name: string, description?: ?string, security_zone?: string|VlanSecurityZone, status?: string|VlanStatus} $data
     */
    public function execute(array $data): Vlan
    {
        $vlanId = (int) $data['vlan_id'];
        $l2DomainId = (int) $data['l2_domain_id'];

        if ($vlanId < 1 || $vlanId > 4094) {
            throw ValidationException::withMessages([
                'vlan_id' => 'VLAN ID must be an integer between 1 and 4094 (802.1Q standard).',
            ]);
        }

        // Critical Invariant: Unique per L2 Domain
        if (Vlan::where('l2_domain_id', $l2DomainId)->where('vlan_id', $vlanId)->exists()) {
            throw ValidationException::withMessages([
                'vlan_id' => "VLAN {$vlanId} already exists in this Layer 2 broadcast domain.",
            ]);
        }

        return DB::transaction(function () use ($data, $l2DomainId, $vlanId) {
            $vlan = Vlan::create([
                'l2_domain_id' => $l2DomainId,
                'vlan_id' => $vlanId,
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'security_zone' => $data['security_zone'] ?? VlanSecurityZone::INTERNAL,
                'status' => $data['status'] ?? VlanStatus::ACTIVE,
            ]);

            AuditEvent::record(
                eventType: 'vlan.created',
                reason: "Created VLAN {$vlan->vlan_id} ({$vlan->name}) in L2 Domain #{$vlan->l2_domain_id}",
                subject: $vlan,
                after: $vlan->toArray()
            );

            return $vlan;
        });
    }
}
