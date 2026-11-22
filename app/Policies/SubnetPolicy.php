<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Subnet;
use App\Models\User;

class SubnetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user, Subnet $subnet): bool
    {
        return $user->isActive();
    }

    public function create(User $user): bool
    {
        return $user->canManageNetwork();
    }

    public function update(User $user, Subnet $subnet): bool
    {
        return $user->canManageNetwork();
    }

    public function delete(User $user, Subnet $subnet): bool
    {
        // Prohibit deletion if active child subnets exist
        if ($subnet->children()->exists()) {
            return false;
        }

        return $user->canManageNetwork();
    }
}

