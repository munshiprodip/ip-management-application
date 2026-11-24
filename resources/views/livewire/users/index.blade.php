<div class="space-y-6">
    <!-- Inline Flash Notifications -->
    @if (session('success'))
        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/70 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 text-xs rounded-xl shadow-xs flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
        </div>
    @endif
    @if (session('error'))
        <div class="p-3 bg-rose-50 dark:bg-rose-950/70 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200 text-xs rounded-xl shadow-xs flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Header / Context Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 sm:p-5 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 font-mono mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600">KYAMCH IPAM</a>
                <span>/</span>
                <span>Administration</span>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-semibold">Users</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">User Management</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Manage system operator accounts, roles, access levels, and security credentials.
            </p>
        </div>

        <div>
            <button
                wire:click="openCreateModal"
                type="button"
                class="w-full sm:w-auto justify-center inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold shadow-xs transition"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                <span>Add User</span>
            </button>
        </div>
    </div>

    <!-- KPI Metric Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl p-3.5 sm:p-4 border border-slate-200 dark:border-slate-700 shadow-xs">
            <span class="text-[11px] font-mono text-slate-400 uppercase">Total Accounts</span>
            <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ $totalUsers }}</div>
            <span class="text-[11px] text-slate-500">{{ $activeUsers }} active in system</span>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-3.5 sm:p-4 border border-slate-200 dark:border-slate-700 shadow-xs">
            <span class="text-[11px] font-mono text-purple-600 dark:text-purple-400 uppercase font-semibold">Super Admins</span>
            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ $superAdminCount }}</div>
            <span class="text-[11px] text-slate-500">Unrestricted all-access</span>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-3.5 sm:p-4 border border-slate-200 dark:border-slate-700 shadow-xs">
            <span class="text-[11px] font-mono text-blue-600 dark:text-blue-400 uppercase font-semibold">Admins</span>
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $adminCount }}</div>
            <span class="text-[11px] text-slate-500">IPAM + User managers</span>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-3.5 sm:p-4 border border-slate-200 dark:border-slate-700 shadow-xs">
            <span class="text-[11px] font-mono text-emerald-600 dark:text-emerald-400 uppercase font-semibold">Users</span>
            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $userCount }}</div>
            <span class="text-[11px] text-slate-500">Core IPAM operations</span>
        </div>
    </div>

    <!-- Filter & Search Controls -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3 sm:p-4 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col md:flex-row gap-3 items-center justify-between">
        <div class="w-full md:w-80">
            <div class="relative">
                <input
                    wire:model.live.debounce.300ms="search"
                    type="text"
                    placeholder="Search name, email, department, phone..."
                    class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                />
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
            <!-- Role Filter -->
            <select
                wire:model.live="roleFilter"
                class="px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500"
            >
                <option value="all">All Roles</option>
                <option value="super_admin">Super Admin</option>
                <option value="admin">Admin</option>
                <option value="user">User</option>
            </select>

            <!-- Status Filter -->
            <select
                wire:model.live="statusFilter"
                class="px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500"
            >
                <option value="all">All Statuses</option>
                <option value="active">Active Only</option>
                <option value="inactive">Inactive Only</option>
            </select>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-900/60 text-[11px] font-mono text-slate-500 border-b border-slate-200 dark:border-slate-700 uppercase">
                    <tr>
                        <th class="py-3 px-4 font-semibold">User</th>
                        <th class="py-3 px-4 font-semibold">Role</th>
                        <th class="py-3 px-4 font-semibold">Department &amp; Phone</th>
                        <th class="py-3 px-4 font-semibold">Status</th>
                        <th class="py-3 px-4 font-semibold">Created</th>
                        <th class="py-3 px-4 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 font-sans">
                    @forelse($users as $u)
                        @php
                            $isSelf = auth()->id() === $u->id;
                            $canEdit = auth()->user()->canEditUser($u);
                            $canDelete = auth()->user()->canDeleteUser($u);
                            $roleEnum = $u->role instanceof \App\Domain\Security\Enums\UserRole ? $u->role : \App\Domain\Security\Enums\UserRole::tryFrom((string)$u->role);
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition">
                            <!-- User info -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold flex items-center justify-center text-xs shrink-0 uppercase">
                                        {{ substr($u->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-900 dark:text-white flex items-center gap-1.5">
                                            <span>{{ $u->name }}</span>
                                            @if($isSelf)
                                                <span class="px-1.5 py-0.2 rounded text-[10px] bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-mono">You</span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-slate-400 font-mono">{{ $u->email }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Role badge -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                @if($roleEnum)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $roleEnum->badgeClasses() }}">
                                        {{ $roleEnum->label() }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                                        {{ $u->role }}
                                    </span>
                                @endif
                            </td>

                            <!-- Department & Phone -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="text-slate-900 dark:text-slate-100 font-medium">{{ $u->department ?: '—' }}</div>
                                <div class="text-[11px] text-slate-400 font-mono">{{ $u->phone ?: '—' }}</div>
                            </td>

                            <!-- Status -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                @if($u->status === 'active')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        Inactive
                                    </span>
                                @endif
                            </td>

                            <!-- Created At -->
                            <td class="py-3 px-4 whitespace-nowrap font-mono text-[11px] text-slate-400">
                                {{ $u->created_at ? $u->created_at->format('Y-m-d') : '—' }}
                            </td>

                            <!-- Actions -->
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- Edit -->
                                    @if($canEdit)
                                        <button
                                            wire:click="openEditModal({{ $u->id }})"
                                            type="button"
                                            class="px-2 py-1 rounded border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 text-[11px] font-medium transition"
                                            title="Edit user details"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            wire:click="openResetPasswordModal({{ $u->id }})"
                                            type="button"
                                            class="p-1 rounded border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                                            title="Reset password"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                        </button>
                                        @if(! $isSelf)
                                            <button
                                                wire:click="toggleStatus({{ $u->id }})"
                                                type="button"
                                                class="px-2 py-1 rounded border text-[11px] font-medium transition {{ $u->status === 'active' ? 'border-amber-300 dark:border-amber-800 text-amber-700 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/40' : 'border-emerald-300 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40' }}"
                                                title="{{ $u->status === 'active' ? 'Deactivate user' : 'Activate user' }}"
                                            >
                                                {{ $u->status === 'active' ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        @endif
                                    @else
                                        <span class="text-[11px] text-slate-400 italic">Protected</span>
                                    @endif

                                    <!-- Delete -->
                                    @if($canDelete)
                                        <button
                                            wire:click="openDeleteModal({{ $u->id }})"
                                            type="button"
                                            class="p-1 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded transition"
                                            title="Delete user"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                <svg class="w-8 h-8 mx-auto mb-2 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                No user accounts found matching current search and filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    <!-- Create User Modal -->
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="w-full max-w-lg bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <form wire:submit="createUser">
                    <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Create New User</h3>
                            <p class="text-xs text-slate-500">Configure credentials, role level, and identity information.</p>
                        </div>
                        <button wire:click="$set('showCreateModal', false)" type="button" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <div class="p-5 space-y-4 text-xs">
                        <!-- Error summary banner -->
                        @if ($errors->any())
                            <div class="p-3 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 rounded-lg text-rose-800 dark:text-rose-300">
                                <span class="font-bold">Please correct the following errors:</span>
                                <ul class="list-disc list-inside mt-1 space-y-0.5">
                                    @foreach ($errors->all() as $err)
                                        <li>{{ $err }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Full Name *</label>
                                <input
                                    wire:model="name"
                                    type="text"
                                    placeholder="e.g. Dr. John Doe"
                                    class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-medium"
                                />
                                @error('name') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Email Address *</label>
                                <input
                                    wire:model="email"
                                    type="email"
                                    placeholder="john.doe@kyamch.local"
                                    class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono"
                                />
                                @error('email') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Password *</label>
                                <input
                                    wire:model="password"
                                    type="password"
                                    placeholder="Minimum 8 characters"
                                    class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono"
                                />
                                @error('password') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Confirm Password *</label>
                                <input
                                    wire:model="password_confirmation"
                                    type="password"
                                    placeholder="Re-type password"
                                    class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Assigned Role *</label>
                                <select
                                    wire:model="role"
                                    class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-semibold"
                                >
                                    @foreach($availableRoles as $r)
                                        <option value="{{ $r->value }}">{{ $r->label() }}</option>
                                    @endforeach
                                </select>
                                @error('role') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                                @if(! auth()->user()->isSuperAdmin())
                                    <span class="text-[10px] text-slate-400 block mt-1 italic">Super Admin role creation is restricted to Super Administrators.</span>
                                @endif
                            </div>

                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Account Status *</label>
                                <select
                                    wire:model="status"
                                    class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100"
                                >
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                @error('status') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Department</label>
                                <input
                                    wire:model="department"
                                    type="text"
                                    placeholder="e.g. IT, Radiology, ICU"
                                    class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100"
                                />
                            </div>

                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Phone Number</label>
                                <input
                                    wire:model="phone"
                                    type="text"
                                    placeholder="e.g. +880 1711 000000"
                                    class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="px-5 py-3.5 bg-slate-50 dark:bg-slate-900/60 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-2">
                        <button
                            wire:click="$set('showCreateModal', false)"
                            type="button"
                            class="px-3.5 py-2 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-medium hover:bg-slate-100 dark:hover:bg-slate-800"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-semibold shadow-xs transition"
                        >
                            Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Edit User Modal -->
    @if($showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="w-full max-w-lg bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <form wire:submit="updateUser">
                    <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit User Account</h3>
                            <p class="text-xs text-slate-500">Update operator details and role permissions.</p>
                        </div>
                        <button wire:click="$set('showEditModal', false)" type="button" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <div class="p-5 space-y-4 text-xs">
                        @if ($errors->any())
                            <div class="p-3 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 rounded-lg text-rose-800 dark:text-rose-300">
                                <span class="font-bold">Please correct the following errors:</span>
                                <ul class="list-disc list-inside mt-1 space-y-0.5">
                                    @foreach ($errors->all() as $err)
                                        <li>{{ $err }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Full Name *</label>
                                <input
                                    wire:model="name"
                                    type="text"
                                    class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-medium"
                                />
                                @error('name') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Email Address *</label>
                                <input
                                    wire:model="email"
                                    type="email"
                                    class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono"
                                />
                                @error('email') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Role *</label>
                                <select
                                    wire:model="role"
                                    class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-semibold"
                                >
                                    @foreach($availableRoles as $r)
                                        <option value="{{ $r->value }}">{{ $r->label() }}</option>
                                    @endforeach
                                </select>
                                @error('role') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Status *</label>
                                <select
                                    wire:model="status"
                                    class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100"
                                >
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                @error('status') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Department</label>
                                <input
                                    wire:model="department"
                                    type="text"
                                    class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100"
                                />
                            </div>

                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Phone Number</label>
                                <input
                                    wire:model="phone"
                                    type="text"
                                    class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="px-5 py-3.5 bg-slate-50 dark:bg-slate-900/60 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-2">
                        <button
                            wire:click="$set('showEditModal', false)"
                            type="button"
                            class="px-3.5 py-2 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-medium hover:bg-slate-100 dark:hover:bg-slate-800"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-semibold shadow-xs transition"
                        >
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Reset Password Modal -->
    @if($showResetPasswordModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="w-full max-w-md bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <form wire:submit="resetPassword">
                    <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Reset Password</h3>
                            <p class="text-xs text-slate-500">Set new password for <span class="font-bold text-slate-800 dark:text-slate-200">{{ $resetPasswordUserName }}</span></p>
                        </div>
                        <button wire:click="$set('showResetPasswordModal', false)" type="button" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <div class="p-5 space-y-3 text-xs">
                        @if ($errors->any())
                            <div class="p-2.5 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 rounded-lg text-rose-800 dark:text-rose-300">
                                @foreach ($errors->all() as $err)
                                    <p>{{ $err }}</p>
                                @endforeach
                            </div>
                        @endif

                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">New Password *</label>
                            <input
                                wire:model="newPassword"
                                type="password"
                                placeholder="Minimum 8 characters"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono"
                            />
                            @error('newPassword') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Confirm New Password *</label>
                            <input
                                wire:model="newPasswordConfirmation"
                                type="password"
                                placeholder="Re-type new password"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono"
                            />
                        </div>
                    </div>

                    <div class="px-5 py-3.5 bg-slate-50 dark:bg-slate-900/60 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-2">
                        <button
                            wire:click="$set('showResetPasswordModal', false)"
                            type="button"
                            class="px-3.5 py-2 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-medium"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-semibold shadow-xs transition"
                        >
                            Set New Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="w-full max-w-md bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="p-5 space-y-3">
                    <div class="w-10 h-10 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Delete User Account</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Are you sure you want to permanently delete user account <span class="font-bold text-slate-900 dark:text-white">{{ $deletingUserName }}</span>? This action is irrevocable and will be logged in the system security audit trail.
                    </p>
                </div>

                <div class="px-5 py-3.5 bg-slate-50 dark:bg-slate-900/60 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-2">
                    <button
                        wire:click="$set('showDeleteModal', false)"
                        type="button"
                        class="px-3.5 py-2 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-xs font-medium"
                    >
                        Cancel
                    </button>
                    <button
                        wire:click="deleteUser"
                        type="button"
                        class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-lg text-xs font-semibold shadow-xs transition"
                    >
                        Permanently Delete
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

