<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\AddressSpace\AllocateIpAction;
use App\Actions\AddressSpace\CreateSubnetAction;
use App\Domain\AddressSpace\Enums\SubnetType;
use App\Domain\Security\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_host_type_allocations_and_quantities(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'status' => 'active',
        ]);

        $subnetAction = app(CreateSubnetAction::class);
        $subnet = $subnetAction->execute([
            'cidr' => '10.10.10.0/24',
            'name' => 'Clinical Subnet',
            'subnet_type' => SubnetType::ASSIGNABLE,
            'is_allocation_enabled' => true,
        ]);

        $allocateAction = app(AllocateIpAction::class);

        // Allocate 3 medical devices and 2 servers
        for ($i = 1; $i <= 3; $i++) {
            $allocateAction->execute([
                'subnet_id' => $subnet->id,
                'ip_address' => "10.10.10.1{$i}",
                'host_type' => 'medical',
                'assigned_to' => "ICU Bed {$i} Monitor",
            ]);
        }

        for ($i = 1; $i <= 2; $i++) {
            $allocateAction->execute([
                'subnet_id' => $subnet->id,
                'ip_address' => "10.10.10.2{$i}",
                'host_type' => 'server',
                'assigned_to' => "App Server {$i}",
            ]);
        }

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Host Type Allocations');
        $response->assertSee('Medical devices');
        $response->assertSee('Servers');
        $response->assertSee('5 TOTAL ASSIGNED');

        Livewire::actingAs($user)
            ->test(\App\Livewire\Dashboard\Index::class)
            ->assertSee('Host Type Allocations')
            ->assertSee('Medical devices')
            ->assertSee('Servers');
    }

    public function test_development_stage_indicators_are_removed_from_ui(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::NETWORK_ADMIN,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('DB: sqlite');
        $response->assertDontSee('TZ: Asia/Dhaka');
        $response->assertDontSee('SAFE / OFF');
        $response->assertDontSee('STREAMLINED IPAM CORE');
        $response->assertDontSee('v1.0.0');
    }
}
