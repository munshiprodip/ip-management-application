<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Security\Enums\UserRole;
use App\Models\Device;
use App\Models\User;

class DevicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Device $device): bool
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

    public function update(User $user, Device $device): bool
    {
        if (in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::NETWORK_ADMIN, UserRole::OPERATOR], true)) {
            return true;
        }

        // Department operator can update devices belonging to their department
        if ($user->role === UserRole::DEPARTMENT_OPERATOR && $user->department && $device->department) {
            return $user->department === $device->department->name || $user->department === $device->department->code;
        }

        return false;
    }

    public function delete(User $user, Device $device): bool
    {
        return in_array($user->role, [
            UserRole::SUPER_ADMIN,
            UserRole::NETWORK_ADMIN,
        ], true);
    }
}

