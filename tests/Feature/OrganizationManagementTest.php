<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\UserRole;
use App\Models\Department;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_create_campus_site_via_livewire(): void
    {
        $component = \Livewire\Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\Index::class)
            ->set('site_code', 'DIAGNOSTICS')
            ->set('site_name', 'Diagnostic & Research Center')
            ->set('site_description', 'Sirajganj diagnostic annex')
            ->set('site_timezone', 'Asia/Dhaka')
            ->call('saveSite');

        $component->assertHasNoErrors();

        $this->assertDatabaseHas('sites', [
            'code' => 'DIAGNOSTICS',
            'name' => 'Diagnostic & Research Center',
            'timezone' => 'Asia/Dhaka',
        ]);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'site.created',
        ]);
    }

    public function test_duplicate_site_code_is_rejected(): void
    {
        $org = Organization::create([
            'code' => 'KYAMCH',
            'name' => 'Khwaja Yunus Ali Medical College & Hospital',
            'slug' => 'kyamch',
            'status' => 'active',
        ]);

        Site::create([
            'organization_id' => $org->id,
            'code' => 'MAIN',
            'name' => 'Existing Site',
            'status' => 'active',
        ]);

        $component = \Livewire\Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\Index::class)
            ->set('site_code', 'MAIN')
            ->set('site_name', 'Duplicate Site Attempt')
            ->call('saveSite');

        $component->assertHasErrors(['site_code']);
    }

    public function test_admin_can_create_department_via_livewire(): void
    {
        $component = \Livewire\Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\Index::class)
            ->set('dept_code', 'NEUROLOGY')
            ->set('dept_name', 'Department of Neurology')
            ->set('dept_email', 'neurology@kyamch.local')
            ->call('saveDept');

        $component->assertHasNoErrors();

        $this->assertDatabaseHas('departments', [
            'code' => 'NEUROLOGY',
            'name' => 'Department of Neurology',
            'contact_email' => 'neurology@kyamch.local',
        ]);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'department.created',
        ]);
    }
}
