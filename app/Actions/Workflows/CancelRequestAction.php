<?php

declare(strict_types=1);

namespace App\Actions\Workflows;

use App\Domain\Workflows\Enums\RequestStatus;
use App\Models\AuditEvent;
use App\Models\IpRequest;
use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class CancelRequestAction
{
    /**
     * Cancels a pending request by the requester or a network admin.
     */
    public function execute(IpRequest $request, User $actor, string $reason): IpRequest
    {
        if (trim($reason) === '') {
            throw new DomainException('A cancellation reason must be provided.');
        }

        // Only the requester or an administrator can cancel a request
        if ((int) $request->requester_id !== (int) $actor->id && ! $actor->isNetworkAdmin()) {
            throw new AuthorizationException('Only the requester or a network administrator can cancel this request.');
        }

        if (! $request->isPending()) {
            throw new DomainException(sprintf(
                "Cannot cancel request %s: current status is '%s'. Only pending requests can be cancelled.",
                $request->request_number,
                $request->status->value
            ));
        }

        return DB::transaction(function () use ($request, $actor, $reason) {
            /** @var IpRequest $lockedRequest */
            $lockedRequest = IpRequest::where('id', $request->id)->lockForUpdate()->firstOrFail();

            if (! $lockedRequest->isPending()) {
                throw new DomainException("Cannot cancel request: status changed to '{$lockedRequest->status->value}'.");
            }

            $lockedRequest->update([
                'status' => RequestStatus::CANCELLED,
                'cancellation_reason' => $reason,
            ]);

            AuditEvent::record(
                eventType: 'request.cancelled',
                reason: sprintf('Cancelled request %s: %s', $lockedRequest->request_number, $reason),
                actor: $actor,
                subject: $lockedRequest,
                before: ['status' => RequestStatus::PENDING_APPROVAL->value],
                after: [
                    'status' => RequestStatus::CANCELLED->value,
                    'cancellation_reason' => $reason,
                ]
            );

            return $lockedRequest->fresh();
        });
    }
}

