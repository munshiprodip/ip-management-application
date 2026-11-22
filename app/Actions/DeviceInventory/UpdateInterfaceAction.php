<?php

declare(strict_types=1);

namespace App\Actions\DeviceInventory;

use App\Domain\DeviceInventory\ValueObjects\MacAddress;
use App\Models\AuditEvent;
use App\Models\DeviceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateInterfaceAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(DeviceInterface $interface, array $data): DeviceInterface
    {
        if (array_key_exists('name', $data)) {
            $name = trim($data['name']);
            $exists = DeviceInterface::where('device_id', $interface->device_id)
                ->where('name', $name)
                ->where('id', '!=', $interface->id)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'name' => "Interface '{$name}' already exists on this device.",
                ]);
            }
            $data['name'] = $name;
        }

        if (array_key_exists('mac_address', $data)) {
            $data['mac_address'] = !empty($data['mac_address'])
                ? MacAddress::normalize($data['mac_address'])
                : null;
        }

        return DB::transaction(function () use ($interface, $data) {
            $before = $interface->toArray();
            $interface->update($data);

            AuditEvent::record(
                eventType: 'interface.updated',
                reason: "Updated interface '{$interface->name}' on device #{$interface->device_id}",
                subject: $interface,
                before: $before,
                after: $interface->fresh()->toArray()
            );

            return $interface;
        });
    }
}
