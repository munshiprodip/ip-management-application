<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->canManageUsers();
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->canManageUsers();
    }

    public function create(User $actor): bool
    {
        return $actor->canManageUsers();
    }

    public function update(User $actor, User $target): bool
    {
        return $actor->canEditUser($target);
    }

    public function delete(User $actor, User $target): bool
    {
        return $actor->canDeleteUser($target);
    }

    public function manageRoles(User $actor): bool
    {
        return $actor->canManageRoles();
    }
}

