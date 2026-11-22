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

    public function test_livewire_ip_explorer_displays_mask_gateway_and_edits_assigned_to(): void
    {
        $this->actingAs($this->admin);

        // Render IP Explorer: should show Subnet Mask & Default Gateway, and not show Pool / Range
        $component = Livewire::test(IpAddressesIndex::class)
            ->assertStatus(200)
            ->set('selectedSubnetId', $this->subnet->id)
            ->assertSee('255.255.255.0')
            ->assertSee('10.50.1.1')
            ->assertSee('Subnet Mask')
            ->assertSee('Default Gateway')
            ->assertDontSee('Pool / Range');

        // Edit Assigned To for 10.50.1.20
        $component->call('openEditAssignmentModal', '10.50.1.20')
            ->assertSet('showEditModal', true)
            ->set('edit_assigned_to', 'Dr. Consulting Desk 3')
            ->set('edit_hostname', 'dr-desk-03.kyamch.local')
            ->call('saveAssignmentEdit')
            ->assertHasNoErrors()
            ->assertSet('showEditModal', false);

        $this->assertDatabaseHas('ip_addresses', [
            'address_text' => '10.50.1.20',
            'assigned_to' => 'Dr. Consulting Desk 3',
            'hostname' => 'dr-desk-03.kyamch.local',
        ]);
    }

    public function test_livewire_ip_explorer_manages_host_types_and_unquarantines(): void
    {
        $this->actingAs($this->admin);

        // 1. Allocate IP with HostType::MEDICAL
        $component = Livewire::test(IpAddressesIndex::class)
            ->set('selectedSubnetId', $this->subnet->id)
            ->call('openAllocateModal', '10.50.1.30')
            ->set('alloc_assigned_to', 'ICU Dialysis Station 1')
            ->set('alloc_hostname', 'dialysis-01.kyamch.local')
            ->set('alloc_host_type', 'medical')
            ->call('saveAllocation')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ip_addresses', [
            'address_text' => '10.50.1.30',
            'assigned_to' => 'ICU Dialysis Station 1',
            'host_type' => 'medical',
            'allocation_status' => 'assigned',
        ]);

        // Verify that the table renders the Medical Devices badge
        $component->assertSee('Medical devices');

        // 2. Release with quarantine
        $ip = \App\Models\IpAddress::where('address_text', '10.50.1.30')->firstOrFail();
        $releaseAction = app(\App\Actions\AddressSpace\ReleaseIpAction::class);
        $releaseAction->execute($ip, 'Device decommissioned', 5, $this->admin);

        // Refresh component to reflect external database change
        $component->call('$refresh')
            ->assertSee('Quarantined')
            ->assertSee('Make Unassigned');

        // 3. Click Make Unassigned
        $component->call('unquarantineIp', '10.50.1.30')
            ->assertHasNoErrors();

        $freshIp = $ip->fresh();
        $this->assertEquals(\App\Domain\AddressSpace\Enums\IpAllocationStatus::UNASSIGNED, $freshIp->allocation_status);
        $this->assertNull($freshIp->quarantined_until);
        $this->assertNull($freshIp->assigned_to);

        // 4. Update Host Type on another IP via Edit Modal
        $component->call('openEditAssignmentModal', '10.50.1.40')
            ->set('edit_assigned_to', 'Core Switch 01')
            ->set('edit_host_type', 'network')
            ->call('saveAssignmentEdit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ip_addresses', [
            'address_text' => '10.50.1.40',
            'assigned_to' => 'Core Switch 01',
            'host_type' => 'network',
        ]);
    }
}
