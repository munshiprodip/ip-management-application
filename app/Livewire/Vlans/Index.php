<?php

declare(strict_types=1);

namespace App\Livewire\Vlans;

use App\Actions\Layer2\CreateL2DomainAction;
use App\Actions\Layer2\CreateVlanAction;
use App\Actions\Layer2\DeleteL2DomainAction;
use App\Actions\Layer2\DeleteVlanAction;
use App\Actions\Layer2\UpdateL2DomainAction;
use App\Actions\Layer2\UpdateVlanAction;
use App\Domain\Layer2\Enums\VlanSecurityZone;
use App\Domain\Layer2\Enums\VlanStatus;
use App\Models\L2Domain;
use App\Models\Site;
use App\Models\Vlan;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('VLANs & Layer 2 Broadcast Domains - KYAMCH IPAM')]
class Index extends Component
{
    use WithPagination;

    public string $activeTab = 'vlans'; // 'vlans' or 'l2_domains'

    // Search and filters
    public string $search = '';
    public string $zoneFilter = 'all';
    public string $domainFilter = 'all';
    public string $statusFilter = 'all';

    // VLAN Modal state
    public bool $showVlanModal = false;
    public bool $isEditingVlan = false;
    public ?int $selectedVlanId = null;

    // VLAN Form inputs
    public ?int $vlan_l2_domain_id = null;
    public ?int $vlan_number = null;
    public string $vlan_name = '';
    public string $vlan_description = '';
    public string $vlan_security_zone = 'internal';
    public string $vlan_status = 'active';

    // L2 Domain Modal state
    public bool $showDomainModal = false;
    public bool $isEditingDomain = false;
    public ?int $selectedDomainId = null;

    // L2 Domain Form inputs
    public ?int $domain_site_id = null;
    public string $domain_code = '';
    public string $domain_name = '';
    public string $domain_description = '';
    public string $domain_status = 'active';

    // Delete confirmation state
    public bool $showDeleteModal = false;
    public string $deleteTargetType = 'vlan'; // 'vlan' or 'l2_domain'
    public ?int $deleteTargetId = null;

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingZoneFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDomainFilter(): void
    {
        $this->resetPage();
    }

    // --- VLAN Methods ---

    public function openCreateVlanModal(): void
    {
        Gate::authorize('create', Vlan::class);

        $this->isEditingVlan = false;
        $this->selectedVlanId = null;
        $this->reset(['vlan_number', 'vlan_name', 'vlan_description']);
        $this->vlan_security_zone = 'internal';
        $this->vlan_status = 'active';

        // Pre-select first available domain if present
        $this->vlan_l2_domain_id = L2Domain::first()?->id;

        $this->showVlanModal = true;
    }

    public function openEditVlanModal(int $id): void
    {
        $vlan = Vlan::findOrFail($id);
        Gate::authorize('update', $vlan);

        $this->isEditingVlan = true;
        $this->selectedVlanId = $vlan->id;
        $this->vlan_l2_domain_id = $vlan->l2_domain_id;
        $this->vlan_number = $vlan->vlan_id;
        $this->vlan_name = $vlan->name;
        $this->vlan_description = (string) $vlan->description;
        $this->vlan_security_zone = $vlan->security_zone->value;
        $this->vlan_status = $vlan->status->value;

        $this->showVlanModal = true;
    }

    public function saveVlan(CreateVlanAction $createAction, UpdateVlanAction $updateAction): void
    {
        $this->validate([
            'vlan_l2_domain_id' => ['required', 'integer', 'exists:l2_domains,id'],
            'vlan_number' => ['required', 'integer', 'between:1,4094'],
            'vlan_name' => ['required', 'string', 'max:128'],
            'vlan_description' => ['nullable', 'string', 'max:500'],
            'vlan_security_zone' => ['required', 'string'],
            'vlan_status' => ['required', 'in:active,reserved,deprecated'],
        ]);

        try {
            if ($this->isEditingVlan) {
                $vlan = Vlan::findOrFail($this->selectedVlanId);
                Gate::authorize('update', $vlan);

                $updateAction->execute($vlan, [
                    'vlan_id' => (int) $this->vlan_number,
                    'name' => $this->vlan_name,
                    'description' => $this->vlan_description,
                    'security_zone' => VlanSecurityZone::from($this->vlan_security_zone),
                    'status' => VlanStatus::from($this->vlan_status),
                ]);

                session()->flash('success', "VLAN {$this->vlan_number} updated successfully.");
            } else {
                Gate::authorize('create', Vlan::class);

                $createAction->execute([
                    'l2_domain_id' => (int) $this->vlan_l2_domain_id,
                    'vlan_id' => (int) $this->vlan_number,
                    'name' => $this->vlan_name,
                    'description' => $this->vlan_description,
                    'security_zone' => VlanSecurityZone::from($this->vlan_security_zone),
                    'status' => VlanStatus::from($this->vlan_status),
                ]);

                session()->flash('success', "VLAN {$this->vlan_number} registered successfully.");
            }

            $this->showVlanModal = false;
        } catch (\Exception $e) {
            $this->addError('vlan_number', $e->getMessage());
        }
    }

    // --- L2 Domain Methods ---

