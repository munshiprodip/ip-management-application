<div class="space-y-6">
    <!-- Inline Flash Notifications (Direct Component Feedback) -->
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
                <span class="text-slate-800 dark:text-slate-200 font-semibold">VLAN Management</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">802.1Q Virtual LAN (VLAN) Management</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Authoritative 802.1Q VLAN tags (1-4094), security zone segmentation, and attached subnet bindings.
            </p>
        </div>

        <div>
            <button
                wire:click="openCreateVlanModal"
                type="button"
                class="w-full sm:w-auto justify-center inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold shadow-xs transition"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Register New VLAN</span>
            </button>
        </div>
    </div>

    <!-- Search & Filters Toolbar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3.5 sm:p-4 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col lg:flex-row gap-3 items-stretch lg:items-center justify-between">
        <div class="w-full lg:w-72">
            <input
                wire:model.live.debounce.300ms="search"
                type="text"
                placeholder="Search by VLAN ID or name..."
                class="w-full px-3 py-2 text-xs rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500"
            />
        </div>

        <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-2.5 text-xs w-full lg:w-auto">
            <div>
                <x-searchable-select 
                    placeholder="All Campuses"
                    searchPlaceholder="Search campus..."
                    containerClass="w-full sm:w-auto min-w-[170px]"
                >
                    <select
                        wire:model.live="siteFilter"
                        class="w-full sm:w-auto px-2.5 py-1.5 text-xs rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-medium"
                    >
                        <option value="">All Campuses</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }} ({{ $site->code }})</option>
                        @endforeach
                    </select>
                </x-searchable-select>
            </div>

            <div>
                <x-searchable-select 
                    placeholder="All Security Zones"
                    searchPlaceholder="Search security zone..."
                    containerClass="w-full sm:w-auto min-w-[170px]"
                >
                    <select
                        wire:model.live="zoneFilter"
                        class="w-full sm:w-auto px-2.5 py-1.5 text-xs rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono"
                    >
                        <option value="all">All Security Zones</option>
                        @foreach($securityZones as $zone)
                            <option value="{{ $zone->value }}">{{ $zone->label() }}</option>
                        @endforeach
                    </select>
                </x-searchable-select>
            </div>

            <div>
                <x-searchable-select 
                    placeholder="All Statuses"
                    searchPlaceholder="Search status..."
                    containerClass="w-full sm:w-auto min-w-[140px]"
                >
                    <select
                        wire:model.live="statusFilter"
                        class="w-full sm:w-auto px-2.5 py-1.5 text-xs rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100"
                    >
                        <option value="all">All Statuses</option>
                        @foreach($vlanStatuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </x-searchable-select>
            </div>
        </div>
    </div>

    <!-- VLANs Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-mono uppercase text-[11px]">
                        <th class="py-3 px-4">VLAN ID</th>
                        <th class="py-3 px-4">Name & Description</th>
                        <th class="py-3 px-4">Security Zone</th>
                        <th class="py-3 px-4">Attached Subnets</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @forelse($vlans as $vlan)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition">
                            <td class="py-3 px-4 font-mono font-bold text-slate-900 dark:text-white whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                    VLAN {{ $vlan->vlan_id }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-medium text-slate-800 dark:text-slate-200 flex items-center gap-2 flex-wrap">
                                    <span>{{ $vlan->name }}</span>
                                    @if($vlan->site)
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-mono font-medium bg-emerald-50 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800" title="Campus: {{ $vlan->site->name }}">
                                            {{ $vlan->site->code }}
                                        </span>
                                    @else
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-mono text-slate-400 bg-slate-100 dark:bg-slate-800">
                                            GLOBAL
                                        </span>
                                    @endif
                                    @if($vlan->is_restricted)
                                        <span class="px-1.5 py-0.5 rounded text-[10px] bg-rose-100 text-rose-800 border border-rose-300 font-mono">
                                            CLINICAL RESTRICTED
                                        </span>
                                    @endif
                                </div>
                                @if($vlan->description)
                                    <div class="text-[11px] text-slate-500 mt-0.5">{{ $vlan->description }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-medium border {{ $vlan->security_zone->badgeClasses() }}">
                                    {{ $vlan->security_zone->label() }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                @forelse($vlan->subnets as $sub)
                                    <span class="inline-block px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-700 font-mono text-[11px] text-slate-700 dark:text-slate-300 mr-1 mb-1">
                                        {{ $sub->cidr }}
                                    </span>
                                @empty
                                    <span class="text-slate-400 font-mono text-[11px]">Unattached</span>
                                @endforelse
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-medium border {{ $vlan->status->badgeClasses() }}">
                                    {{ $vlan->status->label() }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <button
                                        wire:click="openEditVlanModal({{ $vlan->id }})"
                                        type="button"
                                        class="p-1 text-slate-500 hover:text-slate-900 dark:hover:text-white rounded hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                                        title="Edit VLAN"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>

                                    <button
                                        wire:click="confirmDelete({{ $vlan->id }})"
                                        type="button"
                                        class="p-1 text-slate-400 hover:text-rose-600 rounded hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                                        title="Delete VLAN"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 px-4 text-center text-slate-400">
                                No VLANs found matching criteria. Click "Register New VLAN" to add one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40">
            {{ $vlans->links() }}
        </div>
    </div>

    <!-- Create / Edit VLAN Modal -->
    @if($showVlanModal)
        <div wire:key="vlan-modal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/70 backdrop-blur-xs overflow-y-auto">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 max-w-md w-full p-4 sm:p-6 space-y-4 max-h-[92vh] overflow-y-auto my-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        {{ $isEditingVlan ? 'Edit VLAN' : 'Register New VLAN' }}
                    </h3>
                    <button wire:click="closeVlanModal" type="button" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form wire:submit="saveVlan" class="space-y-4 text-xs">
                    @if($errors->any())
                        <div class="p-3 rounded-lg bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs space-y-1">
                            <div class="font-bold flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Please correct the errors below:</span>
                            </div>
                            <ul class="list-disc list-inside pl-1 text-[11px] space-y-0.5">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Campus / Facility Site</label>
                        <x-searchable-select 
                            placeholder="Global / Shared across Campuses"
                            searchPlaceholder="Search campus or site..."
                        >
                            <select
                                wire:model="vlan_site_id"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-medium"
                            >
                                <option value="">Global / Shared across Campuses</option>
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}">{{ $site->name }} ({{ $site->code }})</option>
                                @endforeach
                            </select>
                        </x-searchable-select>
                        @error('vlan_site_id') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">VLAN ID (1-4094) *</label>
                            <input
                                wire:model="vlan_number"
                                type="number"
                                min="1"
                                max="4094"
                                placeholder="10"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono focus:ring-2 focus:ring-emerald-500"
                            />
                            @error('vlan_number') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Security Zone *</label>
                            <x-searchable-select 
                                placeholder="Select Security Zone..."
                                searchPlaceholder="Search security zone..."
                            >
                                <select
                                    wire:model="vlan_security_zone"
                                    class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100"
                                >
                                    @foreach($securityZones as $zone)
                                        <option value="{{ $zone->value }}">{{ $zone->label() }}</option>
                                    @endforeach
                                </select>
                            </x-searchable-select>
                            @error('vlan_security_zone') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">VLAN Name *</label>
                        <input
                            wire:model="vlan_name"
                            type="text"
                            placeholder="e.g. ICU-PATIENT-MONITORING"
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500"
                        />
                        @error('vlan_name') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Status *</label>
                            <x-searchable-select 
                                placeholder="Active"
                                searchPlaceholder="Search status..."
                            >
                                <select
                                    wire:model="vlan_status"
                                    class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100"
                                >
                                    @foreach($vlanStatuses as $status)
                                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                            </x-searchable-select>
                            @error('vlan_status') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center pt-2 sm:pt-5">
                            <label class="flex items-center text-slate-700 dark:text-slate-300 cursor-pointer">
                                <input wire:model="vlan_is_restricted" type="checkbox" class="w-4 h-4 rounded text-rose-600" />
                                <span class="ml-2 font-medium text-rose-600">Restricted Zone</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Description</label>
                        <textarea
                            wire:model="vlan_description"
                            rows="2"
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-700">
                        <button wire:click="closeVlanModal" type="button" class="px-3.5 py-2 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300">
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

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
        <div wire:key="delete-vlan-modal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/70 backdrop-blur-xs overflow-y-auto">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 max-w-sm w-full p-4 sm:p-5 space-y-4 text-xs max-h-[92vh] overflow-y-auto my-auto">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 text-rose-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>Delete VLAN</span>
                </h3>
                @if($deleteError)
                    <div class="p-2.5 rounded-lg bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300">
                        {{ $deleteError }}
                    </div>
                @else
                    <p class="text-slate-600 dark:text-slate-300">
                        Are you sure you want to permanently delete this VLAN?
                    </p>
                @endif
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button wire:click="$set('showDeleteModal', false)" type="button" class="px-3.5 py-2 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300">
                        Cancel
                    </button>
                    <button wire:click="deleteVlan" type="button" @if($deleteError) disabled @endif class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold">
                        Confirm Delete
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
