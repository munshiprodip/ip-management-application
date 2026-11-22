<?php

declare(strict_types=1);

namespace App\Actions\AddressSpace;

use App\Domain\AddressSpace\Enums\IpAllocationStatus;
use App\Models\AuditEvent;
use App\Models\IpAddress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReleaseIpAction
{
    public function execute(IpAddress $ipAddress, string $reason, int $quarantineDays = 3, ?User $actor = null): IpAddress
    {
        return DB::transaction(function () use ($ipAddress, $reason, $quarantineDays, $actor) {
            $ip = IpAddress::where('id', $ipAddress->id)->lockForUpdate()->firstOrFail();

            if ($ip->allocation_status !== IpAllocationStatus::ASSIGNED) {
                throw ValidationException::withMessages([
                    'ip_address' => "Address {$ip->address_text} is not currently assigned.",
                ]);
            }

            $before = $ip->toArray();

            // Close active assignments
            $ip->assignments()->whereNull('released_at')->update([
                'released_at' => now(),
                'release_reason' => trim($reason),
            ]);

            $quarantineUntil = $quarantineDays > 0 ? now()->addDays($quarantineDays) : null;
            $status = $quarantineUntil ? IpAllocationStatus::QUARANTINED : IpAllocationStatus::UNASSIGNED;

            $ip->update([
                'allocation_status' => $status,
                'quarantined_until' => $quarantineUntil,
                'quarantine_reason' => trim($reason),
            ]);

            AuditEvent::record(
                eventType: 'ip.released',
                reason: "Released IP {$ip->address_text} (Quarantined until: " . ($quarantineUntil?->toDateString() ?? 'immediate release') . ")",
                subject: $ip,
                before: $before,
                after: $ip->toArray(),
                actor: $actor
            );

            return $ip;
        });
    }
}
