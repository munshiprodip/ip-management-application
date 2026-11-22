<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Alerts\Enums\ExportStatus;
use App\Domain\Alerts\Enums\ExportType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'requested_by',
        'type',
        'format',
        'filters_json',
        'status',
        'private_file_path',
        'file_name',
        'row_count',
        'file_size_bytes',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => ExportType::class,
            'status' => ExportStatus::class,
            'filters_json' => 'array',
            'row_count' => 'integer',
            'file_size_bytes' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->where('status', ExportStatus::COMPLETED->value)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now());
    }
}
