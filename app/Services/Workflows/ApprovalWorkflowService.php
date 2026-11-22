<?php

declare(strict_types=1);

namespace App\Services\Workflows;

use App\Domain\Workflows\Enums\RequestStatus;
use App\Models\IpRequest;
use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;

class ApprovalWorkflowService
{
    /**
     * Validates whether an actor is authorized to approve or reject a change request.
     * Enforces the two-person rule (T-022), status lifecycle, and scoping rules.
     */
    public function validateApproval(IpRequest $request, User $actor): void
    {
        if (! $actor->isActive()) {
            throw new AuthorizationException('Inactive users cannot perform approval actions.');
        }

        // T-022: Separation of duties — requester cannot approve their own change request
        if ((int) $request->requester_id === (int) $actor->id) {
            throw new DomainException('Separation of duties violation: Requester cannot approve their own change request.');
        }

        // Role check: Only Network Admins, Super Admins, and designated Approvers may approve
        if (! ($actor->isNetworkAdmin() || $actor->isApprover())) {
            throw new AuthorizationException('Actor does not have approval privileges.');
        }

        // Lifecycle check: Only pending requests may be decided
        if (! $request->isPending()) {
            throw new DomainException(sprintf(
                "Cannot decide request %s: current status is '%s'. Only pending requests may be approved or rejected.",
                $request->request_number,
                $request->status->value
            ));
        }

        // T-021: Scoped approval check for department-restricted operators
        if ($actor->isDepartmentOperator() && $request->department_id !== null) {
            if (! $actor->hasScope('department', (int) $request->department_id)) {
                throw new AuthorizationException('Approver cannot decide request outside authorized department scope.');
            }
        }
    }

    /**
     * Checks if the given actor can approve the request without throwing.
     */
    public function canApprove(IpRequest $request, User $actor): bool
    {
        try {
            $this->validateApproval($request, $actor);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}

