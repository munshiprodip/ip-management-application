<div class="space-y-6">
    <!-- Top Action & Navigation Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-5">
        <div>
            <div class="flex items-center space-x-2 text-xs font-mono uppercase tracking-wider text-slate-500 dark:text-slate-400">
                <span>Address Space</span>
                <span>/</span>
                <span class="text-indigo-600 dark:text-indigo-400 font-semibold">IP Explorer & Allocation</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">IP Address Explorer & Allocations</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Virtual candidate window generator, atomic allocations, quarantine hold enforcement, and strict separation between authoritative allocation and observed network state.</p>
        </div>

        <div class="flex items-center space-x-3">
            @can('allocate', App\Models\IpAddress::class)
                <button wire:click="openAllocateModal()"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Allocate IP Address
                </button>
            @endcan
        </div>
    </div>

    <!-- Notification Banners -->
    @if (session()->has('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 rounded-xl text-sm text-emerald-800 dark:text-emerald-300 flex items-center justify-between">
            <span>{{ session('success') }}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">&times;</button>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="p-4 bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 rounded-xl text-sm text-rose-800 dark:text-rose-300 flex items-center justify-between">
            <span>{{ session('error') }}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700">&times;</button>
        </div>
    @endif

    <!-- Context Selectors (VRF & Subnet) -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- VRF Selector -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                    VRF Routing Context
                </label>
                <select wire:model.live="selectedVrfId"
                        class="w-full py-2 px-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @foreach ($vrfs as $vrf)
                        <option value="{{ $vrf->id }}">{{ $vrf->name }} (RD: {{ $vrf->rd ?? 'None' }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Subnet Selector -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                    Assignable Leaf Subnet
                </label>
                <select wire:model.live="selectedSubnetId"
                        class="w-full py-2 px-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @forelse ($subnets as $sub)
                        <option value="{{ $sub->id }}">{{ $sub->cidr }} — {{ $sub->name }}</option>
                    @empty
                        <option value="">No assignable subnets configured in this VRF</option>
                    @endforelse
                </select>
            </div>
        </div>

        @if ($selectedSubnet)
            <!-- Subnet Metadata & Utilization Bar -->
            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-4 text-xs">
                <div class="flex items-center space-x-4">
                    <div>
                        <span class="text-slate-400 uppercase font-semibold">Subnet:</span>
                        <strong class="font-mono text-slate-900 dark:text-white ml-1">{{ $selectedSubnet->cidr }}</strong>
                    </div>
                    <div>
                        <span class="text-slate-400 uppercase font-semibold">Usable Hosts:</span>
                        <span class="font-mono text-slate-700 dark:text-slate-300 ml-1">{{ $selectedSubnet->usableAddressesFormatted() }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 uppercase font-semibold">Allocated IPs:</span>
                        <span class="font-mono text-indigo-600 dark:text-indigo-400 font-bold ml-1">{{ $selectedSubnet->allocatedCount() }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 uppercase font-semibold">Campus:</span>
                        <span class="text-slate-700 dark:text-slate-300 ml-1">{{ $selectedSubnet->site?->name ?? 'General' }}</span>
                    </div>
                </div>

                <!-- Window Pagination Controls -->
                <div class="flex items-center space-x-2">
                    <button wire:click="previousPage"
                            @if ($currentPage <= 1) disabled @endif
                            class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 disabled:opacity-40 text-slate-700 dark:text-slate-300 rounded text-xs font-medium transition">
                        &larr; Prev {{ $perPage }}
                    </button>
                    <span class="text-xs font-mono text-slate-500">
                        Page {{ $windowData['current_page'] }} of {{ max(1, $windowData['last_page']) }}
                    </span>
                    <button wire:click="nextPage"
                            @if ($currentPage >= $windowData['last_page']) disabled @endif
                            class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 disabled:opacity-40 text-slate-700 dark:text-slate-300 rounded text-xs font-medium transition">
                        Next {{ $perPage }} &rarr;
                    </button>
                </div>
            </div>
        @endif
    </div>

    <!-- Candidate Window Table -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-400">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-5 py-3.5">IP Address</th>
                        <th class="px-5 py-3.5">Classification</th>
                        <th class="px-5 py-3.5">Authoritative Allocation</th>
                        <th class="px-5 py-3.5">Observed Connectivity</th>
                        <th class="px-5 py-3.5">Bound Host / Interface</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-mono text-xs">
                    @forelse ($windowData['items'] as $item)
                        @php
                            $allocStatus = $item['allocation_status'] instanceof App\Domain\AddressSpace\Enums\IpAllocationStatus
                                ? $item['allocation_status']
                                : App\Domain\AddressSpace\Enums\IpAllocationStatus::from($item['allocation_status']);
                            $connStatus = $item['connectivity_status'] instanceof App\Domain\AddressSpace\Enums\IpConnectivityStatus
                                ? $item['connectivity_status']
                                : App\Domain\AddressSpace\Enums\IpConnectivityStatus::from($item['connectivity_status']);
                            $classif = $item['classification'] instanceof App\Domain\AddressSpace\Enums\IpClassification
                                ? $item['classification']
                                : App\Domain\AddressSpace\Enums\IpClassification::from($item['classification']);
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                            <!-- IP Address -->
                            <td class="px-5 py-3.5 font-bold text-slate-900 dark:text-white">
                                <div class="flex items-center space-x-2">
                                    <span>{{ $item['address'] }}</span>
                                    @if ($item['hostname'])
                                        <span class="text-[11px] font-normal text-slate-400 font-sans">({{ $item['hostname'] }})</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Classification -->
                            <td class="px-5 py-3.5 font-sans">
                                <span class="px-2 py-0.5 text-[11px] font-medium rounded border {{ $classif->badgeClasses() }}">
                                    {{ $classif->label() }}
                                </span>
                            </td>

                            <!-- Authoritative Allocation Status -->
                            <td class="px-5 py-3.5 font-sans">
                                <span class="px-2 py-0.5 text-[11px] font-medium rounded border {{ $allocStatus->badgeClasses() }}">
                                    {{ $allocStatus->label() }}
                                </span>
                                @if ($allocStatus === App\Domain\AddressSpace\Enums\IpAllocationStatus::QUARANTINED && $item['quarantined_until'])
                                    <div class="text-[10px] text-amber-600 dark:text-amber-400 mt-0.5">
                                        Hold until: {{ \Carbon\Carbon::parse($item['quarantined_until'])->toFormattedDateString() }}
                                    </div>
                                @endif
                            </td>

                            <!-- Observed Connectivity Status (SEPARATE COLUMN!) -->
                            <td class="px-5 py-3.5 font-sans">
                                <span class="px-2 py-0.5 text-[11px] font-medium rounded border {{ $connStatus->badgeClasses() }}">
                                    {{ $connStatus->label() }}
                                </span>
                            </td>

                            <!-- Bound Host / Interface -->
                            <td class="px-5 py-3.5 font-sans">
                                @if (!empty($item['current_assignment']) && !empty($item['current_assignment']->interface))
                                    @php $iface = $item['current_assignment']->interface; @endphp
                                    <div class="font-semibold text-slate-900 dark:text-white">
                                        {{ $iface->device?->name ?? 'Unknown Device' }}
                                    </div>
                                    <div class="text-[11px] font-mono text-indigo-600 dark:text-indigo-400">
                                        Interface: {{ $iface->name }}
                                        @if ($iface->mac_address)
                                            <span class="text-slate-400">&bull; {{ $iface->mac_address }}</span>
                                        @endif
                                    </div>
                                @elseif ($item['description'])
                                    <span class="text-xs text-slate-500 italic">{{ $item['description'] }}</span>
                                @else
                                    <span class="text-slate-400 italic">None</span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="px-5 py-3.5 text-right font-sans space-x-1.5">
                                @if ($allocStatus === App\Domain\AddressSpace\Enums\IpAllocationStatus::UNASSIGNED)
                                    @can('allocate', App\Models\IpAddress::class)
                                        <button wire:click="openAllocateModal('{{ $item['address'] }}')"
                                                class="px-2 py-1 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 text-xs font-semibold rounded transition">
                                            Allocate
                                        </button>
                                        <button wire:click="openReserveModal('{{ $item['address'] }}')"
                                                class="px-2 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-medium rounded transition">
                                            Reserve
                                        </button>
                                    @endcan
                                @elseif ($allocStatus === App\Domain\AddressSpace\Enums\IpAllocationStatus::ASSIGNED && $item['id'])
                                    @can('transfer', App\Models\IpAddress::class)
                                        <button wire:click="openTransferModal({{ $item['id'] }})"
                                                class="px-2 py-1 text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 font-medium transition">
                                            Transfer
                                        </button>
                                    @endcan
                                    @can('release', App\Models\IpAddress::class)
                                        <button wire:click="openReleaseModal({{ $item['id'] }})"
                                                class="px-2 py-1 text-xs text-rose-600 hover:text-rose-800 dark:text-rose-400 font-medium transition">
                                            Release
                                        </button>
                                    @endcan
                                    <button wire:click="viewDetails({{ $item['id'] }})"
                                            class="px-2 py-1 text-xs text-slate-500 hover:text-slate-700 dark:text-slate-400 font-medium transition">
                                        History
                                    </button>
                                @elseif ($item['id'])
                                    <button wire:click="viewDetails({{ $item['id'] }})"
                                            class="px-2 py-1 text-xs text-slate-500 hover:text-slate-700 dark:text-slate-400 font-medium transition">
                                        Details
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400 font-sans">
                                Select an assignable subnet above to explore available IP addresses.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ALLOCATE IP MODAL -->
    @if ($showAllocateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full shadow-2xl p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Authoritative IP Allocation</h3>
                    <button wire:click="$set('showAllocateModal', false)" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
                </div>

                <form wire:submit="saveAllocation" class="space-y-4 text-xs">
                    <!-- IP Address Field -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">Target IP Address *</label>
                            <button type="button" wire:click="suggestNextFree" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                Suggest Next Free IP
                            </button>
                        </div>
                        <input type="text" wire:model="alloc_ip" placeholder="e.g. 10.10.10.15"
                               class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm font-mono text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @error('alloc_ip') <span class="text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Classification -->
                    <div>
                        <label class="block font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Classification *</label>
                        <select wire:model="alloc_classification" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm">
                            @foreach (App\Domain\AddressSpace\Enums\IpClassification::cases() as $cl)
                                <option value="{{ $cl->value }}">{{ $cl->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Hostname -->
                    <div>
                        <label class="block font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Hostname / FQDN</label>
                        <input type="text" wire:model="alloc_hostname" placeholder="e.g. ct01.rad.kyamch.local"
                               class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm font-mono">
                    </div>

                    <!-- Device Binding -->
                    <div>
                        <label class="block font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Target Hardware Device</label>
                        <select wire:model.live="alloc_device_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm">
                            <option value="">Select Device...</option>
                            @foreach ($devices as $d)
                                <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Interface Binding -->
                    @if ($alloc_device_id)
                        <div>
                            <label class="block font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Target Network Interface</label>
                            <select wire:model="alloc_interface_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm font-mono">
                                <option value="">Select Interface...</option>
                                @foreach ($interfaces as $iface)
                                    <option value="{{ $iface->id }}">{{ $iface->name }} (MAC: {{ $iface->mac_address ?? 'None' }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <!-- Description -->
                    <div>
                        <label class="block font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Authoritative Description</label>
                        <input type="text" wire:model="alloc_description" placeholder="e.g. Primary PACs modality imaging link"
                               class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm">
                    </div>

                    <div class="border-t border-slate-100 dark:border-slate-800 pt-4 flex justify-end space-x-3">
                        <button type="button" wire:click="$set('showAllocateModal', false)" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-sm rounded-lg">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm">Commit Allocation</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- RELEASE IP MODAL -->
    @if ($showReleaseModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full shadow-2xl p-6 space-y-4">
                <div class="flex items-center space-x-3 text-rose-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Release & Quarantine IP</h3>
                </div>

                <p class="text-xs text-slate-600 dark:text-slate-400">
                    Releasing an IP will close the active assignment and place the address into mandatory post-release quarantine to prevent stale ARP and DHCP conflicts.
                </p>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Quarantine Hold Duration</label>
                        <select wire:model="release_quarantine_days" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm">
                            <option value="3">3 Days (Standard Hospital Policy)</option>
                            <option value="7">7 Days (Clinical / Medical Hold)</option>
                            <option value="0">0 Days (Immediate Release - Not Recommended)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Reason for Release *</label>
                        <textarea wire:model="release_reason" rows="2" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm"></textarea>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 pt-2">
                    <button wire:click="$set('showReleaseModal', false)" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-sm rounded-lg">Cancel</button>
                    <button wire:click="confirmRelease" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold rounded-lg">Confirm Release</button>
                </div>
            </div>
        </div>
    @endif

    <!-- TRANSFER IP MODAL -->
    @if ($showTransferModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full shadow-2xl p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Atomic IP Address Transfer</h3>
                    <button wire:click="$set('showTransferModal', false)" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
                </div>

                <p class="text-xs text-slate-600 dark:text-slate-400">
                    Transfers ownership directly to another device interface in a single transaction without releasing the IP into the candidate pool.
                </p>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Target Device</label>
                        <select wire:model.live="transfer_device_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm">
                            <option value="">Select Destination Device...</option>
                            @foreach ($devices as $d)
                                <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($transfer_device_id)
                        <div>
                            <label class="block font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Destination Interface *</label>
                            <select wire:model="transfer_interface_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm font-mono">
                                <option value="">Select Interface...</option>
                                @foreach ($transferInterfaces as $iface)
                                    <option value="{{ $iface->id }}">{{ $iface->name }} (MAC: {{ $iface->mac_address ?? 'None' }})</option>
                                @endforeach
                            </select>
                            @error('transfer_interface_id') <span class="text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <div>
                        <label class="block font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Transfer Reason *</label>
                        <input type="text" wire:model="transfer_reason" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm">
                        @error('transfer_reason') <span class="text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex justify-end space-x-3 pt-2">
                    <button wire:click="$set('showTransferModal', false)" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-sm rounded-lg">Cancel</button>
                    <button wire:click="confirmTransfer" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg">Execute Transfer</button>
                </div>
            </div>
        </div>
    @endif

    <!-- RESERVE IP MODAL -->
    @if ($showReserveModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full shadow-2xl p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Reserve IP Address</h3>
                    <button wire:click="$set('showReserveModal', false)" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Target IP</label>
                        <input type="text" wire:model="reserve_ip" readonly class="w-full px-3 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm font-mono font-bold">
                    </div>

                    <div>
                        <label class="block font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Reservation Reason *</label>
                        <input type="text" wire:model="reserve_reason" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm">
                    </div>

                    <div>
                        <label class="block font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Expiration Date (Optional)</label>
                        <input type="date" wire:model="reserve_expires_at" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm">
                    </div>
                </div>

                <div class="flex justify-end space-x-3 pt-2">
                    <button wire:click="$set('showReserveModal', false)" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-sm rounded-lg">Cancel</button>
                    <button wire:click="confirmReservation" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold rounded-lg">Confirm Reservation</button>
                </div>
            </div>
        </div>
    @endif

    <!-- DETAILS & ASSIGNMENT HISTORY SLIDE-OVER -->
    @if ($showDetailsDrawer && $detailIp)
        <div class="fixed inset-0 z-50 overflow-hidden bg-slate-900/50 backdrop-blur-xs flex justify-end">
            <div class="w-full max-w-xl bg-white dark:bg-slate-900 h-full shadow-2xl flex flex-col border-l border-slate-200 dark:border-slate-800">
                <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-start justify-between bg-slate-50/50 dark:bg-slate-800/30">
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-0.5 text-xs font-mono font-medium rounded border {{ $detailIp->allocation_status->badgeClasses() }}">
                                {{ $detailIp->allocation_status->label() }}
                            </span>
                        </div>
                        <h2 class="text-xl font-bold font-mono text-slate-900 dark:text-white mt-2">{{ $detailIp->address_text }}</h2>
                        <div class="text-xs text-slate-500 font-mono mt-0.5">Hex: {{ $detailIp->address_hex }} &bull; VRF: {{ $detailIp->vrf->name }}</div>
                    </div>
                    <button wire:click="$set('showDetailsDrawer', false)" class="text-slate-400 hover:text-slate-600 text-xl font-bold p-1">&times;</button>
                </div>

                <div class="flex-1 overflow-y-auto p-6 space-y-6 text-xs">
                    <div>
                        <h4 class="font-bold text-slate-400 uppercase tracking-wider mb-2">Current Active Ownership</h4>
                        @if ($detailIp->currentAssignment && $detailIp->currentAssignment->interface)
                            @php $curr = $detailIp->currentAssignment; @endphp
                            <div class="p-4 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-lg space-y-1">
                                <div class="font-bold text-sm text-slate-900 dark:text-white">{{ $curr->interface->device?->name }}</div>
                                <div class="text-slate-500 font-mono">Interface: {{ $curr->interface->name }} (MAC: {{ $curr->interface->mac_address }})</div>
                                <div class="text-slate-400 text-[11px]">Assigned At: {{ $curr->assigned_at->toDayDateTimeString() }}</div>
                            </div>
                        @else
                            <div class="p-4 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-lg text-slate-400 text-center">
                                No active host interface currently bound to this address.
                            </div>
                        @endif
                    </div>

                    <div>
                        <h4 class="font-bold text-slate-400 uppercase tracking-wider mb-2">Assignment Timeline & History (T-012, T-013)</h4>
                        <div class="space-y-3">
                            @forelse ($detailIp->assignments()->latest('id')->get() as $hist)
                                <div class="p-3 bg-slate-50 dark:bg-slate-800/30 border border-slate-200 dark:border-slate-800 rounded-lg space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="font-semibold text-slate-900 dark:text-white">
                                            {{ $hist->interface?->device?->name ?? 'Interface #' . $hist->interface_id }} [{{ $hist->interface?->name }}]
                                        </span>
                                        <span class="text-[10px] text-slate-400">{{ $hist->assigned_at->toFormattedDateString() }}</span>
                                    </div>
                                    <div class="text-[11px] text-slate-500">Method: {{ $hist->assignment_method }}</div>
                                    @if ($hist->released_at)
                                        <div class="text-[11px] text-rose-600 dark:text-rose-400 font-mono">
                                            Released: {{ $hist->released_at->toFormattedDateString() }} &bull; {{ $hist->release_reason }}
                                        </div>
                                    @else
                                        <div class="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold font-mono">
                                            CURRENT ACTIVE ASSIGNMENT
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="text-slate-400 italic">No historical assignment records.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 flex justify-end">
                    <button wire:click="$set('showDetailsDrawer', false)" class="px-4 py-2 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-sm font-medium rounded-lg">Close</button>
                </div>
            </div>
        </div>
    @endif
</div>
