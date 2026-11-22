<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\AddressSpace\Enums\IpAllocationStatus;
use App\Domain\Discovery\Enums\FindingSeverity;
use App\Domain\Discovery\Enums\FindingStatus;
use App\Domain\Discovery\Enums\FindingType;
use App\Domain\Security\Enums\UserRole;
use App\Models\Device;
use App\Models\DeviceInterface;
use App\Models\IpAddress;
use App\Models\IpAssignment;
use App\Models\IpObservation;
use App\Models\Organization;
use App\Models\ReconciliationFinding;
use App\Models\Site;
use App\Models\Subnet;
use App\Models\User;
use App\Models\Vrf;
use App\Services\Discovery\ReconciliationEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconciliationEngineTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Subnet $subnet;
    protected Vrf $vrf;
    protected ReconciliationEngineService $engine;

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

        $site = Site::create([
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
            'name' => 'Clinical Workstation Subnet',
            'cidr' => '10.10.110.0/24',
            'ip_version' => 4,
            'network_hex' => '0a0a6e00',
            'start_hex' => '0a0a6e01',
            'end_hex' => '0a0a6efe',
            'prefix_length' => 24,
            'subnet_type' => 'assignable',
            'status' => 'active',
        ]);

        $this->engine = $this->app->make(ReconciliationEngineService::class);
    }

    /**
     * Verifies detection of unexpected MAC drift.
     */
    public function test_detects_unexpected_mac_address_drift(): void
    {
        $deviceType = \App\Models\DeviceType::create([
            'name' => 'Medical Diagnostic Imaging',
            'code' => 'TYPE-MED-IMG',
            'category' => 'medical',
        ]);

        $site = Site::first();

        $device = Device::create([
            'site_id' => $site->id,
            'device_type_id' => $deviceType->id,
            'name' => 'Cardiology Echo Machine',
            'code' => 'KY-CARD-ECHO01',
            'status' => 'active',
        ]);

        $interface = DeviceInterface::create([
            'device_id' => $device->id,
            'name' => 'eth0',
            'mac_address' => '00:11:22:33:44:aa',
            'status' => 'up',
        ]);

        $ip = IpAddress::create([
            'vrf_id' => $this->vrf->id,
            'subnet_id' => $this->subnet->id,
            'ip_version' => 4,
            'address_hex' => '0a0a6e14', // 10.10.110.20
            'address_text' => '10.10.110.20',
            'allocation_status' => IpAllocationStatus::ASSIGNED,
            'hostname' => 'ky-card-echo01.local',
        ]);

        $assignment = IpAssignment::create([
            'ip_address_id' => $ip->id,
            'interface_id' => $interface->id,
            'assigned_by' => $this->admin->id,
            'assigned_at' => now(),
        ]);

        $ip->update(['current_assignment_id' => $assignment->id]);

        $agent = \App\Models\DiscoveryAgent::create([
            'site_id' => $site->id,
            'name' => 'Test Core Agent',
            'code' => 'AGENT-TEST-02',
            'identity_ref' => 'token:test',
            'enabled' => true,
            'last_seen_at' => now(),
        ]);

        $job = \App\Models\DiscoveryJob::create([
            'agent_id' => $agent->id,
            'subnet_id' => $this->subnet->id,
            'status' => \App\Domain\Discovery\Enums\JobStatus::COMPLETED,
            'source_type' => \App\Domain\Discovery\Enums\DiscoverySourceType::ARP,
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        // Observation observes DIFFERENT MAC: 00:11:22:33:44:bb
        IpObservation::create([
            'discovery_job_id' => $job->id,
            'vrf_id' => $this->vrf->id,
            'subnet_id' => $this->subnet->id,
            'ip_address_id' => $ip->id,
            'ip_version' => 4,
            'address_hex' => '0a0a6e14',
            'address_text' => '10.10.110.20',
            'observed_mac' => '00:11:22:33:44:bb',
            'source_type' => 'arp',
            'confidence' => 95,
            'observed_at' => now(),
        ]);

        $count = $this->engine->reconcileSubnet($this->subnet);
        $this->assertGreaterThanOrEqual(1, $count);

        $this->assertDatabaseHas('reconciliation_findings', [
            'subnet_id' => $this->subnet->id,
            'address_text' => '10.10.110.20',
            'finding_type' => FindingType::UNEXPECTED_MAC->value,
            'observed_mac' => '00:11:22:33:44:bb',
            'authoritative_mac' => '00:11:22:33:44:AA',
            'severity' => FindingSeverity::WARNING->value,
        ]);
    }

    /**
     * Verifies that operator can resolve a finding with an audit trail.
     */
    public function test_operator_can_resolve_finding_with_audit_trail(): void
    {
        $finding = ReconciliationFinding::create([
            'finding_type' => FindingType::UNKNOWN_ACTIVE_IP,
            'vrf_id' => $this->vrf->id,
            'subnet_id' => $this->subnet->id,
            'address_text' => '10.10.110.99',
            'severity' => FindingSeverity::CRITICAL,
            'status' => FindingStatus::OPEN,
            'title' => 'Rogue Device on 10.10.110.99',
            'details' => 'Unmanaged laptop discovered.',
        ]);

        $resolved = $this->engine->resolveFinding($finding, $this->admin, 'Device verified as temporary vendor analyzer and registered in IPAM.');

        $this->assertEquals(FindingStatus::RESOLVED, $resolved->status);
        $this->assertEquals($this->admin->id, $resolved->resolved_by);
        $this->assertNotNull($resolved->resolved_at);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'reconciliation.resolved',
            'actor_id' => $this->admin->id,
        ]);
    }
}
