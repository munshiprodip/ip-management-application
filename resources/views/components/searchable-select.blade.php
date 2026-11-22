@props([
    'placeholder' => 'Select an option...',
    'searchPlaceholder' => 'Search options...',
    'buttonClass' => '',
    'containerClass' => 'w-full',
])

<div x-data="searchableSelect({ placeholder: @js($placeholder) })" 
     @keydown="onKeyDown($event)"
     class="relative inline-block text-xs {{ $containerClass }}">
    
    <!-- Hidden native select keeping all Livewire wire:model bindings and form submissions intact -->
    <div class="sr-only select-wrapper" aria-hidden="true" tabindex="-1">
        {{ $slot }}
    </div>

    <!-- Trigger Button -->
    <button type="button" 
            @click="toggle()" 
            class="w-full flex items-center justify-between gap-2 px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg shadow-2xs text-left cursor-pointer hover:border-slate-400 dark:hover:border-slate-600 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition {{ $buttonClass }}">
        <span class="truncate font-medium text-slate-800 dark:text-slate-100" x-text="selectedLabel || @js($placeholder)">
            {{ $placeholder }}
        </span>
        <svg class="w-4 h-4 text-slate-400 flex-shrink-0 transition-transform duration-150" 
             :class="{ 'rotate-180': open }" 
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <!-- Dropdown Menu with Search Field and Instant Autofocus -->
    <div x-show="open" 
         x-cloak
         @click.outside="close()" 
         class="absolute z-50 mt-1 w-full min-w-[220px] bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-xl overflow-hidden text-xs">
        
        <!-- Search Input with Auto-focus -->
        <div class="p-2 border-b border-slate-100 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-900/60">
            <div class="relative flex items-center">
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input x-ref="searchInput" 
                       x-model="search" 
                       type="text" 
                       autofocus
                       placeholder="{{ $searchPlaceholder }}" 
                       class="w-full pl-8 pr-7 py-1.5 text-xs bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-md focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 text-slate-800 dark:text-slate-100 placeholder-slate-400"
                       @keydown.stop />
                <button type="button" 
                        x-show="search.length > 0" 
                        @click="search = ''; $refs.searchInput.focus()" 
                        class="absolute right-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Scrollable Options List -->
        <div class="max-h-60 overflow-y-auto py-1 divide-y divide-slate-50 dark:divide-slate-800/40">
            <template x-for="(opt, idx) in filteredOptions" :key="opt.value + '_' + idx">
                <button type="button" 
                        @click="selectOption(opt.value)"
                        :disabled="opt.disabled"
                        :class="{
                            'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-200 font-semibold': opt.value == selectedValue,
                            'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white': highlightIndex === idx && opt.value != selectedValue,
                            'text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/50': opt.value != selectedValue && highlightIndex !== idx,
                            'opacity-50 cursor-not-allowed': opt.disabled
                        }"
                        class="w-full px-3 py-2 text-left flex items-center justify-between gap-2 transition-colors cursor-pointer">
                    <span class="truncate" x-text="opt.label"></span>
                    <svg x-show="opt.value == selectedValue" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </button>
            </template>
            <div x-show="filteredOptions.length === 0" class="px-3 py-4 text-center text-slate-400 text-xs">
                No matching options found.
            </div>
        </div>
    </div>
</div>
