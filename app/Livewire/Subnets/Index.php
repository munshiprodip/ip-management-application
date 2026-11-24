<?php

declare(strict_types=1);

namespace App\Livewire\Subnets;

use App\Actions\AddressSpace\CreateSubnetAction;
use App\Actions\AddressSpace\DeleteSubnetAction;
use App\Actions\AddressSpace\UpdateSubnetAction;
use App\Domain\AddressSpace\Enums\SubnetStatus;
use App\Domain\AddressSpace\Enums\SubnetType;
use App\Domain\AddressSpace\ValueObjects\IpPrefix;
use App\Models\Site;
use App\Models\Subnet;
use App\Models\Vlan;
use App\Services\AddressSpace\SubnetHierarchyService;
use App\Services\IPMath\IpCalculatorService;
use App\Services\SiteContext;
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
    public array $expandedSubnetIds = [];

    // Filters
    public string $search = '';
    public string $familyFilter = 'all';
    public string $typeFilter = 'all';
    public ?int $siteFilter = null;

    // Create / Edit modal state
    public bool $showSubnetModal = false;
    public bool $isEditing = false;
    public ?int $selectedSubnetId = null;

    // Form inputs
    public ?int $subnet_site_id = null;
    public ?int $subnet_parent_id = null;
    public ?int $subnet_vlan_id = null;
    public string $subnet_name = '';
    public string $subnet_cidr = '';
    public string $subnet_mask = '';
    public string $subnet_gateway = '';
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

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFamilyFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSiteFilter(): void
    {
        $this->resetPage();
    }

    public function mount(SiteContext $context): void
    {
        $this->siteFilter = $context->getActiveSiteId();
    }

    public function drillDown(?int $parentId): void
    {
        $this->selectedParentId = $parentId;
        $this->resetPage();
    }

    public function toggleExpand(int $subnetId): void
    {
        if (in_array($subnetId, $this->expandedSubnetIds, true)) {
            $this->expandedSubnetIds = array_values(array_filter($this->expandedSubnetIds, fn ($id) => $id !== $subnetId));
        } else {
            $this->expandedSubnetIds[] = $subnetId;
        }
    }

    public function expandAll(): void
    {
        $this->expandedSubnetIds = Subnet::has('children')->pluck('id')->all();
    }

    public function collapseAll(): void
    {
        $this->expandedSubnetIds = [];
    }

    public function openCreateModal(?int $parentId = null): void
    {
        $this->isEditing = false;
        $this->selectedSubnetId = null;
        $this->reset(['subnet_name', 'subnet_cidr', 'subnet_mask', 'subnet_gateway', 'subnet_description', 'subnet_vlan_id', 'subnet_site_id']);

        $this->subnet_parent_id = $parentId ?? $this->selectedParentId;
        $this->subnet_type = ($parentId !== null) ? 'assignable' : 'aggregate';
        $this->subnet_status = 'active';
        $this->subnet_allocation_enabled = true;

        if ($this->subnet_parent_id) {
            $parent = Subnet::find($this->subnet_parent_id);
            $this->subnet_site_id = $parent?->site_id ?? $this->siteFilter;
        } else {
            $this->subnet_site_id = $this->siteFilter ?? app(SiteContext::class)->getActiveSiteId();
        }

        $this->showSubnetModal = true;
    }

    public function closeSubnetModal(): void
    {
        $this->showSubnetModal = false;
        $this->resetValidation();
        $this->reset(['subnet_name', 'subnet_cidr', 'subnet_mask', 'subnet_gateway', 'subnet_description', 'subnet_vlan_id', 'subnet_site_id', 'isEditing', 'selectedSubnetId']);
    }

    public function updatedSubnetCidr(string $value): void
    {
        try {
            $prefix = IpPrefix::parse($value);
            if ($prefix->version() === 4) {
                $maskBits = $prefix->prefixLength();
                $maskInt = ($maskBits === 0) ? 0 : (~0 << (32 - $maskBits)) & 0xFFFFFFFF;
                if (empty($this->subnet_mask)) {
                    $this->subnet_mask = long2ip($maskInt);
                }
            } else {
                if (empty($this->subnet_mask)) {
                    $this->subnet_mask = '/' . $prefix->prefixLength();
                }
            }

            if (empty($this->subnet_gateway)) {
                $this->subnet_gateway = $prefix->firstUsableAddress()->toNormalizedString();
            }
        } catch (\Throwable) {
            // Typing in progress or invalid CIDR
        }
    }

    public function openEditModal(int $id): void
    {
        $subnet = Subnet::findOrFail($id);

        $this->isEditing = true;
        $this->selectedSubnetId = $subnet->id;
        $this->subnet_site_id = $subnet->site_id;
        $this->subnet_parent_id = $subnet->parent_id;
        $this->subnet_vlan_id = $subnet->vlan_id;
        $this->subnet_name = $subnet->name;
        $this->subnet_cidr = $subnet->cidr;
        $this->subnet_mask = (string) ($subnet->subnet_mask ?? $subnet->netmask());
        $this->subnet_gateway = (string) ($subnet->gateway ?? '');
        $this->subnet_type = $subnet->subnet_type->value;
        $this->subnet_status = $subnet->status->value;
        $this->subnet_description = (string) $subnet->description;
        $this->subnet_allocation_enabled = $subnet->allocation_enabled;

        $this->showSubnetModal = true;
    }

    public function saveSubnet(?CreateSubnetAction $createAction = null, ?UpdateSubnetAction $updateAction = null): void
    {
        $createAction ??= app(CreateSubnetAction::class);
        $updateAction ??= app(UpdateSubnetAction::class);

        $this->subnet_parent_id = !empty($this->subnet_parent_id) ? (int) $this->subnet_parent_id : null;
        $this->subnet_vlan_id = !empty($this->subnet_vlan_id) ? (int) $this->subnet_vlan_id : null;
        $this->subnet_site_id = !empty($this->subnet_site_id) ? (int) $this->subnet_site_id : null;

        $this->validate([
            'subnet_parent_id' => ['nullable', 'integer', 'exists:subnets,id'],
            'subnet_name' => ['required', 'string', 'max:128'],
            'subnet_cidr' => ['required', 'string', 'regex:/^([0-9a-fA-F:\.]+)\/([0-9]+)$/'],
            'subnet_mask' => ['nullable', 'string', 'max:64'],
            'subnet_gateway' => ['nullable', 'string', 'max:64'],
            'subnet_type' => ['required', 'string'],
            'subnet_status' => ['required', 'in:planned,active,deprecated,retired'],
            'subnet_vlan_id' => ['nullable', 'integer', 'exists:vlans,id'],
            'subnet_site_id' => ['nullable', 'integer', 'exists:sites,id'],
        ]);

        try {
            if ($this->isEditing) {
                $subnet = Subnet::findOrFail($this->selectedSubnetId);

                $updateAction->execute($subnet, [
                    'site_id' => $this->subnet_site_id,
                    'name' => $this->subnet_name,
                    'parent_id' => $this->subnet_parent_id,
                    'vlan_id' => $this->subnet_vlan_id,
                    'gateway' => $this->subnet_gateway,
                    'subnet_mask' => $this->subnet_mask,
                    'subnet_type' => SubnetType::from($this->subnet_type),
                    'status' => SubnetStatus::from($this->subnet_status),
                    'description' => $this->subnet_description,
                    'allocation_enabled' => $this->subnet_allocation_enabled,
                ]);

                $msg = "Subnet '{$subnet->cidr}' updated successfully.";
                session()->flash('success', $msg);
                $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
            } else {
                $subnet = $createAction->execute([
                    'site_id' => $this->subnet_site_id,
                    'parent_id' => $this->subnet_parent_id,
                    'vlan_id' => $this->subnet_vlan_id,
                    'name' => $this->subnet_name,
                    'cidr' => $this->subnet_cidr,
                    'gateway' => $this->subnet_gateway,
                    'subnet_mask' => $this->subnet_mask,
                    'subnet_type' => SubnetType::from($this->subnet_type),
                    'status' => SubnetStatus::from($this->subnet_status),
                    'description' => $this->subnet_description,
                    'allocation_enabled' => $this->subnet_allocation_enabled,
                ]);

                $msg = "Subnet '{$subnet->cidr}' created successfully.";
                session()->flash('success', $msg);
                $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);

                if ($this->subnet_parent_id && !in_array($this->subnet_parent_id, $this->expandedSubnetIds, true)) {
                    $this->expandedSubnetIds[] = $this->subnet_parent_id;
                }
            }

            $this->showSubnetModal = false;
        } catch (\Illuminate\Validation\ValidationException $ve) {
            foreach ($ve->errors() as $key => $messages) {
                $mappedKey = match($key) {
                    'cidr' => 'subnet_cidr',
                    'parent_id' => 'subnet_parent_id',
                    'name' => 'subnet_name',
                    'subnet_type', 'type' => 'subnet_type',
                    'gateway' => 'subnet_gateway',
                    'subnet_mask' => 'subnet_mask',
                    'vlan_id' => 'subnet_vlan_id',
                    'site_id' => 'subnet_site_id',
                    default => $key,
                };
                foreach ($messages as $msg) {
                    $this->addError($mappedKey, $msg);
                }
            }
            $errMsg = $ve->getMessage();
            $this->addError('general', $errMsg);
            $this->dispatch('notify', ['type' => 'error', 'message' => $errMsg]);
        } catch (\Throwable $e) {
            $this->addError('general', $e->getMessage());
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
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
            if ($suggested->version() === 4) {
                $maskBits = $suggested->prefixLength();
                $maskInt = ($maskBits === 0) ? 0 : (~0 << (32 - $maskBits)) & 0xFFFFFFFF;
                $this->subnet_mask = long2ip($maskInt);
            } else {
                $this->subnet_mask = '/' . $suggested->prefixLength();
            }
            $this->subnet_gateway = $suggested->firstUsableAddress()->toNormalizedString();
        } else {
            $this->addError('subnet_cidr', "No free /{$defaultChildLength} prefix blocks remaining in parent {$parent->cidr}.");
        }
    }

    public function confirmDelete(int $id): void
    {
        $subnet = Subnet::findOrFail($id);
        $this->selectedSubnetId = $subnet->id;
        $this->showDeleteModal = true;
    }

    public function deleteSubnet(?DeleteSubnetAction $action = null): void
    {
        $action ??= app(DeleteSubnetAction::class);
        $subnet = Subnet::findOrFail($this->selectedSubnetId);

        try {
            $action->execute($subnet);
            $this->expandedSubnetIds = array_values(array_filter($this->expandedSubnetIds, fn ($id) => $id !== $this->selectedSubnetId));
            $msg = "Subnet '{$subnet->cidr}' deleted successfully.";
            session()->flash('success', $msg);
            $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
            $this->showDeleteModal = false;
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

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
        $vlans = Vlan::orderBy('vlan_id')->get();
        $sites = Site::where('status', 'active')->orderBy('name')->get();

        $query = Subnet::with(['vlan', 'site'])->withCount('children');

        if ($this->siteFilter) {
            $query->where('site_id', $this->siteFilter);
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

        $childrenByParent = empty($this->expandedSubnetIds)
            ? collect()
            : Subnet::whereIn('parent_id', $this->expandedSubnetIds)
                ->with('vlan')
                ->withCount('children')
                ->orderBy('start_hex')
                ->get()
                ->groupBy('parent_id');

        $selectedParent = $this->selectedParentId ? Subnet::find($this->selectedParentId) : null;
        $parentCandidates = Subnet::where('subnet_type', SubnetType::AGGREGATE)
            ->when($this->isEditing && $this->selectedSubnetId, function ($q) {
                $q->where('id', '!=', $this->selectedSubnetId);
            })
            ->orderBy('start_hex')
            ->get();

        return view('livewire.subnets.index', [
            'subnets' => $subnets,
            'childrenByParent' => $childrenByParent,
            'vlans' => $vlans,
            'sites' => $sites,
            'selectedParent' => $selectedParent,
            'parentCandidates' => $parentCandidates,
            'subnetTypes' => SubnetType::cases(),
        ]);
    }
}
