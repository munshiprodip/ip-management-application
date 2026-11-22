<div class="space-y-6">
    <!-- Header / Context Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 font-mono mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600">KYAMCH IPAM</a>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-semibold">Administration</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">Organization, Sites & System Administration</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Institutional master configuration for {{ $organization->name ?? 'KYAMCH' }}.
            </p>
        </div>

        <div class="flex items-center gap-2">
            @if($activeTab === 'sites')
                <button
                    wire:click="openCreateSiteModal"
                    type="button"
                    class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold shadow-xs transition"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Campus Site
                </button>
            @elseif($activeTab === 'departments')
                <button
                    wire:click="openCreateDeptModal"
                    type="button"
                    class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold shadow-xs transition"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Department
                </button>
            @endif
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex border-b border-slate-200 dark:border-slate-700 text-xs font-medium space-x-4">
        <button
            wire:click="setTab('sites')"
            class="pb-2.5 px-2 border-b-2 font-mono uppercase tracking-wide transition flex items-center gap-2 {{ $activeTab === 'sites' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}"
        >
            <span>Campus Sites ({{ $sites->count() }})</span>
        </button>

        <button
            wire:click="setTab('departments')"
            class="pb-2.5 px-2 border-b-2 font-mono uppercase tracking-wide transition flex items-center gap-2 {{ $activeTab === 'departments' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}"
        >
            <span>Hospital Departments ({{ $departments->count() }})</span>
        </button>

        <button
            wire:click="setTab('users')"
            class="pb-2.5 px-2 border-b-2 font-mono uppercase tracking-wide transition flex items-center gap-2 {{ $activeTab === 'users' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}"
        >
            <span>Users & RBAC ({{ $users->count() }})</span>
        </button>

        <button
            wire:click="setTab('parameters')"
            class="pb-2.5 px-2 border-b-2 font-mono uppercase tracking-wide transition flex items-center gap-2 {{ $activeTab === 'parameters' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}"
        >
            <span>System Parameters</span>
        </button>
    </div>

    <!-- Tab 1: Campus Sites -->
    @if($activeTab === 'sites')
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-mono uppercase text-[11px]">
                            <th class="py-3 px-4">Site Code</th>
                            <th class="py-3 px-4">Site / Campus Name</th>
                            <th class="py-3 px-4">Timezone</th>
                            <th class="py-3 px-4">L2 Domains</th>
                            <th class="py-3 px-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                        @foreach($sites as $site)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition">
                                <td class="py-3 px-4 font-mono font-bold text-slate-900 dark:text-white">
                                    {{ $site->code }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-medium text-slate-800 dark:text-slate-200">{{ $site->name }}</div>
                                    @if($site->description)
                                        <div class="text-[11px] text-slate-500 truncate max-w-sm">{{ $site->description }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-600 dark:text-slate-300">
                                    {{ $site->timezone }}
                                </td>
                                <td class="py-3 px-4 font-mono">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-[11px]">
                                        {{ $site->l2_domains_count }} Domain(s)
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                        {{ ucfirst($site->status) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Tab 2: Departments -->
    @if($activeTab === 'departments')
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-mono uppercase text-[11px]">
                            <th class="py-3 px-4">Code</th>
                            <th class="py-3 px-4">Department Name</th>
                            <th class="py-3 px-4">Contact Email</th>
                            <th class="py-3 px-4">Department Lead / Owner</th>
                            <th class="py-3 px-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                        @foreach($departments as $dept)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition">
                                <td class="py-3 px-4 font-mono font-bold text-slate-900 dark:text-white">
                                    {{ $dept->code }}
                                </td>
                                <td class="py-3 px-4 font-medium text-slate-800 dark:text-slate-200">
                                    {{ $dept->name }}
                                </td>
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-600 dark:text-slate-300">
                                    {{ $dept->contact_email ?? '—' }}
                                </td>
                                <td class="py-3 px-4 text-slate-700 dark:text-slate-300">
                                    {{ $dept->ownerUser?->name ?? 'Unassigned' }}
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                        {{ ucfirst($dept->status) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Tab 3: Users & RBAC -->
    @if($activeTab === 'users')
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-mono uppercase text-[11px]">
                            <th class="py-3 px-4">Operator Name</th>
                            <th class="py-3 px-4">Email</th>
                            <th class="py-3 px-4">Assigned Role</th>
                            <th class="py-3 px-4">Department</th>
                            <th class="py-3 px-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                        @foreach($users as $user)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition">
                                <td class="py-3 px-4 font-medium text-slate-800 dark:text-slate-200">{{ $user->name }}</td>
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-500">{{ $user->email }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-medium border {{ $user->role->badgeClasses() }}">
                                        {{ $user->role->label() }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400">{{ $user->department ?? '-' }}</td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center gap-1 font-mono text-[11px] {{ $user->isActive() ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $user->isActive() ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                        {{ ucfirst($user->status) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Tab 4: System Parameters -->
    @if($activeTab === 'parameters')
        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700 shadow-xs space-y-4">
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Active Operational Parameters</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-mono">
                <div class="p-3.5 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700">
                    <span class="text-slate-400 block text-[11px]">Primary Institution</span>
                    <span class="text-slate-900 dark:text-white font-bold">{{ $organization->name ?? 'KYAMCH' }}</span>
                </div>
                <div class="p-3.5 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700">
                    <span class="text-slate-400 block text-[11px]">Default Routing Context</span>
                    <span class="text-slate-900 dark:text-white font-bold">{{ config('ipam.default_vrf_code') }} ({{ config('ipam.default_vrf_name') }})</span>
                </div>
                <div class="p-3.5 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700">
                    <span class="text-slate-400 block text-[11px]">Automated Discovery Engine</span>
                    <span class="text-emerald-600 dark:text-emerald-400 font-bold">{{ $discoveryEnabled ? 'ACTIVE' : 'DISABLED (SAFE)' }}</span>
                </div>
                <div class="p-3.5 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700">
                    <span class="text-slate-400 block text-[11px]">External Network Writes</span>
                    <span class="text-slate-600 dark:text-slate-300 font-bold">{{ $writesEnabled ? 'ENABLED' : 'LOCKED (READ-ONLY)' }}</span>
                </div>
                <div class="p-3.5 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700">
                    <span class="text-slate-400 block text-[11px]">Database Connection</span>
                    <span class="text-blue-500 font-bold uppercase">{{ $dbDriver }}</span>
                </div>
                <div class="p-3.5 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700">
                    <span class="text-slate-400 block text-[11px]">Quarantine Retention Period</span>
                    <span class="text-purple-500 font-bold">{{ $quarantineDays }} Days</span>
                </div>
            </div>
        </div>
    @endif

    <!-- Create Site Modal -->
    @if($showSiteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 max-w-lg w-full p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Create Campus Site</h3>
                    <button wire:click="$set('showSiteModal', false)" type="button" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form wire:submit.prevent="saveSite" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Site Code *</label>
                        <input
                            wire:model="site_code"
                            type="text"
                            placeholder="e.g. DIAGNOSTICS"
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono uppercase focus:ring-2 focus:ring-emerald-500"
                        />
                        @error('site_code') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Site Name *</label>
                        <input
                            wire:model="site_name"
                            type="text"
                            placeholder="e.g. Specialized Diagnostic & Research Complex"
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500"
                        />
                        @error('site_name') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Timezone</label>
                        <input
                            wire:model="site_timezone"
                            type="text"
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono"
                        />
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Description</label>
                        <textarea
                            wire:model="site_description"
                            rows="2"
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-700">
                        <button wire:click="$set('showSiteModal', false)" type="button" class="px-3.5 py-2 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-semibold">
                            Create Site
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Create Department Modal -->
    @if($showDeptModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 max-w-lg w-full p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Create Department</h3>
                    <button wire:click="$set('showDeptModal', false)" type="button" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form wire:submit.prevent="saveDept" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Department Code *</label>
                        <input
                            wire:model="dept_code"
                            type="text"
                            placeholder="e.g. CARDIOLOGY"
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono uppercase focus:ring-2 focus:ring-emerald-500"
                        />
                        @error('dept_code') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Department Name *</label>
                        <input
                            wire:model="dept_name"
                            type="text"
                            placeholder="e.g. Department of Cardiology & CCU"
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500"
                        />
                        @error('dept_name') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Contact Email</label>
                            <input
                                wire:model="dept_email"
                                type="email"
                                placeholder="cardiology@kyamch.local"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100"
                            />
                        </div>

                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Phone Extension</label>
                            <input
                                wire:model="dept_phone"
                                type="text"
                                placeholder="+880-..."
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Department Lead / Owner</label>
                        <select
                            wire:model="dept_owner_id"
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100"
                        >
                            <option value="">Unassigned</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-700">
                        <button wire:click="$set('showDeptModal', false)" type="button" class="px-3.5 py-2 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-semibold">
                            Create Department
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
