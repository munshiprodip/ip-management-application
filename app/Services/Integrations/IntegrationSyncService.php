<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Models\AuditEvent;
use App\Models\Integration;
use App\Models\IntegrationRun;
use App\Models\IpAddress;
use App\Services\Integrations\Contracts\ExternalSyncAdapterInterface;
use Illuminate\Support\Facades\DB;

class IntegrationSyncService
{
    /**
     * @param array<string, ExternalSyncAdapterInterface> $adapters
     */
    public function __construct(
        private array $adapters = []
    ) {}

    public function registerAdapter(ExternalSyncAdapterInterface $adapter): void
    {
        $this->adapters[$adapter->getType()] = $adapter;
    }

    /**
     * Executes a read-only integration synchronization pass.
     * Generates discrepancy diff without modifying authoritative IP allocations (Section 13.3).
     */
    public function sync(Integration $integration, ?ExternalSyncAdapterInterface $customAdapter = null): IntegrationRun
    {
        $adapter = $customAdapter ?? ($this->adapters[$integration->type] ?? null);

        if ($adapter === null) {
            throw new \InvalidArgumentException("No synchronization adapter registered for type '{$integration->type}'.");
        }

        $run = IntegrationRun::create([
            'integration_id' => $integration->id,
            'sync_type' => $integration->type,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $integration->update(['status' => 'syncing']);

        try {
            $snapshot = $adapter->fetchSnapshot($integration);

            $scanned = count($snapshot);
            $matched = 0;
            $divergent = 0;
            $discrepancies = [];

            foreach ($snapshot as $entry) {
                $addressText = $entry['ip_address'];

                // Query authoritative IP record
                /** @var IpAddress|null $ip */
                $ip = IpAddress::where('address_text', $addressText)->first();

                if ($ip !== null && $ip->isAssigned()) {
                    $matched++;
                } else {
                    $divergent++;
                    $discrepancies[] = [
                        'ip_address' => $addressText,
                        'reason' => $ip === null ? 'Active in external system but missing in IPAM' : "Active externally but status is '{$ip->allocation_status->value}'",
                        'external_metadata' => $entry,
                    ];
                }
            }

            $summary = [
                'items_scanned' => $scanned,
                'items_matched' => $matched,
                'items_divergent' => $divergent,
                'discrepancies' => array_slice($discrepancies, 0, 50),
            ];

            $run->update([
                'status' => $divergent > 0 ? 'partial' : 'succeeded',
                'ended_at' => now(),
                'items_scanned' => $scanned,
                'items_matched' => $matched,
                'items_divergent' => $divergent,
                'summary_json' => $summary,
            ]);

            $integration->update([
                'status' => 'idle',
                'last_sync_at' => now(),
                'last_sync_status' => $run->status,
            ]);

            AuditEvent::record(
                eventType: 'integration.synced',
                reason: sprintf('Completed read-only sync for [%s]: %d scanned, %d matched, %d divergent', $integration->name, $scanned, $matched, $divergent),
                subject: $integration,
                after: $summary
            );

            return $run->fresh();
        } catch (\Throwable $e) {
            $run->update([
                'status' => 'failed',
                'ended_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            $integration->update([
                'status' => 'failed',
                'last_sync_at' => now(),
                'last_sync_status' => 'failed',
            ]);

            AuditEvent::record(
                eventType: 'integration.failed',
                reason: sprintf('Sync failed for [%s]: %s', $integration->name, $e->getMessage()),
                subject: $integration
            );

            throw $e;
        }
    }
}
