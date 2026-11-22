<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Integration extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'site_id',
        'config_json',
        'credential_ref',
        'enabled',
        'status',
        'last_sync_at',
        'last_sync_status',
    ];

    protected function casts(): array
    {
        return [
            'config_json' => 'array',
            'enabled' => 'boolean',
            'last_sync_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(IntegrationRun::class);
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }
}
