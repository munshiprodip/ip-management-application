<?php

declare(strict_types=1);

namespace App\Actions\AddressSpace;

use App\Models\AuditEvent;
use App\Models\Vrf;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteVrfAction
{
    public function execute(Vrf $vrf): void
    {
        if ($vrf->isDefault()) {
            throw ValidationException::withMessages([
                'vrf' => 'The mandatory default VRF is permanent and cannot be deleted.',
            ]);
        }

        // Check if subnets are linked
        if (DB::getSchemaBuilder()->hasTable('subnets')) {
            $subnetCount = DB::table('subnets')->where('vrf_id', $vrf->id)->count();
            if ($subnetCount > 0) {
                throw ValidationException::withMessages([
                    'vrf' => "Cannot delete VRF '{$vrf->code}' because it has {$subnetCount} active subnet(s) attached.",
                ]);
            }
        }

        DB::transaction(function () use ($vrf) {
            $before = $vrf->toArray();
            $vrf->delete();

            AuditEvent::record(
                eventType: 'vrf.deleted',
                reason: "Deleted routing context {$vrf->code} ({$vrf->name})",
                before: $before
            );
        });
    }
}
