<?php

declare(strict_types=1);

namespace App\Livewire\Scanner;

use App\Actions\AddressSpace\AllocateIpAction;
use App\Domain\AddressSpace\Enums\HostType;
use App\Domain\AddressSpace\Enums\IpClassification;
use App\Jobs\ScanSubnetJob;
use App\Models\IpAddress;
use App\Models\ScanJob;
use App\Models\ScanResult;
use App\Models\Subnet;
use App\Services\Network\ArpLookupService;
use App\Services\Network\NetworkScanService;
use App\Services\SiteContext;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Network IP Scanner - KYAMCH IPAM')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'subnet')]
    public ?int $selectedSubnetId = null;

    public ?int $currentJobId = null;

    public string $statusFilter = 'all'; // all, responding, unregistered, no_reply
    public string $search = '';

    // Allocation Modal
    public bool $showAllocateModal = false;
    public string $alloc_ip = '';
    public string $alloc_classification = 'static_host';
    public string $alloc_host_type = 'workstation';
    public string $alloc_hostname = '';
    public string $alloc_mac_address = '';
    public string $alloc_assigned_to = '';
    public string $alloc_description = '';

    // Details Drawer
    public bool $showDetailDrawer = false;
    public ?int $detailResultId = null;

    public function mount(SiteContext $siteContext, ?int $subnet = null): void
    {
        $requestedSubnetId = $subnet ?? $this->selectedSubnetId ?? (request()->query('subnet') ? (int) request()->query('subnet') : null);

        if ($requestedSubnetId) {
            $targetSubnet = Subnet::find($requestedSubnetId);
            if ($targetSubnet && $targetSubnet->isAssignable() && $targetSubnet->ip_version === 4) {
                $this->selectedSubnetId = $targetSubnet->id;
                $this->loadLatestJob();
                return;
            }
        }

        $activeSiteId = $siteContext->getActiveSiteId();

        $query = Subnet::where('subnet_type', 'assignable')
            ->where('allocation_enabled', true)
            ->where('ip_version', 4);

        if ($activeSiteId) {
            $query->where('site_id', $activeSiteId);
        }

        $firstSubnet = $query->first();

        if (! $firstSubnet && $activeSiteId) {
            $firstSubnet = Subnet::where('subnet_type', 'assignable')
                ->where('allocation_enabled', true)
                ->where('ip_version', 4)
                ->first();
        }

        $this->selectedSubnetId = $firstSubnet?->id;
        $this->loadLatestJob();
    }

    public function updatedSelectedSubnetId(): void
    {
        $this->selectedSubnetId = !empty($this->selectedSubnetId) ? (int) $this->selectedSubnetId : null;
        $this->resetPage();
        $this->loadLatestJob();
    }

    public function selectSubnet(mixed $subnetId): void
    {
        $this->selectedSubnetId = !empty($subnetId) ? (int) $subnetId : null;
        $this->resetPage();
        $this->loadLatestJob();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    protected function loadLatestJob(): void
    {
        if (! $this->selectedSubnetId) {
            $this->currentJobId = null;
            return;
        }

        $latestJob = ScanJob::where('subnet_id', $this->selectedSubnetId)
            ->latest('id')
            ->first();

        $this->currentJobId = $latestJob?->id;
    }

    public function startScan(?int $subnetId = null, ?NetworkScanService $scanner = null): void
    {
        $scanner ??= app(NetworkScanService::class);

        if ($subnetId) {
            $this->selectedSubnetId = $subnetId;
        }

        if (! $this->selectedSubnetId) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Please select a managed target subnet.']);
            return;
        }

        $subnet = Subnet::find($this->selectedSubnetId);
        if (! $subnet) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Selected subnet does not exist.']);
            return;
        }

        try {
            $job = $scanner->createScanJob($subnet, auth()->user());
            $this->currentJobId = $job->id;

            // Dispatch async queue job
            ScanSubnetJob::dispatch($job->id);

            // Execute initial chunk immediately so live feedback appears on first render
            $scanner->executeScan($job, chunkLimit: (config('queue.default') === 'sync' ? 0 : 10));

            $msg = "Discovery scan started for {$subnet->cidr} ({$job->total_targets} addresses).";
            session()->flash('success', $msg);
            $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
            $this->resetPage();
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function stopScan(?NetworkScanService $scanner = null): void
    {
        $scanner ??= app(NetworkScanService::class);

        if (! $this->currentJobId) {
            return;
        }

        $job = ScanJob::find($this->currentJobId);
        if ($job && $job->isRunning()) {
            $scanner->cancelScan($job, auth()->user());
            $msg = "Discovery scan #{$job->id} cancelled by operator.";
            session()->flash('success', $msg);
            $this->dispatch('notify', ['type' => 'warning', 'message' => $msg]);
        }
    }

    public function pollProgress(?NetworkScanService $scanner = null): void
    {
        $scanner ??= app(NetworkScanService::class);

        if (! $this->currentJobId) {
            return;
        }

        $job = ScanJob::find($this->currentJobId);
        if (! $job || ! $job->isRunning()) {
            return;
        }

        // Defensive progress advance: if background queue worker is idle on local dev workstation,
        // execute a bounded slice of 10 targets per poll tick so UI actively scans smoothly
        $scanner->executeScan($job, chunkLimit: 10);
    }

    public function openAllocateModal(int|string $resultOrIp, ?string $hostname = null, ?string $mac = null): void
    {
        $result = null;
        $ip = '';

        if (is_numeric($resultOrIp)) {
            $result = ScanResult::find((int) $resultOrIp);
            $ip = $result?->ip_address ?? '';
        } else {
            $ip = trim((string) $resultOrIp);
            if ($this->currentJobId) {
                $result = ScanResult::where('scan_job_id', $this->currentJobId)
                    ->where('ip_address', $ip)
                    ->first();
            }
            if (! $result) {
                $result = ScanResult::where('ip_address', $ip)->latest('id')->first();
            }
        }

        $this->alloc_ip = $ip;
        $this->alloc_classification = 'static_host';
        $this->alloc_host_type = 'workstation';

        // 1. Resolve Hostname from discovered information
        $discoveredHostname = $result?->discovered_hostname
            ?: (! empty($hostname) ? trim($hostname) : null)
            ?: $result?->ipam_hostname;

        // If still empty, check if any prior scan result recorded a discovered hostname
        if (empty($discoveredHostname) && ! empty($ip)) {
            $discoveredHostname = ScanResult::where('ip_address', $ip)
                ->whereNotNull('discovered_hostname')
                ->where('discovered_hostname', '!=', '')
                ->latest('id')
                ->value('discovered_hostname');
        }

        // If still empty, check authoritative IPAM record
        if (empty($discoveredHostname) && ! empty($ip)) {
            $discoveredHostname = IpAddress::where('address_text', $ip)->value('hostname');
        }

        // If still empty, attempt live reverse DNS and NetBIOS resolution
        if (empty($discoveredHostname) && ! empty($ip) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $resolved = @gethostbyaddr($ip);
            if ($resolved && $resolved !== $ip) {
                $discoveredHostname = $resolved;
            } else {
                $discoveredHostname = app(NetworkScanService::class)->lookupNetbiosName($ip);
            }

            if ($discoveredHostname && $result && empty($result->discovered_hostname)) {
                $result->update(['discovered_hostname' => $discoveredHostname]);
            }
        }

        // 2. Resolve MAC Address from discovered information
        $discoveredMac = $result?->discovered_mac
            ?: (! empty($mac) ? trim($mac) : null);

        // If still empty, check if any prior scan result recorded a discovered MAC
        if (empty($discoveredMac) && ! empty($ip)) {
            $discoveredMac = ScanResult::where('ip_address', $ip)
                ->whereNotNull('discovered_mac')
                ->where('discovered_mac', '!=', '')
                ->latest('id')
                ->value('discovered_mac');
        }

        // If still empty, check authoritative IPAM record
        if (empty($discoveredMac) && ! empty($ip)) {
            $discoveredMac = IpAddress::where('address_text', $ip)->value('mac_address');
        }

        // If still empty, attempt live ARP lookup
        if (empty($discoveredMac) && ! empty($ip) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $discoveredMac = app(ArpLookupService::class)->lookupMac($ip);
            if ($discoveredMac && $result && empty($result->discovered_mac)) {
                $result->update(['discovered_mac' => $discoveredMac]);
            }
        }

        $this->alloc_hostname = $discoveredHostname ?? '';
        $this->alloc_mac_address = $discoveredMac ? strtoupper($discoveredMac) : '';

        // Pre-fill assigned_to if available from IPAM or discovered hostname
        $this->alloc_assigned_to = $result?->ipam_assigned_to
            ?? (! empty($discoveredHostname) ? $discoveredHostname : '');

        $this->alloc_description = 'Discovered via Network IP Scanner on ' . now()->toDateString();
        $this->resetErrorBag();

        $this->showAllocateModal = true;
    }

    public function saveAllocation(?AllocateIpAction $action = null): void
    {
        $action ??= app(AllocateIpAction::class);

        $this->validate([
            'alloc_ip' => 'required|string',
            'alloc_classification' => 'required|string',
            'alloc_host_type' => ['nullable', 'string', 'max:64'],
            'alloc_assigned_to' => ['required', 'string', 'max:255'],
        ]);

        try {
            $action->execute([
                'subnet_id' => $this->selectedSubnetId,
                'ip_address' => $this->alloc_ip,
                'classification' => IpClassification::from($this->alloc_classification),
                'host_type' => !empty($this->alloc_host_type) ? trim($this->alloc_host_type) : null,
                'hostname' => !empty($this->alloc_hostname) ? trim($this->alloc_hostname) : null,
                'mac_address' => !empty($this->alloc_mac_address) ? strtoupper(trim($this->alloc_mac_address)) : null,
                'assigned_to' => trim($this->alloc_assigned_to),
                'description' => !empty($this->alloc_description) ? trim($this->alloc_description) : null,
            ], auth()->user());

            // Immediately synchronize matching scan result in current scan job
            if ($this->currentJobId) {
                $scanResult = ScanResult::where('scan_job_id', $this->currentJobId)
                    ->where('ip_address', $this->alloc_ip)
                    ->first();

                if ($scanResult) {
                    $scanResult->update([
                        'ipam_status' => 'assigned',
                        'ipam_hostname' => !empty($this->alloc_hostname) ? trim($this->alloc_hostname) : null,
                        'ipam_assigned_to' => trim($this->alloc_assigned_to),
                        'is_unregistered' => false,
                    ]);

                    // Decrement unregistered count on job
                    $job = ScanJob::find($this->currentJobId);
                    if ($job && $job->unregistered_count > 0) {
                        $job->decrement('unregistered_count');
                    }
                }
            }

            $msg = "Discovered device at {$this->alloc_ip} assigned to '{$this->alloc_assigned_to}' and registered into IPAM.";
            session()->flash('success', $msg);
            $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
            $this->showAllocateModal = false;
        } catch (\Illuminate\Validation\ValidationException $ve) {
            $this->setErrorBag($ve->validator->getMessageBag());
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Validation failed: ' . $ve->getMessage()]);
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
            $this->addError('alloc_assigned_to', $e->getMessage());
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function viewDetails(int $resultId): void
    {
        $this->detailResultId = $resultId;
        $this->showDetailDrawer = true;
    }

    public function render()
    {
        $activeSiteId = app(SiteContext::class)->getActiveSiteId();

        $subnetsQuery = Subnet::where('subnet_type', 'assignable')
            ->where('allocation_enabled', true)
            ->where('ip_version', 4)
            ->with('site');

        if ($activeSiteId) {
            $subnetsQuery->where('site_id', $activeSiteId);
        }

        $subnets = $subnetsQuery->orderBy('cidr')->get();

        if ($this->selectedSubnetId && ! $subnets->contains('id', $this->selectedSubnetId)) {
            $currentSubnet = Subnet::with('site')->find($this->selectedSubnetId);
            if ($currentSubnet && $currentSubnet->isAssignable() && $currentSubnet->ip_version === 4) {
                $subnets->prepend($currentSubnet);
            }
        }

        $selectedSubnet = $this->selectedSubnetId ? Subnet::with(['vlan', 'site'])->find($this->selectedSubnetId) : null;
        $currentJob = $this->currentJobId ? ScanJob::with('subnet')->find($this->currentJobId) : null;

        $resultsQuery = ScanResult::query();
        if ($this->currentJobId) {
            $resultsQuery->where('scan_job_id', $this->currentJobId);
        } else {
            $resultsQuery->whereRaw('1 = 0');
        }

        if ($this->statusFilter === 'responding') {
            $resultsQuery->where('status', 'responding');
        } elseif ($this->statusFilter === 'unregistered') {
            $resultsQuery->where('is_unregistered', true);
        } elseif ($this->statusFilter === 'no_reply') {
            $resultsQuery->where('status', 'no_reply');
        }

        if (!empty($this->search)) {
            $s = trim($this->search);
            $resultsQuery->where(function ($q) use ($s) {
                $q->where('ip_address', 'like', "%{$s}%")
                    ->orWhere('discovered_hostname', 'like', "%{$s}%")
                    ->orWhere('discovered_mac', 'like', "%{$s}%")
                    ->orWhere('ipam_hostname', 'like', "%{$s}%")
                    ->orWhere('ipam_assigned_to', 'like', "%{$s}%");
            });
        }

        $results = $resultsQuery->orderBy('id')->paginate(50);
        $detailResult = $this->detailResultId ? ScanResult::find($this->detailResultId) : null;

        $dbHostTypes = \Illuminate\Support\Facades\Schema::hasTable('host_types')
            ? \App\Models\HostType::where('is_active', true)->orderBy('name')->get()
            : collect();

        $hostTypesList = $dbHostTypes->isNotEmpty()
            ? $dbHostTypes
            : collect(HostType::cases())->map(fn ($c) => (object) [
                'slug' => $c->value,
                'name' => $c->label(),
                'description' => $c->description(),
            ]);

        return view('livewire.scanner.index', [
            'subnets' => $subnets,
            'selectedSubnet' => $selectedSubnet,
            'currentJob' => $currentJob,
            'results' => $results,
            'detailResult' => $detailResult,
            'classifications' => IpClassification::cases(),
            'hostTypes' => $hostTypesList,
        ]);
    }
}

