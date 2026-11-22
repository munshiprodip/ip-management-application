<div class="space-y-6">
    <!-- Header / Context Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 font-mono mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600">KYAMCH IPAM</a>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-semibold">Reports</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">Reports, Exports & Audit Log Explorer</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Authorized export generation, bounded 24-hour temporary files, and immutable system audit trail.
            </p>
        </div>
        <div>
            <button
                wire:click="openExportModal"
                class="px-4 py-2 text-xs font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 shadow-xs flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>Generate Scoped Export</span>
            </button>
        </div>
    </div>

    <!-- Feedback Banners -->
    @if ($successMessage)
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-800 dark:text-emerald-300 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span>{{ $successMessage }}</span>
            </div>
            <button wire:click="$set('successMessage', null)" class="text-emerald-600 hover:text-emerald-800 font-bold">&times;</button>
        </div>
    @endif

    @if ($errorMessage)
        <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs text-rose-800 dark:text-rose-300 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span>{{ $errorMessage }}</span>
            </div>
            <button wire:click="$set('errorMessage', null)" class="text-rose-600 hover:text-rose-800 font-bold">&times;</button>
        </div>
    @endif

    <!-- Recent Export Jobs (Gate T-027 / Bounded 24h Expiry) -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Recent Export Files</h2>
                <p class="text-[11px] text-slate-400">Temporary files are automatically purged after 24 hours (Retention Policy T-027).</p>
            </div>
            <span class="text-xs font-mono text-slate-500">Max limit: 5,000 records/file</span>
        </div>

        @if ($exportJobs->isEmpty())
            <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-900 text-center text-xs text-slate-400">
                No exports generated yet. Click "Generate Scoped Export" above to create one.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 text-slate-400 text-[11px] font-mono uppercase">
                            <th class="pb-2">File Name</th>
                            <th class="pb-2">Type</th>
                            <th class="pb-2">Requester</th>
                            <th class="pb-2">Rows</th>
                            <th class="pb-2">Size</th>
                            <th class="pb-2">Status / Expiry</th>
                            <th class="pb-2 text-right">Download</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 font-mono text-[11px]">
                        @foreach ($exportJobs as $job)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-700/30">
                                <td class="py-2.5 font-sans font-medium text-slate-800 dark:text-slate-200">
                                    {{ $job->file_name }}
                                </td>
                                <td class="py-2.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                                        {{ $job->type->label() }}
                                    </span>
                                </td>
                                <td class="py-2.5 font-sans text-slate-600 dark:text-slate-400">
                                    {{ $job->requester->name ?? 'System' }}
                                </td>
                                <td class="py-2.5 text-slate-600 dark:text-slate-400">
                                    {{ number_format($job->row_count) }} rows
                                </td>
                                <td class="py-2.5 text-slate-500">
                                    {{ number_format($job->file_size_bytes) }} B
                                </td>
                                <td class="py-2.5">
                                    @if ($job->isExpired())
                                        <span class="px-2 py-0.5 rounded text-[10px] bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-400">
                                            Expired (Purged)
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] border {{ $job->status->badgeClasses() }}">
                                            Expires {{ $job->expires_at->diffForHumans() }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2.5 text-right">
                                    @if (! $job->isExpired())
                                        <a
                                            href="{{ route('reports.exports.download', $job) }}"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] rounded bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 font-sans font-medium"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                            Download CSV
                                        </a>
                                    @else
                                        <span class="text-slate-400 text-[10px]">Unavailable</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Live Audit Event Table with Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Immutable Event Log Explorer</h2>
                <span class="text-xs text-slate-500 font-mono">Total Recorded Events: {{ $auditEvents->total() }}</span>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search event type, reason, IP..."
                    class="px-3 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-hidden focus:ring-2 focus:ring-emerald-500"
                />

                <select
                    wire:model.live="eventTypeFilter"
                    class="px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200"
                >
                    <option value="all">All Event Types</option>
                    @foreach ($distinctEventTypes as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700 text-slate-400 text-[11px] font-mono uppercase">
                        <th class="pb-2.5">ID</th>
                        <th class="pb-2.5">Timestamp</th>
                        <th class="pb-2.5">Event Type</th>
                        <th class="pb-2.5">Actor</th>
                        <th class="pb-2.5">Subject</th>
                        <th class="pb-2.5">IP Address</th>
                        <th class="pb-2.5">Reason / Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 font-mono text-[11px]">
                    @forelse($auditEvents as $event)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-700/30">
                            <td class="py-2.5 text-slate-400">#{{ $event->id }}</td>
                            <td class="py-2.5 text-slate-500 whitespace-nowrap">
                                {{ $event->created_at->setTimezone(config('ipam.default_timezone'))->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="py-2.5">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                                    {{ $event->event_type }}
                                </span>
                            </td>
                            <td class="py-2.5 text-slate-700 dark:text-slate-300 font-sans">
                                {{ $event->actor_name ?? 'System' }}
                            </td>
                            <td class="py-2.5 text-slate-600 dark:text-slate-400">
                                {{ $event->subject_label ?? '-' }}
                            </td>
                            <td class="py-2.5 text-slate-500">
                                {{ $event->ip_address ?? '-' }}
                            </td>
                            <td class="py-2.5 text-slate-600 dark:text-slate-400 font-sans">
                                {{ $event->reason ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 font-sans">No audit events match the selected criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $auditEvents->links() }}
        </div>
    </div>

    <!-- Export Generation Modal -->
    @if ($showExportModal)
        <div class="fixed inset-0 z-50 overflow-hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="w-full max-w-lg bg-white dark:bg-slate-800 rounded-xl shadow-2xl p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Generate Scoped Data Export</h3>
                    <button wire:click="closeExportModal" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
                </div>

                <div class="space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Export Data Type:</label>
                        <select
                            wire:model="exportType"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200"
                        >
                            <option value="subnets">Subnets & CIDR Hierarchy</option>
                            <option value="ip_addresses">Authoritative IP Addresses</option>
                            <option value="devices">Hardware & Device Inventory</option>
                            @if (! $isDepartmentOperator)
                                <option value="audit_events">Immutable Audit Event Log</option>
                            @endif
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Departmental Scope:
                            @if ($isDepartmentOperator)
                                <span class="text-amber-600 text-[11px] font-normal">(Locked to your assigned scope T-021)</span>
                            @endif
                        </label>
                        <select
                            wire:model="exportDepartmentId"
                            @disabled($isDepartmentOperator && count($departments) === 1)
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200"
                        >
                            @if (! $isDepartmentOperator)
                                <option value="">All Departments (Global Enterprise)</option>
                            @endif
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }} ({{ $dept->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">VRF Routing Context:</label>
                        <select
                            wire:model="exportVrfId"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200"
                        >
                            <option value="">All VRFs</option>
                            @foreach ($vrfs as $vrf)
                                <option value="{{ $vrf->id }}">{{ $vrf->name }} ({{ $vrf->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-[11px] text-slate-500 space-y-1">
                        <p><strong>Compliance & Safety Constraints (Gate T-027):</strong></p>
                        <ul class="list-disc pl-4 space-y-0.5">
                            <li>Export is bounded to a maximum of 5,000 records.</li>
                            <li>Files are stored in private local storage; never accessible via public URLs.</li>
                            <li>Generated file automatically expires and is purged after 24 hours.</li>
                        </ul>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-700">
                    <button
                        wire:click="closeExportModal"
                        class="px-4 py-2 text-xs font-semibold rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300"
                    >
                        Cancel
                    </button>
                    <button
                        wire:click="generateExport"
                        class="px-4 py-2 text-xs font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700"
                    >
                        Generate & Prepare CSV
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
