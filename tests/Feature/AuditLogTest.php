<?php

namespace Tests\Feature;

use App\Domain\Security\Enums\UserRole;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_event_can_be_recorded_with_actor_attribution(): void
    {
        $user = User::factory()->create([
            'name' => 'Dr. Rahman (IT Director)',
            'role' => UserRole::SUPER_ADMIN,
        ]);

        $audit = AuditEvent::record(
            'system.bootstrap',
            'Initialized Stage 0 baseline configuration',
            $user,
            ['discovery' => 'off'],
            ['discovery' => 'off', 'status' => 'ready'],
            $user
        );

        $this->assertDatabaseHas('audit_events', [
            'id' => $audit->id,
            'event_type' => 'system.bootstrap',
            'actor_id' => $user->id,
            'actor_name' => 'Dr. Rahman (IT Director)',
            'reason' => 'Initialized Stage 0 baseline configuration',
        ]);

        $this->assertEquals(['discovery' => 'off'], $audit->before_json);
        $this->assertEquals(['discovery' => 'off', 'status' => 'ready'], $audit->after_json);
    }

    public function test_audit_events_are_retrieved_in_reverse_chronological_order(): void
    {
        $first = AuditEvent::create([
            'event_type' => 'event.first',
            'actor_name' => 'Tester',
            'created_at' => now()->subMinutes(10),
        ]);

        $second = AuditEvent::create([
            'event_type' => 'event.second',
            'actor_name' => 'Tester',
            'created_at' => now(),
        ]);

        $recent = AuditEvent::recent(2)->get();

        $this->assertEquals($second->id, $recent->first()->id);
        $this->assertEquals($first->id, $recent->last()->id);
    }
}

