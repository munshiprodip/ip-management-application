<?php

declare(strict_types=1);

namespace App\Actions\AddressSpace;

use App\Domain\AddressSpace\Enums\IpAllocationStatus;
use App\Models\AuditEvent;
use App\Models\IpAddress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UnquarantineIpAction
{
    public function execute(IpAddress $ipAddress, ?string $reason = null, ?User $actor = null): IpAddress
    {
        return DB::transaction(function () use ($ipAddress, $reason, $actor) {
            $ip = IpAddress::where('id', $ipAddress->id)->lockForUpdate()->firstOrFail();

            if ($ip->allocation_status !== IpAllocationStatus::QUARANTINED) {
                throw ValidationException::withMessages([
                    'ip_address' => "Address {$ip->address_text} is not currently quarantined.",
                ]);
            }

            $before = $ip->toArray();

            // Close any active assignments
            $ip->assignments()->whereNull('released_at')->update([
                'released_at' => now(),
                'release_reason' => $reason ?: 'Quarantine manually cleared by operator',
            ]);

            $ip->update([
                'allocation_status' => IpAllocationStatus::UNASSIGNED,
                'quarantined_until' => null,
                'quarantine_reason' => null,
                'assigned_to' => null,
                'hostname' => null,
                'mac_address' => null,
                'description' => null,
                'assigned_at' => null,
                'host_type' => null,
            ]);

            AuditEvent::record(
                eventType: 'ip.unquarantined',
                reason: "Quarantine manually lifted for IP {$ip->address_text}; address marked as unassigned/available.",
                subject: $ip,
                before: $before,
                after: $ip->fresh()->toArray(),
                actor: $actor
            );

            return $ip;
        });
    }
}

