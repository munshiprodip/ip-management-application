<?php

declare(strict_types=1);

namespace App\Services\Discovery\Contracts;

use App\Models\DiscoveryJob;

interface DiscoveryCollectorInterface
{
    /**
     * Executes safe collection of network observation evidence for the specified job.
     *
     * @return array<int, array{
     *     ip_address: string,
     *     mac_address?: ?string,
     *     hostname?: ?string,
     *     confidence: int,
     *     source_type: string,
     *     payload?: ?array
     * }>
     */
    public function collect(DiscoveryJob $job): array;
}
