<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\AddressSpace\Enums\VrfStatus;
use App\Domain\Security\Enums\UserRole;
use App\Models\AuditEvent;
use App\Models\Site;
use App\Models\User;
use App\Models\Vrf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VrfManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::NETWORK_ADMIN,
            'status' => 'active',
        ]);

        $this->viewer = User::factory()->create([
            'role' => UserRole::VIEWER,
            'status' => 'active',
        ]);
    }

    public function test_mandatory_default_vrf_can_be_retrieved_or_initialized(): void
    {
        $defaultVrf = Vrf::getDefault();

        $this->assertNotNull($defaultVrf);
        $this->assertTrue($defaultVrf->isDefault());
        $this->assertEquals('VRF-GLOBAL', $defaultVrf->code);
        $this->assertEquals(VrfStatus::ACTIVE, $defaultVrf->status);
    }

    public function test_network_admin_can_create_new_vrf_with_audit_trail(): void
    {
        $component = \Livewire\Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Vrfs\Index::class)
            ->set('code', 'VRF-CARDIOLOGY')
            ->set('name', 'Cardiology Imaging & Telemetry')
            ->set('route_distinguisher', '65001:300')
            ->set('status', 'active')
            ->call('createVrf');

        $component->assertHasNoErrors();

        $this->assertDatabaseHas('vrfs', [
            'code' => 'VRF-CARDIOLOGY',
            'name' => 'Cardiology Imaging & Telemetry',
            'route_distinguisher' => '65001:300',
            'is_default' => 0,
        ]);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'vrf.created',
            'actor_id' => $this->admin->id,
        ]);
    }

    public function test_duplicate_vrf_code_is_rejected(): void
    {
        Vrf::create([
            'code' => 'VRF-PACS',
            'name' => 'Existing PACS',
            'status' => VrfStatus::ACTIVE,
        ]);

        $component = \Livewire\Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Vrfs\Index::class)
            ->set('code', 'VRF-PACS')
            ->set('name', 'Duplicate PACS Attempt')
            ->set('status', 'active')
            ->call('createVrf');

        $component->assertHasErrors(['code']);
    }

    public function test_mandatory_default_vrf_cannot_be_deleted(): void
    {
        $defaultVrf = Vrf::getDefault();

        $action = app(\App\Actions\AddressSpace\DeleteVrfAction::class);

        $this->expectException(ValidationException::class);
        $action->execute($defaultVrf);
    }

    public function test_mandatory_default_vrf_cannot_be_retired(): void
    {
        $defaultVrf = Vrf::getDefault();

        $action = app(\App\Actions\AddressSpace\UpdateVrfAction::class);

        $this->expectException(ValidationException::class);
        $action->execute($defaultVrf, [
            'status' => VrfStatus::RETIRED,
        ]);
    }

    public function test_viewer_is_forbidden_from_creating_or_deleting_vrf(): void
    {
        $customVrf = Vrf::create([
            'code' => 'VRF-TEST-CUSTOM',
            'name' => 'Custom VRF',
            'status' => VrfStatus::ACTIVE,
        ]);

        $this->assertFalse($this->viewer->can('create', Vrf::class));
        $this->assertFalse($this->viewer->can('update', $customVrf));
        $this->assertFalse($this->viewer->can('delete', $customVrf));
    }
}

