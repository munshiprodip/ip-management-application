<div class="space-y-6">
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 font-mono mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600">KYAMCH IPAM</a>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-semibold">Administration</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">System Administration & RBAC</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                User management, role assignments, system parameters, feature flags and infrastructure health.
            </p>
        </div>
        <div>
            <span class="px-2.5 py-1 rounded text-xs font-mono font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                Security & RBAC Baseline
            </span>
        </div>
    </div>

    <!-- Active System Parameters -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Live System Configuration Parameters</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs font-mono">
            <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600">
                <span class="text-slate-400 block text-[11px]">Database Connection</span>
                <span class="text-slate-800 dark:text-slate-200 font-bold uppercase">{{ $dbDriver }}</span>
            </div>
            <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600">
                <span class="text-slate-400 block text-[11px]">Default Hospital Timezone</span>
                <span class="text-slate-800 dark:text-slate-200 font-bold">{{ $timezone }}</span>
            </div>
            <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600">
                <span class="text-slate-400 block text-[11px]">Quarantine Duration</span>
                <span class="text-slate-800 dark:text-slate-200 font-bold">{{ $quarantineDays }} Days</span>
            </div>
        </div>
    </div>

    <!-- Authorized Users Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Authorized System Operators & Personas</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700 text-slate-400 text-[11px] font-mono uppercase">
                        <th class="pb-2.5">Name</th>
                        <th class="pb-2.5">Email</th>
                        <th class="pb-2.5">Assigned Role</th>
                        <th class="pb-2.5">Department</th>
                        <th class="pb-2.5">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 text-xs">
                    @foreach($users as $user)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-700/30">
                            <td class="py-2.5 font-medium text-slate-800 dark:text-slate-200">{{ $user->name }}</td>
                            <td class="py-2.5 text-slate-500 font-mono text-[11px]">{{ $user->email }}</td>
                            <td class="py-2.5">
                                <span class="px-2 py-0.5 rounded text-[10px] font-medium border {{ $user->role->badgeClasses() }}">
                                    {{ $user->role->label() }}
                                </span>
                            </td>
                            <td class="py-2.5 text-slate-600 dark:text-slate-400">{{ $user->department ?? '-' }}</td>
                            <td class="py-2.5">
                                <span class="inline-flex items-center gap-1 font-mono text-[11px] {{ $user->isActive() ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $user->isActive() ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ ucfirst($user->status) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
