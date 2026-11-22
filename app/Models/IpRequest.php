<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Workflows\Enums\RequestPriority;
use App\Domain\Workflows\Enums\RequestStatus;
use App\Domain\Workflows\Enums\RequestType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class IpRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_number',
        'requester_id',
        'department_id',
        'request_type',
        'target_type',
        'target_id',
        'priority',
        'status',
        'payload_json',
        'reason',
        'rejection_reason',
        'cancellation_reason',
        'sla_due_at',
        'idempotency_key',
        'approved_at',
        'provisioned_at',
        'provisioned_by',
    ];

    protected function casts(): array
    {
        return [
            'request_type' => RequestType::class,
            'priority' => RequestPriority::class,
            'status' => RequestStatus::class,
            'payload_json' => 'array',
            'sla_due_at' => 'datetime',
            'approved_at' => 'datetime',
            'provisioned_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function provisioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provisioned_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(RequestApproval::class, 'request_id');
    }

    public function changeRecords(): HasMany
    {
        return $this->hasMany(ChangeRecord::class, 'request_id');
    }

    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    public function isPending(): bool
    {
        return in_array($this->status, [RequestStatus::SUBMITTED, RequestStatus::PENDING_APPROVAL], true);
    }

    public function isApproved(): bool
    {
        return $this->status === RequestStatus::APPROVED;
    }

    public function isProvisioned(): bool
    {
        return $this->status === RequestStatus::PROVISIONED;
    }

    public function isRejected(): bool
    {
        return $this->status === RequestStatus::REJECTED;
    }

    public function isCancelled(): bool
    {
        return $this->status === RequestStatus::CANCELLED;
    }

    public function isOverdue(): bool
    {
        if ($this->status->isTerminal()) {
            return false;
        }

        return $this->sla_due_at !== null && $this->sla_due_at->isPast();
    }

    public function isApproverEligible(User $user): bool
    {
        // Two-person rule (T-022): requester cannot approve own request
        if ($this->requester_id === $user->id) {
            return false;
        }

        return $user->isNetworkAdmin() || $user->isApprover();
    }

    /**
     * Scope query to only requests within the user's authorized scope (T-021).
     */
    public function scopeForScope(Builder $query, User $user): Builder
    {
        if ($user->isNetworkAdmin() || $user->isAuditor()) {
            return $query;
        }

        if ($user->isDepartmentOperator()) {
            $deptIds = $user->allowedDepartmentIds();

            return $query->where(function (Builder $q) use ($user, $deptIds) {
                $q->where('requester_id', $user->id);
                if (! empty($deptIds)) {
                    $q->orWhereIn('department_id', $deptIds);
                }
            });
        }

        return $query->where('requester_id', $user->id);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', [RequestStatus::SUBMITTED->value, RequestStatus::PENDING_APPROVAL->value]);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', RequestStatus::APPROVED->value);
    }

    public function scopeProvisioned(Builder $query): Builder
    {
        return $query->where('status', RequestStatus::PROVISIONED->value);
    }
}
