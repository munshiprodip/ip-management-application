<?php

declare(strict_types=1);

namespace App\Services\AddressSpace;

use App\Domain\AddressSpace\Enums\IpAllocationStatus;
use App\Domain\AddressSpace\Enums\IpClassification;
use App\Domain\AddressSpace\ValueObjects\IpAddress as IpAddressVO;
use App\Models\IpAddress;
use App\Models\IpExclusion;
use App\Models\IpPool;
use App\Models\Subnet;

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

        // Cap pagination total for massive IPv6 subnets
        $cappedTotal = $totalUsable->isGreaterThan(100000) ? 100000 : (int) (string) $totalUsable;
        $lastPage = max(1, (int) ceil($cappedTotal / $perPage));
        $page = max(1, min($page, $lastPage));

        $offset = ($page - 1) * $perPage;
        $firstUsable = $prefix->firstUsableAddress();

        // Calculate window start and end IP addresses
        $windowStart = $firstUsable->add($offset);
        $windowCount = min($perPage, $cappedTotal - $offset);
        $windowEnd = $windowStart->add(max(0, $windowCount - 1));

        $startHex = $windowStart->toHex();
        $endHex = $windowEnd->toHex();

        // Fetch materialized DB records in this window for this Subnet
        $materialized = IpAddress::with('currentAssignment')
            ->where('subnet_id', $subnet->id)
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
            ->where('start_hex', '<=', $endHex)
            ->where('end_hex', '>=', $startHex)
            ->get();

        $items = [];
        $current = $windowStart;

        for ($i = 0; $i < $windowCount; $i++) {
            $hex = $current->toHex();
            $ipText = $current->toNormalizedString();

            if (isset($materialized[$hex])) {
                $record = $materialized[$hex];
                $status = $record->allocation_status;
                $classification = $record->classification;
                $hostType = $record->host_type;
                $hostname = $record->hostname;
                $macAddress = $record->mac_address;
                $assignedTo = $record->assigned_to;
                $dbId = $record->id;
                $quarantinedUntil = $record->quarantined_until;
            } else {
                $status = IpAllocationStatus::UNASSIGNED;
                $classification = IpClassification::STATIC_HOST;
                $hostType = null;
                $hostname = null;
                $macAddress = null;
                $assignedTo = null;
                $dbId = null;
                $quarantinedUntil = null;

                // Check if excluded
                foreach ($exclusions as $ex) {
                    if (strcmp($hex, $ex->start_hex) >= 0 && strcmp($hex, $ex->end_hex) <= 0) {
                        $status = IpAllocationStatus::BLOCKED;
                        $classification = IpClassification::NETWORK;
                        break;
                    }
                }
            }

            // Find matching pool
            $poolName = null;
            foreach ($pools as $pool) {
                if (strcmp($hex, $pool->start_hex) >= 0 && strcmp($hex, $pool->end_hex) <= 0) {
                    $poolName = $pool->name;
                    break;
                }
            }

            $items[] = [
                'id' => $dbId,
                'ip_text' => $ipText,
                'hex' => $hex,
                'status' => $status,
                'classification' => $classification,
                'host_type' => $hostType,
                'hostname' => $hostname,
                'mac_address' => $macAddress,
                'assigned_to' => $assignedTo,
                'netmask' => $subnet->netmask(),
                'gateway' => $subnet->defaultGateway(),
                'pool_name' => $poolName,
                'quarantined_until' => $quarantinedUntil,
            ];

            $current = $current->next();
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
