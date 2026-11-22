<?php

declare(strict_types=1);

namespace App\Livewire\Subnets;

use App\Actions\AddressSpace\CreateSubnetAction;
use App\Actions\AddressSpace\DeleteSubnetAction;
use App\Actions\AddressSpace\UpdateSubnetAction;
use App\Domain\AddressSpace\Enums\SubnetStatus;
use App\Domain\AddressSpace\Enums\SubnetType;
use App\Domain\AddressSpace\ValueObjects\IpPrefix;
use App\Models\Department;
use App\Models\Site;
use App\Models\Subnet;
use App\Models\Vlan;
use App\Models\Vrf;
use App\Services\AddressSpace\SubnetHierarchyService;
use App\Services\IPMath\IpCalculatorService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Subnets & CIDR Hierarchy - KYAMCH IPAM')]
class Index extends Component
{
    use WithPagination;

    // View state
    public string $viewMode = 'hierarchy'; // 'hierarchy' or 'flat'
    public ?int $selectedParentId = null;

    // Filters
    public string $search = '';
    public string $vrfFilter = 'all';
    public string $familyFilter = 'all';
    public string $typeFilter = 'all';

    // Create / Edit modal state
    public bool $showSubnetModal = false;
    public bool $isEditing = false;
    public ?int $selectedSubnetId = null;

    // Form inputs
    public ?int $subnet_vrf_id = null;
    public ?int $subnet_parent_id = null;
    public ?int $subnet_site_id = null;
    public ?int $subnet_department_id = null;
    public ?int $subnet_vlan_id = null;
    public string $subnet_name = '';
    public string $subnet_cidr = '';
    public string $subnet_type = 'assignable';
    public string $subnet_status = 'active';
    public string $subnet_description = '';
    public bool $subnet_allocation_enabled = true;

    // Calculator tool state
    public bool $showCalculatorModal = false;
    public string $calcInputCidr = '10.10.0.0/24';
    public ?array $calcResults = null;
    public ?string $calcError = null;

    // Split preview state
    public bool $showSplitModal = false;
    public ?int $splitSubnetId = null;
    public int $splitTargetPrefix = 24;
    public array $splitPreviewList = [];
    public ?string $splitError = null;

    // Delete confirmation
    public bool $showDeleteModal = false;

