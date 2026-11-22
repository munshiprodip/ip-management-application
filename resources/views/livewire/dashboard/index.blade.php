<div class="space-y-6">
    <!-- Header / Context Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 font-mono">
                    STREAMLINED IPAM CORE
                </span>
                <span class="text-xs text-slate-500 font-mono">KYAMCH Network Operations Center</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white mt-1">Network &amp; IP Address Management</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Authoritative Subnet & CIDR hierarchy, 802.1Q VLAN management, and atomic IP address allocations for Khwaja Yunus Ali Medical College & Hospital.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-700 font-mono text-slate-700 dark:text-slate-300">
                Operator: <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->role?->value ?? 'viewer' }})
            </span>
            <span class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-700 font-mono text-slate-700 dark:text-slate-300">
                Timezone: {{ $defaultTimezone }}
            </span>
        </div>
    </div>

    <!-- Quick Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Managed Subnets -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-xs">
            <div class="flex items-center justify-between text-xs text-slate-500">
                <span>Managed Subnets</span>
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            </div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-2 font-mono">{{ $subnetCount }}</p>
            <div class="flex items-center gap-2 mt-2 text-[11px] text-slate-500 font-mono">
                <span class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-700">IPv4: {{ $ipv4SubnetCount }}</span>
                <span class="px-1.5 py-0.5 rounded bg-purple-50 text-purple-700">IPv6: {{ $ipv6SubnetCount }}</span>
            </div>
        </div>

        <!-- 802.1Q VLANs -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-xs">
            <div class="flex items-center justify-between text-xs text-slate-500">
                <span>Configured VLANs</span>
                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
            </div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-2 font-mono">{{ $vlanCount }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Campus broadcast domains (1-4094)</p>
        </div>

        <!-- Active IP Allocations -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-xs">
            <div class="flex items-center justify-between text-xs text-slate-500">
                <span>Assigned IP Addresses</span>
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
            </div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-2 font-mono">{{ $assignedIpCount }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Authoritative active host bindings</p>
        </div>

        <!-- Reserved & Quarantined IPs -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-xs">
            <div class="flex items-center justify-between text-xs text-slate-500">
                <span>Reserved / Quarantined</span>
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
            </div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-2 font-mono">{{ $reservedIpCount + $quarantinedIpCount }}</p>
            <div class="flex items-center gap-2 mt-2 text-[11px] text-slate-500 font-mono">
                <span class="text-amber-600">Reserved: {{ $reservedIpCount }}</span>
                <span class="text-purple-600">Quarantined: {{ $quarantinedIpCount }}</span>
            </div>
        </div>
    </div>

    <!-- Subnet Capacity Watchlist -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
            <div>
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Subnet Capacity Watchlist</h2>
                <p class="text-xs text-slate-500">Top utilized network segments sorted by allocation percentage</p>
            </div>
            <a href="{{ route('subnets.index') }}" class="text-xs text-emerald-600 hover:text-emerald-700 font-medium">
                View all subnets &rarr;
            </a>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="text-[11px] uppercase bg-slate-50 dark:bg-slate-700/50 text-slate-500 font-semibold">
                    <tr>
                        <th class="px-4 py-2.5">Subnet CIDR</th>
                        <th class="px-4 py-2.5">Name</th>
                        <th class="px-4 py-2.5">VLAN</th>
                        <th class="px-4 py-2.5">Allocated / Total</th>
                        <th class="px-4 py-2.5">Utilization</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse ($capacityWatchlist as $item)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                            <td class="px-4 py-3 font-mono font-semibold text-slate-900 dark:text-white">
                                {{ $item['subnet']->cidr }}
                            </td>
                            <td class="px-4 py-3 text-slate-700 dark:text-slate-300">
                                {{ $item['subnet']->name }}
                            </td>
                            <td class="px-4 py-3">
                                @if($item['subnet']->vlan)
                                    <span class="px-2 py-0.5 rounded text-[11px] font-mono bg-indigo-50 text-indigo-700">
                                        VLAN {{ $item['subnet']->vlan->vlan_id }}
                                    </span>
                                @else
                                    <span class="text-slate-400 font-mono">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-slate-600 dark:text-slate-300">
                                {{ number_format($item['allocated']) }} / {{ number_format($item['total']) }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-24 bg-slate-200 dark:bg-slate-700 rounded-full h-2 overflow-hidden">
                                        <div class="h-2 rounded-full {{ $item['utilization'] >= 90 ? 'bg-rose-500' : ($item['utilization'] >= 75 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ min(100, $item['utilization']) }}%"></div>
                                    </div>
                                    <span class="font-mono text-[11px] font-semibold {{ $item['utilization'] >= 90 ? 'text-rose-600' : ($item['utilization'] >= 75 ? 'text-amber-600' : 'text-slate-700 dark:text-slate-300') }}">
                                        {{ $item['utilization'] }}%
                                    </span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-400">
                                No subnets registered yet. Create your first subnet in Subnets & CIDR Hierarchy.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Quick Launch Bar -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="{{ route('subnets.index') }}" class="p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs hover:border-emerald-500 transition flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Subnets & CIDR Hierarchy</h3>
                <p class="text-[11px] text-slate-500">Calculate CIDR, view hierarchy tree, preview splits</p>
            </div>
        </a>

        <a href="{{ route('vlans.index') }}" class="p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs hover:border-emerald-500 transition flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">VLAN Management</h3>
                <p class="text-[11px] text-slate-500">Manage 802.1Q tags (1-4094) and security zones</p>
            </div>
        </a>

        <a href="{{ route('ip-addresses.index') }}" class="p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs hover:border-emerald-500 transition flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">IP Address Explorer</h3>
                <p class="text-[11px] text-slate-500">Virtual candidate window, atomic allocation & quarantine</p>
            </div>
        </a>
    </div>

    <!-- Audit Event Stream -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Recent Operational Audit Events</h2>
        <div class="space-y-2 text-xs">
            @forelse ($recentAudits as $audit)
                <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-700/40 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded font-mono text-[10px] bg-slate-200 text-slate-700">
                            {{ $audit->event_type }}
                        </span>
                        <span class="text-slate-800 dark:text-slate-200 font-medium">
                            {{ $audit->reason ?? 'System action recorded' }}
                        </span>
                    </div>
                    <span class="text-[11px] text-slate-400 font-mono">
                        {{ $audit->created_at->diffForHumans() }}
                    </span>
                </div>
            @empty
                <p class="text-slate-400 py-2">No audit events recorded yet.</p>
            @endforelse
        </div>
    </div>
</div>
