<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\AddressSpace\ValueObjects\IpAddress;
use Brick\Math\BigInteger;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class IpAddressTest extends TestCase
{
    public function test_parses_valid_ipv4_address(): void
    {
        $ip = IpAddress::parse('192.168.1.100');

        $this->assertEquals(4, $ip->version());
        $this->assertTrue($ip->isIpv4());
        $this->assertFalse($ip->isIpv6());
        $this->assertEquals('192.168.1.100', $ip->toNormalizedString());
        $this->assertEquals('c0a80164', $ip->toHex());
        $this->assertEquals('3232235876', (string) $ip->toBigInteger());
    }

    public function test_parses_valid_ipv6_address(): void
    {
        $ip = IpAddress::parse('2001:db8::1');

        $this->assertEquals(6, $ip->version());
        $this->assertFalse($ip->isIpv4());
        $this->assertTrue($ip->isIpv6());
        $this->assertEquals('2001:db8::1', $ip->toNormalizedString());
        $this->assertEquals('20010db8000000000000000000000001', $ip->toHex());
    }

    public function test_rejects_invalid_ip_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        IpAddress::parse('999.999.999.999');
    }

    public function test_rejects_malformed_ipv6_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        IpAddress::parse('2001:db8:::1');
    }

    public function test_recreates_from_hex_for_ipv4(): void
    {
        $ip = IpAddress::fromHex('0a0a0101', 4);

        $this->assertEquals('10.10.1.1', $ip->toNormalizedString());
        $this->assertEquals('0a0a0101', $ip->toHex());
    }

    public function test_recreates_from_hex_for_ipv6(): void
    {
        $ip = IpAddress::fromHex('20010db8000000000000000000000001', 6);

        $this->assertEquals('2001:db8::1', $ip->toNormalizedString());
        $this->assertEquals('20010db8000000000000000000000001', $ip->toHex());
    }

    public function test_rejects_invalid_hex_length(): void
    {
        $this->expectException(InvalidArgumentException::class);
        IpAddress::fromHex('0a0a', 4); // IPv4 needs 8 hex chars
    }

    public function test_supports_128_bit_biginteger_operations(): void
    {
        $base = IpAddress::parse('10.0.0.1');
        $next = $base->next();
        $this->assertEquals('10.0.0.2', $next->toNormalizedString());

        $plusTen = $base->add(10);
        $this->assertEquals('10.0.0.11', $plusTen->toNormalizedString());

        $prev = $plusTen->subtract(1);
        $this->assertEquals('10.0.0.10', $prev->toNormalizedString());
    }

    public function test_ipv6_addition_and_subtraction_without_overflow(): void
    {
        $base = IpAddress::parse('2001:db8::ffff');
        $next = $base->next();
        $this->assertEquals('2001:db8::1:0', $next->toNormalizedString());

        $prev = $next->previous();
        $this->assertEquals('2001:db8::ffff', $prev->toNormalizedString());
    }

    public function test_comparison_between_same_version(): void
    {
        $ip1 = IpAddress::parse('10.0.0.1');
        $ip2 = IpAddress::parse('10.0.0.2');
        $ip3 = IpAddress::parse('10.0.0.1');

        $this->assertEquals(-1, $ip1->compareTo($ip2));
        $this->assertEquals(1, $ip2->compareTo($ip1));
        $this->assertEquals(0, $ip1->compareTo($ip3));
    }

    public function test_comparison_between_different_versions_throws_exception(): void
    {
        $ipv4 = IpAddress::parse('10.0.0.1');
        $ipv6 = IpAddress::parse('::1');

        $this->expectException(InvalidArgumentException::class);
        $ipv4->compareTo($ipv6);
    }

    public function test_arithmetic_overflow_throws_exception(): void
    {
        $maxIpv4 = IpAddress::parse('255.255.255.255');

        $this->expectException(InvalidArgumentException::class);
        $maxIpv4->add(1);
    }

    public function test_arithmetic_underflow_throws_exception(): void
    {
        $zeroIpv4 = IpAddress::parse('0.0.0.0');

        $this->expectException(InvalidArgumentException::class);
        $zeroIpv4->subtract(1);
    }
}
