<div class="flex items-center gap-2">
    <div class="relative flex items-center">
        <label for="campus-select" class="sr-only">Select Campus / Site</label>
        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-emerald-600 dark:text-emerald-400">
            @if($selectedSiteId)
                <!-- Hospital / Building Icon -->
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            @else
                <!-- Global Globe Icon -->
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            @endif
        </div>

        <select
            id="campus-select"
            wire:model.live="selectedSiteId"
            class="pl-8 pr-7 py-1 text-xs font-medium rounded-lg border bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 transition focus:outline-none focus:ring-2 focus:ring-emerald-500 {{ $selectedSiteId ? 'border-emerald-500/70 font-semibold text-emerald-950 dark:text-emerald-200 bg-emerald-50/50 dark:bg-emerald-950/40' : 'border-slate-300 dark:border-slate-700' }}"
            title="Filter IPAM operations by Campus / Site"
        >
            <option value="">🌐 All Campuses (Global)</option>
            @foreach($sites as $site)
                <option value="{{ $site->id }}">
                    {{ $site->name }} ({{ $site->code }})
                </option>
            @endforeach
        </select>
    </div>
</div>

