<?php

declare(strict_types=1);

namespace App\Actions\Layer2;

use App\Models\AuditEvent;
use App\Models\Vlan;
use Illuminate\Support\Facades\DB;

class DeleteVlanAction
{
    public function execute(Vlan $vlan): void
    {
        DB::transaction(function () use ($vlan) {
            $before = $vlan->toArray();
            $vlan->delete();

            AuditEvent::record(
                eventType: 'vlan.deleted',
                reason: "Deleted VLAN {$vlan->vlan_id} ({$vlan->name}) from L2 domain #{$vlan->l2_domain_id}",
                before: $before
            );
        });
    }
}
