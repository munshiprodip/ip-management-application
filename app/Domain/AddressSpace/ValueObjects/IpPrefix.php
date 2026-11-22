<?php

declare(strict_types=1);

namespace App\Domain\AddressSpace\ValueObjects;

use Brick\Math\BigInteger;
use InvalidArgumentException;

/**
 * Immutable Value Object representing an IPv4 or IPv6 CIDR Prefix.
 * Guarantees host-bit canonicalization and 128-bit containment calculations.
 */
final class IpPrefix
{
    private IpAddress $networkAddress;
    private int $prefixLength;
    private int $version;
    private IpAddress $firstAddress;
    private IpAddress $lastAddress;

    private function __construct(IpAddress $networkAddress, int $prefixLength)
    {
        $this->version = $networkAddress->version();
        $maxPrefix = ($this->version === 4) ? 32 : 128;

        if ($prefixLength < 0 || $prefixLength > $maxPrefix) {
            throw new InvalidArgumentException("Prefix length must be between 0 and {$maxPrefix} for IPv{$this->version}. Got {$prefixLength}");
        }

        $this->prefixLength = $prefixLength;

        // Canonicalize: Clear host bits to guarantee base network representation
        $totalBits = $maxPrefix;
        $hostBits = $totalBits - $prefixLength;

        if ($hostBits === 0) {
            $canonicalInt = $networkAddress->toBigInteger();
            $lastInt = $canonicalInt;
        } else {
            // Divide and multiply by 2^hostBits to clear host bits without float/int overflow
            $hostSpan = BigInteger::of(2)->power($hostBits);
            $canonicalInt = $networkAddress->toBigInteger()->quotient($hostSpan)->multipliedBy($hostSpan);
            $lastInt = $canonicalInt->plus($hostSpan->minus(1));
        }

        $this->networkAddress = IpAddress::fromBigInteger($canonicalInt, $this->version);
        $this->firstAddress = $this->networkAddress;
        $this->lastAddress = IpAddress::fromBigInteger($lastInt, $this->version);
    }

    public static function parse(string $cidr): self
    {
        $parts = explode('/', trim($cidr));
        if (count($parts) !== 2) {
            throw new InvalidArgumentException("Invalid CIDR format '{$cidr}'. Expected format 'IP/prefix' (e.g. 10.0.0.0/16 or 2001:db8::/32)");
        }

        $ip = IpAddress::parse($parts[0]);
        $prefixLength = (int) $parts[1];

        return new self($ip, $prefixLength);
    }

    public static function create(string $ip, int $prefixLength): self
    {
        return new self(IpAddress::parse($ip), $prefixLength);
    }

    public function version(): int
    {
        return $this->version;
    }

    public function prefixLength(): int
    {
        return $this->prefixLength;
    }

    public function networkAddress(): IpAddress
    {
        return $this->networkAddress;
    }

    public function firstAddress(): IpAddress
    {
        return $this->firstAddress;
    }

    public function lastAddress(): IpAddress
    {
        return $this->lastAddress;
    }

    public function networkHex(): string
    {
        return $this->networkAddress->toHex();
    }

    public function startHex(): string
    {
        return $this->firstAddress->toHex();
    }

    public function endHex(): string
    {
        return $this->lastAddress->toHex();
    }

    public function cidr(): string
    {
        return $this->networkAddress->toNormalizedString() . '/' . $this->prefixLength;
    }

    public function broadcastAddress(): ?IpAddress
    {
        if ($this->version === 6 || $this->prefixLength >= 31) {
            return null; // IPv6, /31 and /32 have no broadcast address
        }

        return $this->lastAddress;
    }

    public function firstUsableAddress(): IpAddress
    {
        if ($this->version === 4) {
            if ($this->prefixLength === 31 || $this->prefixLength === 32) {
                return $this->firstAddress;
            }
            // Ordinary IPv4 subnets (/30 or larger) exclude network address
            return $this->firstAddress->next();
        }

        // IPv6: /127 and /128 can use base; larger subnets exclude subnet router anycast (::0)
        if ($this->prefixLength >= 127) {
            return $this->firstAddress;
        }

        return $this->firstAddress->next();
    }

    public function lastUsableAddress(): IpAddress
    {
        if ($this->version === 4) {
            if ($this->prefixLength === 31 || $this->prefixLength === 32) {
                return $this->lastAddress;
            }
            // Ordinary IPv4 subnets exclude broadcast address
            return $this->lastAddress->previous();
        }

        return $this->lastAddress;
    }

