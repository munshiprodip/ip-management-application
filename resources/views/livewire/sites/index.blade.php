<div class="space-y-6">
    <!-- Header / Context Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 font-mono mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600">KYAMCH IPAM</a>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-semibold">Campus Management</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">Hospital Campuses &amp; Facility Sites</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Top-level campus network segregation for hospital units, academic medical college, and remote clinics.
            </p>
        </div>

        <div>
            <button
                wire:click="openCreateModal"
                type="button"
                class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold shadow-xs transition"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Campus Site
            </button>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-wrap gap-3 items-center justify-between">
        <div class="w-full sm:w-72">
            <input
                wire:model.live.debounce.300ms="search"
                type="text"
                placeholder="Search campus by name or code..."
                class="w-full px-3 py-2 text-xs rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500"
            />
        </div>

        <div class="flex items-center gap-3">
            <select
                wire:model.live="statusFilter"
                class="px-3 py-1.5 text-xs rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono focus:ring-2 focus:ring-emerald-500"
            >
                <option value="all">All Statuses</option>
                <option value="active">Active Only</option>
                <option value="inactive">Inactive Only</option>
            </select>
        </div>
    </div>

    <!-- Sites Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-mono uppercase text-[11px]">
                        <th class="py-3 px-4">Campus Code</th>
                        <th class="py-3 px-4">Campus / Facility Name</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Subnets</th>
                        <th class="py-3 px-4">VLANs</th>
                        <th class="py-3 px-4">Active Context</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @forelse($sites as $site)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition {{ $activeSiteId === $site->id ? 'bg-emerald-50/40 dark:bg-emerald-950/20' : '' }}">
                            <td class="py-3 px-4 font-mono font-bold text-slate-900 dark:text-white whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-emerald-700 dark:text-emerald-300">
                                    {{ $site->code }}
                                </span>
                                @if($site->is_default)
                                    <span class="ml-1 text-[10px] text-amber-600 dark:text-amber-400 font-mono font-bold">DEFAULT</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-semibold text-slate-800 dark:text-slate-200">{{ $site->name }}</div>
                                @if($site->description)
                                    <div class="text-[11px] text-slate-500 truncate max-w-sm">{{ $site->description }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $site->status === 'active' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300' }}">
                                    {{ strtoupper($site->status) }}
                                </span>
                            </td>
                            <td class="py-3 px-4 font-mono font-medium text-slate-700 dark:text-slate-300">
                                {{ $site->subnets_count }} subnet{{ $site->subnets_count === 1 ? '' : 's' }}
                            </td>
                            <td class="py-3 px-4 font-mono font-medium text-slate-700 dark:text-slate-300">
                                {{ $site->vlans_count }} VLAN{{ $site->vlans_count === 1 ? '' : 's' }}
                            </td>
                            <td class="py-3 px-4">
                                @if($activeSiteId === $site->id)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-semibold bg-emerald-600 text-white shadow-xs font-mono">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                        Active Campus
                                    </span>
                                @else
                                    <button
                                        wire:click="selectAsActive({{ $site->id }})"
                                        type="button"
                                        class="px-2 py-0.5 rounded border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-300 transition text-[11px] font-mono"
                                    >
                                        Select &amp; View
                                    </button>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <button
                                        wire:click="openEditModal({{ $site->id }})"
                                        type="button"
                                        class="p-1 text-slate-500 hover:text-slate-900 dark:hover:text-white rounded hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                                        title="Edit Campus"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>

                                    <button
                                        wire:click="confirmDelete({{ $site->id }})"
                                        type="button"
                                        class="p-1 text-slate-400 hover:text-rose-600 rounded hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                                        title="Delete Campus"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 px-4 text-center text-slate-400">
                                No campuses or sites found. Click "Add Campus Site" to create your first facility.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40">
            {{ $sites->links() }}
        </div>
    </div>

    <!-- Create / Edit Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-lg w-full border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                        {{ $isEditing ? 'Edit Campus Facility' : 'Add New Campus / Site' }}
                    </h3>
                    <button wire:click="$set('showModal', false)" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <form wire:submit="saveSite(new \App\Actions\Site\CreateSiteAction(), new \App\Actions\Site\UpdateSiteAction())" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Campus Name *</label>
                        <input
                            type="text"
                            wire:model="name"
                            placeholder="e.g. Main Hospital Campus"
                            class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                        />
                        @error('name') <span class="text-[11px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Campus Code *</label>
                            <input
                                type="text"
                                wire:model="code"
                                placeholder="e.g. KYAMCH-MAIN"
                                class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white font-mono uppercase focus:ring-2 focus:ring-emerald-500"
                            />
                            @error('code') <span class="text-[11px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Status *</label>
                            <select
                                wire:model="status"
                                class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                            >
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Description</label>
                        <textarea
                            wire:model="description"
                            rows="3"
                            placeholder="Brief description of the campus site..."
                            class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                        ></textarea>
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            id="is_default"
                            wire:model="is_default"
                            class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                        />
                        <label for="is_default" class="text-xs text-slate-700 dark:text-slate-300 font-medium">
                            Set as Default Campus
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-700">
                        <button
                            type="button"
                            wire:click="$set('showModal', false)"
                            class="px-3.5 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold shadow-xs"
                        >
                            {{ $isEditing ? 'Save Changes' : 'Create Campus' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-sm w-full border border-slate-200 dark:border-slate-700 p-6 space-y-4">
                <div class="w-10 h-10 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div class="text-center">
                    <h3 class="font-bold text-sm text-slate-900 dark:text-white">Delete Campus Site</h3>
                    <p class="text-xs text-slate-500 mt-1">Are you sure you want to delete this campus? This operation cannot be undone.</p>
                </div>
                <div class="flex items-center justify-center gap-2 pt-2">
                    <button
                        wire:click="$set('showDeleteModal', false)"
                        class="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-100 rounded-lg"
                    >
                        Cancel
                    </button>
                    <button
                        wire:click="deleteSite(new \App\Actions\Site\DeleteSiteAction())"
                        class="px-4 py-1.5 bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold rounded-lg"
                    >
                        Yes, Delete
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

