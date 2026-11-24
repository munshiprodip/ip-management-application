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
        return $user->canOperate();
    }

    public function update(User $user, DeviceInterface $interface): bool
    {
        return $user->canOperate();
    }

    public function delete(User $user, DeviceInterface $interface): bool
    {
        return $user->canManageNetwork();
    }
}

