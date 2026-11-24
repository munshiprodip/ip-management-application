<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50 dark:bg-slate-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'KYAMCH Smart IPAM' }} - Hospital Network & IP Address Management</title>

    <!-- Tailwind CSS 4 & Vite Bundle -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body 
    x-data="{ mobileMenuOpen: false }" 
    @keydown.escape.window="mobileMenuOpen = false"
    class="h-full font-sans antialiased text-slate-800 dark:text-slate-100 flex flex-col md:flex-row overflow-hidden"
>
    <!-- Off-Canvas Mobile Drawer Backdrop (Mobile only) -->
    <div 
        x-show="mobileMenuOpen" 
        x-cloak
        x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="mobileMenuOpen = false"
        class="fixed inset-0 z-40 bg-slate-950/70 backdrop-blur-xs md:hidden"
        aria-hidden="true"
    ></div>

    <!-- Mobile Slide-over Navigation Drawer (Mobile only) -->
    <div 
        x-show="mobileMenuOpen" 
        x-cloak
        x-transition:enter="transition ease-in-out duration-200 transform"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in-out duration-200 transform"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed inset-y-0 left-0 z-50 w-72 max-w-[85vw] bg-slate-900 text-slate-200 flex flex-col border-r border-slate-800 shadow-2xl md:hidden"
    >
        <!-- Mobile Drawer Header -->
        <div class="px-4 py-3.5 border-b border-slate-800 bg-slate-950/80 flex items-center justify-between">
            <div class="flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-lg bg-emerald-600 flex items-center justify-center font-bold text-white shadow-md shadow-emerald-900/30">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-sm font-semibold tracking-wide text-white leading-tight">KYAMCH IPAM</h1>
                    <p class="text-[10px] text-slate-400 font-mono tracking-wider">HOSPITAL NETWORK</p>
                </div>
            </div>
            <button 
                type="button" 
                @click="mobileMenuOpen = false" 
                class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none"
                aria-label="Close menu"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Mobile Drawer Navigation Links -->
        <nav class="flex-1 overflow-y-auto px-3 py-3 space-y-1 text-xs">
            <div class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">Core IPAM</div>

            <a href="{{ route('dashboard') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('dashboard') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('subnets.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('subnets.*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                <span>Subnets & CIDR Hierarchy</span>
            </a>

            <a href="{{ route('vlans.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('vlans.*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <span>VLAN Management</span>
            </a>

            <a href="{{ route('ip-addresses.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('ip-addresses.*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <span>IP Explorer & Allocations</span>
            </a>

            <a href="{{ route('scanner.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('scanner.*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" /></svg>
                <span>Network Scanner</span>
            </a>

            <div class="px-3 pt-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">Administration</div>

            @can('manage-users')
                <a href="{{ route('users.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('users.*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span>Users</span>
                </a>
            @endcan

            @can('manage-roles')
                <a href="{{ route('roles.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('roles.*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span>Roles &amp; Permissions</span>
                </a>
            @endcan

            <a href="{{ route('sites.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('sites.*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span>Campuses &amp; Sites</span>
            </a>

            <a href="{{ route('host-types.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('host-types.*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                <span>Host Types</span>
            </a>
        </nav>

        <!-- Mobile Drawer Operator Profile & Logout -->
        <div class="p-3.5 border-t border-slate-800 bg-slate-950/80 flex items-center justify-between text-xs">
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
                <button type="submit" title="Sign Out" class="p-2 text-slate-400 hover:text-rose-400 rounded-lg hover:bg-slate-800 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </button>
            </form>
        </div>
    </div>

    <!-- Desktop Persistent Sidebar (md:flex) -->
    <aside class="hidden md:flex md:w-64 bg-slate-900 text-slate-200 flex-col flex-shrink-0 border-r border-slate-800 shadow-xl z-30">
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

            <a href="{{ route('scanner.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md font-medium transition {{ request()->routeIs('scanner.*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" /></svg>
                <span>Network Scanner</span>
            </a>

            <div class="px-3 pt-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">Administration</div>

            @can('manage-users')
                <a href="{{ route('users.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md font-medium transition {{ request()->routeIs('users.*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span>Users</span>
                </a>
            @endcan

            @can('manage-roles')
                <a href="{{ route('roles.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md font-medium transition {{ request()->routeIs('roles.*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span>Roles &amp; Permissions</span>
                </a>
            @endcan

            <a href="{{ route('sites.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md font-medium transition {{ request()->routeIs('sites.*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span>Campuses &amp; Sites</span>
            </a>

            <a href="{{ route('host-types.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md font-medium transition {{ request()->routeIs('host-types.*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                <span>Host Types</span>
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
        <header class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 px-3 sm:px-6 py-2.5 sm:py-3 flex items-center justify-between shadow-xs z-10 flex-shrink-0">
            <div class="flex items-center gap-2 sm:gap-4 min-w-0">
                <!-- Hamburger button for mobile (< md) -->
                <button 
                    type="button" 
                    @click="mobileMenuOpen = true"
                    class="p-2 -ml-1 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700/60 rounded-lg md:hidden focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    aria-label="Open navigation menu"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>

                <h2 class="text-sm sm:text-base font-semibold text-slate-900 dark:text-white leading-tight truncate">
                    {{ $header ?? 'KYAMCH Smart IPAM Operations' }}
                </h2>
            </div>

            <!-- Operations Badge -->
            <div class="flex items-center gap-2 sm:gap-3 text-xs flex-shrink-0">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-700/80 text-slate-600 dark:text-slate-300 font-medium">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    Operational
                </span>
            </div>
        </header>

        <!-- Toast Notifications (Alpine.js + Livewire Event Listener) -->
        <div 
            x-data="{
                toasts: [],
                add(type, message) {
                    if (!message) return;
                    const id = Date.now() + Math.random();
                    this.toasts.push({ id, type, message });
                    setTimeout(() => this.remove(id), 5000);
                },
                remove(id) {
                    this.toasts = this.toasts.filter(t => t.id !== id);
                },
                handleNotify(detail) {
                    const data = Array.isArray(detail) ? detail[0] : detail;
                    const type = (typeof data === 'object' && data !== null) ? (data.type || 'success') : 'success';
                    const message = (typeof data === 'object' && data !== null) ? (data.message || '') : (data || '');
                    this.add(type, message);
                }
            }"
            @notify.window="handleNotify($event.detail)"
            class="fixed top-4 right-4 z-[99999] flex flex-col gap-2.5 max-w-sm w-full pointer-events-none px-3 sm:px-0"
        >
            @if (session('success'))
                <div x-init="$nextTick(() => add('success', {{ json_encode(session('success')) }}))"></div>
            @endif
            @if (session('error'))
                <div x-init="$nextTick(() => add('error', {{ json_encode(session('error')) }}))"></div>
            @endif
            @if (session('status'))
                <div x-init="$nextTick(() => add('info', {{ json_encode(session('status')) }}))"></div>
            @endif

            <template x-for="toast in toasts" :key="toast.id">
                <div 
                    x-show="true"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-4"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="pointer-events-auto flex items-start gap-3 p-3.5 rounded-xl shadow-2xl border text-xs font-medium backdrop-blur-md transition"
                    :class="{
                        'bg-emerald-50/95 dark:bg-emerald-950/95 text-emerald-900 dark:text-emerald-100 border-emerald-300 dark:border-emerald-700': toast.type === 'success',
                        'bg-rose-50/95 dark:bg-rose-950/95 text-rose-900 dark:text-rose-100 border-rose-300 dark:border-rose-700': toast.type === 'error' || toast.type === 'danger',
                        'bg-amber-50/95 dark:bg-amber-950/95 text-amber-900 dark:text-amber-100 border-amber-300 dark:border-amber-700': toast.type === 'warning',
                        'bg-slate-50/95 dark:bg-slate-800/95 text-slate-900 dark:text-slate-100 border-slate-300 dark:border-slate-700': toast.type === 'info'
                    }"
                >
                    <template x-if="toast.type === 'success'">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </template>
                    <template x-if="toast.type === 'error' || toast.type === 'danger'">
                        <svg class="w-4 h-4 text-rose-600 dark:text-rose-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </template>
                    <template x-if="toast.type === 'warning'">
                        <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </template>
                    <template x-if="toast.type === 'info'">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </template>

                    <div class="flex-1 text-xs leading-relaxed" x-text="toast.message"></div>

                    <button type="button" @click="remove(toast.id)" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 shrink-0 p-0.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </template>
        </div>

        <!-- Scrollable Page Content Slot -->
        <main class="flex-1 overflow-y-auto p-3.5 sm:p-5 md:p-6 pb-16 md:pb-6">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
