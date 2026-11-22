<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Discovery\Enums\DiscoverySourceType;
use App\Domain\Discovery\Enums\JobStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscoveryJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'subnet_id',
        'requested_by',
        'source_type',
        'status',
        'started_at',
        'finished_at',
        'metrics_json',
        'error_summary',
    ];

    protected function casts(): array
    {
        return [
            'status' => JobStatus::class,
            'source_type' => DiscoverySourceType::class,
            'metrics_json' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(DiscoveryAgent::class, 'agent_id');
    }

    public function subnet(): BelongsTo
    {
        return $this->belongsTo(Subnet::class, 'subnet_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function observations(): HasMany
    {
        return $this->hasMany(IpObservation::class, 'discovery_job_id');
    }
}
