<?php

declare(strict_types=1);

namespace App\Services\AddressSpace;

use App\Domain\AddressSpace\ValueObjects\IpAddress;
use App\Domain\AddressSpace\ValueObjects\IpPrefix;
use App\Models\Subnet;
use Brick\Math\BigInteger;
use Illuminate\Validation\ValidationException;

class SubnetHierarchyService
{
    /**
     * Validate all parent/child containment and non-overlapping invariants.
     */
    public function validateHierarchy(
        IpPrefix $prefix,
        ?int $parentId = null,
        ?int $ignoreSubnetId = null
    ): void {
        // 1. Parent containment validation
        if ($parentId !== null) {
            $parent = Subnet::find($parentId);
            if (! $parent) {
                throw ValidationException::withMessages([
                    'parent_id' => "Parent subnet #{$parentId} does not exist.",
                ]);
            }

            if ($parent->ip_version !== $prefix->version()) {
                throw ValidationException::withMessages([
                    'cidr' => "Subnet address family (IPv{$prefix->version()}) does not match parent family (IPv{$parent->ip_version}).",
                ]);
            }

            $parentPrefix = $parent->toIpPrefix();

            if (! $parentPrefix->containsPrefix($prefix)) {
                throw ValidationException::withMessages([
                    'cidr' => "Child prefix {$prefix->cidr()} is outside parent prefix {$parentPrefix->cidr()}.",
                ]);
            }

            if ($parentPrefix->prefixLength() >= $prefix->prefixLength()) {
                throw ValidationException::withMessages([
                    'cidr' => "Child prefix length /{$prefix->prefixLength()} must be strictly longer than parent /{$parentPrefix->prefixLength()}.",
                ]);
            }
        }

        // 2. Overlapping siblings and illegal overlap detection within the same address family
        $conflicts = Subnet::where('ip_version', $prefix->version())
            ->when($ignoreSubnetId, fn ($q) => $q->where('id', '!=', $ignoreSubnetId))
            ->get();

        foreach ($conflicts as $existing) {
            $existingPrefix = $existing->toIpPrefix();

            if ($prefix->overlapsPrefix($existingPrefix)) {
                if ($parentId !== null && $existing->id === $parentId) {
                    continue;
                }

                if ($prefix->isProperAncestorOf($existingPrefix)) {
                    continue;
                }

                if ($existingPrefix->isProperAncestorOf($prefix)) {
                    if ($parentId === null) {
                        throw ValidationException::withMessages([
                            'cidr' => "Prefix {$prefix->cidr()} overlaps with existing subnet {$existingPrefix->cidr()}. Must declare parent relationship.",
                        ]);
                    }
                    continue;
                }

                throw ValidationException::withMessages([
                    'cidr' => "Prefix {$prefix->cidr()} illegally overlaps with existing subnet {$existingPrefix->cidr()} ({$existing->name}).",
                ]);
            }
        }
    }

    /**
     * Validate that setting a new parent does not create an ancestry cycle.
     */
    public function validateNoCycle(Subnet $subnet, int $newParentId): void
    {
        if ($subnet->id === $newParentId) {
            throw ValidationException::withMessages([
                'parent_id' => 'A subnet cannot be its own parent.',
            ]);
        }

        $current = Subnet::find($newParentId);
        while ($current !== null) {
            if ($current->id === $subnet->id) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Cycle detected: Cannot make a subnet a child of its own descendant.',
                ]);
            }
            $current = $current->parent;
        }
    }

    /**
     * Suggest next free child prefix inside a parent container.
     */
    public function findFreeChildPrefix(Subnet $parent, int $childPrefixLength): ?IpPrefix
    {
        $parentPrefix = $parent->toIpPrefix();
        if ($childPrefixLength <= $parentPrefix->prefixLength()) {
            return null;
        }

        $childBits = ($parent->ip_version === 4) ? (32 - $childPrefixLength) : (128 - $childPrefixLength);
        $childSpan = BigInteger::of(2)->power($childBits);

        $existingChildren = $parent->children()->orderBy('network_hex')->get();
        $occupiedHexRanges = $existingChildren->map(fn($c) => [$c->network_hex, $c->end_hex])->all();

        $currentInt = $parentPrefix->networkAddress()->toBigInteger();
        $endInt = $parentPrefix->broadcastAddress()->toBigInteger();

        while ($currentInt->plus($childSpan)->minus(1)->compareTo($endInt) <= 0) {
            $candidatePrefix = IpPrefix::fromBigIntegerAndLength($currentInt, $childPrefixLength, $parent->ip_version);
            $candidateStartHex = $candidatePrefix->networkHex();
            $candidateEndHex = $candidatePrefix->endHex();

            $isOccupied = false;
            foreach ($occupiedHexRanges as [$occStart, $occEnd]) {
                if (strcmp($candidateStartHex, $occEnd) <= 0 && strcmp($candidateEndHex, $occStart) >= 0) {
                    $isOccupied = true;
                    break;
                }
            }

            if (! $isOccupied) {
                return $candidatePrefix;
            }

            $currentInt = $currentInt->plus($childSpan);
        }

        return null;
    }
}
