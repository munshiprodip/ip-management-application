<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'integration_id',
        'sync_type',
        'status',
        'started_at',
        'ended_at',
        'items_scanned',
        'items_matched',
        'items_divergent',
        'summary_json',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'summary_json' => 'array',
            'items_scanned' => 'integer',
            'items_matched' => 'integer',
            'items_divergent' => 'integer',
        ];
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }
}
