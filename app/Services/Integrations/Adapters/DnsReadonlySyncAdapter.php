<?php

declare(strict_types=1);

namespace App\Services\Integrations\Adapters;

use App\Models\Integration;
use App\Services\Integrations\Contracts\ExternalSyncAdapterInterface;

class DnsReadonlySyncAdapter implements ExternalSyncAdapterInterface
{
    /**
     * @var array<array{ip_address: string, hostname?: ?string, record_type?: string}>|null
     */
    private ?array $fixtureRecords = null;

    /**
     * Set deterministic fixture records for testing.
     *
     * @param array<array{ip_address: string, hostname?: ?string, record_type?: string}> $records
     */
    public function setFixtureRecords(array $records): void
    {
        $this->fixtureRecords = $records;
    }

    public function fetchSnapshot(Integration $integration): array
    {
        if ($this->fixtureRecords !== null) {
            return $this->fixtureRecords;
        }

        $config = $integration->config_json ?? [];
        if (! empty($config['fixtures'])) {
            return (array) $config['fixtures'];
        }

        return [];
    }

    public function getType(): string
    {
        return 'dns_server';
    }
}