    public function mount(): void
    {
        // Default to default VRF if present
        $defaultVrf = Vrf::where('is_default', true)->first();
        if ($defaultVrf) {
            $this->vrfFilter = (string) $defaultVrf->id;
            $this->subnet_vrf_id = $defaultVrf->id;
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingVrfFilter(): void
    {
        $this->resetPage();
        $this->selectedParentId = null;
    }

    public function drillDown(?int $parentId): void
    {
        $this->selectedParentId = $parentId;
        $this->resetPage();
    }

    // --- Subnet CRUD ---

    public function openCreateModal(?int $parentId = null): void
    {
        Gate::authorize('create', Subnet::class);

        $this->isEditing = false;
        $this->selectedSubnetId = null;
        $this->reset(['subnet_name', 'subnet_cidr', 'subnet_description', 'subnet_vlan_id', 'subnet_department_id', 'subnet_site_id']);

        if ($this->vrfFilter !== 'all') {
            $this->subnet_vrf_id = (int) $this->vrfFilter;
        } else {
            $this->subnet_vrf_id = Vrf::first()?->id;
        }

        $this->subnet_parent_id = $parentId ?? $this->selectedParentId;
        $this->subnet_type = ($parentId !== null) ? 'assignable' : 'aggregate';
        $this->subnet_status = 'active';
        $this->subnet_allocation_enabled = true;

        $this->showSubnetModal = true;
    }

    public function openEditModal(int $id): void
    {
        $subnet = Subnet::findOrFail($id);
        Gate::authorize('update', $subnet);

        $this->isEditing = true;
        $this->selectedSubnetId = $subnet->id;
        $this->subnet_vrf_id = $subnet->vrf_id;
        $this->subnet_parent_id = $subnet->parent_id;
        $this->subnet_site_id = $subnet->site_id;
        $this->subnet_department_id = $subnet->department_id;
        $this->subnet_vlan_id = $subnet->vlan_id;
        $this->subnet_name = $subnet->name;
        $this->subnet_cidr = $subnet->cidr;
        $this->subnet_type = $subnet->subnet_type->value;
        $this->subnet_status = $subnet->status->value;
        $this->subnet_description = (string) $subnet->description;
        $this->subnet_allocation_enabled = $subnet->allocation_enabled;

        $this->showSubnetModal = true;
    }

    public function saveSubnet(CreateSubnetAction $createAction, UpdateSubnetAction $updateAction): void
    {
        $this->validate([
            'subnet_vrf_id' => ['required', 'integer', 'exists:vrfs,id'],
            'subnet_parent_id' => ['nullable', 'integer', 'exists:subnets,id'],
            'subnet_name' => ['required', 'string', 'max:128'],
            'subnet_cidr' => ['required', 'string', 'regex:/^([0-9a-fA-F:\.]+)\/([0-9]+)$/'],
            'subnet_type' => ['required', 'string'],
            'subnet_status' => ['required', 'in:planned,active,deprecated,retired'],
            'subnet_site_id' => ['nullable', 'integer', 'exists:sites,id'],
            'subnet_department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'subnet_vlan_id' => ['nullable', 'integer', 'exists:vlans,id'],
        ]);

        try {
            if ($this->isEditing) {
                $subnet = Subnet::findOrFail($this->selectedSubnetId);
                Gate::authorize('update', $subnet);

                $updateAction->execute($subnet, [
                    'name' => $this->subnet_name,
                    'parent_id' => $this->subnet_parent_id,
                    'site_id' => $this->subnet_site_id,
                    'department_id' => $this->subnet_department_id,
                    'vlan_id' => $this->subnet_vlan_id,
                    'subnet_type' => SubnetType::from($this->subnet_type),
                    'status' => SubnetStatus::from($this->subnet_status),
                    'description' => $this->subnet_description,
                    'allocation_enabled' => $this->subnet_allocation_enabled,
                ]);

                session()->flash('success', "Subnet '{$subnet->cidr}' updated successfully.");
            } else {
                Gate::authorize('create', Subnet::class);

                $createAction->execute([
                    'vrf_id' => (int) $this->subnet_vrf_id,
                    'parent_id' => $this->subnet_parent_id,
                    'site_id' => $this->subnet_site_id,
                    'department_id' => $this->subnet_department_id,
                    'vlan_id' => $this->subnet_vlan_id,
                    'name' => $this->subnet_name,
                    'cidr' => $this->subnet_cidr,
                    'subnet_type' => SubnetType::from($this->subnet_type),
                    'status' => SubnetStatus::from($this->subnet_status),
                    'description' => $this->subnet_description,
                    'allocation_enabled' => $this->subnet_allocation_enabled,
                ]);

                session()->flash('success', "Subnet created successfully.");
            }

            $this->showSubnetModal = false;
        } catch (\Exception $e) {
            $this->addError('subnet_cidr', $e->getMessage());
        }
    }

    public function suggestNextChild(SubnetHierarchyService $service): void
    {
        if (empty($this->subnet_parent_id)) {
            return;
        }

        $parent = Subnet::find($this->subnet_parent_id);
        if (!$parent) {
            return;
        }

        $defaultChildLength = ($parent->ip_version === 4)
            ? min(32, $parent->prefix_length + 8)
            : min(128, $parent->prefix_length + 16);

        $suggested = $service->findFreeChildPrefix($parent, $defaultChildLength);
        if ($suggested) {
            $this->subnet_cidr = $suggested->cidr();
        } else {
            $this->addError('subnet_cidr', "No free /{$defaultChildLength} prefix blocks remaining in parent {$parent->cidr}.");
        }
    }

    public function confirmDelete(int $id): void
    {
        $subnet = Subnet::findOrFail($id);
        Gate::authorize('delete', $subnet);

        $this->selectedSubnetId = $subnet->id;
        $this->showDeleteModal = true;
    }

    public function deleteSubnet(DeleteSubnetAction $action): void
    {
        $subnet = Subnet::findOrFail($this->selectedSubnetId);
        Gate::authorize('delete', $subnet);

        try {
            $action->execute($subnet);
            session()->flash('success', "Subnet '{$subnet->cidr}' deleted successfully.");
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }

        $this->showDeleteModal = false;
    }

    // --- Calculator Tool ---

    public function openCalculator(?string $cidr = null): void
    {
        if ($cidr) {
            $this->calcInputCidr = $cidr;
        }
        $this->runCalculation();
        $this->showCalculatorModal = true;
    }

    public function runCalculation(): void
    {
        $this->calcError = null;
        try {
            $service = app(IpCalculatorService::class);
            $this->calcResults = $service->calculate($this->calcInputCidr);
        } catch (\Exception $e) {
            $this->calcError = $e->getMessage();
            $this->calcResults = null;
        }
    }

    // --- Split Preview Tool ---

    public function openSplitModal(int $id): void
    {
        $subnet = Subnet::findOrFail($id);
        $this->splitSubnetId = $subnet->id;
        $this->splitTargetPrefix = min($subnet->ip_version === 4 ? 32 : 128, $subnet->prefix_length + 2);
        $this->generateSplitPreview();
        $this->showSplitModal = true;
    }

    public function generateSplitPreview(): void
    {
        $this->splitError = null;
        $subnet = Subnet::find($this->splitSubnetId);
        if (!$subnet) {
            return;
        }

        try {
            $service = app(IpCalculatorService::class);
            $this->splitPreviewList = $service->previewSplit($subnet->cidr, $this->splitTargetPrefix);
        } catch (\Exception $e) {
            $this->splitError = $e->getMessage();
            $this->splitPreviewList = [];
        }
    }

    public function render()
    {
        $vrfs = Vrf::orderBy('name')->get();
        $sites = Site::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        $vlans = Vlan::orderBy('vlan_id')->get();

        $query = Subnet::with(['vrf', 'site', 'vlan', 'department'])->withCount('children');

        if ($this->vrfFilter !== 'all') {
            $query->where('vrf_id', (int) $this->vrfFilter);
        }

        if ($this->familyFilter !== 'all') {
            $query->where('ip_version', (int) $this->familyFilter);
        }

        if ($this->typeFilter !== 'all') {
            $query->where('subnet_type', $this->typeFilter);
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('cidr', 'like', "%{$this->search}%")
                    ->orWhere('name', 'like', "%{$this->search}%");
            });
        }

        if ($this->viewMode === 'hierarchy' && empty($this->search)) {
            if ($this->selectedParentId !== null) {
                $query->where('parent_id', $this->selectedParentId);
            } else {
                $query->whereNull('parent_id');
            }
        }

        $subnets = $query->orderBy('start_hex')->paginate(12);

        $selectedParent = $this->selectedParentId ? Subnet::find($this->selectedParentId) : null;
        $parentCandidates = Subnet::when($this->subnet_vrf_id, fn($q) => $q->where('vrf_id', $this->subnet_vrf_id))
            ->where('subnet_type', SubnetType::AGGREGATE)
            ->get();

        return view('livewire.subnets.index', [
            'subnets' => $subnets,
            'vrfs' => $vrfs,
            'sites' => $sites,
            'departments' => $departments,
            'vlans' => $vlans,
            'selectedParent' => $selectedParent,
            'parentCandidates' => $parentCandidates,
            'subnetTypes' => SubnetType::cases(),
        ]);
    }
}
