<?php

declare(strict_types=1);

namespace App\Actions\DeviceInventory;

use App\Domain\DeviceInventory\Enums\DeviceCriticality;
use App\Domain\DeviceInventory\Enums\DeviceStatus;
use App\Domain\DeviceInventory\Enums\InterfaceType;
use App\Models\AuditEvent;
use App\Models\Device;
use App\Models\DeviceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateDeviceAction
{
    /**
     * @param array{
     *     code: string,
     *     name: string,
     *     hostname?: ?string,
     *     device_type_id: int,
     *     manufacturer_id?: ?int,
     *     device_model_id?: ?int,
     *     asset_tag?: ?string,
     *     serial_number?: ?string,
     *     site_id: int,
     *     department_id?: ?int,
     *     location_id?: ?int,
     *     owner_user_id?: ?int,
     *     rack_location?: ?string,
     *     criticality?: string|DeviceCriticality,
     *     status?: string|DeviceStatus,
     *     environment?: string,
     *     os_firmware?: ?string,
     *     is_monitored?: bool,
     *     notes?: ?string,
     *     default_interface_name?: ?string,
     *     default_interface_mac?: ?string,
     *     default_interface_type?: string|InterfaceType
     * } $data
     */
    public function execute(array $data): Device
    {
        $code = strtoupper(trim($data['code']));

        if (Device::where('code', $code)->exists()) {
            throw ValidationException::withMessages([
                'code' => "A device with code '{$code}' already exists.",
            ]);
        }

        $assetTag = !empty($data['asset_tag']) ? trim($data['asset_tag']) : null;
        if ($assetTag !== null && Device::where('asset_tag', $assetTag)->exists()) {
            throw ValidationException::withMessages([
                'asset_tag' => "A device with asset tag '{$assetTag}' is already registered in the hospital inventory.",
            ]);
        }

        return DB::transaction(function () use ($data, $code, $assetTag) {
            $device = Device::create([
                'code' => $code,
                'name' => trim($data['name']),
                'hostname' => !empty($data['hostname']) ? trim($data['hostname']) : null,
                'device_type_id' => (int) $data['device_type_id'],
                'manufacturer_id' => !empty($data['manufacturer_id']) ? (int) $data['manufacturer_id'] : null,
                'device_model_id' => !empty($data['device_model_id']) ? (int) $data['device_model_id'] : null,
                'asset_tag' => $assetTag,
                'serial_number' => !empty($data['serial_number']) ? trim($data['serial_number']) : null,
                'site_id' => (int) $data['site_id'],
                'department_id' => !empty($data['department_id']) ? (int) $data['department_id'] : null,
                'location_id' => !empty($data['location_id']) ? (int) $data['location_id'] : null,
                'owner_user_id' => !empty($data['owner_user_id']) ? (int) $data['owner_user_id'] : null,
                'rack_location' => !empty($data['rack_location']) ? trim($data['rack_location']) : null,
                'criticality' => $data['criticality'] ?? DeviceCriticality::MEDIUM,
                'status' => $data['status'] ?? DeviceStatus::ACTIVE,
                'environment' => $data['environment'] ?? 'production',
                'os_firmware' => !empty($data['os_firmware']) ? trim($data['os_firmware']) : null,
                'is_monitored' => $data['is_monitored'] ?? true,
                'notes' => $data['notes'] ?? null,
            ]);

            // Optional default interface creation
            if (!empty($data['default_interface_name'])) {
                $device->interfaces()->create([
                    'name' => trim($data['default_interface_name']),
                    'mac_address' => $data['default_interface_mac'] ?? null,
                    'type' => $data['default_interface_type'] ?? InterfaceType::PHYSICAL,
                    'status' => 'up',
                ]);
            }

            AuditEvent::record(
                eventType: 'device.created',
                reason: "Registered new hardware device '{$device->name}' [{$device->code}]",
                subject: $device,
                after: $device->toArray()
            );

            return $device;
        });
    }
}
