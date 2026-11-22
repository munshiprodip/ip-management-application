<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\DeviceInventory\Enums\InterfaceStatus;
use App\Domain\DeviceInventory\Enums\InterfaceType;
use App\Domain\DeviceInventory\ValueObjects\MacAddress;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceInterface extends Model
{
    use HasFactory;

    protected $table = 'device_interfaces';

    protected $fillable = [
        'device_id',
        'name',
        'if_index',
        'type',
        'mac_address',
        'parent_interface_id',
        'vlan_id',
        'status',
        'speed_mbps',
        'switch_port_label',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'type' => InterfaceType::class,
            'status' => InterfaceStatus::class,
            'if_index' => 'integer',
            'speed_mbps' => 'integer',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function parentInterface(): BelongsTo
    {
        return $this->belongsTo(DeviceInterface::class, 'parent_interface_id');
    }

    public function subInterfaces(): HasMany
    {
        return $this->hasMany(DeviceInterface::class, 'parent_interface_id');
    }

    public function vlan(): BelongsTo
    {
        return $this->belongsTo(Vlan::class);
    }

    public function fromLinks(): HasMany
    {
        return $this->hasMany(InterfaceLink::class, 'from_interface_id');
    }

    public function toLinks(): HasMany
    {
        return $this->hasMany(InterfaceLink::class, 'to_interface_id');
    }

    public function setMacAddressAttribute(?string $value): void
    {
        $this->attributes['mac_address'] = MacAddress::normalize($value);
    }

    public function getFormattedSpeedAttribute(): ?string
    {
        if ($this->speed_mbps === null) {
            return null;
        }

        if ($this->speed_mbps >= 1000) {
            return ($this->speed_mbps / 1000) . ' Gbps';
        }

        return $this->speed_mbps . ' Mbps';
    }
}

