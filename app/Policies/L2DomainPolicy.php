<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\L2Domain;
use App\Models\User;

class L2DomainPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user, L2Domain $l2Domain): bool
    {
        return $user->isActive();
    }

    public function create(User $user): bool
    {
        return $user->canManageNetwork();
    }

    public function update(User $user, L2Domain $l2Domain): bool
    {
        return $user->canManageNetwork();
    }

    public function delete(User $user, L2Domain $l2Domain): bool
    {
        // Prohibit deletion if active child VLANs exist
        if ($l2Domain->vlans()->exists()) {
            return false;
        }

        return $user->canManageNetwork();
    }
}
