<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\DeviceInventory\CreateDeviceAction;
use App\Actions\DeviceInventory\CreateInterfaceAction;
use App\Actions\DeviceInventory\DeleteInterfaceAction;
use App\Actions\DeviceInventory\UpdateInterfaceAction;
use App\Domain\DeviceInventory\Enums\DeviceCategory;
use App\Domain\DeviceInventory\Enums\InterfaceStatus;
use App\Domain\DeviceInventory\Enums\InterfaceType;
use App\Domain\Security\Enums\UserRole;
use App\Models\Device;
use App\Models\DeviceInterface;
use App\Models\DeviceType;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use App\Models\Vlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DeviceInterfaceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Device $device;
    private Device $secondDevice;

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

        $type = DeviceType::create([
            'code' => 'NET-SW',
            'name' => 'Switch',
            'category' => DeviceCategory::NETWORK,
        ]);

        $this->admin = User::factory()->create([
            'role' => UserRole::NETWORK_ADMIN,
            'status' => 'active',
        ]);

        $createDevice = app(CreateDeviceAction::class);
        $this->device = $createDevice->execute([
            'code' => 'SW-TEST-01',
            'name' => 'Core Switch 01',
            'device_type_id' => $type->id,
            'site_id' => $site->id,
        ]);

        $this->secondDevice = $createDevice->execute([
            'code' => 'SW-TEST-02',
            'name' => 'Core Switch 02',
            'device_type_id' => $type->id,
            'site_id' => $site->id,
        ]);
    }

    public function test_device_supports_multiple_network_interfaces(): void
    {
        $this->actingAs($this->admin);
        $action = app(CreateInterfaceAction::class);

        $if1 = $action->execute($this->device, [
            'name' => 'mgmt0',
            'type' => InterfaceType::MANAGEMENT,
            'mac_address' => '00:14:22:01:01:01',
        ]);

        $if2 = $action->execute($this->device, [
            'name' => 'GigabitEthernet0/1',
            'type' => InterfaceType::PHYSICAL,
            'mac_address' => '00:14:22:01:01:02',
            'speed_mbps' => 1000,
        ]);

        $if3 = $action->execute($this->device, [
            'name' => 'Loopback0',
            'type' => InterfaceType::LOOPBACK,
        ]);

        $this->assertEquals(3, $this->device->interfaces()->count());
        $this->assertDatabaseHas('audit_events', ['event_type' => 'interface.created', 'subject_id' => $if1->id]);
    }

    public function test_duplicate_interface_name_on_same_device_is_rejected(): void
    {
        $this->actingAs($this->admin);
        $action = app(CreateInterfaceAction::class);

        $action->execute($this->device, [
            'name' => 'eth0',
            'type' => InterfaceType::PHYSICAL,
        ]);

        $this->expectException(ValidationException::class);

        // Attempting to add duplicate eth0 to same device must fail
        $action->execute($this->device, [
            'name' => 'eth0',
            'type' => InterfaceType::PHYSICAL,
        ]);
    }

    public function test_same_interface_name_allowed_on_different_devices(): void
    {
        $this->actingAs($this->admin);
        $action = app(CreateInterfaceAction::class);

        $ifA = $action->execute($this->device, [
            'name' => 'eth0',
            'type' => InterfaceType::PHYSICAL,
        ]);

        $ifB = $action->execute($this->secondDevice, [
            'name' => 'eth0',
            'type' => InterfaceType::PHYSICAL,
        ]);

        $this->assertNotEquals($ifA->device_id, $ifB->device_id);
        $this->assertEquals($ifA->name, $ifB->name);
    }

    public function test_mac_address_is_normalized_on_save(): void
    {
        $this->actingAs($this->admin);
        $action = app(CreateInterfaceAction::class);

        // Input with dashes and lowercase
        $iface = $action->execute($this->device, [
            'name' => 'eth1',
            'type' => InterfaceType::PHYSICAL,
            'mac_address' => 'aa-bb-cc-11-22-33',
        ]);

        $this->assertEquals('AA:BB:CC:11:22:33', $iface->mac_address);
        $this->assertDatabaseHas('device_interfaces', [
            'id' => $iface->id,
            'mac_address' => 'AA:BB:CC:11:22:33',
        ]);
    }

    public function test_sub_interface_hierarchy(): void
    {
        $this->actingAs($this->admin);
        $action = app(CreateInterfaceAction::class);

        $parent = $action->execute($this->device, [
            'name' => 'GigabitEthernet0/1',
            'type' => InterfaceType::PHYSICAL,
        ]);

        $sub = $action->execute($this->device, [
            'name' => 'GigabitEthernet0/1.10',
            'type' => InterfaceType::SUBINTERFACE,
            'parent_interface_id' => $parent->id,
        ]);

        $this->assertEquals($parent->id, $sub->parent_interface_id);
        $this->assertEquals(1, $parent->subInterfaces()->count());
    }

    public function test_delete_interface_with_sub_interfaces_is_rejected(): void
    {
        $this->actingAs($this->admin);
        $createAction = app(CreateInterfaceAction::class);
        $deleteAction = app(DeleteInterfaceAction::class);

        $parent = $createAction->execute($this->device, [
            'name' => 'bond0',
            'type' => InterfaceType::AGGREGATED,
        ]);

        $createAction->execute($this->device, [
            'name' => 'bond0.20',
            'type' => InterfaceType::SUBINTERFACE,
            'parent_interface_id' => $parent->id,
        ]);

        $this->expectException(ValidationException::class);
        $deleteAction->execute($parent);
    }

    public function test_delete_leaf_interface_with_audit_trail(): void
    {
        $this->actingAs($this->admin);
        $createAction = app(CreateInterfaceAction::class);
        $deleteAction = app(DeleteInterfaceAction::class);

        $iface = $createAction->execute($this->device, [
            'name' => 'temp0',
            'type' => InterfaceType::PHYSICAL,
        ]);

        $ifId = $iface->id;
        $deleteAction->execute($iface);

        $this->assertDatabaseMissing('device_interfaces', ['id' => $ifId]);
        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'interface.deleted',
            'subject_id' => $ifId,
        ]);
    }
}
