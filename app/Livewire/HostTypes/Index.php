<?php

declare(strict_types=1);

namespace App\Livewire\HostTypes;

use App\Actions\HostType\CreateHostTypeAction;
use App\Actions\HostType\DeleteHostTypeAction;
use App\Actions\HostType\UpdateHostTypeAction;
use App\Models\HostType;
use App\Models\IpAddress;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Host Types Management - KYAMCH IPAM')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';

    // Modal state
    public bool $showModal = false;
    public bool $isEditing = false;
    public ?int $selectedHostTypeId = null;

    // Form inputs
    public string $name = '';
    public string $slug = '';
    public string $description = '';
    public string $color = 'slate';
    public bool $is_active = true;

    // Delete confirmation
    public bool $showDeleteModal = false;
    public ?int $deleteHostTypeId = null;
    public string $deleteHostTypeName = '';
    public ?string $deleteError = null;

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
        $this->selectedHostTypeId = null;
        $this->reset(['name', 'slug', 'description']);
        $this->color = 'indigo';
        $this->is_active = true;
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $hostType = HostType::findOrFail($id);
        $this->isEditing = true;
        $this->selectedHostTypeId = $hostType->id;
        $this->name = $hostType->name;
        $this->slug = $hostType->slug;
        $this->description = (string) $hostType->description;
        $this->color = $hostType->color;
        $this->is_active = (bool) $hostType->is_active;
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function saveHostType(CreateHostTypeAction $createAction, UpdateHostTypeAction $updateAction): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:64'],
            'slug' => ['nullable', 'string', 'max:64'],
            'color' => ['required', 'string', 'in:' . implode(',', array_keys(HostType::colorOptions()))],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            if ($this->isEditing) {
                $hostType = HostType::findOrFail($this->selectedHostTypeId);
                $updateAction->execute($hostType, [
                    'name' => $this->name,
                    'slug' => $this->slug,
                    'description' => $this->description,
                    'color' => $this->color,
                    'is_active' => $this->is_active,
                ], auth()->user());
                session()->flash('success', "Host type '{$hostType->name}' updated successfully.");
            } else {
                $created = $createAction->execute([
                    'name' => $this->name,
                    'slug' => $this->slug,
                    'description' => $this->description,
                    'color' => $this->color,
                    'is_active' => $this->is_active,
                ], auth()->user());
                session()->flash('success', "Host type '{$created->name}' created successfully.");
            }

            $this->showModal = false;
        } catch (\Illuminate\Validation\ValidationException $ve) {
            $this->setErrorBag($ve->validator->getMessageBag());
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function toggleStatus(int $id, UpdateHostTypeAction $action): void
    {
        $hostType = HostType::findOrFail($id);
        $newStatus = ! $hostType->is_active;

        $action->execute($hostType, ['is_active' => $newStatus], auth()->user());
        session()->flash('success', "Host type '{$hostType->name}' is now " . ($newStatus ? 'active' : 'inactive') . '.');
    }

    public function confirmDelete(int $id): void
    {
        $hostType = HostType::findOrFail($id);
        $this->deleteHostTypeId = $hostType->id;
        $this->deleteHostTypeName = $hostType->name;
        $this->deleteError = null;

        if ($hostType->is_system) {
            $this->deleteError = "This is a built-in system host type and cannot be deleted. You can deactivate it instead.";
        } else {
            $allocCount = $hostType->allocatedCount();
            if ($allocCount > 0) {
                $this->deleteError = "Cannot delete '{$hostType->name}' because {$allocCount} IP address(es) currently reference it. Reassign those addresses first or deactivate this type.";
            }
        }

        $this->showDeleteModal = true;
    }

    public function deleteHostType(DeleteHostTypeAction $action): void
    {
        if (! $this->deleteHostTypeId) {
            return;
        }

        $hostType = HostType::findOrFail($this->deleteHostTypeId);

        try {
            $action->execute($hostType, auth()->user());
            session()->flash('success', "Host type '{$this->deleteHostTypeName}' deleted successfully.");
            $this->showDeleteModal = false;
            $this->deleteHostTypeId = null;
        } catch (\Exception $e) {
            $this->deleteError = $e->getMessage();
        }
    }

    public function render()
    {
        $query = HostType::query()->withCount('ipAddresses');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('slug', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        $hostTypes = $query->orderBy('name')->paginate(12);

        $totalTypes = HostType::count();
        $activeTypes = HostType::where('is_active', true)->count();
        $systemTypes = HostType::where('is_system', true)->count();
        $allocatedIps = IpAddress::whereNotNull('host_type')->count();

        return view('livewire.host-types.index', [
            'hostTypes' => $hostTypes,
            'totalTypes' => $totalTypes,
            'activeTypes' => $activeTypes,
            'systemTypes' => $systemTypes,
            'allocatedIps' => $allocatedIps,
            'colorOptions' => HostType::colorOptions(),
        ]);
    }
}

