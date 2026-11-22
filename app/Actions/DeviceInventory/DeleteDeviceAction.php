<?php

declare(strict_types=1);

namespace App\Actions\DeviceInventory;

use App\Models\AuditEvent;
use App\Models\Device;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteDeviceAction
{
    public function execute(Device $device): void
    {
        // 1. Invariant: Check if any interface on this device has active IP assignments
        if (DB::getSchemaBuilder()->hasTable('ip_assignments')) {
            $interfaceIds = $device->interfaces()->pluck('id');
            $assignmentCount = DB::table('ip_assignments')
                ->whereIn('interface_id', $interfaceIds)
                ->whereNull('released_at')
                ->count();

            if ($assignmentCount > 0) {
                throw ValidationException::withMessages([
                    'device' => "Cannot delete device '{$device->name}' because it has {$assignmentCount} active IP address assignment(s). Release IP addresses before removing device.",
                ]);
            }
        }

        DB::transaction(function () use ($device) {
            $before = $device->toArray();
            $device->delete();

            AuditEvent::record(
                eventType: 'device.deleted',
                reason: "Decommissioned and deleted device '{$device->name}' [{$device->code}]",
                subject: $device,
                before: $before
            );
        });
    }
}

