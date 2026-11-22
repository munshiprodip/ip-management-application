<?php

declare(strict_types=1);

namespace App\Livewire\IPAddresses;

use App\Actions\AddressSpace\AllocateIpAction;
use App\Actions\AddressSpace\ReleaseIpAction;
use App\Actions\AddressSpace\ReserveIpAction;
use App\Actions\AddressSpace\TransferIpAction;
use App\Domain\AddressSpace\Enums\IpAllocationStatus;
use App\Domain\AddressSpace\Enums\IpClassification;
use App\Domain\AddressSpace\Enums\IpConnectivityStatus;
use App\Domain\AddressSpace\ValueObjects\IpAddress as IpAddressVO;
use App\Models\Device;
use App\Models\DeviceInterface;
use App\Models\IpAddress;
use App\Models\Subnet;
use App\Models\Vrf;
use App\Services\AddressSpace\CandidateWindowGenerator;
use App\Services\AddressSpace\IpAllocationService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('IP Explorer & Address Allocation - KYAMCH IPAM')]
class Index extends Component
{
    // Context selectors
    public ?int $selectedVrfId = null;
    public ?int $selectedSubnetId = null;

    // Window navigation
    public int $currentPage = 1;
    public int $perPage = 50;
    public string $statusFilter = 'all'; // 'all', 'available', 'assigned', 'reserved', 'quarantined'
    public string $search = '';

    // Allocate Modal
    public bool $showAllocateModal = false;
    public string $alloc_ip = '';
    public bool $alloc_auto_free = false;
    public ?int $alloc_device_id = null;
    public ?int $alloc_interface_id = null;
    public string $alloc_classification = 'static_host';
    public string $alloc_hostname = '';
    public string $alloc_description = '';

    // Release Modal
    public bool $showReleaseModal = false;
    public ?int $releaseIpId = null;
    public string $release_reason = '';
    public int $release_quarantine_days = 3;

    // Transfer Modal
    public bool $showTransferModal = false;
    public ?int $transferIpId = null;
    public ?int $transfer_device_id = null;
    public ?int $transfer_interface_id = null;
    public string $transfer_reason = '';

    // Reserve Modal
    public bool $showReserveModal = false;
    public string $reserve_ip = '';
    public string $reserve_reason = '';
    public ?string $reserve_expires_at = null;
    public ?int $reserve_device_id = null;

    // Details Slide-over
    public bool $showDetailsDrawer = false;
    public ?int $detailIpId = null;

    public function mount(): void
    {
        $defaultVrf = Vrf::where('is_default', true)->first() ?? Vrf::first();
        if ($defaultVrf) {
            $this->selectedVrfId = $defaultVrf->id;
            // Default to first assignable leaf subnet in this VRF
            $firstSubnet = Subnet::where('vrf_id', $this->selectedVrfId)
                ->where('subnet_type', 'assignable')
                ->where('allocation_enabled', true)
                ->first();
            $this->selectedSubnetId = $firstSubnet?->id;
        }
    }

