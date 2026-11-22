<?php

declare(strict_types=1);

namespace App\Actions\Layer2;

use App\Domain\Layer2\Enums\VlanSecurityZone;
use App\Domain\Layer2\Enums\VlanStatus;
use App\Models\AuditEvent;
use App\Models\Vlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateVlanAction
{
    /**
     * @param array{vlan_id?: int, name?: string, description?: ?string, security_zone?: string|VlanSecurityZone, status?: string|VlanStatus} $data
     */
    public function execute(Vlan $vlan, array $data): Vlan
    {
        if (isset($data['vlan_id'])) {
            $vlanId = (int) $data['vlan_id'];
            if ($vlanId < 1 || $vlanId > 4094) {
                throw ValidationException::withMessages([
                    'vlan_id' => 'VLAN ID must be an integer between 1 and 4094.',
                ]);
            }

            if (Vlan::where('l2_domain_id', $vlan->l2_domain_id)
                ->where('vlan_id', $vlanId)
                ->where('id', '!=', $vlan->id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'vlan_id' => "VLAN {$vlanId} already exists in this Layer 2 broadcast domain.",
                ]);
            }
            $data['vlan_id'] = $vlanId;
        }

        return DB::transaction(function () use ($vlan, $data) {
            $before = $vlan->toArray();
            $vlan->update($data);
            $after = $vlan->fresh()->toArray();

            AuditEvent::record(
                eventType: 'vlan.updated',
                reason: "Updated VLAN {$vlan->vlan_id} ({$vlan->name})",
                subject: $vlan,
                before: $before,
                after: $after
            );

            return $vlan;
        });
    }
}

