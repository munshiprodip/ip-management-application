<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\AddressSpace\AllocateIpAction;
use App\Actions\AddressSpace\CreateSubnetAction;
use App\Actions\HostType\CreateHostTypeAction;
use App\Actions\HostType\DeleteHostTypeAction;
use App\Actions\HostType\UpdateHostTypeAction;
use App\Domain\AddressSpace\Enums\SubnetType;
use App\Livewire\HostTypes\Index as HostTypesIndex;
use App\Models\HostType;
use App\Models\Subnet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class HostTypeManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'network_admin',
            'status' => 'active',
        ]);
    }

    public function test_guest_cannot_access_host_types(): void
    {
        $response = $this->get(route('host-types.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authorized_user_can_view_host_types(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('host-types.index'));
        $response->assertStatus(200);
        $response->assertSee('Host Types Management');
        $response->assertSee('Medical devices');
        $response->assertSee('Servers');
    }

    public function test_can_create_new_host_type(): void
    {
        $action = app(CreateHostTypeAction::class);

        $created = $action->execute([
            'name' => 'Biomedical Telemetry',
            'slug' => 'biomedical_telemetry',
            'description' => 'ECG and telemetry transmitter pods',
            'color' => 'teal',
            'is_active' => true,
        ], $this->admin);

        $this->assertDatabaseHas('host_types', [
            'name' => 'Biomedical Telemetry',
            'slug' => 'biomedical_telemetry',
            'color' => 'teal',
            'is_system' => false,
        ]);
    }

    public function test_auto_slug_generation_when_slug_omitted(): void
    {
        $action = app(CreateHostTypeAction::class);

        $created = $action->execute([
            'name' => 'Infusion Pumps & Drivers',
            'color' => 'rose',
        ], $this->admin);

        $this->assertEquals('infusion_pumps_drivers', $created->slug);
    }

    public function test_cannot_create_duplicate_slug(): void
    {
        $action = app(CreateHostTypeAction::class);

        $this->expectException(ValidationException::class);
        $action->execute([
            'name' => 'Duplicate Server',
            'slug' => 'server', // Already seeded in migration
        ], $this->admin);
    }

    public function test_can_update_host_type(): void
    {
        $action = app(CreateHostTypeAction::class);
        $updateAction = app(UpdateHostTypeAction::class);

        $custom = $action->execute([
            'name' => 'Nurse Tablets',
            'slug' => 'nurse_tablets',
            'color' => 'blue',
        ], $this->admin);

        $updated = $updateAction->execute($custom, [
            'name' => 'Clinical Ward Tablets',
            'color' => 'sky',
            'description' => 'Mobile bedside clinical charting tablets',
        ], $this->admin);

        $this->assertEquals('Clinical Ward Tablets', $updated->name);
        $this->assertEquals('sky', $updated->color);
        $this->assertEquals('Mobile bedside clinical charting tablets', $updated->description);
    }

    public function test_cannot_delete_system_host_type(): void
    {
        $deleteAction = app(DeleteHostTypeAction::class);
        $systemType = HostType::where('slug', 'medical')->firstOrFail();

        $this->expectException(ValidationException::class);
        $deleteAction->execute($systemType, $this->admin);
    }

    public function test_cannot_delete_host_type_with_active_ip_allocations(): void
    {
        $subnetAction = app(CreateSubnetAction::class);
        $allocateAction = app(AllocateIpAction::class);
        $createHtAction = app(CreateHostTypeAction::class);
        $deleteAction = app(DeleteHostTypeAction::class);

        $subnet = $subnetAction->execute([
            'name' => 'Test Subnet',
            'cidr' => '10.88.1.0/24',
            'subnet_type' => SubnetType::ASSIGNABLE,
            'allocation_enabled' => true,
        ]);

        $customType = $createHtAction->execute([
            'name' => 'Dialysis Machines',
            'slug' => 'dialysis_machines',
            'color' => 'rose',
        ], $this->admin);

        // Allocate IP with this custom host type
        $allocateAction->execute([
            'subnet_id' => $subnet->id,
            'ip_address' => '10.88.1.10',
            'host_type' => 'dialysis_machines',
            'assigned_to' => 'Hemodialysis Unit 1',
        ], $this->admin);

        $this->expectException(ValidationException::class);
        $deleteAction->execute($customType, $this->admin);
    }

    public function test_can_delete_unused_custom_host_type(): void
    {
        $createAction = app(CreateHostTypeAction::class);
        $deleteAction = app(DeleteHostTypeAction::class);

        $unused = $createAction->execute([
            'name' => 'Temporary Sensor Type',
            'slug' => 'temp_sensor',
            'color' => 'slate',
        ], $this->admin);

        $deleted = $deleteAction->execute($unused, $this->admin);
        $this->assertTrue($deleted);

        $this->assertDatabaseMissing('host_types', [
            'slug' => 'temp_sensor',
        ]);
    }

    public function test_livewire_host_types_index_crud(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(HostTypesIndex::class)
            ->assertStatus(200)
            ->assertSee('Medical devices')
            ->call('openCreateModal')
            ->assertSet('showModal', true)
            ->set('name', 'Anesthesia Workstations')
            ->set('slug', 'anesthesia_workstations')
            ->set('color', 'rose')
            ->set('description', 'Operating room anesthesia delivery platforms')
            ->call('saveHostType')
            ->assertHasNoErrors()
            ->assertSet('showModal', false)
            ->assertSee('Anesthesia Workstations');

        $this->assertDatabaseHas('host_types', [
            'slug' => 'anesthesia_workstations',
            'name' => 'Anesthesia Workstations',
            'color' => 'rose',
        ]);
    }
}

