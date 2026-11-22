<div class="space-y-6">
    <!-- Header & Action Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 font-mono mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600">KYAMCH IPAM</a>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-semibold">Evidence & Reconciliation</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">Discovery & Reconciliation Engine</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Authorized zone agents, read-only evidence collection, observation deduplication and discrepancy detection.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-2.5 py-1 rounded text-xs font-mono font-medium {{ $discoveryEnabled ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border-amber-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border-emerald-300 dark:border-emerald-800' }} border">
                Safe Posture: {{ $discoveryEnabled ? 'LIVE PROBING ENABLED' : 'ACTIVE DISCOVERY DISABLED (SAFE)' }}
            </span>
            <button
                wire:click="openScanModal"
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Run Discovery Scan
            </button>
        </div>
    </div>

    <!-- Architectural Invariant Safety Banner -->
    <div class="p-4 rounded-xl bg-slate-900 text-slate-100 border border-slate-800 shadow-xs">
        <div class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-lg bg-emerald-600/30 text-emerald-400 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <div class="text-xs space-y-1">
                <h3 class="font-semibold text-slate-100 flex items-center gap-2">
                    <span>Safe Evidence Principle: Observations Never Overwrite Allocations</span>
                    <span class="px-1.5 py-0.2 rounded text-[10px] font-mono bg-emerald-950 text-emerald-400 border border-emerald-800">GUARANTEED</span>
                </h3>
                <p class="text-slate-400 leading-relaxed">
                    Discovered network observations (ARP, ICMP, DHCP, DNS, SNMP) are treated exclusively as <strong>observational evidence</strong> and never overwrite authoritative allocations. Unobserved devices remain allocated (silent is not free), and unallocated active IPs trigger reconciliation review findings rather than mutating records silently.
                </p>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    @if (session()->has('success'))
        <div class="p-4 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-800 dark:text-emerald-300 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="p-4 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs text-rose-800 dark:text-rose-300 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- KPI Metric Summary Tiles -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Observations</div>
            <div class="text-2xl font-bold font-mono text-slate-900 dark:text-white mt-1">{{ number_format($totalObservations) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Ingested evidence records</div>
        </div>
        <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
            <div class="text-xs font-medium text-amber-600 uppercase tracking-wider">Open Findings</div>
            <div class="text-2xl font-bold font-mono text-amber-600 dark:text-amber-400 mt-1">{{ number_format($openFindingsCount) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Awaiting operator review</div>
        </div>
        <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-rose-200 dark:border-rose-900/60 bg-rose-50/20 dark:bg-rose-950/10">
            <div class="text-xs font-medium text-rose-600 dark:text-rose-400 uppercase tracking-wider flex items-center justify-between">
                <span>Rogue / Unmanaged IPs</span>
                @if($criticalFindingsCount > 0)
                    <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                @endif
            </div>
            <div class="text-2xl font-bold font-mono text-rose-700 dark:text-rose-300 mt-1">{{ number_format($criticalFindingsCount) }}</div>
            <div class="text-[10px] text-rose-500/80 mt-0.5">Critical unallocated active devices</div>
        </div>
        <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
            <div class="text-xs font-medium text-teal-600 uppercase tracking-wider">Discovery Agents</div>
            <div class="text-2xl font-bold font-mono text-teal-700 dark:text-teal-300 mt-1">{{ $agentsHealthy }} / {{ $agentsTotal }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Healthy zone collectors</div>
        </div>
    </div>

    <!-- Navigation Tabs & Search Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 space-y-4">
        <div class="flex items-center gap-1 border-b border-slate-200 dark:border-slate-700 pb-3 overflow-x-auto text-xs font-medium">
            <button
                wire:click="$set('activeTab', 'findings')"
                class="px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 {{ $activeTab === 'findings' ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}"
            >
                <span>Reconciliation Findings</span>
                @if($openFindingsCount > 0)
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono {{ $activeTab === 'findings' ? 'bg-rose-500 text-white' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300' }}">{{ $openFindingsCount }}</span>
                @endif
            </button>
            <button
                wire:click="$set('activeTab', 'observations')"
                class="px-3 py-1.5 rounded-lg transition {{ $activeTab === 'observations' ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}"
            >
                Observation Stream ({{ number_format($totalObservations) }})
            </button>
            <button
                wire:click="$set('activeTab', 'agents')"
                class="px-3 py-1.5 rounded-lg transition {{ $activeTab === 'agents' ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}"
            >
                Zone Collectors &amp; Agents ({{ $agentsTotal }})
            </button>
            <button
                wire:click="$set('activeTab', 'jobs')"
                class="px-3 py-1.5 rounded-lg transition {{ $activeTab === 'jobs' ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}"
            >
                Scan Job History
            </button>
        </div>

        @if($activeTab === 'findings')
            <!-- Findings Filter Toolbar -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                <div class="relative">
                    <input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Search findings by IP, title, details..."
                        class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-2 focus:ring-emerald-500"
                    />
                    <svg class="w-4 h-4 absolute left-3 top-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <div>
                    <select wire:model.live="severityFilter" class="w-full py-2 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs">
                        <option value="">All Severities</option>
                        <option value="critical">Critical (Rogue / Duplicate)</option>
                        <option value="warning">Warning (MAC Drift)</option>
                        <option value="info">Info (Silent / Stale)</option>
                    </select>
                </div>
                <div>
                    <select wire:model.live="statusFilter" class="w-full py-2 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs">
                        <option value="open">Open / Unresolved Findings</option>
                        <option value="resolved">Resolved Findings</option>
                        <option value="all">All Statuses</option>
                    </select>
                </div>
            </div>
        @endif
    </div>

    <!-- TAB 1: Reconciliation Findings -->
    @if($activeTab === 'findings')
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                            <th class="py-3 px-4">Severity &amp; Type</th>
                            <th class="py-3 px-4">IP Address &amp; Subnet</th>
                            <th class="py-3 px-4">Discrepancy Details</th>
                            <th class="py-3 px-4">Observed vs Authoritative MAC</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 font-mono">
                        @forelse($findings as $finding)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition">
                                <td class="py-3 px-4 font-sans">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $finding->severity->badgeClasses() }}">
                                        {{ $finding->severity->label() }}
                                    </span>
                                    <div class="text-[10px] text-slate-400 mt-1 font-mono">{{ $finding->finding_type->label() }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ $finding->address_text }}</div>
                                    <div class="text-[10px] text-slate-400 font-sans mt-0.5">{{ $finding->subnet ? $finding->subnet->cidr : 'N/A' }} [{{ $finding->vrf->code }}]</div>
                                </td>
                                <td class="py-3 px-4 font-sans max-w-sm">
                                    <div class="font-medium text-slate-800 dark:text-slate-200">{{ $finding->title }}</div>
                                    <div class="text-[11px] text-slate-500 truncate mt-0.5" title="{{ $finding->details }}">{{ $finding->details }}</div>
                                </td>
                                <td class="py-3 px-4 font-mono text-[11px]">
                                    <div><span class="text-slate-400 text-[10px]">OBS:</span> {{ $finding->observed_mac ?: 'N/A' }}</div>
                                    <div><span class="text-slate-400 text-[10px]">AUTH:</span> {{ $finding->authoritative_mac ?: 'None (Unassigned)' }}</div>
                                </td>
                                <td class="py-3 px-4 font-sans">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold border {{ $finding->status->badgeClasses() }}">
                                        {{ $finding->status->label() }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right font-sans space-x-1">
                                    <button
                                        wire:click="openFindingDrawer({{ $finding->id }})"
                                        class="px-2.5 py-1 text-xs rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 transition font-medium"
                                    >
                                        Investigate
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400 font-sans">
                                    No reconciliation findings match the active criteria. Network observations match authoritative IPAM state.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($findings->hasPages())
                <div class="p-4 border-t border-slate-200 dark:border-slate-700">
                    {{ $findings->links() }}
                </div>
            @endif
        </div>
    @endif

    <!-- TAB 2: Observation Stream -->
    @if($activeTab === 'observations')
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-xs">
            <div class="p-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Raw Ingested Network Observations</h3>
                    <p class="text-[11px] text-slate-500">Append-only evidence history gathered by authorized collectors.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider font-sans">
                            <th class="py-3 px-4">Observed At</th>
                            <th class="py-3 px-4">IP Address</th>
                            <th class="py-3 px-4">MAC Address</th>
                            <th class="py-3 px-4">Hostname</th>
                            <th class="py-3 px-4">Source Protocol</th>
                            <th class="py-3 px-4">Confidence</th>
                            <th class="py-3 px-4">Collector Agent</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 font-mono">
                        @forelse($observations as $obs)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition">
                                <td class="py-2.5 px-4 text-slate-400 text-[11px] font-sans">
                                    {{ $obs->observed_at->format('M d, H:i:s') }}
                                </td>
                                <td class="py-2.5 px-4 font-bold text-slate-900 dark:text-white">
                                    {{ $obs->address_text }}
                                </td>
                                <td class="py-2.5 px-4 text-slate-600 dark:text-slate-300">
                                    {{ $obs->observed_mac ?: 'N/A' }}
                                </td>
                                <td class="py-2.5 px-4 text-slate-600 dark:text-slate-300 font-sans text-[11px]">
                                    {{ $obs->hostname ?: 'Unresolved' }}
                                </td>
                                <td class="py-2.5 px-4 font-sans">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-medium border {{ $obs->source_type->badgeClasses() }}">
                                        {{ $obs->source_type->label() }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-4">
                                    <span class="text-slate-700 dark:text-slate-300">{{ $obs->confidence }}%</span>
                                </td>
                                <td class="py-2.5 px-4 font-sans text-slate-500">
                                    {{ $obs->job && $obs->job->agent ? $obs->job->agent->code : 'Manual Fixture' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400 font-sans">
                                    No observations recorded yet. Run a discovery scan to ingest evidence.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($observations->hasPages())
                <div class="p-4 border-t border-slate-200 dark:border-slate-700">
                    {{ $observations->links() }}
                </div>
            @endif
        </div>
    @endif

    <!-- TAB 3: Discovery Agents -->
    @if($activeTab === 'agents')
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-xs">
            <div class="p-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Registered Zone Discovery Collectors</h3>
                    <p class="text-[11px] text-slate-500">Decentralized collectors operating within permitted CIDR boundaries.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider font-sans">
                            <th class="py-3 px-4">Agent Code / Name</th>
                            <th class="py-3 px-4">Site Location</th>
                            <th class="py-3 px-4">Status &amp; Heartbeat</th>
                            <th class="py-3 px-4">Allowed Subnets / CIDRs</th>
                            <th class="py-3 px-4">Rate Limit</th>
                            <th class="py-3 px-4">Last Error</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 font-mono">
                        @forelse($agents as $agent)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition">
                                <td class="py-3 px-4 font-sans">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ $agent->name }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">{{ $agent->code }}</div>
                                </td>
                                <td class="py-3 px-4 font-sans text-slate-700 dark:text-slate-300">
                                    {{ $agent->site ? $agent->site->name : 'Global Campus' }}
                                </td>
                                <td class="py-3 px-4 font-sans">
                                    @if($agent->isHealthy())
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                                            Online ({{ $agent->last_seen_at ? $agent->last_seen_at->diffForHumans() : 'Just now' }})
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 border border-rose-300 dark:border-rose-800 font-bold">
                                            Stale / Offline
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 font-mono text-[11px]">
                                    @if(!empty($agent->allowed_subnets_json))
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($agent->allowed_subnets_json as $c)
                                                <span class="px-1.5 py-0.2 bg-slate-100 dark:bg-slate-700 rounded text-[10px]">{{ $c }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-slate-400 font-sans text-[11px]">All Campus Subnets</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-300">
                                    {{ $agent->rate_limit_pps }} pps
                                </td>
                                <td class="py-3 px-4 font-sans text-[11px] text-slate-400">
                                    {{ $agent->last_error ?: 'None' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400 font-sans">
                                    No discovery agents registered.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- TAB 4: Scan Job History -->
    @if($activeTab === 'jobs')
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-xs">
            <div class="p-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Discovery Scan Job History</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider font-sans">
                            <th class="py-3 px-4">Job ID</th>
                            <th class="py-3 px-4">Subnet Target</th>
                            <th class="py-3 px-4">Collector Agent</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Started / Duration</th>
                            <th class="py-3 px-4">Observations Ingested</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 font-mono">
                        @forelse($jobs as $job)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition">
                                <td class="py-3 px-4 font-bold text-slate-900 dark:text-white">#{{ $job->id }}</td>
                                <td class="py-3 px-4">{{ $job->subnet ? $job->subnet->cidr : 'N/A' }}</td>
                                <td class="py-3 px-4 font-sans">{{ $job->agent ? $job->agent->name : 'N/A' }}</td>
                                <td class="py-3 px-4 font-sans">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold border {{ $job->status->badgeClasses() }}">
                                        {{ $job->status->label() }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-sans text-slate-400 text-[11px]">
                                    {{ $job->started_at ? $job->started_at->format('M d, H:i:s') : 'N/A' }}
                                    @if(isset($job->metrics_json['duration_ms']))
                                        ({{ $job->metrics_json['duration_ms'] }}ms)
                                    @endif
                                </td>
                                <td class="py-3 px-4 font-bold text-emerald-600">
                                    {{ $job->metrics_json['observations_count'] ?? $job->observations()->count() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400 font-sans">No discovery jobs recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Finding Investigation Slide-over Drawer -->
    @if($selectedFinding)
        <div class="fixed inset-0 z-50 overflow-hidden bg-slate-900/50 backdrop-blur-xs flex justify-end">
            <div class="w-full max-w-xl bg-white dark:bg-slate-800 h-full shadow-2xl flex flex-col justify-between overflow-y-auto">
                <div class="p-6 space-y-6">
                    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $selectedFinding->severity->badgeClasses() }}">
                                    {{ $selectedFinding->severity->label() }}
                                </span>
                                <span class="font-mono text-base font-bold text-slate-900 dark:text-white">{{ $selectedFinding->address_text }}</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $selectedFinding->finding_type->label() }}</p>
                        </div>
                        <button wire:click="closeFindingDrawer" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Finding Description & State Comparison -->
                    <div class="bg-slate-50 dark:bg-slate-900/60 rounded-xl p-4 border border-slate-200 dark:border-slate-700 space-y-4 text-xs">
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Finding Title</span>
                            <div class="font-semibold text-slate-900 dark:text-white mt-0.5">{{ $selectedFinding->title }}</div>
                            <p class="text-slate-600 dark:text-slate-300 mt-1 leading-relaxed">{{ $selectedFinding->details }}</p>
                        </div>

                        <!-- Side by Side Observed vs Authoritative -->
                        <div class="grid grid-cols-2 gap-3 p-3 bg-white dark:bg-slate-800 rounded border border-slate-200 dark:border-slate-700 font-mono">
                            <div class="space-y-1">
                                <span class="text-amber-600 dark:text-amber-400 block text-[10px] font-bold uppercase font-sans">Observed Live Evidence</span>
                                <div><span class="text-slate-400 text-[10px]">IP:</span> {{ $selectedFinding->address_text }}</div>
                                <div><span class="text-slate-400 text-[10px]">MAC:</span> {{ $selectedFinding->observed_mac ?: 'N/A' }}</div>
                            </div>
                            <div class="space-y-1">
                                <span class="text-blue-600 dark:text-blue-400 block text-[10px] font-bold uppercase font-sans">Authoritative IPAM State</span>
                                <div><span class="text-slate-400 text-[10px]">Status:</span> {{ $selectedFinding->ipAddress ? $selectedFinding->ipAddress->allocation_status->label() : 'Unallocated' }}</div>
                                <div><span class="text-slate-400 text-[10px]">MAC:</span> {{ $selectedFinding->authoritative_mac ?: 'None' }}</div>
                            </div>
                        </div>

                        <!-- Technical Evidence Reference -->
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold mb-1">Evidence Metadata</span>
                            <div class="bg-slate-900 text-emerald-400 p-3 rounded font-mono text-[11px] overflow-x-auto">
                                <pre>{{ json_encode($selectedFinding->evidence_refs_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                        </div>

                        @if($selectedFinding->resolution_note)
                            <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded">
                                <span class="text-emerald-700 dark:text-emerald-400 block text-[10px] font-semibold uppercase">Resolution Record</span>
                                <p class="text-emerald-800 dark:text-emerald-300 mt-0.5">{{ $selectedFinding->resolution_note }}</p>
                                <div class="text-[10px] text-emerald-600 mt-1">Resolved by {{ $selectedFinding->resolver?->name }} on {{ $selectedFinding->resolved_at?->format('M d, Y H:i') }}</div>
                            </div>
                        @endif
                    </div>

                    <!-- Operator Resolution Form -->
                    @if($selectedFinding->isOpen())
                        <div class="space-y-3 font-sans text-xs">
                            <h4 class="font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Operator Action &amp; Audit</h4>
                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Audit Resolution Note *</label>
                                <textarea
                                    wire:model="resolutionNote"
                                    rows="3"
                                    placeholder="Explain why this finding is resolved (e.g. Registered rogue device in IPAM, verified replacement NIC, acknowledged scheduled maintenance)..."
                                    class="w-full py-2 px-3 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs"
                                ></textarea>
                                @error('resolutionNote') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    wire:click="resolveFinding({{ $selectedFinding->id }})"
                                    class="py-2.5 px-4 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold transition shadow-xs"
                                >
                                    Mark as Resolved
                                </button>
                                <button
                                    wire:click="ignoreFinding({{ $selectedFinding->id }})"
                                    class="py-2.5 px-4 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium transition"
                                >
                                    Ignore False Positive
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Trigger Scan Modal -->
    @if($showScanModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-lg p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Trigger Safe Discovery Scan</h3>
                        <p class="text-xs text-slate-500">Read-only evidence collection within permitted CIDR boundaries.</p>
                    </div>
                    <button wire:click="closeScanModal" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Collector Agent *</label>
                        <select wire:model="scanAgentId" class="w-full py-2 px-3 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs">
                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}">{{ $agent->name }} ({{ $agent->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Target Subnet *</label>
                        <select wire:model="scanSubnetId" class="w-full py-2 px-3 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-mono">
                            @foreach($allSubnets as $sub)
                                <option value="{{ $sub->id }}">{{ $sub->cidr }} [{{ $sub->vrf->code }}] ({{ $sub->name }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Source Protocol *</label>
                        <select wire:model="scanSourceType" class="w-full py-2 px-3 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs">
                            <option value="fixture">Synthetic Safe Simulation (Fixture)</option>
                            <option value="arp">ARP / NDP Neighbor Cache Read</option>
                            <option value="dhcp_lease">DHCP Lease Table Import</option>
                            <option value="icmp">ICMP Ping Sweep (Disabled if Safe Posture active)</option>
                        </select>
                    </div>
                </div>

                <div class="p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 rounded text-[11px] text-amber-800 dark:text-amber-300">
                    <strong>Clinical Safety Guardrail:</strong> Subnets linking to clinical/biomedical zones forbid active ICMP/SNMP sweeps by default. Scans collect read-only passive evidence without mutating allocations.
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-700">
                    <button wire:click="closeScanModal" class="px-3.5 py-1.5 rounded border border-slate-200 text-xs">Cancel</button>
                    <button wire:click="triggerScan" class="px-4 py-1.5 rounded bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-xs">Execute Scan</button>
                </div>
            </div>
        </div>
    @endif
</div>
