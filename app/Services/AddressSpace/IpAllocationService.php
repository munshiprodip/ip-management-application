<?php

declare(strict_types=1);

namespace App\Services\AddressSpace;

use App\Domain\AddressSpace\ValueObjects\IpAddress as IpAddressVO;
use App\Models\IpAddress;
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
        $stepLimit = 256;
        $checked = 0;

        while ($current->compareTo($end) <= 0 && $checked < $stepLimit) {
            $hex = $current->toHex();

            if (! $this->poolValidation->isExcludedOrDynamic($subnet, $current)) {
                $existing = IpAddress::where('subnet_id', $subnet->id)
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
