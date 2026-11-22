<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\Organization\ManageOrganizationAction;
use App\Models\ApiToken;
use App\Models\Department;
use App\Models\Integration;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use App\Services\Integrations\Adapters\DhcpReadonlySyncAdapter;
use App\Services\Integrations\Adapters\DnsReadonlySyncAdapter;
use App\Services\Integrations\IntegrationSyncService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('System Administration & Organization - KYAMCH IPAM')]
class Index extends Component
{
    public string $activeTab = 'sites'; // 'sites', 'departments', 'users', 'tokens', 'integrations', 'parameters'

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

    // Token creation state
    public bool $showTokenModal = false;
    public string $tokenName = '';
    public array $tokenScopes = ['read:subnets', 'read:ips', 'write:allocations'];
    public int $tokenExpiresInDays = 90;
    public ?string $newlyGeneratedPlainToken = null;

    public ?string $feedbackMessage = null;
    public ?string $feedbackError = null;

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->feedbackMessage = null;
        $this->feedbackError = null;
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

    public function openCreateTokenModal(): void
    {
        $this->tokenName = '';
        $this->tokenScopes = ['read:subnets', 'read:ips', 'write:allocations'];
        $this->tokenExpiresInDays = 90;
        $this->newlyGeneratedPlainToken = null;
        $this->showTokenModal = true;
    }

    public function createApiToken(): void
    {
        $this->validate([
            'tokenName' => 'required|string|min:3|max:128',
            'tokenScopes' => 'required|array|min:1',
            'tokenExpiresInDays' => 'required|integer|min:1|max:365',
        ]);

        $result = ApiToken::createToken(
            name: $this->tokenName,
            scopes: $this->tokenScopes,
            user: auth()->user(),
            expiresInDays: $this->tokenExpiresInDays,
            rateLimit: 60
        );

        $this->newlyGeneratedPlainToken = $result['plain_token'];
        $this->feedbackMessage = "API Token '{$this->tokenName}' created successfully. Copy the token now; it will never be displayed again.";
    }

    public function revokeApiToken(int $id): void
    {
        $token = ApiToken::findOrFail($id);
        $token->revoke();
        $this->feedbackMessage = "API Token '{$token->name}' revoked.";
    }

    public function triggerSync(int $integrationId, IntegrationSyncService $syncService): void
    {
        $integration = Integration::findOrFail($integrationId);

        // Register default adapters
        $syncService->registerAdapter(new DhcpReadonlySyncAdapter());
        $syncService->registerAdapter(new DnsReadonlySyncAdapter());

        try {
            $run = $syncService->sync($integration);
            $this->feedbackMessage = sprintf(
                "Read-only sync completed for [%s]: %d scanned, %d matched, %d divergent.",
                $integration->name,
                $run->items_scanned,
                $run->items_matched,
                $run->items_divergent
            );
        } catch (\Throwable $e) {
            $this->feedbackError = "Sync failed: " . $e->getMessage();
        }
    }

    public function render()
    {
        $organization = Organization::first();
        $sites = Site::withCount(['l2Domains', 'locations'])->orderBy('name')->get();
        $departments = Department::with('ownerUser')->orderBy('name')->get();
        $users = User::orderBy('name')->get();
        $tokens = ApiToken::with('user')->orderByDesc('id')->get();
        $integrations = Integration::with('site')->orderBy('name')->get();

        return view('livewire.admin.index', [
            'organization' => $organization,
            'sites' => $sites,
            'departments' => $departments,
            'users' => $users,
            'tokens' => $tokens,
            'integrations' => $integrations,
            'discoveryEnabled' => config('ipam.discovery_enabled'),
            'writesEnabled' => config('ipam.external_writes_enabled'),
            'dbDriver' => config('database.default'),
            'timezone' => config('ipam.default_timezone'),
            'quarantineDays' => config('ipam.quarantine_duration_days'),
        ]);
    }
}
