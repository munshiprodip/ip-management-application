<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\AddressSpace\Enums\AllocationStrategy;
use App\Domain\AddressSpace\Enums\PoolType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IpPool extends Model
{
    use HasFactory;

    protected $table = 'ip_pools';

    protected $fillable = [
        'subnet_id',
        'name',
        'description',
        'start_address',
        'end_address',
        'start_hex',
        'end_hex',
        'type',
        'allocation_strategy',
        'status',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'type' => PoolType::class,
            'allocation_strategy' => AllocationStrategy::class,
            'priority' => 'integer',
        ];
    }

    public function subnet(): BelongsTo
    {
        return $this->belongsTo(Subnet::class);
    }

    public function exclusions(): HasMany
    {
        return $this->hasMany(IpExclusion::class);
    }
}

