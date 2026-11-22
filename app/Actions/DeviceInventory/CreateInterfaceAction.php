<?php

declare(strict_types=1);

namespace App\Actions\DeviceInventory;

use App\Domain\DeviceInventory\Enums\InterfaceStatus;
use App\Domain\DeviceInventory\Enums\InterfaceType;
use App\Domain\DeviceInventory\ValueObjects\MacAddress;
use App\Models\AuditEvent;
use App\Models\Device;
use App\Models\DeviceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateInterfaceAction
{
    /**
     * @param array{
     *     name: string,
     *     type?: string|InterfaceType,
     *     mac_address?: ?string,
     *     if_index?: ?int,
     *     parent_interface_id?: ?int,
     *     vlan_id?: ?int,
     *     status?: string|InterfaceStatus,
     *     speed_mbps?: ?int,
     *     switch_port_label?: ?string,
     *     description?: ?string
     * } $data
     */
    public function execute(Device $device, array $data): DeviceInterface
    {
        $name = trim($data['name']);

        if ($device->interfaces()->where('name', $name)->exists()) {
            throw ValidationException::withMessages([
                'name' => "Interface '{$name}' already exists on device '{$device->name}'.",
            ]);
        }

        $mac = !empty($data['mac_address']) ? MacAddress::normalize($data['mac_address']) : null;

        return DB::transaction(function () use ($device, $data, $name, $mac) {
            $interface = $device->interfaces()->create([
                'name' => $name,
                'type' => $data['type'] ?? InterfaceType::PHYSICAL,
                'mac_address' => $mac,
                'if_index' => !empty($data['if_index']) ? (int) $data['if_index'] : null,
                'parent_interface_id' => !empty($data['parent_interface_id']) ? (int) $data['parent_interface_id'] : null,
                'vlan_id' => !empty($data['vlan_id']) ? (int) $data['vlan_id'] : null,
                'status' => $data['status'] ?? InterfaceStatus::UP,
                'speed_mbps' => !empty($data['speed_mbps']) ? (int) $data['speed_mbps'] : null,
                'switch_port_label' => !empty($data['switch_port_label']) ? trim($data['switch_port_label']) : null,
                'description' => $data['description'] ?? null,
            ]);

            AuditEvent::record(
                eventType: 'interface.created',
                reason: "Added interface '{$interface->name}' to device '{$device->name}'",
                subject: $interface,
                after: $interface->toArray()
            );

            return $interface;
        });
    }
}

