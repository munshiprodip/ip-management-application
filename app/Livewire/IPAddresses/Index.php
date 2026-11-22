<?php

declare(strict_types=1);

namespace App\Livewire\IPAddresses;

use App\Actions\AddressSpace\AllocateIpAction;
use App\Actions\AddressSpace\ReleaseIpAction;
use App\Actions\AddressSpace\ReserveIpAction;
use App\Domain\AddressSpace\Enums\IpAllocationStatus;
use App\Domain\AddressSpace\Enums\IpClassification;
use App\Domain\AddressSpace\ValueObjects\IpAddress as IpAddressVO;
use App\Models\IpAddress;
use App\Models\Subnet;
use App\Services\AddressSpace\CandidateWindowGenerator;
use App\Services\AddressSpace\IpAllocationService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('IP Explorer & Address Allocation - KYAMCH IPAM')]
class Index extends Component
{
    public ?int $selectedSubnetId = null;

    // Window navigation
    public int $currentPage = 1;
    public int $perPage = 50;
    public string $statusFilter = 'all'; // 'all', 'unassigned', 'assigned', 'reserved', 'quarantined'
    public string $search = '';

    // Allocate Modal
    public bool $showAllocateModal = false;
    public string $alloc_ip = '';
    public bool $alloc_auto_free = false;
    public string $alloc_classification = 'static_host';
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

    public function mount(): void
    {
        $firstSubnet = Subnet::where('subnet_type', 'assignable')
            ->where('allocation_enabled', true)
            ->first();
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
        ]);

        try {
            $action->execute([
                'subnet_id' => $this->selectedSubnetId,
                'ip_address' => $this->alloc_ip,
                'classification' => IpClassification::from($this->alloc_classification),
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

    public function render()
    {
        $subnets = Subnet::where('subnet_type', 'assignable')
            ->orderBy('cidr')
            ->get();

        $selectedSubnet = $this->selectedSubnetId ? Subnet::with(['vlan', 'pools', 'exclusions'])->find($this->selectedSubnetId) : null;

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
        }

        $detailIp = $this->detailIpId ? IpAddress::with(['assignments.assignedBy', 'reservations.reservedBy'])->find($this->detailIpId) : null;

        return view('livewire.ip-addresses.index', [
            'subnets' => $subnets,
            'selectedSubnet' => $selectedSubnet,
            'windowData' => $windowData,
            'detailIp' => $detailIp,
            'classifications' => IpClassification::cases(),
        ]);
    }
}
