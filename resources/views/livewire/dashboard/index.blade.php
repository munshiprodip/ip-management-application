<div class="space-y-6">
    <!-- Header / Context Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 font-mono">
                    STAGE 7 OPERATIONAL POSTURE
                </span>
                <span class="text-xs text-slate-500 font-mono">KYAMCH Network Operations Center</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white mt-1">Enterprise IPAM Control Center</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Authoritative IP space, subnet hierarchy, device assignments, operational alerting and audited reporting for Khwaja Yunus Ali Medical College & Hospital.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-700 font-mono text-slate-700 dark:text-slate-300">
                Operator: <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->role->label() }})
            </span>
            <span class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-700 font-mono text-slate-700 dark:text-slate-300">
                Timezone: {{ $defaultTimezone }}
            </span>
        </div>
    </div>

    <!-- Mandatory Safety Guardrails Card (Authoritative Invariants) -->
    <div class="bg-slate-900 text-slate-100 rounded-xl p-5 border border-slate-800 shadow-md">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                <h2 class="text-sm font-semibold tracking-wide uppercase text-slate-200">System Safety Posture & Non-Negotiable Invariants</h2>
            </div>
            <span class="text-[11px] font-mono text-emerald-400 font-medium">PROTECTION ACTIVE</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4 text-xs">
            <div class="p-3 rounded-lg bg-slate-950/70 border border-slate-800">
                <p class="text-slate-400 text-[11px]">Automated Discovery</p>
                <p class="text-sm font-bold mt-1 font-mono {{ $discoveryEnabled ? 'text-amber-400' : 'text-emerald-400' }}">
                    {{ $discoveryEnabled ? 'ENABLED (CAUTION)' : 'DISABLED (SAFE)' }}
                </p>
                <p class="text-[10px] text-slate-500 mt-1">Evidence-only; observations never overwrite authoritative allocation (T-018).</p>
            </div>

            <div class="p-3 rounded-lg bg-slate-950/70 border border-slate-800">
                <p class="text-slate-400 text-[11px]">External Network Writes</p>
                <p class="text-sm font-bold mt-1 font-mono {{ $writesEnabled ? 'text-rose-400' : 'text-slate-300' }}">
                    {{ $writesEnabled ? 'ENABLED' : 'LOCKED (READ-ONLY)' }}
                </p>
                <p class="text-[10px] text-slate-500 mt-1">DHCP/DNS/Switch write-back disabled by default in baseline.</p>
            </div>

            <div class="p-3 rounded-lg bg-slate-950/70 border border-slate-800">
                <p class="text-slate-400 text-[11px]">Database Engine</p>
                <p class="text-sm font-bold mt-1 font-mono text-blue-400 uppercase">
                    {{ $databaseDriver }}
                </p>
                <p class="text-[10px] text-slate-500 mt-1">SQLite for local development; MySQL InnoDB for production concurrency.</p>
            </div>

            <div class="p-3 rounded-lg bg-slate-950/70 border border-slate-800">
                <p class="text-slate-400 text-[11px]">Quarantine Policy</p>
                <p class="text-sm font-bold mt-1 font-mono text-purple-400">
                    {{ $quarantineDays }} DAYS
                </p>
                <p class="text-[10px] text-slate-500 mt-1">Released IPs held in quarantine (T-012); never freed by ping silence.</p>
            </div>
        </div>
    </div>

    <!-- Real Database Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Managed Subnets & Capacity -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-xs">
            <div class="flex items-center justify-between text-xs text-slate-500">
                <span>Managed Subnets</span>
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            </div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-2 font-mono">{{ $subnetCount }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Across {{ $vrfCount }} VRF routing contexts</p>
        </div>

        <!-- IP Allocations & Capacity -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-xs">
            <div class="flex items-center justify-between text-xs text-slate-500">
                <span>Active IP Addresses</span>
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
            </div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-2 font-mono">{{ $assignedIpCount }}</p>
            <p class="text-[11px] text-slate-400 mt-1">
                {{ $reservedIpCount }} reserved &bull; {{ $quarantinedIpCount }} in quarantine
            </p>
        </div>

        <!-- Devices & Inventory -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-xs">
            <div class="flex items-center justify-between text-xs text-slate-500">
                <span>Hardware Devices</span>
                <span class="w-2 h-2 rounded-full bg-purple-500"></span>
            </div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-2 font-mono">{{ $deviceCount }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Servers, switches, clinical & workstations</p>
        </div>

        <!-- Open Operational Alerts -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-xs">
            <div class="flex items-center justify-between text-xs text-slate-500">
                <span>Operational Alerts</span>
                @if ($criticalAlertCount > 0)
                    <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
                @else
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                @endif
            </div>
            <p class="text-2xl font-bold {{ $criticalAlertCount > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }} mt-2 font-mono">
                {{ $openAlertCount }}
            </p>
            <p class="text-[11px] text-slate-400 mt-1">
                {{ $criticalAlertCount }} critical &bull; {{ $pendingRequestCount }} pending approvals
            </p>
        </div>
    </div>

    <!-- Network Views & Widgets -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Subnet Capacity & Exhaustion Watchlist -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Subnet Capacity & Exhaustion Watchlist</h3>
                    <p class="text-[11px] text-slate-400">Monitoring prefixes approaching threshold limits (Threshold: >= 80%).</p>
                </div>
                <a href="{{ route('subnets.index') }}" class="text-xs text-emerald-600 hover:text-emerald-700 font-medium">View all &rarr;</a>
            </div>

            @if ($capacityWatchlist->isEmpty())
                <p class="text-xs text-slate-400 py-4 text-center">No subnets configured yet.</p>
            @else
                <div class="space-y-3">
                    @foreach ($capacityWatchlist as $item)
                        @php
                            $sub = $item['subnet'];
                            $pct = $item['utilization'];
                            $barColor = $pct >= 95 ? 'bg-rose-500' : ($pct >= 80 ? 'bg-amber-500' : 'bg-emerald-500');
                        @endphp
                        <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700/60 text-xs">
                            <div class="flex items-center justify-between mb-1.5">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-slate-900 dark:text-white">{{ $sub->cidr }}</span>
                                    <span class="text-slate-500 text-[11px]">({{ $sub->name }})</span>
                                </div>
                                <span class="font-mono font-semibold {{ $pct >= 80 ? 'text-amber-600 dark:text-amber-400 font-bold' : 'text-slate-600 dark:text-slate-400' }}">
                                    {{ $pct }}% ({{ $item['allocated'] }}/{{ $item['total'] }})
                                </span>
                            </div>
                            <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                                <div class="{{ $barColor }} h-1.5 rounded-full" style="width: {{ min($pct, 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Active Alerts Widget -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Active Operational Alerts</h3>
                    <p class="text-[11px] text-slate-400">Deduplicated operational events requiring NOC attention.</p>
                </div>
                <a href="{{ route('alerts.index') }}" class="text-xs text-emerald-600 hover:text-emerald-700 font-medium">Alerts Console &rarr;</a>
            </div>

            @if ($activeAlerts->isEmpty())
                <div class="p-6 text-center text-xs text-slate-400">
                    <svg class="w-8 h-8 mx-auto text-emerald-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    All systems operational. Zero active alerts.
                </div>
            @else
                <div class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @foreach ($activeAlerts as $alert)
                        <div class="py-2.5 flex items-center justify-between gap-3 text-xs">
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span class="px-1.5 py-0.5 rounded text-[10px] border {{ $alert->severity->badgeClasses() }}">
                                        {{ $alert->severity->label() }}
                                    </span>
                                    <span class="font-semibold text-slate-900 dark:text-white truncate">{{ $alert->title }}</span>
                                    @if ($alert->occurrence_count > 1)
                                        <span class="px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-amber-100 text-amber-800">
                                            {{ $alert->occurrence_count }}x
                                        </span>
                                    @endif
                                </div>
                                <p class="text-[11px] text-slate-500 font-mono mt-0.5">{{ Str::limit($alert->dedupe_key, 35) }}</p>
                            </div>
                            <span class="text-[10px] text-slate-400 font-mono whitespace-nowrap">
                                {{ $alert->last_occurred_at->diffForHumans() }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Recent Audit Trail -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Recent System Audit Trail</h3>
                <p class="text-[11px] text-slate-400">Immutable chronological record of administrative and allocation events.</p>
            </div>
            <a href="{{ route('reports.index') }}" class="text-xs text-emerald-600 hover:text-emerald-700 font-medium">Audit Explorer &rarr;</a>
        </div>

        @if($recentAudits->isEmpty())
            <div class="p-8 text-center text-xs text-slate-400">
                No audit records registered yet.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 text-slate-400 text-[11px] font-mono uppercase">
                            <th class="pb-2">Timestamp</th>
                            <th class="pb-2">Event Type</th>
                            <th class="pb-2">Actor</th>
                            <th class="pb-2">Description / Reason</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 font-mono text-[11px]">
                        @foreach($recentAudits as $audit)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-700/30">
                                <td class="py-2.5 text-slate-500 whitespace-nowrap">
                                    {{ $audit->created_at->setTimezone($defaultTimezone)->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="py-2.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                                        {{ $audit->event_type }}
                                    </span>
                                </td>
                                <td class="py-2.5 text-slate-700 dark:text-slate-300 font-sans">
                                    {{ $audit->actor_name }}
                                </td>
                                <td class="py-2.5 text-slate-600 dark:text-slate-400 font-sans">
                                    {{ $audit->reason ?? $audit->subject_label ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
