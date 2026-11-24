<?php

declare(strict_types=1);

namespace App\Actions\Layer2;

use App\Models\AuditEvent;
use App\Models\Vlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteVlanAction
{
    public function execute(Vlan $vlan): void
    {
        $attachedCount = $vlan->subnets()->count();
        if ($attachedCount > 0) {
            throw ValidationException::withMessages([
                'vlan' => "Cannot delete VLAN {$vlan->vlan_id} ({$vlan->name}) because it is currently attached to {$attachedCount} subnet(s). Detach those subnets first.",
            ]);
        }

        DB::transaction(function () use ($vlan) {
            $before = $vlan->toArray();
            $vlan->delete();

            AuditEvent::record(
                eventType: 'vlan.deleted',
                reason: "Deleted VLAN {$vlan->vlan_id} ({$vlan->name})",
                before: $before
            );
        });
    }
}
