<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Vrf;

class VrfPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user, Vrf $vrf): bool
    {
        return $user->isActive();
    }

    public function create(User $user): bool
    {
        return $user->canManageNetwork();
    }

    public function update(User $user, Vrf $vrf): bool
    {
        return $user->canManageNetwork();
    }

    public function delete(User $user, Vrf $vrf): bool
    {
        // Non-negotiable invariant: The default VRF can never be deleted
        if ($vrf->isDefault()) {
            return false;
        }

        return $user->canManageNetwork();
    }
}
