<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    use HasFactory;

    protected $table = 'sites';

    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function subnets(): HasMany
    {
        return $this->hasMany(Subnet::class);
    }

    public function vlans(): HasMany
    {
        return $this->hasMany(Vlan::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

