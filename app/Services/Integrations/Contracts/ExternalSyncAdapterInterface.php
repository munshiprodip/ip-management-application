<?php

declare(strict_types=1);

namespace App\Services\Integrations\Contracts;

use App\Models\Integration;

interface ExternalSyncAdapterInterface
{
    /**
     * Retrieves an external evidence snapshot in read-only mode.
     *
     * @return array<array{
     *     ip_address: string,
     *     mac_address?: ?string,
     *     hostname?: ?string,
     *     lease_start?: ?string,
     *     lease_end?: ?string,
     *     scope?: ?string
     * }>
     */
    public function fetchSnapshot(Integration $integration): array;

    /**
     * Unique integration type key (e.g. 'dhcp_server', 'dns_server').
     */
    public function getType(): string;
}
