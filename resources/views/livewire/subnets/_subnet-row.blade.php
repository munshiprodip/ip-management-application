<tr class="{{ $depth > 0 ? 'bg-slate-50/70 dark:bg-slate-900/40 border-l-2 border-l-emerald-500/60' : '' }} hover:bg-emerald-50/30 dark:hover:bg-slate-700/30 transition">
    <td class="py-3 px-4 font-mono text-slate-900 dark:text-white whitespace-nowrap" style="padding-left: {{ ($depth * 28) + 16 }}px;">
        <div class="flex items-center gap-1.5">
            @if($depth > 0)
                <span class="text-slate-400 dark:text-slate-500 font-mono text-xs select-none mr-1 inline-flex items-center">
                    <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </span>
            @endif

            @if($subnet->children_count > 0)
                <button
                    wire:click="toggleExpand({{ $subnet->id }})"
                    type="button"
                    class="p-1 rounded hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition inline-flex items-center justify-center focus:outline-none"
                    title="{{ in_array($subnet->id, $expandedSubnetIds, true) ? 'Collapse child subnets' : 'Expand child subnets' }}"
                >
                    @if(in_array($subnet->id, $expandedSubnetIds, true))
                        <!-- Expanded Down Chevron -->
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                        </svg>
                    @else
                        <!-- Collapsed Right Chevron -->
                        <svg class="w-4 h-4 text-slate-500 hover:text-slate-800 dark:hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                    @endif
                </button>
            @else
                <span class="w-6 inline-block"></span>
            @endif

            <span class="px-2 py-0.5 rounded {{ $depth > 0 ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 font-semibold border border-emerald-200 dark:border-emerald-800' : 'bg-slate-100 dark:bg-slate-700 text-emerald-600 dark:text-emerald-400 font-bold' }}">
                {{ $subnet->cidr }}
            </span>
            <span class="text-[10px] text-slate-400 uppercase font-mono">IPv{{ $subnet->ip_version }}</span>
        </div>
    </td>
    <td class="py-3 px-4">
        <div class="font-medium text-slate-800 dark:text-slate-200 flex items-center gap-1.5 flex-wrap">
            <span>{{ $subnet->name }}</span>
            @if($subnet->site)
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-mono font-medium bg-emerald-50 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800" title="Campus: {{ $subnet->site->name }}">
                    {{ $subnet->site->code }}
                </span>
            @endif
            @if($depth > 0)
                <span class="px-1.5 py-0.5 rounded text-[9px] uppercase tracking-wider bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400 font-mono">
                    Level {{ $depth }} Child
                </span>
            @endif
        </div>
        @if($subnet->description)
            <div class="text-[11px] text-slate-500 truncate max-w-xs">{{ $subnet->description }}</div>
        @endif
    </td>
    <td class="py-3 px-4">
        <span class="px-2 py-0.5 rounded text-[10px] font-medium border {{ $subnet->subnet_type->badgeClasses() }}">
            {{ $subnet->subnet_type->label() }}
        </span>
    </td>
    <td class="py-3 px-4">
        @if($subnet->vlan)
            <span class="px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-950 text-blue-700 dark:text-blue-300 font-mono text-[10px] border border-blue-200 dark:border-blue-800">
                VLAN {{ $subnet->vlan->vlan_id }}
            </span>
        @else
            <span class="text-slate-400 font-mono text-[11px]">—</span>
        @endif
    </td>
    <td class="py-3 px-4 font-mono text-slate-700 dark:text-slate-300">
        <div>{{ $subnet->usableAddressesFormatted() }} usable</div>
        <div class="text-[10px] text-slate-400">{{ $subnet->totalAddressesFormatted() }} total</div>
    </td>
    <td class="py-3 px-4 text-right whitespace-nowrap">
        <div class="inline-flex items-center gap-1.5">
            @if($subnet->children_count > 0)
                <button
                    wire:click="toggleExpand({{ $subnet->id }})"
                    type="button"
                    class="px-2 py-1 rounded text-[11px] font-mono transition flex items-center gap-1 {{ in_array($subnet->id, $expandedSubnetIds, true) ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700 font-semibold shadow-xs' : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200' }}"
                    title="{{ in_array($subnet->id, $expandedSubnetIds, true) ? 'Collapse child subnets' : 'Expand child subnets' }}"
                >
                    @if(in_array($subnet->id, $expandedSubnetIds, true))
                        <span>▲</span>
                        <span>Collapse ({{ $subnet->children_count }})</span>
                    @else
                        <span>▼</span>
                        <span>{{ $subnet->children_count }} Child{{ $subnet->children_count === 1 ? '' : 'ren' }}</span>
                    @endif
                </button>
            @endif

            @if($subnet->isAggregate())
                <button
                    wire:click="openCreateModal({{ $subnet->id }})"
                    type="button"
                    class="p-1 text-slate-400 hover:text-emerald-600 rounded hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                    title="Add Child Subnet to {{ $subnet->cidr }}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                </button>
            @endif

            <button
                wire:click="openCalculator('{{ $subnet->cidr }}')"
                type="button"
                class="p-1 text-slate-500 hover:text-emerald-600 rounded hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                title="Subnet Calculator"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            </button>

            <button
                wire:click="openSplitModal({{ $subnet->id }})"
                type="button"
                class="p-1 text-slate-500 hover:text-blue-600 rounded hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                title="Split Subnet Preview"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            </button>

            <button
                wire:click="openEditModal({{ $subnet->id }})"
                type="button"
                class="p-1 text-slate-500 hover:text-slate-900 dark:hover:text-white rounded hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                title="Edit Subnet"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </button>

            <button
                wire:click="confirmDelete({{ $subnet->id }})"
                type="button"
                class="p-1 text-slate-400 hover:text-rose-600 rounded hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                title="Delete Subnet"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        </div>
    </td>
</tr>

{{-- Render nested children if expanded --}}
@if($subnet->children_count > 0 && in_array($subnet->id, $expandedSubnetIds, true) && isset($childrenByParent) && $childrenByParent->has($subnet->id))
    @foreach($childrenByParent->get($subnet->id) as $child)
        @include('livewire.subnets._subnet-row', [
            'subnet' => $child,
            'depth' => $depth + 1,
            'expandedSubnetIds' => $expandedSubnetIds,
            'childrenByParent' => $childrenByParent,
            'selectedParentId' => $selectedParentId,
        ])
    @endforeach
@endif

