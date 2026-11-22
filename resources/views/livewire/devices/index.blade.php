<div class="space-y-6">
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 font-mono mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600">KYAMCH IPAM</a>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-semibold">Devices</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">Device & Network Interface Inventory</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Hospital servers, switches, routers, workstations and biomedical instruments with multi-NIC topology.
            </p>
        </div>
        <div>
            <span class="px-2.5 py-1 rounded text-xs font-mono font-medium bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                Scheduled for Stage 3 Delivery
            </span>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl p-8 border border-slate-200 dark:border-slate-700 text-center">
        <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-400 mb-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        </div>
        <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">Device Identity Independent from IP</h3>
        <p class="text-xs text-slate-500 max-w-md mx-auto mt-1">
            Device entities hold physical locations, asset tags and 0..N interfaces. Historical assignments persist when addresses are transferred or released.
        </p>
    </div>
</div>

