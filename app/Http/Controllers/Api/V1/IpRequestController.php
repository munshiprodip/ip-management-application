<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Workflows\SubmitRequestAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IpRequestController extends Controller
{
    public function store(Request $request, SubmitRequestAction $submitter): JsonResponse
    {
        $validated = $request->validate([
            'request_type' => 'nullable|string|in:ip_assignment,ip_release,ip_transfer,subnet_create,dhcp_reservation,policy_exception',
            'reason' => 'nullable|string|max:1000',
            'title' => 'nullable|string|max:128',
            'description' => 'nullable|string|max:1000',
            'justification' => 'nullable|string|max:1000',
            'vrf_id' => 'required|integer|exists:vrfs,id',
            'subnet_id' => 'nullable|integer|exists:subnets,id',
            'department_id' => 'required|integer|exists:departments,id',
            'priority' => 'nullable|string|in:low,normal,urgent,emergency',
            'idempotency_key' => 'nullable|string|max:64',
            'payload' => 'nullable|array',
        ]);

        /** @var User|null $actor */
        $actor = Auth::user();

        if ($actor === null) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'User context required to submit IP requests.',
                ],
            ], 401);
        }

        $submissionData = [
            'request_type' => $validated['request_type'] ?? 'ip_assignment',
            'department_id' => $validated['department_id'],
            'priority' => $validated['priority'] ?? 'normal',
            'reason' => $validated['reason'] ?? ($validated['justification'] ?? ($validated['description'] ?? 'API change request')),
            'idempotency_key' => $validated['idempotency_key'] ?? null,
            'payload' => array_merge($validated['payload'] ?? [], [
                'vrf_id' => $validated['vrf_id'],
                'subnet_id' => $validated['subnet_id'] ?? null,
                'title' => $validated['title'] ?? null,
            ]),
        ];

        try {
            $ipRequest = $submitter->execute($actor, $submissionData);

            return response()->json([
                'data' => [
                    'id' => $ipRequest->id,
                    'request_number' => $ipRequest->request_number,
                    'status' => $ipRequest->status->value,
                    'priority' => $ipRequest->priority->value,
                    'sla_due_at' => $ipRequest->sla_due_at?->toIso8601String(),
                ],
                'meta' => [
                    'message' => 'IP request successfully submitted for approval workflow.',
                    'version' => 'v1',
                ],
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => [
                    'code' => 'REQUEST_SUBMISSION_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 422);
        }
    }
}
