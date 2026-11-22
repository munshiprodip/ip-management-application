<?php

declare(strict_types=1);

namespace App\Services\AddressSpace;

use App\Domain\AddressSpace\Enums\IpAllocationStatus;
use App\Domain\AddressSpace\Enums\IpClassification;
use App\Domain\AddressSpace\Enums\IpConnectivityStatus;
use App\Domain\AddressSpace\ValueObjects\IpAddress as IpAddressVO;
use App\Models\IpAddress;
use App\Models\IpExclusion;
use App\Models\IpPool;
use App\Models\Subnet;
use Brick\Math\BigInteger;
use Illuminate\Pagination\LengthAwarePaginator;

class CandidateWindowGenerator
{
    /**
     * Generate a virtual window of candidate addresses for IP Explorer.
     * Guarantees zero memory explosion by calculating window bounds using 128-bit arithmetic.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int, per_page: int, current_page: int, last_page: int}
     */
    public function generateWindow(Subnet $subnet, int $page = 1, int $perPage = 50): array
    {
        $prefix = $subnet->toIpPrefix();
        $totalUsable = $prefix->usableAddresses();

        // Cap pagination total for massive IPv6 subnets to prevent integer overflow in UI paginator
        $cappedTotal = $totalUsable->isGreaterThan(100000) ? 100000 : (int) (string) $totalUsable;
        $lastPage = (int) ceil($cappedTotal / $perPage);
        $page = max(1, min($page, $lastPage));

        $offset = ($page - 1) * $perPage;
        $firstUsable = $prefix->firstUsableAddress();

        // Calculate window start and end IP addresses
        $windowStart = $firstUsable->add($offset);
        $windowCount = min($perPage, $cappedTotal - $offset);
        $windowEnd = $windowStart->add(max(0, $windowCount - 1));

        $startHex = $windowStart->toHex();
        $endHex = $windowEnd->toHex();

        // Fetch materialized DB records in this window for this VRF
        $materialized = IpAddress::with(['currentAssignment.interface.device', 'currentAssignment.assignedBy'])
            ->where('vrf_id', $subnet->vrf_id)
            ->where('ip_version', $subnet->ip_version)
            ->whereBetween('address_hex', [$startHex, $endHex])
            ->get()
            ->keyBy('address_hex');

        // Fetch exclusions overlapping this window
        $exclusions = IpExclusion::where('subnet_id', $subnet->id)
            ->where('status', 'active')
            ->where('start_hex', '<=', $endHex)
            ->where('end_hex', '>=', $startHex)
            ->get();

        // Fetch pools overlapping this window
        $pools = IpPool::where('subnet_id', $subnet->id)
            ->where('status', 'active')
            ->where('start_hex', '<=', $endHex)
            ->where('end_hex', '>=', $startHex)
            ->get();

        $items = [];
        $currentIp = $windowStart;

        for ($i = 0; $i < $windowCount; $i++) {
            $hex = $currentIp->toHex();
            $text = $currentIp->toNormalizedString();

            if ($materialized->has($hex)) {
                /** @var IpAddress $dbIp */
                $dbIp = $materialized->get($hex);
                $items[] = [
                    'id' => $dbIp->id,
                    'address' => $text,
                    'hex' => $hex,
                    'is_materialized' => true,
                    'allocation_status' => $dbIp->allocation_status,
                    'connectivity_status' => $dbIp->connectivity_status,
                    'classification' => $dbIp->classification,
                    'hostname' => $dbIp->hostname,
                    'description' => $dbIp->description,
                    'current_assignment' => $dbIp->currentAssignment,
                    'quarantined_until' => $dbIp->quarantined_until,
                    'quarantine_reason' => $dbIp->quarantine_reason,
                    'exclusion' => null,
                    'pool' => null,
                ];
            } else {
                // Check if excluded
                $matchingExclusion = $exclusions->first(fn ($ex) => $hex >= $ex->start_hex && $hex <= $ex->end_hex);
                $matchingPool = $pools->first(fn ($p) => $hex >= $p->start_hex && $hex <= $p->end_hex);

                $allocStatus = $matchingExclusion
                    ? IpAllocationStatus::BLOCKED
                    : ($matchingPool && !$matchingPool->type->allowsStaticAllocation() ? IpAllocationStatus::BLOCKED : IpAllocationStatus::UNASSIGNED);

                $items[] = [
                    'id' => null,
                    'address' => $text,
                    'hex' => $hex,
                    'is_materialized' => false,
                    'allocation_status' => $allocStatus,
                    'connectivity_status' => IpConnectivityStatus::UNOBSERVED,
                    'classification' => $matchingExclusion ? IpClassification::GATEWAY : IpClassification::STATIC_HOST,
                    'hostname' => null,
                    'description' => $matchingExclusion?->reason ?? ($matchingPool ? $matchingPool->name : null),
                    'current_assignment' => null,
                    'quarantined_until' => null,
                    'quarantine_reason' => null,
                    'exclusion' => $matchingExclusion,
                    'pool' => $matchingPool,
                ];
            }

            if ($i < $windowCount - 1) {
                $currentIp = $currentIp->next();
            }
        }

        return [
            'items' => $items,
            'total' => $cappedTotal,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
        ];
    }
}

