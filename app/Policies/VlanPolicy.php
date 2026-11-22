<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Vlan;

class VlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user, Vlan $vlan): bool
    {
        return $user->isActive();
    }

    public function create(User $user): bool
    {
        return $user->canManageNetwork();
    }

    public function update(User $user, Vlan $vlan): bool
    {
        return $user->canManageNetwork();
    }

    public function delete(User $user, Vlan $vlan): bool
    {
        return $user->canManageNetwork();
    }
}
