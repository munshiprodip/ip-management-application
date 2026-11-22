<?php

declare(strict_types=1);

namespace App\Livewire\Discovery;

use App\Domain\Discovery\Enums\DiscoverySourceType;
use App\Domain\Discovery\Enums\FindingSeverity;
use App\Domain\Discovery\Enums\FindingStatus;
use App\Models\DiscoveryAgent;
use App\Models\DiscoveryJob;
use App\Models\IpObservation;
use App\Models\ReconciliationFinding;
use App\Models\Subnet;
use App\Services\Discovery\DiscoveryExecutionService;
use App\Services\Discovery\ReconciliationEngineService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Discovery & Reconciliation - KYAMCH IPAM')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $activeTab = 'findings'; // findings, observations, agents, jobs

    #[Url]
    public string $search = '';

    #[Url]
    public string $severityFilter = '';

    #[Url]
    public string $statusFilter = 'open'; // open, resolved, all

    // Finding Drawer
    public ?int $selectedFindingId = null;
    public string $resolutionNote = '';

    // Trigger Scan Modal
    public bool $showScanModal = false;
    public string $scanAgentId = '';
    public string $scanSubnetId = '';
    public string $scanSourceType = 'fixture';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingActiveTab(): void
    {
        $this->resetPage();
    }

    public function openFindingDrawer(int $id): void
    {
        $this->selectedFindingId = $id;
        $this->resolutionNote = '';
    }

    public function closeFindingDrawer(): void
    {
        $this->selectedFindingId = null;
        $this->resolutionNote = '';
    }

    public function acknowledgeFinding(int $id, ReconciliationEngineService $engine): void
    {
        $finding = ReconciliationFinding::findOrFail($id);
        $finding->acknowledge(Auth::user());
        session()->flash('success', "Finding {$finding->title} marked as acknowledged.");
    }

    public function resolveFinding(int $id, ReconciliationEngineService $engine): void
    {
        $this->validate([
            'resolutionNote' => 'required|string|min:5|max:500',
        ]);

        $finding = ReconciliationFinding::findOrFail($id);

        try {
            $engine->resolveFinding($finding, Auth::user(), $this->resolutionNote);
            session()->flash('success', "Finding {$finding->title} resolved successfully.");
            $this->closeFindingDrawer();
        } catch (\Throwable $e) {
            session()->flash('error', 'Error resolving finding: ' . $e->getMessage());
        }
    }

    public function ignoreFinding(int $id): void
    {
        $this->validate([
            'resolutionNote' => 'required|string|min:5|max:500',
        ]);

        $finding = ReconciliationFinding::findOrFail($id);
        $finding->ignore(Auth::user(), $this->resolutionNote);
        session()->flash('success', "Finding {$finding->title} marked as ignored.");
        $this->closeFindingDrawer();
    }

    public function openScanModal(): void
    {
        $this->scanAgentId = (string) (DiscoveryAgent::enabled()->first()?->id ?? '');
        $this->scanSubnetId = (string) (Subnet::first()?->id ?? '');
        $this->scanSourceType = 'fixture';
        $this->showScanModal = true;
    }

    public function closeScanModal(): void
    {
        $this->showScanModal = false;
    }

    public function triggerScan(DiscoveryExecutionService $executionService): void
    {
        $this->validate([
            'scanAgentId' => 'required|integer|exists:discovery_agents,id',
            'scanSubnetId' => 'required|integer|exists:subnets,id',
            'scanSourceType' => 'required|string',
        ]);

        $agent = DiscoveryAgent::findOrFail((int) $this->scanAgentId);
        $subnet = Subnet::findOrFail((int) $this->scanSubnetId);
        $sourceType = DiscoverySourceType::from($this->scanSourceType);

        try {
            $job = $executionService->executeScan($agent, $subnet, Auth::user(), $sourceType);
            session()->flash('success', "Discovery job #{$job->id} executed on {$subnet->cidr}. Ingested {$job->metrics_json['observations_count']} observations.");
            $this->closeScanModal();
            $this->activeTab = 'findings';
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
        } catch (DomainException $e) {
            session()->flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            session()->flash('error', 'Scan failed: ' . $e->getMessage());
        }
    }

    public function render(): View
    {
        $discoveryEnabled = (bool) config('ipam.discovery_enabled', false);

        // KPIs
        $totalObservations = IpObservation::count();
        $openFindingsCount = ReconciliationFinding::open()->count();
        $criticalFindingsCount = ReconciliationFinding::open()->critical()->count();
        $agentsTotal = DiscoveryAgent::count();
        $agentsHealthy = DiscoveryAgent::all()->filter(fn ($a) => $a->isHealthy())->count();

        // Findings Query
        $findingsQuery = ReconciliationFinding::with(['subnet', 'vrf', 'ipAddress', 'resolver']);

        if ($this->statusFilter === 'open') {
            $findingsQuery->open();
        } elseif ($this->statusFilter === 'resolved') {
            $findingsQuery->where('status', FindingStatus::RESOLVED->value);
        }

        if ($this->severityFilter !== '') {
            $findingsQuery->where('severity', $this->severityFilter);
        }

        if ($this->search !== '') {
            $findingsQuery->where(function ($q) {
                $q->where('address_text', 'like', '%' . $this->search . '%')
                    ->orWhere('title', 'like', '%' . $this->search . '%')
                    ->orWhere('details', 'like', '%' . $this->search . '%');
            });
        }

        $findings = $findingsQuery->orderByDesc('created_at')->paginate(12);

        // Observations Query
        $observations = IpObservation::with(['job.agent', 'subnet', 'vrf'])
            ->orderByDesc('observed_at')
            ->paginate(15);

        // Agents Query
        $agents = DiscoveryAgent::with('site')->orderBy('name')->get();

        // Jobs Query
        $jobs = DiscoveryJob::with(['agent', 'subnet', 'requester'])->orderByDesc('created_at')->take(20)->get();

        // Lookup selected finding
        $selectedFinding = null;
        if ($this->selectedFindingId !== null) {
            $selectedFinding = ReconciliationFinding::with(['subnet', 'vrf', 'ipAddress.currentAssignment.deviceInterface.device', 'resolver'])
                ->find($this->selectedFindingId);
        }

        $allSubnets = Subnet::where('subnet_type', 'assignable')->with('vrf')->orderBy('cidr')->get();

        return view('livewire.discovery.index', [
            'discoveryEnabled' => $discoveryEnabled,
            'totalObservations' => $totalObservations,
            'openFindingsCount' => $openFindingsCount,
            'criticalFindingsCount' => $criticalFindingsCount,
            'agentsTotal' => $agentsTotal,
            'agentsHealthy' => $agentsHealthy,
            'findings' => $findings,
            'observations' => $observations,
            'agents' => $agents,
            'jobs' => $jobs,
            'selectedFinding' => $selectedFinding,
            'allSubnets' => $allSubnets,
        ]);
    }
}
