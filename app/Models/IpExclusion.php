<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpExclusion extends Model
{
    use HasFactory;

    protected $table = 'ip_exclusions';

    protected $fillable = [
        'subnet_id',
        'ip_pool_id',
        'start_address',
        'end_address',
        'start_hex',
        'end_hex',
        'reason',
        'status',
    ];

    public function subnet(): BelongsTo
    {
        return $this->belongsTo(Subnet::class);
    }

    public function pool(): BelongsTo
    {
        return $this->belongsTo(IpPool::class, 'ip_pool_id');
    }
}

