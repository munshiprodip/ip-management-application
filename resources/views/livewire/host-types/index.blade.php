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
                <span>Administration</span>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-semibold">Host Types</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">Host Types Management</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Authoritative device categorization classes, clinical equipment tags, and IP address host classification.
            </p>
        </div>

        <div>
            <button
                wire:click="openCreateModal"
                type="button"
                class="w-full sm:w-auto justify-center inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold shadow-xs transition"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Host Type</span>
            </button>
        </div>
    </div>

    <!-- KPI Metric Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl p-3.5 sm:p-4 border border-slate-200 dark:border-slate-700 shadow-xs">
            <span class="text-[11px] font-mono text-slate-400 uppercase">Total Host Types</span>
            <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ $totalTypes }}</div>
            <span class="text-[11px] text-slate-500">Configured classes</span>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-3.5 sm:p-4 border border-slate-200 dark:border-slate-700 shadow-xs">
            <span class="text-[11px] font-mono text-slate-400 uppercase">Active Status</span>
            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $activeTypes }}</div>
            <span class="text-[11px] text-slate-500">Available for allocation</span>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-3.5 sm:p-4 border border-slate-200 dark:border-slate-700 shadow-xs">
            <span class="text-[11px] font-mono text-slate-400 uppercase">System Types</span>
            <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">{{ $systemTypes }}</div>
            <span class="text-[11px] text-slate-500">Built-in hospital standards</span>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-3.5 sm:p-4 border border-slate-200 dark:border-slate-700 shadow-xs">
            <span class="text-[11px] font-mono text-slate-400 uppercase">Categorized IPs</span>
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $allocatedIps }}</div>
            <span class="text-[11px] text-slate-500">Assigned device addresses</span>
        </div>
    </div>

    <!-- Toolbar: Search & Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3.5 sm:p-4 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
        <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-3 flex-1 min-w-[280px]">
            <div class="relative flex-1 max-w-full md:max-w-sm">
                <input
                    wire:model.live.debounce.300ms="search"
                    type="text"
                    placeholder="Search by name, code or description..."
                    class="w-full pl-9 pr-3 py-2 sm:py-1.5 text-xs rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-emerald-500"
                />
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5 sm:top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <div>
                <x-searchable-select 
                    placeholder="All Statuses"
                    searchPlaceholder="Search status..."
                    containerClass="w-full sm:w-auto min-w-[150px]"
                >
                    <select
                        wire:model.live="statusFilter"
                        class="w-full sm:w-auto px-3 py-1.5 text-xs rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100"
                    >
                        <option value="all">All Statuses</option>
                        <option value="active">Active Only</option>
                        <option value="inactive">Inactive Only</option>
                    </select>
                </x-searchable-select>
            </div>
        </div>

        <div class="text-xs text-slate-400 font-mono">
            Showing {{ $hostTypes->total() }} Host Types
        </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-mono uppercase text-[11px]">
                        <th class="py-3 px-4">Host Type</th>
                        <th class="py-3 px-4">Code / Slug</th>
                        <th class="py-3 px-4">Description</th>
                        <th class="py-3 px-4 text-center">Assigned IPs</th>
                        <th class="py-3 px-4 text-center">Type</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @forelse($hostTypes as $ht)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition">
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="flex items-center gap-2.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] border {{ $ht->badgeClasses() }}">
                                        {{ $ht->name }}
                                    </span>
                                </div>
                            </td>
                            <td class="py-3 px-4 font-mono text-[11px] text-slate-600 dark:text-slate-400">
                                <code>{{ $ht->slug }}</code>
                            </td>
                            <td class="py-3 px-4 text-slate-600 dark:text-slate-300 max-w-xs truncate" title="{{ $ht->description }}">
                                {{ $ht->description ?? '—' }}
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @php
                                    $assignedCount = $ht->assigned_count ?? $ht->allocatedCount();
                                @endphp
                                @if($assignedCount > 0)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-mono font-semibold bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                        {{ $assignedCount }}
                                    </span>
                                @else
                                    <span class="text-slate-400 font-mono text-[11px]">0</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($ht->is_system)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-medium bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600" title="Core system type cannot be deleted">
                                        System
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-medium bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        Custom
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <button
                                    wire:click="toggleStatus({{ $ht->id }})"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium transition {{ $ht->is_active ? 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200' : 'bg-slate-100 hover:bg-slate-200 text-slate-600 border border-slate-300' }}"
                                    title="Click to toggle status"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full {{ $ht->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    <span>{{ $ht->is_active ? 'Active' : 'Inactive' }}</span>
                                </button>
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <button
                                        wire:click="openEditModal({{ $ht->id }})"
                                        type="button"
                                        class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-[11px] font-medium transition"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        wire:click="confirmDelete({{ $ht->id }})"
                                        type="button"
                                        class="px-2.5 py-1 rounded bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 text-[11px] font-medium transition"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 px-4 text-center text-slate-400">
                                No host types found matching the search criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($hostTypes->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-700">
                {{ $hostTypes->links() }}
            </div>
        @endif
    </div>

    <!-- Create / Edit Host Type Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/70 backdrop-blur-xs overflow-y-auto">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 max-w-md w-full p-4 sm:p-6 space-y-4 text-xs max-h-[92vh] overflow-y-auto my-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                            {{ $isEditing ? 'Edit Host Type' : 'Add New Host Type' }}
                        </h3>
                        <p class="text-xs text-slate-500 font-mono mt-0.5">
                            {{ $isEditing ? "Modifying {$name}" : 'Define a new device classification category' }}
                        </p>
                    </div>
                    <button wire:click="$set('showModal', false)" type="button" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form wire:submit="saveHostType" class="space-y-4">
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
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Host Type Name *</label>
                        <input
                            wire:model="name"
                            type="text"
                            placeholder="e.g. Infusion Pumps or Telemetry Monitors"
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 font-medium"
                        />
                        @error('name') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Code / Slug</label>
                            <input
                                wire:model="slug"
                                type="text"
                                placeholder="auto_generated"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono text-xs focus:ring-2 focus:ring-emerald-500"
                            />
                            @error('slug') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Badge Color *</label>
                            <x-searchable-select 
                                placeholder="Select Badge Color..."
                                searchPlaceholder="Search color..."
                            >
                                <select
                                    wire:model="color"
                                    class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-medium"
                                >
                                    @foreach($colorOptions as $cKey => $cLabel)
                                        <option value="{{ $cKey }}">{{ $cLabel }}</option>
                                    @endforeach
                                </select>
                            </x-searchable-select>
                            @error('color') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Description / Examples</label>
                        <textarea
                            wire:model="description"
                            rows="2"
                            placeholder="e.g. Bedside IV infusion, syringe drivers, smart pumps..."
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500"
                        ></textarea>
                        @error('description') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input
                            wire:model="is_active"
                            type="checkbox"
                            id="is_active"
                            class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300"
                        />
                        <label for="is_active" class="font-medium text-slate-700 dark:text-slate-300 cursor-pointer select-none">
                            Active (available in Allocate &amp; Edit IP forms)
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-700">
                        <button
                            wire:click="$set('showModal', false)"
                            type="button"
                            class="px-3.5 py-2 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-medium"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-semibold shadow-xs transition"
                        >
                            {{ $isEditing ? 'Save Changes' : 'Create Host Type' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/70 backdrop-blur-xs overflow-y-auto">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 max-w-sm w-full p-4 sm:p-5 space-y-4 text-xs max-h-[92vh] overflow-y-auto my-auto">
                <div class="flex items-center gap-3 text-rose-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Delete Host Type</h3>
                </div>

                @if($deleteError)
                    <div class="p-3 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs">
                        {{ $deleteError }}
                    </div>
                @else
                    <p class="text-slate-600 dark:text-slate-300">
                        Are you sure you want to delete host type <strong class="text-slate-900 dark:text-white">{{ $deleteHostTypeName }}</strong>? This action cannot be undone.
                    </p>
                @endif

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-700">
                    <button
                        wire:click="$set('showDeleteModal', false)"
                        type="button"
                        class="px-3.5 py-2 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-medium"
                    >
                        {{ $deleteError ? 'Close' : 'Cancel' }}
                    </button>
                    @if(! $deleteError)
                        <button
                            wire:click="deleteHostType"
                            type="button"
                            class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-lg font-semibold shadow-xs transition"
                        >
                            Confirm Delete
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

