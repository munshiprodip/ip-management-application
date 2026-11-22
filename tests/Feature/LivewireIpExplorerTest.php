<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\AddressSpace\AllocateIpAction;
use App\Actions\AddressSpace\CreateSubnetAction;
use App\Actions\DeviceInventory\CreateDeviceAction;
use App\Domain\AddressSpace\Enums\SubnetStatus;
use App\Domain\AddressSpace\Enums\SubnetType;
use App\Domain\DeviceInventory\Enums\DeviceCategory;
use App\Domain\Security\Enums\UserRole;
use App\Livewire\IPAddresses\Index as IpAddressesIndex;
use App\Models\Device;
use App\Models\DeviceType;
use App\Models\Organization;
use App\Models\Site;
use App\Models\Subnet;
use App\Models\User;
use App\Models\Vrf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireIpExplorerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Vrf $vrf;
    private Subnet $subnet;
    private Device $device;

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

        $this->vrf = Vrf::create([
            'name' => 'VRF-GLOBAL',
            'code' => 'GLOBAL',
            'rd' => '65000:1',
            'is_default' => true,
        ]);

        $createSubnet = app(CreateSubnetAction::class);
        $this->subnet = $createSubnet->execute([
            'vrf_id' => $this->vrf->id,
            'site_id' => $site->id,
            'cidr' => '10.10.10.0/24',
            'name' => 'Radiology Subnet',
            'subnet_type' => SubnetType::ASSIGNABLE->value,
            'status' => SubnetStatus::ACTIVE->value,
            'allocation_enabled' => true,
        ]);

        $this->admin = User::factory()->create([
            'role' => UserRole::NETWORK_ADMIN,
            'status' => 'active',
        ]);

        $devType = DeviceType::create([
            'code' => 'MED-CT',
            'name' => 'CT Scanner',
            'category' => DeviceCategory::MEDICAL,
        ]);

        $createDevice = app(CreateDeviceAction::class);
        $this->device = $createDevice->execute([
            'code' => 'RAD-CT01',
            'name' => 'GE Revolution CT Scanner',
            'device_type_id' => $devType->id,
            'site_id' => $site->id,
            'default_interface_name' => 'eth0',
            'default_interface_mac' => '00:14:22:99:88:01',
        ]);
    }

    public function test_livewire_ip_addresses_index_renders_with_explorer(): void
    {
        Livewire::actingAs($this->admin)
            ->test(IpAddressesIndex::class)
            ->assertStatus(200)
            ->assertSee('IP Address Explorer')
            ->assertSee('10.10.10.0/24')
            ->assertSee('10.10.10.1');
    }

    public function test_livewire_allocate_modal_and_submission(): void
    {
        $iface = $this->device->interfaces()->first();

        Livewire::actingAs($this->admin)
            ->test(IpAddressesIndex::class)
            ->call('openAllocateModal', '10.10.10.20')
            ->assertSet('showAllocateModal', true)
            ->assertSet('alloc_ip', '10.10.10.20')
            ->set('alloc_device_id', $this->device->id)
            ->set('alloc_interface_id', $iface->id)
            ->set('alloc_hostname', 'ct01.rad.kyamch.local')
            ->call('saveAllocation')
            ->assertHasNoErrors()
            ->assertSet('showAllocateModal', false);

        $this->assertDatabaseHas('ip_addresses', [
            'vrf_id' => $this->vrf->id,
            'address_text' => '10.10.10.20',
            'allocation_status' => 'assigned',
            'hostname' => 'ct01.rad.kyamch.local',
        ]);
    }

    public function test_livewire_release_and_transfer_modals(): void
    {
        $allocateAction = app(AllocateIpAction::class);
        $iface = $this->device->interfaces()->first();

        $ip = $allocateAction->execute([
            'subnet_id' => $this->subnet->id,
            'ip_address' => '10.10.10.30',
            'interface_id' => $iface->id,
        ], $this->admin);

        Livewire::actingAs($this->admin)
            ->test(IpAddressesIndex::class)
            ->call('openReleaseModal', $ip->id)
            ->assertSet('showReleaseModal', true)
            ->assertSet('releaseIpId', $ip->id)
            ->set('release_reason', 'Replaced host')
            ->set('release_quarantine_days', 3)
            ->call('confirmRelease')
            ->assertSet('showReleaseModal', false);

        $this->assertDatabaseHas('ip_addresses', [
            'id' => $ip->id,
            'allocation_status' => 'quarantined',
        ]);
    }
}
