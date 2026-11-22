<div class="space-y-6">
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 font-mono mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600">KYAMCH IPAM</a>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-semibold">Reports</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">Reports & Audit Log Explorer</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Immutable audit trail of all authentication, allocation and state transition events with actor attribution.
            </p>
        </div>
        <div>
            <span class="px-2.5 py-1 rounded text-xs font-mono font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                Audit Trail Live (Stage 0)
            </span>
        </div>
    </div>

    <!-- Live Audit Event Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Immutable Event Log</h2>
            <span class="text-xs text-slate-500 font-mono">Total Records: {{ $auditEvents->total() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700 text-slate-400 text-[11px] font-mono uppercase">
                        <th class="pb-2.5">ID</th>
                        <th class="pb-2.5">Timestamp</th>
                        <th class="pb-2.5">Event Type</th>
                        <th class="pb-2.5">Actor</th>
                        <th class="pb-2.5">Subject</th>
                        <th class="pb-2.5">IP Address</th>
                        <th class="pb-2.5">Reason / Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 font-mono text-[11px]">
                    @forelse($auditEvents as $event)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-700/30">
                            <td class="py-2.5 text-slate-400">#{{ $event->id }}</td>
                            <td class="py-2.5 text-slate-500 whitespace-nowrap">
                                {{ $event->created_at->setTimezone(config('ipam.default_timezone'))->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="py-2.5">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                                    {{ $event->event_type }}
                                </span>
                            </td>
                            <td class="py-2.5 text-slate-700 dark:text-slate-300 font-sans">
                                {{ $event->actor_name ?? 'System' }}
                            </td>
                            <td class="py-2.5 text-slate-600 dark:text-slate-400">
                                {{ $event->subject_label ?? '-' }}
                            </td>
                            <td class="py-2.5 text-slate-500">
                                {{ $event->ip_address ?? '-' }}
                            </td>
                            <td class="py-2.5 text-slate-600 dark:text-slate-400 font-sans">
                                {{ $event->reason ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">No audit events recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $auditEvents->links() }}
        </div>
    </div>
</div>
