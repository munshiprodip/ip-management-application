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
use Illuminate\Support\Facades\DB;

class ApproveRequestAction
{
    public function __construct(
        protected ApprovalWorkflowService $workflowService
    ) {}

    /**
     * Approves an IPAM request, enforcing the two-person rule (T-022) and updating status.
     */
    public function execute(IpRequest $request, User $approver, ?string $comments = null): IpRequest
    {
        // 1. Enforce Two-Person Rule & Policy Requirements
        $this->workflowService->validateApproval($request, $approver);

        return DB::transaction(function () use ($request, $approver, $comments) {
            // Lock request row for atomic update
            /** @var IpRequest $lockedRequest */
            $lockedRequest = IpRequest::where('id', $request->id)->lockForUpdate()->firstOrFail();

            // Re-verify pending state under lock
            $this->workflowService->validateApproval($lockedRequest, $approver);

            // Record independent approval decision
            RequestApproval::create([
                'request_id' => $lockedRequest->id,
                'approver_id' => $approver->id,
                'decision' => ApprovalDecision::APPROVED,
                'comments' => $comments,
                'decided_at' => now(),
            ]);

            // Transition request status
            $lockedRequest->update([
                'status' => RequestStatus::APPROVED,
                'approved_at' => now(),
            ]);

            AuditEvent::record(
                eventType: 'request.approved',
                reason: sprintf('Approved request %s: %s', $lockedRequest->request_number, $comments ?? 'Approved by authorized operator'),
                actor: $approver,
                subject: $lockedRequest,
                before: ['status' => RequestStatus::PENDING_APPROVAL->value],
                after: [
                    'status' => RequestStatus::APPROVED->value,
                    'approved_at' => now()->toIso8601String(),
                    'approver_id' => $approver->id,
                ]
            );

            return $lockedRequest->fresh();
        });
    }
}
