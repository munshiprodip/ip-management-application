<div class="space-y-6">
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 font-mono mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600">KYAMCH IPAM</a>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-semibold">IP Addresses</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">IP Address Explorer & Records</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Sparse materialized authoritative addresses. Independent separation of Allocation Status from Observed Reachability.
            </p>
        </div>
        <div>
            <span class="px-2.5 py-1 rounded text-xs font-mono font-medium bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                Core MVP Gate (Stage 4)
            </span>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl p-8 border border-slate-200 dark:border-slate-700 text-center">
        <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-400 mb-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">Atomic IP Address Allocation & Explorer</h3>
        <p class="text-xs text-slate-500 max-w-md mx-auto mt-1">
            Addresses are allocated atomically under transaction with row locks and duplicate catch. Offline addresses never automatically transition to free.
        </p>
    </div>
</div>

