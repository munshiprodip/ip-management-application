<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\AddressSpace\ValueObjects\IpAddress;
use App\Domain\AddressSpace\ValueObjects\IpPrefix;
use Brick\Math\BigInteger;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class IpPrefixTest extends TestCase
{
    /**
     * T-001: CIDR host bits normalization.
     * Host bits must be zeroed to guarantee base network canonicalization.
     */
    public function test_t001_canonicalizes_host_bits_for_ipv4_and_ipv6(): void
    {
        // 10.10.5.12/24 has host bit 12, canonical network is 10.10.5.0/24
        $ipv4 = IpPrefix::parse('10.10.5.12/24');
        $this->assertEquals('10.10.5.0/24', $ipv4->cidr());
        $this->assertEquals('10.10.5.0', $ipv4->networkAddress()->toNormalizedString());
        $this->assertEquals('10.10.5.255', $ipv4->lastAddress()->toNormalizedString());

        // 172.16.23.45/16 -> 172.16.0.0/16
        $ipv4ClassB = IpPrefix::parse('172.16.23.45/16');
        $this->assertEquals('172.16.0.0/16', $ipv4ClassB->cidr());

        // 2001:db8::beef/64 -> 2001:db8::/64
        $ipv6 = IpPrefix::parse('2001:db8::beef/64');
        $this->assertEquals('2001:db8::/64', $ipv6->cidr());
        $this->assertEquals('2001:db8::', $ipv6->networkAddress()->toNormalizedString());
    }

    /**
     * T-014: Handling of IPv4 /31 (RFC 3021) and /32 host routes.
     */
    public function test_t014_rfc3021_point_to_point_and_host_routes(): void
    {
        // /31 Point-to-Point (RFC 3021): Both addresses are usable, no broadcast address
        $p2p = IpPrefix::parse('192.168.10.0/31');
        $this->assertEquals(2, (int) (string) $p2p->totalAddresses());
        $this->assertEquals(2, (int) (string) $p2p->usableAddresses());
        $this->assertNull($p2p->broadcastAddress());
        $this->assertEquals('192.168.10.0', $p2p->firstUsableAddress()->toNormalizedString());
        $this->assertEquals('192.168.10.1', $p2p->lastUsableAddress()->toNormalizedString());

        // /32 Single Host: 1 total address, 1 usable, no broadcast
        $host = IpPrefix::parse('10.200.1.50/32');
        $this->assertEquals(1, (int) (string) $host->totalAddresses());
        $this->assertEquals(1, (int) (string) $host->usableAddresses());
        $this->assertNull($host->broadcastAddress());
        $this->assertEquals('10.200.1.50', $host->firstUsableAddress()->toNormalizedString());
        $this->assertEquals('10.200.1.50', $host->lastUsableAddress()->toNormalizedString());

        // Standard /24: 256 total, 254 usable, broadcast is .255
        $standard = IpPrefix::parse('192.168.1.0/24');
        $this->assertEquals(256, (int) (string) $standard->totalAddresses());
        $this->assertEquals(254, (int) (string) $standard->usableAddresses());
        $this->assertEquals('192.168.1.255', $standard->broadcastAddress()?->toNormalizedString());
        $this->assertEquals('192.168.1.1', $standard->firstUsableAddress()->toNormalizedString());
        $this->assertEquals('192.168.1.254', $standard->lastUsableAddress()->toNormalizedString());
    }

    /**
     * T-015: Handling of IPv6 /64, /127 (RFC 6164), and /128 without integer overflow.
     */
    public function test_t015_ipv6_prefix_handling_without_overflow(): void
    {
        // /127 inter-router link (RFC 6164): 2 total, 2 usable
        $p2p6 = IpPrefix::parse('2001:db8::10/127');
        $this->assertEquals(2, (int) (string) $p2p6->totalAddresses());
        $this->assertEquals(2, (int) (string) $p2p6->usableAddresses());
        $this->assertNull($p2p6->broadcastAddress());

        // /128 single address: 1 total, 1 usable
        $host6 = IpPrefix::parse('2001:db8::1/128');
        $this->assertEquals(1, (int) (string) $host6->totalAddresses());
        $this->assertEquals(1, (int) (string) $host6->usableAddresses());

        // Standard /64: 2^64 addresses (18446744073709551616)
        $standard6 = IpPrefix::parse('fd00:abcd::/64');
        $expectedTotal = BigInteger::of(2)->power(64);
        $this->assertTrue($standard6->totalAddresses()->isEqualTo($expectedTotal));
        // Subnet router anycast excluded -> 2^64 - 1
        $this->assertTrue($standard6->usableAddresses()->isEqualTo($expectedTotal->minus(1)));
    }

    public function test_performance_on_huge_prefixes_without_memory_allocation(): void
    {
        $startTime = microtime(true);

        // IPv4 /8: 16.7 million addresses
        $slash8 = IpPrefix::parse('10.0.0.0/8');
        $totalV4 = $slash8->totalAddresses();
        $this->assertEquals('16777216', (string) $totalV4);

        // IPv6 /32: 2^96 addresses
        $slash32 = IpPrefix::parse('2001:db8::/32');
        $totalV6 = $slash32->totalAddresses();
        $this->assertEquals(BigInteger::of(2)->power(96)->toBase(10), (string) $totalV6);

        $duration = microtime(true) - $startTime;
        // Must complete instantaneously in milliseconds, zero host enumeration
        $this->assertLessThan(0.05, $duration, 'Prefix calculations took too long; possible enumeration detected.');
    }

    public function test_containment_of_ip_addresses(): void
    {
        $prefix = IpPrefix::parse('10.10.0.0/16');

        $this->assertTrue($prefix->containsIp('10.10.0.1'));
        $this->assertTrue($prefix->containsIp('10.10.255.254'));
        $this->assertTrue($prefix->containsIp('10.10.0.0'));
        $this->assertTrue($prefix->containsIp('10.10.255.255'));

        $this->assertFalse($prefix->containsIp('10.11.0.1'));
        $this->assertFalse($prefix->containsIp('192.168.1.1'));
        $this->assertFalse($prefix->containsIp('2001:db8::1')); // wrong version
    }

    public function test_containment_of_child_prefixes(): void
    {
        $parent = IpPrefix::parse('10.10.0.0/16');
        $child1 = IpPrefix::parse('10.10.1.0/24');
        $child2 = IpPrefix::parse('10.10.254.0/23');
        $foreign = IpPrefix::parse('10.11.0.0/16');
        $larger = IpPrefix::parse('10.0.0.0/8');

        $this->assertTrue($parent->containsPrefix($child1));
        $this->assertTrue($parent->containsPrefix($child2));
        $this->assertFalse($parent->containsPrefix($foreign));
        $this->assertFalse($parent->containsPrefix($larger));

        // Self containment
        $this->assertTrue($parent->containsPrefix($parent));
        $this->assertFalse($parent->isProperAncestorOf($parent));
        $this->assertTrue($parent->isProperAncestorOf($child1));
    }

    public function test_prefix_overlap_detection(): void
    {
        $sub1 = IpPrefix::parse('10.10.1.0/24'); // 10.10.1.0 - 10.10.1.255
        $sub2 = IpPrefix::parse('10.10.1.128/25'); // overlaps inside sub1
        $sub3 = IpPrefix::parse('10.10.2.0/24'); // adjacent, does not overlap

        $this->assertTrue($sub1->overlapsPrefix($sub2));
        $this->assertTrue($sub2->overlapsPrefix($sub1));
        $this->assertFalse($sub1->overlapsPrefix($sub3));
    }

    public function test_split_creates_equal_sized_child_prefixes(): void
    {
        $prefix = IpPrefix::parse('192.168.1.0/24');
        $children = $prefix->split(26);

        // Splitting /24 into /26 should yield 4 blocks
        $this->assertCount(4, $children);
        $this->assertEquals('192.168.1.0/26', $children[0]->cidr());
        $this->assertEquals('192.168.1.64/26', $children[1]->cidr());
        $this->assertEquals('192.168.1.128/26', $children[2]->cidr());
        $this->assertEquals('192.168.1.192/26', $children[3]->cidr());
    }

    public function test_split_guard_prevents_excessive_cardinality(): void
    {
        $prefix = IpPrefix::parse('10.0.0.0/8');

        // Splitting /8 into /24 would yield 65,536 prefixes - must be rejected (> 8 levels)
        $this->expectException(InvalidArgumentException::class);
        $prefix->split(24);
    }
}
