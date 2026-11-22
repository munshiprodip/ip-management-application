<?php

declare(strict_types=1);

namespace App\Actions\DeviceInventory;

use App\Models\AuditEvent;
use App\Models\DeviceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteInterfaceAction
{
    public function execute(DeviceInterface $interface): void
    {
        // Check for sub-interfaces
        if ($interface->subInterfaces()->count() > 0) {
            throw ValidationException::withMessages([
                'interface' => "Cannot delete interface '{$interface->name}' because it has active sub-interfaces.",
            ]);
        }

        // Check for active IP assignments
        if (DB::getSchemaBuilder()->hasTable('ip_assignments')) {
            $assignmentCount = DB::table('ip_assignments')
                ->where('interface_id', $interface->id)
                ->whereNull('released_at')
                ->count();

            if ($assignmentCount > 0) {
                throw ValidationException::withMessages([
                    'interface' => "Cannot delete interface '{$interface->name}' because an active IP address assignment is bound to it.",
                ]);
            }
        }

        DB::transaction(function () use ($interface) {
            $before = $interface->toArray();
            $interface->delete();

            AuditEvent::record(
                eventType: 'interface.deleted',
                reason: "Deleted interface '{$interface->name}' from device #{$interface->device_id}",
                subject: $interface,
                before: $before
            );
        });
    }
}

