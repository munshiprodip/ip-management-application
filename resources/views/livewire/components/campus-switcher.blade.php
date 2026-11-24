<div class="flex items-center gap-2">
    <x-searchable-select 
        placeholder="🌐 All Campuses" 
        searchPlaceholder="Search campus or site..." 
        containerClass="min-w-[140px] sm:min-w-[220px] max-w-[200px] sm:max-w-none"
        buttonClass="py-1 text-xs font-medium rounded-lg {{ $selectedSiteId ? 'border-emerald-500/80 font-semibold text-emerald-900 dark:text-emerald-200 bg-emerald-50/60 dark:bg-emerald-950/40' : 'border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900' }}"
    >
        <select
            id="campus-select"
            wire:model.live="selectedSiteId"
            title="Filter IPAM operations by Campus / Site"
        >
            <option value="">🌐 All Campuses (Global)</option>
            @foreach($sites as $site)
                <option value="{{ $site->id }}">
                    {{ $site->name }} ({{ $site->code }})
                </option>
            @endforeach
        </select>
    </x-searchable-select>
</div>
