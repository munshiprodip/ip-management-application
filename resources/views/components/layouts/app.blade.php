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

            <a href="{{ route('vlans.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md font-medium transition {{ request()->routeIs('vlans.*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <span>VLAN Management</span>
            </a>

            <a href="{{ route('ip-addresses.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md font-medium transition {{ request()->routeIs('ip-addresses.*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <span>IP Explorer & Allocations</span>
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

