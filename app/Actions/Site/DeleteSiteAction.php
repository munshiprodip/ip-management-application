<?php

declare(strict_types=1);

namespace App\Actions\Site;

use App\Models\AuditEvent;
use App\Models\Site;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteSiteAction
{
    public function execute(Site $site): void
    {
        $subnetCount = $site->subnets()->count();
        $vlanCount = $site->vlans()->count();

        if ($subnetCount > 0 || $vlanCount > 0) {
            throw ValidationException::withMessages([
                'site' => "Cannot delete campus '{$site->name}' because it has {$subnetCount} subnet(s) and {$vlanCount} VLAN(s) linked to it. Reassign or delete them first.",
            ]);
        }

        DB::transaction(function () use ($site) {
            $before = $site->toArray();

            AuditEvent::record(
                eventType: 'site.deleted',
                reason: "Deleted campus '{$site->name}' [{$site->code}]",
                subject: $site,
                before: $before
            );

            $site->delete();
        });
    }
}

