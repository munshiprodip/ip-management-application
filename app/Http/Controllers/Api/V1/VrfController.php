<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Vrf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VrfController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $vrfs = Vrf::withCount('subnets')->get();

        return response()->json([
            'data' => $vrfs->map(fn ($vrf) => [
                'id' => $vrf->id,
                'name' => $vrf->name,
                'code' => $vrf->code,
                'rd' => $vrf->route_distinguisher ?? $vrf->rd,
                'description' => $vrf->description,
                'is_default' => $vrf->is_default,
                'subnets_count' => $vrf->subnets_count,
            ]),
            'meta' => [
                'count' => $vrfs->count(),
                'version' => 'v1',
            ],
        ]);
    }
}
