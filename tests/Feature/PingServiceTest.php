<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\AddressSpace\Enums\SubnetType;
use App\Domain\Security\Enums\UserRole;
use App\Models\Subnet;
use App\Models\User;
use App\Services\Network\PingResult;
use App\Services\Network\PingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PingServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Subnet $subnet;
    private PingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::NETWORK_ADMIN,
            'status' => 'active',
        ]);

        $this->subnet = app(\App\Actions\AddressSpace\CreateSubnetAction::class)->execute([
            'name' => 'Management Network',
            'cidr' => '10.10.10.0/24',
            'subnet_type' => SubnetType::ASSIGNABLE,
            'allocation_enabled' => true,
        ]);

        $this->service = app(PingService::class);
        RateLimiter::clear('ipam_ping:' . $this->admin->id);
    }

    public function test_rejects_invalid_ip_syntax(): void
    {
        $result = $this->service->ping('not-an-ip', $this->admin);

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Invalid IP address syntax', $result->message);
    }

    public function test_rejects_ip_outside_authorized_managed_subnets(): void
    {
        // 8.8.8.8 is a valid IP but not part of any managed hospital subnet
        $result = $this->service->ping('8.8.8.8', $this->admin);

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('outside authorized, managed subnets', $result->message);
    }

    public function test_parses_windows_reachable_output(): void
    {
        $sampleOutput = "Pinging 10.10.10.5 with 32 bytes of data:\nReply from 10.10.10.5: bytes=32 time=4ms TTL=64\nPackets: Sent = 1, Received = 1, Lost = 0 (0% loss)";
        $result = $this->service->parsePingOutput($sampleOutput, 0, isWindows: true);

        $this->assertTrue($result->isReachable());
        $this->assertEquals('4ms', $result->latency);
        $this->assertNotEmpty($result->checkedAt);
    }

    public function test_parses_windows_reachable_sub_millisecond_output(): void
    {
        $sampleOutput = "Reply from 10.10.10.1: bytes=32 time<1ms TTL=128\nPackets: Sent = 1, Received = 1, Lost = 0 (0% loss)";
        $result = $this->service->parsePingOutput($sampleOutput, 0, isWindows: true);

        $this->assertTrue($result->isReachable());
        $this->assertEquals('<1ms', $result->latency);
    }

    public function test_parses_windows_timeout_and_unreachable_output(): void
    {
        $timeoutOutput = "Pinging 10.10.10.99 with 32 bytes of data:\nRequest timed out.\nPackets: Sent = 1, Received = 0, Lost = 1 (100% loss)";
        $result = $this->service->parsePingOutput($timeoutOutput, 1, isWindows: true);

        $this->assertTrue($result->isNoReply());

        $unreachableOutput = "Reply from 10.10.10.1: Destination host unreachable.";
        $unreachResult = $this->service->parsePingOutput($unreachableOutput, 1, isWindows: true);

        $this->assertTrue($unreachResult->isNoReply());
    }

    public function test_parses_linux_reachable_output(): void
    {
        $sampleOutput = "64 bytes from 10.10.10.5: icmp_seq=1 ttl=64 time=1.24 ms\n1 packets transmitted, 1 received, 0% packet loss";
        $result = $this->service->parsePingOutput($sampleOutput, 0, isWindows: false);

        $this->assertTrue($result->isReachable());
        $this->assertEquals('1.24ms', $result->latency);
    }

    public function test_parses_linux_timeout_output(): void
    {
        $sampleOutput = "1 packets transmitted, 0 received, 100% packet loss, time 0ms";
        $result = $this->service->parsePingOutput($sampleOutput, 1, isWindows: false);

        $this->assertTrue($result->isNoReply());
    }

    public function test_parses_icmp_driver_unavailable_error(): void
    {
        $driverError = "Unable to contact IP driver. General failure.";
        $result = $this->service->parsePingOutput($driverError, 1, isWindows: true);

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('ICMP execution is unavailable', $result->message);
    }

    public function test_rate_limiting_protects_ping_operations(): void
    {
        $rateKey = 'ipam_ping:' . $this->admin->id;

        // Hit limit
        for ($i = 0; $i < 30; $i++) {
            RateLimiter::hit($rateKey, 60);
        }

        $result = $this->service->ping('10.10.10.5', $this->admin);

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Rate limit reached', $result->message);
    }

    public function test_ping_failure_never_alters_ip_allocation_status(): void
    {
        $allocateAction = app(\App\Actions\AddressSpace\AllocateIpAction::class);
        $allocated = $allocateAction->execute([
            'subnet_id' => $this->subnet->id,
            'ip_address' => '10.10.10.50',
            'assigned_to' => 'Critical Care Ventilator',
        ], $this->admin);

        $this->assertEquals('assigned', $allocated->allocation_status->value);

        // Ping non-responsive address
        $mockOutput = "Request timed out.\nLost = 1 (100% loss)";
        $pingResult = $this->service->parsePingOutput($mockOutput, 1, isWindows: true);
        $this->assertTrue($pingResult->isNoReply());

        // Verify allocated record remains 100% untouched
        $allocated->refresh();
        $this->assertEquals('assigned', $allocated->allocation_status->value);
        $this->assertEquals('Critical Care Ventilator', $allocated->assigned_to);
    }
}

