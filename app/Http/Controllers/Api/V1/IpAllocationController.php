<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\AddressSpace\AllocateIpAction;
use App\Actions\AddressSpace\ReleaseIpAction;
use App\Http\Controllers\Controller;
use App\Models\IpAddress;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IpAllocationController extends Controller
{
    public function store(Request $request, AllocateIpAction $allocator): JsonResponse
    {
        $validated = $request->validate([
            'subnet_id' => 'required|integer|exists:subnets,id',
            'ip_address' => 'nullable|string',
            'pool_id' => 'nullable|integer|exists:ip_pools,id',
            'interface_id' => 'nullable|integer|exists:device_interfaces,id',
            'assignment_type' => 'nullable|string',
            'classification' => 'nullable|string',
            'fqdn' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
            'idempotency_key' => 'nullable|string|max:64',
        ]);

        /** @var User|null $actor */
        $actor = Auth::user();

        try {
            $ip = $allocator->execute($validated, $actor);

            return response()->json([
                'data' => [
                    'id' => $ip->id,
                    'address' => $ip->address_text,
                    'vrf_id' => $ip->vrf_id,
                    'subnet_id' => $ip->subnet_id,
                    'allocation_status' => $ip->allocation_status->value,
                    'classification' => $ip->classification->value,
                    'fqdn' => $ip->fqdn,
                ],
                'meta' => [
                    'message' => 'IP address successfully allocated.',
                    'version' => 'v1',
                ],
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => [
                    'code' => 'ALLOCATION_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 422);
        }
    }

    public function release(Request $request, int $id, ReleaseIpAction $releaser): JsonResponse
    {
        $ip = IpAddress::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
            'quarantine_days' => 'nullable|integer|min:0|max:90',
        ]);

        /** @var User|null $actor */
        $actor = Auth::user();

        try {
            $released = $releaser->execute(
                $ip,
                $validated['reason'] ?? 'API release request',
                $validated['quarantine_days'] ?? (int) config('ipam.quarantine_duration_days', 14),
                $actor
            );

            return response()->json([
                'data' => [
                    'id' => $released->id,
                    'address' => $released->address_text,
                    'allocation_status' => $released->allocation_status->value,
                    'quarantine_until' => $released->quarantine_until?->toIso8601String(),
                ],
                'meta' => [
                    'message' => 'IP successfully released and transitioned to quarantine hold (T-012).',
                    'version' => 'v1',
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => [
                    'code' => 'RELEASE_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 422);
        }
    }
}
