<?php

declare(strict_types=1);

namespace App\Services\AddressSpace;

use App\Domain\AddressSpace\ValueObjects\IpAddress as IpAddressVO;
use App\Models\IpExclusion;
use App\Models\IpPool;
use App\Models\Subnet;
use Illuminate\Validation\ValidationException;

class PoolValidationService
{
    /**
     * Validate that pool start and end addresses fall inside parent subnet.
     */
    public function validatePool(Subnet $subnet, string $startIp, string $endIp): void
    {
        $prefix = $subnet->toIpPrefix();
        $start = IpAddressVO::parse($startIp);
        $end = IpAddressVO::parse($endIp);

        if ($start->version() !== $subnet->ip_version || $end->version() !== $subnet->ip_version) {
            throw ValidationException::withMessages([
                'start_address' => "Address family mismatch for Subnet IPv{$subnet->ip_version}.",
            ]);
        }

        if ($start->compareTo($end) > 0) {
            throw ValidationException::withMessages([
                'start_address' => "Start address '{$startIp}' cannot be greater than end address '{$endIp}'.",
            ]);
        }

        if (! $prefix->containsIp($start) || ! $prefix->containsIp($end)) {
            throw ValidationException::withMessages([
                'start_address' => "Pool range {$startIp} - {$endIp} is outside subnet boundaries ({$subnet->cidr}).",
            ]);
        }
    }

    /**
     * Check if a requested IP address is excluded or part of a dynamic DHCP pool (T-009).
     */
    public function isExcludedOrDynamic(Subnet $subnet, IpAddressVO $ip): bool
    {
        $hex = $ip->toHex();

        // 1. Check exclusions
        $isExcluded = IpExclusion::where('subnet_id', $subnet->id)
            ->where('status', 'active')
            ->where('start_hex', '<=', $hex)
            ->where('end_hex', '>=', $hex)
            ->exists();

        if ($isExcluded) {
            return true;
        }

        // 2. Check dynamic DHCP pools (disallowing static candidate offering without approved workflow)
        $isDynamicPool = IpPool::where('subnet_id', $subnet->id)
            ->where('status', 'active')
            ->where('start_hex', '<=', $hex)
            ->where('end_hex', '>=', $hex)
            ->get()
            ->contains(fn (IpPool $p) => ! $p->type->allowsStaticAllocation());

        return $isDynamicPool;
    }
}

