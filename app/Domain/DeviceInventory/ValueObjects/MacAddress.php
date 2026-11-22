<?php

declare(strict_types=1);

namespace App\Domain\DeviceInventory\ValueObjects;

use InvalidArgumentException;

/**
 * Immutable Value Object for MAC addresses (EUI-48 / EUI-64).
 * Handles cross-vendor normalization (Cisco dot, IEEE dash, colon, raw) and validation.
 */
final class MacAddress
{
    private string $normalized;
    private string $rawHex;

    private function __construct(string $rawInput)
    {
        // Strip common delimiters (colons, dashes, dots, spaces)
        $clean = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', trim($rawInput)) ?? '');

        $len = strlen($clean);
        if ($len !== 12 && $len !== 16) {
            throw new InvalidArgumentException("Invalid MAC address length. Expected 12 hex chars (EUI-48) or 16 hex chars (EUI-64), got '{$rawInput}'");
        }

        $this->rawHex = strtolower($clean);

        // Format as colon-delimited pairs (e.g. 00:14:22:01:23:45)
        $pairs = str_split($clean, 2);
        $this->normalized = implode(':', $pairs);
    }

    public static function parse(?string $mac): ?self
    {
        if ($mac === null || trim($mac) === '') {
            return null;
        }

        return new self($mac);
    }

    public static function normalize(?string $mac): ?string
    {
        if ($mac === null || trim($mac) === '') {
            return null;
        }

        return (new self($mac))->normalized();
    }

    public function normalized(): string
    {
        return $this->normalized;
    }

    public function rawHex(): string
    {
        return $this->rawHex;
    }

    /**
     * Get the 3-octet Organizationally Unique Identifier (OUI).
     */
    public function oui(): string
    {
        return substr($this->normalized, 0, 8); // e.g. "00:14:22"
    }

    /**
     * Check if this is a multicast MAC address (least-significant bit of first octet is 1).
     */
    public function isMulticast(): bool
    {
        $firstByte = hexdec(substr($this->rawHex, 0, 2));

        return ($firstByte & 0x01) === 1;
    }

    /**
     * Check if this is a unicast MAC address.
     */
    public function isUnicast(): bool
    {
        return ! $this->isMulticast();
    }

    public function equals(self $other): bool
    {
        return $this->rawHex === $other->rawHex;
    }

    public function __toString(): string
    {
        return $this->normalized;
    }
}
