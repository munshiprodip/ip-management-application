<div class="space-y-6">
    <!-- Header / Context Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 font-mono mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600">KYAMCH IPAM</a>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-semibold">IP Explorer & Allocations</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">IP Address Explorer &amp; Allocations</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Authoritative host IP address allocation, quarantine management, and virtual candidate window explorer.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <button
                wire:click="openAllocateModal()"
                type="button"
                @if(!$selectedSubnetId) disabled @endif
                class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white rounded-lg text-xs font-semibold shadow-xs transition"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Allocate IP Address
            </button>
        </div>
    </div>

    <!-- Subnet Selector & Status Filter Toolbar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-wrap gap-3 items-center justify-between">
        <div class="flex flex-wrap items-center gap-3">
            <div>
                <label class="block text-[11px] font-mono text-slate-500 uppercase mb-1">Target Subnet:</label>
                <select
                    wire:model.live="selectedSubnetId"
                    class="px-3 py-1.5 text-xs rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono font-semibold focus:ring-2 focus:ring-emerald-500"
                >
                    @forelse($subnets as $sub)
                        <option value="{{ $sub->id }}">{{ $sub->cidr }} — {{ $sub->name }}{{ $sub->site ? ' [' . $sub->site->code . ']' : '' }}</option>
                    @empty
                        <option value="">No assignable subnets available</option>
                    @endforelse
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-mono text-slate-500 uppercase mb-1">Status Filter:</label>
                <select
                    wire:model.live="statusFilter"
                    class="px-3 py-1.5 text-xs rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500"
                >
                    <option value="all">All Address Candidates</option>
                    <option value="unassigned">Available (Unassigned)</option>
                    <option value="assigned">Assigned</option>
                    <option value="reserved">Reserved</option>
                    <option value="quarantined">Quarantined</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-mono uppercase text-slate-400 mb-1">Host Type Filter:</label>
                <select
                    wire:model.live="hostTypeFilter"
                    class="px-3 py-1.5 text-xs rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 font-medium"
                >
                    <option value="all">All Host Types</option>
                    @foreach($hostTypes as $ht)
                        <option value="{{ $ht->slug ?? $ht->value }}">{{ $ht->name ?? $ht->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if($selectedSubnet)
            <div class="flex flex-wrap items-center gap-2.5 text-xs font-mono">
                <span class="px-2.5 py-1 rounded bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold" title="Subnet Mask">
                    Mask: <span class="text-emerald-700 dark:text-emerald-300">{{ $selectedSubnet->netmask() }}</span>
                </span>
                <span class="px-2.5 py-1 rounded bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold" title="Default Gateway">
                    Gateway: <span class="text-blue-700 dark:text-blue-300">{{ $selectedSubnet->defaultGateway() }}</span>
                </span>
                <span class="px-2.5 py-1 rounded bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                    {{ $selectedSubnet->usableAddressesFormatted() }} Usable Hosts
                </span>
                @if($selectedSubnet->vlan)
                    <span class="px-2.5 py-1 rounded bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                        VLAN {{ $selectedSubnet->vlan->vlan_id }}
                    </span>
                @endif
            </div>
        @endif
    </div>

    <!-- Candidate Window Grid / Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-mono uppercase text-[11px]">
                        <th class="py-3 px-4">Candidate IP</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Host Type</th>
                        <th class="py-3 px-4">Hostname</th>
                        <th class="py-3 px-4">Assigned To</th>
                        <th class="py-3 px-4">Subnet Mask</th>
                        <th class="py-3 px-4">Default Gateway</th>
                        <th class="py-3 px-4">MAC Address</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @forelse($windowData['items'] as $item)
                        @php
                            $statusStr = is_object($item['status']) ? $item['status']->value : (string) $item['status'];
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition">
                            <td class="py-3 px-4 font-mono font-bold text-slate-900 dark:text-white whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded {{ $statusStr === 'assigned' ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : ($statusStr === 'reserved' ? 'bg-amber-50 text-amber-700' : ($statusStr === 'quarantined' ? 'bg-purple-50 text-purple-700' : 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-200')) }}">
                                    {{ $item['ip_text'] }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                @if($statusStr === 'assigned')
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-semibold bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Assigned
                                    </span>
                                @elseif($statusStr === 'reserved')
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Reserved
                                    </span>
                                @elseif($statusStr === 'quarantined')
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-semibold bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span> Quarantined
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Available
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                @if(!empty($item['host_type']))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] border {{ $item['host_type_badge'] ?? 'bg-slate-100 text-slate-700' }}" title="{{ $item['host_type_description'] ?? '' }}">
                                        {{ $item['host_type_label'] ?? $item['host_type'] }}
                                    </span>
                                @else
                                    <span class="text-slate-400 font-mono text-[11px]">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 font-mono text-[11px] text-slate-800 dark:text-slate-200">
                                {{ $item['hostname'] ?? '—' }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    @if($item['assigned_to'])
                                        <span class="font-medium text-slate-800 dark:text-slate-200">{{ $item['assigned_to'] }}</span>
                                        <button
                                            wire:click="openEditAssignmentModal('{{ $item['ip_text'] }}')"
                                            type="button"
                                            class="p-0.5 text-slate-400 hover:text-emerald-600 rounded transition"
                                            title="Edit Assigned To"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        </button>
                                    @elseif($statusStr === 'assigned')
                                        <button
                                            wire:click="openEditAssignmentModal('{{ $item['ip_text'] }}')"
                                            type="button"
                                            class="inline-flex items-center gap-1 text-[11px] text-emerald-600 hover:text-emerald-700 font-medium"
                                        >
                                            <span>+ Set Assigned To</span>
                                        </button>
                                    @else
                                        <span class="text-slate-400 font-mono text-[11px]">—</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-4 font-mono text-[11px] text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                {{ $item['netmask'] ?? ($selectedSubnet ? $selectedSubnet->netmask() : '—') }}
                            </td>
                            <td class="py-3 px-4 font-mono text-[11px] text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                {{ $item['gateway'] ?? ($selectedSubnet ? $selectedSubnet->defaultGateway() : '—') }}
                            </td>
                            <td class="py-3 px-4 font-mono text-[11px] text-slate-600 dark:text-slate-300">
                                {{ $item['mac_address'] ?? '—' }}
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    @if($statusStr === 'unassigned')
                                        <button
                                            wire:click="openAllocateModal('{{ $item['ip_text'] }}')"
                                            type="button"
                                            class="px-2 py-1 rounded bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-[11px] font-semibold transition"
                                        >
                                            Allocate
                                        </button>
                                        <button
                                            wire:click="openReserveModal('{{ $item['ip_text'] }}')"
                                            type="button"
                                            class="px-2 py-1 rounded bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-[11px] font-semibold transition"
                                        >
                                            Reserve
                                        </button>
                                    @elseif($statusStr === 'assigned')
                                        <button
                                            wire:click="openEditAssignmentModal('{{ $item['ip_text'] }}')"
                                            type="button"
                                            class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-[11px] font-semibold transition"
                                            title="Edit Assigned To & Hostname"
                                        >
                                            Edit
                                        </button>
                                        @if($item['id'])
                                            <button
                                                wire:click="openReleaseModal({{ $item['id'] }})"
                                                type="button"
                                                class="px-2 py-1 rounded bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-[11px] font-semibold transition"
                                            >
                                                Release
                                            </button>
                                            <button
                                                wire:click="viewDetails({{ $item['id'] }})"
                                                type="button"
                                                class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] transition"
                                            >
                                                Details
                                            </button>
                                        @endif
                                    @elseif($statusStr === 'quarantined')
                                        <button
                                            wire:click="unquarantineIp('{{ $item['ip_text'] }}')"
                                            type="button"
                                            class="px-2 py-1 rounded bg-amber-50 hover:bg-emerald-100 text-amber-700 hover:text-emerald-700 border border-amber-300 hover:border-emerald-300 text-[11px] font-semibold transition"
                                            title="Lift quarantine and mark address as unassigned / available"
                                        >
                                            Make Unassigned
                                        </button>
                                        @if($item['id'])
                                            <button
                                                wire:click="viewDetails({{ $item['id'] }})"
                                                type="button"
                                                class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] transition"
                                            >
                                                Details
                                            </button>
                                        @endif
                                    @elseif($item['id'])
                                        <button
                                            wire:click="viewDetails({{ $item['id'] }})"
                                            type="button"
                                            class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] transition"
                                        >
                                            Details
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 px-4 text-center text-slate-400">
                                @if($selectedSubnet)
                                    No addresses matching criteria in candidate window.
                                @else
                                    Please select an assignable subnet above to view candidate IP address window.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        @if($windowData['total'] > 0)
            <div class="p-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 flex items-center justify-between text-xs font-mono">
                <span class="text-slate-500">
                    Showing page {{ $windowData['current_page'] }} of {{ $windowData['last_page'] }} (Total candidate addresses: {{ number_format($windowData['total']) }})
                </span>
                <div class="flex items-center gap-2">
                    <button
                        wire:click="previousPage"
                        type="button"
                        @if($currentPage <= 1) disabled @endif
                        class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 disabled:opacity-40"
                    >
                        &larr; Previous 50
                    </button>
                    <button
                        wire:click="nextPage"
                        type="button"
                        @if($currentPage >= $windowData['last_page']) disabled @endif
                        class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 disabled:opacity-40"
                    >
                        Next 50 &rarr;
                    </button>
                </div>
            </div>
        @endif
    </div>

    <!-- Allocate Modal -->
    @if($showAllocateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 max-w-md w-full p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        Allocate IP Address
                    </h3>
                    <button wire:click="$set('showAllocateModal', false)" type="button" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form wire:submit="saveAllocation" class="space-y-4 text-xs">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="font-medium text-slate-700 dark:text-slate-300">Target IP Address *</label>
                            <button wire:click="suggestNextFree" type="button" class="text-[11px] text-emerald-600 font-semibold">
                                Suggest Free
                            </button>
                        </div>
                        <input
                            wire:model="alloc_ip"
                            type="text"
                            placeholder="10.10.1.5"
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono focus:ring-2 focus:ring-emerald-500"
                        />
                        @error('alloc_ip') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Classification *</label>
                            <select
                                wire:model="alloc_classification"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100"
                            >
                                @foreach($classifications as $c)
                                    <option value="{{ $c->value }}">{{ $c->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Host Type</label>
                            <select
                                wire:model="alloc_host_type"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-medium"
                            >
                                <option value="">None / Unspecified</option>
                                @foreach($hostTypes as $ht)
                                    <option value="{{ $ht->slug ?? $ht->value }}">{{ $ht->name ?? $ht->label() }} {{ !empty($ht->description) ? '— ' . $ht->description : '' }}</option>
                                @endforeach
                            </select>
                            @error('alloc_host_type') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Hostname (FQDN or short)</label>
                            <input
                                wire:model="alloc_hostname"
                                type="text"
                                placeholder="e.g. icu-mon-01.kyamch.local"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100"
                            />
                        </div>

                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">MAC Address</label>
                            <input
                                wire:model="alloc_mac_address"
                                type="text"
                                placeholder="00:1A:2B:3C:4D:5E"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Assigned To (Device / System / Person)</label>
                        <input
                            wire:model="alloc_assigned_to"
                            type="text"
                            placeholder="e.g. ICU Bed 4 Monitor"
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100"
                        />
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Description / Notes</label>
                        <textarea
                            wire:model="alloc_description"
                            rows="2"
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-700">
                        <button wire:click="$set('showAllocateModal', false)" type="button" class="px-3.5 py-2 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-semibold">
                            Confirm Allocation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Release Modal -->
    @if($showReleaseModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 max-w-sm w-full p-5 space-y-4 text-xs">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 text-rose-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>Release IP Address</span>
                </h3>

                <div class="space-y-3">
                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Post-Release Quarantine (Days):</label>
                        <input
                            wire:model="release_quarantine_days"
                            type="number"
                            min="0"
                            max="30"
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono"
                        />
                        <p class="text-[10px] text-slate-400 mt-1">Sets cooling off period before address can be allocated again.</p>
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Release Reason *</label>
                        <textarea
                            wire:model="release_reason"
                            rows="2"
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100"
                        ></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-700">
                    <button wire:click="$set('showReleaseModal', false)" type="button" class="px-3.5 py-2 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300">
                        Cancel
                    </button>
                    <button wire:click="confirmRelease" type="button" class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-500 text-white font-semibold">
                        Confirm Release
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Reserve Modal -->
    @if($showReserveModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 max-w-sm w-full p-5 space-y-4 text-xs">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 text-amber-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>Reserve IP Address: {{ $reserve_ip }}</span>
                </h3>

                <div class="space-y-3">
                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Reservation Reason *</label>
                        <textarea
                            wire:model="reserve_reason"
                            rows="2"
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Expires On (Optional):</label>
                        <input
                            wire:model="reserve_expires_at"
                            type="date"
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-700">
                    <button wire:click="$set('showReserveModal', false)" type="button" class="px-3.5 py-2 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300">
                        Cancel
                    </button>
                    <button wire:click="confirmReservation" type="button" class="px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-500 text-white font-semibold">
                        Confirm Reservation
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Details Slide-over Drawer -->
    @if($showDetailsDrawer && $detailIp)
        <div class="fixed inset-0 z-50 overflow-hidden bg-slate-950/60 backdrop-blur-xs flex justify-end">
            <div class="w-full max-w-md bg-white dark:bg-slate-800 shadow-2xl p-6 overflow-y-auto space-y-5 text-xs">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
                    <div>
                        <span class="text-[11px] font-mono text-slate-400">IP ADDRESS RECORD</span>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white font-mono mt-0.5">
                            {{ $detailIp->address_text }}
                        </h3>
                    </div>
                    <button wire:click="$set('showDetailsDrawer', false)" type="button" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <div class="space-y-3 font-mono text-xs">
                    <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-900 space-y-1.5 border border-slate-200 dark:border-slate-800">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Status:</span>
                            <span class="font-bold uppercase text-emerald-600">{{ $detailIp->allocation_status->value }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Subnet:</span>
                            <span>{{ $detailIp->subnet->cidr }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Hostname:</span>
                            <span>{{ $detailIp->hostname ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">MAC Address:</span>
                            <span>{{ $detailIp->mac_address ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Assigned To:</span>
                            <span>{{ $detailIp->assigned_to ?? '—' }}</span>
                        </div>
                        @if($detailIp->host_type)
                            @php
                                $htModel = $detailIp->hostTypeModel();
                            @endphp
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500">Host Type:</span>
                                <span class="px-2 py-0.5 rounded text-[11px] border {{ $htModel?->badgeClasses() ?? 'bg-slate-100 text-slate-700' }}" title="{{ $htModel?->description ?? '' }}">
                                    {{ $htModel?->name ?? $detailIp->host_type }}
                                </span>
                            </div>
                        @endif
                        @if($detailIp->quarantined_until)
                            <div class="flex justify-between text-purple-600">
                                <span>Quarantine Until:</span>
                                <span>{{ $detailIp->quarantined_until->toDateTimeString() }}</span>
                            </div>
                        @endif
                    </div>

                    @if($detailIp->isQuarantined())
                        <div class="p-3 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 space-y-2">
                            <div class="flex items-center gap-1.5 text-amber-800 dark:text-amber-300 font-bold text-[11px]">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                <span>Post-Release Quarantine Active</span>
                            </div>
                            <p class="text-slate-600 dark:text-slate-400 text-[11px]">
                                Address is cooling off until {{ $detailIp->quarantined_until?->toDateTimeString() }}.
                                @if($detailIp->quarantine_reason)
                                    <br><span class="text-slate-500">Reason: {{ $detailIp->quarantine_reason }}</span>
                                @endif
                            </p>
                            <button
                                wire:click="unquarantineIp('{{ $detailIp->address_text }}')"
                                type="button"
                                class="w-full py-1.5 px-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded text-xs font-semibold shadow-xs transition"
                            >
                                Lift Quarantine & Make Unassigned
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Assignment History -->
                <div>
                    <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Assignment History</h4>
                    <div class="space-y-2">
                        @forelse($detailIp->assignments as $asgn)
                            <div class="p-2.5 rounded bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-[11px] font-mono">
                                <div class="flex justify-between font-semibold">
                                    <span>{{ $asgn->assigned_to ?? 'Host' }}</span>
                                    <span class="{{ $asgn->released_at ? 'text-slate-400' : 'text-emerald-600' }}">
                                        {{ $asgn->released_at ? 'RELEASED' : 'ACTIVE' }}
                                    </span>
                                </div>
                                <div class="text-slate-500 mt-1">
                                    Assigned: {{ $asgn->assigned_at->toDateTimeString() }}
                                </div>
                                @if($asgn->released_at)
                                    <div class="text-slate-500">
                                        Released: {{ $asgn->released_at->toDateTimeString() }} ({{ $asgn->release_reason }})
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-slate-400">No previous assignments recorded.</p>
                        @endforelse
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                    <button wire:click="$set('showDetailsDrawer', false)" type="button" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold">
                        Close Drawer
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Edit Assignment Modal -->
    @if($showEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 max-w-md w-full p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Edit IP Assignment Details</h3>
                        <p class="text-xs text-slate-500 font-mono mt-0.5">IP: {{ $edit_ip_text }} (Mask: {{ $selectedSubnet?->netmask() }}, GW: {{ $selectedSubnet?->defaultGateway() }})</p>
                    </div>
                    <button wire:click="$set('showEditModal', false)" type="button" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form wire:submit="saveAssignmentEdit" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Assigned To (Device / User / Bed / Station) *</label>
                        <input
                            wire:model="edit_assigned_to"
                            type="text"
                            placeholder="e.g. ICU Bed 1 Patient Monitor or Dr. Room 3"
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 font-medium"
                        />
                        @error('edit_assigned_to') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Host Type</label>
                            <select
                                wire:model="edit_host_type"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-medium"
                            >
                                <option value="">None / Unspecified</option>
                                @foreach($hostTypes as $ht)
                                    <option value="{{ $ht->slug ?? $ht->value }}">{{ $ht->name ?? $ht->label() }} {{ !empty($ht->description) ? '— ' . $ht->description : '' }}</option>
                                @endforeach
                            </select>
                            @error('edit_host_type') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Hostname (FQDN)</label>
                            <input
                                wire:model="edit_hostname"
                                type="text"
                                placeholder="host.kyamch.local"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">MAC Address</label>
                        <input
                            wire:model="edit_mac_address"
                            type="text"
                            placeholder="AA:BB:CC:DD:EE:FF"
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono uppercase focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Description / Notes</label>
                        <textarea
                            wire:model="edit_description"
                            rows="2"
                            placeholder="Notes on hardware, room location, port, or vendor..."
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-700">
                        <button wire:click="$set('showEditModal', false)" type="button" class="px-3.5 py-2 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-medium">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-semibold shadow-xs transition">
                            Save Assignment Details
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
