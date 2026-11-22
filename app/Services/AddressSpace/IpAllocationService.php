<?php

declare(strict_types=1);

namespace App\Services\AddressSpace;

use App\Domain\AddressSpace\Enums\IpAllocationStatus;
use App\Domain\AddressSpace\ValueObjects\IpAddress as IpAddressVO;
use App\Models\IpAddress;
use App\Models\IpExclusion;
use App\Models\IpPool;
use App\Models\Subnet;

class IpAllocationService
{
    public function __construct(
        protected PoolValidationService $poolValidation
    ) {}

    /**
     * Locate the first available free candidate IP address in a subnet or pool.
     */
    public function findNextFreeCandidate(Subnet $subnet, ?IpPool $pool = null): ?IpAddressVO
    {
        $prefix = $subnet->toIpPrefix();

        if ($pool !== null) {
            $start = IpAddressVO::fromHex($pool->start_hex, $subnet->ip_version);
            $end = IpAddressVO::fromHex($pool->end_hex, $subnet->ip_version);
        } else {
            $start = $prefix->firstUsableAddress();
            $end = $prefix->lastUsableAddress();
        }

        $current = $start;
        $stepLimit = 256; // Check up to 256 sequential candidates per attempt
        $checked = 0;

        while ($current->compareTo($end) <= 0 && $checked < $stepLimit) {
            $hex = $current->toHex();

            // 1. Check if excluded or in non-static pool (T-009)
            if (! $this->poolValidation->isExcludedOrDynamic($subnet, $current)) {
                // 2. Check database for existing active allocation
                $existing = IpAddress::where('vrf_id', $subnet->vrf_id)
                    ->where('ip_version', $subnet->ip_version)
                    ->where('address_hex', $hex)
                    ->first();

                if (! $existing || $existing->isAvailable()) {
                    return $current;
                }
            }

            $current = $current->next();
            $checked++;
        }

        return null;
    }
}
