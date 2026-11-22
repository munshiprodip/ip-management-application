<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\DeviceInventory\CreateDeviceAction;
use App\Actions\DeviceInventory\DeleteDeviceAction;
use App\Actions\DeviceInventory\UpdateDeviceAction;
use App\Domain\DeviceInventory\Enums\DeviceCategory;
use App\Domain\DeviceInventory\Enums\DeviceCriticality;
use App\Domain\DeviceInventory\Enums\DeviceStatus;
use App\Domain\Security\Enums\UserRole;
use App\Models\AuditEvent;
use App\Models\Department;
use App\Models\Device;
use App\Models\DeviceType;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DeviceManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $viewer;
    private Site $site;
    private Department $dept;
    private DeviceType $deviceType;

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
            'name' => 'Radiology & Imaging',
            'code' => 'RAD-TEST',
            'slug' => 'radiology-test',
        ]);

        $this->deviceType = DeviceType::create([
            'code' => 'TEST-MOD',
            'name' => 'Diagnostic Modality',
            'category' => DeviceCategory::MEDICAL,
        ]);

        $this->admin = User::factory()->create([
            'role' => UserRole::NETWORK_ADMIN,
            'status' => 'active',
        ]);

        $this->viewer = User::factory()->create([
            'role' => UserRole::VIEWER,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_create_device_with_audit_trail(): void
    {
        $this->actingAs($this->admin);
        $action = app(CreateDeviceAction::class);

        $device = $action->execute([
            'code' => 'KY-RAD-MRI01',
            'name' => 'Siemens Magnetom 1.5T MRI',
            'hostname' => 'mri01.rad.kyamch.local',
            'device_type_id' => $this->deviceType->id,
            'site_id' => $this->site->id,
            'department_id' => $this->dept->id,
            'asset_tag' => 'AST-MRI-9901',
            'serial_number' => 'SN-SIEMENS-1234',
            'criticality' => DeviceCriticality::LIFE_SAFETY,
            'status' => DeviceStatus::ACTIVE,
            'default_interface_name' => 'eth0',
            'default_interface_mac' => '00:14:22:99:88:77',
        ]);

        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'code' => 'KY-RAD-MRI01',
            'name' => 'Siemens Magnetom 1.5T MRI',
            'asset_tag' => 'AST-MRI-9901',
            'criticality' => 'life_safety',
        ]);

        $this->assertDatabaseHas('device_interfaces', [
            'device_id' => $device->id,
            'name' => 'eth0',
            'mac_address' => '00:14:22:99:88:77',
        ]);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'device.created',
            'subject_id' => $device->id,
        ]);
    }

    public function test_duplicate_device_code_is_rejected(): void
    {
        $this->actingAs($this->admin);
        $action = app(CreateDeviceAction::class);

        $action->execute([
            'code' => 'KY-TEST-01',
            'name' => 'First Device',
            'device_type_id' => $this->deviceType->id,
            'site_id' => $this->site->id,
        ]);

        $this->expectException(ValidationException::class);

        $action->execute([
            'code' => 'KY-TEST-01',
            'name' => 'Duplicate Device Code',
            'device_type_id' => $this->deviceType->id,
            'site_id' => $this->site->id,
        ]);
    }

    public function test_duplicate_asset_tag_is_rejected(): void
    {
        $this->actingAs($this->admin);
        $action = app(CreateDeviceAction::class);

        $action->execute([
            'code' => 'KY-DEV-01',
            'name' => 'First Device',
            'asset_tag' => 'AST-UNIQUE-100',
            'device_type_id' => $this->deviceType->id,
            'site_id' => $this->site->id,
        ]);

        $this->expectException(ValidationException::class);

        $action->execute([
            'code' => 'KY-DEV-02',
            'name' => 'Second Device with Duplicate Tag',
            'asset_tag' => 'AST-UNIQUE-100',
            'device_type_id' => $this->deviceType->id,
            'site_id' => $this->site->id,
        ]);
    }

    public function test_admin_can_update_device_details(): void
    {
        $this->actingAs($this->admin);
        $createAction = app(CreateDeviceAction::class);
        $updateAction = app(UpdateDeviceAction::class);

        $device = $createAction->execute([
            'code' => 'KY-UP-01',
            'name' => 'Old Device Name',
            'device_type_id' => $this->deviceType->id,
            'site_id' => $this->site->id,
        ]);

        $updated = $updateAction->execute($device, [
            'name' => 'Updated Radiology Core Scanner',
            'criticality' => DeviceCriticality::MISSION_CRITICAL,
            'notes' => 'Updated service contract notes',
        ]);

        $this->assertEquals('Updated Radiology Core Scanner', $updated->name);
        $this->assertEquals(DeviceCriticality::MISSION_CRITICAL, $updated->criticality);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'device.updated',
            'subject_id' => $device->id,
        ]);
    }

    public function test_admin_can_delete_device(): void
    {
        $this->actingAs($this->admin);
        $createAction = app(CreateDeviceAction::class);
        $deleteAction = app(DeleteDeviceAction::class);

        $device = $createAction->execute([
            'code' => 'KY-DEL-01',
            'name' => 'Decommission Test Device',
            'device_type_id' => $this->deviceType->id,
            'site_id' => $this->site->id,
        ]);

        $deviceId = $device->id;
        $deleteAction->execute($device);

        $this->assertDatabaseMissing('devices', ['id' => $deviceId]);
        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'device.deleted',
            'subject_id' => $deviceId,
        ]);
    }

    public function test_viewer_is_forbidden_from_device_management(): void
    {
        $this->actingAs($this->viewer);

        $this->assertFalse($this->viewer->can('create', Device::class));

        $device = Device::create([
            'code' => 'KY-FORBIDDEN-01',
            'name' => 'Policy Check Device',
            'device_type_id' => $this->deviceType->id,
            'site_id' => $this->site->id,
            'criticality' => DeviceCriticality::LOW,
            'status' => DeviceStatus::ACTIVE,
        ]);

        $this->assertFalse($this->viewer->can('update', $device));
        $this->assertFalse($this->viewer->can('delete', $device));
    }
}
