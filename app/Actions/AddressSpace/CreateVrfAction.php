<?php

declare(strict_types=1);

namespace App\Actions\AddressSpace;

use App\Domain\AddressSpace\Enums\VrfStatus;
use App\Models\AuditEvent;
use App\Models\Vrf;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateVrfAction
{
    /**
     * @param array{code: string, name: string, description?: ?string, route_distinguisher?: ?string, site_id?: ?int, is_default?: bool, status?: string|VrfStatus} $data
     */
    public function execute(array $data): Vrf
    {
        $code = strtoupper(trim($data['code']));

        if (Vrf::where('code', $code)->exists()) {
            throw ValidationException::withMessages([
                'code' => "VRF code '{$code}' is already registered in the routing table.",
            ]);
        }

        return DB::transaction(function () use ($data, $code) {
            $vrf = Vrf::create([
                'code' => $code,
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'route_distinguisher' => !empty($data['route_distinguisher']) ? trim($data['route_distinguisher']) : null,
                'site_id' => $data['site_id'] ?? null,
                'is_default' => $data['is_default'] ?? false,
                'status' => $data['status'] ?? VrfStatus::ACTIVE,
            ]);

            AuditEvent::record(
                eventType: 'vrf.created',
                reason: "Created routing context {$vrf->code}",
                subject: $vrf,
                after: $vrf->toArray()
            );

            return $vrf;
        });
    }
}

