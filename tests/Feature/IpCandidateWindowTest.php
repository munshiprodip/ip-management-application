<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\AddressSpace\AllocateIpAction;
use App\Actions\AddressSpace\CreateSubnetAction;
use App\Domain\AddressSpace\Enums\IpAllocationStatus;
use App\Domain\AddressSpace\Enums\SubnetStatus;
use App\Domain\AddressSpace\Enums\SubnetType;
use App\Models\Organization;
use App\Models\Site;
use App\Models\Subnet;
use App\Models\User;
use App\Models\Vrf;
use App\Services\AddressSpace\CandidateWindowGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IpCandidateWindowTest extends TestCase
{
    use RefreshDatabase;

    private Subnet $subnet;

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organization::create([
            'name' => 'KYAMCH Test Org',
            'code' => 'KYAMCH-TEST',
            'slug' => 'kyamch-test',
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
        $this->subnet = $createSubnet->execute([
            'vrf_id' => $vrf->id,
            'site_id' => $site->id,
            'cidr' => '192.168.10.0/24',
            'name' => 'Test Subnet',
            'subnet_type' => SubnetType::ASSIGNABLE->value,
            'status' => SubnetStatus::ACTIVE->value,
            'allocation_enabled' => true,
        ]);
    }

    public function test_candidate_window_generates_bounded_virtual_addresses(): void
    {
        $generator = app(CandidateWindowGenerator::class);

        // Window size 50 for /24 (254 usable addresses)
        $window = $generator->generateWindow($this->subnet, page: 1, perPage: 50);

        $this->assertCount(50, $window['items']);
        $this->assertEquals(254, $window['total']);
        $this->assertEquals(1, $window['current_page']);
        $this->assertEquals(6, $window['last_page']); // ceil(254/50) = 6

        // First usable address in /24 is .1
        $this->assertEquals('192.168.10.1', $window['items'][0]['address']);
        $this->assertEquals(IpAllocationStatus::UNASSIGNED, $window['items'][0]['allocation_status']);

        // 50th address is .50
        $this->assertEquals('192.168.10.50', $window['items'][49]['address']);
    }

    public function test_materialized_allocated_ip_reflected_in_candidate_window(): void
    {
        $allocateAction = app(AllocateIpAction::class);
        $generator = app(CandidateWindowGenerator::class);

        // Allocate 192.168.10.5
        $allocateAction->execute([
            'subnet_id' => $this->subnet->id,
            'ip_address' => '192.168.10.5',
            'hostname' => 'host05.kyamch.local',
        ]);

        $window = $generator->generateWindow($this->subnet, page: 1, perPage: 10);

        // Item 4 (index 4 corresponds to .5)
        $targetItem = $window['items'][4];
        $this->assertEquals('192.168.10.5', $targetItem['address']);
        $this->assertTrue($targetItem['is_materialized']);
        $this->assertEquals(IpAllocationStatus::ASSIGNED, $targetItem['allocation_status']);
        $this->assertEquals('host05.kyamch.local', $targetItem['hostname']);

        // Adjacent item .6 remains unassigned and virtual
        $adjacentItem = $window['items'][5];
        $this->assertEquals('192.168.10.6', $adjacentItem['address']);
        $this->assertFalse($adjacentItem['is_materialized']);
        $this->assertEquals(IpAllocationStatus::UNASSIGNED, $adjacentItem['allocation_status']);
    }

    public function test_pagination_bounds_and_last_page(): void
    {
        $generator = app(CandidateWindowGenerator::class);

        // Page 6 (last page of 254 usable addresses)
        $window = $generator->generateWindow($this->subnet, page: 6, perPage: 50);

        // Page 6 holds remaining 4 addresses (.251, .252, .253, .254)
        $this->assertCount(4, $window['items']);
        $this->assertEquals('192.168.10.251', $window['items'][0]['address']);
        $this->assertEquals('192.168.10.254', $window['items'][3]['address']);
    }
}

