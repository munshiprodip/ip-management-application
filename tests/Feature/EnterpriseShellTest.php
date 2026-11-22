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

        $response = $this->get('/vlans');
        $response->assertRedirect('/login');

        $response = $this->get('/ip-addresses');
        $response->assertRedirect('/login');

        $response = $this->get('/sites');
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
        $response->assertSee('Network & IP Address Management');
        $response->assertSee('KYAMCH IPAM');

        // Check for core navigation links
        $response->assertSee(route('dashboard'));
        $response->assertSee(route('subnets.index'));
        $response->assertSee(route('vlans.index'));
        $response->assertSee(route('ip-addresses.index'));
        $response->assertSee(route('sites.index'));
    }

    public function test_all_streamlined_module_screens_render_cleanly(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::NETWORK_ADMIN,
            'status' => 'active',
        ]);

        $modules = [
            route('dashboard'),
            route('subnets.index'),
            route('vlans.index'),
            route('ip-addresses.index'),
            route('sites.index'),
        ];

        foreach ($modules as $url) {
            $response = $this->actingAs($user)->get($url);
            $response->assertStatus(200);
        }
    }
}
