<?php

declare(strict_types=1);

namespace App\Actions\HostType;

use App\Models\AuditEvent;
use App\Models\HostType;
use App\Models\IpAddress;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class DeleteHostTypeAction
{
    public function execute(HostType $hostType, ?User $actor = null): bool
    {
        if ($hostType->is_system) {
            throw ValidationException::withMessages([
                'host_type' => "System host type '{$hostType->name}' cannot be deleted. You may deactivate it instead.",
            ]);
        }

        $allocatedCount = IpAddress::where('host_type', $hostType->slug)->count();
        if ($allocatedCount > 0) {
            throw ValidationException::withMessages([
                'host_type' => "Cannot delete '{$hostType->name}' because {$allocatedCount} IP address allocation(s) are currently categorized with this host type. Reassign or decommission those addresses first, or deactivate this type.",
            ]);
        }

        $before = $hostType->toArray();
        $name = $hostType->name;
        $slug = $hostType->slug;

        $hostType->delete();

        AuditEvent::record(
            eventType: 'host_type.deleted',
            reason: "Deleted host type '{$name}' ({$slug})",
            before: $before,
            actor: $actor
        );

        return true;
    }
}

