<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\DeviceInventory\Enums\DeviceCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceType extends Model
{
    use HasFactory;

    protected $table = 'device_types';

    protected $fillable = [
        'code',
        'name',
        'category',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'category' => DeviceCategory::class,
        ];
    }

    public function deviceModels(): HasMany
    {
        return $this->hasMany(DeviceModel::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}

