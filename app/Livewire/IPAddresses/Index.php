<?php

declare(strict_types=1);

namespace App\Livewire\IPAddresses;

use App\Actions\AddressSpace\AllocateIpAction;
use App\Actions\AddressSpace\ReleaseIpAction;
use App\Actions\AddressSpace\ReserveIpAction;
use App\Actions\AddressSpace\UnquarantineIpAction;
use App\Domain\AddressSpace\Enums\HostType;
use App\Domain\AddressSpace\Enums\IpAllocationStatus;
use App\Domain\AddressSpace\Enums\IpClassification;
use App\Domain\AddressSpace\ValueObjects\IpAddress as IpAddressVO;
use App\Models\IpAddress;
use App\Models\Subnet;
use App\Services\AddressSpace\CandidateWindowGenerator;
use App\Services\AddressSpace\IpAllocationService;
use App\Services\SiteContext;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('IP Explorer & Address Allocation - KYAMCH IPAM')]
class Index extends Component
{
    #[Url(as: 'subnet')]
    public ?int $selectedSubnetId = null;

    // Window navigation
    public int $currentPage = 1;
    public int $perPage = 50;
    public string $statusFilter = 'all'; // 'all', 'unassigned', 'assigned', 'reserved', 'quarantined'
    #[Url(as: 'host_type')]
    public string $hostTypeFilter = 'all';
    public string $search = '';

    // Allocate Modal
    public bool $showAllocateModal = false;
    public string $alloc_ip = '';
    public bool $alloc_auto_free = false;
    public string $alloc_classification = 'static_host';
    public string $alloc_host_type = 'workstation';
    public string $alloc_hostname = '';
    public string $alloc_mac_address = '';
    public string $alloc_assigned_to = '';
    public string $alloc_description = '';

    // Release Modal
    public bool $showReleaseModal = false;
    public ?int $releaseIpId = null;
    public string $release_reason = '';
    public int $release_quarantine_days = 3;

    // Reserve Modal
    public bool $showReserveModal = false;
    public string $reserve_ip = '';
    public string $reserve_reason = '';
    public ?string $reserve_expires_at = null;

    // Details Slide-over
    public bool $showDetailsDrawer = false;
    public ?int $detailIpId = null;

    // Edit Assignment Modal
    public bool $showEditModal = false;
    public ?int $editIpId = null;
    public string $edit_ip_text = '';
    public string $edit_assigned_to = '';
    public ?string $edit_host_type = null;
    public string $edit_hostname = '';
    public string $edit_mac_address = '';
    public string $edit_description = '';

    public function mount(SiteContext $siteContext, ?int $subnet = null): void
    {
        $requestedSubnetId = $subnet ?? $this->selectedSubnetId ?? (request()->query('subnet') ? (int) request()->query('subnet') : null);

        if ($requestedSubnetId) {
            $targetSubnet = Subnet::find($requestedSubnetId);
            if ($targetSubnet) {
                $this->selectedSubnetId = $targetSubnet->id;

                // Sync active campus context if target subnet has a specific site
                if ($targetSubnet->site_id && $targetSubnet->site_id !== $siteContext->getActiveSiteId()) {
                    $siteContext->setActiveSiteId($targetSubnet->site_id);
                }
                return;
            }
        }

        $activeSiteId = $siteContext->getActiveSiteId();

        $query = Subnet::where('subnet_type', 'assignable')
            ->where('allocation_enabled', true);

        if ($activeSiteId) {
            $query->where('site_id', $activeSiteId);
        }

        $firstSubnet = $query->first();

        // Fallback to any assignable if no subnet found in active site
        if (!$firstSubnet && $activeSiteId) {
            $firstSubnet = Subnet::where('subnet_type', 'assignable')
                ->where('allocation_enabled', true)
                ->first();
        }

        $this->selectedSubnetId = $firstSubnet?->id;
    }

    public function updatedSelectedSubnetId(): void
    {
        $this->currentPage = 1;
    }

    public function nextPage(): void
    {
        $this->currentPage++;
    }

