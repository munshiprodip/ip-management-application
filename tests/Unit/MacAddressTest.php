<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\DeviceInventory\ValueObjects\MacAddress;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MacAddressTest extends TestCase
{
    public function test_normalizes_colon_formatted_mac(): void
    {
        $mac = MacAddress::parse('00:14:22:01:23:45');

        $this->assertNotNull($mac);
        $this->assertEquals('00:14:22:01:23:45', $mac->normalized());
        $this->assertEquals('001422012345', $mac->rawHex());
        $this->assertEquals('00:14:22', $mac->oui());
    }

    public function test_normalizes_dash_formatted_mac(): void
    {
        $mac = MacAddress::parse('00-14-22-01-23-45');

        $this->assertNotNull($mac);
        $this->assertEquals('00:14:22:01:23:45', $mac->normalized());
    }

    public function test_normalizes_cisco_dot_notation(): void
    {
        $mac = MacAddress::parse('0014.2201.2345');

        $this->assertNotNull($mac);
        $this->assertEquals('00:14:22:01:23:45', $mac->normalized());
    }

    public function test_normalizes_raw_hex_string(): void
    {
        $mac = MacAddress::parse('001422012345');

        $this->assertNotNull($mac);
        $this->assertEquals('00:14:22:01:23:45', $mac->normalized());
    }

    public function test_converts_lowercase_to_uppercase_normalized(): void
    {
        $mac = MacAddress::parse('aa:bb:cc:dd:ee:ff');

        $this->assertNotNull($mac);
        $this->assertEquals('AA:BB:CC:DD:EE:FF', $mac->normalized());
        $this->assertEquals('aabbccddeeff', $mac->rawHex());
    }

    public function test_rejects_invalid_mac_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MacAddress::parse('INVALID-MAC-ADDRESS');
    }

    public function test_rejects_short_mac_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MacAddress::parse('00:11:22');
    }

    public function test_detects_unicast_and_multicast(): void
    {
        // Unicast: first byte LSB is 0 (0x00 -> 0)
        $unicast = MacAddress::parse('00:14:22:01:23:45');
        $this->assertTrue($unicast->isUnicast());
        $this->assertFalse($unicast->isMulticast());

        // Multicast: first byte LSB is 1 (0x01 -> 1)
        $multicast = MacAddress::parse('01:00:5E:00:00:01');
        $this->assertTrue($multicast->isMulticast());
        $this->assertFalse($multicast->isUnicast());
    }

    public function test_equals_comparison(): void
    {
        $mac1 = MacAddress::parse('00:14:22:01:23:45');
        $mac2 = MacAddress::parse('0014.2201.2345');
        $mac3 = MacAddress::parse('11:22:33:44:55:66');

        $this->assertTrue($mac1->equals($mac2));
        $this->assertFalse($mac1->equals($mac3));
    }

    public function test_static_normalize_helper_handles_null(): void
    {
        $this->assertNull(MacAddress::normalize(null));
        $this->assertNull(MacAddress::normalize(''));
        $this->assertEquals('00:14:22:01:23:45', MacAddress::normalize('00-14-22-01-23-45'));
    }
}

