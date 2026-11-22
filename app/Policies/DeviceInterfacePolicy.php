<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Security\Enums\UserRole;
use App\Models\DeviceInterface;
use App\Models\User;

class DeviceInterfacePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DeviceInterface $interface): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [
            UserRole::SUPER_ADMIN,
            UserRole::NETWORK_ADMIN,
            UserRole::OPERATOR,
        ], true);
    }

    public function update(User $user, DeviceInterface $interface): bool
    {
        return in_array($user->role, [
            UserRole::SUPER_ADMIN,
            UserRole::NETWORK_ADMIN,
            UserRole::OPERATOR,
        ], true);
    }

    public function delete(User $user, DeviceInterface $interface): bool
    {
        return in_array($user->role, [
            UserRole::SUPER_ADMIN,
            UserRole::NETWORK_ADMIN,
        ], true);
    }
}
