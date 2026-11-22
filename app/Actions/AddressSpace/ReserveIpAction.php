<?php

declare(strict_types=1);

namespace App\Actions\AddressSpace;

use App\Domain\AddressSpace\Enums\IpAllocationStatus;
use App\Domain\AddressSpace\ValueObjects\IpAddress as IpAddressVO;
use App\Models\AuditEvent;
use App\Models\IpAddress;
use App\Models\IpReservation;
use App\Models\Subnet;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReserveIpAction
{
    /**
     * @param array{
     *     subnet_id: int,
     *     ip_address: string,
     *     reason: string,
     *     expires_at?: ?string
     * } $data
     */
    public function execute(array $data, ?User $actor = null): IpReservation
    {
        $subnet = Subnet::findOrFail($data['subnet_id']);
        $ipVO = IpAddressVO::parse($data['ip_address']);

        if (! $subnet->toIpPrefix()->containsIp($ipVO)) {
            throw ValidationException::withMessages([
                'ip_address' => "Address {$data['ip_address']} is outside subnet boundary {$subnet->cidr}.",
            ]);
        }

        $addressHex = $ipVO->toHex();

        return DB::transaction(function () use ($subnet, $ipVO, $addressHex, $data, $actor) {
            $ipRecord = IpAddress::where('subnet_id', $subnet->id)
                ->where('address_hex', $addressHex)
                ->lockForUpdate()
                ->first();

            if (! $ipRecord) {
                $ipRecord = IpAddress::create([
                    'subnet_id' => $subnet->id,
                    'ip_version' => $subnet->ip_version,
                    'address_hex' => $addressHex,
                    'address_text' => $ipVO->toNormalizedString(),
                    'allocation_status' => IpAllocationStatus::RESERVED,
                    'description' => $data['reason'],
                ]);
            } else {
                if ($ipRecord->allocation_status === IpAllocationStatus::ASSIGNED) {
                    throw ValidationException::withMessages([
                        'ip_address' => "Cannot reserve {$ipRecord->address_text} because it is currently assigned.",
                    ]);
                }

                $ipRecord->update([
                    'allocation_status' => IpAllocationStatus::RESERVED,
                    'description' => $data['reason'],
                ]);
            }

            $expiresAt = !empty($data['expires_at']) ? Carbon::parse($data['expires_at']) : null;

            $reservation = IpReservation::create([
                'ip_address_id' => $ipRecord->id,
                'reserved_by_user_id' => $actor?->id ?? auth()->id(),
                'purpose' => trim($data['reason']),
                'reserved_until' => $expiresAt,
            ]);

            AuditEvent::record(
                eventType: 'ip.reserved',
                reason: "Reserved IP {$ipRecord->address_text}: {$data['reason']}",
                subject: $ipRecord,
                after: $ipRecord->toArray(),
                actor: $actor
            );

            return $reservation;
        });
    }
}
