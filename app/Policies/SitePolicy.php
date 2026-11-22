<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Site;
use App\Models\User;

class SitePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user, Site $site): bool
    {
        return $user->isActive();
    }

    public function create(User $user): bool
    {
        return $user->canManageNetwork();
    }

    public function update(User $user, Site $site): bool
    {
        return $user->canManageNetwork();
    }

    public function delete(User $user, Site $site): bool
    {
        // Prevent deletion if dependent locations or L2 domains exist
        if ($site->locations()->exists() || $site->l2Domains()->exists()) {
            return false;
        }

        return $user->isSuperAdmin();
    }
}

