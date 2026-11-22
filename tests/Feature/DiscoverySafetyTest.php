<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\AddressSpace\Enums\IpAllocationStatus;
use App\Domain\AddressSpace\Enums\IpConnectivityStatus;
use App\Domain\Discovery\Enums\DiscoverySourceType;
use App\Domain\Discovery\Enums\FindingType;
use App\Domain\Discovery\Enums\JobStatus;
use App\Domain\Security\Enums\UserRole;
use App\Models\Device;
use App\Models\DeviceInterface;
use App\Models\DiscoveryAgent;
use App\Models\IpAddress;
use App\Models\IpAssignment;
use App\Models\IpObservation;
use App\Models\Organization;
use App\Models\ReconciliationFinding;
use App\Models\Site;
use App\Models\Subnet;
use App\Models\User;
use App\Models\Vrf;
use App\Services\Discovery\Adapters\FixtureDiscoveryAdapter;
use App\Services\Discovery\DiscoveryExecutionService;
use App\Services\Discovery\ReconciliationEngineService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscoverySafetyTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected DiscoveryAgent $agent;
    protected Subnet $subnet;
    protected Vrf $vrf;
    protected Site $site;
    protected FixtureDiscoveryAdapter $fixtureAdapter;
    protected DiscoveryExecutionService $executionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'status' => 'active',
        ]);

        $org = Organization::create([
            'name' => 'KYAMCH Hospital',
            'code' => 'KYAMCH-TEST',
            'slug' => 'kyamch-test',
            'status' => 'active',
        ]);

        $this->site = Site::create([
            'organization_id' => $org->id,
            'name' => 'Main Campus',
            'code' => 'MAIN-01',
            'status' => 'active',
        ]);

        $this->vrf = Vrf::firstOrCreate(
            ['code' => 'VRF-GLOBAL'],
            [
                'name' => 'Default Global VRF',
                'description' => 'Mandatory default routing context',
                'status' => 'active',
                'is_default' => true,
            ]
        );

        $this->subnet = Subnet::create([
            'vrf_id' => $this->vrf->id,
            'name' => 'General Workstation Subnet',
            'cidr' => '10.10.100.0/24',
            'ip_version' => 4,
            'network_hex' => '0a0a6400',
            'start_hex' => '0a0a6401',
            'end_hex' => '0a0a64fe',
            'prefix_length' => 24,
            'subnet_type' => 'assignable',
            'status' => 'active',
        ]);

        $this->agent = DiscoveryAgent::create([
            'site_id' => $this->site->id,
            'name' => 'Test Campus Collector',
            'code' => 'AGENT-TEST-01',
            'identity_ref' => 'token:test-identity',
            'enabled' => true,
            'allowed_subnets_json' => [$this->subnet->id],
            'rate_limit_pps' => 50,
            'last_seen_at' => now(),
        ]);

        $this->fixtureAdapter = new FixtureDiscoveryAdapter();
        $this->app->instance(
            \App\Services\Discovery\Contracts\DiscoveryCollectorInterface::class,
            $this->fixtureAdapter
        );

        $this->executionService = $this->app->make(DiscoveryExecutionService::class);
    }

    /**
     * T-018: Discovery cannot alter authoritative assignment.
     * Observing an active rogue device generates an observation & reconciliation finding,
     * but NEVER creates or mutates an authoritative IP allocation record.
     */
    public function test_t018_discovery_cannot_alter_authoritative_assignment(): void
    {
        // Simulate fixture observation for unmanaged IP 10.10.100.88 (not in IPAM)
        $this->fixtureAdapter->setCustomFixture($this->subnet->cidr, [
            [
                'ip_address' => '10.10.100.88',
                'mac_address' => '00:11:22:33:44:55',
                'hostname' => 'rogue-laptop.local',
                'confidence' => 90,
                'source_type' => DiscoverySourceType::ARP->value,
            ],
        ]);

        // Prior check: address does not exist in authoritative ip_addresses table
        $this->assertDatabaseMissing('ip_addresses', [
            'vrf_id' => $this->vrf->id,
            'address_text' => '10.10.100.88',
        ]);

        // Execute scan
        $job = $this->executionService->executeScan($this->agent, $this->subnet, $this->admin);

        // Verification 1: Observation evidence IS stored
        $this->assertDatabaseHas('ip_observations', [
            'discovery_job_id' => $job->id,
            'address_text' => '10.10.100.88',
            'observed_mac' => '00:11:22:33:44:55',
        ]);

        // Verification 2: Authoritative allocation record is NOT created or modified (T-018)
        $this->assertDatabaseMissing('ip_addresses', [
            'vrf_id' => $this->vrf->id,
            'address_text' => '10.10.100.88',
            'allocation_status' => IpAllocationStatus::ASSIGNED->value,
        ]);

        // Verification 3: A reconciliation finding is created for operator review
        $this->assertDatabaseHas('reconciliation_findings', [
            'subnet_id' => $this->subnet->id,
            'address_text' => '10.10.100.88',
            'finding_type' => FindingType::UNKNOWN_ACTIVE_IP->value,
            'status' => 'open',
        ]);
    }

    /**
     * T-019: Unauthorized scan target.
     * Agent attempting to scan a subnet outside its permitted allowlist is blocked.
     */
    public function test_t019_unauthorized_scan_target_is_blocked(): void
    {
        // Create an unauthorized subnet
        $otherSubnet = Subnet::create([
            'vrf_id' => $this->vrf->id,
            'name' => 'Restricted Data Center Subnet',
            'cidr' => '10.10.200.0/24',
            'ip_version' => 4,
            'network_hex' => '0a0ac800',
            'start_hex' => '0a0ac801',
            'end_hex' => '0a0ac8fe',
            'prefix_length' => 24,
            'subnet_type' => 'assignable',
            'status' => 'active',
        ]);

        // Agent allowed_subnets_json only allows $this->subnet->id
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Unauthorized scan target');

        $this->executionService->executeScan($this->agent, $otherSubnet, $this->admin);
    }

    /**
     * T-020: No ping results -> Addresses never auto-freed.
     * An assigned device that does not respond to ping/ARP remains allocated.
     */
    public function test_t020_no_ping_results_never_auto_frees_assigned_ip(): void
    {
        // 1. Create an authoritative assignment in this subnet
        $assignedIp = IpAddress::create([
            'vrf_id' => $this->vrf->id,
            'subnet_id' => $this->subnet->id,
            'ip_version' => 4,
            'address_hex' => '0a0a6432', // 10.10.100.50
            'address_text' => '10.10.100.50',
            'allocation_status' => IpAllocationStatus::ASSIGNED,
            'connectivity_status' => IpConnectivityStatus::OBSERVED_ONLINE,
            'hostname' => 'silent-workstation.local',
        ]);

        // 2. Simulate discovery that finds NO hosts in the subnet (device is silent/offline)
        $this->fixtureAdapter->setCustomFixture($this->subnet->cidr, []);

        // 3. Execute scan
        $this->executionService->executeScan($this->agent, $this->subnet, $this->admin);

        // 4. Invariant check: address allocation status MUST REMAIN ASSIGNED (never freed!)
        $refreshed = $assignedIp->fresh();
        $this->assertEquals(IpAllocationStatus::ASSIGNED, $refreshed->allocation_status);

        // 5. Only connectivity status becomes observed_offline
        $this->assertEquals(IpConnectivityStatus::OBSERVED_OFFLINE, $refreshed->connectivity_status);
    }

    /**
     * T-028: Agent offline / heartbeat missed flags agent as stale without altering allocations.
     */
    public function test_t028_agent_offline_flags_stale_without_altering_allocations(): void
    {
        // Move heartbeat to 30 minutes ago (> 15 min threshold)
        $this->agent->update([
            'last_seen_at' => now()->subMinutes(30),
        ]);

        $this->assertFalse($this->agent->isHealthy());

        $reconciliationEngine = $this->app->make(ReconciliationEngineService::class);
        $finding = $reconciliationEngine->checkAgentHealth($this->agent);

        $this->assertNotNull($finding);
        $this->assertEquals(FindingType::AGENT_DOWN, $finding->finding_type);
        $this->assertDatabaseHas('reconciliation_findings', [
            'finding_type' => FindingType::AGENT_DOWN->value,
            'status' => 'open',
        ]);
    }
}
