<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\AddressSpace\CreateSubnetAction;
use App\Domain\AddressSpace\Enums\SubnetType;
use App\Models\Subnet;
use App\Models\User;
use App\Models\Vlan;
use App\Models\Site;
use App\Models\HostType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationFeedbackTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);
    }

    public function test_subnet_create_and_edit_dispatches_notify_and_flashes_message(): void
    {
        $this->actingAs($this->admin);

        // 1. Create root subnet
        $comp = Livewire::test(\App\Livewire\Subnets\Index::class)
            ->call('openCreateModal', null)
            ->set('subnet_cidr', '10.50.0.0/16')
            ->set('subnet_name', 'Campus Core Aggregation')
            ->set('subnet_type', SubnetType::AGGREGATE->value)
            ->set('subnet_allocation_enabled', false)
            ->call('saveSubnet');

        $comp->assertDispatched('notify', function (string $event, array $params) {
            return ($params[0]['type'] ?? '') === 'success' && str_contains($params[0]['message'] ?? '', '10.50.0.0/16');
        });

        $createdSubnet = Subnet::where('cidr', '10.50.0.0/16')->firstOrFail();

        // 2. Edit existing subnet
        $editComp = Livewire::test(\App\Livewire\Subnets\Index::class)
            ->call('openEditModal', $createdSubnet->id)
            ->set('subnet_name', 'Updated Core Aggregation')
            ->call('saveSubnet');

        $editComp->assertDispatched('notify', function (string $event, array $params) {
            return ($params[0]['type'] ?? '') === 'success' && str_contains($params[0]['message'] ?? '', 'updated');
        });
    }

    public function test_ip_address_assignment_dispatches_notify_event(): void
    {
        $this->actingAs($this->admin);

        $subnetAction = app(CreateSubnetAction::class);
        $subnet = $subnetAction->execute([
            'cidr' => '10.60.0.0/24',
            'name' => 'Assignable Subnet',
            'subnet_type' => SubnetType::ASSIGNABLE,
            'allocation_enabled' => true,
        ]);

        Livewire::test(\App\Livewire\IPAddresses\Index::class, ['subnet' => $subnet->id])
            ->call('openAllocateModal', '10.60.0.10')
            ->set('alloc_assigned_to', 'ICU Patient Monitor 1')
            ->call('saveAllocation')
            ->assertDispatched('notify', function (string $event, array $params) {
                return ($params[0]['type'] ?? '') === 'success' && str_contains($params[0]['message'] ?? '', '10.60.0.10');
            });
    }

    public function test_vlan_registration_dispatches_notify_event(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(\App\Livewire\Vlans\Index::class)
            ->call('openCreateVlanModal')
            ->set('vlan_number', 88)
            ->set('vlan_name', 'SURGICAL-THEATRE')
            ->set('vlan_security_zone', 'internal')
            ->set('vlan_status', 'active')
            ->call('saveVlan')
            ->assertDispatched('notify', function (string $event, array $params) {
                return ($params[0]['type'] ?? '') === 'success' && str_contains($params[0]['message'] ?? '', '88');
            });
    }

    public function test_campus_site_create_dispatches_notify_event(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(\App\Livewire\Sites\Index::class)
            ->call('openCreateModal')
            ->set('name', 'Cancer Research Annex')
            ->set('code', 'KYAMCH-ANNEX')
            ->set('status', 'active')
            ->call('saveSite')
            ->assertDispatched('notify', function (string $event, array $params) {
                return ($params[0]['type'] ?? '') === 'success';
            });
    }

    public function test_host_type_create_and_toggle_dispatches_notify_event(): void
    {
        $this->actingAs($this->admin);

        $comp = Livewire::test(\App\Livewire\HostTypes\Index::class)
            ->call('openCreateModal')
            ->set('name', 'Infusion Pump')
            ->set('slug', 'infusion_pump')
            ->set('color', 'purple')
            ->call('saveHostType');

        $comp->assertDispatched('notify', function (string $event, array $params) {
            return ($params[0]['type'] ?? '') === 'success' && str_contains($params[0]['message'] ?? '', 'Infusion Pump');
        });

        $ht = HostType::where('slug', 'infusion_pump')->firstOrFail();

        Livewire::test(\App\Livewire\HostTypes\Index::class)
            ->call('toggleStatus', $ht->id)
            ->assertDispatched('notify', function (string $event, array $params) {
                return ($params[0]['type'] ?? '') === 'success';
            });
    }
}

