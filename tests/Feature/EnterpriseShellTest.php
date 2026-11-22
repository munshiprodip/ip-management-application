<?php

namespace Tests\Feature;

use App\Domain\Security\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');

        $response = $this->get('/subnets');
        $response->assertRedirect('/login');

        $response = $this->get('/vrfs');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_dashboard_and_navigation_shell(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Enterprise IPAM Control Center');
        $response->assertSee('KYAMCH IPAM');
        $response->assertSee('SAFE / OFF');
        $response->assertSee('LOCKED');

        // Check for all 10 core navigation links in the enterprise shell
        $response->assertSee(route('dashboard'));
        $response->assertSee(route('subnets.index'));
        $response->assertSee(route('vrfs.index'));
        $response->assertSee(route('vlans.index'));
        $response->assertSee(route('ip-addresses.index'));
        $response->assertSee(route('devices.index'));
        $response->assertSee(route('requests.index'));
        $response->assertSee(route('discovery.index'));
        $response->assertSee(route('alerts.index'));
        $response->assertSee(route('reports.index'));
        $response->assertSee(route('admin.index'));
    }

    public function test_all_enterprise_module_screens_render_cleanly(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::NETWORK_ADMIN,
            'status' => 'active',
        ]);

        $routes = [
            '/dashboard',
            '/subnets',
            '/vrfs',
            '/vlans',
            '/ip-addresses',
            '/devices',
            '/requests',
            '/discovery',
            '/alerts',
            '/reports',
            '/admin',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($user)->get($route);
            $response->assertStatus(200, "Failed asserting that route [{$route}] renders successfully.");
        }
    }
}

