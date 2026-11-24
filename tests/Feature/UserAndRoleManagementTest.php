<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\UserRole;
use App\Livewire\Roles\Index as RolesIndex;
use App\Livewire\Users\Index as UsersIndex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserAndRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $admin;
    private User $standardUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'name' => 'Root Super Admin',
            'email' => 'root@kyamch.local',
            'role' => UserRole::SUPER_ADMIN,
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Department Admin',
            'email' => 'deptadmin@kyamch.local',
            'role' => UserRole::ADMIN,
            'status' => 'active',
        ]);

        $this->standardUser = User::factory()->create([
            'name' => 'Clinical Staff',
            'email' => 'staff@kyamch.local',
            'role' => UserRole::USER,
            'status' => 'active',
        ]);
    }

    public function test_guest_cannot_access_users_or_roles(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
        $this->get(route('roles.index'))->assertRedirect(route('login'));
    }

    public function test_standard_user_cannot_access_users_or_roles_but_can_access_ipam(): void
    {
        $this->actingAs($this->standardUser);

        // Blocked from User and Role management
        $this->get(route('users.index'))->assertStatus(403);
        $this->get(route('roles.index'))->assertStatus(403);

        // Allowed to access core IPAM modules
        $this->get(route('dashboard'))->assertStatus(200);
        $this->get(route('subnets.index'))->assertStatus(200);
        $this->get(route('vlans.index'))->assertStatus(200);
        $this->get(route('ip-addresses.index'))->assertStatus(200);
        $this->get(route('scanner.index'))->assertStatus(200);
    }

    public function test_admin_can_access_users_page_but_cannot_access_roles_page(): void
    {
        $this->actingAs($this->admin);

        // Can access User Management
        $this->get(route('users.index'))->assertStatus(200);

        // Cannot access Role Management
        $this->get(route('roles.index'))->assertStatus(403);
    }

    public function test_super_admin_can_access_both_users_and_roles_pages(): void
    {
        $this->actingAs($this->superAdmin);

        $this->get(route('users.index'))->assertStatus(200);
        $this->get(route('roles.index'))->assertStatus(200);
    }

    public function test_super_admin_can_create_user_with_any_of_the_three_roles(): void
    {
        $this->actingAs($this->superAdmin);

        // 1. Super Admin creates another Super Admin
        Livewire::test(UsersIndex::class)
            ->call('openCreateModal')
            ->set('name', 'Second Super Admin')
            ->set('email', 'super2@kyamch.local')
            ->set('password', 'SecretPassword123!')
            ->set('password_confirmation', 'SecretPassword123!')
            ->set('role', UserRole::SUPER_ADMIN->value)
            ->set('status', 'active')
            ->call('createUser')
            ->assertDispatched('notify')
            ->assertSet('showCreateModal', false);

        $this->assertDatabaseHas('users', [
            'email' => 'super2@kyamch.local',
            'role' => UserRole::SUPER_ADMIN->value,
        ]);

        // 2. Super Admin creates an Admin
        Livewire::test(UsersIndex::class)
            ->call('openCreateModal')
            ->set('name', 'New Admin Account')
            ->set('email', 'newadmin@kyamch.local')
            ->set('password', 'SecretPassword123!')
            ->set('password_confirmation', 'SecretPassword123!')
            ->set('role', UserRole::ADMIN->value)
            ->set('status', 'active')
            ->call('createUser')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('users', [
            'email' => 'newadmin@kyamch.local',
            'role' => UserRole::ADMIN->value,
        ]);

        // 3. Super Admin creates a User
        Livewire::test(UsersIndex::class)
            ->call('openCreateModal')
            ->set('name', 'New Operator Staff')
            ->set('email', 'newstaff@kyamch.local')
            ->set('password', 'SecretPassword123!')
            ->set('password_confirmation', 'SecretPassword123!')
            ->set('role', UserRole::USER->value)
            ->set('status', 'active')
            ->call('createUser')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('users', [
            'email' => 'newstaff@kyamch.local',
            'role' => UserRole::USER->value,
        ]);
    }

    public function test_admin_can_create_admin_and_user_but_cannot_create_super_admin(): void
    {
        $this->actingAs($this->admin);

        // 1. Admin creates a standard User -> Allowed
        Livewire::test(UsersIndex::class)
            ->call('openCreateModal')
            ->set('name', 'Created By Admin')
            ->set('email', 'byadmin@kyamch.local')
            ->set('password', 'SecretPassword123!')
            ->set('password_confirmation', 'SecretPassword123!')
            ->set('role', UserRole::USER->value)
            ->set('status', 'active')
            ->call('createUser')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('users', [
            'email' => 'byadmin@kyamch.local',
            'role' => UserRole::USER->value,
        ]);

        // 2. Admin creates an Admin -> Allowed
        Livewire::test(UsersIndex::class)
            ->call('openCreateModal')
            ->set('name', 'Peer Admin')
            ->set('email', 'peeradmin@kyamch.local')
            ->set('password', 'SecretPassword123!')
            ->set('password_confirmation', 'SecretPassword123!')
            ->set('role', UserRole::ADMIN->value)
            ->set('status', 'active')
            ->call('createUser')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('users', [
            'email' => 'peeradmin@kyamch.local',
            'role' => UserRole::ADMIN->value,
        ]);

        // 3. Admin attempts to create Super Admin -> Strictly Blocked with validation exception
        Livewire::test(UsersIndex::class)
            ->call('openCreateModal')
            ->set('name', 'Attempted Super Admin')
            ->set('email', 'escalation@kyamch.local')
            ->set('password', 'SecretPassword123!')
            ->set('password_confirmation', 'SecretPassword123!')
            ->set('role', UserRole::SUPER_ADMIN->value)
            ->set('status', 'active')
            ->call('createUser')
            ->assertHasErrors(['role']);

        $this->assertDatabaseMissing('users', [
            'email' => 'escalation@kyamch.local',
        ]);
    }

    public function test_admin_cannot_edit_or_delete_super_admin(): void
    {
        $this->actingAs($this->admin);

        // Attempt to edit Super Admin
        Livewire::test(UsersIndex::class)
            ->call('openEditModal', $this->superAdmin->id)
            ->assertSet('showEditModal', false)
            ->assertDispatched('notify');

        // Attempt to delete Super Admin
        Livewire::test(UsersIndex::class)
            ->call('openDeleteModal', $this->superAdmin->id)
            ->assertSet('showDeleteModal', false)
            ->assertDispatched('notify');
    }

    public function test_admin_cannot_promote_user_to_super_admin(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(UsersIndex::class)
            ->call('openEditModal', $this->standardUser->id)
            ->assertSet('showEditModal', true)
            ->set('role', UserRole::SUPER_ADMIN->value)
            ->call('updateUser')
            ->assertHasErrors(['role']);

        $this->standardUser->refresh();
        $this->assertEquals(UserRole::USER, $this->standardUser->role);
    }

    public function test_user_cannot_delete_self(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(UsersIndex::class)
            ->call('openDeleteModal', $this->admin->id)
            ->assertSet('showDeleteModal', false)
            ->assertDispatched('notify');
    }

    public function test_cannot_delete_the_last_super_admin(): void
    {
        $this->actingAs($this->superAdmin);

        // Create a temporary second super admin to test deleting him
        $secondSuper = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'status' => 'active',
        ]);

        // When 2 super admins exist, superAdmin can delete secondSuper
        Livewire::test(UsersIndex::class)
            ->call('openDeleteModal', $secondSuper->id)
            ->assertSet('showDeleteModal', true)
            ->call('deleteUser')
            ->assertDispatched('notify');

        $this->assertDatabaseMissing('users', ['id' => $secondSuper->id]);

        // Now only 1 super admin remains ($this->superAdmin)
        // If another super admin attempts to delete the last one, it is blocked
        $this->assertFalse($this->superAdmin->canDeleteUser($this->superAdmin));
    }

    public function test_admin_can_toggle_status_and_reset_password_for_user(): void
    {
        $this->actingAs($this->admin);

        // 1. Toggle status
        Livewire::test(UsersIndex::class)
            ->call('toggleStatus', $this->standardUser->id)
            ->assertDispatched('notify');

        $this->standardUser->refresh();
        $this->assertEquals('inactive', $this->standardUser->status);

        // 2. Reset password
        Livewire::test(UsersIndex::class)
            ->call('openResetPasswordModal', $this->standardUser->id)
            ->assertSet('showResetPasswordModal', true)
            ->set('newPassword', 'NewBrandPassword123!')
            ->set('newPasswordConfirmation', 'NewBrandPassword123!')
            ->call('resetPassword')
            ->assertDispatched('notify')
            ->assertSet('showResetPasswordModal', false);

        $this->standardUser->refresh();
        $this->assertTrue(Hash::check('NewBrandPassword123!', $this->standardUser->password));
    }

    public function test_roles_page_displays_all_three_roles_and_matrix_for_super_admin(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(RolesIndex::class)
            ->assertStatus(200)
            ->assertSee('Super Admin')
            ->assertSee('Admin')
            ->assertSee('User')
            ->assertSee('Permission Comparison Matrix')
            ->assertSee('Active Member Roster');
    }
}