    public function totalAddresses(): BigInteger
    {
        $maxPrefix = ($this->version === 4) ? 32 : 128;
        $hostBits = $maxPrefix - $this->prefixLength;

        return BigInteger::of(2)->power($hostBits);
    }

    public function usableAddresses(): BigInteger
    {
        if ($this->version === 4) {
            if ($this->prefixLength === 32) {
                return BigInteger::of(1);
            }
            if ($this->prefixLength === 31) {
                return BigInteger::of(2); // RFC 3021 Point-to-Point links
            }
            // Ordinary IPv4: Total - 2 (network and broadcast)
            return $this->totalAddresses()->minus(2);
        }

        // IPv6
        if ($this->prefixLength === 128) {
            return BigInteger::of(1);
        }
        if ($this->prefixLength === 127) {
            return BigInteger::of(2); // RFC 6164 Inter-router links
        }

        // Ordinary IPv6: Total - 1 (excluding Subnet-Router Anycast address)
        return $this->totalAddresses()->minus(1);
    }

    /**
     * Check if this prefix wholly contains the given IP address.
     */
    public function containsIp(IpAddress|string $ip): bool
    {
        $addr = is_string($ip) ? IpAddress::parse($ip) : $ip;

        if ($addr->version() !== $this->version) {
            return false;
        }

        return $addr->toBigInteger()->isGreaterThanOrEqualTo($this->firstAddress->toBigInteger())
            && $addr->toBigInteger()->isLessThanOrEqualTo($this->lastAddress->toBigInteger());
    }

    /**
     * Check if this prefix wholly contains a child prefix.
     */
    public function containsPrefix(self $child): bool
    {
        if ($child->version !== $this->version) {
            return false;
        }

        return $this->prefixLength <= $child->prefixLength
            && $child->firstAddress->toBigInteger()->isGreaterThanOrEqualTo($this->firstAddress->toBigInteger())
            && $child->lastAddress->toBigInteger()->isLessThanOrEqualTo($this->lastAddress->toBigInteger());
    }

    /**
     * Check if this prefix is a proper ancestor (strictly larger and containing).
     */
    public function isProperAncestorOf(self $other): bool
    {
        return $this->prefixLength < $other->prefixLength && $this->containsPrefix($other);
    }

    /**
     * Check if two prefixes overlap (have any common address space).
     */
    public function overlapsPrefix(self $other): bool
    {
        if ($this->version !== $other->version) {
            return false;
        }

        return $this->firstAddress->toBigInteger()->isLessThanOrEqualTo($other->lastAddress->toBigInteger())
            && $this->lastAddress->toBigInteger()->isGreaterThanOrEqualTo($other->firstAddress->toBigInteger());
    }

    /**
     * Split this prefix into smaller equal-sized child prefixes.
     * Guaranteed no enumeration of individual hosts.
     *
     * @return array<self>
     */
    public function split(int $newPrefixLength): array
    {
        if ($newPrefixLength <= $this->prefixLength) {
            throw new InvalidArgumentException("New prefix length /{$newPrefixLength} must be greater than current /{$this->prefixLength}.");
        }

        $maxPrefix = ($this->version === 4) ? 32 : 128;
        if ($newPrefixLength > $maxPrefix) {
            throw new InvalidArgumentException("Prefix length cannot exceed {$maxPrefix}.");
        }

        $stepExponent = $maxPrefix - $newPrefixLength;
        $stepSize = BigInteger::of(2)->power($stepExponent);
        $totalChildrenExponent = $newPrefixLength - $this->prefixLength;

        // Cap split preview to 256 prefixes to prevent memory spikes in UI
        if ($totalChildrenExponent > 8) {
            throw new InvalidArgumentException("Splitting by more than 8 prefix levels (yielding >256 subnets) is not supported in a single operation.");
        }

        $totalChildren = 1 << $totalChildrenExponent;
        $children = [];
        $currentStart = $this->firstAddress->toBigInteger();

        for ($i = 0; $i < $totalChildren; $i++) {
            $childIp = IpAddress::fromBigInteger($currentStart, $this->version);
            $children[] = new self($childIp, $newPrefixLength);
            $currentStart = $currentStart->plus($stepSize);
        }

        return $children;
    }

    public function __toString(): string
    {
        return $this->cidr();
    }
}
