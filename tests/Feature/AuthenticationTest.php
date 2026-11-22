<?php

namespace Tests\Feature;

use App\Domain\Security\Enums\UserRole;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('KYAMCH Smart IPAM');
        $response->assertSee('Restricted Hospital System');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'email' => 'netadmin@kyamch.local',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::NETWORK_ADMIN,
            'status' => 'active',
        ]);

        $this->post('/login', [
            'email' => 'netadmin@kyamch.local',
            'password' => 'Password123!',
        ]);

        // Alternatively test via Livewire login component or post
        $component = \Livewire\Livewire::test(\App\Livewire\Auth\Login::class)
            ->set('email', 'netadmin@kyamch.local')
            ->set('password', 'Password123!')
            ->call('login');

        $component->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        // Verify audit event recorded
        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'auth.login',
            'actor_id' => $user->id,
        ]);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'operator@kyamch.local',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::OPERATOR,
            'status' => 'active',
        ]);

        $component = \Livewire\Livewire::test(\App\Livewire\Auth\Login::class)
            ->set('email', 'operator@kyamch.local')
            ->set('password', 'WrongPassword!')
            ->call('login');

        $component->assertHasErrors(['email']);
        $this->assertGuest();

        // Verify audit event recorded for failed attempt
        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'auth.failed',
        ]);
    }

    public function test_deactivated_users_cannot_authenticate(): void
    {
        User::factory()->create([
            'email' => 'deactivated@kyamch.local',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::VIEWER,
            'status' => 'inactive',
        ]);

        $component = \Livewire\Livewire::test(\App\Livewire\Auth\Login::class)
            ->set('email', 'deactivated@kyamch.local')
            ->set('password', 'Password123!')
            ->call('login');

        $component->assertHasErrors(['email']);
        $this->assertGuest();

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'auth.blocked_inactive',
        ]);
    }

    public function test_users_can_logout_and_audit_event_is_created(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'auth.logout',
            'actor_id' => $user->id,
        ]);
    }
}
