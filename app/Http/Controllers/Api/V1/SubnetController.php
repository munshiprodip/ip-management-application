<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Subnet;
use App\Services\AddressSpace\CandidateWindowGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubnetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Subnet::with(['vrf', 'site', 'department']);

        if ($request->has('vrf_id')) {
            $query->where('vrf_id', (int) $request->input('vrf_id'));
        }

        if ($request->has('site_id')) {
            $query->where('site_id', (int) $request->input('site_id'));
        }

        if ($request->has('parent_id')) {
            $parentId = $request->input('parent_id');
            if ($parentId === 'null' || $parentId === '') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', (int) $parentId);
            }
        }

        $subnets = $query->paginate((int) $request->input('per_page', 25));

        return response()->json([
            'data' => $subnets->items(),
            'meta' => [
                'current_page' => $subnets->currentPage(),
                'last_page' => $subnets->lastPage(),
                'total' => $subnets->total(),
                'version' => 'v1',
            ],
        ]);
    }

    public function show(Subnet $subnet): JsonResponse
    {
        $subnet->load(['vrf', 'site', 'department', 'parent', 'children', 'pools']);

        return response()->json([
            'data' => $subnet,
            'meta' => ['version' => 'v1'],
        ]);
    }

    public function capacity(Subnet $subnet): JsonResponse
    {
        $total = (int) (string) $subnet->totalAddresses();
        $allocated = $subnet->allocatedCount();
        $free = max(0, $total - $allocated);
        $utilizationPct = $total > 0 ? round(($allocated / $total) * 100, 2) : 0.0;

        return response()->json([
            'data' => [
                'subnet_id' => $subnet->id,
                'cidr' => $subnet->cidr,
                'ip_version' => $subnet->ip_version,
                'total_addresses' => $total,
                'allocated_addresses' => $allocated,
                'free_addresses' => $free,
                'utilization_percent' => $utilizationPct,
                'is_exhaustion_warning' => $utilizationPct >= 80.0,
                'is_exhaustion_critical' => $utilizationPct >= 95.0,
            ],
            'meta' => ['version' => 'v1'],
        ]);
    }

    public function candidateIps(Request $request, Subnet $subnet, CandidateWindowGenerator $generator): JsonResponse
    {
        $limit = min(50, max(1, (int) $request->input('limit', 10)));
        $window = $generator->generateWindow($subnet, 1, $limit);

        return response()->json([
            'data' => $window['candidates'] ?? $window,
            'meta' => [
                'subnet_id' => $subnet->id,
                'cidr' => $subnet->cidr,
                'limit' => $limit,
                'version' => 'v1',
            ],
        ]);
    }
}
