<div class="space-y-6">
    <!-- Top Action & Navigation Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-5">
        <div>
            <div class="flex items-center space-x-2 text-xs font-mono uppercase tracking-wider text-slate-500 dark:text-slate-400">
                <span>Hardware Inventory</span>
                <span>/</span>
                <span class="text-indigo-600 dark:text-indigo-400 font-semibold">Devices & Interfaces</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">Device & Multi-NIC Inventory</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Source-of-truth hardware catalog, multi-interface topologies, medical modalities and asset tracking independent of IP allocations.</p>
        </div>

        <div class="flex items-center space-x-3">
            @can('create', App\Models\Device::class)
                <button wire:click="openCreateDeviceModal"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Register New Device
                </button>
            @endcan
        </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
            <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Devices</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-bold text-slate-900 dark:text-white font-mono">{{ number_format($kpi['total']) }}</span>
                <span class="text-xs px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">All Scopes</span>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
            <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Active Production</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 font-mono">{{ number_format($kpi['active']) }}</span>
                <span class="text-xs px-2 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400">Online</span>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
            <span class="text-xs font-medium text-rose-600 dark:text-rose-400 uppercase tracking-wider">Medical Modalities</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-bold text-rose-600 dark:text-rose-400 font-mono">{{ number_format($kpi['medical']) }}</span>
                <span class="text-xs px-2 py-0.5 rounded bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-400">Clinical</span>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
            <span class="text-xs font-medium text-red-600 dark:text-red-400 uppercase tracking-wider">Life Safety Critical</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-bold text-red-600 dark:text-red-400 font-mono">{{ number_format($kpi['life_safety']) }}</span>
                <span class="text-xs px-2 py-0.5 rounded bg-red-100 dark:bg-red-950/50 text-red-800 dark:text-red-300 font-semibold animate-pulse">ICU/OT</span>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
            <span class="text-xs font-medium text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Network Interfaces</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 font-mono">{{ number_format($kpi['interfaces']) }}</span>
                <span class="text-xs px-2 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-400">Ports & SVI</span>
            </div>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
            <!-- Search -->
            <div class="md:col-span-2 relative">
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Search hostname, name, asset tag, serial, MAC..."
                       class="w-full pl-9 pr-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>

            <!-- Site Filter -->
            <div>
                <select wire:model.live="siteFilter"
                        class="w-full py-2 px-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="all">All Sites</option>
                    @foreach ($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Department Filter -->
            <div>
                <select wire:model.live="departmentFilter"
                        class="w-full py-2 px-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="all">All Departments</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Category Filter -->
            <div>
                <select wire:model.live="categoryFilter"
                        class="w-full py-2 px-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="all">All Categories</option>
                    @foreach (App\Domain\DeviceInventory\Enums\DeviceCategory::cases() as $cat)
                        <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Criticality Filter -->
            <div>
                <select wire:model.live="criticalityFilter"
                        class="w-full py-2 px-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="all">All Criticality</option>
                    @foreach (App\Domain\DeviceInventory\Enums\DeviceCriticality::cases() as $crit)
                        <option value="{{ $crit->value }}">{{ $crit->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Notification Alerts -->
    @if (session()->has('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 rounded-xl text-sm text-emerald-800 dark:text-emerald-300 flex items-center justify-between">
            <span>{{ session('success') }}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">&times;</button>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="p-4 bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 rounded-xl text-sm text-rose-800 dark:text-rose-300 flex items-center justify-between">
            <span>{{ session('error') }}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700">&times;</button>
        </div>
    @endif

    <!-- Devices Inventory Table -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-400">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-5 py-3.5">Device Code & Name</th>
                        <th class="px-5 py-3.5">Category / Type</th>
                        <th class="px-5 py-3.5">Site & Location</th>
                        <th class="px-5 py-3.5">Asset Tag / Serial</th>
                        <th class="px-5 py-3.5">Interfaces</th>
                        <th class="px-5 py-3.5">Criticality</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($devices as $dev)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                            <!-- Code & Name -->
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-900 dark:text-white flex items-center space-x-2">
                                    <span>{{ $dev->name }}</span>
                                    @if ($dev->isLifeSafety())
                                        <span class="px-1.5 py-0.5 rounded text-[10px] bg-red-100 text-red-800 dark:bg-red-900/60 dark:text-red-300 font-bold">LIFE SAFETY</span>
                                    @endif
                                </div>
                                <div class="text-xs font-mono text-indigo-600 dark:text-indigo-400 mt-0.5">
                                    {{ $dev->code }}
                                    @if ($dev->hostname)
                                        <span class="text-slate-400 dark:text-slate-500">&bull; {{ $dev->hostname }}</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Category / Type -->
                            <td class="px-5 py-4">
                                <div class="text-xs font-medium text-slate-800 dark:text-slate-200">
                                    {{ $dev->deviceType?->name ?? 'Unspecified' }}
                                </div>
                                <span class="inline-block mt-1 px-2 py-0.5 text-[11px] font-medium rounded border {{ $dev->deviceType?->category->badgeClasses() ?? '' }}">
                                    {{ $dev->deviceType?->category->label() ?? 'General' }}
                                </span>
                            </td>

                            <!-- Site & Location -->
                            <td class="px-5 py-4">
                                <div class="text-xs font-medium text-slate-900 dark:text-white">
                                    {{ $dev->site?->name ?? 'No Site' }}
                                </div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    {{ $dev->department?->name ?? 'No Dept' }}
                                    @if ($dev->rack_location)
                                        <span class="font-mono text-indigo-500">&bull; {{ $dev->rack_location }}</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Asset Tag / Serial -->
                            <td class="px-5 py-4 font-mono text-xs">
                                @if ($dev->asset_tag)
                                    <div class="text-slate-900 dark:text-white font-semibold">{{ $dev->asset_tag }}</div>
                                @else
                                    <span class="text-slate-400 italic">No tag</span>
                                @endif
                                @if ($dev->serial_number)
                                    <div class="text-slate-400 text-[11px]">{{ $dev->serial_number }}</div>
                                @endif
                            </td>

                            <!-- Interfaces -->
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-mono font-medium bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-300">
                                    {{ $dev->interfaces->count() }} NIC{{ $dev->interfaces->count() === 1 ? '' : 's' }}
                                </span>
                            </td>

                            <!-- Criticality -->
                            <td class="px-5 py-4">
                                <span class="px-2 py-0.5 text-[11px] font-medium rounded border {{ $dev->criticality->badgeClasses() }}">
                                    {{ $dev->criticality->label() }}
                                </span>
                            </td>

                            <!-- Status -->
                            <td class="px-5 py-4">
                                <span class="px-2 py-0.5 text-[11px] font-medium rounded border {{ $dev->status->badgeClasses() }}">
                                    {{ $dev->status->label() }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="px-5 py-4 text-right space-x-2">
                                <button wire:click="viewDevice({{ $dev->id }})"
                                        class="px-2.5 py-1 text-xs font-medium bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded transition">
                                    Interfaces & Details
                                </button>
                                @can('update', $dev)
                                    <button wire:click="editDevice({{ $dev->id }})"
                                            class="px-2.5 py-1 text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 transition">
                                        Edit
                                    </button>
                                @endcan
                                @can('delete', $dev)
                                    <button wire:click="confirmDeleteDevice({{ $dev->id }})"
                                            class="px-2.5 py-1 text-xs font-medium text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-300 transition">
                                        Delete
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-400 dark:text-slate-500">
                                <svg class="w-10 h-10 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                                </svg>
                                No devices found matching the current search query or filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $devices->links() }}
        </div>
    </div>

    <!-- DETAIL SLIDE-OVER DRAWER -->
    @if ($showDetailSlideover && $viewDevice)
        <div class="fixed inset-0 z-50 overflow-hidden bg-slate-900/50 backdrop-blur-xs flex justify-end">
            <div class="w-full max-w-3xl bg-white dark:bg-slate-900 h-full shadow-2xl flex flex-col border-l border-slate-200 dark:border-slate-800">
                <!-- Slide-over Header -->
                <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-start justify-between bg-slate-50/50 dark:bg-slate-800/30">
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-0.5 text-xs font-mono font-medium rounded border {{ $viewDevice->status->badgeClasses() }}">
                                {{ $viewDevice->status->label() }}
                            </span>
                            <span class="px-2 py-0.5 text-xs font-medium rounded border {{ $viewDevice->criticality->badgeClasses() }}">
                                {{ $viewDevice->criticality->label() }}
                            </span>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white mt-2">{{ $viewDevice->name }}</h2>
                        <div class="text-xs font-mono text-slate-500 mt-0.5">
                            Code: <span class="text-indigo-600 dark:text-indigo-400 font-bold">{{ $viewDevice->code }}</span>
                            @if ($viewDevice->hostname)
                                &bull; FQDN: {{ $viewDevice->hostname }}
                            @endif
                        </div>
                    </div>
                    <button wire:click="closeDetailSlideover" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold p-1">
                        &times;
                    </button>
                </div>

                <!-- Tabs -->
                <div class="flex border-b border-slate-200 dark:border-slate-800 px-6 space-x-6 text-sm font-medium">
                    <button wire:click="$set('detailTab', 'interfaces')"
                            class="py-3 border-b-2 transition {{ $detailTab === 'interfaces' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}">
                        Network Interfaces ({{ $viewDevice->interfaces->count() }})
                    </button>
                    <button wire:click="$set('detailTab', 'specs')"
                            class="py-3 border-b-2 transition {{ $detailTab === 'specs' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}">
                        Hardware Specs & Ownership
                    </button>
                    <button wire:click="$set('detailTab', 'audit')"
                            class="py-3 border-b-2 transition {{ $detailTab === 'audit' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}">
                        Audit History
                    </button>
                </div>

                <!-- Tab Content Body -->
                <div class="flex-1 overflow-y-auto p-6 space-y-6">
                    @if ($detailTab === 'interfaces')
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Device Network Interfaces</h3>
                                <p class="text-xs text-slate-500">Physical ports, virtual NICs, management consoles and 802.1Q sub-interfaces.</p>
                            </div>
                            @can('create', App\Models\DeviceInterface::class)
                                <button wire:click="openAddInterfaceModal"
                                        class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-xs transition">
                                    + Add Interface
                                </button>
                            @endcan
                        </div>

                        <div class="space-y-3">
                            @forelse ($viewDevice->interfaces as $iface)
                                <div class="bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-lg p-4 flex items-center justify-between">
                                    <div class="space-y-1">
                                        <div class="flex items-center space-x-2">
                                            <span class="font-mono font-bold text-slate-900 dark:text-white text-sm">{{ $iface->name }}</span>
                                            <span class="px-2 py-0.5 text-[10px] rounded border {{ $iface->type->badgeClasses() }}">{{ $iface->type->label() }}</span>
                                            <span class="px-2 py-0.5 text-[10px] rounded border {{ $iface->status->badgeClasses() }}">{{ $iface->status->label() }}</span>
                                        </div>
                                        <div class="text-xs font-mono text-slate-500 flex items-center space-x-3">
                                            @if ($iface->mac_address)
                                                <span>MAC: <strong class="text-slate-800 dark:text-slate-200">{{ $iface->mac_address }}</strong></span>
                                            @endif
                                            @if ($iface->speed_mbps)
                                                <span>Speed: {{ $iface->formatted_speed }}</span>
                                            @endif
                                            @if ($iface->vlan)
                                                <span>VLAN: <strong class="text-indigo-600 dark:text-indigo-400">VLAN {{ $iface->vlan->vlan_id }} ({{ $iface->vlan->name }})</strong></span>
                                            @endif
                                            @if ($iface->switch_port_label)
                                                <span>Switch Port: <strong class="text-emerald-600 dark:text-emerald-400">{{ $iface->switch_port_label }}</strong></span>
                                            @endif
                                        </div>
                                        @if ($iface->description)
                                            <div class="text-xs text-slate-500 italic mt-0.5">{{ $iface->description }}</div>
                                        @endif
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        @can('update', $iface)
                                            <button wire:click="editInterface({{ $iface->id }})"
                                                    class="p-1.5 text-slate-400 hover:text-indigo-600 transition"
                                                    title="Edit Interface">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </button>
                                        @endcan
                                        @can('delete', $iface)
                                            <button wire:click="confirmDeleteInterface({{ $iface->id }})"
                                                    class="p-1.5 text-slate-400 hover:text-rose-600 transition"
                                                    title="Delete Interface">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                            @empty
                                <div class="p-8 text-center border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-lg text-slate-400">
                                    No network interfaces added to this device yet. Add a physical or virtual NIC to bind IP addresses.
                                </div>
                            @endforelse
                        </div>

                    @elseif ($detailTab === 'specs')
                        <div class="grid grid-cols-2 gap-4 text-xs">
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                                <span class="text-slate-400 uppercase font-semibold">Manufacturer</span>
                                <div class="text-slate-900 dark:text-white font-medium text-sm mt-0.5">{{ $viewDevice->manufacturer?->name ?? 'Unspecified' }}</div>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                                <span class="text-slate-400 uppercase font-semibold">Hardware Model</span>
                                <div class="text-slate-900 dark:text-white font-medium text-sm mt-0.5">{{ $viewDevice->deviceModel?->name ?? 'Unspecified' }}</div>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                                <span class="text-slate-400 uppercase font-semibold">Asset Tag</span>
                                <div class="text-slate-900 dark:text-white font-mono font-bold text-sm mt-0.5">{{ $viewDevice->asset_tag ?? 'None' }}</div>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                                <span class="text-slate-400 uppercase font-semibold">Serial Number</span>
                                <div class="text-slate-900 dark:text-white font-mono text-sm mt-0.5">{{ $viewDevice->serial_number ?? 'None' }}</div>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                                <span class="text-slate-400 uppercase font-semibold">Campus & Facility</span>
                                <div class="text-slate-900 dark:text-white font-medium text-sm mt-0.5">{{ $viewDevice->site?->name }}</div>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                                <span class="text-slate-400 uppercase font-semibold">Department</span>
                                <div class="text-slate-900 dark:text-white font-medium text-sm mt-0.5">{{ $viewDevice->department?->name ?? 'None' }}</div>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                                <span class="text-slate-400 uppercase font-semibold">Rack / Physical Location</span>
                                <div class="text-slate-900 dark:text-white font-mono text-sm mt-0.5">{{ $viewDevice->rack_location ?? 'Unspecified' }}</div>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                                <span class="text-slate-400 uppercase font-semibold">OS / Firmware</span>
                                <div class="text-slate-900 dark:text-white font-mono text-sm mt-0.5">{{ $viewDevice->os_firmware ?? 'None' }}</div>
                            </div>
                        </div>

                        @if ($viewDevice->notes)
                            <div class="p-4 bg-slate-50 dark:bg-slate-800/30 rounded-lg">
                                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Administrative Notes</h4>
                                <p class="text-xs text-slate-700 dark:text-slate-300 whitespace-pre-wrap">{{ $viewDevice->notes }}</p>
                            </div>
                        @endif

                    @elseif ($detailTab === 'audit')
                        <div class="space-y-3">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Device Change Audit Trail</h3>
                            @forelse ($deviceAudits as $audit)
                                <div class="p-3 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-lg text-xs space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="font-mono font-semibold text-indigo-600 dark:text-indigo-400">{{ $audit->event_type }}</span>
                                        <span class="text-slate-400">{{ $audit->created_at?->diffForHumans() }}</span>
                                    </div>
                                    <p class="text-slate-700 dark:text-slate-300">{{ $audit->reason }}</p>
                                    <div class="text-[10px] text-slate-400 font-mono">Actor: {{ $audit->actor_name }}</div>
                                </div>
                            @empty
                                <div class="text-slate-400 text-xs italic">No audit records logged for this device yet.</div>
                            @endforelse
                        </div>
                    @endif
                </div>

                <!-- Slide-over Footer -->
                <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 flex justify-end">
                    <button wire:click="closeDetailSlideover" class="px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-sm font-medium rounded-lg transition">
                        Close Drawer
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- REGISTER / EDIT DEVICE MODAL -->
    @if ($showDeviceModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-3xl w-full shadow-2xl p-6 space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                        {{ $isEditingDevice ? 'Edit Device Details' : 'Register New Hardware Device' }}
                    </h3>
                    <button wire:click="$set('showDeviceModal', false)" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold">&times;</button>
                </div>

                <form wire:submit="saveDevice" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Code -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Device Code / Asset Slug *
                            </label>
                            <input type="text" wire:model="device_code" placeholder="e.g. CORE-SW-01 or RAD-CT-01"
                                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm font-mono text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @error('device_code') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Name -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Display Name *
                            </label>
                            <input type="text" wire:model="device_name" placeholder="e.g. GE Revolution CT Scanner 01"
                                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @error('device_name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Hostname -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Hostname / FQDN
                            </label>
                            <input type="text" wire:model="device_hostname" placeholder="e.g. ct01.rad.kyamch.local"
                                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm font-mono text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <!-- Device Type -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Device Type *
                            </label>
                            <select wire:model="device_type_id"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                @foreach ($deviceTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }} ({{ $type->category->label() }})</option>
                                @endforeach
                            </select>
                            @error('device_type_id') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Manufacturer -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Manufacturer
                            </label>
                            <select wire:model.live="device_manufacturer_id"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Select Manufacturer...</option>
                                @foreach ($manufacturers as $mfg)
                                    <option value="{{ $mfg->id }}">{{ $mfg->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Device Model -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Hardware Model
                            </label>
                            <select wire:model="device_model_id"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Select Model...</option>
                                @foreach ($deviceModels as $model)
                                    <option value="{{ $model->id }}">{{ $model->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Asset Tag -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Hospital Asset Tag
                            </label>
                            <input type="text" wire:model="device_asset_tag" placeholder="e.g. KY-AST-8820"
                                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm font-mono text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @error('device_asset_tag') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Serial Number -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Serial Number
                            </label>
                            <input type="text" wire:model="device_serial_number" placeholder="e.g. SN-99882200"
                                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm font-mono text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <!-- Site -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Campus Site *
                            </label>
                            <select wire:model.live="device_site_id"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                @foreach ($sites as $site)
                                    <option value="{{ $site->id }}">{{ $site->name }}</option>
                                @endforeach
                            </select>
                            @error('device_site_id') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Department -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Department Custodian
                            </label>
                            <select wire:model="device_department_id"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Select Department...</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Criticality -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Operational Criticality *
                            </label>
                            <select wire:model="device_criticality"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                @foreach (App\Domain\DeviceInventory\Enums\DeviceCriticality::cases() as $crit)
                                    <option value="{{ $crit->value }}">{{ $crit->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Lifecycle Status *
                            </label>
                            <select wire:model="device_status"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                @foreach (App\Domain\DeviceInventory\Enums\DeviceStatus::cases() as $st)
                                    <option value="{{ $st->value }}">{{ $st->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Rack / Location -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Rack / Room Location
                            </label>
                            <input type="text" wire:model="device_rack_location" placeholder="e.g. Server Room 1, Rack B, U12-U14"
                                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <!-- OS / Firmware -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                OS / Firmware Version
                            </label>
                            <input type="text" wire:model="device_os_firmware" placeholder="e.g. Cisco IOS-XE 17.9 / HealthOS v4.2"
                                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm font-mono text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    @if (!$isEditingDevice)
                        <!-- Initial Interface Quick Provisioning -->
                        <div class="border-t border-slate-100 dark:border-slate-800 pt-4 mt-2">
                            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Default Management Interface (Optional)</h4>
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[11px] text-slate-500 mb-1">Interface Name</label>
                                    <input type="text" wire:model="initial_if_name" placeholder="eth0 or GigabitEthernet0/1"
                                           class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded text-xs font-mono">
                                </div>
                                <div>
                                    <label class="block text-[11px] text-slate-500 mb-1">MAC Address</label>
                                    <input type="text" wire:model="initial_if_mac" placeholder="00:14:22:01:23:45"
                                           class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded text-xs font-mono">
                                </div>
                                <div>
                                    <label class="block text-[11px] text-slate-500 mb-1">Type</label>
                                    <select wire:model="initial_if_type"
                                            class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded text-xs">
                                        <option value="physical">Physical Port</option>
                                        <option value="management">Management / OOBM</option>
                                        <option value="virtual">Virtual NIC</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="border-t border-slate-100 dark:border-slate-800 pt-4 flex justify-end space-x-3">
                        <button type="button" wire:click="$set('showDeviceModal', false)"
                                class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-sm font-medium rounded-lg transition">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                            {{ $isEditingDevice ? 'Save Changes' : 'Register Device' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- ADD / EDIT INTERFACE MODAL -->
    @if ($showInterfaceModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full shadow-2xl p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        {{ $isEditingInterface ? 'Edit Network Interface' : 'Add Interface to Device' }}
                    </h3>
                    <button wire:click="$set('showInterfaceModal', false)" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
                </div>

                <form wire:submit="saveInterface" class="space-y-3">
                    <!-- Interface Name -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Interface Name *
                        </label>
                        <input type="text" wire:model="if_name" placeholder="e.g. eth0, GigabitEthernet0/1, Loopback0"
                               class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm font-mono text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @error('if_name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Type & Status -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Type *</label>
                            <select wire:model="if_type" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm">
                                @foreach (App\Domain\DeviceInventory\Enums\InterfaceType::cases() as $t)
                                    <option value="{{ $t->value }}">{{ $t->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Status *</label>
                            <select wire:model="if_status" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm">
                                @foreach (App\Domain\DeviceInventory\Enums\InterfaceStatus::cases() as $s)
                                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- MAC Address -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            MAC Address (Auto-normalized)
                        </label>
                        <input type="text" wire:model="if_mac" placeholder="e.g. 00:14:22:01:23:45 or 0014.2201.2345"
                               class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm font-mono text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @error('if_mac') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Speed & VLAN -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Speed (Mbps)</label>
                            <input type="number" wire:model="if_speed_mbps" placeholder="1000"
                                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">VLAN Tag</label>
                            <select wire:model="if_vlan_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm">
                                <option value="">None (Untagged)</option>
                                @foreach ($vlans as $vlan)
                                    <option value="{{ $vlan->id }}">VLAN {{ $vlan->vlan_id }} ({{ $vlan->name }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Switch Port Connection -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Switch Port Connection / Patch
                        </label>
                        <input type="text" wire:model="if_switch_port" placeholder="e.g. CORE-SW1:Gi1/0/24 or PATCH-PANEL-B12"
                               class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm font-mono">
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Description / Role
                        </label>
                        <input type="text" wire:model="if_description" placeholder="e.g. PACS Imaging Storage Uplink"
                               class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm">
                    </div>

                    <div class="border-t border-slate-100 dark:border-slate-800 pt-4 flex justify-end space-x-3">
                        <button type="button" wire:click="$set('showInterfaceModal', false)"
                                class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-sm rounded-lg">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm">
                            Save Interface
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- DELETE DEVICE CONFIRMATION MODAL -->
    @if ($showDeleteDeviceModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-rose-200 dark:border-rose-900 rounded-2xl max-w-md w-full shadow-2xl p-6 space-y-4">
                <div class="flex items-center space-x-3 text-rose-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Decommission Device?</h3>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    Are you sure you want to permanently delete this device? All associated interfaces will be deleted. Any active IP address allocations must be released before deletion can proceed.
                </p>
                <div class="flex justify-end space-x-3 pt-2">
                    <button wire:click="$set('showDeleteDeviceModal', false)" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-sm rounded-lg">Cancel</button>
                    <button wire:click="deleteDevice" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold rounded-lg">Yes, Delete Device</button>
                </div>
            </div>
        </div>
    @endif

    <!-- DELETE INTERFACE CONFIRMATION MODAL -->
    @if ($showDeleteInterfaceModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-rose-200 dark:border-rose-900 rounded-2xl max-w-md w-full shadow-2xl p-6 space-y-4">
                <div class="flex items-center space-x-3 text-rose-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Delete Network Interface?</h3>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    Are you sure you want to delete this network interface? Any active IP assignments or sub-interfaces will block this action.
                </p>
                <div class="flex justify-end space-x-3 pt-2">
                    <button wire:click="$set('showDeleteInterfaceModal', false)" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-sm rounded-lg">Cancel</button>
                    <button wire:click="deleteInterface" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold rounded-lg">Yes, Delete Interface</button>
                </div>
            </div>
        </div>
    @endif
</div>
