<?php

declare(strict_types=1);

namespace App\Livewire\Vrfs;

use App\Actions\AddressSpace\CreateVrfAction;
use App\Actions\AddressSpace\DeleteVrfAction;
use App\Actions\AddressSpace\UpdateVrfAction;
use App\Domain\AddressSpace\Enums\VrfStatus;
use App\Models\Site;
use App\Models\Vrf;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('VRF Routing Contexts - KYAMCH IPAM')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';

    // Modal state
    public bool $showCreateModal = false;
    public bool $showEditModal = false;
    public bool $showDeleteModal = false;
    public ?int $selectedVrfId = null;

    // Form inputs
    public string $code = '';
    public string $name = '';
    public string $description = '';
    public string $route_distinguisher = '';
    public ?int $site_id = null;
    public string $status = 'active';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        Gate::authorize('create', Vrf::class);

        $this->reset(['code', 'name', 'description', 'route_distinguisher', 'site_id', 'status']);
        $this->status = 'active';
        $this->showCreateModal = true;
    }

    public function createVrf(CreateVrfAction $action): void
    {
        Gate::authorize('create', Vrf::class);

        $this->validate([
            'code' => ['required', 'string', 'max:64', 'regex:/^[A-Z0-9_\-]+$/i'],
            'name' => ['required', 'string', 'max:128'],
            'description' => ['nullable', 'string', 'max:500'],
            'route_distinguisher' => ['nullable', 'string', 'max:64'],
            'site_id' => ['nullable', 'integer', 'exists:sites,id'],
            'status' => ['required', 'in:active,retired'],
        ]);

        try {
            $action->execute([
                'code' => $this->code,
                'name' => $this->name,
                'description' => $this->description,
                'route_distinguisher' => $this->route_distinguisher,
                'site_id' => $this->site_id,
                'status' => VrfStatus::from($this->status),
            ]);

            $this->showCreateModal = false;
            session()->flash('success', "VRF '{$this->code}' created successfully.");
        } catch (\Exception $e) {
            $this->addError('code', $e->getMessage());
        }
    }

    public function openEditModal(int $id): void
    {
        $vrf = Vrf::findOrFail($id);
        Gate::authorize('update', $vrf);

        $this->selectedVrfId = $vrf->id;
        $this->code = $vrf->code;
        $this->name = $vrf->name;
        $this->description = (string) $vrf->description;
        $this->route_distinguisher = (string) $vrf->route_distinguisher;
        $this->site_id = $vrf->site_id;
        $this->status = $vrf->status->value;

        $this->showEditModal = true;
    }

    public function updateVrf(UpdateVrfAction $action): void
    {
        $vrf = Vrf::findOrFail($this->selectedVrfId);
        Gate::authorize('update', $vrf);

        $this->validate([
            'code' => ['required', 'string', 'max:64', 'regex:/^[A-Z0-9_\-]+$/i'],
            'name' => ['required', 'string', 'max:128'],
            'description' => ['nullable', 'string', 'max:500'],
            'route_distinguisher' => ['nullable', 'string', 'max:64'],
            'site_id' => ['nullable', 'integer', 'exists:sites,id'],
            'status' => ['required', 'in:active,retired'],
        ]);

        try {
            $action->execute($vrf, [
                'code' => $this->code,
                'name' => $this->name,
                'description' => $this->description,
                'route_distinguisher' => $this->route_distinguisher,
                'site_id' => $this->site_id,
                'status' => VrfStatus::from($this->status),
            ]);

            $this->showEditModal = false;
            session()->flash('success', "VRF '{$vrf->code}' updated successfully.");
        } catch (\Exception $e) {
            $this->addError('code', $e->getMessage());
        }
    }

    public function confirmDelete(int $id): void
    {
        $vrf = Vrf::findOrFail($id);
        Gate::authorize('delete', $vrf);

        $this->selectedVrfId = $vrf->id;
        $this->showDeleteModal = true;
    }

    public function deleteVrf(DeleteVrfAction $action): void
    {
        $vrf = Vrf::findOrFail($this->selectedVrfId);
        Gate::authorize('delete', $vrf);

        try {
            $action->execute($vrf);
            $this->showDeleteModal = false;
            session()->flash('success', "VRF '{$vrf->code}' deleted successfully.");
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->showDeleteModal = false;
        }
    }

    public function render()
    {
        $query = Vrf::with('site');

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('code', 'like', "%{$this->search}%")
                    ->orWhere('name', 'like', "%{$this->search}%")
                    ->orWhere('route_distinguisher', 'like', "%{$this->search}%");
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $vrfs = $query->orderByDesc('is_default')->orderBy('code')->paginate(10);
        $sites = Site::orderBy('name')->get();

        return view('livewire.vrfs.index', [
            'vrfs' => $vrfs,
            'sites' => $sites,
        ]);
    }
}
