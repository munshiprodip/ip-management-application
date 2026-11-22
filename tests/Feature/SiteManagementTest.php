<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\AddressSpace\CreateSubnetAction;
use App\Actions\Layer2\CreateVlanAction;
use App\Actions\Site\CreateSiteAction;
use App\Actions\Site\DeleteSiteAction;
use App\Actions\Site\UpdateSiteAction;
use App\Domain\AddressSpace\Enums\SubnetType;
use App\Livewire\Components\CampusSwitcher;
use App\Livewire\Sites\Index as SitesIndex;
use App\Models\AuditEvent;
use App\Models\Site;
use App\Models\Subnet;
use App\Models\User;
use App\Models\Vlan;
use App\Services\SiteContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class SiteManagementTest extends TestCase
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

    public function test_admin_can_create_campus_site_with_audit_event(): void
    {
        $action = app(CreateSiteAction::class);

        $site = $action->execute([
            'name' => 'KYAMCH Cancer Center',
            'code' => 'KYAMCH-CANCER',
            'description' => 'Dedicated oncology and radiotherapy building',
            'is_default' => false,
        ]);

        $this->assertDatabaseHas('sites', [
            'id' => $site->id,
            'name' => 'KYAMCH Cancer Center',
            'code' => 'KYAMCH-CANCER',
        ]);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'site.created',
        ]);
    }

    public function test_cannot_create_duplicate_campus_code(): void
    {
        $action = app(CreateSiteAction::class);

        $action->execute([
            'name' => 'Campus Alpha',
            'code' => 'CAMPUS-A',
        ]);

        $this->expectException(ValidationException::class);

        $action->execute([
            'name' => 'Campus Alpha Duplicate',
            'code' => 'CAMPUS-A',
        ]);
    }

    public function test_admin_can_update_campus_site(): void
    {
        $createAction = app(CreateSiteAction::class);
        $updateAction = app(UpdateSiteAction::class);

        $site = $createAction->execute([
            'name' => 'Main Wing',
            'code' => 'WING-MAIN',
        ]);

        $updated = $updateAction->execute($site, [
            'name' => 'Main Hospital Complex',
            'description' => 'Expanded acute care facility',
        ]);

        $this->assertEquals('Main Hospital Complex', $updated->name);
        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'site.updated',
        ]);
    }

    public function test_cannot_delete_campus_with_linked_subnets_or_vlans(): void
    {
        $createSiteAction = app(CreateSiteAction::class);
        $createSubnetAction = app(CreateSubnetAction::class);
        $deleteSiteAction = app(DeleteSiteAction::class);

        $site = $createSiteAction->execute([
            'name' => 'Site with Resources',
            'code' => 'SITE-RES',
        ]);

        $createSubnetAction->execute([
            'site_id' => $site->id,
            'name' => 'Site Subnet',
            'cidr' => '10.80.0.0/16',
            'subnet_type' => SubnetType::AGGREGATE,
        ]);

        $this->expectException(ValidationException::class);
        $deleteSiteAction->execute($site);
    }

    public function test_admin_can_delete_empty_campus(): void
    {
        $createSiteAction = app(CreateSiteAction::class);
        $deleteSiteAction = app(DeleteSiteAction::class);

        $site = $createSiteAction->execute([
            'name' => 'Decommissioned Facility',
            'code' => 'DECOM-01',
        ]);

        $deleteSiteAction->execute($site);

        $this->assertDatabaseMissing('sites', ['id' => $site->id]);
        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'site.deleted',
        ]);
    }

    public function test_child_subnet_inherits_parent_site(): void
    {
        $createSiteAction = app(CreateSiteAction::class);
        $createSubnetAction = app(CreateSubnetAction::class);

        $site = $createSiteAction->execute([
            'name' => 'North Campus',
            'code' => 'NORTH-CAMPUS',
        ]);

        $parent = $createSubnetAction->execute([
            'site_id' => $site->id,
            'name' => 'North Campus Root',
            'cidr' => '10.90.0.0/16',
            'subnet_type' => SubnetType::AGGREGATE,
        ]);

        $child = $createSubnetAction->execute([
            'parent_id' => $parent->id,
            'name' => 'North Campus Child',
            'cidr' => '10.90.1.0/24',
            'subnet_type' => SubnetType::ASSIGNABLE,
        ]);

        $this->assertEquals($site->id, $child->site_id);
    }

    public function test_livewire_sites_management_renders_and_creates_site(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(SitesIndex::class)
            ->assertStatus(200)
            ->assertSee('Hospital Campuses & Facility Sites')
            ->call('openCreateModal')
            ->set('name', 'Community Health Clinic')
            ->set('code' ,'KYAMCH-CLINIC')
            ->set('status', 'active')
            ->call('saveSite', app(CreateSiteAction::class), app(UpdateSiteAction::class))
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sites', [
            'code' => 'KYAMCH-CLINIC',
            'name' => 'Community Health Clinic',
        ]);
    }

    public function test_livewire_campus_switcher_sets_session(): void
    {
        $site = Site::factory()->create([
            'name' => 'Academic Campus',
            'code' => 'ACAD-CAMPUS',
            'status' => 'active',
        ]);

        Livewire::test(CampusSwitcher::class)
            ->assertStatus(200)
            ->set('selectedSiteId', (string) $site->id)
            ->assertDispatched('site-changed');

        $this->assertEquals($site->id, session('active_site_id'));
    }
}

