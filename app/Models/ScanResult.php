<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanResult extends Model
{
    use HasFactory;

    protected $table = 'scan_results';

    protected $fillable = [
        'scan_job_id',
        'ip_address',
        'ip_hex',
        'status',
        'response_time_ms',
        'discovered_hostname',
        'discovered_mac',
        'ipam_status',
        'ipam_hostname',
        'ipam_assigned_to',
        'is_unregistered',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'response_time_ms' => 'float',
            'is_unregistered' => 'boolean',
            'checked_at' => 'datetime',
        ];
    }

    public function scanJob(): BelongsTo
    {
        return $this->belongsTo(ScanJob::class, 'scan_job_id');
    }

    public function isResponding(): bool
    {
        return $this->status === 'responding';
    }

    public function reachabilityBadge(): array
    {
        return match ($this->status) {
            'responding' => [
                'label' => 'Responding',
                'class' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                'dot' => 'bg-emerald-500',
            ],
            'no_reply' => [
                'label' => 'No Reply',
                'class' => 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 border-slate-200 dark:border-slate-700',
                'dot' => 'bg-slate-400',
            ],
            default => [
                'label' => 'Unknown / Error',
                'class' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300 border-rose-200 dark:border-rose-800',
                'dot' => 'bg-rose-500',
            ],
        };
    }

    public function ipamStatusBadge(): array
    {
        return match ($this->ipam_status) {
            'assigned' => [
                'label' => 'Assigned',
                'class' => 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300 border-blue-200 dark:border-blue-800',
            ],
            'reserved' => [
                'label' => 'Reserved',
                'class' => 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300 border-amber-200 dark:border-amber-800',
            ],
            'quarantined' => [
                'label' => 'Quarantined',
                'class' => 'bg-purple-50 text-purple-700 dark:bg-purple-950 dark:text-purple-300 border-purple-200 dark:border-purple-800',
            ],
            default => [
                'label' => 'Unassigned',
                'class' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700',
            ],
        };
    }
}

