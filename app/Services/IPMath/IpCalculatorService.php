<?php

declare(strict_types=1);

namespace App\Services\IPMath;

use App\Domain\AddressSpace\ValueObjects\IpAddress;
use App\Domain\AddressSpace\ValueObjects\IpPrefix;

class IpCalculatorService
{
    /**
     * @return array<string, mixed>
     */
    public function calculate(string $cidr): array
    {
        $prefix = IpPrefix::parse($cidr);
        $total = $prefix->totalAddresses();
        $usable = $prefix->usableAddresses();

        $broadcast = $prefix->broadcastAddress();
        $firstUsable = $prefix->firstUsableAddress();
        $lastUsable = $prefix->lastUsableAddress();

        // Calculate IPv4 netmask string
        $netmask = null;
        $wildcard = null;
        if ($prefix->version() === 4) {
            $maskBits = $prefix->prefixLength();
            $maskInt = ($maskBits === 0) ? 0 : (~0 << (32 - $maskBits)) & 0xFFFFFFFF;
            $netmask = long2ip($maskInt);
            $wildcard = long2ip(~$maskInt & 0xFFFFFFFF);
        }

        return [
            'cidr' => $prefix->cidr(),
            'version' => $prefix->version(),
            'network' => $prefix->networkAddress()->toNormalizedString(),
            'prefix_length' => $prefix->prefixLength(),
            'netmask' => $netmask,
            'wildcard' => $wildcard,
            'broadcast' => $broadcast?->toNormalizedString(),
            'first_usable' => $firstUsable->toNormalizedString(),
            'last_usable' => $lastUsable->toNormalizedString(),
            'total_addresses' => (string) $total,
            'usable_addresses' => (string) $usable,
            'start_hex' => $prefix->startHex(),
            'end_hex' => $prefix->endHex(),
            'is_point_to_point' => ($prefix->version() === 4 && $prefix->prefixLength() === 31) || ($prefix->version() === 6 && $prefix->prefixLength() === 127),
            'is_host_route' => ($prefix->version() === 4 && $prefix->prefixLength() === 32) || ($prefix->version() === 6 && $prefix->prefixLength() === 128),
        ];
    }

    /**
     * Preview splitting a prefix into smaller blocks.
     *
     * @return array<int, array<string, mixed>>
     */
    public function previewSplit(string $cidr, int $newPrefixLength): array
    {
        $parent = IpPrefix::parse($cidr);
        $children = $parent->split($newPrefixLength);

        return array_map(function (IpPrefix $child) {
            return [
                'cidr' => $child->cidr(),
                'network' => $child->networkAddress()->toNormalizedString(),
                'prefix_length' => $child->prefixLength(),
                'start_hex' => $child->startHex(),
                'end_hex' => $child->endHex(),
                'usable_addresses' => (string) $child->usableAddresses(),
            ];
        }, $children);
    }
}

