<div class="space-y-6">
    <!-- Header / Context Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 font-mono mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600">KYAMCH IPAM</a>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-semibold">Subnet Hierarchy</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">Subnet &amp; CIDR Hierarchy Management</h1>
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

            <button
                wire:click="openCreateModal({{ $selectedParentId }})"
                type="button"
                class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold shadow-xs transition"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ $selectedParent ? 'Add Child Prefix' : 'Add Root Subnet' }}
            </button>
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

            @if($viewMode === 'hierarchy')
                <div class="h-4 w-px bg-slate-300 dark:bg-slate-700 mx-1"></div>
                <button
                    wire:click="expandAll"
                    type="button"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-[11px] font-mono text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 border border-emerald-300 dark:border-emerald-800 font-medium transition"
                    title="Expand all child subnets"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    <span>Expand All</span>
                </button>
                <button
                    wire:click="collapseAll"
                    type="button"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-[11px] font-mono text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-700 font-medium transition"
                    title="Collapse all child subnets"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                    <span>Collapse All</span>
                </button>
            @endif
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
                    wire:model.live="siteFilter"
                    class="px-2.5 py-1.5 text-xs rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-medium focus:ring-2 focus:ring-emerald-500"
                >
                    <option value="">All Campuses</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->name }} ({{ $site->code }})</option>
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
                        <th class="py-3 px-4">Subnet Type</th>
                        <th class="py-3 px-4">VLAN Link</th>
                        <th class="py-3 px-4">Usable Capacity</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @forelse($subnets as $subnet)
                        @include('livewire.subnets._subnet-row', [
                            'subnet' => $subnet,
                            'depth' => 0,
                            'expandedSubnetIds' => $expandedSubnetIds,
                            'childrenByParent' => $childrenByParent,
                            'selectedParentId' => $selectedParentId,
                        ])
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 px-4 text-center text-slate-400">
                                No subnets found matching the selected filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40">
            {{ $subnets->links() }}
        </div>
    </div>

    <!-- Create / Edit Subnet Modal -->
    @if($showSubnetModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 max-w-xl w-full p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        {{ $isEditing ? 'Edit Subnet' : ($subnet_parent_id ? 'Add Child Subnet' : 'Create Root Subnet') }}
                    </h3>
                    <button wire:click="$set('showSubnetModal', false)" type="button" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form wire:submit="saveSubnet" class="space-y-4 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Parent Subnet</label>
                            <select
                                wire:model.live="subnet_parent_id"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono"
                            >
                                <option value="">None (Top-Level Container)</option>
                                @foreach($parentCandidates as $parent)
                                    <option value="{{ $parent->id }}">{{ $parent->cidr }} ({{ $parent->name }})</option>
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

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="font-medium text-slate-700 dark:text-slate-300">CIDR Prefix *</label>
                                @if($subnet_parent_id && !$isEditing)
                                    <button
                                        wire:click="suggestNextChild"
                                        type="button"
                                        class="text-[11px] text-emerald-600 hover:text-emerald-700 font-medium"
                                    >
                                        Auto-Suggest Free
                                    </button>
                                @endif
                            </div>
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

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Campus / Facility Site</label>
                            <select
                                wire:model="subnet_site_id"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-medium focus:ring-2 focus:ring-emerald-500"
                            >
                                <option value="">Global / Unassigned</option>
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}">{{ $site->name }} ({{ $site->code }})</option>
                                @endforeach
                            </select>
                        </div>

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
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Lifecycle Status *</label>
                            <select
                                wire:model="subnet_status"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100"
                            >
                                <option value="active">Active</option>
                                <option value="planned">Planned</option>
                                <option value="deprecated">Deprecated</option>
                                <option value="retired">Retired</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Allocation Policy</label>
                            <div class="flex items-center h-9">
                                <label class="inline-flex items-center gap-2 cursor-pointer text-xs">
                                    <input type="checkbox" wire:model="subnet_allocation_enabled" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                    <span>Enable IP Allocations</span>
                                </label>
                            </div>
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
                                placeholder="10.10.0.0/24"
                                class="flex-1 px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 font-mono text-slate-900 dark:text-slate-100"
                            />
                            <button
                                wire:click="runCalculation"
                                type="button"
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-semibold"
                            >
                                Calculate
                            </button>
                        </div>
                        @if($calcError)
                            <p class="text-rose-500 text-[11px] mt-1">{{ $calcError }}</p>
                        @endif
                    </div>

                    @if($calcResults)
                        <div class="bg-slate-50 dark:bg-slate-900 rounded-lg p-4 font-mono text-xs space-y-2 border border-slate-200 dark:border-slate-800">
                            <div class="flex justify-between py-1 border-b border-slate-200 dark:border-slate-800">
                                <span class="text-slate-500">Canonical Prefix:</span>
                                <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $calcResults['cidr'] }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-200 dark:border-slate-800">
                                <span class="text-slate-500">Network Address:</span>
                                <span class="font-bold">{{ $calcResults['network'] }}</span>
                            </div>
                            @if($calcResults['netmask'])
                                <div class="flex justify-between py-1 border-b border-slate-200 dark:border-slate-800">
                                    <span class="text-slate-500">Subnet Mask:</span>
                                    <span>{{ $calcResults['netmask'] }}</span>
                                </div>
                            @endif
                            @if($calcResults['broadcast'])
                                <div class="flex justify-between py-1 border-b border-slate-200 dark:border-slate-800">
                                    <span class="text-slate-500">Broadcast Address:</span>
                                    <span>{{ $calcResults['broadcast'] }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between py-1 border-b border-slate-200 dark:border-slate-800">
                                <span class="text-slate-500">First Usable Host:</span>
                                <span class="text-blue-600 dark:text-blue-400 font-semibold">{{ $calcResults['first_usable'] }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-200 dark:border-slate-800">
                                <span class="text-slate-500">Last Usable Host:</span>
                                <span class="text-blue-600 dark:text-blue-400 font-semibold">{{ $calcResults['last_usable'] }}</span>
                            </div>
                            <div class="flex justify-between py-1">
                                <span class="text-slate-500">Total / Usable Hosts:</span>
                                <span class="font-bold text-slate-900 dark:text-white">
                                    {{ number_format((float)$calcResults['usable_addresses']) }} usable ({{ number_format((float)$calcResults['total_addresses']) }} total)
                                </span>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex justify-end pt-2">
                    <button wire:click="$set('showCalculatorModal', false)" type="button" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Split Preview Modal -->
    @if($showSplitModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 max-w-lg w-full p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        <span>Subnet Splitting Preview</span>
                    </h3>
                    <button wire:click="$set('showSplitModal', false)" type="button" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <div class="space-y-4 text-xs">
                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Target Split Prefix Length (e.g. /26, /27):</label>
                        <div class="flex gap-2">
                            <input
                                wire:model.live.debounce.300ms="splitTargetPrefix"
                                wire:change="generateSplitPreview"
                                type="number"
                                min="1"
                                max="128"
                                class="w-24 px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 font-mono text-slate-900 dark:text-slate-100"
                            />
                            <span class="text-slate-500 self-center">Produces {{ count($splitPreviewList) }} child segments</span>
                        </div>
                        @if($splitError)
                            <p class="text-rose-500 text-[11px] mt-1">{{ $splitError }}</p>
                        @endif
                    </div>

                    <div class="max-h-60 overflow-y-auto space-y-1.5 font-mono text-xs">
                        @foreach($splitPreviewList as $child)
                            <div class="p-2 rounded bg-slate-50 dark:bg-slate-900 flex items-center justify-between border border-slate-200 dark:border-slate-800">
                                <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $child['cidr'] }}</span>
                                <span class="text-slate-500 text-[11px]">{{ $child['usable_addresses'] ?? $child['usable_hosts'] ?? '0' }} usable</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button wire:click="$set('showSplitModal', false)" type="button" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 max-w-sm w-full p-5 space-y-4 text-xs">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 text-rose-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>Delete Subnet</span>
                </h3>
                <p class="text-slate-600 dark:text-slate-300">
                    Are you sure you want to permanently delete this subnet? This action is irreversible. Subnets with active child subnets or allocated IP addresses cannot be deleted.
                </p>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button wire:click="$set('showDeleteModal', false)" type="button" class="px-3.5 py-2 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300">
                        Cancel
                    </button>
                    <button wire:click="deleteSubnet" type="button" class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-500 text-white font-semibold">
                        Confirm Delete
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
