<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\Organization\ManageOrganizationAction;
use App\Models\Department;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('System Administration & Organization - KYAMCH IPAM')]
class Index extends Component
{
    public string $activeTab = 'sites'; // 'sites', 'departments', 'users', 'parameters'

    // Site modal
    public bool $showSiteModal = false;
    public string $site_code = '';
    public string $site_name = '';
    public string $site_description = '';
    public string $site_timezone = 'Asia/Dhaka';

    // Department modal
    public bool $showDeptModal = false;
    public string $dept_code = '';
    public string $dept_name = '';
    public ?int $dept_owner_id = null;
    public string $dept_email = '';
    public string $dept_phone = '';

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function openCreateSiteModal(): void
    {
        $this->reset(['site_code', 'site_name', 'site_description']);
        $this->site_timezone = 'Asia/Dhaka';
        $this->showSiteModal = true;
    }

    public function saveSite(ManageOrganizationAction $action): void
    {
        $this->validate([
            'site_code' => ['required', 'string', 'max:32', 'regex:/^[A-Z0-9_\-]+$/i'],
            'site_name' => ['required', 'string', 'max:150'],
            'site_description' => ['nullable', 'string', 'max:255'],
            'site_timezone' => ['required', 'string', 'max:64'],
        ]);

        try {
            $action->createSite([
                'code' => $this->site_code,
                'name' => $this->site_name,
                'description' => $this->site_description,
                'timezone' => $this->site_timezone,
            ]);

            $this->showSiteModal = false;
            session()->flash('success', "Campus site '{$this->site_code}' created successfully.");
        } catch (\Exception $e) {
            $this->addError('site_code', $e->getMessage());
        }
    }

    public function openCreateDeptModal(): void
    {
        $this->reset(['dept_code', 'dept_name', 'dept_email', 'dept_phone', 'dept_owner_id']);
        $this->showDeptModal = true;
    }

    public function saveDept(ManageOrganizationAction $action): void
    {
        $this->validate([
            'dept_code' => ['required', 'string', 'max:32', 'regex:/^[A-Z0-9_\-]+$/i'],
            'dept_name' => ['required', 'string', 'max:150'],
            'dept_owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'dept_email' => ['nullable', 'email', 'max:150'],
            'dept_phone' => ['nullable', 'string', 'max:40'],
        ]);

        try {
            $action->createDepartment([
                'code' => $this->dept_code,
                'name' => $this->dept_name,
                'owner_user_id' => $this->dept_owner_id,
                'contact_email' => $this->dept_email,
                'contact_phone' => $this->dept_phone,
            ]);

            $this->showDeptModal = false;
            session()->flash('success', "Department '{$this->dept_code}' registered successfully.");
        } catch (\Exception $e) {
            $this->addError('dept_code', $e->getMessage());
        }
    }

    public function render()
    {
        $organization = Organization::first();
        $sites = Site::withCount(['l2Domains', 'locations'])->orderBy('name')->get();
        $departments = Department::with('ownerUser')->orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('livewire.admin.index', [
            'organization' => $organization,
            'sites' => $sites,
            'departments' => $departments,
            'users' => $users,
            'discoveryEnabled' => config('ipam.discovery_enabled'),
            'writesEnabled' => config('ipam.external_writes_enabled'),
            'dbDriver' => config('database.default'),
            'timezone' => config('ipam.default_timezone'),
            'quarantineDays' => config('ipam.quarantine_duration_days'),
        ]);
    }
}
