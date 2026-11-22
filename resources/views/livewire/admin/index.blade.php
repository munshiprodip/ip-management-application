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
            @elseif($activeTab === 'tokens')
                <button
                    wire:click="openCreateTokenModal"
                    type="button"
                    class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold shadow-xs transition"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    Generate API Token
                </button>
            @endif
        </div>
    </div>

    @if($feedbackMessage)
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-800 dark:text-emerald-300 flex items-center justify-between">
            <span>{{ $feedbackMessage }}</span>
            <button wire:click="$set('feedbackMessage', null)" class="text-emerald-600 hover:text-emerald-800 font-bold">&times;</button>
        </div>
    @endif

    @if($feedbackError)
        <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs text-rose-800 dark:text-rose-300 flex items-center justify-between">
            <span>{{ $feedbackError }}</span>
            <button wire:click="$set('feedbackError', null)" class="text-rose-600 hover:text-rose-800 font-bold">&times;</button>
        </div>
    @endif

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
            wire:click="setTab('tokens')"
            class="pb-2.5 px-2 border-b-2 font-mono uppercase tracking-wide transition flex items-center gap-2 {{ $activeTab === 'tokens' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}"
        >
            <span>API Tokens ({{ $tokens->count() }})</span>
        </button>

        <button
            wire:click="setTab('integrations')"
            class="pb-2.5 px-2 border-b-2 font-mono uppercase tracking-wide transition flex items-center gap-2 {{ $activeTab === 'integrations' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}"
        >
            <span>Integrations ({{ $integrations->count() }})</span>
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

    <!-- Tab: API Tokens & Scopes -->
    @if($activeTab === 'tokens')
        <div class="space-y-4">
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Active Scoped API Tokens</h3>
                        <p class="text-[11px] text-slate-500">Tokens authenticate external automation tools, discovery agents, and IT systems.</p>
                    </div>
                </div>

                @if($tokens->isEmpty())
                    <div class="p-8 text-center text-xs text-slate-400">
                        No API tokens generated yet. Click "Generate API Token" above to issue one.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs font-mono">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-700 text-slate-500 uppercase text-[11px]">
                                    <th class="py-3 px-4">Token Name</th>
                                    <th class="py-3 px-4">Prefix</th>
                                    <th class="py-3 px-4">Scopes</th>
                                    <th class="py-3 px-4">Rate Limit</th>
                                    <th class="py-3 px-4">Last Used</th>
                                    <th class="py-3 px-4">Status / Expiry</th>
                                    <th class="py-3 px-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 text-[11px]">
                                @foreach($tokens as $t)
                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition">
                                        <td class="py-3 px-4 font-sans font-semibold text-slate-900 dark:text-white">
                                            {{ $t->name }}
                                        </td>
                                        <td class="py-3 px-4 text-slate-500">
                                            {{ $t->token_prefix }}...
                                        </td>
                                        <td class="py-3 px-4 font-sans">
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($t->scopes_json ?? [] as $scope)
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                                                        {{ $scope }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 text-slate-500">
                                            {{ $t->rate_limit_per_minute }} req/min
                                        </td>
                                        <td class="py-3 px-4 text-slate-500">
                                            {{ $t->last_used_at ? $t->last_used_at->diffForHumans() : 'Never' }}
                                        </td>
                                        <td class="py-3 px-4">
                                            @if($t->revoked_at)
                                                <span class="px-2 py-0.5 rounded text-[10px] bg-rose-100 text-rose-800 font-sans font-semibold">Revoked</span>
                                            @elseif($t->expires_at && $t->expires_at->isPast())
                                                <span class="px-2 py-0.5 rounded text-[10px] bg-amber-100 text-amber-800 font-sans font-semibold">Expired</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded text-[10px] bg-emerald-100 text-emerald-800 font-sans font-semibold">Active</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-right">
                                            @if(! $t->revoked_at)
                                                <button
                                                    wire:click="revokeApiToken({{ $t->id }})"
                                                    class="px-2.5 py-1 text-[11px] rounded bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 font-sans font-medium"
                                                >
                                                    Revoke
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Tab: External Integrations -->
    @if($activeTab === 'integrations')
        <div class="space-y-4">
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">External Network Synchronization Connectors</h3>
                        <p class="text-[11px] text-slate-500">Read-only snapshots from DHCP servers and DNS infrastructure (Section 13.3).</p>
                    </div>
                </div>

                @if($integrations->isEmpty())
                    <div class="p-8 text-center text-xs text-slate-400">
                        No external integrations registered yet.
                    </div>
                @else
                    <div class="divide-y divide-slate-100 dark:divide-slate-700/60">
                        @foreach($integrations as $intg)
                            <div class="p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-blue-100 text-blue-800 uppercase font-semibold">
                                            {{ $intg->type }}
                                        </span>
                                        <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ $intg->name }}</h4>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-1">
                                        Site: {{ $intg->site->name ?? 'Enterprise Campus' }} &bull;
                                        Last Sync: {{ $intg->last_sync_at ? $intg->last_sync_at->diffForHumans() : 'Never' }}
                                        @if($intg->last_sync_status)
                                            ({{ $intg->last_sync_status }})
                                        @endif
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        wire:click="triggerSync({{ $intg->id }})"
                                        class="px-3 py-1.5 text-xs rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 font-semibold"
                                    >
                                        Trigger Read-Only Sync
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Create Token Modal -->
    @if($showTokenModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 max-w-lg w-full p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Generate Scoped API Token</h3>
                    <button wire:click="$set('showTokenModal', false)" type="button" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                @if($newlyGeneratedPlainToken)
                    <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 space-y-2">
                        <p class="text-xs font-bold text-amber-800 dark:text-amber-300">
                            Token Created! Copy this secret token now.
                        </p>
                        <p class="text-[11px] text-amber-700 dark:text-amber-400">
                            It will never be displayed again. For security, only the SHA-256 hash is stored in the database.
                        </p>
                        <div class="p-2.5 rounded-lg bg-slate-900 text-emerald-400 font-mono text-xs break-all select-all">
                            {{ $newlyGeneratedPlainToken }}
                        </div>
                        <button
                            wire:click="$set('showTokenModal', false)"
                            type="button"
                            class="w-full mt-2 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold"
                        >
                            I have copied my token
                        </button>
                    </div>
                @else
                    <form wire:submit.prevent="createApiToken" class="space-y-3.5 text-xs">
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Token Name / Client Description *</label>
                            <input
                                wire:model="tokenName"
                                type="text"
                                placeholder="e.g. Monitoring Automation Service"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100"
                            />
                            @error('tokenName') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Expiration Period</label>
                            <select
                                wire:model="tokenExpiresInDays"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100"
                            >
                                <option value="30">30 Days</option>
                                <option value="60">60 Days</option>
                                <option value="90">90 Days</option>
                                <option value="180">180 Days</option>
                                <option value="365">1 Year</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Granted Token Scopes</label>
                            <div class="grid grid-cols-2 gap-2 text-[11px] font-mono">
                                <label class="flex items-center gap-1.5"><input type="checkbox" wire:model="tokenScopes" value="read:subnets"> read:subnets</label>
                                <label class="flex items-center gap-1.5"><input type="checkbox" wire:model="tokenScopes" value="read:ips"> read:ips</label>
                                <label class="flex items-center gap-1.5"><input type="checkbox" wire:model="tokenScopes" value="write:allocations"> write:allocations</label>
                                <label class="flex items-center gap-1.5"><input type="checkbox" wire:model="tokenScopes" value="write:requests"> write:requests</label>
                                <label class="flex items-center gap-1.5"><input type="checkbox" wire:model="tokenScopes" value="read:devices"> read:devices</label>
                                <label class="flex items-center gap-1.5"><input type="checkbox" wire:model="tokenScopes" value="read:vlans"> read:vlans</label>
                                <label class="flex items-center gap-1.5"><input type="checkbox" wire:model="tokenScopes" value="read:discovery"> read:discovery</label>
                                <label class="flex items-center gap-1.5"><input type="checkbox" wire:model="tokenScopes" value="write:discovery"> write:discovery</label>
                                <label class="flex items-center gap-1.5"><input type="checkbox" wire:model="tokenScopes" value="read:alerts"> read:alerts</label>
                                <label class="flex items-center gap-1.5"><input type="checkbox" wire:model="tokenScopes" value="write:alerts"> write:alerts</label>
                            </div>
                            @error('tokenScopes') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-700">
                            <button wire:click="$set('showTokenModal', false)" type="button" class="px-3.5 py-2 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-semibold">
                                Generate Token
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endif
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
