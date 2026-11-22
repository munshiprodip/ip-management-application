<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\AddressSpace\Enums\IpAllocationStatus;
use App\Domain\AddressSpace\Enums\IpClassification;
use App\Domain\AddressSpace\Enums\IpConnectivityStatus;
use App\Domain\AddressSpace\ValueObjects\IpAddress as IpAddressVO;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IpAddress extends Model
{
    use HasFactory;

    protected $table = 'ip_addresses';

    protected $fillable = [
        'vrf_id',
        'subnet_id',
        'ip_version',
        'address_hex',
        'address_text',
        'allocation_status',
        'connectivity_status',
        'classification',
        'current_assignment_id',
        'hostname',
        'fqdn',
        'description',
        'quarantined_until',
        'quarantine_reason',
        'last_reconciled_at',
    ];

    protected function casts(): array
    {
        return [
            'ip_version' => 'integer',
            'allocation_status' => IpAllocationStatus::class,
            'connectivity_status' => IpConnectivityStatus::class,
            'classification' => IpClassification::class,
            'quarantined_until' => 'datetime',
            'last_reconciled_at' => 'datetime',
        ];
    }

    public function vrf(): BelongsTo
    {
        return $this->belongsTo(Vrf::class);
    }

    public function subnet(): BelongsTo
    {
        return $this->belongsTo(Subnet::class);
    }

    public function currentAssignment(): BelongsTo
    {
        return $this->belongsTo(IpAssignment::class, 'current_assignment_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(IpAssignment::class);
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

        // If in quarantine, check if quarantine expired
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

        if ($this->quarantined_until === null) {
            return true;
        }

        return ! $this->quarantined_until->isPast();
    }

    public function toValueObject(): IpAddressVO
    {
        return IpAddressVO::fromHex($this->address_hex, $this->ip_version);
    }
}
