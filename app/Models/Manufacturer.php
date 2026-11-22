<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Manufacturer extends Model
{
    use HasFactory;

    protected $table = 'manufacturers';

    protected $fillable = [
        'name',
        'slug',
        'website',
        'status',
    ];

    public function deviceModels(): HasMany
    {
        return $this->hasMany(DeviceModel::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}

