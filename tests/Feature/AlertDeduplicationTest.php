<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\AddressSpace\Enums\IpAllocationStatus;
use App\Domain\Alerts\Enums\AlertRuleCode;
use App\Domain\Alerts\Enums\AlertSeverity;
use App\Domain\Alerts\Enums\AlertStatus;
use App\Domain\Discovery\Enums\FindingSeverity;
use App\Domain\Discovery\Enums\FindingStatus;
use App\Domain\Discovery\Enums\FindingType;
use App\Domain\Security\Enums\UserRole;
use App\Models\Alert;
use App\Models\IpAddress;
use App\Models\Organization;
use App\Models\ReconciliationFinding;
use App\Models\Site;
use App\Models\Subnet;
use App\Models\User;
use App\Models\Vrf;
use App\Services\Alerting\AlertDispatcherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertDeduplicationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operator;
    private AlertDispatcherService $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::NETWORK_ADMIN,
            'status' => 'active',
        ]);

        $this->operator = User::factory()->create([
            'role' => UserRole::OPERATOR,
            'status' => 'active',
        ]);

        $this->dispatcher = app(AlertDispatcherService::class);
    }

    public function test_dispatch_alert_creates_initial_alert_with_occurrence_one(): void
    {
        $alert = $this->dispatcher->dispatchAlert([
            'rule_code' => AlertRuleCode::ROGUE_IP,
            'dedupe_key' => 'FINDING:rogue:10.10.10.50',
            'severity' => AlertSeverity::CRITICAL,
            'title' => 'Rogue IP Detected: 10.10.10.50',
            'description' => 'Unassigned IP actively responding on network',
        ]);

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'dedupe_key' => 'FINDING:rogue:10.10.10.50',
            'severity' => 'critical',
            'status' => 'open',
            'occurrence_count' => 1,
        ]);
    }

    public function test_subsequent_occurrence_increments_count_without_duplicate_alert(): void
    {
        // First occurrence
        $first = $this->dispatcher->dispatchAlert([
            'rule_code' => AlertRuleCode::ROGUE_IP,
            'dedupe_key' => 'FINDING:rogue:10.10.10.50',
            'severity' => AlertSeverity::CRITICAL,
            'title' => 'Rogue IP Detected: 10.10.10.50',
            'description' => 'Unassigned IP actively responding on network',
        ]);

        $firstOccurredAt = $first->last_occurred_at;

        // Simulate 5 minutes later
        $this->travel(5)->minutes();

        // Second occurrence with identical dedupe key
        $second = $this->dispatcher->dispatchAlert([
            'rule_code' => AlertRuleCode::ROGUE_IP,
            'dedupe_key' => 'FINDING:rogue:10.10.10.50',
            'severity' => AlertSeverity::CRITICAL,
            'title' => 'Rogue IP Detected: 10.10.10.50',
            'description' => 'Second sweep detected same rogue host',
        ]);

        // Assert only ONE alert exists in database
        $this->assertEquals(1, Alert::where('dedupe_key', 'FINDING:rogue:10.10.10.50')->count());
        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(2, $second->occurrence_count);
        $this->assertTrue($second->last_occurred_at->isAfter($firstOccurredAt));
    }

    public function test_subnet_exhaustion_evaluates_capacity_thresholds(): void
    {
        $org = Organization::create(['name' => 'KYAMCH', 'slug' => 'kyamch-test', 'code' => 'KYAMCH-TEST']);
        $site = Site::create(['name' => 'Main Hospital', 'organization_id' => $org->id, 'code' => 'HQ-TEST']);
        $vrf = Vrf::create(['name' => 'Default VRF', 'code' => 'VRF-TEST', 'rd' => '65000:10']);

        $createSubnet = app(\App\Actions\AddressSpace\CreateSubnetAction::class);
        $subnet = $createSubnet->execute([
            'vrf_id' => $vrf->id,
            'site_id' => $site->id,
            'name' => 'Point to Point Test',
            'cidr' => '192.168.100.0/30',
        ]);

        // Case 1: 0 allocations (0% utilization) -> No alert
        $alertZero = $this->dispatcher->evaluateSubnetExhaustion($subnet);
        $this->assertNull($alertZero);

        // Case 2: 3 allocations out of 4 (75% utilization) -> Still < 80%, No alert
        // Let's create 3 assigned IP records
        for ($i = 0; $i < 3; $i++) {
            IpAddress::create([
                'subnet_id' => $subnet->id,
                'vrf_id' => $vrf->id,
                'ip_version' => 4,
                'address_text' => '192.168.100.' . $i,
                'address_hex' => sprintf('c0a8640%x', $i),
                'allocation_status' => IpAllocationStatus::ASSIGNED,
            ]);
        }

        $alert75 = $this->dispatcher->evaluateSubnetExhaustion($subnet);
        $this->assertNull($alert75);

        // Case 3: 4th allocation (100% utilization >= 95%) -> CRITICAL Alert
        IpAddress::create([
            'subnet_id' => $subnet->id,
            'vrf_id' => $vrf->id,
            'ip_version' => 4,
            'address_text' => '192.168.100.3',
            'address_hex' => 'c0a86403',
            'allocation_status' => IpAllocationStatus::ASSIGNED,
        ]);

        $alert100 = $this->dispatcher->evaluateSubnetExhaustion($subnet);
        $this->assertNotNull($alert100);
        $this->assertEquals(AlertSeverity::CRITICAL, $alert100->severity);
        $this->assertEquals(AlertRuleCode::SUBNET_EXHAUSTION, $alert100->rule_code);
    }

    public function test_evaluate_from_reconciliation_finding_generates_alert(): void
    {
        $org = Organization::create(['name' => 'KYAMCH', 'slug' => 'kyamch-find-test', 'code' => 'KYAMCH-FIND']);
        $site = Site::create(['name' => 'Main Hospital', 'organization_id' => $org->id, 'code' => 'FIND-HQ']);
        $vrf = Vrf::create(['name' => 'Default VRF', 'code' => 'VRF-FIND', 'rd' => '65000:99']);

        $createSubnet = app(\App\Actions\AddressSpace\CreateSubnetAction::class);
        $subnet = $createSubnet->execute([
            'vrf_id' => $vrf->id,
            'site_id' => $site->id,
            'name' => 'Clinical Network',
            'cidr' => '172.16.20.0/24',
        ]);

        $finding = ReconciliationFinding::create([
            'vrf_id' => $vrf->id,
            'subnet_id' => $subnet->id,
            'finding_type' => FindingType::UNKNOWN_ACTIVE_IP,
            'severity' => FindingSeverity::CRITICAL,
            'status' => FindingStatus::OPEN,
            'address_text' => '172.16.20.10',
            'observed_mac' => 'AA:BB:CC:DD:EE:01',
            'title' => 'Rogue Device on Clinical Network',
            'details' => 'Active IP observed without registration',
        ]);

        $alert = $this->dispatcher->evaluateFromFinding($finding);

        $this->assertEquals(AlertRuleCode::ROGUE_IP, $alert->rule_code);
        $this->assertEquals(AlertSeverity::CRITICAL, $alert->severity);
        $this->assertEquals($finding->id, $alert->finding_id);
    }

    public function test_alert_lifecycle_transitions(): void
    {
        $alert = $this->dispatcher->dispatchAlert([
            'rule_code' => AlertRuleCode::IP_CONFLICT,
            'dedupe_key' => 'CONFLICT:ip:10.0.0.1',
            'severity' => AlertSeverity::CRITICAL,
            'title' => 'IP Conflict on 10.0.0.1',
            'description' => 'Two distinct MAC addresses claiming identical IP',
        ]);

        $this->assertTrue($alert->isOpen());

        // Acknowledge
        $alert->acknowledge($this->admin);
        $this->assertEquals(AlertStatus::ACKNOWLEDGED, $alert->status);
        $this->assertEquals($this->admin->id, $alert->assignee_id);
        $this->assertTrue($alert->isOpen());

        // Resolve
        $alert->resolve($this->admin, 'Duplicate MAC disconnected from port 12');
        $this->assertEquals(AlertStatus::RESOLVED, $alert->status);
        $this->assertNotNull($alert->resolved_at);
        $this->assertFalse($alert->isOpen());
    }
}
