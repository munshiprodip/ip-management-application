<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Layer2\Enums\VlanSecurityZone;
use App\Domain\Layer2\Enums\VlanStatus;
use App\Domain\Security\Enums\UserRole;
use App\Models\L2Domain;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use App\Models\Vlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VlanManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $viewer;
    protected Site $siteA;
    protected Site $siteB;
    protected L2Domain $domainA;
    protected L2Domain $domainB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::NETWORK_ADMIN,
            'status' => 'active',
        ]);

        $this->viewer = User::factory()->create([
            'role' => UserRole::VIEWER,
            'status' => 'active',
        ]);

        $org = Organization::create([
            'code' => 'KYAMCH',
            'name' => 'Khwaja Yunus Ali Medical College & Hospital',
            'slug' => 'kyamch',
            'status' => 'active',
        ]);

        $this->siteA = Site::create([
            'organization_id' => $org->id,
            'code' => 'MAIN-HOSPITAL',
            'name' => 'Main Hospital Facility',
            'status' => 'active',
        ]);

        $this->siteB = Site::create([
            'organization_id' => $org->id,
            'code' => 'ACADEMIC-WING',
            'name' => 'Academic Complex',
            'status' => 'active',
        ]);

        $this->domainA = L2Domain::create([
            'site_id' => $this->siteA->id,
            'code' => 'L2-MAIN-CORE',
            'name' => 'Hospital Core Fabric',
            'status' => 'active',
        ]);

        $this->domainB = L2Domain::create([
            'site_id' => $this->siteB->id,
            'code' => 'L2-ACAD-DIST',
            'name' => 'Academic Distribution Fabric',
            'status' => 'active',
        ]);
    }

    /**
     * T-016: Duplicate VLAN within same L2 domain must be rejected.
     */
    public function test_t016_duplicate_vlan_within_same_l2_domain_is_rejected(): void
    {
        // First VLAN 100 in Domain A
        Vlan::create([
            'l2_domain_id' => $this->domainA->id,
            'vlan_id' => 100,
            'name' => 'Primary ICU Network',
            'security_zone' => VlanSecurityZone::CLINICAL,
            'status' => VlanStatus::ACTIVE,
        ]);

        // Attempt second VLAN 100 in same Domain A
        $component = \Livewire\Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Vlans\Index::class)
            ->set('vlan_l2_domain_id', $this->domainA->id)
            ->set('vlan_number', 100)
            ->set('vlan_name', 'Duplicate ICU Attempt')
            ->set('vlan_security_zone', 'clinical')
            ->set('vlan_status', 'active')
            ->call('saveVlan');

        $component->assertHasErrors(['vlan_number']);

        $this->assertEquals(1, Vlan::where('l2_domain_id', $this->domainA->id)->where('vlan_id', 100)->count());
    }

    /**
     * T-017: Same VLAN ID reused across separate L2 domains is explicitly allowed.
     */
    public function test_t017_same_vlan_id_reused_in_separate_l2_domain_is_allowed(): void
    {
        // Create VLAN 10 in Domain A
        $vlanA = Vlan::create([
            'l2_domain_id' => $this->domainA->id,
            'vlan_id' => 10,
            'name' => 'Hospital Core Management',
            'security_zone' => VlanSecurityZone::MANAGEMENT,
            'status' => VlanStatus::ACTIVE,
        ]);

        // Create identical numeric VLAN 10 in Domain B (different L2 domain)
        $component = \Livewire\Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Vlans\Index::class)
            ->set('vlan_l2_domain_id', $this->domainB->id)
            ->set('vlan_number', 10)
            ->set('vlan_name', 'Academic Wing Management')
            ->set('vlan_security_zone', 'management')
            ->set('vlan_status', 'active')
            ->call('saveVlan');

        $component->assertHasNoErrors();

        $this->assertDatabaseHas('vlans', [
            'id' => $vlanA->id,
            'l2_domain_id' => $this->domainA->id,
            'vlan_id' => 10,
        ]);

        $this->assertDatabaseHas('vlans', [
            'l2_domain_id' => $this->domainB->id,
            'vlan_id' => 10,
            'name' => 'Academic Wing Management',
        ]);
    }

    public function test_invalid_vlan_number_outside_8021q_range_is_rejected(): void
    {
        $componentLow = \Livewire\Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Vlans\Index::class)
            ->set('vlan_l2_domain_id', $this->domainA->id)
            ->set('vlan_number', 0)
            ->set('vlan_name', 'Zero VLAN')
            ->set('vlan_security_zone', 'internal')
            ->set('vlan_status', 'active')
            ->call('saveVlan');

        $componentLow->assertHasErrors(['vlan_number']);

        $componentHigh = \Livewire\Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Vlans\Index::class)
            ->set('vlan_l2_domain_id', $this->domainA->id)
            ->set('vlan_number', 4095)
            ->set('vlan_name', 'Overflow VLAN')
            ->set('vlan_security_zone', 'internal')
            ->set('vlan_status', 'active')
            ->call('saveVlan');

        $componentHigh->assertHasErrors(['vlan_number']);
    }

    public function test_l2_domain_with_active_vlans_cannot_be_deleted(): void
    {
        Vlan::create([
            'l2_domain_id' => $this->domainA->id,
            'vlan_id' => 50,
            'name' => 'Active Staff Workstations',
            'security_zone' => VlanSecurityZone::WORKSTATIONS,
            'status' => VlanStatus::ACTIVE,
        ]);

        $action = app(\App\Actions\Layer2\DeleteL2DomainAction::class);

        $this->expectException(ValidationException::class);
        $action->execute($this->domainA);
    }

    public function test_medical_restricted_flag_evaluates_true_for_clinical_zones(): void
    {
        $clinicalVlan = new Vlan(['security_zone' => VlanSecurityZone::CLINICAL]);
        $biomedVlan = new Vlan(['security_zone' => VlanSecurityZone::BIOMEDICAL]);
        $guestVlan = new Vlan(['security_zone' => VlanSecurityZone::GUEST]);
        $mgmtVlan = new Vlan(['security_zone' => VlanSecurityZone::MANAGEMENT]);

        $this->assertTrue($clinicalVlan->isMedicalRestricted());
        $this->assertTrue($biomedVlan->isMedicalRestricted());
        $this->assertFalse($guestVlan->isMedicalRestricted());
        $this->assertFalse($mgmtVlan->isMedicalRestricted());
    }

    public function test_vlan_lifecycle_operations_are_audited(): void
    {
        $vlan = Vlan::create([
            'l2_domain_id' => $this->domainA->id,
            'vlan_id' => 200,
            'name' => 'Audit Test VLAN',
            'security_zone' => VlanSecurityZone::INTERNAL,
            'status' => VlanStatus::ACTIVE,
        ]);

        $updateAction = app(\App\Actions\Layer2\UpdateVlanAction::class);
        $updateAction->execute($vlan, ['name' => 'Renamed Audit Test VLAN']);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'vlan.updated',
            'subject_id' => $vlan->id,
        ]);

        $deleteAction = app(\App\Actions\Layer2\DeleteVlanAction::class);
        $deleteAction->execute($vlan);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'vlan.deleted',
        ]);
    }
}

