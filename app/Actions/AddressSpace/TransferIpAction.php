<?php

declare(strict_types=1);

namespace App\Actions\AddressSpace;

use App\Domain\AddressSpace\Enums\IpAllocationStatus;
use App\Models\AuditEvent;
use App\Models\DeviceInterface;
use App\Models\IpAddress;
use App\Models\IpAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransferIpAction
{
    /**
     * T-013: Transfer IP address between interfaces atomically.
     * Single transaction: lock IP, close old assignment, append new assignment, update pointer.
     * Never drops into candidate pool between steps.
     */
    public function execute(IpAddress $ipAddress, int $newInterfaceId, string $reason, ?User $actor = null): IpAddress
    {
        $newInterface = DeviceInterface::findOrFail($newInterfaceId);

        return DB::transaction(function () use ($ipAddress, $newInterface, $reason, $actor) {
            $ip = IpAddress::where('id', $ipAddress->id)->lockForUpdate()->firstOrFail();

            if ($ip->allocation_status !== IpAllocationStatus::ASSIGNED || $ip->current_assignment_id === null) {
                throw ValidationException::withMessages([
                    'ip_address' => "Address {$ip->address_text} must be currently assigned to perform a transfer.",
                ]);
            }

            $oldAssignment = $ip->currentAssignment;
            if ($oldAssignment && $oldAssignment->interface_id === $newInterface->id) {
                throw ValidationException::withMessages([
                    'new_interface_id' => "Address is already assigned to interface '{$newInterface->name}'.",
                ]);
            }

            $before = $ip->toArray();

            // 1. Close old assignment with transfer notation
            if ($oldAssignment) {
                $oldAssignment->update([
                    'released_at' => now(),
                    'release_reason' => "Transferred to interface #{$newInterface->id} ({$newInterface->name}): {$reason}",
                ]);
            }

            // 2. Append new assignment record
            $newAssignment = IpAssignment::create([
                'ip_address_id' => $ip->id,
                'interface_id' => $newInterface->id,
                'assigned_at' => now(),
                'assignment_method' => 'transfer',
                'assigned_by_user_id' => $actor?->id ?? auth()->id(),
            ]);

            // 3. Atomically point to new assignment (single current owner throughout)
            $ip->update([
                'current_assignment_id' => $newAssignment->id,
                'allocation_status' => IpAllocationStatus::ASSIGNED,
            ]);

            AuditEvent::record(
                eventType: 'ip.transferred',
                reason: "Transferred IP {$ip->address_text} from interface #" . ($oldAssignment?->interface_id ?? 'none') . " to interface #{$newInterface->id}",
                subject: $ip,
                before: $before,
                after: $ip->toArray(),
                actor: $actor
            );

            return $ip;
        });
    }
}
