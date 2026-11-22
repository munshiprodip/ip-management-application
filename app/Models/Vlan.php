<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Layer2\Enums\VlanSecurityZone;
use App\Domain\Layer2\Enums\VlanStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vlan extends Model
{
    use HasFactory;

    protected $table = 'vlans';

    protected $fillable = [
        'l2_domain_id',
        'vlan_id',
        'name',
        'description',
        'security_zone',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'vlan_id' => 'integer',
            'security_zone' => VlanSecurityZone::class,
            'status' => VlanStatus::class,
        ];
    }

    public function l2Domain(): BelongsTo
    {
        return $this->belongsTo(L2Domain::class);
    }

    public function isMedicalRestricted(): bool
    {
        return $this->security_zone instanceof VlanSecurityZone && $this->security_zone->isMedicalRestricted();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', VlanStatus::ACTIVE);
    }
}
