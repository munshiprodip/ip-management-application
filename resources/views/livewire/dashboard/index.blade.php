<div class="space-y-6">
    <!-- Header / Context Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 font-mono">
                    STAGE 0 BASELINE ACTIVE
                </span>
                <span class="text-xs text-slate-500 font-mono">KYAMCH Network Operations Center</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white mt-1">Enterprise IPAM Control Center</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Authoritative IP address space, VRF routing boundaries, device assignments and audit logs for Khwaja Yunus Ali Medical College & Hospital.
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
                <p class="text-[10px] text-slate-500 mt-1">No autonomous probes or ping sweeps without explicit operator scope.</p>
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
                <p class="text-[10px] text-slate-500 mt-1">SQLite for local development; MySQL InnoDB for production.</p>
            </div>

            <div class="p-3 rounded-lg bg-slate-950/70 border border-slate-800">
                <p class="text-slate-400 text-[11px]">Quarantine Policy</p>
                <p class="text-sm font-bold mt-1 font-mono text-purple-400">
                    {{ $quarantineDays }} DAYS
                </p>
                <p class="text-[10px] text-slate-500 mt-1">Released IPs are held in quarantine; never auto-freed by ping failure.</p>
            </div>
        </div>
    </div>

    <!-- Real Database Metric Cards (Genuine Data from Database) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Authorized Users -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-xs">
            <div class="flex items-center justify-between text-xs text-slate-500">
                <span>Authorized Operators</span>
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            </div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-2 font-mono">{{ $userCount }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Active users with RBAC assignments</p>
        </div>

        <!-- Audit Events -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-xs">
            <div class="flex items-center justify-between text-xs text-slate-500">
                <span>Audit Log Records</span>
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
            </div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-2 font-mono">{{ $auditCount }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Immutable change & authentication events</p>
        </div>

        <!-- VRF Routing Contexts -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-xs">
            <div class="flex items-center justify-between text-xs text-slate-500">
                <span>Routing Contexts (VRF)</span>
                @if($hasVrfsTable)
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                @else
                    <span class="text-[10px] text-slate-400 font-mono">Stage 1</span>
                @endif
            </div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-2 font-mono">{{ $vrfCount }}</p>
            <p class="text-[11px] text-slate-400 mt-1">
                {{ $hasVrfsTable ? 'Configured VRF domains' : 'Prerequisite: Stage 1 Foundation' }}
            </p>
        </div>

        <!-- Subnets / Prefixes -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-xs">
            <div class="flex items-center justify-between text-xs text-slate-500">
                <span>Managed Subnets</span>
                @if($hasSubnetsTable)
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                @else
                    <span class="text-[10px] text-slate-400 font-mono">Stage 2</span>
                @endif
            </div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-2 font-mono">{{ $subnetCount }}</p>
            <p class="text-[11px] text-slate-400 mt-1">
                {{ $hasSubnetsTable ? 'Active parent/child CIDR blocks' : 'Prerequisite: Stage 2 Subnet Hierarchy' }}
            </p>
        </div>
    </div>

    <!-- Stage Roadmap Status & Recent Audit Events -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Stage Roadmap Tracker -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs">
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-3 flex items-center justify-between">
                <span>Implementation Staging Plan</span>
                <span class="text-[11px] font-mono text-emerald-600 dark:text-emerald-400">Master Plan v1.0.0</span>
            </h3>

            <div class="space-y-3 text-xs">
                <div class="p-3 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800">
                    <div class="flex items-center justify-between">
                        <strong class="text-emerald-900 dark:text-emerald-300 font-mono">Stage 0 — Baseline & Architecture</strong>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-600 text-white">COMPLETE</span>
                    </div>
                    <p class="text-[11px] text-emerald-700 dark:text-emerald-400 mt-1">
                        Laravel 13, Livewire 4, Tailwind CSS 4, SQLite local, auth shell, safety feature flags, initial migrations and test suite verified.
                    </p>
                </div>

                <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <strong class="text-slate-800 dark:text-slate-200 font-mono">Stage 1 — Organization & VRF/VLAN</strong>
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-200 dark:bg-slate-600 text-slate-700 dark:text-slate-300">UP NEXT</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                        Sites, departments, roles/permissions, mandatory default VRF, L2 domains and VLAN management.
                    </p>
                </div>

                <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-700 opacity-70">
                    <div class="flex items-center justify-between">
                        <strong class="text-slate-700 dark:text-slate-300 font-mono">Stage 2 — IP Math & Subnets</strong>
                        <span class="text-[10px] text-slate-400 font-mono">QUEUED</span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">
                        IPv4/IPv6 math value objects, parent/child hierarchy, overlap detection and prefix calculator.
                    </p>
                </div>

                <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-700 opacity-70">
                    <div class="flex items-center justify-between">
                        <strong class="text-slate-700 dark:text-slate-300 font-mono">Stage 4 — Allocation MVP</strong>
                        <span class="text-[10px] text-slate-400 font-mono">MVP GATE</span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">
                        Atomic IP allocation, MySQL row-locking, candidate explorer, reservations and quarantine.
                    </p>
                </div>
            </div>
        </div>

        <!-- Genuine Recent Audit Trail (Database-backed) -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Recent System Audit Trail</h3>
                <span class="text-[11px] font-mono text-slate-500">Live Database Records</span>
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
</div>
