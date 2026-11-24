<div class="bg-slate-900 border border-slate-800 rounded-xl shadow-2xl p-8 backdrop-blur-sm">
    <!-- Header -->
    <div class="text-center mb-6">
        <div class="inline-flex w-12 h-12 rounded-xl bg-emerald-600 items-center justify-center font-bold text-white shadow-lg shadow-emerald-950 mb-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
            </svg>
        </div>
        <h2 class="text-xl font-bold text-white tracking-tight">KYAMCH Smart IPAM</h2>
        <p class="text-xs text-slate-400 mt-1 font-mono">Khwaja Yunus Ali Medical College & Hospital</p>
        <p class="text-[11px] text-emerald-400 font-medium mt-0.5">Enterprise Network Infrastructure & Address Management</p>
    </div>

    <!-- Security Warning Notice -->
    @if (session('success'))
        <div class="mb-5 p-3 rounded-lg bg-emerald-950/70 border border-emerald-700 text-xs text-emerald-200 flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="mb-5 p-3 rounded-lg bg-rose-950/70 border border-rose-700 text-xs text-rose-200 flex items-center gap-2">
            <svg class="w-4 h-4 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif
    <div class="mb-5 p-3 rounded-lg bg-slate-950/70 border border-slate-800 text-[11px] text-slate-400 flex items-start gap-2.5">
        <svg class="w-4 h-4 text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <span><strong>Restricted Hospital System:</strong> Authorized IT operations personnel only. All access, queries and IP modifications are strictly audited.</span>
    </div>

    <!-- Login Form -->
    <form wire:submit.prevent="login" class="space-y-4">
        <div>
            <label for="email" class="block text-xs font-medium text-slate-300 mb-1">Operator Email</label>
            <input
                wire:model="email"
                id="email"
                type="email"
                required
                autocomplete="email"
                placeholder="operator@kyamch.local"
                class="w-full px-3 py-2 text-xs rounded-lg bg-slate-950 border border-slate-700 text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition"
            />
            @error('email')
                <p class="mt-1 text-[11px] text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-xs font-medium text-slate-300 mb-1">Password</label>
            <input
                wire:model="password"
                id="password"
                type="password"
                required
                autocomplete="current-password"
                placeholder="••••••••••••"
                class="w-full px-3 py-2 text-xs rounded-lg bg-slate-950 border border-slate-700 text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition"
            />
            @error('password')
                <p class="mt-1 text-[11px] text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between text-xs">
            <label class="flex items-center text-slate-400">
                <input wire:model="remember" type="checkbox" class="w-3.5 h-3.5 rounded bg-slate-950 border-slate-700 text-emerald-600 focus:ring-emerald-500" />
                <span class="ml-2 text-[11px]">Remember operator session</span>
            </label>
            <span class="text-[11px] text-slate-500 font-mono">TLS / Local Mode</span>
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            class="w-full py-2.5 px-4 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-medium text-xs shadow-md shadow-emerald-950 transition flex items-center justify-center gap-2 disabled:opacity-50"
        >
            <span wire:loading.remove>Sign In to Infrastructure Console</span>
            <span wire:loading class="flex items-center gap-2">
                <svg class="animate-spin w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                Authenticating...
            </span>
        </button>
    </form>

    <!-- Quick Access Role Helper Buttons -->
    <div class="mt-6 pt-5 border-t border-slate-800">
        <p class="text-[11px] text-slate-400 font-mono mb-2 text-center">Quick Select Role:</p>
        <div class="grid grid-cols-3 gap-1.5 text-[10px]">
            <button wire:click="fillAccount('admin')" type="button" class="px-2 py-1 bg-slate-800 hover:bg-slate-700 rounded text-slate-300 transition text-center truncate">
                Super Admin
            </button>
            <button wire:click="fillAccount('netadmin')" type="button" class="px-2 py-1 bg-slate-800 hover:bg-slate-700 rounded text-slate-300 transition text-center truncate">
                Net Admin
            </button>
            <button wire:click="fillAccount('operator')" type="button" class="px-2 py-1 bg-slate-800 hover:bg-slate-700 rounded text-slate-300 transition text-center truncate">
                IP Operator
            </button>
            <button wire:click="fillAccount('auditor')" type="button" class="px-2 py-1 bg-slate-800 hover:bg-slate-700 rounded text-slate-300 transition text-center truncate">
                Auditor
            </button>
            <button wire:click="fillAccount('viewer')" type="button" class="px-2 py-1 bg-slate-800 hover:bg-slate-700 rounded text-slate-300 transition text-center truncate">
                Viewer
            </button>
            <div class="px-2 py-1 bg-slate-950 rounded text-slate-400 text-center truncate font-mono">
                PWD: Password123!
            </div>
        </div>
    </div>
</div>

