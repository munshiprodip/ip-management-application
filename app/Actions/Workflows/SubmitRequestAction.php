<?php

declare(strict_types=1);

namespace App\Actions\Workflows;

use App\Domain\Workflows\Enums\RequestPriority;
use App\Domain\Workflows\Enums\RequestStatus;
use App\Domain\Workflows\Enums\RequestType;
use App\Models\AuditEvent;
use App\Models\IpRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class SubmitRequestAction
{
    /**
     * Submit an IPAM change request with SLA calculation and scope enforcement.
     *
     * @param array{
     *     request_type: RequestType|string,
     *     target_type?: string|null,
     *     target_id?: int|null,
     *     department_id?: int|null,
     *     priority?: RequestPriority|string|null,
     *     payload: array,
     *     reason: string,
     *     idempotency_key?: string|null
     * } $data
     */
    public function execute(User $requester, array $data): IpRequest
    {
        // 1. Idempotency Check: return existing request if key already used
        if (! empty($data['idempotency_key'])) {
            $existing = IpRequest::where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing !== null) {
                return $existing;
            }
        }

        // 2. T-021: Department Operator Scoping Check
        $departmentId = isset($data['department_id']) && $data['department_id'] !== '' ? (int) $data['department_id'] : null;
        if ($requester->isDepartmentOperator() && $departmentId !== null) {
            if (! $requester->hasScope('department', $departmentId)) {
                throw new AuthorizationException('Department operator is not authorized to submit requests for this department scope.');
            }
        }

        // 3. Resolve Enums
        $requestType = $data['request_type'] instanceof RequestType
            ? $data['request_type']
            : RequestType::from((string) $data['request_type']);

        $priority = isset($data['priority']) && $data['priority'] instanceof RequestPriority
            ? $data['priority']
            : RequestPriority::from((string) ($data['priority'] ?? 'normal'));

        // 4. Calculate SLA Due Date
        $slaDueAt = now()->addHours($priority->slaHours());

        return DB::transaction(function () use ($requester, $data, $requestType, $priority, $departmentId, $slaDueAt) {
            // Generate sequential request number
            $year = date('Y');
            $latestId = (int) (IpRequest::max('id') ?? 0) + 1;
            $requestNumber = sprintf('REQ-%s-%05d', $year, $latestId);

            $request = IpRequest::create([
                'request_number' => $requestNumber,
                'requester_id' => $requester->id,
                'department_id' => $departmentId,
                'request_type' => $requestType,
                'target_type' => $data['target_type'] ?? null,
                'target_id' => $data['target_id'] ?? null,
                'priority' => $priority,
                'status' => RequestStatus::PENDING_APPROVAL,
                'payload_json' => $data['payload'] ?? [],
                'reason' => $data['reason'],
                'sla_due_at' => $slaDueAt,
                'idempotency_key' => $data['idempotency_key'] ?? null,
            ]);

            AuditEvent::record(
                eventType: 'request.submitted',
                reason: sprintf('Submitted change request %s for %s (%s priority)', $requestNumber, $requestType->label(), $priority->label()),
                actor: $requester,
                subject: $request,
                after: [
                    'request_number' => $requestNumber,
                    'request_type' => $requestType->value,
                    'priority' => $priority->value,
                    'sla_due_at' => $slaDueAt->toIso8601String(),
                ]
            );

            return $request;
        });
    }
}

