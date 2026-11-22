<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\IpAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IpAddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = IpAddress::with(['subnet', 'vrf', 'assignments.deviceInterface.device']);

        if ($request->has('address')) {
            $query->where('address_text', 'like', '%' . $request->input('address') . '%');
        }

        if ($request->has('vrf_id')) {
            $query->where('vrf_id', (int) $request->input('vrf_id'));
        }

        if ($request->has('allocation_status')) {
            $query->where('allocation_status', $request->input('allocation_status'));
        }

        if ($request->has('connectivity_status')) {
            $query->where('connectivity_status', $request->input('connectivity_status'));
        }

        $ips = $query->paginate((int) $request->input('per_page', 25));

        return response()->json([
            'data' => $ips->items(),
            'meta' => [
                'current_page' => $ips->currentPage(),
                'last_page' => $ips->lastPage(),
                'total' => $ips->total(),
                'version' => 'v1',
            ],
        ]);
    }

    public function history(IpAddress $ip): JsonResponse
    {
        $assignments = $ip->assignments()->with('deviceInterface.device')->orderByDesc('id')->get();
        $audits = AuditEvent::where('subject_type', IpAddress::class)
            ->where('subject_id', $ip->id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => [
                'ip_address' => $ip->address_text,
                'vrf_id' => $ip->vrf_id,
                'current_allocation_status' => $ip->allocation_status->value,
                'current_connectivity_status' => $ip->connectivity_status->value,
                'assignment_history' => $assignments,
                'audit_trail' => $audits,
            ],
            'meta' => ['version' => 'v1'],
        ]);
    }
}
