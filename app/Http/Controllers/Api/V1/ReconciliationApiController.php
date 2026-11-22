<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ReconciliationFinding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReconciliationApiController extends Controller
{
    public function findings(Request $request): JsonResponse
    {
        $query = ReconciliationFinding::with(['subnet', 'vrf']);

        if ($request->has('finding_type')) {
            $query->where('finding_type', $request->input('finding_type'));
        }

        if ($request->has('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        } else {
            $query->open();
        }

        $findings = $query->orderByDesc('id')->paginate(25);

        return response()->json([
            'data' => $findings->items(),
            'meta' => [
                'current_page' => $findings->currentPage(),
                'last_page' => $findings->lastPage(),
                'total' => $findings->total(),
                'version' => 'v1',
            ],
        ]);
    }
}
