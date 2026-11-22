<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\AddressSpace\CreateSubnetAction;
use App\Domain\AddressSpace\Enums\IpAllocationStatus;
use App\Domain\AddressSpace\Enums\IpConnectivityStatus;
use App\Domain\Alerts\Enums\AlertRuleCode;
use App\Domain\Alerts\Enums\AlertSeverity;
use App\Domain\Alerts\Enums\AlertStatus;
use App\Domain\Security\Enums\UserRole;
use App\Models\Alert;
use App\Models\ApiToken;
use App\Models\Department;
use App\Models\DiscoveryAgent;
use App\Models\IpAddress;
use App\Models\Organization;
use App\Models\Site;
use App\Models\Subnet;
use App\Models\User;
use App\Models\Vrf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiV1EndpointsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $plainToken;
    private Vrf $vrf;
    private Site $site;
    private Department $dept;
    private Subnet $subnet;

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organization::create(['name' => 'KYAMCH', 'slug' => 'kyamch-api-end', 'code' => 'KYAMCH-END']);
        $this->site = Site::create(['name' => 'Main Hospital', 'organization_id' => $org->id, 'code' => 'END-HQ']);
        $this->dept = Department::create(['name' => 'ICU Clinical', 'code' => 'ICU', 'site_id' => $this->site->id, 'organization_id' => $org->id]);
        $this->vrf = Vrf::create(['name' => 'Default VRF', 'code' => 'VRF-GLOBAL', 'rd' => '65000:1', 'is_default' => true]);

        $this->admin = User::factory()->create([
            'role' => UserRole::NETWORK_ADMIN,
            'status' => 'active',
        ]);

        // Generate token with all scopes
        $created = ApiToken::createToken(
            name: 'Master API Token',
            scopes: ['*'],
            user: $this->admin,
            expiresInDays: 30,
            rateLimit: 120
        );

        $this->plainToken = $created['plain_token'];

        $createSubnet = app(CreateSubnetAction::class);
        $this->subnet = $createSubnet->execute([
            'vrf_id' => $this->vrf->id,
            'site_id' => $this->site->id,
            'department_id' => $this->dept->id,
            'name' => 'Medical Monitoring Block',
            'cidr' => '10.20.10.0/28',
        ]);
    }

    public function test_subnets_capacity_and_candidate_endpoints(): void
    {
        // 1. Capacity
        $capacityResp = $this->withHeader('Authorization', 'Bearer ' . $this->plainToken)
            ->getJson("/api/v1/subnets/{$this->subnet->id}/capacity");

        $capacityResp->assertStatus(200);
        $capacityResp->assertJson([
            'data' => [
                'subnet_id' => $this->subnet->id,
                'cidr' => '10.20.10.0/28',
                'total_addresses' => 16,
                'allocated_addresses' => 0,
                'free_addresses' => 16,
                'utilization_percent' => 0.0,
            ],
        ]);

        // 2. Candidate IPs
        $candResp = $this->withHeader('Authorization', 'Bearer ' . $this->plainToken)
            ->getJson("/api/v1/subnets/{$this->subnet->id}/candidate-ips?limit=3");

        $candResp->assertStatus(200);
        $candResp->assertJsonStructure([
            'data',
            'meta' => ['subnet_id', 'limit'],
        ]);
    }

    public function test_atomic_ip_allocation_and_release_via_api(): void
    {
        // 1. Allocate IP
        $allocResp = $this->withHeader('Authorization', 'Bearer ' . $this->plainToken)
            ->postJson('/api/v1/ip-allocations', [
                'subnet_id' => $this->subnet->id,
                'ip_address' => '10.20.10.5',
                'classification' => 'static_host',
                'fqdn' => 'mon-05.kyamch.local',
                'idempotency_key' => 'alloc_key_test_1001',
            ]);

        $allocResp->assertStatus(201);
        $allocResp->assertJson([
            'data' => [
                'address' => '10.20.10.5',
                'vrf_id' => $this->vrf->id,
                'subnet_id' => $this->subnet->id,
                'allocation_status' => 'assigned',
            ],
        ]);

        $allocatedIpId = $allocResp->json('data.id');

        // 2. Release IP into quarantine (Gate T-012)
        $releaseResp = $this->withHeader('Authorization', 'Bearer ' . $this->plainToken)
            ->postJson("/api/v1/ip-allocations/{$allocatedIpId}/release", [
                'reason' => 'Server decommissioned',
            ]);

        $releaseResp->assertStatus(200);
        $releaseResp->assertJson([
            'data' => [
                'id' => $allocatedIpId,
                'allocation_status' => 'quarantined',
            ],
        ]);

        $this->assertDatabaseHas('ip_addresses', [
            'id' => $allocatedIpId,
            'allocation_status' => 'quarantined',
        ]);
    }

    public function test_submit_ip_request_via_api(): void
    {
        $resp = $this->withHeader('Authorization', 'Bearer ' . $this->plainToken)
            ->postJson('/api/v1/ip-requests', [
                'title' => 'Radiology PACS Station IP',
                'description' => 'Static IP needed for workstation',
                'justification' => 'Direct MRI scanner connection',
                'vrf_id' => $this->vrf->id,
                'subnet_id' => $this->subnet->id,
                'department_id' => $this->dept->id,
                'priority' => 'urgent',
            ]);

        $resp->assertStatus(201);
        $resp->assertJsonStructure([
            'data' => [
                'id',
                'request_number',
                'status',
                'priority',
            ],
        ]);
    }

    /**
     * Gate T-018: Observations strictly update evidence; NEVER mutate authoritative allocation!
     */
    public function test_gate_t018_discovery_observations_update_evidence_without_mutating_allocation(): void
    {
        $agent = DiscoveryAgent::create([
            'site_id' => $this->site->id,
            'name' => 'ICU Zone Probe',
            'code' => 'AGENT-ICU-01',
            'identity_ref' => 'probe-icu-01',
            'api_token' => 'agent_token_hash',
            'allowed_subnets_json' => [$this->subnet->id],
            'allowed_protocols_json' => ['icmp', 'arp'],
            'max_rate_per_second' => 50,
            'status' => 'active',
            'last_heartbeat_at' => now(),
        ]);

        // Pre-create an assigned IP address
        $ip = IpAddress::create([
            'subnet_id' => $this->subnet->id,
            'vrf_id' => $this->vrf->id,
            'ip_version' => 4,
            'address_text' => '10.20.10.8',
            'address_hex' => '0a140a08',
            'allocation_status' => IpAllocationStatus::ASSIGNED,
            'connectivity_status' => IpConnectivityStatus::UNOBSERVED,
        ]);

        // Agent submits live observation
        $resp = $this->withHeader('Authorization', 'Bearer ' . $this->plainToken)
            ->postJson('/api/v1/discovery/observations', [
                'agent_id' => $agent->id,
                'observations' => [
                    [
                        'address_text' => '10.20.10.8',
                        'vrf_id' => $this->vrf->id,
                        'subnet_id' => $this->subnet->id,
                        'source_type' => 'icmp',
                        'mac_address' => '00:1A:2B:3C:4D:08',
                        'response_time_ms' => 2,
                        'confidence_score' => 0.95,
                    ],
                ],
            ]);

        $resp->assertStatus(201);

        // Verification: Observation recorded in ip_observations
        $this->assertDatabaseHas('ip_observations', [
            'address_text' => '10.20.10.8',
            'observed_mac' => '00:1A:2B:3C:4D:08',
        ]);

        // T-018: Authoritative allocation status remains strictly ASSIGNED; connectivity becomes OBSERVED_ONLINE
        $ip->refresh();
        $this->assertEquals(IpAllocationStatus::ASSIGNED, $ip->allocation_status);
        $this->assertEquals(IpConnectivityStatus::OBSERVED_ONLINE, $ip->connectivity_status);
    }

    public function test_acknowledge_and_resolve_alert_via_api(): void
    {
        $alert = Alert::create([
            'rule_code' => AlertRuleCode::IP_CONFLICT,
            'dedupe_key' => 'CONFLICT:ip:10.20.10.99',
            'severity' => AlertSeverity::CRITICAL,
            'status' => AlertStatus::OPEN,
            'title' => 'Conflict on 10.20.10.99',
            'description' => 'Two hosts claiming identical IP',
            'opened_at' => now(),
            'last_occurred_at' => now(),
        ]);

        // 1. Acknowledge
        $ackResp = $this->withHeader('Authorization', 'Bearer ' . $this->plainToken)
            ->postJson("/api/v1/alerts/{$alert->id}/acknowledge");

        $ackResp->assertStatus(200);
        $this->assertEquals(AlertStatus::ACKNOWLEDGED, $alert->fresh()->status);

        // 2. Resolve
        $resResp = $this->withHeader('Authorization', 'Bearer ' . $this->plainToken)
            ->postJson("/api/v1/alerts/{$alert->id}/resolve", [
                'resolution_note' => 'MAC spoofing host quarantined on switch port Fa0/12',
            ]);

        $resResp->assertStatus(200);
        $this->assertEquals(AlertStatus::RESOLVED, $alert->fresh()->status);
        $this->assertNotNull($alert->fresh()->resolved_at);
    }
}
