<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50 dark:bg-slate-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'KYAMCH Smart IPAM' }} - Hospital Network & IP Address Management</title>

    <!-- Tailwind CSS 4 & Vite Bundle -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans antialiased text-slate-800 dark:text-slate-100 flex flex-col md:flex-row overflow-hidden">
    <!-- Sidebar / Enterprise Navigation -->
    <aside class="w-full md:w-64 bg-slate-900 text-slate-200 flex flex-col flex-shrink-0 border-r border-slate-800 shadow-xl z-30">
        <!-- Brand Header -->
        <div class="px-5 py-4 border-b border-slate-800 bg-slate-950/70 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-lg bg-emerald-600 flex items-center justify-center font-bold text-white shadow-md shadow-emerald-900/30">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-sm font-semibold tracking-wide text-white leading-tight">KYAMCH IPAM</h1>
                    <p class="text-[11px] text-slate-400 font-mono tracking-wider">HOSPITAL NETWORK</p>
                </div>
            </div>
        </div>

        <!-- Safety Status Banner -->
        <div class="px-4 py-2.5 bg-slate-950/40 border-b border-slate-800/80 text-[11px] flex flex-col gap-1.5">
            <div class="flex items-center justify-between">
                <span class="text-slate-400">Discovery Engine:</span>
                @if(config('ipam.discovery_enabled'))
                    <span class="inline-flex items-center gap-1 font-semibold text-amber-400 font-mono">
                        <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span> ACTIVE
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 font-semibold text-emerald-400 font-mono">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> SAFE / OFF
                    </span>
                @endif
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-400">Network Writes:</span>
                @if(config('ipam.external_writes_enabled'))
                    <span class="inline-flex items-center gap-1 font-semibold text-rose-400 font-mono">
                        <span class="w-2 h-2 rounded-full bg-rose-400"></span> ENABLED
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 font-semibold text-slate-300 font-mono">
                        <span class="w-2 h-2 rounded-full bg-slate-500"></span> LOCKED
                    </span>
                @endif
            </div>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 overflow-y-auto px-3 py-3 space-y-1 text-xs">
            <div class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">Core IPAM</div>

            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-md font-medium transition {{ request()->routeIs('dashboard') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('subnets.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md font-medium transition {{ request()->routeIs('subnets.*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                <span>Subnets & CIDR Hierarchy</span>
            </a>

            <a href="{{ route('vrfs.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md font-medium transition {{ request()->routeIs('vrfs.*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <span>VRF Routing Contexts</span>
            </a>

            <a href="{{ route('vlans.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md font-medium transition {{ request()->routeIs('vlans.*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <span>VLANs & L2 Domains</span>
            </a>

            <a href="{{ route('ip-addresses.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md font-medium transition {{ request()->routeIs('ip-addresses.*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <span>IP Explorer & Records</span>
            </a>

            <div class="pt-3 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">Operations & Assets</div>

            <a href="{{ route('devices.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md font-medium transition {{ request()->routeIs('devices.*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <span>Devices & Interfaces</span>
            </a>

            <a href="{{ route('requests.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md font-medium transition {{ request()->routeIs('requests.*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                <span>Requests & Approvals</span>
            </a>

            <a href="{{ route('discovery.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md font-medium transition {{ request()->routeIs('discovery.*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span>Discovery & Reconcile</span>
            </a>

            <a href="{{ route('alerts.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md font-medium transition {{ request()->routeIs('alerts.*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <span>Alerts & Events</span>
            </a>

            <div class="pt-3 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">Governance</div>

            <a href="{{ route('reports.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md font-medium transition {{ request()->routeIs('reports.*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Reports & Audit Trail</span>
            </a>

            <a href="{{ route('admin.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md font-medium transition {{ request()->routeIs('admin.*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>System Administration</span>
            </a>
        </nav>

        <!-- Current Operator Profile & Logout -->
        <div class="p-3 border-t border-slate-800 bg-slate-950/60 flex items-center justify-between text-xs">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-full bg-slate-700 text-slate-200 font-semibold flex items-center justify-center flex-shrink-0 text-xs">
                    {{ substr(auth()->user()->name ?? 'Op', 0, 2) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-medium text-slate-200 truncate leading-tight">{{ auth()->user()->name ?? 'Operator' }}</p>
                    <p class="text-[11px] text-slate-400 font-mono truncate">{{ auth()->user()->role?->value ?? 'viewer' }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="Sign Out" class="p-1.5 text-slate-400 hover:text-rose-400 rounded hover:bg-slate-800 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-slate-100 dark:bg-slate-900">
        <!-- Top App Bar -->
        <header class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 px-6 py-3.5 flex items-center justify-between shadow-xs z-10">
            <div class="flex items-center gap-3">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white leading-tight">
                    {{ $header ?? 'KYAMCH Smart IPAM Operations' }}
                </h2>
                <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-mono">
                    {{ config('ipam.system_code') }} v1.0.0
                </span>
            </div>

            <!-- Context Status Pills -->
            <div class="flex items-center gap-3 text-xs">
                <div class="hidden lg:flex items-center gap-2 px-2.5 py-1 rounded bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-mono">
                    <span class="text-slate-400">DB:</span> {{ config('database.default') }}
                </div>
                <div class="hidden md:flex items-center gap-2 px-2.5 py-1 rounded bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-mono">
                    <span class="text-slate-400">TZ:</span> {{ config('ipam.default_timezone') }}
                </div>
                <div class="flex items-center gap-1.5 px-2.5 py-1 rounded bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-[11px] font-medium font-mono">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> SAFE
                </div>
            </div>
        </header>

        <!-- Flash Notifications -->
        @if (session('success'))
            <div class="mx-6 mt-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-md shadow-xs flex items-center justify-between">
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if (session('error'))
            <div class="mx-6 mt-4 p-3 bg-rose-50 border border-rose-200 text-rose-800 text-xs rounded-md shadow-xs flex items-center justify-between">
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Scrollable Page Content Slot -->
        <main class="flex-1 overflow-y-auto p-6">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
