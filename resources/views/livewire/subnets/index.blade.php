<div class="space-y-6">
    <!-- Header / Context Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 font-mono mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600">KYAMCH IPAM</a>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-semibold">Subnet Hierarchy</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">Subnet & CIDR Hierarchy Management</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Authoritative parent-child IP prefix blocks, non-overlapping routing containers and host allocation capacity.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <button
                wire:click="openCalculator()"
                type="button"
                class="inline-flex items-center gap-2 px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold shadow-xs transition"
            >
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                CIDR Calculator
            </button>

            @can('create', App\Models\Subnet::class)
                <button
                    wire:click="openCreateModal({{ $selectedParentId }})"
                    type="button"
                    class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold shadow-xs transition"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ $selectedParent ? 'Add Child Prefix' : 'Add Root Subnet' }}
                </button>
            @endcan
        </div>
    </div>

    <!-- Hierarchy Breadcrumbs -->
    <div class="bg-slate-50 dark:bg-slate-900/60 rounded-xl px-4 py-3 border border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs">
        <div class="flex items-center gap-2 overflow-x-auto font-mono">
            <button
                wire:click="drillDown(null)"
                type="button"
                class="flex items-center gap-1.5 px-2.5 py-1 rounded {{ $selectedParentId === null ? 'bg-emerald-600 text-white font-bold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-800' }}"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>Root Aggregates</span>
            </button>

            @if($selectedParent)
                @foreach($selectedParent->ancestors() as $ancestor)
                    <span class="text-slate-400">/</span>
                    <button
                        wire:click="drillDown({{ $ancestor->id }})"
                        type="button"
                        class="px-2 py-1 rounded text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-800"
                    >
                        {{ $ancestor->cidr }}
                    </button>
                @endforeach
                <span class="text-slate-400">/</span>
                <span class="px-2.5 py-1 rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 font-bold border border-emerald-300 dark:border-emerald-700">
                    {{ $selectedParent->cidr }} ({{ $selectedParent->name }})
                </span>
            @endif
        </div>

        <div class="flex items-center gap-2">
            <span class="text-[11px] text-slate-500 font-mono">View:</span>
            <button
                wire:click="$set('viewMode', 'hierarchy')"
                type="button"
                class="px-2 py-1 rounded text-[11px] font-mono {{ $viewMode === 'hierarchy' ? 'bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold' : 'text-slate-500' }}"
            >
                Hierarchy Tree
            </button>
            <button
                wire:click="$set('viewMode', 'flat')"
                type="button"
                class="px-2 py-1 rounded text-[11px] font-mono {{ $viewMode === 'flat' ? 'bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold' : 'text-slate-500' }}"
            >
                Flat Table
            </button>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-wrap gap-3 items-center justify-between">
        <div class="w-full sm:w-72">
            <input
                wire:model.live.debounce.300ms="search"
                type="text"
                placeholder="Search by CIDR (e.g. 10.10.0.0) or name..."
                class="w-full px-3 py-2 text-xs rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500"
            />
        </div>

        <div class="flex flex-wrap items-center gap-2.5 text-xs">
            <div>
                <select
                    wire:model.live="vrfFilter"
                    class="px-2.5 py-1.5 text-xs rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 font-mono"
                >
                    <option value="all">All VRFs</option>
                    @foreach($vrfs as $vrf)
                        <option value="{{ $vrf->id }}">{{ $vrf->code }} ({{ $vrf->name }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select
                    wire:model.live="familyFilter"
                    class="px-2.5 py-1.5 text-xs rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 font-mono"
                >
                    <option value="all">All Families</option>
                    <option value="4">IPv4 Only</option>
                    <option value="6">IPv6 Only</option>
                </select>
            </div>

            <div>
                <select
                    wire:model.live="typeFilter"
                    class="px-2.5 py-1.5 text-xs rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500"
                >
                    <option value="all">All Subnet Types</option>
                    @foreach($subnetTypes as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Subnets List / Tree -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-mono uppercase text-[11px]">
                        <th class="py-3 px-4">CIDR Prefix</th>
                        <th class="py-3 px-4">Subnet Name</th>
                        <th class="py-3 px-4">VRF Context</th>
                        <th class="py-3 px-4">Subnet Type</th>
                        <th class="py-3 px-4">VLAN Link</th>
                        <th class="py-3 px-4">Usable Capacity</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @forelse($subnets as $subnet)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition">
                            <td class="py-3 px-4 font-mono font-bold text-slate-900 dark:text-white whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-emerald-600 dark:text-emerald-400">
                                        {{ $subnet->cidr }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 uppercase font-mono">IPv{{ $subnet->ip_version }}</span>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-medium text-slate-800 dark:text-slate-200">{{ $subnet->name }}</div>
                                <div class="text-[11px] text-slate-500 flex items-center gap-2 mt-0.5">
                                    @if($subnet->site)
                                        <span>{{ $subnet->site->name }}</span>
                                    @endif
                                    @if($subnet->department)
                                        <span>• {{ $subnet->department->name }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-4 font-mono text-[11px] text-slate-700 dark:text-slate-300">
                                <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-700">
                                    {{ $subnet->vrf->code }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-medium border {{ $subnet->subnet_type->badgeClasses() }}">
                                    {{ $subnet->subnet_type->label() }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                @if($subnet->vlan)
                                    <span class="px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-950 text-blue-700 dark:text-blue-300 font-mono text-[10px] border border-blue-200 dark:border-blue-800">
                                        VLAN {{ $subnet->vlan->vlan_id }}
                                    </span>
                                @else
                                    <span class="text-slate-400 font-mono text-[11px]">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-700 dark:text-slate-300">
                                <div>{{ $subnet->usableAddressesFormatted() }} usable</div>
                                <div class="text-[10px] text-slate-400">{{ $subnet->totalAddressesFormatted() }} total</div>
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    @if($subnet->children_count > 0 || $subnet->isAggregate())
                                        <button
                                            wire:click="drillDown({{ $subnet->id }})"
                                            type="button"
                                            class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-[11px] font-mono transition"
                                            title="Drill down into child subnets"
                                        >
                                            ▼ {{ $subnet->children_count }} Child{{ $subnet->children_count === 1 ? '' : 'ren' }}
                                        </button>
                                    @endif

                                    <button
                                        wire:click="openCalculator('{{ $subnet->cidr }}')"
                                        type="button"
                                        class="p-1 text-slate-500 hover:text-emerald-600 rounded hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                                        title="Subnet Calculator"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    </button>

                                    @can('update', $subnet)
                                        <button
                                            wire:click="openSplitModal({{ $subnet->id }})"
                                            type="button"
                                            class="p-1 text-slate-500 hover:text-blue-600 rounded hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                                            title="Split Subnet Preview"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                                        </button>

                                        <button
                                            wire:click="openEditModal({{ $subnet->id }})"
                                            type="button"
                                            class="p-1 text-slate-500 hover:text-emerald-600 rounded hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                                            title="Edit Subnet"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                    @endcan

                                    @can('delete', $subnet)
                                        <button
                                            wire:click="confirmDelete({{ $subnet->id }})"
                                            type="button"
                                            class="p-1 text-slate-500 hover:text-rose-600 rounded hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                                            title="Delete Subnet"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-xs text-slate-400">
                                @if($selectedParent)
                                    No child prefixes defined inside parent {{ $selectedParent->cidr }}.
                                @else
                                    No subnets registered in this VRF context yet.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200 dark:border-slate-700">
            {{ $subnets->links() }}
        </div>
    </div>

    <!-- Create / Edit Subnet Modal -->
    @if($showSubnetModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 max-w-xl w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        {{ $isEditing ? 'Edit Subnet Prefix' : ($subnet_parent_id ? 'Add Child Subnet' : 'Create Root Subnet') }}
                    </h3>
                    <button wire:click="$set('showSubnetModal', false)" type="button" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form wire:submit.prevent="saveSubnet" class="space-y-3.5 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">VRF Context *</label>
                            <select
                                wire:model="subnet_vrf_id"
                                @if($isEditing) disabled @endif
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono disabled:opacity-50"
                            >
                                @foreach($vrfs as $vrf)
                                    <option value="{{ $vrf->id }}">{{ $vrf->code }} ({{ $vrf->name }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Subnet Type *</label>
                            <select
                                wire:model="subnet_type"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100"
                            >
                                @foreach($subnetTypes as $type)
                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if($subnet_parent_id || count($parentCandidates) > 0)
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="font-medium text-slate-700 dark:text-slate-300">Parent Aggregate Subnet</label>
                                @if($subnet_parent_id)
                                    <button
                                        wire:click="suggestNextChild"
                                        type="button"
                                        class="text-emerald-600 hover:text-emerald-500 font-mono text-[11px] font-bold"
                                    >
                                        ⚡ Suggest Next Free Block
                                    </button>
                                @endif
                            </div>
                            <select
                                wire:model="subnet_parent_id"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono"
                            >
                                <option value="">None (Top-Level Root Subnet)</option>
                                @foreach($parentCandidates as $candidate)
                                    <option value="{{ $candidate->id }}">{{ $candidate->cidr }} — {{ $candidate->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">CIDR Prefix (e.g. 10.20.0.0/16 or 2001:db8::/32) *</label>
                            <input
                                wire:model="subnet_cidr"
                                type="text"
                                @if($isEditing) disabled @endif
                                placeholder="10.20.0.0/24"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono disabled:opacity-50 focus:ring-2 focus:ring-emerald-500"
                            />
                            @error('subnet_cidr') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Subnet Name *</label>
                            <input
                                wire:model="subnet_name"
                                type="text"
                                placeholder="e.g. ICU Telemetry & Bedside Sensors"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500"
                            />
                            @error('subnet_name') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Attached VLAN</label>
                            <select
                                wire:model="subnet_vlan_id"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono"
                            >
                                <option value="">None (Unattached)</option>
                                @foreach($vlans as $vlan)
                                    <option value="{{ $vlan->id }}">VLAN {{ $vlan->vlan_id }} ({{ $vlan->name }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Campus / Site</label>
                            <select
                                wire:model="subnet_site_id"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100"
                            >
                                <option value="">Unassigned</option>
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}">{{ $site->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Department</label>
                            <select
                                wire:model="subnet_department_id"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100"
                            >
                                <option value="">Unassigned</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Description / Routing Purpose</label>
                        <textarea
                            wire:model="subnet_description"
                            rows="2"
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100"
                        ></textarea>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <label class="flex items-center text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input wire:model="subnet_allocation_enabled" type="checkbox" class="w-4 h-4 rounded text-emerald-600" />
                            <span class="ml-2 font-medium">Enable IP address allocation in this subnet</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-700">
                        <button wire:click="$set('showSubnetModal', false)" type="button" class="px-3.5 py-2 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-semibold">
                            {{ $isEditing ? 'Save Changes' : 'Create Subnet' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- CIDR Calculator Modal -->
    @if($showCalculatorModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 max-w-xl w-full p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span>Enterprise Subnet & CIDR Calculator</span>
                    </h3>
                    <button wire:click="$set('showCalculatorModal', false)" type="button" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <div class="space-y-4 text-xs">
                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Enter IPv4 or IPv6 CIDR:</label>
                        <div class="flex gap-2">
                            <input
                                wire:model="calcInputCidr"
                                wire:keydown.enter="runCalculation"
                                type="text"
                                placeholder="e.g. 172.16.0.0/20 or 2001:db8::/64"
                                class="flex-1 px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono focus:ring-2 focus:ring-emerald-500"
                            />
                            <button
                                wire:click="runCalculation"
                                type="button"
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold rounded-lg"
                            >
                                Calculate
                            </button>
                        </div>
                        @if($calcError)
                            <p class="text-rose-500 text-[11px] mt-1">{{ $calcError }}</p>
                        @endif
                    </div>

                    @if($calcResults)
                        <div class="grid grid-cols-2 gap-3 p-4 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 font-mono">
                            <div>
                                <span class="text-slate-400 text-[11px] block">Canonical CIDR</span>
                                <span class="text-emerald-600 font-bold text-sm">{{ $calcResults['cidr'] }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[11px] block">Address Family</span>
                                <span class="font-bold">IPv{{ $calcResults['version'] }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[11px] block">Network Base Address</span>
                                <span class="font-semibold">{{ $calcResults['network'] }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[11px] block">Broadcast Address</span>
                                <span class="font-semibold">{{ $calcResults['broadcast'] ?? 'N/A (IPv6 / RFC 3021)' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[11px] block">First Usable IP</span>
                                <span class="font-semibold">{{ $calcResults['first_usable'] }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[11px] block">Last Usable IP</span>
                                <span class="font-semibold">{{ $calcResults['last_usable'] }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[11px] block">Usable Host Capacity</span>
                                <span class="text-emerald-600 font-bold">{{ number_format((float) $calcResults['usable_addresses']) }} Hosts</span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[11px] block">Total IP Space</span>
                                <span class="font-semibold">{{ number_format((float) $calcResults['total_addresses']) }}</span>
                            </div>
                            @if($calcResults['netmask'])
                                <div>
                                    <span class="text-slate-400 text-[11px] block">Subnet Mask</span>
                                    <span class="font-semibold">{{ $calcResults['netmask'] }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 text-[11px] block">Wildcard Mask</span>
                                    <span class="font-semibold">{{ $calcResults['wildcard'] }}</span>
                                </div>
                            @endif
                            <div class="col-span-2">
                                <span class="text-slate-400 text-[11px] block">Fixed Hexadecimal Bounds (DB Indexed)</span>
                                <span class="text-[10px] text-slate-500 break-all">{{ $calcResults['start_hex'] }} — {{ $calcResults['end_hex'] }}</span>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex justify-end pt-2 border-t border-slate-200 dark:border-slate-700">
                    <button wire:click="$set('showCalculatorModal', false)" type="button" class="px-4 py-2 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-semibold">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Split Subnet Preview Modal -->
    @if($showSplitModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 max-w-2xl w-full p-6 space-y-4 max-h-[85vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        Subnet Split Planning (Preview Only)
                    </h3>
                    <button wire:click="$set('showSplitModal', false)" type="button" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <div class="space-y-3 text-xs">
                    <p class="text-slate-500">
                        Preview splitting this prefix into smaller equal child blocks. Per architectural invariant ADR-004, active address allocations cannot be automatically orphaned; splitting is <strong>preview-only</strong> until migration mapping is executed.
                    </p>

                    <div class="flex items-center gap-3">
                        <label class="font-medium text-slate-700 dark:text-slate-300">Target Child Prefix Length:</label>
                        <input
                            wire:model.live="splitTargetPrefix"
                            wire:change="generateSplitPreview"
                            type="number"
                            min="1"
                            max="128"
                            class="w-24 px-3 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 font-mono text-xs"
                        />
                        <button
                            wire:click="generateSplitPreview"
                            type="button"
                            class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 font-semibold"
                        >
                            Refresh Preview
                        </button>
                    </div>

                    @if($splitError)
                        <div class="p-3 bg-rose-50 text-rose-700 rounded-lg text-xs">{{ $splitError }}</div>
                    @endif

                    @if(count($splitPreviewList) > 0)
                        <div class="border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden max-h-60 overflow-y-auto">
                            <table class="w-full text-left font-mono text-xs">
                                <thead class="bg-slate-50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-700 text-[11px] text-slate-400">
                                    <tr>
                                        <th class="p-2">#</th>
                                        <th class="p-2">Child CIDR</th>
                                        <th class="p-2">Hex Range</th>
                                        <th class="p-2">Capacity</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                                    @foreach($splitPreviewList as $idx => $child)
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                                            <td class="p-2 text-slate-400">{{ $idx + 1 }}</td>
                                            <td class="p-2 text-emerald-600 font-bold">{{ $child['cidr'] }}</td>
                                            <td class="p-2 text-[10px] text-slate-500">{{ substr($child['start_hex'], -8) }} .. {{ substr($child['end_hex'], -8) }}</td>
                                            <td class="p-2 text-slate-600 dark:text-slate-300">{{ number_format((float) $child['usable_addresses']) }} hosts</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="text-[11px] text-slate-400 font-mono text-right">Yields {{ count($splitPreviewList) }} child subnets</p>
                    @endif
                </div>

                <div class="flex justify-end pt-3 border-t border-slate-200 dark:border-slate-700">
                    <button wire:click="$set('showSplitModal', false)" type="button" class="px-4 py-2 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-semibold">
                        Done Preview
                    </button>
                </div>
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
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white">Delete Subnet?</h4>
                        <p class="text-xs text-slate-500 mt-0.5">Subnets with child subnets or active allocations cannot be deleted.</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-700 text-xs">
                    <button wire:click="$set('showDeleteModal', false)" type="button" class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300">
                        Cancel
                    </button>
                    <button wire:click="deleteSubnet" type="button" class="px-3.5 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-500 text-white font-semibold">
                        Confirm Delete
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
