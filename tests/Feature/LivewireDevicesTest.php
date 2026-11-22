<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\DeviceInventory\CreateDeviceAction;
use App\Domain\DeviceInventory\Enums\DeviceCategory;
use App\Domain\DeviceInventory\Enums\DeviceCriticality;
use App\Domain\DeviceInventory\Enums\DeviceStatus;
use App\Domain\Security\Enums\UserRole;
use App\Livewire\Devices\Index as DevicesIndex;
use App\Models\Department;
use App\Models\Device;
use App\Models\DeviceType;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireDevicesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Site $site;
    private Department $dept;
    private DeviceType $deviceType;
    private Device $device;

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organization::create([
            'name' => 'KYAMCH Test Org',
            'code' => 'KYAMCH-TEST',
            'slug' => 'kyamch-test',
        ]);

        $this->site = Site::create([
            'organization_id' => $org->id,
            'name' => 'Main Hospital',
            'code' => 'MAIN',
            'slug' => 'main-hospital',
        ]);

        $this->dept = Department::create([
            'organization_id' => $org->id,
            'name' => 'Cardiology',
            'code' => 'CARD-TEST',
            'slug' => 'cardiology-test',
        ]);

        $this->deviceType = DeviceType::create([
            'code' => 'MED-MON-TEST',
            'name' => 'Patient Telemetry',
            'category' => DeviceCategory::MEDICAL,
        ]);

        $this->admin = User::factory()->create([
            'role' => UserRole::NETWORK_ADMIN,
            'status' => 'active',
        ]);

        $createDevice = app(CreateDeviceAction::class);
        $this->device = $createDevice->execute([
            'code' => 'KY-CARD-MON01',
            'name' => 'Cardiology ICU Monitor 01',
            'hostname' => 'mon01.card.kyamch.local',
            'device_type_id' => $this->deviceType->id,
            'site_id' => $this->site->id,
            'department_id' => $this->dept->id,
            'asset_tag' => 'AST-CARD-1001',
            'serial_number' => 'SN-CARD-8877',
            'criticality' => DeviceCriticality::LIFE_SAFETY,
            'status' => DeviceStatus::ACTIVE,
            'default_interface_name' => 'eth0',
            'default_interface_mac' => '00:1B:77:AA:BB:CC',
        ]);
    }

    public function test_livewire_devices_index_renders_with_kpi_and_list(): void
    {
        Livewire::actingAs($this->admin)
            ->test(DevicesIndex::class)
            ->assertStatus(200)
            ->assertSee('Cardiology ICU Monitor 01')
            ->assertSee('KY-CARD-MON01')
            ->assertSee('AST-CARD-1001')
            ->assertSee('LIFE SAFETY');
    }

    public function test_searching_by_asset_tag_and_mac(): void
    {
        Livewire::actingAs($this->admin)
            ->test(DevicesIndex::class)
            ->set('search', 'AST-CARD-1001')
            ->assertSee('Cardiology ICU Monitor 01')
            ->set('search', '00:1B:77')
            ->assertSee('Cardiology ICU Monitor 01')
            ->set('search', 'NON-EXISTENT-TAG')
            ->assertDontSee('Cardiology ICU Monitor 01');
    }

    public function test_detail_slide_over_drawer_and_interface_management(): void
    {
        Livewire::actingAs($this->admin)
            ->test(DevicesIndex::class)
            ->call('viewDevice', $this->device->id)
            ->assertSet('showDetailSlideover', true)
            ->assertSet('viewDeviceId', $this->device->id)
            ->assertSee('Device Network Interfaces')
            ->assertSee('eth0')
            ->assertSee('00:1B:77:AA:BB:CC')
            ->call('openAddInterfaceModal')
            ->assertSet('showInterfaceModal', true)
            ->set('if_name', 'eth1')
            ->set('if_type', 'physical')
            ->set('if_mac', '00:1B:77:AA:BB:DD')
            ->set('if_status', 'up')
            ->call('saveInterface')
            ->assertHasNoErrors()
            ->assertSee('eth1');

        $this->assertEquals(2, $this->device->fresh()->interfaces()->count());
    }
}