    public function previousPage(): void
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
        }
    }

    public function openAllocateModal(?string $ip = null): void
    {
        $this->alloc_ip = $ip ?? '';
        $this->alloc_auto_free = empty($ip);
        $this->alloc_classification = 'static_host';
        $this->alloc_host_type = 'workstation';
        $this->alloc_hostname = '';
        $this->alloc_mac_address = '';
        $this->alloc_assigned_to = '';
        $this->alloc_description = '';
        $this->resetErrorBag();

        if ($this->alloc_auto_free && $this->selectedSubnetId) {
            $this->suggestNextFree();
        }

        $this->showAllocateModal = true;
    }

    public function suggestNextFree(): void
    {
        if (! $this->selectedSubnetId) {
            return;
        }

        $subnet = Subnet::find($this->selectedSubnetId);
        if ($subnet) {
            $service = app(IpAllocationService::class);
            $candidate = $service->findNextFreeCandidate($subnet);
            if ($candidate) {
                $this->alloc_ip = $candidate->toNormalizedString();
            }
        }
    }

    public function saveAllocation(AllocateIpAction $action): void
    {
        $this->validate([
            'alloc_ip' => 'required|string',
            'alloc_classification' => 'required|string',
            'alloc_host_type' => ['nullable', 'string', 'max:64'],
        ]);

        try {
            $action->execute([
                'subnet_id' => $this->selectedSubnetId,
                'ip_address' => $this->alloc_ip,
                'classification' => IpClassification::from($this->alloc_classification),
                'host_type' => !empty($this->alloc_host_type) ? trim($this->alloc_host_type) : null,
                'hostname' => !empty($this->alloc_hostname) ? $this->alloc_hostname : null,
                'mac_address' => !empty($this->alloc_mac_address) ? $this->alloc_mac_address : null,
                'assigned_to' => !empty($this->alloc_assigned_to) ? $this->alloc_assigned_to : null,
                'description' => !empty($this->alloc_description) ? $this->alloc_description : null,
            ], auth()->user());

            session()->flash('success', "IP address {$this->alloc_ip} assigned successfully.");
            $this->showAllocateModal = false;
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function openReleaseModal(int $ipId): void
    {
        $ip = IpAddress::findOrFail($ipId);

        $this->releaseIpId = $ip->id;
        $this->release_reason = 'Decommissioned host or released IP.';
        $this->release_quarantine_days = 3;
        $this->showReleaseModal = true;
    }

    public function confirmRelease(ReleaseIpAction $action): void
    {
        $ip = IpAddress::findOrFail($this->releaseIpId);

        try {
            $action->execute($ip, $this->release_reason, $this->release_quarantine_days, auth()->user());
            session()->flash('success', "IP address {$ip->address_text} released and quarantined.");
            $this->showReleaseModal = false;
            $this->showDetailsDrawer = false;
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function openReserveModal(string $ip): void
    {
        $this->reserve_ip = $ip;
        $this->reserve_reason = 'Future host reservation.';
        $this->reserve_expires_at = now()->addDays(30)->toDateString();
        $this->showReserveModal = true;
    }

    public function confirmReservation(ReserveIpAction $action): void
    {
        $this->validate([
            'reserve_ip' => 'required|string',
            'reserve_reason' => 'required|string|max:255',
        ]);

        try {
            $action->execute([
                'subnet_id' => $this->selectedSubnetId,
                'ip_address' => $this->reserve_ip,
                'reason' => $this->reserve_reason,
                'expires_at' => $this->reserve_expires_at,
            ], auth()->user());

            session()->flash('success', "IP address {$this->reserve_ip} reserved.");
            $this->showReserveModal = false;
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function viewDetails(int $ipId): void
    {
        $this->detailIpId = $ipId;
        $this->showDetailsDrawer = true;
    }

    public function openEditAssignmentModal(string $ipText): void
    {
        $this->edit_ip_text = $ipText;
        $ipVO = IpAddressVO::parse($ipText);
        $record = IpAddress::where('subnet_id', $this->selectedSubnetId)
            ->where('address_hex', $ipVO->toHex())
            ->first();

        if ($record) {
            $this->editIpId = $record->id;
            $this->edit_assigned_to = (string) $record->assigned_to;
            $this->edit_host_type = $record->host_type?->value ?? 'workstation';
            $this->edit_hostname = (string) $record->hostname;
            $this->edit_mac_address = (string) $record->mac_address;
            $this->edit_description = (string) $record->description;
        } else {
            $this->editIpId = null;
            $this->edit_assigned_to = '';
            $this->edit_host_type = 'workstation';
            $this->edit_hostname = '';
            $this->edit_mac_address = '';
            $this->edit_description = '';
        }

        $this->showEditModal = true;
    }

    public function saveAssignmentEdit(): void
    {
        $this->validate([
            'edit_assigned_to' => ['required', 'string', 'max:255'],
            'edit_host_type' => ['nullable', 'string', 'max:64'],
            'edit_hostname' => ['nullable', 'string', 'max:255'],
            'edit_mac_address' => ['nullable', 'string', 'max:32'],
            'edit_description' => ['nullable', 'string', 'max:1000'],
        ]);

        $ipVO = IpAddressVO::parse($this->edit_ip_text);
        $record = IpAddress::where('subnet_id', $this->selectedSubnetId)
            ->where('address_hex', $ipVO->toHex())
            ->first();

        $hostType = !empty($this->edit_host_type) ? trim($this->edit_host_type) : null;

        if ($record) {
            $before = $record->toArray();
            $record->update([
                'assigned_to' => trim($this->edit_assigned_to),
                'host_type' => $hostType,
                'hostname' => $this->edit_hostname ? trim($this->edit_hostname) : null,
                'mac_address' => $this->edit_mac_address ? strtoupper(trim($this->edit_mac_address)) : null,
                'description' => $this->edit_description ? trim($this->edit_description) : null,
            ]);

            // Also update current active assignment record if exists
            $assignment = $record->currentAssignment;
            if ($assignment) {
                $assignment->update([
                    'assigned_to' => trim($this->edit_assigned_to),
                    'host_type' => $hostType,
                    'hostname' => $this->edit_hostname ? trim($this->edit_hostname) : null,
                    'mac_address' => $this->edit_mac_address ? strtoupper(trim($this->edit_mac_address)) : null,
                ]);
            }

            \App\Models\AuditEvent::record(
                eventType: 'ip.assignment_updated',
                reason: "Updated assigned details for IP {$record->address_text}",
                subject: $record,
                before: $before,
                after: $record->fresh()->toArray()
            );

            session()->flash('success', "Assigned To details for IP {$this->edit_ip_text} updated successfully.");
        } else {
            $allocateAction = app(\App\Actions\AddressSpace\AllocateIpAction::class);
            $allocateAction->execute([
                'subnet_id' => $this->selectedSubnetId,
                'ip_address' => $this->edit_ip_text,
                'assigned_to' => trim($this->edit_assigned_to),
                'host_type' => $hostType,
                'hostname' => $this->edit_hostname ? trim($this->edit_hostname) : null,
                'mac_address' => $this->edit_mac_address ? strtoupper(trim($this->edit_mac_address)) : null,
                'description' => $this->edit_description ? trim($this->edit_description) : null,
            ], auth()->user());

            session()->flash('success', "IP {$this->edit_ip_text} assigned to '{$this->edit_assigned_to}' successfully.");
        }

        $this->showEditModal = false;
    }

    public function unquarantineIp(string $ipText, UnquarantineIpAction $action): void
    {
        $ipVO = IpAddressVO::parse($ipText);
        $record = IpAddress::where('subnet_id', $this->selectedSubnetId)
            ->where('address_hex', $ipVO->toHex())
            ->first();

        if (! $record) {
            session()->flash('error', "IP address record for {$ipText} not found.");
            return;
        }

        try {
            $action->execute($record, 'Quarantine manually cleared by operator', auth()->user());
            session()->flash('success', "IP address {$ipText} removed from quarantine and marked as unassigned / available.");
            $this->showDetailsDrawer = false;
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $activeSiteId = app(SiteContext::class)->getActiveSiteId();

        $subnetQuery = Subnet::where('subnet_type', 'assignable')->with('site');
        if ($activeSiteId) {
            $subnetQuery->where('site_id', $activeSiteId);
        }

        $subnets = $subnetQuery->orderBy('cidr')->get();

        // If currently selected subnet exists, ensure it is included in options even if site differed
        if ($this->selectedSubnetId && !$subnets->contains('id', $this->selectedSubnetId)) {
            $currentSubnet = Subnet::with('site')->find($this->selectedSubnetId);
            if ($currentSubnet) {
                $subnets->prepend($currentSubnet);
            }
        }

        if (!$this->selectedSubnetId && $subnets->isNotEmpty()) {
            $this->selectedSubnetId = $subnets->first()->id;
        }

        $selectedSubnet = $this->selectedSubnetId ? Subnet::with(['vlan', 'site', 'pools', 'exclusions'])->find($this->selectedSubnetId) : null;

        $windowData = ['items' => [], 'total' => 0, 'per_page' => $this->perPage, 'current_page' => 1, 'last_page' => 1];

        if ($selectedSubnet) {
            $generator = app(CandidateWindowGenerator::class);
            $windowData = $generator->generateWindow($selectedSubnet, $this->currentPage, $this->perPage);

            if ($this->statusFilter !== 'all') {
                $filter = $this->statusFilter;
                $windowData['items'] = array_filter($windowData['items'], function ($item) use ($filter) {
                    $statusValue = $item['status'] instanceof IpAllocationStatus ? $item['status']->value : (string) $item['status'];
                    return $statusValue === $filter;
                });
            }

            if ($this->hostTypeFilter !== 'all') {
                $htFilter = $this->hostTypeFilter;
                $windowData['items'] = array_filter($windowData['items'], function ($item) use ($htFilter) {
                    $htValue = $item['host_type'] instanceof HostType ? $item['host_type']->value : (string) ($item['host_type'] ?? '');
                    return $htValue === $htFilter;
                });
            }
        }

        $detailIp = $this->detailIpId ? IpAddress::with(['assignments.assignedBy', 'reservations.reservedBy'])->find($this->detailIpId) : null;

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

        return view('livewire.ip-addresses.index', [
            'subnets' => $subnets,
            'selectedSubnet' => $selectedSubnet,
            'windowData' => $windowData,
            'detailIp' => $detailIp,
            'classifications' => IpClassification::cases(),
            'hostTypes' => $hostTypesList,
        ]);
    }
}
