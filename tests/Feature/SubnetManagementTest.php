<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\AddressSpace\CreateSubnetAction;
use App\Actions\AddressSpace\DeleteSubnetAction;
use App\Actions\AddressSpace\UpdateSubnetAction;
use App\Domain\AddressSpace\Enums\SubnetStatus;
use App\Domain\AddressSpace\Enums\SubnetType;
use App\Livewire\Subnets\Index as SubnetsIndex;
use App\Models\AuditEvent;
use App\Models\Organization;
use App\Models\Site;
use App\Models\Subnet;
use App\Models\User;
use App\Models\Vrf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class SubnetManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $viewer;
    private Vrf $vrf;
    private Site $site;

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

        $this->vrf = Vrf::create([
            'name' => 'VRF-GLOBAL',
            'code' => 'GLOBAL',
            'rd' => '65000:1',
            'description' => 'Global default VRF',
            'is_default' => true,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'network_admin',
            'status' => 'active',
        ]);

        $this->viewer = User::factory()->create([
            'role' => 'viewer',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_create_subnet_with_audit_event(): void
    {
        $this->actingAs($this->admin);
        $action = app(CreateSubnetAction::class);

        $subnet = $action->execute([
            'vrf_id' => $this->vrf->id,
            'site_id' => $this->site->id,
            'cidr' => '172.16.10.0/24',
            'name' => 'Cardiology ICU Network',
            'subnet_type' => SubnetType::ASSIGNABLE->value,
            'status' => SubnetStatus::ACTIVE->value,
            'description' => 'Subnet for ICU monitors',
        ]);

        $this->assertDatabaseHas('subnets', [
            'id' => $subnet->id,
            'cidr' => '172.16.10.0/24',
            'name' => 'Cardiology ICU Network',
            'ip_version' => 4,
            'prefix_length' => 24,
            'start_hex' => 'ac100a00',
            'end_hex' => 'ac100aff',
        ]);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'subnet.created',
            'subject_id' => $subnet->id,
        ]);
    }

    public function test_admin_can_update_subnet_with_audit_event(): void
    {
        $this->actingAs($this->admin);
        $createAction = app(CreateSubnetAction::class);
        $updateAction = app(UpdateSubnetAction::class);

        $subnet = $createAction->execute([
            'vrf_id' => $this->vrf->id,
            'cidr' => '172.16.20.0/24',
            'name' => 'Old Name',
            'subnet_type' => SubnetType::ASSIGNABLE->value,
        ]);

        $updated = $updateAction->execute($subnet, [
            'name' => 'Updated Radiology Block',
            'status' => SubnetStatus::DEPRECATED->value,
            'description' => 'Updated block description',
        ]);

        $this->assertEquals('Updated Radiology Block', $updated->name);
        $this->assertEquals(SubnetStatus::DEPRECATED, $updated->status);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'subnet.updated',
            'subject_id' => $subnet->id,
        ]);
    }

    public function test_cannot_delete_subnet_with_active_children(): void
    {
        $this->actingAs($this->admin);
        $createAction = app(CreateSubnetAction::class);
        $deleteAction = app(DeleteSubnetAction::class);

        $parent = $createAction->execute([
            'vrf_id' => $this->vrf->id,
            'cidr' => '10.0.0.0/16',
            'name' => 'Parent Subnet',
            'subnet_type' => SubnetType::AGGREGATE->value,
        ]);

        $child = $createAction->execute([
            'vrf_id' => $this->vrf->id,
            'parent_id' => $parent->id,
            'cidr' => '10.0.1.0/24',
            'name' => 'Child Subnet',
            'subnet_type' => SubnetType::ASSIGNABLE->value,
        ]);

        $this->expectException(ValidationException::class);
        $deleteAction->execute($parent);
    }

    public function test_admin_can_delete_leaf_subnet_with_audit_event(): void
    {
        $this->actingAs($this->admin);
        $createAction = app(CreateSubnetAction::class);
        $deleteAction = app(DeleteSubnetAction::class);

        $subnet = $createAction->execute([
            'vrf_id' => $this->vrf->id,
            'cidr' => '10.150.0.0/24',
            'name' => 'Temporary Subnet',
            'subnet_type' => SubnetType::ASSIGNABLE->value,
        ]);

        $subnetId = $subnet->id;
        $deleteAction->execute($subnet);

        $this->assertDatabaseMissing('subnets', ['id' => $subnetId]);
        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'subnet.deleted',
            'subject_id' => $subnetId,
        ]);
    }

    public function test_viewer_is_forbidden_from_creating_or_deleting_subnets(): void
    {
        $this->actingAs($this->viewer);

        $this->assertFalse($this->viewer->can('create', Subnet::class));

        $subnet = Subnet::create([
            'vrf_id' => $this->vrf->id,
            'cidr' => '10.250.0.0/24',
            'ip_version' => 4,
            'prefix_length' => 24,
            'network_hex' => '0afa0000',
            'start_hex' => '0afa0000',
            'end_hex' => '0afa00ff',
            'name' => 'Forbidden Check',
            'subnet_type' => SubnetType::ASSIGNABLE,
            'status' => SubnetStatus::ACTIVE,
        ]);

        $this->assertFalse($this->viewer->can('update', $subnet));
        $this->assertFalse($this->viewer->can('delete', $subnet));
    }

    public function test_livewire_subnets_index_renders_and_calculates_cidr(): void
    {
        Livewire::actingAs($this->admin)
            ->test(SubnetsIndex::class)
            ->assertStatus(200)
            ->call('openCalculator')
            ->assertSet('showCalculatorModal', true)
            ->set('calcInputCidr', '192.168.50.0/26')
            ->call('runCalculation')
            ->assertSet('calcResults.network', '192.168.50.0')
            ->assertSet('calcResults.broadcast', '192.168.50.63')
            ->assertSet('calcResults.first_usable', '192.168.50.1')
            ->assertSet('calcResults.last_usable', '192.168.50.62')
            ->assertSet('calcResults.total_addresses', '64')
            ->assertSet('calcResults.usable_addresses', '62');
    }

    public function test_livewire_subnets_split_preview_and_drill_down(): void
    {
        $createAction = app(CreateSubnetAction::class);

        $parent = $createAction->execute([
            'vrf_id' => $this->vrf->id,
            'cidr' => '10.50.0.0/22',
            'name' => 'Campus Data Block',
            'subnet_type' => SubnetType::AGGREGATE->value,
        ]);

        $child = $createAction->execute([
            'vrf_id' => $this->vrf->id,
            'parent_id' => $parent->id,
            'cidr' => '10.50.0.0/24',
            'name' => 'Floor 1',
            'subnet_type' => SubnetType::ASSIGNABLE->value,
        ]);

        Livewire::actingAs($this->admin)
            ->test(SubnetsIndex::class)
            ->assertSee('Campus Data Block')
            ->call('drillDown', $parent->id)
            ->assertSet('selectedParentId', $parent->id)
            ->assertSee('Floor 1')
            ->call('openSplitModal', $child->id)
            ->assertSet('showSplitModal', true)
            ->set('splitTargetPrefix', 25)
            ->call('generateSplitPreview')
            ->assertCount('splitPreviewList', 2)
            ->call('drillDown', null)
            ->assertSet('selectedParentId', null);
    }
}
