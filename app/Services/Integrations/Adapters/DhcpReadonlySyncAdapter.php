<?php

declare(strict_types=1);

namespace App\Services\Integrations\Adapters;

use App\Models\Integration;
use App\Services\Integrations\Contracts\ExternalSyncAdapterInterface;

class DhcpReadonlySyncAdapter implements ExternalSyncAdapterInterface
{
    /**
     * @var array<array{ip_address: string, mac_address?: ?string, hostname?: ?string}>|null
     */
    private ?array $fixtureLeases = null;

    /**
     * Set deterministic fixture leases for testing and offline environments.
     *
     * @param array<array{ip_address: string, mac_address?: ?string, hostname?: ?string}> $leases
     */
    public function setFixtureLeases(array $leases): void
    {
        $this->fixtureLeases = $leases;
    }

    public function fetchSnapshot(Integration $integration): array
    {
        if ($this->fixtureLeases !== null) {
            return $this->fixtureLeases;
        }

        // Configuration-driven snapshot parsing (e.g., from mock / staging API or local file fixture)
        $config = $integration->config_json ?? [];
        if (! empty($config['fixtures'])) {
            return (array) $config['fixtures'];
        }

        return [];
    }

    public function getType(): string
    {
        return 'dhcp_server';
    }
}
