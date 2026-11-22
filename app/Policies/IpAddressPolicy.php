<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Security\Enums\UserRole;
use App\Models\IpAddress;
use App\Models\User;

class IpAddressPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, IpAddress $ip): bool
    {
        return true;
    }

    public function allocate(User $user): bool
    {
        return in_array($user->role, [
            UserRole::SUPER_ADMIN,
            UserRole::NETWORK_ADMIN,
            UserRole::OPERATOR,
        ], true);
    }

    public function release(User $user, ?IpAddress $ip = null): bool
    {
        return in_array($user->role, [
            UserRole::SUPER_ADMIN,
            UserRole::NETWORK_ADMIN,
            UserRole::OPERATOR,
        ], true);
    }

    public function transfer(User $user, ?IpAddress $ip = null): bool
    {
        return in_array($user->role, [
            UserRole::SUPER_ADMIN,
            UserRole::NETWORK_ADMIN,
        ], true);
    }

    public function reserve(User $user): bool
    {
        return in_array($user->role, [
            UserRole::SUPER_ADMIN,
            UserRole::NETWORK_ADMIN,
            UserRole::OPERATOR,
        ], true);
    }
}
