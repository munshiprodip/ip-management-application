<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Alerts\Enums\AlertRuleCode;
use App\Domain\Alerts\Enums\AlertSeverity;
use App\Domain\Alerts\Enums\AlertStatus;
use App\Domain\Security\Enums\UserRole;
use App\Livewire\Alerts\Index as AlertsIndex;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Models\Alert;
use App\Models\Organization;
use App\Models\Site;
use App\Models\Subnet;
use App\Models\User;
use App\Models\Vrf;
use App\Services\Alerting\AlertDispatcherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireAlertsAndReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operator;

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
    }

    public function test_alerts_index_renders_and_filters(): void
    {
        $alertService = app(AlertDispatcherService::class);
        $alert = $alertService->dispatchAlert([
            'rule_code' => AlertRuleCode::ROGUE_IP,
            'dedupe_key' => 'TEST:rogue:10.0.0.1',
            'severity' => AlertSeverity::CRITICAL,
            'title' => 'Critical Rogue Device on Network',
            'description' => 'Discovered rogue device transmitting packets',
        ]);

        Livewire::actingAs($this->admin)
            ->test(AlertsIndex::class)
            ->assertOk()
            ->assertSee('Critical Rogue Device on Network')
            ->assertSee('TEST:rogue:10.0.0.1')
            ->assertSee('Critical Incident');
    }

    public function test_operator_can_acknowledge_and_resolve_alert_via_livewire(): void
    {
        $alertService = app(AlertDispatcherService::class);
        $alert = $alertService->dispatchAlert([
            'rule_code' => AlertRuleCode::AGENT_OFFLINE,
            'dedupe_key' => 'TEST:agent:offline',
            'severity' => AlertSeverity::WARNING,
            'title' => 'Collector Agent Stale',
            'description' => 'Zone collector has not checked in for 20 minutes',
        ]);

        Livewire::actingAs($this->admin)
            ->test(AlertsIndex::class)
            ->call('acknowledgeAlert', $alert->id)
            ->assertSee('successfully acknowledged');

        $this->assertEquals(AlertStatus::ACKNOWLEDGED, $alert->fresh()->status);
        $this->assertEquals($this->admin->id, $alert->fresh()->assignee_id);

        Livewire::actingAs($this->admin)
            ->test(AlertsIndex::class)
            ->call('selectAlert', $alert->id)
            ->set('resolutionNote', 'Agent process restarted on collector VM; heartbeats resumed.')
            ->call('resolveAlert')
            ->assertSee('marked as resolved');

        $this->assertEquals(AlertStatus::RESOLVED, $alert->fresh()->status);
        $this->assertNotNull($alert->fresh()->resolved_at);
    }

    public function test_reports_index_renders_and_generates_export(): void
    {
        $org = Organization::create(['name' => 'KYAMCH', 'slug' => 'kyamch-rep-test', 'code' => 'KYAMCH-REP']);
        $site = Site::create(['name' => 'Main Hospital', 'organization_id' => $org->id, 'code' => 'REP-HQ']);
        $vrf = Vrf::create(['name' => 'Default VRF', 'code' => 'VRF-REP', 'rd' => '65000:99']);

        $createSubnet = app(\App\Actions\AddressSpace\CreateSubnetAction::class);
        $createSubnet->execute([
            'vrf_id' => $vrf->id,
            'site_id' => $site->id,
            'name' => 'Reports Subnet Test',
            'cidr' => '10.50.0.0/24',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ReportsIndex::class)
            ->assertOk()
            ->assertSee('Reports, Exports')
            ->call('openExportModal')
            ->assertSet('showExportModal', true)
            ->set('exportType', 'subnets')
            ->call('generateExport')
            ->assertSet('showExportModal', false)
            ->assertSee('Export generated successfully');
    }

    public function test_dashboard_displays_stage_7_operational_views(): void
    {
        $alertService = app(AlertDispatcherService::class);
        $alertService->dispatchAlert([
            'rule_code' => AlertRuleCode::SUBNET_EXHAUSTION,
            'dedupe_key' => 'TEST:dashboard:exhaustion',
            'severity' => AlertSeverity::WARNING,
            'title' => 'Subnet Capacity Watchlist Alert',
            'description' => 'Subnet reaching capacity limits',
        ]);

        Livewire::actingAs($this->admin)
            ->test(DashboardIndex::class)
            ->assertOk()
            ->assertSee('STAGE 7 OPERATIONAL POSTURE')
            ->assertSee('Subnet Capacity')
            ->assertSee('Active Operational Alerts')
            ->assertSee('Subnet Capacity Watchlist Alert');
    }
}
