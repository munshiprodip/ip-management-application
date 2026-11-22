<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditEvent extends Model
{
    public $timestamps = false;

    protected $table = 'audit_events';

    protected $fillable = [
        'actor_type',
        'actor_id',
        'actor_name',
        'event_type',
        'subject_type',
        'subject_id',
        'subject_label',
        'ip_address',
        'user_agent',
        'before_json',
        'after_json',
        'reason',
        'request_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'before_json' => 'array',
            'after_json' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeRecent(Builder $query, int $limit = 10): Builder
    {
        return $query->orderByDesc('id')->limit($limit);
    }

    /**
     * Record a tamper-evident, structured audit entry.
     *
     * @param string $eventType
     * @param string|null $reason
     * @param Model|null $subject
     * @param array<string, mixed>|null $before
     * @param array<string, mixed>|null $after
     * @param User|null $actor
     * @return static
     */
    public static function record(
        string $eventType,
        ?string $reason = null,
        ?Model $subject = null,
        ?array $before = null,
        ?array $after = null,
        ?User $actor = null,
    ): self {
        $actor = $actor ?? auth()->user();
        $request = request();

        return self::create([
            'actor_type' => $actor ? get_class($actor) : null,
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name ?? 'System/Anonymous',
            'event_type' => $eventType,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->getKey(),
            'subject_label' => $subject ? (string) ($subject->name ?? $subject->hostname ?? $subject->cidr ?? $subject->getKey()) : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 500) : null,
            'before_json' => $before,
            'after_json' => $after,
            'reason' => $reason,
            'request_id' => $request?->header('X-Request-Id') ?? (string) str()->uuid(),
            'created_at' => now(),
        ]);
    }
}
