<?php

declare(strict_types=1);

namespace App\Services\Discovery\Adapters;

use App\Domain\Discovery\Enums\DiscoverySourceType;
use App\Models\DiscoveryJob;
use App\Services\Discovery\Contracts\DiscoveryCollectorInterface;

class FixtureDiscoveryAdapter implements DiscoveryCollectorInterface
{
    /**
     * Custom simulated observations fixture generator for safe testing and verification.
     *
     * @var array<string, array<int, array{ip_address: string, mac_address?: ?string, hostname?: ?string, confidence: int, source_type: string}>>
     */
    protected array $customFixtures = [];

    public function setCustomFixture(string $cidr, array $observations): void
    {
        $this->customFixtures[$cidr] = $observations;
    }

    public function collect(DiscoveryJob $job): array
    {
        $subnet = $job->subnet;

        if (isset($this->customFixtures[$subnet->cidr])) {
            return $this->customFixtures[$subnet->cidr];
        }

        // Default deterministic simulated observations from authoritative database state
        $results = [];
        $allocatedIps = $subnet->ipAddresses()->with(['currentAssignment.deviceInterface.device'])->get();

        foreach ($allocatedIps as $ip) {
            $iface = $ip->currentAssignment?->deviceInterface;
            $results[] = [
                'ip_address' => $ip->address_text,
                'mac_address' => $iface?->mac_address ?? '00:50:56:a1:b2:c3',
                'hostname' => $ip->hostname ?: ($iface?->device?->name ? strtolower($iface->device->name) . '.kyamch.local' : null),
                'confidence' => 95,
                'source_type' => DiscoverySourceType::ARP->value,
                'payload' => ['ttl' => 64, 'rtt_ms' => 1.2],
            ];
        }

        return $results;
    }
}
