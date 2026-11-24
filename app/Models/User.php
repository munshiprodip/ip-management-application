<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Security\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'department',
        'phone',
        'timezone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SUPER_ADMIN;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [
            UserRole::SUPER_ADMIN,
            UserRole::ADMIN,
            UserRole::NETWORK_ADMIN,
        ], true);
    }

    public function isNetworkAdmin(): bool
    {
        return $this->isAdmin();
    }

    public function isOperator(): bool
    {
        return in_array($this->role, [
            UserRole::SUPER_ADMIN,
            UserRole::ADMIN,
            UserRole::NETWORK_ADMIN,
            UserRole::USER,
            UserRole::OPERATOR,
        ], true);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function canManageRoles(): bool
    {
        return $this->isActive() && $this->isSuperAdmin();
    }

    public function canManageUsers(): bool
    {
        return $this->isActive() && $this->isAdmin();
    }

    public function canAssignRole(UserRole|string $role): bool
    {
        $roleValue = $role instanceof UserRole ? $role->value : (string) $role;

        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->isAdmin()) {
            return $roleValue !== UserRole::SUPER_ADMIN->value;
        }

        return false;
    }

    public function canEditUser(User $target): bool
    {
        if (! $this->canManageUsers()) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->isAdmin()) {
            // Admin cannot edit Super Admin accounts
            return ! $target->isSuperAdmin();
        }

        return false;
    }

    public function canDeleteUser(User $target): bool
    {
        if (! $this->canManageUsers()) {
            return false;
        }

        // Cannot delete oneself
        if ($this->id === $target->id) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            // If deleting a Super Admin, verify at least one other active Super Admin remains
            if ($target->isSuperAdmin()) {
                $count = User::where('role', UserRole::SUPER_ADMIN->value)
                    ->where('status', 'active')
                    ->count();
                return $count > 1;
            }
            return true;
        }

        if ($this->isAdmin()) {
            // Admin cannot delete a Super Admin account
            return ! $target->isSuperAdmin();
        }

        return false;
    }

    public function canManageNetwork(): bool
    {
        return $this->isActive() && ($this->isAdmin() || $this->role === UserRole::USER);
    }

    public function canOperate(): bool
    {
        return $this->isActive();
    }

    public function hasRole(UserRole|string|array $roles): bool
    {
        $currentRole = $this->role instanceof UserRole ? $this->role->value : (string) $this->role;
        $targetRoles = is_array($roles) ? $roles : [$roles];

        foreach ($targetRoles as $target) {
            $targetValue = $target instanceof UserRole ? $target->value : (string) $target;
            if ($currentRole === $targetValue) {
                return true;
            }
        }

        return false;
    }
}
