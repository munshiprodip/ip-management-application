<div class="space-y-6">
    <!-- Header / Context Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 font-mono mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600">KYAMCH IPAM</a>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-semibold">VLANs & Layer 2</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">Layer 2 Domains & 802.1Q VLANs</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                L2 broadcast domain namespaces and security-zoned VLAN segmentation.
            </p>
        </div>

        <div class="flex items-center gap-2">
            @if($activeTab === 'vlans')
                @can('create', App\Models\Vlan::class)
                    <button
                        wire:click="openCreateVlanModal"
                        type="button"
                        class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold shadow-xs transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Create VLAN
                    </button>
                @endcan
            @else
                @can('create', App\Models\L2Domain::class)
                    <button
                        wire:click="openCreateDomainModal"
                        type="button"
                        class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold shadow-xs transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Create L2 Domain
                    </button>
                @endcan
            @endif
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex border-b border-slate-200 dark:border-slate-700 text-xs font-medium space-x-4">
        <button
            wire:click="setTab('vlans')"
            class="pb-2.5 px-2 border-b-2 font-mono uppercase tracking-wide transition flex items-center gap-2 {{ $activeTab === 'vlans' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            <span>VLANs (802.1Q)</span>
        </button>

        <button
            wire:click="setTab('l2_domains')"
            class="pb-2.5 px-2 border-b-2 font-mono uppercase tracking-wide transition flex items-center gap-2 {{ $activeTab === 'l2_domains' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            <span>Layer 2 Broadcast Domains</span>
        </button>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-wrap gap-3 items-center justify-between">
        <div class="w-full sm:w-72">
            <input
                wire:model.live.debounce.300ms="search"
                type="text"
                placeholder="{{ $activeTab === 'vlans' ? 'Search VLAN ID or name...' : 'Search L2 domain code or name...' }}"
                class="w-full px-3 py-2 text-xs rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500"
            />
        </div>

        <div class="flex flex-wrap items-center gap-2.5 text-xs">
            @if($activeTab === 'vlans')
                <div>
                    <select
                        wire:model.live="domainFilter"
                        class="px-2.5 py-1.5 text-xs rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500"
                    >
                        <option value="all">All L2 Domains</option>
                        @foreach($l2DomainsList as $domain)
                            <option value="{{ $domain->id }}">{{ $domain->code }} ({{ $domain->site->name }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <select
                        wire:model.live="zoneFilter"
                        class="px-2.5 py-1.5 text-xs rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500"
                    >
                        <option value="all">All Security Zones</option>
                        @foreach($securityZones as $zone)
                            <option value="{{ $zone->value }}">{{ $zone->label() }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <select
                    wire:model.live="statusFilter"
                    class="px-2.5 py-1.5 text-xs rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500"
                >
                    <option value="all">All Statuses</option>
                    <option value="active">Active Only</option>
                    @if($activeTab === 'vlans')
                        <option value="reserved">Reserved Only</option>
                        <option value="deprecated">Deprecated Only</option>
                    @else
                        <option value="retired">Retired Only</option>
                    @endif
                </select>
            </div>
        </div>
    </div>

    <!-- Active Tab: VLANs Table -->
    @if($activeTab === 'vlans')
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-mono uppercase text-[11px]">
                            <th class="py-3 px-4">VLAN ID</th>
                            <th class="py-3 px-4">VLAN Name</th>
                            <th class="py-3 px-4">L2 Domain / Campus</th>
                            <th class="py-3 px-4">Security Zone</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                        @forelse($items as $vlan)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition">
                                <td class="py-3 px-4 font-mono font-bold text-slate-900 dark:text-white">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200 font-mono">
                                        VLAN {{ $vlan->vlan_id }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-medium text-slate-800 dark:text-slate-200">{{ $vlan->name }}</div>
                                    @if($vlan->description)
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate max-w-xs">{{ $vlan->description }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-mono text-[11px] text-slate-800 dark:text-slate-200 font-semibold">
                                        {{ $vlan->l2Domain->code }}
                                    </div>
                                    <div class="text-[10px] text-slate-500">
                                        {{ $vlan->l2Domain->site->name }}
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-medium border {{ $vlan->security_zone->badgeClasses() }}">
                                        {{ $vlan->security_zone->label() }}
                                    </span>
                                    @if($vlan->isMedicalRestricted())
                                        <span class="block mt-1 text-[9px] font-mono text-rose-500 font-bold uppercase">
                                            ● PROBE RESTRICTED
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-medium border {{ $vlan->status->badgeClasses() }}">
                                        {{ $vlan->status->label() }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        @can('update', $vlan)
                                            <button
                                                wire:click="openEditVlanModal({{ $vlan->id }})"
                                                type="button"
                                                class="p-1 text-slate-500 hover:text-emerald-600 rounded hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                                                title="Edit VLAN"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                        @endcan

                                        @can('delete', $vlan)
                                            <button
                                                wire:click="confirmDelete('vlan', {{ $vlan->id }})"
                                                type="button"
                                                class="p-1 text-slate-500 hover:text-rose-600 rounded hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                                                title="Delete VLAN"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-xs text-slate-400">
                                    No VLANs found matching your query.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-200 dark:border-slate-700">
                {{ $items->links() }}
            </div>
        </div>
    @else
        <!-- Active Tab: Layer 2 Domains Table -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-mono uppercase text-[11px]">
                            <th class="py-3 px-4">L2 Domain Code</th>
                            <th class="py-3 px-4">Domain Name</th>
                            <th class="py-3 px-4">Campus / Site</th>
                            <th class="py-3 px-4">Attached VLANs</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                        @forelse($items as $domain)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition">
                                <td class="py-3 px-4 font-mono font-bold text-slate-900 dark:text-white">
                                    {{ $domain->code }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-medium text-slate-800 dark:text-slate-200">{{ $domain->name }}</div>
                                    @if($domain->description)
                                        <div class="text-[11px] text-slate-500 truncate max-w-xs">{{ $domain->description }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-slate-700 dark:text-slate-300">
                                    {{ $domain->site->name }}
                                </td>
                                <td class="py-3 px-4 font-mono">
                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200">
                                        {{ $domain->vlans_count }} VLANs
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-medium {{ $domain->status === 'active' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-100 text-slate-700' }}">
                                        {{ ucfirst($domain->status) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        @can('update', $domain)
                                            <button
                                                wire:click="openEditDomainModal({{ $domain->id }})"
                                                type="button"
                                                class="p-1 text-slate-500 hover:text-emerald-600 rounded hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                                                title="Edit L2 Domain"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                        @endcan

                                        @can('delete', $domain)
                                            <button
                                                wire:click="confirmDelete('l2_domain', {{ $domain->id }})"
                                                type="button"
                                                class="p-1 text-slate-500 hover:text-rose-600 rounded hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                                                title="Delete L2 Domain"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-xs text-slate-400">
                                    No Layer 2 domains found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-200 dark:border-slate-700">
                {{ $items->links() }}
            </div>
        </div>
    @endif

    <!-- VLAN Create/Edit Modal -->
    @if($showVlanModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 max-w-lg w-full p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        {{ $isEditingVlan ? 'Edit VLAN Details' : 'Create 802.1Q VLAN' }}
                    </h3>
                    <button wire:click="$set('showVlanModal', false)" type="button" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form wire:submit.prevent="saveVlan" class="space-y-3.5 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Layer 2 Domain *</label>
                            <select
                                wire:model="vlan_l2_domain_id"
                                @if($isEditingVlan) disabled @endif
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 disabled:opacity-50"
                            >
                                @foreach($l2DomainsList as $domain)
                                    <option value="{{ $domain->id }}">{{ $domain->code }} ({{ $domain->site->name }})</option>
                                @endforeach
                            </select>
                            @error('vlan_l2_domain_id') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">VLAN ID (1–4094) *</label>
                            <input
                                wire:model="vlan_number"
                                type="number"
                                min="1"
                                max="4094"
                                placeholder="e.g. 100"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono focus:ring-2 focus:ring-emerald-500"
                            />
                            @error('vlan_number') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">VLAN Name *</label>
                        <input
                            wire:model="vlan_name"
                            type="text"
                            placeholder="e.g. ICU Bedside Patient Monitors"
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500"
                        />
                        @error('vlan_name') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Security Zone *</label>
                            <select
                                wire:model="vlan_security_zone"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500"
                            >
                                @foreach($securityZones as $zone)
                                    <option value="{{ $zone->value }}">{{ $zone->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Status *</label>
                            <select
                                wire:model="vlan_status"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500"
                            >
                                <option value="active">Active</option>
                                <option value="reserved">Reserved</option>
                                <option value="deprecated">Deprecated</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Description / Routing Notes</label>
                        <textarea
                            wire:model="vlan_description"
                            rows="2"
                            placeholder="Connected hospital systems, default gateway, or switch assignments..."
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-700">
                        <button wire:click="$set('showVlanModal', false)" type="button" class="px-3.5 py-2 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-semibold">
                            {{ $isEditingVlan ? 'Save Changes' : 'Register VLAN' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- L2 Domain Create/Edit Modal -->
    @if($showDomainModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 max-w-lg w-full p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        {{ $isEditingDomain ? 'Edit Layer 2 Domain' : 'Create Layer 2 Domain' }}
                    </h3>
                    <button wire:click="$set('showDomainModal', false)" type="button" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form wire:submit.prevent="saveDomain" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Campus / Site *</label>
                        <select
                            wire:model="domain_site_id"
                            @if($isEditingDomain) disabled @endif
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 disabled:opacity-50"
                        >
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}">{{ $site->name }}</option>
                            @endforeach
                        </select>
                        @error('domain_site_id') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Domain Code *</label>
                            <input
                                wire:model="domain_code"
                                type="text"
                                placeholder="e.g. L2-HOSPITAL-CORE"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono uppercase focus:ring-2 focus:ring-emerald-500"
                            />
                            @error('domain_code') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Status *</label>
                            <select
                                wire:model="domain_status"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500"
                            >
                                <option value="active">Active</option>
                                <option value="retired">Retired</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Domain Name *</label>
                        <input
                            wire:model="domain_name"
                            type="text"
                            placeholder="e.g. Main Hospital Core Fabric"
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500"
                        />
                        @error('domain_name') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Description</label>
                        <textarea
                            wire:model="domain_description"
                            rows="2"
                            placeholder="Physical switches or distribution layer covered..."
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-700">
                        <button wire:click="$set('showDomainModal', false)" type="button" class="px-3.5 py-2 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-semibold">
                            {{ $isEditingDomain ? 'Save Changes' : 'Create Domain' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 max-w-sm w-full p-5 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                            Delete {{ $deleteTargetType === 'vlan' ? 'VLAN' : 'L2 Domain' }}?
                        </h4>
                        <p class="text-xs text-slate-500 mt-0.5">This operation is audited. Confirm permanent deletion.</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-700 text-xs">
                    <button wire:click="$set('showDeleteModal', false)" type="button" class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100">
                        Cancel
                    </button>
                    <button wire:click="executeDelete" type="button" class="px-3.5 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-500 text-white font-semibold">
                        Confirm Delete
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
