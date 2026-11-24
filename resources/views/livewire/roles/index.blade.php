<div class="space-y-6">
    <!-- Header / Context Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 sm:p-5 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 font-mono mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600">KYAMCH IPAM</a>
                <span>/</span>
                <span>Administration</span>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-semibold">Roles &amp; Permissions</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">Role &amp; Permission Governance</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Authoritative three-tier security access model: Super Admin, Admin, and User.
            </p>
        </div>

        <div>
            <a
                href="{{ route('users.index') }}"
                class="w-full sm:w-auto justify-center inline-flex items-center gap-2 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold shadow-xs transition"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <span>Manage Users</span>
            </a>
        </div>
    </div>

    <!-- The 3 System Role Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        @foreach($roles as $r)
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs p-5 flex flex-col justify-between space-y-4">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold border {{ $r['badge'] }}">
                            {{ $r['name'] }}
                        </span>
                        <span class="text-xs font-mono font-semibold text-slate-500 dark:text-slate-400">
                            {{ $r['count'] }} active {{ Str::plural('user', $r['count']) }}
                        </span>
                    </div>

                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ $r['summary'] }}</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                            {{ $r['description'] }}
                        </p>
                    </div>

                    <div class="pt-2 border-t border-slate-100 dark:border-slate-700/60 space-y-2">
                        <span class="text-[10px] font-mono uppercase text-slate-400 font-bold block">Assigned Capabilities</span>
                        <ul class="space-y-1.5 text-xs text-slate-600 dark:text-slate-300">
                            @foreach($r['capabilities'] as $cap)
                                <li class="flex items-start gap-2">
                                    @if(str_starts_with($cap, 'Restricted:'))
                                        <svg class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        <span class="text-slate-400 dark:text-slate-500 italic">{{ $cap }}</span>
                                    @else
                                        <svg class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        <span>{{ $cap }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between text-xs">
                    <span class="text-slate-400 font-mono text-[11px]">Role identifier: <code class="text-slate-700 dark:text-slate-300 font-bold">{{ $r['key'] }}</code></span>
                    <a href="{{ route('users.index', ['role' => $r['key']]) }}" class="text-emerald-600 hover:text-emerald-500 font-semibold text-[11px] flex items-center gap-1">
                        <span>View members</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Permission Comparison Matrix -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-base font-bold text-slate-900 dark:text-white">Permission Comparison Matrix</h2>
            <p class="text-xs text-slate-500">Comprehensive breakdown of access privileges across all functional modules.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-900/60 text-[11px] font-mono text-slate-500 border-b border-slate-200 dark:border-slate-700 uppercase">
                    <tr>
                        <th class="py-3 px-5 font-semibold">Capability / Operation</th>
                        <th class="py-3 px-4 font-semibold text-center w-36 text-purple-700 dark:text-purple-400">Super Admin</th>
                        <th class="py-3 px-4 font-semibold text-center w-36 text-blue-700 dark:text-blue-400">Admin</th>
                        <th class="py-3 px-4 font-semibold text-center w-36 text-emerald-700 dark:text-emerald-400">User</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 font-sans">
                    @foreach($matrix as $sec)
                        <tr class="bg-slate-50/50 dark:bg-slate-900/30">
                            <td colspan="4" class="py-2 px-5 font-mono text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                {{ $sec['category'] }}
                            </td>
                        </tr>
                        @foreach($sec['features'] as $f)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/20 transition">
                                <td class="py-2.5 px-5 font-medium text-slate-800 dark:text-slate-200">
                                    {{ $f['name'] }}
                                </td>
                                <!-- Super Admin -->
                                <td class="py-2.5 px-4 text-center">
                                    @if($f['super_admin'])
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 text-xs font-bold">✓</span>
                                    @else
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800 text-xs">✕</span>
                                    @endif
                                </td>
                                <!-- Admin -->
                                <td class="py-2.5 px-4 text-center">
                                    @if($f['admin'])
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 text-xs font-bold">✓</span>
                                    @else
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400 text-xs font-bold">✕</span>
                                    @endif
                                </td>
                                <!-- User -->
                                <td class="py-2.5 px-4 text-center">
                                    @if($f['user'])
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 text-xs font-bold">✓</span>
                                    @else
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400 text-xs font-bold">✕</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- User Roster by Role -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-white">Active Member Roster</h2>
                <p class="text-xs text-slate-500">Personnel currently assigned to each security tier.</p>
            </div>

            <div class="flex items-center gap-2">
                <select
                    wire:model.live="selectedRole"
                    class="px-3 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                >
                    <option value="all">All Roles ({{ $superAdminCount + $adminCount + $userCount }})</option>
                    <option value="super_admin">Super Admin ({{ $superAdminCount }})</option>
                    <option value="admin">Admin ({{ $adminCount }})</option>
                    <option value="user">User ({{ $userCount }})</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-900/60 text-[11px] font-mono text-slate-500 border-b border-slate-200 dark:border-slate-700 uppercase">
                    <tr>
                        <th class="py-3 px-4 font-semibold">User</th>
                        <th class="py-3 px-4 font-semibold">Role Tier</th>
                        <th class="py-3 px-4 font-semibold">Department</th>
                        <th class="py-3 px-4 font-semibold">Status</th>
                        <th class="py-3 px-4 font-semibold text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 font-sans">
                    @forelse($rosterUsers as $ru)
                        @php
                            $rEnum = $ru->role instanceof \App\Domain\Security\Enums\UserRole ? $ru->role : \App\Domain\Security\Enums\UserRole::tryFrom((string)$ru->role);
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition">
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold flex items-center justify-center text-xs shrink-0 uppercase">
                                        {{ substr($ru->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <span class="font-semibold text-slate-900 dark:text-white">{{ $ru->name }}</span>
                                        <div class="text-[11px] text-slate-400 font-mono">{{ $ru->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[11px] font-semibold border {{ $rEnum ? $rEnum->badgeClasses() : 'bg-slate-100 text-slate-700' }}">
                                    {{ $rEnum ? $rEnum->label() : $ru->role }}
                                </span>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap text-slate-800 dark:text-slate-200">
                                {{ $ru->department ?: '—' }}
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                @if($ru->status === 'active')
                                    <span class="text-emerald-600 font-medium text-[11px]">Active</span>
                                @else
                                    <span class="text-slate-400 font-medium text-[11px]">Inactive</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <a
                                    href="{{ route('users.index', ['q' => $ru->email]) }}"
                                    class="text-emerald-600 hover:text-emerald-500 font-medium text-xs"
                                >
                                    Manage User &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400">
                                No active users found for selected role.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rosterUsers->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40">
                {{ $rosterUsers->links() }}
            </div>
        @endif
    </div>
</div>