    public function updatedSelectedVrfId(): void
    {
        $firstSubnet = Subnet::where('vrf_id', $this->selectedVrfId)
            ->where('subnet_type', 'assignable')
            ->where('allocation_enabled', true)
            ->first();
        $this->selectedSubnetId = $firstSubnet?->id;
        $this->currentPage = 1;
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

    public function updatedAllocDeviceId(): void
    {
        $this->alloc_interface_id = null;
    }

    public function updatedTransferDeviceId(): void
    {
        $this->transfer_interface_id = null;
    }

    // --- Allocate Actions ---

    public function openAllocateModal(?string $ip = null): void
    {
        Gate::authorize('allocate', IpAddress::class);

        $this->alloc_ip = $ip ?? '';
        $this->alloc_auto_free = empty($ip);
        $this->alloc_device_id = null;
        $this->alloc_interface_id = null;
        $this->alloc_classification = 'static_host';
        $this->alloc_hostname = '';
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
        Gate::authorize('allocate', IpAddress::class);

        $this->validate([
            'alloc_ip' => 'required|string',
            'alloc_classification' => 'required|string',
        ]);

        try {
            $action->execute([
                'subnet_id' => $this->selectedSubnetId,
                'ip_address' => $this->alloc_ip,
                'interface_id' => $this->alloc_interface_id,
                'classification' => IpClassification::from($this->alloc_classification),
                'hostname' => !empty($this->alloc_hostname) ? $this->alloc_hostname : null,
                'description' => !empty($this->alloc_description) ? $this->alloc_description : null,
            ], auth()->user());

            session()->flash('success', "IP address {$this->alloc_ip} assigned successfully.");
            $this->showAllocateModal = false;
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    // --- Release Actions ---

    public function openReleaseModal(int $ipId): void
    {
        $ip = IpAddress::findOrFail($ipId);
        Gate::authorize('release', $ip);

        $this->releaseIpId = $ip->id;
        $this->release_reason = 'Decommissioned or retired interface binding.';
        $this->release_quarantine_days = 3;
        $this->showReleaseModal = true;
    }

    public function confirmRelease(ReleaseIpAction $action): void
    {
        $ip = IpAddress::findOrFail($this->releaseIpId);
        Gate::authorize('release', $ip);

        try {
            $action->execute($ip, $this->release_reason, $this->release_quarantine_days, auth()->user());
            session()->flash('success', "IP address {$ip->address_text} released and quarantined.");
            $this->showReleaseModal = false;
            $this->showDetailsDrawer = false;
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    // --- Transfer Actions ---

    public function openTransferModal(int $ipId): void
    {
        $ip = IpAddress::findOrFail($ipId);
        Gate::authorize('transfer', $ip);

        $this->transferIpId = $ip->id;
        $this->transfer_device_id = null;
        $this->transfer_interface_id = null;
        $this->transfer_reason = 'Reassigned to new host hardware interface.';
        $this->showTransferModal = true;
    }

    public function confirmTransfer(TransferIpAction $action): void
    {
        $ip = IpAddress::findOrFail($this->transferIpId);
        Gate::authorize('transfer', $ip);

        $this->validate([
            'transfer_interface_id' => 'required|exists:device_interfaces,id',
            'transfer_reason' => 'required|string|max:255',
        ]);

        try {
            $action->execute($ip, $this->transfer_interface_id, $this->transfer_reason, auth()->user());
            session()->flash('success', "IP address {$ip->address_text} transferred to target interface.");
            $this->showTransferModal = false;
            $this->showDetailsDrawer = false;
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    // --- Reserve Actions ---

    public function openReserveModal(string $ip): void
    {
        Gate::authorize('reserve', IpAddress::class);

        $this->reserve_ip = $ip;
        $this->reserve_reason = 'Future medical device deployment reservation.';
        $this->reserve_expires_at = now()->addDays(30)->toDateString();
        $this->reserve_device_id = null;
        $this->showReserveModal = true;
    }

    public function confirmReservation(ReserveIpAction $action): void
    {
        Gate::authorize('reserve', IpAddress::class);

        $this->validate([
            'reserve_ip' => 'required|string',
            'reserve_reason' => 'required|string|max:255',
        ]);

        try {
            $action->execute([
                'subnet_id' => $this->selectedSubnetId,
                'ip_address' => $this->reserve_ip,
                'reason' => $this->reserve_reason,
                'device_id' => $this->reserve_device_id,
                'expires_at' => $this->reserve_expires_at,
            ], auth()->user());

            session()->flash('success', "IP address {$this->reserve_ip} reserved.");
            $this->showReserveModal = false;
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    // --- Details Drawer ---

    public function viewDetails(int $ipId): void
    {
        $this->detailIpId = $ipId;
        $this->showDetailsDrawer = true;
    }

    public function render()
    {
        $vrfs = Vrf::orderBy('name')->get();
        $subnets = Subnet::where('vrf_id', $this->selectedVrfId)
            ->where('subnet_type', 'assignable')
            ->orderBy('cidr')
            ->get();

        $selectedSubnet = $this->selectedSubnetId ? Subnet::with(['vrf', 'site', 'pools', 'exclusions'])->find($this->selectedSubnetId) : null;

        $windowData = ['items' => [], 'total' => 0, 'per_page' => $this->perPage, 'current_page' => 1, 'last_page' => 1];

        if ($selectedSubnet) {
            $generator = app(CandidateWindowGenerator::class);
            $windowData = $generator->generateWindow($selectedSubnet, $this->currentPage, $this->perPage);

            // Filter items in view if status filter is active
            if ($this->statusFilter !== 'all') {
                $filter = $this->statusFilter;
                $windowData['items'] = array_filter($windowData['items'], function ($item) use ($filter) {
                    $statusValue = is_object($item['allocation_status']) ? $item['allocation_status']->value : $item['allocation_status'];
                    return $statusValue === $filter;
                });
            }
        }

        $detailIp = $this->detailIpId ? IpAddress::with(['currentAssignment.interface.device', 'assignments.interface.device', 'assignments.assignedBy', 'reservations.reservedForDevice'])->find($this->detailIpId) : null;

        $devices = Device::orderBy('name')->get();
        $interfaces = $this->alloc_device_id ? DeviceInterface::where('device_id', $this->alloc_device_id)->orderBy('name')->get() : collect();
        $transferInterfaces = $this->transfer_device_id ? DeviceInterface::where('device_id', $this->transfer_device_id)->orderBy('name')->get() : collect();

        return view('livewire.ip-addresses.index', [
            'vrfs' => $vrfs,
            'subnets' => $subnets,
            'selectedSubnet' => $selectedSubnet,
            'windowData' => $windowData,
            'detailIp' => $detailIp,
            'devices' => $devices,
            'interfaces' => $interfaces,
            'transferInterfaces' => $transferInterfaces,
        ]);
    }
}
