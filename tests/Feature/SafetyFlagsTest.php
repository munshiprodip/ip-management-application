<?php

namespace Tests\Feature;

use App\Domain\Security\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SafetyFlagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_discovery_is_disabled_by_default(): void
    {
        $this->assertFalse(
            config('ipam.discovery_enabled'),
            'SAFETY INVARIANT VIOLATION: Discovery must be disabled by default.'
        );
    }

    public function test_external_writes_are_disabled_by_default(): void
    {
        $this->assertFalse(
            config('ipam.external_writes_enabled'),
            'SAFETY INVARIANT VIOLATION: External network writes must be disabled by default.'
        );
    }

    public function test_live_discovery_targets_are_forbidden_by_default(): void
    {
        $this->assertFalse(
            config('ipam.discovery_allow_live_targets'),
            'SAFETY INVARIANT VIOLATION: Live target probes must be disabled by default.'
        );
    }

    public function test_safe_posture_is_displayed_on_dashboard_and_discovery_pages(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::NETWORK_ADMIN,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertSee('DISABLED (SAFE)');
        $response->assertSee('LOCKED (READ-ONLY)');

        $response = $this->actingAs($user)->get('/discovery');
        $response->assertSee('Safe Evidence Principle: Observations Never Overwrite Allocations');
        $response->assertSee('DISABLED (SAFE)');
    }
}

