<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Security\Enums\UserRole;
use App\Models\IpPool;
use App\Models\User;

class IpPoolPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, IpPool $pool): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [
            UserRole::SUPER_ADMIN,
            UserRole::NETWORK_ADMIN,
        ], true);
    }

    public function update(User $user, IpPool $pool): bool
    {
        return in_array($user->role, [
            UserRole::SUPER_ADMIN,
            UserRole::NETWORK_ADMIN,
        ], true);
    }

    public function delete(User $user, IpPool $pool): bool
    {
        return in_array($user->role, [
            UserRole::SUPER_ADMIN,
            UserRole::NETWORK_ADMIN,
        ], true);
    }
}
