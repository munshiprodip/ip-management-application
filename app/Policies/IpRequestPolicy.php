<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\IpRequest;
use App\Models\User;

class IpRequestPolicy
{
    /**
     * Determine whether the user can view any requests.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    /**
     * Determine whether the user can view the specific request.
     * Enforces Department Operator scoping restrictions (T-021).
     */
    public function view(User $user, IpRequest $request): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        if ($user->isNetworkAdmin() || $user->isAuditor()) {
            return true;
        }

        if ((int) $request->requester_id === (int) $user->id) {
            return true;
        }

        // T-021: Department operator reads other scope -> DENIED
        if ($user->isDepartmentOperator()) {
            if ($request->department_id !== null && $user->hasScope('department', (int) $request->department_id)) {
                return true;
            }

            return false;
        }

        return false;
    }

    /**
     * Determine whether the user can create requests.
     */
    public function create(User $user): bool
    {
        return $user->isActive();
    }

    /**
     * Determine whether the user can approve the request.
     * Enforces two-person separation of duties rule (T-022).
     */
    public function approve(User $user, IpRequest $request): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        // T-022: Requester cannot approve their own change request
        if ((int) $request->requester_id === (int) $user->id) {
            return false;
        }

        if (! ($user->isNetworkAdmin() || $user->isApprover())) {
            return false;
        }

        return $request->isPending();
    }

    /**
     * Determine whether the user can reject the request.
     */
    public function reject(User $user, IpRequest $request): bool
    {
        return $this->approve($user, $request);
    }

    /**
     * Determine whether the user can cancel the request.
     */
    public function cancel(User $user, IpRequest $request): bool
    {
        if (! $user->isActive() || ! $request->isPending()) {
            return false;
        }

        return (int) $request->requester_id === (int) $user->id || $user->isNetworkAdmin();
    }

    /**
     * Determine whether the user can provision the approved request.
     */
    public function provision(User $user, IpRequest $request): bool
    {
        if (! $user->isActive() || ! $user->isNetworkAdmin()) {
            return false;
        }

        return $request->isApproved() && ! $request->isProvisioned();
    }
}
