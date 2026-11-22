<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\AddressSpace\AllocateIpAction;
use App\Actions\AddressSpace\CreateSubnetAction;
use App\Domain\AddressSpace\Enums\IpAllocationStatus;
use App\Domain\AddressSpace\Enums\SubnetType;
use App\Models\Subnet;
use App\Models\User;
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

        $subnetAction = app(CreateSubnetAction::class);
        $this->subnet = $subnetAction->execute([
            'name' => 'Window Test Subnet',
            'cidr' => '192.168.10.0/24',
            'subnet_type' => SubnetType::ASSIGNABLE,
            'allocation_enabled' => true,
        ]);
    }

    public function test_candidate_window_generates_bounded_page_of_addresses(): void
    {
        $generator = app(CandidateWindowGenerator::class);
        $window = $generator->generateWindow($this->subnet, 1, 50);

        $this->assertEquals(254, $window['total']);
        $this->assertEquals(50, $window['per_page']);
        $this->assertEquals(1, $window['current_page']);
        $this->assertEquals(6, $window['last_page']);
        $this->assertCount(50, $window['items']);

        // First usable address in 192.168.10.0/24 is 192.168.10.1
        $this->assertEquals('192.168.10.1', $window['items'][0]['ip_text']);
        $this->assertEquals('192.168.10.50', $window['items'][49]['ip_text']);
    }

    public function test_candidate_window_page_2_offsets_correctly(): void
    {
        $generator = app(CandidateWindowGenerator::class);
        $window = $generator->generateWindow($this->subnet, 2, 50);

        $this->assertEquals(2, $window['current_page']);
        $this->assertEquals('192.168.10.51', $window['items'][0]['ip_text']);
        $this->assertEquals('192.168.10.100', $window['items'][49]['ip_text']);
    }

    public function test_materialized_allocated_ip_merges_status_into_window(): void
    {
        $admin = User::factory()->create(['role' => 'network_admin']);
        $allocateAction = app(AllocateIpAction::class);

        $allocateAction->execute([
            'subnet_id' => $this->subnet->id,
            'ip_address' => '192.168.10.5',
            'hostname' => 'nurse-desk-01.kyamch.local',
        ], $admin);

        $generator = app(CandidateWindowGenerator::class);
        $window = $generator->generateWindow($this->subnet, 1, 50);

        $item5 = null;
        foreach ($window['items'] as $item) {
            if ($item['ip_text'] === '192.168.10.5') {
                $item5 = $item;
                break;
            }
        }

        $this->assertNotNull($item5);
        $this->assertEquals(IpAllocationStatus::ASSIGNED, $item5['status']);
        $this->assertEquals('nurse-desk-01.kyamch.local', $item5['hostname']);
    }
}
