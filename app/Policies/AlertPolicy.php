<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Security\Enums\UserRole;
use App\Models\Alert;
use App\Models\User;

class AlertPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Alert $alert): bool
    {
        return true;
    }

    public function acknowledge(User $user, Alert $alert): bool
    {
        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::NETWORK_ADMIN], true);
    }

    public function resolve(User $user, Alert $alert): bool
    {
        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::NETWORK_ADMIN], true);
    }

    public function suppress(User $user, Alert $alert): bool
    {
        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::NETWORK_ADMIN], true);
    }
}
