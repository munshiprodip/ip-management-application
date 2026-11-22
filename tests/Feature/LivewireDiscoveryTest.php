<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Discovery\Enums\FindingSeverity;
use App\Domain\Discovery\Enums\FindingStatus;
use App\Domain\Discovery\Enums\FindingType;
use App\Domain\Security\Enums\UserRole;
use App\Livewire\Discovery\Index as DiscoveryIndex;
use App\Models\DiscoveryAgent;
use App\Models\Organization;
use App\Models\ReconciliationFinding;
use App\Models\Site;
use App\Models\Subnet;
use App\Models\User;
use App\Models\Vrf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected DiscoveryAgent $agent;
    protected Subnet $subnet;
    protected Vrf $vrf;

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
            'name' => 'ICU Assignable Block',
            'cidr' => '10.10.120.0/24',
            'ip_version' => 4,
            'network_hex' => '0a0a7800',
            'start_hex' => '0a0a7801',
            'end_hex' => '0a0a78fe',
            'prefix_length' => 24,
            'subnet_type' => 'assignable',
            'status' => 'active',
        ]);

        $this->agent = DiscoveryAgent::create([
            'site_id' => $site->id,
            'name' => 'Main Core Agent',
            'code' => 'AGENT-MAIN-01',
            'identity_ref' => 'token:livewire-test',
            'enabled' => true,
            'allowed_subnets_json' => [$this->subnet->id],
            'last_seen_at' => now(),
        ]);
    }

    /**
     * Verifies discovery dashboard renders for administrator.
     */
    public function test_discovery_index_renders_with_kpi_and_tabs(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(DiscoveryIndex::class)
            ->assertStatus(200)
            ->assertSee('Discovery')
            ->assertSee('Reconciliation Engine')
            ->assertSee('Total Observations')
            ->assertSee('Open Findings')
            ->assertSee('Discovery Agents');
    }

    /**
     * Verifies investigating and resolving a finding through Livewire.
     */
    public function test_operator_can_investigate_and_resolve_finding(): void
    {
        $finding = ReconciliationFinding::create([
            'finding_type' => FindingType::UNKNOWN_ACTIVE_IP,
            'vrf_id' => $this->vrf->id,
            'subnet_id' => $this->subnet->id,
            'address_text' => '10.10.120.88',
            'severity' => FindingSeverity::CRITICAL,
            'status' => FindingStatus::OPEN,
            'title' => 'Unmanaged Active Device on 10.10.120.88',
            'details' => 'Observed live traffic without assignment.',
        ]);

        $this->actingAs($this->admin);

        Livewire::test(DiscoveryIndex::class)
            ->call('openFindingDrawer', $finding->id)
            ->assertSee('10.10.120.88')
            ->set('resolutionNote', 'Confirmed authorized biomedical telemetry test unit.')
            ->call('resolveFinding', $finding->id)
            ->assertHasNoErrors();

        $this->assertEquals(FindingStatus::RESOLVED, $finding->fresh()->status);
    }

    /**
     * Verifies triggering a discovery scan through Livewire modal.
     */
    public function test_operator_can_trigger_safe_discovery_scan(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(DiscoveryIndex::class)
            ->set('scanAgentId', (string) $this->agent->id)
            ->set('scanSubnetId', (string) $this->subnet->id)
            ->set('scanSourceType', 'fixture')
            ->call('triggerScan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('discovery_jobs', [
            'agent_id' => $this->agent->id,
            'subnet_id' => $this->subnet->id,
            'status' => 'completed',
        ]);
    }
}
