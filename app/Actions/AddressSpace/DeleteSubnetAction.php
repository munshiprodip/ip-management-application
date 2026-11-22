<?php

declare(strict_types=1);

namespace App\Actions\AddressSpace;

use App\Models\AuditEvent;
use App\Models\Subnet;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteSubnetAction
{
    public function execute(Subnet $subnet): void
    {
        $childCount = $subnet->children()->count();
        if ($childCount > 0) {
            throw ValidationException::withMessages([
                'subnet' => "Cannot delete subnet '{$subnet->cidr}' because it has {$childCount} child subnet(s) in its hierarchy.",
            ]);
        }

        $ipCount = $subnet->ipAddresses()->count();
        if ($ipCount > 0) {
            throw ValidationException::withMessages([
                'subnet' => "Cannot delete subnet '{$subnet->cidr}' because {$ipCount} IP address record(s) are allocated within it.",
            ]);
        }

        DB::transaction(function () use ($subnet) {
            $before = $subnet->toArray();
            $subnet->delete();

            AuditEvent::record(
                eventType: 'subnet.deleted',
                reason: "Deleted subnet {$subnet->cidr} ({$subnet->name})",
                subject: $subnet,
                before: $before
            );
        });
    }
}
