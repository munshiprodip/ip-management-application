<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\AddressSpace\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpReservation extends Model
{
    use HasFactory;

    protected $table = 'ip_reservations';

    protected $fillable = [
        'ip_address_id',
        'reserved_for_device_id',
        'reserved_by_user_id',
        'reason',
        'starts_at',
        'expires_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'status' => ReservationStatus::class,
        ];
    }

    public function ipAddress(): BelongsTo
    {
        return $this->belongsTo(IpAddress::class);
    }

    public function reservedForDevice(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'reserved_for_device_id');
    }

    public function reservedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reserved_by_user_id');
    }

    public function isActive(): bool
    {
        if ($this->status !== ReservationStatus::ACTIVE) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}

