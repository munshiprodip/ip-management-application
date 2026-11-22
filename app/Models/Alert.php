<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Alerts\Enums\AlertRuleCode;
use App\Domain\Alerts\Enums\AlertSeverity;
use App\Domain\Alerts\Enums\AlertStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'finding_id',
        'rule_code',
        'dedupe_key',
        'severity',
        'status',
        'title',
        'description',
        'context_json',
        'occurrence_count',
        'assignee_id',
        'opened_at',
        'last_occurred_at',
        'resolved_at',
        'resolution_note',
    ];

    protected function casts(): array
    {
        return [
            'rule_code' => AlertRuleCode::class,
            'severity' => AlertSeverity::class,
            'status' => AlertStatus::class,
            'context_json' => 'array',
            'occurrence_count' => 'integer',
            'opened_at' => 'datetime',
            'last_occurred_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(ReconciliationFinding::class, 'finding_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class, 'alert_id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [AlertStatus::OPEN, AlertStatus::ACKNOWLEDGED], true);
    }

    public function acknowledge(User $user): void
    {
        $this->update([
            'status' => AlertStatus::ACKNOWLEDGED,
            'assignee_id' => $user->id,
        ]);
    }

    public function resolve(User $user, string $note): void
    {
        $this->update([
            'status' => AlertStatus::RESOLVED,
            'resolved_at' => now(),
            'resolution_note' => $note,
            'assignee_id' => $user->id,
        ]);
    }

    public function suppress(User $user, string $note): void
    {
        $this->update([
            'status' => AlertStatus::SUPPRESSED,
            'resolved_at' => now(),
            'resolution_note' => $note,
            'assignee_id' => $user->id,
        ]);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [AlertStatus::OPEN->value, AlertStatus::ACKNOWLEDGED->value]);
    }

    public function scopeCritical(Builder $query): Builder
    {
        return $query->where('severity', AlertSeverity::CRITICAL->value);
    }

    public function scopeByRule(Builder $query, AlertRuleCode|string $code): Builder
    {
        $val = $code instanceof AlertRuleCode ? $code->value : $code;

        return $query->where('rule_code', $val);
    }
}
