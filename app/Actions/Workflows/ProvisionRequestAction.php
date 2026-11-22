<?php

declare(strict_types=1);

namespace App\Actions\Workflows;

use App\Actions\AddressSpace\AllocateIpAction;
use App\Actions\AddressSpace\ReleaseIpAction;
use App\Actions\AddressSpace\ReserveIpAction;
use App\Actions\AddressSpace\TransferIpAction;
use App\Domain\AddressSpace\Enums\IpClassification;
use App\Domain\Workflows\Enums\RequestStatus;
use App\Domain\Workflows\Enums\RequestType;
use App\Models\AuditEvent;
use App\Models\ChangeRecord;
use App\Models\DeviceInterface;
use App\Models\IpAddress;
use App\Models\IpRequest;
use App\Models\Subnet;
use App\Models\User;
use App\Services\AddressSpace\IpAllocationService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProvisionRequestAction
{
    public function __construct(
        protected AllocateIpAction $allocateIpAction,
        protected ReleaseIpAction $releaseIpAction,
        protected TransferIpAction $transferIpAction,
        protected ReserveIpAction $reserveIpAction,
        protected IpAllocationService $allocationService
    ) {}

    /**
     * Executes the technical provisioning for an approved IPAM change request.
     * Enforces idempotency (cannot provision twice) and records change records.
     */
    public function execute(IpRequest $request, User $actor): IpRequest
    {
        if (! $actor->isNetworkAdmin()) {
            throw new AuthorizationException('Only Network Administrators can provision approved requests.');
        }

        // Refresh in-memory instance to capture latest committed status
        $request->refresh();

        // Idempotency: Replay protection — if already provisioned, return safely
        if ($request->isProvisioned()) {
            return $request;
        }

        if (! $request->isApproved()) {
            throw new DomainException(sprintf(
                "Cannot provision request %s: current status is '%s'. Only approved requests can be provisioned.",
                $request->request_number,
                $request->status->value
            ));
        }

        return DB::transaction(function () use ($request, $actor) {
            /** @var IpRequest $lockedRequest */
            $lockedRequest = IpRequest::where('id', $request->id)->lockForUpdate()->firstOrFail();

            if ($lockedRequest->isProvisioned()) {
                return $lockedRequest;
            }

            if (! $lockedRequest->isApproved()) {
                throw new DomainException("Cannot provision request: current status is '{$lockedRequest->status->value}'.");
            }

            $correlationId = (string) Str::uuid();
            $payload = $lockedRequest->payload_json ?? [];
            $afterData = [];

            switch ($lockedRequest->request_type) {
                case RequestType::IP_ASSIGNMENT:
                    $subnet = Subnet::findOrFail((int) $payload['subnet_id']);
                    $interfaceId = isset($payload['interface_id']) && $payload['interface_id'] !== ''
                        ? (int) $payload['interface_id']
                        : null;

                    $requestedIp = $payload['ip_address'] ?? null;
                    if (empty($requestedIp)) {
                        $candidate = $this->allocationService->findNextFree($subnet);
                        if ($candidate === null) {
                            throw new DomainException("No available IP addresses in subnet {$subnet->cidr}.");
                        }
                        $requestedIp = $candidate->toCanonical();
                    }

                    $classification = isset($payload['classification'])
                        ? IpClassification::from((string) $payload['classification'])
                        : IpClassification::STATIC_HOST;

                    $assignedIp = $this->allocateIpAction->execute([
                        'subnet_id' => $subnet->id,
                        'ip_address' => $requestedIp,
                        'interface_id' => $interfaceId,
                        'hostname' => $payload['hostname'] ?? null,
                        'classification' => $classification,
                        'description' => sprintf('Provisioned from request %s', $lockedRequest->request_number),
                    ], $actor);

                    $afterData = [
                        'ip_address' => $assignedIp->address,
                        'ip_id' => $assignedIp->id,
                        'interface_id' => $interfaceId,
                    ];
                    break;

                case RequestType::IP_RELEASE:
                    $ipAddress = IpAddress::findOrFail((int) $payload['ip_address_id']);
                    $quarantineDays = (int) ($payload['quarantine_days'] ?? 14);

                    $releasedIp = $this->releaseIpAction->execute(
                        ipAddress: $ipAddress,
                        reason: sprintf('Provisioned release from request %s', $lockedRequest->request_number),
                        quarantineDays: $quarantineDays,
                        actor: $actor
                    );

                    $afterData = [
                        'ip_address' => $releasedIp->address,
                        'allocation_status' => $releasedIp->allocation_status->value,
                        'quarantined_until' => $releasedIp->quarantined_until?->toIso8601String(),
                    ];
                    break;

                case RequestType::IP_TRANSFER:
                    $ipAddress = IpAddress::findOrFail((int) $payload['ip_address_id']);

                    $transferredIp = $this->transferIpAction->execute(
                        ipAddress: $ipAddress,
                        newInterfaceId: (int) $payload['target_interface_id'],
                        reason: sprintf('Provisioned transfer from request %s', $lockedRequest->request_number),
                        actor: $actor
                    );

                    $afterData = [
                        'ip_address' => $transferredIp->address,
                        'new_interface_id' => (int) $payload['target_interface_id'],
                    ];
                    break;

                case RequestType::DHCP_RESERVATION:
                    $subnet = Subnet::findOrFail((int) $payload['subnet_id']);

                    $reservation = $this->reserveIpAction->execute([
                        'subnet_id' => $subnet->id,
                        'ip_address' => $payload['ip_address'],
                        'reason' => $payload['purpose'] ?? sprintf('DHCP static reservation from %s', $lockedRequest->request_number),
                        'expires_at' => $payload['expires_at'] ?? null,
                    ], $actor);

                    $afterData = [
                        'ip_address' => $payload['ip_address'],
                        'reservation_id' => $reservation->id,
                    ];
                    break;

                default:
                    // For other request types like SUBNET_CREATE or POLICY_EXCEPTION
                    $afterData = ['status' => 'provisioned_manually', 'type' => $lockedRequest->request_type->value];
                    break;
            }

            // Create immutable change record
            ChangeRecord::create([
                'request_id' => $lockedRequest->id,
                'actor_id' => $actor->id,
                'change_type' => $lockedRequest->request_type->value,
                'before_json' => ['request_status' => RequestStatus::APPROVED->value],
                'after_json' => $afterData,
                'result' => 'success',
                'correlation_id' => $correlationId,
            ]);

            // Transition request status to PROVISIONED
            $lockedRequest->update([
                'status' => RequestStatus::PROVISIONED,
                'provisioned_at' => now(),
                'provisioned_by' => $actor->id,
            ]);

            AuditEvent::record(
                eventType: 'request.provisioned',
                reason: sprintf('Provisioned change request %s (%s)', $lockedRequest->request_number, $lockedRequest->request_type->label()),
                actor: $actor,
                subject: $lockedRequest,
                before: ['status' => RequestStatus::APPROVED->value],
                after: [
                    'status' => RequestStatus::PROVISIONED->value,
                    'provisioned_at' => now()->toIso8601String(),
                    'correlation_id' => $correlationId,
                    'details' => $afterData,
                ]
            );

            return $lockedRequest->fresh();
        });
    }
}
