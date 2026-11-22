<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\AddressSpace\AllocateIpAction;
use App\Actions\AddressSpace\CreateSubnetAction;
use App\Domain\AddressSpace\Enums\SubnetType;
use App\Livewire\IPAddresses\Index as IpAddressesIndex;
use App\Models\Subnet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireIpExplorerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Subnet $subnet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'network_admin',
            'status' => 'active',
        ]);

        $subnetAction = app(CreateSubnetAction::class);
        $this->subnet = $subnetAction->execute([
            'name' => 'Explorer Test Subnet',
            'cidr' => '10.50.1.0/24',
            'subnet_type' => SubnetType::ASSIGNABLE,
            'allocation_enabled' => true,
        ]);
    }

    public function test_livewire_ip_explorer_renders_candidate_window(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(IpAddressesIndex::class)
            ->assertStatus(200)
            ->assertSee('IP Address Explorer')
            ->assertSee('Allocations')
            ->assertSee('10.50.1.0/24')
            ->assertSee('10.50.1.1');
    }

    public function test_livewire_ip_explorer_can_allocate_ip(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(IpAddressesIndex::class)
            ->set('selectedSubnetId', $this->subnet->id)
            ->call('openAllocateModal', '10.50.1.15')
            ->set('alloc_hostname', 'lab-analyzer-01.kyamch.local')
            ->set('alloc_assigned_to', 'Bio-chemistry Analyzer 1')
            ->call('saveAllocation')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ip_addresses', [
            'address_text' => '10.50.1.15',
            'hostname' => 'lab-analyzer-01.kyamch.local',
            'allocation_status' => 'assigned',
        ]);
    }

    public function test_livewire_ip_explorer_respects_subnet_query_parameter(): void
    {
        $this->actingAs($this->admin);

        $action = app(\App\Actions\AddressSpace\CreateSubnetAction::class);
        $secondSubnet = $action->execute([
            'name' => 'Secondary ICU Subnet',
            'cidr' => '10.60.1.0/24',
            'subnet_type' => SubnetType::ASSIGNABLE,
            'allocation_enabled' => true,
        ]);

        Livewire::withQueryParams(['subnet' => $secondSubnet->id])
            ->test(IpAddressesIndex::class)
            ->assertStatus(200)
            ->assertSet('selectedSubnetId', $secondSubnet->id)
            ->assertSee('10.60.1.0/24')
            ->assertSee('10.60.1.1');
    }
}
