<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScanJob extends Model
{
    use HasFactory;

    protected $table = 'scan_jobs';

    protected $fillable = [
        'subnet_id',
        'user_id',
        'status',
        'total_targets',
        'scanned_count',
        'responding_count',
        'unregistered_count',
        'started_at',
        'completed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'total_targets' => 'integer',
            'scanned_count' => 'integer',
            'responding_count' => 'integer',
            'unregistered_count' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function subnet(): BelongsTo
    {
        return $this->belongsTo(Subnet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(ScanResult::class, 'scan_job_id');
    }

    public function isRunning(): bool
    {
        return $this->status === 'running' || $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function progressPercentage(): int
    {
        if ($this->total_targets <= 0) {
            return 0;
        }

        return (int) round(($this->scanned_count / $this->total_targets) * 100);
    }

    public function durationInSeconds(): ?int
    {
        if (! $this->started_at) {
            return null;
        }

        $end = $this->completed_at ?? now();
        return (int) $this->started_at->diffInSeconds($end);
    }
}

