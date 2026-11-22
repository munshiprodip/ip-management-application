<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\AddressSpace\Enums\IpAllocationStatus;
use App\Domain\AddressSpace\Enums\IpClassification;
use App\Domain\AddressSpace\ValueObjects\IpAddress as IpAddressVO;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class IpAddress extends Model
{
    use HasFactory;

    protected $table = 'ip_addresses';

    protected $fillable = [
        'subnet_id',
        'ip_version',
        'address_hex',
        'address_text',
        'allocation_status',
        'classification',
        'hostname',
        'mac_address',
        'assigned_to',
        'description',
        'assigned_at',
        'quarantined_until',
        'quarantine_reason',
    ];

    protected function casts(): array
    {
        return [
            'ip_version' => 'integer',
            'allocation_status' => IpAllocationStatus::class,
            'classification' => IpClassification::class,
            'assigned_at' => 'datetime',
            'quarantined_until' => 'datetime',
        ];
    }

    public function subnet(): BelongsTo
    {
        return $this->belongsTo(Subnet::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(IpAssignment::class);
    }

    public function currentAssignment(): HasOne
    {
        return $this->hasOne(IpAssignment::class)->whereNull('released_at')->latestOfMany();
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(IpReservation::class);
    }

    public function isAssigned(): bool
    {
        return $this->allocation_status === IpAllocationStatus::ASSIGNED;
    }

    public function isAvailable(): bool
    {
        if ($this->allocation_status === IpAllocationStatus::UNASSIGNED) {
            return true;
        }

        if ($this->allocation_status === IpAllocationStatus::QUARANTINED && $this->quarantined_until !== null) {
            return $this->quarantined_until->isPast();
        }

        return false;
    }

    public function isQuarantined(): bool
    {
        if ($this->allocation_status !== IpAllocationStatus::QUARANTINED) {
            return false;
        }

        return $this->quarantined_until === null || $this->quarantined_until->isFuture();
    }

    public function toValueObject(): IpAddressVO
    {
        return IpAddressVO::parse($this->address_text);
    }
}
