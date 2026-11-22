<?php

declare(strict_types=1);

namespace App\Actions\AddressSpace;

use App\Domain\AddressSpace\Enums\VrfStatus;
use App\Models\AuditEvent;
use App\Models\Vrf;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateVrfAction
{
    /**
     * @param array{code?: string, name?: string, description?: ?string, route_distinguisher?: ?string, site_id?: ?int, status?: string|VrfStatus} $data
     */
    public function execute(Vrf $vrf, array $data): Vrf
    {
        if (isset($data['code'])) {
            $code = strtoupper(trim($data['code']));
            if (Vrf::where('code', $code)->where('id', '!=', $vrf->id)->exists()) {
                throw ValidationException::withMessages([
                    'code' => "VRF code '{$code}' is already assigned to another routing context.",
                ]);
            }
            $data['code'] = $code;
        }

        // The default VRF cannot be retired
        if ($vrf->isDefault() && isset($data['status'])) {
            $newStatus = $data['status'] instanceof VrfStatus ? $data['status'] : VrfStatus::from($data['status']);
            if ($newStatus === VrfStatus::RETIRED) {
                throw ValidationException::withMessages([
                    'status' => 'The mandatory default VRF cannot be retired.',
                ]);
            }
        }

        return DB::transaction(function () use ($vrf, $data) {
            $before = $vrf->toArray();
            $vrf->update($data);
            $after = $vrf->fresh()->toArray();

            AuditEvent::record(
                eventType: 'vrf.updated',
                reason: "Updated routing context {$vrf->code}",
                subject: $vrf,
                before: $before,
                after: $after
            );

            return $vrf;
        });
    }
}
