<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'token_hash',
        'token_prefix',
        'scopes_json',
        'rate_limit_per_minute',
        'last_used_at',
        'last_used_ip',
        'expires_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'scopes_json' => 'array',
            'rate_limit_per_minute' => 'integer',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generates a new API token with SHA-256 hash storage.
     * Returns plain token (shown once to operator) and the created model instance.
     *
     * @param array<string> $scopes
     * @return array{plain_token: string, token: ApiToken}
     */
    public static function createToken(
        string $name,
        array $scopes = ['*'],
        ?User $user = null,
        ?int $expiresInDays = 90,
        int $rateLimit = 60
    ): array {
        $prefix = 'kyamch_';
        $random = Str::random(40);
        $plainToken = $prefix . $random;
        $hash = hash('sha256', $plainToken);

        $model = static::create([
            'user_id' => $user?->id,
            'name' => $name,
            'token_hash' => $hash,
            'token_prefix' => substr($plainToken, 0, 12),
            'scopes_json' => $scopes,
            'rate_limit_per_minute' => $rateLimit,
            'expires_at' => $expiresInDays !== null ? now()->addDays($expiresInDays) : null,
        ]);

        return [
            'plain_token' => $plainToken,
            'token' => $model,
        ];
    }

    public static function findValidToken(string $plainToken): ?self
    {
        $hash = hash('sha256', $plainToken);

        /** @var self|null $token */
        $token = static::where('token_hash', $hash)->active()->first();

        return $token;
    }

    public function isValid(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function hasScope(string $requiredScope): bool
    {
        $scopes = $this->scopes_json ?? [];

        if (in_array('*', $scopes, true)) {
            return true;
        }

        return in_array($requiredScope, $scopes, true);
    }

    public function revoke(): void
    {
        $this->update(['revoked_at' => now()]);
    }

    public function recordUsage(string $ip): void
    {
        $this->update([
            'last_used_at' => now(),
            'last_used_ip' => $ip,
        ]);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
