<?php

declare(strict_types=1);

namespace App\Domain\AddressSpace\ValueObjects;

use Brick\Math\BigInteger;
use InvalidArgumentException;

/**
 * Immutable Value Object representing an IPv4 or IPv6 Address.
 * Provides exact 128-bit mathematical operations via Brick\Math\BigInteger.
 */
final class IpAddress
{
    private int $version;
    private string $bytes;
    private string $normalizedText;
    private string $hex;
    private BigInteger $integer;

    private function __construct(string $ip)
    {
        $trimmed = trim($ip);
        $binary = @inet_pton($trimmed);

        if ($binary === false) {
            throw new InvalidArgumentException("Invalid IPv4/IPv6 address: '{$ip}'");
        }

        $this->bytes = $binary;
        $byteLength = strlen($binary);

        if ($byteLength === 4) {
            $this->version = 4;
            $this->hex = str_pad(bin2hex($binary), 8, '0', STR_PAD_LEFT);
        } elseif ($byteLength === 16) {
            $this->version = 6;
            $this->hex = str_pad(bin2hex($binary), 32, '0', STR_PAD_LEFT);
        } else {
            throw new InvalidArgumentException("Unsupported address length of {$byteLength} bytes.");
        }

        $this->normalizedText = inet_ntop($binary);
        $this->integer = BigInteger::fromBase($this->hex, 16);
    }

    public static function parse(string $ip): self
    {
        return new self($ip);
    }

    public static function fromHex(string $hex, int $version): self
    {
        $hex = strtolower(trim($hex));
        $expectedLength = ($version === 4) ? 8 : 32;

        if (strlen($hex) !== $expectedLength) {
            throw new InvalidArgumentException("Expected fixed hex length of {$expectedLength} characters for IPv{$version}, got " . strlen($hex));
        }

        $binary = hex2bin($hex);
        if ($binary === false) {
            throw new InvalidArgumentException("Invalid hexadecimal sequence '{$hex}'");
        }

        $ipText = inet_ntop($binary);
        if ($ipText === false) {
            throw new InvalidArgumentException("Cannot convert hex '{$hex}' to IP string.");
        }

        return new self($ipText);
    }

    public static function fromBigInteger(BigInteger $int, int $version): self
    {
        $expectedLength = ($version === 4) ? 8 : 32;
        $hex = $int->toBase(16);
        $paddedHex = str_pad($hex, $expectedLength, '0', STR_PAD_LEFT);

        return self::fromHex($paddedHex, $version);
    }

    public function version(): int
    {
        return $this->version;
    }

    public function isIpv4(): bool
    {
        return $this->version === 4;
    }

    public function isIpv6(): bool
    {
        return $this->version === 6;
    }

    public function toHex(): string
    {
        return $this->hex;
    }

    public function toNormalizedString(): string
    {
        return $this->normalizedText;
    }

    public function toBigInteger(): BigInteger
    {
        return $this->integer;
    }

    public function add(int|string|BigInteger $offset): self
    {
        $newInt = $this->integer->plus(BigInteger::of($offset));
        $maxInt = ($this->version === 4)
            ? BigInteger::of(2)->power(32)->minus(1)
            : BigInteger::of(2)->power(128)->minus(1);

        if ($newInt->isLessThan(0) || $newInt->isGreaterThan($maxInt)) {
            throw new InvalidArgumentException("Address offset calculation out of bounds for IPv{$this->version}.");
        }

        return self::fromBigInteger($newInt, $this->version);
    }

    public function subtract(int|string|BigInteger $offset): self
    {
        return $this->add(BigInteger::of($offset)->negated());
    }

    public function next(): self
    {
        return $this->add(1);
    }

    public function previous(): self
    {
        return $this->subtract(1);
    }

    public function compareTo(self $other): int
    {
        if ($this->version !== $other->version) {
            throw new InvalidArgumentException("Cannot compare IPv{$this->version} with IPv{$other->version}.");
        }

        return $this->integer->compareTo($other->integer);
    }

    public function equals(self $other): bool
    {
        return $this->version === $other->version && $this->hex === $other->hex;
    }

    public function __toString(): string
    {
        return $this->toNormalizedString();
    }
}