    public function openCreateDomainModal(): void
    {
        Gate::authorize('create', L2Domain::class);

        $this->isEditingDomain = false;
        $this->selectedDomainId = null;
        $this->reset(['domain_code', 'domain_name', 'domain_description']);
        $this->domain_site_id = Site::first()?->id;
        $this->domain_status = 'active';

        $this->showDomainModal = true;
    }

    public function openEditDomainModal(int $id): void
    {
        $domain = L2Domain::findOrFail($id);
        Gate::authorize('update', $domain);

        $this->isEditingDomain = true;
        $this->selectedDomainId = $domain->id;
        $this->domain_site_id = $domain->site_id;
        $this->domain_code = $domain->code;
        $this->domain_name = $domain->name;
        $this->domain_description = (string) $domain->description;
        $this->domain_status = $domain->status;

        $this->showDomainModal = true;
    }

    public function saveDomain(CreateL2DomainAction $createAction, UpdateL2DomainAction $updateAction): void
    {
        $this->validate([
            'domain_site_id' => ['required', 'integer', 'exists:sites,id'],
            'domain_code' => ['required', 'string', 'max:64', 'regex:/^[A-Z0-9_\-]+$/i'],
            'domain_name' => ['required', 'string', 'max:128'],
            'domain_description' => ['nullable', 'string', 'max:500'],
            'domain_status' => ['required', 'in:active,retired'],
        ]);

        try {
            if ($this->isEditingDomain) {
                $domain = L2Domain::findOrFail($this->selectedDomainId);
                Gate::authorize('update', $domain);

                $updateAction->execute($domain, [
                    'code' => $this->domain_code,
                    'name' => $this->domain_name,
                    'description' => $this->domain_description,
                    'status' => $this->domain_status,
                ]);

                session()->flash('success', "Layer 2 domain '{$this->domain_code}' updated successfully.");
            } else {
                Gate::authorize('create', L2Domain::class);

                $createAction->execute([
                    'site_id' => (int) $this->domain_site_id,
                    'code' => $this->domain_code,
                    'name' => $this->domain_name,
                    'description' => $this->domain_description,
                    'status' => $this->domain_status,
                ]);

                session()->flash('success', "Layer 2 domain '{$this->domain_code}' created successfully.");
            }

            $this->showDomainModal = false;
        } catch (\Exception $e) {
            $this->addError('domain_code', $e->getMessage());
        }
    }

    // --- Deletion Methods ---

    public function confirmDelete(string $type, int $id): void
    {
        $this->deleteTargetType = $type;
        $this->deleteTargetId = $id;

        if ($type === 'vlan') {
            Gate::authorize('delete', Vlan::findOrFail($id));
        } else {
            Gate::authorize('delete', L2Domain::findOrFail($id));
        }

        $this->showDeleteModal = true;
    }

    public function executeDelete(DeleteVlanAction $deleteVlan, DeleteL2DomainAction $deleteDomain): void
    {
        try {
            if ($this->deleteTargetType === 'vlan') {
                $vlan = Vlan::findOrFail($this->deleteTargetId);
                Gate::authorize('delete', $vlan);
                $deleteVlan->execute($vlan);
                session()->flash('success', "VLAN {$vlan->vlan_id} deleted successfully.");
            } else {
                $domain = L2Domain::findOrFail($this->deleteTargetId);
                Gate::authorize('delete', $domain);
                $deleteDomain->execute($domain);
                session()->flash('success', "L2 Domain '{$domain->code}' deleted successfully.");
            }
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }

        $this->showDeleteModal = false;
    }

    public function render()
    {
        $sites = Site::orderBy('name')->get();
        $l2DomainsList = L2Domain::with('site')->orderBy('code')->get();

        if ($this->activeTab === 'vlans') {
            $vlanQuery = Vlan::with('l2Domain.site');

            if (!empty($this->search)) {
                $vlanQuery->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%");
                    if (is_numeric($this->search)) {
                        $q->orWhere('vlan_id', (int) $this->search);
                    }
                });
            }

            if ($this->domainFilter !== 'all') {
                $vlanQuery->where('l2_domain_id', (int) $this->domainFilter);
            }

            if ($this->zoneFilter !== 'all') {
                $vlanQuery->where('security_zone', $this->zoneFilter);
            }

            if ($this->statusFilter !== 'all') {
                $vlanQuery->where('status', $this->statusFilter);
            }

            $items = $vlanQuery->orderBy('l2_domain_id')->orderBy('vlan_id')->paginate(12);
        } else {
            $domainQuery = L2Domain::with(['site'])->withCount('vlans');

            if (!empty($this->search)) {
                $domainQuery->where(function ($q) {
                    $q->where('code', 'like', "%{$this->search}%")
                        ->orWhere('name', 'like', "%{$this->search}%");
                });
            }

            if ($this->statusFilter !== 'all') {
                $domainQuery->where('status', $this->statusFilter);
            }

            $items = $domainQuery->orderBy('code')->paginate(10);
        }

        return view('livewire.vlans.index', [
            'items' => $items,
            'sites' => $sites,
            'l2DomainsList' => $l2DomainsList,
            'securityZones' => VlanSecurityZone::cases(),
        ]);
    }
}
