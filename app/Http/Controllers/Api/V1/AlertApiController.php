<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Alerts\Enums\AlertStatus;
use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AlertApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Alert::with(['assignee', 'finding']);

        if ($request->has('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        } else {
            $query->open();
        }

        if ($request->has('rule_code')) {
            $query->where('rule_code', $request->input('rule_code'));
        }

        $alerts = $query->orderByDesc('last_occurred_at')->paginate(25);

        return response()->json([
            'data' => $alerts->items(),
            'meta' => [
                'current_page' => $alerts->currentPage(),
                'last_page' => $alerts->lastPage(),
                'total' => $alerts->total(),
                'version' => 'v1',
            ],
        ]);
    }

    public function acknowledge(Request $request, Alert $alert): JsonResponse
    {
        /** @var User|null $actor */
        $actor = Auth::user();

        if ($actor === null) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'User context required to acknowledge alerts.',
                ],
            ], 401);
        }

        $alert->acknowledge($actor);

        AuditEvent::record(
            eventType: 'alert.acknowledged',
            reason: sprintf('Alert #%d acknowledged via API by %s', $alert->id, $actor->name),
            subject: $alert
        );

        return response()->json([
            'data' => [
                'id' => $alert->id,
                'status' => $alert->status->value,
                'assignee_id' => $alert->assignee_id,
            ],
            'meta' => [
                'message' => 'Alert successfully acknowledged.',
                'version' => 'v1',
            ],
        ]);
    }

    public function resolve(Request $request, Alert $alert): JsonResponse
    {
        $validated = $request->validate([
            'resolution_note' => 'required|string|min:5|max:1000',
        ]);

        /** @var User|null $actor */
        $actor = Auth::user();

        if ($actor === null) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'User context required to resolve alerts.',
                ],
            ], 401);
        }

        $alert->resolve($actor, $validated['resolution_note']);

        AuditEvent::record(
            eventType: 'alert.resolved',
            reason: sprintf('Alert #%d resolved via API by %s: %s', $alert->id, $actor->name, $validated['resolution_note']),
            subject: $alert,
            after: ['resolution_note' => $validated['resolution_note']]
        );

        return response()->json([
            'data' => [
                'id' => $alert->id,
                'status' => $alert->status->value,
                'resolved_at' => $alert->resolved_at?->toIso8601String(),
                'resolution_note' => $alert->resolution_note,
            ],
            'meta' => [
                'message' => 'Alert marked as resolved.',
                'version' => 'v1',
            ],
        ]);
    }
}
