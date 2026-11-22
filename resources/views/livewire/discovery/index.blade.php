<div class="space-y-6">
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 font-mono mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600">KYAMCH IPAM</a>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-semibold">Discovery</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">Discovery & Reconciliation</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Authorized zone agents, read-only evidence collection, observation deduplication and conflict detection.
            </p>
        </div>
        <div>
            <span class="px-2.5 py-1 rounded text-xs font-mono font-medium {{ $discoveryEnabled ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' }} border border-emerald-200 dark:border-emerald-800">
                Safe Posture: {{ $discoveryEnabled ? 'ACTIVE' : 'DISABLED (SAFE)' }}
            </span>
        </div>
    </div>

    <!-- Safety Guardrail Alert -->
    <div class="p-4 rounded-xl bg-slate-900 text-slate-100 border border-slate-800">
        <div class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-lg bg-emerald-600/30 text-emerald-400 flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <div class="text-xs space-y-1">
                <h3 class="font-semibold text-slate-100">Safe Evidence Principle: Observations Never Overwrite Allocations</h3>
                <p class="text-slate-400 leading-relaxed">
                    Under the KYAMCH IPAM architecture contract, discovered observations (ARP, ICMP, DHCP, DNS, SNMP) are strictly treated as <strong>evidence</strong>, not administrative ownership. Lack of ping response does not release an IP address, and an unallocated active IP triggers a reconciliation finding rather than mutating records silently.
                </p>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl p-8 border border-slate-200 dark:border-slate-700 text-center">
        <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">Scheduled for Stage 6 Implementation</h3>
        <p class="text-xs text-slate-500 max-w-md mx-auto mt-1">
            Discovery agents will operate within permitted CIDR allowlists. Live target probing defaults to disabled.
        </p>
    </div>
</div>
