<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Device::with(['site', 'department', 'deviceType', 'model.manufacturer']);

        if ($request->has('site_id')) {
            $query->where('site_id', (int) $request->input('site_id'));
        }

        if ($request->has('department_id')) {
            $query->where('department_id', (int) $request->input('department_id'));
        }

        if ($request->has('device_type_id')) {
            $query->where('device_type_id', (int) $request->input('device_type_id'));
        }

        if ($request->has('search')) {
            $term = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('primary_fqdn', 'like', $term)
                    ->orWhere('serial_number', 'like', $term);
            });
        }

        $devices = $query->paginate((int) $request->input('per_page', 25));

        return response()->json([
            'data' => $devices->items(),
            'meta' => [
                'current_page' => $devices->currentPage(),
                'last_page' => $devices->lastPage(),
                'total' => $devices->total(),
                'version' => 'v1',
            ],
        ]);
    }

    public function interfaces(Device $device): JsonResponse
    {
        $interfaces = $device->interfaces()->with(['ipAssignments.ipAddress.vrf', 'vlan'])->get();

        return response()->json([
            'data' => $interfaces,
            'meta' => [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'version' => 'v1',
            ],
        ]);
    }
}
