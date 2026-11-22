<?php

declare(strict_types=1);

namespace App\Actions\Layer2;

use App\Models\AuditEvent;
use App\Models\L2Domain;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteL2DomainAction
{
    public function execute(L2Domain $domain): void
    {
        $vlanCount = $domain->vlans()->count();
        if ($vlanCount > 0) {
            throw ValidationException::withMessages([
                'l2_domain' => "Cannot delete L2 domain '{$domain->code}' because it has {$vlanCount} active VLAN(s) attached.",
            ]);
        }

        DB::transaction(function () use ($domain) {
            $before = $domain->toArray();
            $domain->delete();

            AuditEvent::record(
                eventType: 'l2_domain.deleted',
                reason: "Deleted L2 Domain {$domain->code} ({$domain->name})",
                before: $before
            );
        });
    }
}
