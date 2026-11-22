<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Vlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Vlan::with(['l2Domain', 'subnets']);

        if ($request->has('l2_domain_id')) {
            $query->where('l2_domain_id', (int) $request->input('l2_domain_id'));
        }

        if ($request->has('vlan_id')) {
            $query->where('vlan_id', (int) $request->input('vlan_id'));
        }

        $vlans = $query->paginate((int) $request->input('per_page', 25));

        return response()->json([
            'data' => $vlans->items(),
            'meta' => [
                'current_page' => $vlans->currentPage(),
                'last_page' => $vlans->lastPage(),
                'total' => $vlans->total(),
                'version' => 'v1',
            ],
        ]);
    }
}
