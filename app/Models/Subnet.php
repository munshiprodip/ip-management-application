<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\AddressSpace\Enums\SubnetStatus;
use App\Domain\AddressSpace\Enums\SubnetType;
use App\Domain\AddressSpace\ValueObjects\IpPrefix;
use Brick\Math\BigInteger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subnet extends Model
{
    use HasFactory;

    protected $table = 'subnets';

    protected $fillable = [
        'site_id',
        'parent_id',
        'vlan_id',
        'name',
        'description',
        'cidr',
        'gateway',
        'subnet_mask',
        'ip_version',
        'network_hex',
        'start_hex',
        'end_hex',
        'prefix_length',
        'subnet_type',
        'status',
        'allocation_enabled',
    ];

    protected function casts(): array
    {
        return [
            'ip_version' => 'integer',
            'prefix_length' => 'integer',
            'subnet_type' => SubnetType::class,
            'status' => SubnetStatus::class,
            'allocation_enabled' => 'boolean',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Subnet::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Subnet::class, 'parent_id');
    }

    public function vlan(): BelongsTo
    {
        return $this->belongsTo(Vlan::class);
    }

    public function pools(): HasMany
    {
        return $this->hasMany(IpPool::class);
    }

    public function exclusions(): HasMany
    {
        return $this->hasMany(IpExclusion::class);
    }

    public function ipAddresses(): HasMany
    {
        return $this->hasMany(IpAddress::class);
    }

    /**
     * Calculate allocated address count.
     * Leaf subnets count active materialized IPs (assigned, reserved, quarantined).
     * Aggregate containers sum child active allocations without double-counting capacities.
     */
    public function allocatedCount(bool $recursive = false): int
    {
        if ($this->isLeaf()) {
            return $this->ipAddresses()
                ->whereIn('allocation_status', [
                    \App\Domain\AddressSpace\Enums\IpAllocationStatus::ASSIGNED,
                    \App\Domain\AddressSpace\Enums\IpAllocationStatus::RESERVED,
                    \App\Domain\AddressSpace\Enums\IpAllocationStatus::QUARANTINED,
                ])
                ->count();
        }

        $sum = 0;
        foreach ($this->children as $child) {
            $sum += $child->allocatedCount(true);
        }
        return $sum;
    }

    /**
     * Calculate strictly assigned address count (excluding reserved and quarantined).
     */
    public function assignedCount(bool $recursive = true): int
    {
        if ($this->isLeaf()) {
            return $this->ipAddresses()
                ->where('allocation_status', \App\Domain\AddressSpace\Enums\IpAllocationStatus::ASSIGNED)
                ->count();
        }

        $sum = 0;
        foreach ($this->children as $child) {
            $sum += $child->assignedCount(true);
        }
        return $sum;
    }

    public function toIpPrefix(): IpPrefix
    {
        return IpPrefix::parse($this->cidr);
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    public function isLeaf(): bool
    {
        return $this->children()->count() === 0;
    }

    public function isAggregate(): bool
    {
        return $this->subnet_type === SubnetType::AGGREGATE;
    }

    public function isAssignable(): bool
    {
        return $this->subnet_type->canAllocate() && $this->allocation_enabled && $this->status === SubnetStatus::ACTIVE;
    }

    public function totalAddresses(): BigInteger
    {
        return $this->toIpPrefix()->totalAddresses();
    }

    public function getTotalAddressesAttribute(): int
    {
        return (int) (string) $this->totalAddresses();
    }

    public function usableAddresses(): BigInteger
    {
        return $this->toIpPrefix()->usableAddresses();
    }

    public function totalAddressesFormatted(): string
    {
        $total = $this->totalAddresses();
        if ($this->ip_version === 6) {
            return $total->isGreaterThan('18446744073709551616')
                ? '2^' . (128 - $this->prefix_length) . ' (' . $total->toBase(10)[0] . 'e+' . (strlen($total->toBase(10)) - 1) . ')'
                : number_format((float) (string) $total);
        }

        return number_format((int) (string) $total);
    }

    public function usableAddressesFormatted(): string
    {
        $usable = $this->usableAddresses();
        if ($this->ip_version === 6) {
            return $usable->isGreaterThan('18446744073709551616')
                ? '2^' . (128 - $this->prefix_length) . ' - 1'
                : number_format((float) (string) $usable);
        }

        return number_format((int) (string) $usable);
    }

    public function netmask(): string
    {
        if (!empty($this->subnet_mask)) {
            return $this->subnet_mask;
        }

        $prefix = $this->toIpPrefix();
        if ($prefix->version() === 4) {
            $maskBits = $prefix->prefixLength();
            $maskInt = ($maskBits === 0) ? 0 : (~0 << (32 - $maskBits)) & 0xFFFFFFFF;
            return long2ip($maskInt);
        }

        return '/' . $prefix->prefixLength();
    }

    public function defaultGateway(): string
    {
        if (!empty($this->gateway)) {
            return $this->gateway;
        }

        return $this->toIpPrefix()->firstUsableAddress()->toNormalizedString();
    }

    public function calculatedNetmask(): string
    {
        $prefix = $this->toIpPrefix();
        if ($prefix->version() === 4) {
            $maskBits = $prefix->prefixLength();
            $maskInt = ($maskBits === 0) ? 0 : (~0 << (32 - $maskBits)) & 0xFFFFFFFF;
            return long2ip($maskInt);
        }

        return '/' . $prefix->prefixLength();
    }

    public function calculatedGateway(): string
    {
        return $this->toIpPrefix()->firstUsableAddress()->toNormalizedString();
    }

    /**
     * Get ancestry chain from root down to this subnet.
     *
     * @return Collection<int, Subnet>
     */
    public function ancestors(): Collection
    {
        $ancestors = new Collection();
        $current = $this->parent;

        while ($current !== null) {
            $ancestors->prepend($current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    public function depth(): int
    {
        return $this->ancestors()->count();
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', SubnetStatus::ACTIVE);
    }
}
