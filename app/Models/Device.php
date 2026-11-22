<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\DeviceInventory\Enums\DeviceCategory;
use App\Domain\DeviceInventory\Enums\DeviceCriticality;
use App\Domain\DeviceInventory\Enums\DeviceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    use HasFactory;

    protected $table = 'devices';

    protected $fillable = [
        'code',
        'hostname',
        'name',
        'device_type_id',
        'manufacturer_id',
        'device_model_id',
        'asset_tag',
        'serial_number',
        'site_id',
        'department_id',
        'location_id',
        'owner_user_id',
        'rack_location',
        'criticality',
        'status',
        'environment',
        'os_firmware',
        'is_monitored',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'criticality' => DeviceCriticality::class,
            'status' => DeviceStatus::class,
            'is_monitored' => 'boolean',
        ];
    }

    public function deviceType(): BelongsTo
    {
        return $this->belongsTo(DeviceType::class);
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function deviceModel(): BelongsTo
    {
        return $this->belongsTo(DeviceModel::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function interfaces(): HasMany
    {
        return $this->hasMany(DeviceInterface::class);
    }

    public function isMedical(): bool
    {
        return $this->deviceType?->category === DeviceCategory::MEDICAL;
    }

    public function isLifeSafety(): bool
    {
        return $this->criticality === DeviceCriticality::LIFE_SAFETY;
    }

    public function managementInterface(): ?DeviceInterface
    {
        return $this->interfaces()
            ->where('type', 'management')
            ->first() ?? $this->interfaces()->first();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', DeviceStatus::ACTIVE);
    }

    public function scopeCritical(Builder $query): Builder
    {
        return $query->whereIn('criticality', [
            DeviceCriticality::LIFE_SAFETY,
            DeviceCriticality::MISSION_CRITICAL,
        ]);
    }
}
