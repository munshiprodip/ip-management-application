<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'description',
        'timezone',
        'status',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function l2Domains(): HasMany
    {
        return $this->hasMany(L2Domain::class);
    }

    public function vrfs(): HasMany
    {
        return $this->hasMany(Vrf::class);
    }
}

