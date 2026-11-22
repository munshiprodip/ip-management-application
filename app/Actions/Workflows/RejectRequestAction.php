<?php

declare(strict_types=1);

namespace App\Actions\Workflows;

use App\Domain\Workflows\Enums\ApprovalDecision;
use App\Domain\Workflows\Enums\RequestStatus;
use App\Models\AuditEvent;
use App\Models\IpRequest;
use App\Models\RequestApproval;
use App\Models\User;
use App\Services\Workflows\ApprovalWorkflowService;
use DomainException;
use Illuminate\Support\Facades\DB;

class RejectRequestAction
{
    public function __construct(
        protected ApprovalWorkflowService $workflowService
    ) {}

    /**
     * Rejects an IPAM change request with mandatory justification.
     */
    public function execute(IpRequest $request, User $approver, string $reason): IpRequest
    {
        if (trim($reason) === '') {
            throw new DomainException('A rejection reason must be provided.');
        }

        $this->workflowService->validateApproval($request, $approver);

        return DB::transaction(function () use ($request, $approver, $reason) {
            /** @var IpRequest $lockedRequest */
            $lockedRequest = IpRequest::where('id', $request->id)->lockForUpdate()->firstOrFail();

            $this->workflowService->validateApproval($lockedRequest, $approver);

            RequestApproval::create([
                'request_id' => $lockedRequest->id,
                'approver_id' => $approver->id,
                'decision' => ApprovalDecision::REJECTED,
                'comments' => $reason,
                'decided_at' => now(),
            ]);

            $lockedRequest->update([
                'status' => RequestStatus::REJECTED,
                'rejection_reason' => $reason,
            ]);

            AuditEvent::record(
                eventType: 'request.rejected',
                reason: sprintf('Rejected request %s: %s', $lockedRequest->request_number, $reason),
                actor: $approver,
                subject: $lockedRequest,
                before: ['status' => RequestStatus::PENDING_APPROVAL->value],
                after: [
                    'status' => RequestStatus::REJECTED->value,
                    'rejection_reason' => $reason,
                    'decided_by' => $approver->id,
                ]
            );

            return $lockedRequest->fresh();
        });
    }
}

