<?php

declare(strict_types=1);

namespace App\Actions\DeviceInventory;

use App\Models\AuditEvent;
use App\Models\Device;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateDeviceAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(Device $device, array $data): Device
    {
        if (array_key_exists('code', $data)) {
            $code = strtoupper(trim($data['code']));
            if (Device::where('code', $code)->where('id', '!=', $device->id)->exists()) {
                throw ValidationException::withMessages([
                    'code' => "Device code '{$code}' is already used by another device.",
                ]);
            }
            $data['code'] = $code;
        }

        if (array_key_exists('asset_tag', $data) && !empty($data['asset_tag'])) {
            $assetTag = trim($data['asset_tag']);
            if (Device::where('asset_tag', $assetTag)->where('id', '!=', $device->id)->exists()) {
                throw ValidationException::withMessages([
                    'asset_tag' => "Asset tag '{$assetTag}' is already registered to another device.",
                ]);
            }
            $data['asset_tag'] = $assetTag;
        }

        return DB::transaction(function () use ($device, $data) {
            $before = $device->toArray();
            $device->update($data);

            AuditEvent::record(
                eventType: 'device.updated',
                reason: "Updated device '{$device->name}' [{$device->code}]",
                subject: $device,
                before: $before,
                after: $device->fresh()->toArray()
            );

            return $device;
        });
    }
}

