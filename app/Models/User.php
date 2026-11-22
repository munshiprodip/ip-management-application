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

    public function isNetworkAdmin(): bool
    {
        return in_array($this->role, [UserRole::SUPER_ADMIN, UserRole::NETWORK_ADMIN], true);
    }

    public function isOperator(): bool
    {
        return in_array($this->role, [
            UserRole::SUPER_ADMIN,
            UserRole::NETWORK_ADMIN,
            UserRole::OPERATOR,
        ], true);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function canManageNetwork(): bool
    {
        return $this->isActive() && $this->isNetworkAdmin();
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
