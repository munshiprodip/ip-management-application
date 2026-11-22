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
}
