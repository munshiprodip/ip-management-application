<div class="space-y-6">
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 font-mono mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600">KYAMCH IPAM</a>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-semibold">Subnets & CIDR</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">Subnet & CIDR Hierarchy Management</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Hierarchical parent-child prefix planner, boundary validation, and non-overlapping IPv4/IPv6 allocation spaces.
            </p>
        </div>
        <div>
            <span class="px-2.5 py-1 rounded text-xs font-mono font-medium bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                Stage 2 Prerequisite (After Stage 1 VRFs)
            </span>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl p-8 border border-slate-200 dark:border-slate-700 text-center">
        <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-400 mb-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
        </div>
        <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">Subnet Hierarchy Module Baseline</h3>
        <p class="text-xs text-slate-500 max-w-md mx-auto mt-1">
            Subnet planning and containment validation require Stage 1 (VRF routing domains & L2 namespaces) to be established first. No artificial placeholder networks are seeded.
        </p>
        <div class="mt-4 flex items-center justify-center gap-3">
            <a href="{{ route('vrfs.index') }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-medium transition">
                View VRF Routing Domains (Stage 1)
            </a>
        </div>
    </div>
</div>
