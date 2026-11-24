<?php

declare(strict_types=1);

namespace App\Livewire\Roles;

use App\Domain\Security\Enums\UserRole;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Roles & Permissions - KYAMCH IPAM')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'role')]
    public string $selectedRole = 'all';

    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canManageRoles(), 403, 'Unauthorized: Access to Role Management is restricted to Platform Super Administrators.');
    }

    public function updatingSelectedRole(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $superAdminCount = User::where('role', UserRole::SUPER_ADMIN->value)->count();
        $adminCount = User::whereIn('role', [UserRole::ADMIN->value, UserRole::NETWORK_ADMIN->value])->count();
        $userCount = User::whereNotIn('role', [
            UserRole::SUPER_ADMIN->value,
            UserRole::ADMIN->value,
            UserRole::NETWORK_ADMIN->value,
        ])->count();

        // 3 System Roles Definitions
        $roles = [
            [
                'key' => 'super_admin',
                'name' => 'Super Admin',
                'badge' => 'bg-purple-100 text-purple-800 border-purple-200 dark:bg-purple-900/40 dark:text-purple-300 dark:border-purple-800',
                'count' => $superAdminCount,
                'summary' => 'All access. Unrestricted platform root authority.',
                'description' => 'Platform super administrator with unrestricted governance over core IPAM, infrastructure topology, security policies, user administration, and role assignments.',
                'capabilities' => [
                    'Full Core IPAM operations (Subnets, VLANs, IP allocations, ICMP Ping, Network Scanner)',
                    'Infrastructure Administration (Campuses & Sites, Host Types Management)',
                    'Complete User Administration (Create, edit, reset passwords, delete all accounts)',
                    'Role Management & Security Governance (Assign Super Admin, Admin, and User roles)',
                    'Security Audit Trail & System Log Review',
                ],
            ],
            [
                'key' => 'admin',
                'name' => 'Admin',
                'badge' => 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/40 dark:text-blue-300 dark:border-blue-800',
                'count' => $adminCount,
                'summary' => 'All access without role management; can make users but not Super Admin.',
                'description' => 'Network administrator with full operational IPAM and user account administration. Restricted from security role definition and cannot create or alter Super Admin accounts.',
                'capabilities' => [
                    'Full Core IPAM operations (Subnets, VLANs, IP allocations, ICMP Ping, Network Scanner)',
                    'Infrastructure Administration (Campuses & Sites, Host Types Management)',
                    'User Management for Admin and User accounts (Create, edit, deactivate, reset passwords)',
                    'Restricted: Cannot assign or create Super Admin accounts',
                    'Restricted: Cannot edit, alter, or delete Super Admin accounts',
                    'Restricted: Cannot access or modify Role Management & Security Policies',
                ],
            ],
            [
                'key' => 'user',
                'name' => 'User',
                'badge' => 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-800',
                'count' => $userCount,
                'summary' => 'All access without user and role management.',
                'description' => 'Operational enterprise user with complete access to all daily network management operations. Restricted from managing user accounts and role definitions.',
                'capabilities' => [
                    'Full Core IPAM operations (Subnets & CIDR Hierarchy, 802.1Q VLANs, IP Explorer & Allocations)',
                    'Live ICMP Ping reachability checks & Network IP Scanner device discovery',
                    'Infrastructure views and management (Campuses & Sites, Host Types)',
                    'Restricted: Cannot access or perform User Management',
                    'Restricted: Cannot access or perform Role Management',
                ],
            ],
        ];

        // Permission Comparison Matrix
        $matrix = [
            [
                'category' => 'Core IPAM Operations',
                'features' => [
                    ['name' => 'Dashboard KPI Metrics & Capacity Watchlist', 'super_admin' => true, 'admin' => true, 'user' => true],
                    ['name' => 'Subnets & CIDR Hierarchy Management (Create/Edit/Split)', 'super_admin' => true, 'admin' => true, 'user' => true],
                    ['name' => '802.1Q VLAN Tag & Security Zone Management', 'super_admin' => true, 'admin' => true, 'user' => true],
                    ['name' => 'IP Address Explorer, Allocations & Reservations', 'super_admin' => true, 'admin' => true, 'user' => true],
                    ['name' => 'Real ICMP Echo Ping Reachability Probing', 'super_admin' => true, 'admin' => true, 'user' => true],
                    ['name' => 'Network IP Scanner & Discovered Device Registration', 'super_admin' => true, 'admin' => true, 'user' => true],
                ],
            ],
            [
                'category' => 'Infrastructure Administration',
                'features' => [
                    ['name' => 'Campuses & Facility Sites Management', 'super_admin' => true, 'admin' => true, 'user' => true],
                    ['name' => 'Host Types & Device Classification Tags', 'super_admin' => true, 'admin' => true, 'user' => true],
                ],
            ],
            [
                'category' => 'User Management',
                'features' => [
                    ['name' => 'View User Management Page (/users)', 'super_admin' => true, 'admin' => true, 'user' => false],
                    ['name' => 'Create & Edit User Accounts', 'super_admin' => true, 'admin' => true, 'user' => false],
                    ['name' => 'Create or Promote Super Admin Accounts', 'super_admin' => true, 'admin' => false, 'user' => false],
                    ['name' => 'Edit or Delete Super Admin Accounts', 'super_admin' => true, 'admin' => false, 'user' => false],
                    ['name' => 'Reset User Passwords & Toggle Status', 'super_admin' => true, 'admin' => true, 'user' => false],
                ],
            ],
            [
                'category' => 'Role & Governance Management',
                'features' => [
                    ['name' => 'View Roles & Permissions Page (/roles)', 'super_admin' => true, 'admin' => false, 'user' => false],
                    ['name' => 'Security Authority & Role Assignment', 'super_admin' => true, 'admin' => false, 'user' => false],
                    ['name' => 'Security Audit Trail & Governance Log Access', 'super_admin' => true, 'admin' => false, 'user' => false],
                ],
            ],
        ];

        // Query user roster
        $rosterQuery = User::query();

        if ($this->selectedRole !== 'all') {
            if ($this->selectedRole === 'admin') {
                $rosterQuery->whereIn('role', [UserRole::ADMIN->value, UserRole::NETWORK_ADMIN->value]);
            } elseif ($this->selectedRole === 'user') {
                $rosterQuery->whereNotIn('role', [
                    UserRole::SUPER_ADMIN->value,
                    UserRole::ADMIN->value,
                    UserRole::NETWORK_ADMIN->value,
                ]);
            } else {
                $rosterQuery->where('role', $this->selectedRole);
            }
        }

        if (! empty($this->search)) {
            $term = '%' . trim($this->search) . '%';
            $rosterQuery->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('department', 'like', $term);
            });
        }

        $rosterUsers = $rosterQuery->orderBy('name')->paginate(10);

        return view('livewire.roles.index', [
            'roles' => $roles,
            'matrix' => $matrix,
            'rosterUsers' => $rosterUsers,
            'superAdminCount' => $superAdminCount,
            'adminCount' => $adminCount,
            'userCount' => $userCount,
        ]);
    }
}

