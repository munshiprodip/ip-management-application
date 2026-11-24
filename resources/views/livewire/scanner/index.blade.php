<div class="space-y-6" @if($currentJob && $currentJob->isRunning()) wire:poll.2s="pollProgress" @endif>
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
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 sm:p-5 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 font-mono mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600">KYAMCH IPAM</a>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-semibold">Network Scanner</span>
            </div>
            <h1 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2.5">
                <span class="p-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" /></svg>
                </span>
                <span>Network IP Scanner &amp; Device Discovery</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Active ICMP reachability probes, reverse DNS hostname resolution, ARP MAC detection, and IPAM reconciliation.
            </p>
        </div>

        <!-- Scan Controls Toolbar -->
        <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
            @if($currentJob && $currentJob->isRunning())
                <button
                    wire:click="stopScan"
                    type="button"
                    class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-lg text-xs font-semibold shadow-xs transition"
                >
                    <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Stop Scan</span>
                </button>
            @else
                <button
                    wire:click="startScan()"
                    type="button"
                    @if(!$selectedSubnetId) disabled @endif
                    class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white rounded-lg text-xs font-semibold shadow-xs transition cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Start Scan {{ $selectedSubnet ? '(' . $selectedSubnet->cidr . ')' : '' }}</span>
                </button>
            @endif
        </div>
    </div>

    <!-- Target Subnet Selector Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3.5 sm:p-4 border border-slate-200 dark:border-slate-700 shadow-xs">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 sm:gap-4">
            <div class="flex-1 flex flex-col sm:flex-row items-stretch sm:items-end gap-2.5 max-w-2xl">
                <div class="flex-1">
                    <label class="block text-[11px] font-mono text-slate-500 uppercase mb-1">Target Managed Subnet:</label>
                    <x-searchable-select 
                        placeholder="Select Subnet to Scan..."
                        searchPlaceholder="Search by CIDR or subnet name..."
                    >
                        <select
                            wire:model.live="selectedSubnetId"
                            wire:change="selectSubnet($event.target.value)"
                            class="px-3 py-2 text-xs rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono font-semibold w-full"
                        >
                            @forelse($subnets as $sub)
                                <option value="{{ $sub->id }}" @selected((int)$selectedSubnetId === (int)$sub->id)>{{ $sub->cidr }} — {{ $sub->name }}{{ $sub->site ? ' [' . $sub->site->code . ']' : '' }} ({{ $sub->usableAddressesFormatted() }} hosts)</option>
                            @empty
                                <option value="">No assignable IPv4 subnets available</option>
                            @endforelse
                        </select>
                    </x-searchable-select>
                </div>

                @if($currentJob && $currentJob->isRunning())
                    <button
                        wire:click="stopScan"
                        type="button"
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-lg text-xs font-semibold shadow-xs transition"
                    >
                        <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Stop Scan</span>
                    </button>
                @else
                    <button
                        wire:click="startScan()"
                        type="button"
                        @if(!$selectedSubnetId) disabled @endif
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white rounded-lg text-xs font-semibold shadow-xs transition cursor-pointer whitespace-nowrap"
                        title="Start network discovery scan for {{ $selectedSubnet ? $selectedSubnet->cidr : 'selected subnet' }}"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Start Scan {{ $selectedSubnet ? '(' . $selectedSubnet->cidr . ')' : '' }}</span>
                    </button>
                @endif
            </div>

            @if($selectedSubnet)
                <div class="flex flex-wrap items-center gap-2 text-xs font-mono pt-2 sm:pt-0">
                    <span class="px-2.5 py-1 rounded bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                        Netmask: <strong>{{ $selectedSubnet->netmask() }}</strong>
                    </span>
                    <span class="px-2.5 py-1 rounded bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                        Gateway: <strong>{{ $selectedSubnet->defaultGateway() }}</strong>
                    </span>
                    @if($selectedSubnet->vlan)
                        <span class="px-2.5 py-1 rounded bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                            VLAN {{ $selectedSubnet->vlan->vlan_id }}
                        </span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Active Scan Status & KPI Summary Cards -->
    @if($currentJob)
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 sm:p-5 border border-slate-200 dark:border-slate-700 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-700 pb-3">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-mono font-bold text-slate-900 dark:text-white">
                        Scan Job #{{ $currentJob->id }} — {{ $currentJob->subnet->cidr }}
                    </span>
                    @if($currentJob->isRunning())
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 animate-pulse font-mono">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Scanning in progress...
                        </span>
                    @elseif($currentJob->isCompleted())
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300 border border-blue-200 dark:border-blue-800 font-mono">
                            ✓ Completed
                        </span>
                    @elseif($currentJob->isCancelled())
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300 border border-amber-200 dark:border-amber-800 font-mono">
                            Cancelled
                        </span>
                    @elseif($currentJob->isFailed())
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-300 border border-rose-200 dark:border-rose-800 font-mono">
                            Failed
                        </span>
                    @endif
                </div>

                <div class="text-[11px] font-mono text-slate-500">
                    Started: {{ $currentJob->started_at?->diffForHumans() ?? 'Just now' }}
                    @if($currentJob->durationInSeconds() !== null)
                        ({{ $currentJob->durationInSeconds() }}s)
                    @endif
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="space-y-1.5">
                <div class="flex justify-between text-xs font-mono">
                    <span class="text-slate-600 dark:text-slate-400">Progress: <strong>{{ $currentJob->progressPercentage() }}%</strong></span>
                    <span class="text-slate-500">{{ $currentJob->scanned_count }} / {{ $currentJob->total_targets }} addresses checked</span>
                </div>
                <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-2.5 overflow-hidden">
                    <div
                        class="h-2.5 rounded-full transition-all duration-300 {{ $currentJob->isRunning() ? 'bg-emerald-500 animate-pulse' : ($currentJob->isCompleted() ? 'bg-emerald-600' : 'bg-amber-500') }}"
                        style="width: {{ $currentJob->progressPercentage() }}%"
                    ></div>
                </div>
            </div>

            <!-- 4 Metric Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 pt-1">
                <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800">
                    <span class="text-[10px] font-mono uppercase text-slate-400">Total Targets</span>
                    <div class="text-xl font-bold font-mono text-slate-900 dark:text-white mt-0.5">{{ $currentJob->total_targets }}</div>
                    <span class="text-[11px] text-slate-500">Hosts in /{{ $currentJob->subnet->prefix_length }} CIDR</span>
                </div>

                <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800">
                    <span class="text-[10px] font-mono uppercase text-slate-400">Scanned Hosts</span>
                    <div class="text-xl font-bold font-mono text-slate-900 dark:text-white mt-0.5">{{ $currentJob->scanned_count }}</div>
                    <span class="text-[11px] text-slate-500">{{ $currentJob->total_targets - $currentJob->scanned_count }} remaining</span>
                </div>

                <div class="p-3 rounded-lg bg-emerald-50/50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60">
                    <span class="text-[10px] font-mono uppercase text-emerald-600 dark:text-emerald-400 font-semibold">Responding Devices</span>
                    <div class="text-xl font-bold font-mono text-emerald-600 dark:text-emerald-400 mt-0.5">{{ $currentJob->responding_count }}</div>
                    <span class="text-[11px] text-slate-500">Live network hosts</span>
                </div>

                <div class="p-3 rounded-lg {{ $currentJob->unregistered_count > 0 ? 'bg-rose-50/70 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-800' : 'bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800' }}">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-mono uppercase {{ $currentJob->unregistered_count > 0 ? 'text-rose-600 dark:text-rose-400 font-bold' : 'text-slate-400' }}">
                            Unregistered Devices
                        </span>
                        @if($currentJob->unregistered_count > 0)
                            <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
                        @endif
                    </div>
                    <div class="text-xl font-bold font-mono {{ $currentJob->unregistered_count > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }} mt-0.5">
                        {{ $currentJob->unregistered_count }}
                    </div>
                    <span class="text-[11px] {{ $currentJob->unregistered_count > 0 ? 'text-rose-600 dark:text-rose-400 font-medium' : 'text-slate-500' }}">
                        {{ $currentJob->unregistered_count > 0 ? 'Action required: unregistered IP' : 'All live devices accounted for' }}
                    </span>
                </div>
            </div>
        </div>
    @endif

    <!-- Results Toolbar & Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3.5 sm:p-4 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
        <div class="w-full sm:w-80">
            <input
                wire:model.live.debounce.300ms="search"
                type="text"
                placeholder="Search by IP, Hostname, MAC, or Assigned..."
                class="w-full px-3 py-2 text-xs rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500"
            />
        </div>

        <div class="flex items-center gap-2">
            <x-searchable-select 
                placeholder="All Results"
                searchPlaceholder="Search filter..."
                containerClass="w-full sm:w-auto min-w-[200px]"
            >
                <select
                    wire:model.live="statusFilter"
                    class="w-full sm:w-auto px-3 py-1.5 text-xs rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-medium"
                >
                    <option value="all">All Scanned Results</option>
                    <option value="responding">Responding Devices Only</option>
                    <option value="unregistered">⚠️ Unregistered Responding (Rogue / New)</option>
                    <option value="no_reply">No Reply (Offline)</option>
                </select>
            </x-searchable-select>
        </div>
    </div>

    <!-- Results Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-mono uppercase text-[11px]">
                        <th class="py-3 px-4">1. IP Address</th>
                        <th class="py-3 px-4">2. Reachability</th>
                        <th class="py-3 px-4">3. Hostname</th>
                        <th class="py-3 px-4">4. MAC Address</th>
                        <th class="py-3 px-4 text-center">5. Response</th>
                        <th class="py-3 px-4">6. IPAM Status</th>
                        <th class="py-3 px-4">7. Last Checked</th>
                        <th class="py-3 px-4 text-right">8. Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @forelse($results as $res)
                        @php
                            $reachBadge = $res->reachabilityBadge();
                            $ipamBadge = $res->ipamStatusBadge();
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition {{ $res->is_unregistered ? 'bg-amber-50/30 dark:bg-amber-950/20' : '' }}">
                            <!-- 1. IP Address -->
                            <td class="py-3 px-4 font-mono font-bold whitespace-nowrap">
                                <span class="{{ $res->isResponding() ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-700 dark:text-slate-300' }}">
                                    {{ $res->ip_address }}
                                </span>
                                @if($res->is_unregistered)
                                    <span class="ml-1.5 px-1.5 py-0.5 rounded text-[9px] font-mono font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300 dark:border-amber-700" title="Live device detected but unassigned in IPAM!">
                                        UNREGISTERED
                                    </span>
                                @endif
                            </td>

                            <!-- 2. Reachability -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-medium border {{ $reachBadge['class'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $reachBadge['dot'] }}"></span>
                                    <span>{{ $reachBadge['label'] }}</span>
                                </span>
                            </td>

                            <!-- 3. Hostname -->
                            <td class="py-3 px-4 font-mono">
                                @if($res->discovered_hostname)
                                    <div class="font-semibold text-slate-800 dark:text-slate-200">
                                        {{ $res->discovered_hostname }}
                                    </div>
                                    @if($res->ipam_hostname && $res->ipam_hostname !== $res->discovered_hostname)
                                        <div class="text-[10px] text-amber-600 dark:text-amber-400" title="Assigned hostname differs from DNS">
                                            IPAM: {{ $res->ipam_hostname }}
                                        </div>
                                    @endif
                                @elseif($res->ipam_hostname)
                                    <div class="text-slate-600 dark:text-slate-400">
                                        {{ $res->ipam_hostname }}
                                    </div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif

                                @if($res->ipam_assigned_to)
                                    <div class="text-[10px] text-slate-500 font-sans truncate max-w-xs" title="Assigned To">
                                        {{ $res->ipam_assigned_to }}
                                    </div>
                                @endif
                            </td>

                            <!-- 4. MAC Address -->
                            <td class="py-3 px-4 font-mono text-[11px]">
                                @if($res->discovered_mac)
                                    <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200 font-semibold">
                                        {{ $res->discovered_mac }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            <!-- 5. Response Time (ms) -->
                            <td class="py-3 px-4 text-center font-mono text-[11px] whitespace-nowrap">
                                @if($res->response_time_ms !== null)
                                    <span class="font-bold text-emerald-600 dark:text-emerald-400">
                                        {{ number_format($res->response_time_ms, 1) }} ms
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            <!-- 6. IPAM Allocation Status -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] font-medium border {{ $ipamBadge['class'] }}">
                                    {{ $ipamBadge['label'] }}
                                </span>
                            </td>

                            <!-- 7. Last Checked -->
                            <td class="py-3 px-4 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                {{ $res->checked_at->format('H:i:s') }}
                            </td>

                            <!-- 8. Actions -->
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    @if($res->is_unregistered || $res->ipam_status === 'unassigned')
                                        <button
                                            wire:click="openAllocateModal({{ $res->id }})"
                                            type="button"
                                            class="px-2.5 py-1 rounded bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-[11px] shadow-xs transition"
                                            title="Register and allocate this discovered device into authoritative IPAM"
                                        >
                                            Register
                                        </button>
                                    @endif

                                    <button
                                        wire:click="viewDetails({{ $res->id }})"
                                        type="button"
                                        class="px-2 py-1 rounded border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 text-[11px]"
                                    >
                                        Details
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 px-4 text-center text-slate-400">
                                @if($currentJob && $currentJob->isRunning())
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <svg class="w-6 h-6 animate-spin text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        <p class="font-medium text-slate-600 dark:text-slate-300">Scanning in progress... results are populating below.</p>
                                    </div>
                                @elseif($currentJob)
                                    <p>No addresses matched the active filters.</p>
                                @else
                                    <p>No scan results available. Select a target subnet and click <strong>"Start Scan"</strong> to discover devices.</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($results->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40">
                {{ $results->links() }}
            </div>
        @endif
    </div>

    <!-- Allocate Modal (For registering discovered host) -->
    @if($showAllocateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/70 backdrop-blur-xs overflow-y-auto">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 max-w-md w-full p-4 sm:p-6 space-y-4 max-h-[92vh] overflow-y-auto my-auto text-xs">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Register Discovered Device</h3>
                        <p class="text-[11px] font-mono text-slate-500 mt-0.5">Subnet: {{ $selectedSubnet?->cidr }}</p>
                    </div>
                    <button wire:click="$set('showAllocateModal', false)" type="button" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form wire:submit="saveAllocation" class="space-y-4">
                    @if($errors->any())
                        <div class="p-3 rounded-lg bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs space-y-1">
                            <ul class="list-disc list-inside pl-1 text-[11px] space-y-0.5">
                                @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
                            </ul>
                        </div>
                    @endif

                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Target IP Address *</label>
                        <input
                            wire:model="alloc_ip"
                            type="text"
                            readonly
                            class="w-full px-3 py-2 rounded-lg bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono font-bold"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Classification *</label>
                            <x-searchable-select 
                                placeholder="Select Classification..."
                                searchPlaceholder="Search classification..."
                            >
                                <select
                                    wire:model="alloc_classification"
                                    class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100"
                                >
                                    @foreach($classifications as $c)
                                        <option value="{{ $c->value }}">{{ $c->label() }}</option>
                                    @endforeach
                                </select>
                            </x-searchable-select>
                        </div>

                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Host Type</label>
                            <x-searchable-select 
                                placeholder="None / Unspecified"
                                searchPlaceholder="Search host type..."
                            >
                                <select
                                    wire:model="alloc_host_type"
                                    class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-medium"
                                >
                                    <option value="">None / Unspecified</option>
                                    @foreach($hostTypes as $ht)
                                        <option value="{{ $ht->slug ?? $ht->value }}">{{ $ht->name ?? $ht->label() }}</option>
                                    @endforeach
                                </select>
                            </x-searchable-select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Assigned To (Device / System / Person) *</label>
                        <input
                            wire:model="alloc_assigned_to"
                            type="text"
                            placeholder="e.g. ICU Bed 3 Patient Monitor"
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-medium focus:ring-2 focus:ring-emerald-500"
                        />
                        @error('alloc_assigned_to') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                    </div>

                    @if(!empty($alloc_hostname) || !empty($alloc_mac_address))
                        <div class="p-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-[11px] text-emerald-800 dark:text-emerald-300 flex items-center gap-2">
                            <span class="font-bold">✓</span>
                            <span>Auto-filled discovered network attributes from scan observation.</span>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Discovered / Registered Hostname
                                @if(!empty($alloc_hostname))
                                    <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold ml-1">✓ auto-filled</span>
                                @endif
                            </label>
                            <input
                                wire:model="alloc_hostname"
                                type="text"
                                placeholder="e.g. icu-mon-03.kyamch.local"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono"
                            />
                        </div>

                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Discovered MAC
                                @if(!empty($alloc_mac_address))
                                    <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold ml-1">✓ auto-filled</span>
                                @endif
                            </label>
                            <input
                                wire:model="alloc_mac_address"
                                type="text"
                                placeholder="00:1A:2B:3C:4D:5E"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 font-mono"
                            />
                        </div>
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
                        <button wire:click="$set('showAllocateModal', false)" type="button" class="px-3.5 py-2 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-medium">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-semibold shadow-xs transition">
                            Confirm Registration &amp; Assign
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Details Slide-over Drawer -->
    @if($showDetailDrawer && $detailResult)
        <div class="fixed inset-0 z-50 overflow-hidden bg-slate-950/60 backdrop-blur-xs flex justify-end">
            <div class="w-full max-w-md bg-white dark:bg-slate-800 shadow-2xl p-4 sm:p-6 overflow-y-auto space-y-5 text-xs">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
                    <div>
                        <span class="text-[10px] font-mono text-slate-400 uppercase">Scanner Observation Details</span>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white font-mono mt-0.5">
                            {{ $detailResult->ip_address }}
                        </h3>
                    </div>
                    <button wire:click="$set('showDetailDrawer', false)" type="button" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <div class="space-y-3 font-mono text-xs">
                    <div class="p-3.5 rounded-lg bg-slate-50 dark:bg-slate-900 space-y-2 border border-slate-200 dark:border-slate-800">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Reachability:</span>
                            <span class="font-bold {{ $detailResult->isResponding() ? 'text-emerald-600' : 'text-slate-500' }}">
                                {{ strtoupper($detailResult->status) }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Response Time:</span>
                            <span>{{ $detailResult->response_time_ms ? number_format($detailResult->response_time_ms, 2) . ' ms' : 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Discovered Hostname:</span>
                            <span>{{ $detailResult->discovered_hostname ?? 'None' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Discovered MAC:</span>
                            <span>{{ $detailResult->discovered_mac ?? 'None' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Checked At:</span>
                            <span>{{ $detailResult->checked_at->toDateTimeString() }}</span>
                        </div>
                    </div>

                    <div class="p-3.5 rounded-lg bg-slate-50 dark:bg-slate-900 space-y-2 border border-slate-200 dark:border-slate-800">
                        <span class="text-[10px] font-mono uppercase text-slate-400 font-bold block mb-1">IPAM Authoritative Record</span>
                        <div class="flex justify-between">
                            <span class="text-slate-500">IPAM Status:</span>
                            <span class="font-bold uppercase text-blue-600">{{ $detailResult->ipam_status }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">IPAM Hostname:</span>
                            <span>{{ $detailResult->ipam_hostname ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Assigned To:</span>
                            <span>{{ $detailResult->ipam_assigned_to ?? '—' }}</span>
                        </div>
                    </div>

                    @if($detailResult->is_unregistered)
                        <div class="p-3.5 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-800 space-y-2">
                            <div class="flex items-center gap-1.5 text-rose-800 dark:text-rose-300 font-bold text-xs">
                                <span>⚠️ Unregistered Device Warning</span>
                            </div>
                            <p class="text-slate-600 dark:text-slate-400 text-[11px] leading-relaxed">
                                This address responded to ICMP echo probe on the network, but is marked as unassigned in authoritative IPAM records.
                            </p>
                            <button
                                wire:click="openAllocateModal({{ $detailResult->id }})"
                                type="button"
                                class="w-full py-2 px-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold shadow-xs transition"
                            >
                                Register Device Now
                            </button>
                        </div>
                    @endif
                </div>

                <div class="pt-4 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                    <button wire:click="$set('showDetailDrawer', false)" type="button" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold">
                        Close Drawer
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

