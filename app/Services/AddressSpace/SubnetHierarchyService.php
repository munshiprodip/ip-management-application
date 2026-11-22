<?php

declare(strict_types=1);

namespace App\Services\AddressSpace;

use App\Domain\AddressSpace\ValueObjects\IpPrefix;
use App\Models\Subnet;
use Illuminate\Validation\ValidationException;

class SubnetHierarchyService
{
    /**
     * Validate all parent/child containment and non-overlapping invariants.
     */
    public function validateHierarchy(
        IpPrefix $prefix,
        int $vrfId,
        ?int $parentId = null,
        ?int $ignoreSubnetId = null
    ): void {
        // 1. Parent containment validation (T-002, T-003)
        if ($parentId !== null) {
            $parent = Subnet::find($parentId);
            if (! $parent) {
                throw ValidationException::withMessages([
                    'parent_id' => "Parent subnet #{$parentId} does not exist.",
                ]);
            }

            if ($parent->vrf_id !== $vrfId) {
                throw ValidationException::withMessages([
                    'parent_id' => "Parent subnet belongs to a different VRF context.",
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

        // 2. Overlapping siblings and illegal overlap detection within the same VRF (T-004)
        $conflicts = Subnet::where('vrf_id', $vrfId)
            ->where('ip_version', $prefix->version())
            ->when($ignoreSubnetId, fn ($q) => $q->where('id', '!=', $ignoreSubnetId))
            ->get();

        foreach ($conflicts as $existing) {
            $existingPrefix = $existing->toIpPrefix();

            // Check if prefixes have any overlapping address space
            if ($prefix->overlapsPrefix($existingPrefix)) {
                // If it is our designated parent, it is valid containment
                if ($parentId !== null && $existing->id === $parentId) {
                    continue;
                }

                // If this new prefix is a proper ancestor (or proper descendant) of an existing subnet,
                // verify that the hierarchy matches parent_id relationship.
                if ($prefix->isProperAncestorOf($existingPrefix)) {
                    // Allowed only if existing is a descendant, but for sibling/peer in same level it is prohibited
                    continue;
                }

                if ($existingPrefix->isProperAncestorOf($prefix)) {
                    // If existing is an ancestor, it MUST be the declared parent or an ancestor of parent
                    if ($parentId === null) {
                        throw ValidationException::withMessages([
                            'cidr' => "Prefix {$prefix->cidr()} overlaps with existing subnet {$existingPrefix->cidr()} in this VRF. Must declare parent relationship.",
                        ]);
                    }
                    continue;
                }

                // In all other cases (identical CIDR duplicate, or overlapping siblings/partial overlap):
                throw ValidationException::withMessages([
                    'cidr' => "Prefix {$prefix->cidr()} illegally overlaps with existing subnet {$existingPrefix->cidr()} ({$existing->name}) in this VRF.",
                ]);
            }
        }
    }

    /**
     * Validate that setting a new parent does not create an ancestry cycle (T-005).
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
                    'parent_id' => 'Cycle detected: this subnet is already an ancestor of the selected parent.',
                ]);
            }
            $current = $current->parent;
        }
    }

    /**
     * Find next available child prefix of a given length within a parent prefix.
     */
    public function findFreeChildPrefix(Subnet $parent, int $childPrefixLength): ?IpPrefix
    {
        $parentPrefix = $parent->toIpPrefix();
        $candidates = $parentPrefix->split($childPrefixLength);

        $existingChildren = $parent->children()->get()->map(fn (Subnet $s) => $s->toIpPrefix());

        foreach ($candidates as $candidate) {
            $isUsed = false;
            foreach ($existingChildren as $existing) {
                if ($candidate->overlapsPrefix($existing)) {
                    $isUsed = true;
                    break;
                }
            }

            if (! $isUsed) {
                return $candidate;
            }
        }

        return null;
    }
}

