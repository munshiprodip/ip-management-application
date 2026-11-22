<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Layer2\Enums\VlanSecurityZone;
use App\Domain\Layer2\Enums\VlanStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vlan extends Model
{
    use HasFactory;

    protected $table = 'vlans';

    protected $fillable = [
        'site_id',
        'vlan_id',
        'name',
        'description',
        'security_zone',
        'status',
        'is_restricted',
    ];

    protected function casts(): array
    {
        return [
            'vlan_id' => 'integer',
            'security_zone' => VlanSecurityZone::class,
            'status' => VlanStatus::class,
            'is_restricted' => 'boolean',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function subnets(): HasMany
    {
        return $this->hasMany(Subnet::class);
    }

    public function isMedicalRestricted(): bool
    {
        if ($this->is_restricted) {
            return true;
        }

        return $this->security_zone instanceof VlanSecurityZone && $this->security_zone->isMedicalRestricted();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', VlanStatus::ACTIVE);
    }
}
