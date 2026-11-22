<div class="space-y-6">
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 font-mono mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600">KYAMCH IPAM</a>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-semibold">VLANs</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">VLANs & Layer 2 Broadcast Domains</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                L2 namespace mapping, 802.1Q tags (1–4094), campus switching domains and subnet links.
            </p>
        </div>
        <div>
            <span class="px-2.5 py-1 rounded text-xs font-mono font-medium bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                Scheduled for Stage 1 Delivery
            </span>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl p-8 border border-slate-200 dark:border-slate-700 text-center">
        <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-400 mb-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
        </div>
        <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">VLAN & L2 Domain Namespace</h3>
        <p class="text-xs text-slate-500 max-w-md mx-auto mt-1">
            VLAN uniqueness is enforced per L2 domain <code class="font-mono text-emerald-600">(l2_domain_id, vlan_id)</code>, allowing independent physical buildings or fabric segments to reuse IDs legitimately.
        </p>
    </div>
</div>
