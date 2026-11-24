<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Layer2\CreateVlanAction;
use App\Actions\Layer2\DeleteVlanAction;
use App\Actions\Layer2\UpdateVlanAction;
use App\Domain\Layer2\Enums\VlanSecurityZone;
use App\Domain\Layer2\Enums\VlanStatus;
use App\Domain\Security\Enums\UserRole;
use App\Livewire\Vlans\Index as VlansIndex;
use App\Models\AuditEvent;
use App\Models\User;
use App\Models\Vlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class VlanManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::NETWORK_ADMIN,
            'status' => 'active',
        ]);
    }

    public function test_duplicate_vlan_is_rejected(): void
    {
        $action = app(CreateVlanAction::class);

        $action->execute([
            'vlan_id' => 10,
            'name' => 'Management VLAN',
            'security_zone' => VlanSecurityZone::MANAGEMENT,
        ]);

        $this->expectException(ValidationException::class);
        $action->execute([
            'vlan_id' => 10,
            'name' => 'Duplicate Management VLAN',
            'security_zone' => VlanSecurityZone::MANAGEMENT,
        ]);
    }

    public function test_invalid_vlan_number_outside_8021q_range_is_rejected(): void
    {
        $action = app(CreateVlanAction::class);

        $this->expectException(ValidationException::class);
        $action->execute([
            'vlan_id' => 5000,
            'name' => 'Out of Range VLAN',
        ]);
    }

    public function test_vlan_lifecycle_operations_are_audited(): void
    {
        $createAction = app(CreateVlanAction::class);
        $updateAction = app(UpdateVlanAction::class);
        $deleteAction = app(DeleteVlanAction::class);

        // 1. Create
        $vlan = $createAction->execute([
            'vlan_id' => 20,
            'name' => 'ICU Telemetry',
            'security_zone' => VlanSecurityZone::CLINICAL,
        ]);

        $this->assertDatabaseHas('vlans', ['vlan_id' => 20, 'name' => 'ICU Telemetry']);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'vlan.created']);

        // 2. Update
        $updateAction->execute($vlan, [
            'name' => 'ICU Critical Telemetry',
        ]);

        $this->assertDatabaseHas('vlans', ['vlan_id' => 20, 'name' => 'ICU Critical Telemetry']);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'vlan.updated']);

        // 3. Delete
        $deleteAction->execute($vlan);

        $this->assertDatabaseMissing('vlans', ['vlan_id' => 20]);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'vlan.deleted']);
    }

    public function test_medical_restricted_flag_evaluates_true_for_clinical_zones(): void
    {
        $clinicalVlan = Vlan::create([
            'vlan_id' => 100,
            'name' => 'Bedside Telemetry',
            'security_zone' => VlanSecurityZone::CLINICAL,
            'status' => VlanStatus::ACTIVE,
            'is_restricted' => false,
        ]);

        $staffVlan = Vlan::create([
            'vlan_id' => 200,
            'name' => 'Office PCs',
            'security_zone' => VlanSecurityZone::WORKSTATIONS,
            'status' => VlanStatus::ACTIVE,
            'is_restricted' => false,
        ]);

        $restrictedCustomVlan = Vlan::create([
            'vlan_id' => 300,
            'name' => 'Special Lab',
            'security_zone' => VlanSecurityZone::INTERNAL,
            'status' => VlanStatus::ACTIVE,
            'is_restricted' => true,
        ]);

        $this->assertTrue($clinicalVlan->isMedicalRestricted());
        $this->assertFalse($staffVlan->isMedicalRestricted());
        $this->assertTrue($restrictedCustomVlan->isMedicalRestricted());
    }

    public function test_livewire_vlans_index_renders_and_creates_vlan(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(VlansIndex::class)
            ->assertStatus(200)
            ->assertSee('802.1Q Virtual LAN (VLAN) Management')
            ->set('vlan_number', 55)
            ->set('vlan_name', 'Pharmacy Dispensing Network')
            ->set('vlan_security_zone', 'internal')
            ->set('vlan_status', 'active')
            ->call('saveVlan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('vlans', ['vlan_id' => 55, 'name' => 'Pharmacy Dispensing Network']);
    }

    public function test_cannot_delete_vlan_with_attached_subnets(): void
    {
        $vlanAction = app(CreateVlanAction::class);
        $vlan = $vlanAction->execute([
            'vlan_id' => 77,
            'name' => 'Cath Lab VLAN',
        ]);

        $subnetAction = app(\App\Actions\AddressSpace\CreateSubnetAction::class);
        $subnetAction->execute([
            'name' => 'Cath Lab Subnet',
            'cidr' => '10.88.1.0/24',
            'vlan_id' => $vlan->id,
        ]);

        $deleteAction = app(DeleteVlanAction::class);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('because it is currently attached to');

        $deleteAction->execute($vlan);
    }
}
