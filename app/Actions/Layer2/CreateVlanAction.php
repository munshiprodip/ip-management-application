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
     * @param array{vlan_id: int, name: string, description?: ?string, security_zone?: string|VlanSecurityZone, status?: string|VlanStatus, is_restricted?: bool} $data
     */
    public function execute(array $data): Vlan
    {
        $vlanId = (int) $data['vlan_id'];

        if ($vlanId < 1 || $vlanId > 4094) {
            throw ValidationException::withMessages([
                'vlan_id' => 'VLAN ID must be an integer between 1 and 4094 (802.1Q standard).',
            ]);
        }

        if (Vlan::where('vlan_id', $vlanId)->exists()) {
            throw ValidationException::withMessages([
                'vlan_id' => "VLAN {$vlanId} already exists.",
            ]);
        }

        return DB::transaction(function () use ($data, $vlanId) {
            $vlan = Vlan::create([
                'site_id' => !empty($data['site_id']) ? (int) $data['site_id'] : null,
                'vlan_id' => $vlanId,
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'security_zone' => $data['security_zone'] ?? VlanSecurityZone::INTERNAL,
                'status' => $data['status'] ?? VlanStatus::ACTIVE,
                'is_restricted' => $data['is_restricted'] ?? false,
            ]);

            AuditEvent::record(
                eventType: 'vlan.created',
                reason: "Created VLAN {$vlan->vlan_id} ({$vlan->name})",
                subject: $vlan,
                after: $vlan->toArray()
            );

            return $vlan;
        });
    }
}
