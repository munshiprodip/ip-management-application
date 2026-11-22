<div class="space-y-6">
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 font-mono mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600">KYAMCH IPAM</a>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-semibold">Alerts</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">Alerts & Operational Event Management</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Deduplicated alerts, reconciliation discrepancies, subnet exhaustion warnings and stale device tracking.
            </p>
        </div>
        <div>
            <span class="px-2.5 py-1 rounded text-xs font-mono font-medium bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                Scheduled for Stage 7 Delivery
            </span>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl p-8 border border-slate-200 dark:border-slate-700 text-center">
        <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-400 mb-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        </div>
        <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">Alert Deduplication & Escalation Framework</h3>
        <p class="text-xs text-slate-500 max-w-md mx-auto mt-1">
            System monitors for duplicate active IPs, MAC address flapping, agent dropouts and DNS/DHCP mismatches.
        </p>
    </div>
</div>
