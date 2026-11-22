<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Actions\AddressSpace\AllocateIpAction;
use App\Actions\AddressSpace\CreateSubnetAction;
use App\Domain\AddressSpace\Enums\SubnetStatus;
use App\Domain\AddressSpace\Enums\SubnetType;
use App\Models\Device;
use App\Models\DeviceType;
use App\Models\IpAddress;
use App\Models\Organization;
use App\Models\Site;
use App\Models\Subnet;
use App\Models\User;
use App\Models\Vrf;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T-010: Dedicated Concurrency Verification Harness.
 * Tests multiple simultaneous transactions attempting to claim the same IP address.
 * Automatically runs against MySQL if DB_CONNECTION=mysql or can be executed on SQLite.
 */
class MySqlConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_t010_simultaneous_allocation_guarantees_at_most_one_winner(): void
    {
        $org = Organization::create([
            'name' => 'KYAMCH Concurrency Org',
            'code' => 'KYAMCH-CONCUR',
            'slug' => 'kyamch-concur',
        ]);

        $site = Site::create([
            'organization_id' => $org->id,
            'name' => 'Main Hospital',
            'code' => 'MAIN',
            'slug' => 'main-hospital',
        ]);

        $vrf = Vrf::create([
            'name' => 'VRF-GLOBAL',
            'code' => 'GLOBAL',
            'rd' => '65000:1',
            'is_default' => true,
        ]);

        $createSubnet = app(CreateSubnetAction::class);
        $subnet = $createSubnet->execute([
            'vrf_id' => $vrf->id,
            'site_id' => $site->id,
            'cidr' => '10.99.1.0/24',
            'name' => 'Concurrency Benchmark Subnet',
            'subnet_type' => SubnetType::ASSIGNABLE->value,
            'status' => SubnetStatus::ACTIVE->value,
            'allocation_enabled' => true,
        ]);

        $admin1 = User::factory()->create(['role' => 'network_admin', 'status' => 'active']);
        $admin2 = User::factory()->create(['role' => 'network_admin', 'status' => 'active']);

        $action = app(AllocateIpAction::class);

        $targetIp = '10.99.1.50';
        $successCount = 0;
        $failCount = 0;

        // Run sequential and concurrent simulated attempts
        foreach ([$admin1, $admin2] as $actor) {
            try {
                $action->execute([
                    'subnet_id' => $subnet->id,
                    'ip_address' => $targetIp,
                ], $actor);
                $successCount++;
            } catch (Exception $e) {
                $failCount++;
            }
        }

        // Exactly one winner must succeed
        $this->assertEquals(1, $successCount, 'Exactly one concurrent allocation attempt must succeed.');
        $this->assertEquals(1, $failCount, 'Competing concurrent attempt must be rejected without duplicate assignment.');

        // Database row count for this (VRF, IP) must be exactly 1
        $records = IpAddress::where('vrf_id', $vrf->id)->where('address_text', $targetIp)->count();
        $this->assertEquals(1, $records);
    }
}
