<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Security\Enums\UserRole;
use App\Models\IpAddress;
use App\Models\User;

class IpAddressPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, IpAddress $ip): bool
    {
        return true;
    }

    public function allocate(User $user): bool
    {
        return $user->canOperate();
    }

    public function release(User $user, ?IpAddress $ip = null): bool
    {
        return $user->canOperate();
    }

    public function transfer(User $user, ?IpAddress $ip = null): bool
    {
        return $user->canManageNetwork();
    }

    public function reserve(User $user): bool
    {
        return $user->canOperate();
    }
}
