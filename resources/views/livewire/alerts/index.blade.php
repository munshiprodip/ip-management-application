<div class="space-y-6">
    <!-- Header / Breadcrumb -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 font-mono mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600">KYAMCH IPAM</a>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-semibold">Alerts</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">Alerts & Operational Event Management</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Deduplicated alerts, reconciliation discrepancies, subnet exhaustion warnings and stale device tracking.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-2.5 py-1 rounded text-xs font-mono font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                Deduplication Engine Active
            </span>
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

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-xs">
            <div class="flex items-center justify-between text-xs text-slate-500">
                <span>Open Alerts</span>
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
            </div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-2 font-mono">{{ $openCount }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Requiring operator review or triage</p>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-xs">
            <div class="flex items-center justify-between text-xs text-slate-500">
                <span>Critical Incidents</span>
                <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
            </div>
            <p class="text-2xl font-bold text-rose-600 dark:text-rose-400 mt-2 font-mono">{{ $criticalCount }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Conflicts, rogues or >=95% exhaustion</p>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-xs">
            <div class="flex items-center justify-between text-xs text-slate-500">
                <span>Subnet Exhaustion</span>
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
            </div>
            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-2 font-mono">{{ $exhaustionCount }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Subnets >= 80% capacity utilization</p>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-xs">
            <div class="flex items-center justify-between text-xs text-slate-500">
                <span>Acknowledged / Triage</span>
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
            </div>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-2 font-mono">{{ $acknowledgedCount }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Currently assigned & under investigation</p>
        </div>
    </div>

    <!-- Filter Toolbar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col md:flex-row gap-4 items-center justify-between">
        <div class="w-full md:w-80">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search alerts, rules, dedupe keys..."
                class="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-hidden focus:ring-2 focus:ring-emerald-500"
            />
        </div>

        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <div class="flex items-center gap-1.5 text-xs text-slate-500">
                <label>Severity:</label>
                <select
                    wire:model.live="severityFilter"
                    class="px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200"
                >
                    <option value="all">All Severities</option>
                    <option value="critical">Critical</option>
                    <option value="warning">Warning</option>
                    <option value="info">Informational</option>
                </select>
            </div>

            <div class="flex items-center gap-1.5 text-xs text-slate-500">
                <label>Status:</label>
                <select
                    wire:model.live="statusFilter"
                    class="px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200"
                >
                    <option value="open">Open / Triage</option>
                    <option value="acknowledged">Acknowledged</option>
                    <option value="resolved">Resolved</option>
                    <option value="suppressed">Suppressed</option>
                    <option value="all">All Statuses</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Alerts Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700 text-slate-400 text-[11px] font-mono uppercase">
                        <th class="pb-2.5">Severity</th>
                        <th class="pb-2.5">Rule / Title</th>
                        <th class="pb-2.5">Dedupe Key</th>
                        <th class="pb-2.5">Occurrences</th>
                        <th class="pb-2.5">Last Seen</th>
                        <th class="pb-2.5">Status</th>
                        <th class="pb-2.5">Assignee</th>
                        <th class="pb-2.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 font-mono text-[11px]">
                    @forelse ($alerts as $alert)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-700/30">
                            <td class="py-3">
                                <span class="px-2 py-0.5 rounded text-[10px] border {{ $alert->severity->badgeClasses() }}">
                                    {{ $alert->severity->label() }}
                                </span>
                            </td>
                            <td class="py-3 font-sans">
                                <p class="font-semibold text-slate-900 dark:text-white">{{ $alert->title }}</p>
                                <p class="text-[11px] text-slate-500 font-mono mt-0.5">{{ $alert->rule_code->label() }}</p>
                            </td>
                            <td class="py-3 text-slate-500 font-mono text-[10px]">
                                {{ Str::limit($alert->dedupe_key, 28) }}
                            </td>
                            <td class="py-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $alert->occurrence_count > 1 ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300' }}">
                                    {{ $alert->occurrence_count }}x
                                </span>
                            </td>
                            <td class="py-3 text-slate-500 whitespace-nowrap">
                                {{ $alert->last_occurred_at->setTimezone(config('ipam.default_timezone'))->format('Y-m-d H:i') }}
                            </td>
                            <td class="py-3">
                                <span class="px-2 py-0.5 rounded text-[10px] border {{ $alert->status->badgeClasses() }}">
                                    {{ $alert->status->label() }}
                                </span>
                            </td>
                            <td class="py-3 font-sans text-slate-600 dark:text-slate-400">
                                {{ $alert->assignee->name ?? 'Unassigned' }}
                            </td>
                            <td class="py-3 text-right whitespace-nowrap space-x-1">
                                @if ($alert->status === \App\Domain\Alerts\Enums\AlertStatus::OPEN && auth()->user()->isNetworkAdmin())
                                    <button
                                        wire:click="acknowledgeAlert({{ $alert->id }})"
                                        class="px-2 py-1 text-[11px] rounded bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 font-sans font-medium"
                                        title="Acknowledge & assign to self"
                                    >
                                        Acknowledge
                                    </button>
                                @endif
                                <button
                                    wire:click="selectAlert({{ $alert->id }})"
                                    class="px-2 py-1 text-[11px] rounded bg-slate-100 text-slate-700 border border-slate-200 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-200 font-sans font-medium"
                                >
                                    Investigate
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400 font-sans">
                                No operational alerts matching the selected criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $alerts->links() }}
        </div>
    </div>

    <!-- Alert Investigation Drawer -->
    @if ($selectedAlert)
        <div class="fixed inset-0 z-50 overflow-hidden bg-slate-900/50 backdrop-blur-xs flex justify-end">
            <div class="w-full max-w-xl bg-white dark:bg-slate-800 h-full shadow-2xl p-6 overflow-y-auto space-y-6">
                <!-- Drawer Header -->
                <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-700">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 rounded text-[10px] border {{ $selectedAlert->severity->badgeClasses() }}">
                                {{ $selectedAlert->severity->label() }}
                            </span>
                            <span class="text-xs font-mono text-slate-400">Alert #{{ $selectedAlert->id }}</span>
                        </div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ $selectedAlert->title }}</h2>
                    </div>
                    <button wire:click="closeDrawer" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
                </div>

                <!-- Occurrence & Lifecycle Details -->
                <div class="bg-slate-50 dark:bg-slate-900/60 rounded-xl p-4 border border-slate-200 dark:border-slate-700 space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Rule Code:</span>
                        <span class="font-mono font-semibold text-slate-800 dark:text-slate-200">{{ $selectedAlert->rule_code->value }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Dedupe Key:</span>
                        <span class="font-mono text-[11px] text-slate-800 dark:text-slate-200 break-all">{{ $selectedAlert->dedupe_key }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Occurrences:</span>
                        <span class="font-mono font-bold text-amber-600">{{ $selectedAlert->occurrence_count }} occurrences</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">First Opened:</span>
                        <span class="font-mono text-slate-800 dark:text-slate-200">{{ $selectedAlert->opened_at->setTimezone(config('ipam.default_timezone'))->format('Y-m-d H:i:s') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Last Occurred:</span>
                        <span class="font-mono text-slate-800 dark:text-slate-200">{{ $selectedAlert->last_occurred_at->setTimezone(config('ipam.default_timezone'))->format('Y-m-d H:i:s') }}</span>
                    </div>
                    @if ($selectedAlert->resolved_at)
                        <div class="flex justify-between">
                            <span class="text-slate-500">Resolved At:</span>
                            <span class="font-mono text-emerald-600">{{ $selectedAlert->resolved_at->setTimezone(config('ipam.default_timezone'))->format('Y-m-d H:i:s') }}</span>
                        </div>
                    @endif
                </div>

                <!-- Description & Context Payload -->
                <div class="space-y-2">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Description</h3>
                    <p class="text-xs text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-900 p-3 rounded-lg border border-slate-200 dark:border-slate-700">
                        {{ $selectedAlert->description }}
                    </p>
                </div>

                @if (!empty($selectedAlert->context_json))
                    <div class="space-y-2">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Context Metadata</h3>
                        <pre class="text-[11px] font-mono bg-slate-900 text-slate-200 p-3 rounded-lg overflow-x-auto border border-slate-800">{{ json_encode($selectedAlert->context_json, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                @endif

                @if ($selectedAlert->resolution_note)
                    <div class="space-y-2">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Resolution Note</h3>
                        <p class="text-xs text-emerald-800 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 p-3 rounded-lg border border-emerald-200 dark:border-emerald-800 font-sans">
                            {{ $selectedAlert->resolution_note }}
                        </p>
                    </div>
                @endif

                <!-- Resolution Actions (Authorized Operators Only) -->
                @if ($selectedAlert->isOpen() && auth()->user()->isNetworkAdmin())
                    <div class="space-y-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Operational Remediation</h3>
                        <div>
                            <label class="block text-xs text-slate-600 dark:text-slate-400 mb-1">Resolution or Suppression Reason:</label>
                            <textarea
                                wire:model="resolutionNote"
                                rows="3"
                                placeholder="Explain corrective actions taken or justification for suppression..."
                                class="w-full text-xs p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                            ></textarea>
                            @error('resolutionNote') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex gap-2">
                            <button
                                wire:click="resolveAlert"
                                class="flex-1 px-4 py-2 text-xs font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700"
                            >
                                Mark as Resolved
                            </button>
                            <button
                                wire:click="suppressAlert"
                                class="px-4 py-2 text-xs font-semibold rounded-lg bg-slate-200 text-slate-700 hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-300"
                            >
                                Suppress Alert
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
