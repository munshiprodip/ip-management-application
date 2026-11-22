<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Discovery\Enums\DiscoverySourceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpObservation extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'discovery_job_id',
        'vrf_id',
        'subnet_id',
        'ip_address_id',
        'ip_version',
        'address_hex',
        'address_text',
        'observed_mac',
        'hostname',
        'source_type',
        'confidence',
        'observed_at',
        'payload_json',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'source_type' => DiscoverySourceType::class,
            'confidence' => 'integer',
            'observed_at' => 'datetime',
            'created_at' => 'datetime',
            'payload_json' => 'array',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(DiscoveryJob::class, 'discovery_job_id');
    }

    public function vrf(): BelongsTo
    {
        return $this->belongsTo(Vrf::class, 'vrf_id');
    }

    public function subnet(): BelongsTo
    {
        return $this->belongsTo(Subnet::class, 'subnet_id');
    }

    public function ipAddress(): BelongsTo
    {
        return $this->belongsTo(IpAddress::class, 'ip_address_id');
    }
}
