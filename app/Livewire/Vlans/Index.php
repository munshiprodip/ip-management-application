<?php

declare(strict_types=1);

namespace App\Livewire\Vlans;

use App\Actions\Layer2\CreateVlanAction;
use App\Actions\Layer2\DeleteVlanAction;
use App\Actions\Layer2\UpdateVlanAction;
use App\Domain\Layer2\Enums\VlanSecurityZone;
use App\Domain\Layer2\Enums\VlanStatus;
use App\Models\Site;
use App\Models\Vlan;
use App\Services\SiteContext;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('VLAN Management - KYAMCH IPAM')]
class Index extends Component
{
    use WithPagination;

    // Search and filters
    public string $search = '';
    public string $zoneFilter = 'all';
    public string $statusFilter = 'all';
    public ?int $siteFilter = null;

    // VLAN Modal state
    public bool $showVlanModal = false;
    public bool $isEditingVlan = false;
    public ?int $selectedVlanId = null;

    // VLAN Form inputs
    public ?int $vlan_site_id = null;
    public ?int $vlan_number = null;
    public string $vlan_name = '';
    public string $vlan_description = '';
    public string $vlan_security_zone = 'internal';
    public string $vlan_status = 'active';
    public bool $vlan_is_restricted = false;

    // Delete confirmation state
    public bool $showDeleteModal = false;
    public ?int $deleteVlanId = null;
    public ?string $deleteError = null;

    public function closeVlanModal(): void
    {
        $this->showVlanModal = false;
        $this->resetValidation();
        $this->reset(['vlan_number', 'vlan_name', 'vlan_description', 'vlan_site_id', 'isEditingVlan', 'selectedVlanId']);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingZoneFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
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

    public function openCreateVlanModal(): void
    {
        $this->isEditingVlan = false;
        $this->selectedVlanId = null;
        $this->reset(['vlan_number', 'vlan_name', 'vlan_description', 'vlan_site_id']);
        $this->vlan_site_id = $this->siteFilter ?? app(SiteContext::class)->getActiveSiteId();
        $this->vlan_security_zone = 'internal';
        $this->vlan_status = 'active';
        $this->vlan_is_restricted = false;
        $this->resetValidation();

        $this->showVlanModal = true;
    }

    public function openEditVlanModal(int $id): void
    {
        $vlan = Vlan::findOrFail($id);

        $this->isEditingVlan = true;
        $this->selectedVlanId = $vlan->id;
        $this->vlan_site_id = $vlan->site_id;
        $this->vlan_number = $vlan->vlan_id;
        $this->vlan_name = $vlan->name;
        $this->vlan_description = (string) $vlan->description;
        $this->vlan_security_zone = $vlan->security_zone instanceof VlanSecurityZone ? $vlan->security_zone->value : (string) $vlan->security_zone;
        $this->vlan_status = $vlan->status instanceof VlanStatus ? $vlan->status->value : (string) $vlan->status;
        $this->vlan_is_restricted = (bool) $vlan->is_restricted;
        $this->resetValidation();

        $this->showVlanModal = true;
    }

    public function saveVlan(?CreateVlanAction $createAction = null, ?UpdateVlanAction $updateAction = null): void
    {
        $createAction ??= app(CreateVlanAction::class);
        $updateAction ??= app(UpdateVlanAction::class);

        $this->vlan_site_id = !empty($this->vlan_site_id) ? (int) $this->vlan_site_id : null;

        $this->validate([
            'vlan_number' => ['required', 'integer', 'between:1,4094'],
            'vlan_name' => ['required', 'string', 'max:128'],
            'vlan_site_id' => ['nullable', 'exists:sites,id'],
            'vlan_description' => ['nullable', 'string', 'max:500'],
            'vlan_security_zone' => ['required', 'string'],
            'vlan_status' => ['required', 'in:active,reserved,deprecated'],
            'vlan_is_restricted' => ['boolean'],
        ]);

        try {
            if ($this->isEditingVlan) {
                $vlan = Vlan::findOrFail($this->selectedVlanId);

                $updateAction->execute($vlan, [
                    'site_id' => $this->vlan_site_id,
                    'vlan_id' => (int) $this->vlan_number,
                    'name' => $this->vlan_name,
                    'description' => $this->vlan_description,
                    'security_zone' => VlanSecurityZone::from($this->vlan_security_zone),
                    'status' => VlanStatus::from($this->vlan_status),
                    'is_restricted' => $this->vlan_is_restricted,
                ]);

                $msg = "VLAN {$this->vlan_number} updated successfully.";
                session()->flash('success', $msg);
                $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
            } else {
                $createAction->execute([
                    'site_id' => $this->vlan_site_id,
                    'vlan_id' => (int) $this->vlan_number,
                    'name' => $this->vlan_name,
                    'description' => $this->vlan_description,
                    'security_zone' => VlanSecurityZone::from($this->vlan_security_zone),
                    'status' => VlanStatus::from($this->vlan_status),
                    'is_restricted' => $this->vlan_is_restricted,
                ]);

                $msg = "VLAN {$this->vlan_number} registered successfully.";
                session()->flash('success', $msg);
                $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
            }

            $this->showVlanModal = false;
        } catch (\Illuminate\Validation\ValidationException $ve) {
            $this->setErrorBag($ve->validator->getMessageBag());
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Validation failed: ' . $ve->getMessage()]);
        } catch (\Exception $e) {
            $this->addError('vlan_number', $e->getMessage());
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function confirmDelete(int $id): void
    {
        $vlan = Vlan::findOrFail($id);
        $this->deleteVlanId = $vlan->id;
        $this->deleteError = null;

        $attached = $vlan->subnets()->count();
        if ($attached > 0) {
            $this->deleteError = "Cannot delete VLAN {$vlan->vlan_id} ({$vlan->name}) because it is currently attached to {$attached} subnet(s). Detach or reassign those subnets first.";
        }

        $this->showDeleteModal = true;
    }

    public function deleteVlan(?DeleteVlanAction $action = null): void
    {
        $action ??= app(DeleteVlanAction::class);
        $vlan = Vlan::findOrFail($this->deleteVlanId);

        try {
            $action->execute($vlan);
            $msg = "VLAN {$vlan->vlan_id} deleted successfully.";
            session()->flash('success', $msg);
            $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
            $this->showDeleteModal = false;
        } catch (\Illuminate\Validation\ValidationException $ve) {
            $this->deleteError = $ve->getMessage();
            $this->dispatch('notify', ['type' => 'error', 'message' => $ve->getMessage()]);
        } catch (\Exception $e) {
            $this->deleteError = $e->getMessage();
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        $sites = Site::where('status', 'active')->orderBy('name')->get();
        $query = Vlan::with(['subnets', 'site']);

        if ($this->siteFilter) {
            $query->where(function ($q) {
                $q->where('site_id', $this->siteFilter)->orWhereNull('site_id');
            });
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('vlan_id', 'like', "%{$this->search}%");
            });
        }

        if ($this->zoneFilter !== 'all') {
            $query->where('security_zone', $this->zoneFilter);
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $vlans = $query->orderBy('vlan_id')->paginate(15);

        return view('livewire.vlans.index', [
            'vlans' => $vlans,
            'sites' => $sites,
            'securityZones' => VlanSecurityZone::cases(),
            'vlanStatuses' => VlanStatus::cases(),
        ]);
    }
}
