<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Security\Enums\UserRole;
use App\Models\ExportJob;
use App\Models\User;

class ExportJobPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ExportJob $job): bool
    {
        if ($job->requested_by === $user->id) {
            return true;
        }

        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::SECURITY_AUDITOR], true);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function download(User $user, ExportJob $job): bool
    {
        if ($job->isExpired()) {
            return false;
        }

        if ($job->requested_by === $user->id) {
            return true;
        }

        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::SECURITY_AUDITOR], true);
    }
}
