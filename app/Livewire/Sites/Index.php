<?php

declare(strict_types=1);

namespace App\Livewire\Sites;

use App\Actions\Site\CreateSiteAction;
use App\Actions\Site\DeleteSiteAction;
use App\Actions\Site\UpdateSiteAction;
use App\Models\Site;
use App\Services\SiteContext;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Campuses & Sites - KYAMCH IPAM')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';

    // Modal state
    public bool $showModal = false;
    public bool $isEditing = false;
    public ?int $selectedSiteId = null;

    // Form inputs
    public string $name = '';
    public string $code = '';
    public string $description = '';
    public string $status = 'active';
    public bool $is_default = false;

    // Delete confirmation
    public bool $showDeleteModal = false;

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
        $this->isEditing = false;
        $this->selectedSiteId = null;
        $this->reset(['name', 'code', 'description']);
        $this->status = 'active';
        $this->is_default = false;
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $site = Site::findOrFail($id);
        $this->isEditing = true;
        $this->selectedSiteId = $site->id;
        $this->name = $site->name;
        $this->code = $site->code;
        $this->description = (string) $site->description;
        $this->status = $site->status;
        $this->is_default = (bool) $site->is_default;
        $this->showModal = true;
    }

    public function saveSite(CreateSiteAction $createAction, UpdateSiteAction $updateAction): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:32'],
            'status' => ['required', 'in:active,inactive'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            if ($this->isEditing) {
                $site = Site::findOrFail($this->selectedSiteId);
                $updateAction->execute($site, [
                    'name' => $this->name,
                    'code' => $this->code,
                    'description' => $this->description,
                    'status' => $this->status,
                    'is_default' => $this->is_default,
                ]);
                session()->flash('success', "Campus '{$site->name}' updated successfully.");
            } else {
                $createAction->execute([
                    'name' => $this->name,
                    'code' => $this->code,
                    'description' => $this->description,
                    'status' => $this->status,
                    'is_default' => $this->is_default,
                ]);
                session()->flash('success', "Campus created successfully.");
            }

            $this->showModal = false;
        } catch (\Exception $e) {
            $this->addError('code', $e->getMessage());
        }
    }

    public function confirmDelete(int $id): void
    {
        $site = Site::findOrFail($id);
        $this->selectedSiteId = $site->id;
        $this->showDeleteModal = true;
    }

    public function deleteSite(DeleteSiteAction $action): void
    {
        $site = Site::findOrFail($this->selectedSiteId);

        try {
            $action->execute($site);
            session()->flash('success', "Campus '{$site->name}' deleted successfully.");
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }

        $this->showDeleteModal = false;
    }

    public function selectAsActive(int $id): void
    {
        app(SiteContext::class)->setActiveSiteId($id);
        $this->redirect(route('subnets.index'));
    }

    public function render()
    {
        $query = Site::withCount(['subnets', 'vlans']);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%");
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $sites = $query->orderBy('name')->paginate(10);
        $activeSiteId = app(SiteContext::class)->getActiveSiteId();

        return view('livewire.sites.index', [
            'sites' => $sites,
            'activeSiteId' => $activeSiteId,
        ]);
    }
}

