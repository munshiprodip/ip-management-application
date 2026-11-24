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
use App\Models\Subnet;
use App\Models\User;
use App\Models\Vlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class SubnetManagementTest extends TestCase
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

    public function test_admin_can_create_subnet_with_audit_event(): void
    {
        $action = app(CreateSubnetAction::class);

        $subnet = $action->execute([
            'name' => 'ICU Telemetry Subnet',
            'cidr' => '10.10.20.0/24',
            'subnet_type' => SubnetType::ASSIGNABLE,
            'status' => SubnetStatus::ACTIVE,
            'description' => 'Dedicated ICU Bedside telemetry subnet',
        ]);

        $this->assertDatabaseHas('subnets', [
            'id' => $subnet->id,
            'name' => 'ICU Telemetry Subnet',
            'cidr' => '10.10.20.0/24',
        ]);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'subnet.created',
        ]);
    }

    public function test_admin_can_update_subnet_with_audit_event(): void
    {
        $createAction = app(CreateSubnetAction::class);
        $updateAction = app(UpdateSubnetAction::class);

        $subnet = $createAction->execute([
            'name' => 'Radiology Subnet',
            'cidr' => '10.10.30.0/24',
        ]);

        $updated = $updateAction->execute($subnet, [
            'name' => 'PACS & Radiology Subnet',
            'description' => 'Updated PACS storage network',
        ]);

        $this->assertEquals('PACS & Radiology Subnet', $updated->name);
        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'subnet.updated',
        ]);
    }

    public function test_cannot_delete_subnet_with_active_children(): void
    {
        $createAction = app(CreateSubnetAction::class);
        $deleteAction = app(DeleteSubnetAction::class);

        $parent = $createAction->execute([
            'name' => 'Campus Supernet',
            'cidr' => '10.10.0.0/16',
            'subnet_type' => SubnetType::AGGREGATE,
        ]);

        $createAction->execute([
            'parent_id' => $parent->id,
            'name' => 'Campus Child Subnet',
            'cidr' => '10.10.1.0/24',
        ]);

        $this->expectException(ValidationException::class);
        $deleteAction->execute($parent);
    }

    public function test_admin_can_delete_leaf_subnet_with_audit_event(): void
    {
        $createAction = app(CreateSubnetAction::class);
        $deleteAction = app(DeleteSubnetAction::class);

        $subnet = $createAction->execute([
            'name' => 'Temporary Subnet',
            'cidr' => '10.10.99.0/24',
        ]);

        $deleteAction->execute($subnet);

        $this->assertDatabaseMissing('subnets', ['id' => $subnet->id]);
        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'subnet.deleted',
        ]);
    }

    public function test_livewire_subnets_index_renders_and_calculates_cidr(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(SubnetsIndex::class)
            ->assertStatus(200)
            ->assertSee('Subnet & CIDR Hierarchy Management')
            ->set('calcInputCidr', '172.16.0.0/24')
            ->call('runCalculation')
            ->assertSet('calcResults.network', '172.16.0.0')
            ->assertSet('calcResults.netmask', '255.255.255.0')
            ->assertSet('calcResults.broadcast', '172.16.0.255')
            ->assertSet('calcResults.usable_addresses', '254');
    }

    public function test_livewire_subnets_split_preview_and_drill_down(): void
    {
        $createAction = app(CreateSubnetAction::class);
        $subnet = $createAction->execute([
            'name' => 'Split Target',
            'cidr' => '192.168.0.0/24',
            'subnet_type' => SubnetType::AGGREGATE,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(SubnetsIndex::class)
            ->call('openSplitModal', $subnet->id)
            ->set('splitTargetPrefix', 25)
            ->call('generateSplitPreview')
            ->assertCount('splitPreviewList', 2)
            ->assertSet('splitPreviewList.0.cidr', '192.168.0.0/25')
            ->assertSet('splitPreviewList.1.cidr', '192.168.0.128/25');
    }

    public function test_livewire_subnets_expand_and_collapse_toggle(): void
    {
        $createAction = app(CreateSubnetAction::class);
        $parent = $createAction->execute([
            'name' => 'Campus Supernet',
            'cidr' => '10.20.0.0/16',
            'subnet_type' => SubnetType::AGGREGATE,
        ]);

        $child = $createAction->execute([
            'parent_id' => $parent->id,
            'name' => 'Campus Child Subnet',
            'cidr' => '10.20.1.0/24',
            'subnet_type' => SubnetType::ASSIGNABLE,
        ]);

        $this->actingAs($this->admin);

        // Initially collapsed: parent visible, child not visible in hierarchy tree
        $component = Livewire::test(SubnetsIndex::class)
            ->assertStatus(200)
            ->assertSee('10.20.0.0/16')
            ->assertDontSee('10.20.1.0/24')
            ->assertSee('1 Child');

        // Toggle expand: child becomes visible
        $component->call('toggleExpand', $parent->id)
            ->assertSee('10.20.1.0/24')
            ->assertSee('Collapse (1)');

        // Toggle collapse: child hidden again
        $component->call('toggleExpand', $parent->id)
            ->assertDontSee('10.20.1.0/24')
            ->assertSee('1 Child');

        // Expand all
        $component->call('expandAll')
            ->assertSee('10.20.1.0/24');

        // Collapse all
        $component->call('collapseAll')
            ->assertDontSee('10.20.1.0/24');
    }

    public function test_admin_can_create_and_update_subnet_with_optional_mask_and_gateway(): void
    {
        $createAction = app(CreateSubnetAction::class);
        $updateAction = app(UpdateSubnetAction::class);

        $subnet = $createAction->execute([
            'name' => 'Cardiology Subnet',
            'cidr' => '10.30.1.0/24',
            'subnet_mask' => '255.255.255.0',
            'gateway' => '10.30.1.254', // Custom gateway IP
        ]);

        $this->assertEquals('255.255.255.0', $subnet->netmask());
        $this->assertEquals('10.30.1.254', $subnet->defaultGateway());
        $this->assertDatabaseHas('subnets', [
            'id' => $subnet->id,
            'subnet_mask' => '255.255.255.0',
            'gateway' => '10.30.1.254',
        ]);

        $updated = $updateAction->execute($subnet, [
            'gateway' => '10.30.1.1',
        ]);

        $this->assertEquals('10.30.1.1', $updated->defaultGateway());
        $this->assertDatabaseHas('subnets', [
            'id' => $subnet->id,
            'gateway' => '10.30.1.1',
        ]);
    }

    public function test_livewire_can_create_subnet_with_optional_mask_and_gateway(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(SubnetsIndex::class)
            ->set('subnet_name', 'Emergency OT Subnet')
            ->set('subnet_cidr', '10.40.10.0/24')
            ->set('subnet_mask', '255.255.255.0')
            ->set('subnet_gateway', '10.40.10.1')
            ->set('subnet_type', 'assignable')
            ->set('subnet_status', 'active')
            ->call('saveSubnet')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('subnets', [
            'name' => 'Emergency OT Subnet',
            'cidr' => '10.40.10.0/24',
            'subnet_mask' => '255.255.255.0',
            'gateway' => '10.40.10.1',
        ]);

        $response = $this->get(route('subnets.index'));
        $response->assertStatus(200);
        $response->assertSee('Mask:');
        $response->assertSee('GW:');
        $response->assertSee('255.255.255.0');
        $response->assertSee('10.40.10.1');
    }

    public function test_aggregate_subnet_forces_allocation_enabled_false(): void
    {
        $createAction = app(CreateSubnetAction::class);
        $subnet = $createAction->execute([
            'name' => 'Campus WAN Supernet',
            'cidr' => '172.16.0.0/12',
            'subnet_type' => SubnetType::AGGREGATE,
            'is_allocation_enabled' => true, // Attempt to enable
        ]);

        $this->assertFalse($subnet->allocation_enabled);
        $this->assertFalse($subnet->isAssignable());
    }

    public function test_cannot_convert_aggregate_subnet_with_children_to_assignable(): void
    {
        $createAction = app(CreateSubnetAction::class);
        $updateAction = app(UpdateSubnetAction::class);

        $parent = $createAction->execute([
            'name' => 'Campus Core Block',
            'cidr' => '10.50.0.0/16',
            'subnet_type' => SubnetType::AGGREGATE,
        ]);

        $createAction->execute([
            'parent_id' => $parent->id,
            'name' => 'Campus Child Subnet',
            'cidr' => '10.50.1.0/24',
            'subnet_type' => SubnetType::ASSIGNABLE,
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('has child subnets and must remain an Aggregate Container');

        $updateAction->execute($parent, [
            'subnet_type' => SubnetType::ASSIGNABLE,
        ]);
    }

    public function test_cannot_convert_assignable_subnet_with_assigned_ips_to_aggregate(): void
    {
        $createAction = app(CreateSubnetAction::class);
        $updateAction = app(UpdateSubnetAction::class);
        $allocateAction = app(\App\Actions\AddressSpace\AllocateIpAction::class);

        $subnet = $createAction->execute([
            'name' => 'Clinical Telemetry Block',
            'cidr' => '10.60.1.0/24',
            'subnet_type' => SubnetType::ASSIGNABLE,
        ]);

        $allocateAction->execute([
            'subnet_id' => $subnet->id,
            'ip_address' => '10.60.1.5',
            'assigned_to' => 'ICU Bed Monitor',
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('has active assigned IP addresses and cannot be converted to an Aggregate Container');

        $updateAction->execute($subnet, [
            'subnet_type' => SubnetType::AGGREGATE,
        ]);
    }

    public function test_clearing_subnet_parent_validates_root_overlap(): void
    {
        $createAction = app(CreateSubnetAction::class);
        $updateAction = app(UpdateSubnetAction::class);

        // Root 1: 10.70.0.0/16
        $root = $createAction->execute([
            'name' => 'Root Block',
            'cidr' => '10.70.0.0/16',
            'subnet_type' => SubnetType::AGGREGATE,
        ]);

        // Child: 10.70.1.0/24
        $child = $createAction->execute([
            'parent_id' => $root->id,
            'name' => 'Child Block',
            'cidr' => '10.70.1.0/24',
            'subnet_type' => SubnetType::ASSIGNABLE,
        ]);

        // If we try to make child a root by removing parent_id, it would overlap with existing root 10.70.0.0/16!
        $this->expectException(ValidationException::class);
        $updateAction->execute($child, [
            'parent_id' => null,
        ]);
    }
}
