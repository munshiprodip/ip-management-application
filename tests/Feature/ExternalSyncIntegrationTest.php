<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\AddressSpace\CreateSubnetAction;
use App\Domain\AddressSpace\Enums\IpAllocationStatus;
use App\Models\Integration;
use App\Models\IpAddress;
use App\Models\Organization;
use App\Models\Site;
use App\Models\Subnet;
use App\Models\Vrf;
use App\Services\Integrations\Adapters\DhcpReadonlySyncAdapter;
use App\Services\Integrations\Adapters\DnsReadonlySyncAdapter;
use App\Services\Integrations\IntegrationSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalSyncIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Vrf $vrf;
    private Subnet $subnet;
    private Integration $dhcpIntegration;
    private Integration $dnsIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organization::create(['name' => 'KYAMCH', 'slug' => 'kyamch-sync', 'code' => 'KYAMCH-SYNC']);
        $site = Site::create(['name' => 'Main Hospital', 'organization_id' => $org->id, 'code' => 'SYNC-HQ']);
        $this->vrf = Vrf::create(['name' => 'Default VRF', 'code' => 'VRF-GLOBAL', 'rd' => '65000:1', 'is_default' => true]);

        $createSubnet = app(CreateSubnetAction::class);
        $this->subnet = $createSubnet->execute([
            'vrf_id' => $this->vrf->id,
            'site_id' => $site->id,
            'name' => 'Sync Verification Subnet',
            'cidr' => '10.30.0.0/24',
        ]);

        $this->dhcpIntegration = Integration::create([
            'type' => 'dhcp_server',
            'name' => 'Core DHCP Server',
            'site_id' => $site->id,
            'enabled' => true,
        ]);

        $this->dnsIntegration = Integration::create([
            'type' => 'dns_server',
            'name' => 'Internal DNS Server',
            'site_id' => $site->id,
            'enabled' => true,
        ]);
    }

    /**
     * Section 13.3 Invariant: External sync snapshots are strictly read-only;
     * failed or divergent sync never flips authoritative allocation status.
     */
    public function test_dhcp_readonly_sync_identifies_matches_and_divergences_without_mutating_allocations(): void
    {
        // Authoritative record 1: 10.30.0.10 is assigned
        $assignedIp = IpAddress::create([
            'subnet_id' => $this->subnet->id,
            'vrf_id' => $this->vrf->id,
            'ip_version' => 4,
            'address_text' => '10.30.0.10',
            'address_hex' => '0a1e000a',
            'allocation_status' => IpAllocationStatus::ASSIGNED,
        ]);

        // Authoritative record 2: 10.30.0.20 is reserved (not active host assignment)
        $reservedIp = IpAddress::create([
            'subnet_id' => $this->subnet->id,
            'vrf_id' => $this->vrf->id,
            'ip_version' => 4,
            'address_text' => '10.30.0.20',
            'address_hex' => '0a1e0014',
            'allocation_status' => IpAllocationStatus::RESERVED,
        ]);

        // DHCP fixture returns:
        // - 10.30.0.10: matches assigned IP
        // - 10.30.0.20: active lease but IPAM status is reserved (divergent)
        // - 10.30.0.99: active lease but missing in IPAM completely (divergent)
        $dhcpAdapter = new DhcpReadonlySyncAdapter();
        $dhcpAdapter->setFixtureLeases([
            ['ip_address' => '10.30.0.10', 'mac_address' => '00:1A:2B:3C:4D:10', 'hostname' => 'nurse-pc.kyamch.local'],
            ['ip_address' => '10.30.0.20', 'mac_address' => '00:1A:2B:3C:4D:20', 'hostname' => 'printer.kyamch.local'],
            ['ip_address' => '10.30.0.99', 'mac_address' => '00:1A:2B:3C:4D:99', 'hostname' => 'unknown.kyamch.local'],
        ]);

        $syncService = app(IntegrationSyncService::class);
        $syncService->registerAdapter($dhcpAdapter);

        $run = $syncService->sync($this->dhcpIntegration);

        // Verification of metrics
        $this->assertEquals(3, $run->items_scanned);
        $this->assertEquals(1, $run->items_matched);
        $this->assertEquals(2, $run->items_divergent);
        $this->assertEquals('partial', $run->status);

        // Core Invariant Verification: Authoritative allocations are completely untouched!
        $assignedIp->refresh();
        $this->assertEquals(IpAllocationStatus::ASSIGNED, $assignedIp->allocation_status);

        $reservedIp->refresh();
        $this->assertEquals(IpAllocationStatus::RESERVED, $reservedIp->allocation_status);

        // 10.30.0.99 was NOT auto-created as assigned
        $this->assertDatabaseMissing('ip_addresses', ['address_text' => '10.30.0.99']);
    }

    public function test_dns_readonly_sync_identifies_records(): void
    {
        $dnsAdapter = new DnsReadonlySyncAdapter();
        $dnsAdapter->setFixtureRecords([
            ['ip_address' => '10.30.0.1', 'hostname' => 'gateway-30.kyamch.local', 'record_type' => 'PTR'],
        ]);

        $syncService = app(IntegrationSyncService::class);
        $syncService->registerAdapter($dnsAdapter);

        $run = $syncService->sync($this->dnsIntegration);

        $this->assertEquals(1, $run->items_scanned);
        $this->assertEquals(0, $run->items_matched);
        $this->assertEquals(1, $run->items_divergent);
    }
}
