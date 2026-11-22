<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\AddressSpace\Enums\VrfStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vrf extends Model
{
    use HasFactory;

    protected $table = 'vrfs';

    protected $fillable = [
        'code',
        'name',
        'description',
        'route_distinguisher',
        'site_id',
        'is_default',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'status' => VrfStatus::class,
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function isDefault(): bool
    {
        return $this->is_default === true;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', VrfStatus::ACTIVE);
    }

    /**
     * Get or create the mandatory non-null default VRF.
     */
    public static function getDefault(): self
    {
        $code = config('ipam.default_vrf_code', 'VRF-GLOBAL');
        $name = config('ipam.default_vrf_name', 'Default VRF');

        return self::firstOrCreate(
            ['is_default' => true],
            [
                'code' => $code,
                'name' => $name,
                'description' => 'Mandatory authoritative default routing domain for general hospital address space.',
                'status' => VrfStatus::ACTIVE,
            ]
        );
    }
}
