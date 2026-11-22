<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscoveryAgent extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'name',
        'code',
        'identity_ref',
        'api_key_hash',
        'enabled',
        'allowed_subnets_json',
        'protocols_json',
        'rate_limit_pps',
        'last_seen_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'allowed_subnets_json' => 'array',
            'protocols_json' => 'array',
            'rate_limit_pps' => 'integer',
            'last_seen_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(DiscoveryJob::class, 'agent_id');
    }

    public function isHealthy(): bool
    {
        if (! $this->enabled) {
            return false;
        }

        return $this->last_seen_at !== null && $this->last_seen_at->diffInMinutes(now()) <= 15;
    }

    public function isAllowedSubnet(Subnet $subnet): bool
    {
        $allowed = $this->allowed_subnets_json;

        // If not explicitly restricted, agent can scan assigned campus subnets
        if (empty($allowed)) {
            return true;
        }

        return in_array($subnet->id, $allowed, true) || in_array($subnet->cidr, $allowed, true);
    }

    public function recordHeartbeat(?string $error = null): void
    {
        $this->update([
            'last_seen_at' => now(),
            'last_error' => $error,
        ]);
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    public function scopeStale(Builder $query): Builder
    {
        return $query->where('enabled', true)
            ->where(function ($q) {
                $q->whereNull('last_seen_at')
                    ->orWhere('last_seen_at', '<', now()->subMinutes(15));
            });
    }
}
