<?php

declare(strict_types=1);

namespace App\Livewire\Users;

use App\Domain\Security\Enums\UserRole;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('User Management - KYAMCH IPAM')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'role')]
    public string $roleFilter = 'all';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    public int $perPage = 15;

    // Modals
    public bool $showCreateModal = false;
    public bool $showEditModal = false;
    public bool $showDeleteModal = false;
    public bool $showResetPasswordModal = false;

    // Form fields
    public ?int $editingUserId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $role = 'user';
    public string $status = 'active';
    public string $department = '';
    public string $phone = '';

    // Delete confirmation
    public ?int $deletingUserId = null;
    public string $deletingUserName = '';

    // Password reset
    public ?int $resetPasswordUserId = null;
    public string $resetPasswordUserName = '';
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canManageUsers(), 403, 'Unauthorized: Access to User Management is restricted to Administrators.');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Get roles available for assignment based on actor's role.
     * Super Admin can assign Super Admin, Admin, and User.
     * Admin can only assign Admin and User (cannot create or assign Super Admin).
     */
    public function getAvailableRoles(): array
    {
        $actor = auth()->user();

        if ($actor->isSuperAdmin()) {
            return [
                UserRole::SUPER_ADMIN,
                UserRole::ADMIN,
                UserRole::USER,
            ];
        }

        if ($actor->isAdmin()) {
            return [
                UserRole::ADMIN,
                UserRole::USER,
            ];
        }

        return [];
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->role = 'user';
        $this->status = 'active';
        $this->showCreateModal = true;
    }

    public function createUser(): void
    {
        abort_unless(auth()->user()->canManageUsers(), 403);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'same:password_confirmation'],
            'role' => ['required', 'string'],
            'status' => ['required', 'in:active,inactive'],
            'department' => ['nullable', 'string', 'max:128'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        // Security check: Admin cannot create Super Admin accounts
        if (! auth()->user()->canAssignRole($this->role)) {
            throw ValidationException::withMessages([
                'role' => 'You do not have permission to assign the Super Admin role.',
            ]);
        }

        $user = User::create([
            'name' => trim($this->name),
            'email' => strtolower(trim($this->email)),
            'password' => Hash::make($this->password),
            'role' => $this->role,
            'status' => $this->status,
            'department' => ! empty($this->department) ? trim($this->department) : null,
            'phone' => ! empty($this->phone) ? trim($this->phone) : null,
            'timezone' => 'Asia/Dhaka',
        ]);

        AuditEvent::record(
            eventType: 'user.created',
            reason: "Created user account {$user->name} ({$user->email}) with role {$user->role->value}",
            subject: $user,
            actor: auth()->user()
        );

        $msg = "User {$user->name} ({$user->email}) created successfully.";
        session()->flash('success', $msg);
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);

        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function openEditModal(int $userId): void
    {
        $user = User::findOrFail($userId);

        if (! auth()->user()->canEditUser($user)) {
            $msg = 'You do not have permission to edit a Super Administrator account.';
            $this->dispatch('notify', ['type' => 'error', 'message' => $msg]);
            return;
        }

        $this->resetForm();
        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        $this->status = $user->status;
        $this->department = $user->department ?? '';
        $this->phone = $user->phone ?? '';

        $this->showEditModal = true;
    }

    public function updateUser(): void
    {
        $user = User::findOrFail($this->editingUserId);

        abort_unless(auth()->user()->canEditUser($user), 403, 'Unauthorized: Cannot edit this user account.');

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'string'],
            'status' => ['required', 'in:active,inactive'],
            'department' => ['nullable', 'string', 'max:128'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        // Security check: Admin cannot promote any user to Super Admin
        if (! auth()->user()->canAssignRole($this->role)) {
            throw ValidationException::withMessages([
                'role' => 'You do not have permission to assign the Super Admin role.',
            ]);
        }

        // Security check: Cannot deactivate oneself
        if (auth()->id() === $user->id && $this->status === 'inactive') {
            throw ValidationException::withMessages([
                'status' => 'You cannot deactivate your own administrative account.',
            ]);
        }

        // Security check: Cannot deactivate the last remaining active super admin
        if ($user->isSuperAdmin() && $this->status === 'inactive') {
            $activeCount = User::where('role', UserRole::SUPER_ADMIN->value)
                ->where('status', 'active')
                ->count();
            if ($activeCount <= 1) {
                throw ValidationException::withMessages([
                    'status' => 'Cannot deactivate the last remaining Platform Super Administrator.',
                ]);
            }
        }

        $user->update([
            'name' => trim($this->name),
            'email' => strtolower(trim($this->email)),
            'role' => $this->role,
            'status' => $this->status,
            'department' => ! empty($this->department) ? trim($this->department) : null,
            'phone' => ! empty($this->phone) ? trim($this->phone) : null,
        ]);

        AuditEvent::record(
            eventType: 'user.updated',
            reason: "Updated user account {$user->name} ({$user->email})",
            subject: $user,
            actor: auth()->user()
        );

        $msg = "User {$user->name} updated successfully.";
        session()->flash('success', $msg);
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);

        $this->showEditModal = false;
        $this->resetForm();
    }

    public function toggleStatus(int $userId): void
    {
        $user = User::findOrFail($userId);

        if (! auth()->user()->canEditUser($user)) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'You do not have permission to modify a Super Administrator account.']);
            return;
        }

        if (auth()->id() === $user->id) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'You cannot deactivate your own account.']);
            return;
        }

        $newStatus = $user->status === 'active' ? 'inactive' : 'active';

        if ($user->isSuperAdmin() && $newStatus === 'inactive') {
            $activeCount = User::where('role', UserRole::SUPER_ADMIN->value)
                ->where('status', 'active')
                ->count();
            if ($activeCount <= 1) {
                $this->dispatch('notify', ['type' => 'error', 'message' => 'Cannot deactivate the last active Super Administrator.']);
                return;
            }
        }

        $user->update(['status' => $newStatus]);

        AuditEvent::record(
            eventType: 'user.status_toggled',
            reason: "User {$user->name} status changed to {$newStatus}",
            subject: $user,
            actor: auth()->user()
        );

        $msg = "User {$user->name} is now {$newStatus}.";
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
    }

    public function openDeleteModal(int $userId): void
    {
        $user = User::findOrFail($userId);

        if (! auth()->user()->canDeleteUser($user)) {
            if (auth()->id() === $user->id) {
                $msg = 'You cannot delete your own account.';
            } elseif ($user->isSuperAdmin() && ! auth()->user()->isSuperAdmin()) {
                $msg = 'You do not have permission to delete a Super Administrator account.';
            } else {
                $msg = 'Cannot delete the last remaining Super Administrator account.';
            }
            $this->dispatch('notify', ['type' => 'error', 'message' => $msg]);
            return;
        }

        $this->deletingUserId = $user->id;
        $this->deletingUserName = $user->name;
        $this->showDeleteModal = true;
    }

    public function deleteUser(): void
    {
        $user = User::findOrFail($this->deletingUserId);

        abort_unless(auth()->user()->canDeleteUser($user), 403, 'Unauthorized: Cannot delete this user.');

        $name = $user->name;
        $email = $user->email;

        AuditEvent::record(
            eventType: 'user.deleted',
            reason: "Deleted user account {$name} ({$email})",
            subject: $user,
            actor: auth()->user()
        );

        $user->delete();

        $msg = "User {$name} ({$email}) has been permanently deleted.";
        session()->flash('success', $msg);
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);

        $this->showDeleteModal = false;
        $this->deletingUserId = null;
        $this->deletingUserName = '';
    }

    public function openResetPasswordModal(int $userId): void
    {
        $user = User::findOrFail($userId);

        if (! auth()->user()->canEditUser($user)) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'You do not have permission to reset password for a Super Administrator.']);
            return;
        }

        $this->resetPasswordUserId = $user->id;
        $this->resetPasswordUserName = $user->name;
        $this->newPassword = '';
        $this->newPasswordConfirmation = '';
        $this->resetErrorBag();
        $this->showResetPasswordModal = true;
    }

    public function resetPassword(): void
    {
        $user = User::findOrFail($this->resetPasswordUserId);

        abort_unless(auth()->user()->canEditUser($user), 403, 'Unauthorized.');

        $this->validate([
            'newPassword' => ['required', 'string', 'min:8', 'same:newPasswordConfirmation'],
        ]);

        $user->update([
            'password' => Hash::make($this->newPassword),
        ]);

        AuditEvent::record(
            eventType: 'user.password_reset',
            reason: "Password reset for user {$user->name} ({$user->email})",
            subject: $user,
            actor: auth()->user()
        );

        $msg = "Password reset successfully for {$user->name}.";
        session()->flash('success', $msg);
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);

        $this->showResetPasswordModal = false;
        $this->resetPasswordUserId = null;
        $this->resetPasswordUserName = '';
        $this->newPassword = '';
        $this->newPasswordConfirmation = '';
    }

    private function resetForm(): void
    {
        $this->editingUserId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->role = 'user';
        $this->status = 'active';
        $this->department = '';
        $this->phone = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = User::query();

        if (! empty($this->search)) {
            $term = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('department', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            });
        }

        if ($this->roleFilter !== 'all') {
            if ($this->roleFilter === 'admin') {
                $query->whereIn('role', [UserRole::ADMIN->value, UserRole::NETWORK_ADMIN->value]);
            } elseif ($this->roleFilter === 'user') {
                $query->whereIn('role', [
                    UserRole::USER->value,
                    UserRole::OPERATOR->value,
                    UserRole::VIEWER->value,
                    UserRole::DEPARTMENT_OPERATOR->value,
                    UserRole::AUDITOR->value,
                    UserRole::AGENT->value,
                    UserRole::APPROVER->value,
                ]);
            } else {
                $query->where('role', $this->roleFilter);
            }
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $users = $query->orderBy('name')->paginate($this->perPage);

        // Counts for KPI Cards
        $totalUsers = User::count();
        $activeUsers = User::where('status', 'active')->count();
        $superAdminCount = User::where('role', UserRole::SUPER_ADMIN->value)->count();
        $adminCount = User::whereIn('role', [UserRole::ADMIN->value, UserRole::NETWORK_ADMIN->value])->count();
        $userCount = User::whereNotIn('role', [
            UserRole::SUPER_ADMIN->value,
            UserRole::ADMIN->value,
            UserRole::NETWORK_ADMIN->value,
        ])->count();

        return view('livewire.users.index', [
            'users' => $users,
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'superAdminCount' => $superAdminCount,
            'adminCount' => $adminCount,
            'userCount' => $userCount,
            'availableRoles' => $this->getAvailableRoles(),
        ]);
    }
}

