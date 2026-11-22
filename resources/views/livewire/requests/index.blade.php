<div class="space-y-6">
    <!-- Header & Action Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 font-mono mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600">KYAMCH IPAM</a>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-semibold">Change Workflow</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">IP Requests & Approvals Portal</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Departmental IP requests, two-person policy approvals, SLA monitoring, and idempotent provisioning.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button
                wire:click="openCreateModal"
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Change Request
            </button>
        </div>
    </div>

    <!-- Alert Banners -->
    @if (session()->has('success'))
        <div class="p-4 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-800 dark:text-emerald-300 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="p-4 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs text-rose-800 dark:text-rose-300 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- KPI Metric Summary Tiles -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Requests</div>
            <div class="text-2xl font-bold font-mono text-slate-900 dark:text-white mt-1">{{ number_format($totalCount) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Authoritative audit log</div>
        </div>
        <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
            <div class="text-xs font-medium text-amber-600 uppercase tracking-wider">Pending Review</div>
            <div class="text-2xl font-bold font-mono text-amber-600 dark:text-amber-400 mt-1">{{ number_format($pendingCount) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Awaiting decision</div>
        </div>
        <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-indigo-200 dark:border-indigo-800/60 bg-indigo-50/20 dark:bg-indigo-950/10">
            <div class="text-xs font-medium text-indigo-600 dark:text-indigo-400 uppercase tracking-wider flex items-center justify-between">
                <span>Pending My Action</span>
                @if($pendingMyApprovalCount > 0)
                    <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                @endif
            </div>
            <div class="text-2xl font-bold font-mono text-indigo-700 dark:text-indigo-300 mt-1">{{ number_format($pendingMyApprovalCount) }}</div>
            <div class="text-[10px] text-indigo-500/80 mt-0.5">Eligible 2-person reviews</div>
        </div>
        <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
            <div class="text-xs font-medium text-blue-600 uppercase tracking-wider">Approved Ready</div>
            <div class="text-2xl font-bold font-mono text-blue-600 dark:text-blue-400 mt-1">{{ number_format($approvedCount) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Ready for provisioning</div>
        </div>
        <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
            <div class="text-xs font-medium text-rose-600 uppercase tracking-wider flex items-center justify-between">
                <span>SLA Breached</span>
                @if($overdueCount > 0)
                    <span class="px-1.5 py-0.5 text-[9px] font-mono rounded bg-rose-100 dark:bg-rose-900/60 text-rose-700 font-bold">URGENT</span>
                @endif
            </div>
            <div class="text-2xl font-bold font-mono text-rose-600 dark:text-rose-400 mt-1">{{ number_format($overdueCount) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Response SLA exceeded</div>
        </div>
    </div>

    <!-- Filter Tabs & Toolbar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 space-y-4">
        <!-- Status Tabs -->
        <div class="flex items-center gap-1 border-b border-slate-200 dark:border-slate-700 pb-3 overflow-x-auto text-xs font-medium">
            <button
                wire:click="$set('activeTab', 'all')"
                class="px-3 py-1.5 rounded-lg transition {{ $activeTab === 'all' ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}"
            >
                All Requests ({{ $totalCount }})
            </button>
            <button
                wire:click="$set('activeTab', 'pending_my_approval')"
                class="px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 {{ $activeTab === 'pending_my_approval' ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}"
            >
                <span>Pending My Action</span>
                @if($pendingMyApprovalCount > 0)
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono {{ $activeTab === 'pending_my_approval' ? 'bg-indigo-800 text-white' : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300' }}">{{ $pendingMyApprovalCount }}</span>
                @endif
            </button>
            <button
                wire:click="$set('activeTab', 'my_requests')"
                class="px-3 py-1.5 rounded-lg transition {{ $activeTab === 'my_requests' ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}"
            >
                My Submissions
            </button>
            <button
                wire:click="$set('activeTab', 'pending')"
                class="px-3 py-1.5 rounded-lg transition {{ $activeTab === 'pending' ? 'bg-amber-600 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}"
            >
                Pending ({{ $pendingCount }})
            </button>
            <button
                wire:click="$set('activeTab', 'approved')"
                class="px-3 py-1.5 rounded-lg transition {{ $activeTab === 'approved' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}"
            >
                Approved ({{ $approvedCount }})
            </button>
            <button
                wire:click="$set('activeTab', 'provisioned')"
                class="px-3 py-1.5 rounded-lg transition {{ $activeTab === 'provisioned' ? 'bg-emerald-600 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}"
            >
                Provisioned ({{ $provisionedCount }})
            </button>
            <button
                wire:click="$set('activeTab', 'rejected')"
                class="px-3 py-1.5 rounded-lg transition {{ $activeTab === 'rejected' ? 'bg-rose-600 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}"
            >
                Rejected
            </button>
        </div>

        <!-- Search & Filter Controls -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-xs">
            <div class="relative">
                <input
                    wire:model.live.debounce.300ms="search"
                    type="text"
                    placeholder="Search by REQ#, requester, reason..."
                    class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-2 focus:ring-emerald-500"
                />
                <svg class="w-4 h-4 absolute left-3 top-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <div>
                <select wire:model.live="selectedType" class="w-full py-2 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs">
                    <option value="">All Request Types</option>
                    @foreach(\App\Domain\Workflows\Enums\RequestType::cases() as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select wire:model.live="selectedPriority" class="w-full py-2 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs">
                    <option value="">All Priorities</option>
                    @foreach(\App\Domain\Workflows\Enums\RequestPriority::cases() as $priority)
                        <option value="{{ $priority->value }}">{{ $priority->label() }} Priority</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select wire:model.live="selectedDepartment" class="w-full py-2 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Request #</th>
                        <th class="py-3 px-4">Type & Details</th>
                        <th class="py-3 px-4">Requester / Dept</th>
                        <th class="py-3 px-4">Priority & SLA</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 font-mono">
                    @forelse($requests as $req)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition">
                            <td class="py-3.5 px-4 font-semibold text-slate-900 dark:text-white">
                                <button wire:click="openReviewDrawer({{ $req->id }})" class="hover:text-emerald-600 flex items-center gap-1.5">
                                    <span>{{ $req->request_number }}</span>
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                                <div class="text-[10px] text-slate-400 font-sans mt-0.5">{{ $req->created_at->format('M d, H:i') }}</div>
                            </td>
                            <td class="py-3.5 px-4 font-sans">
                                <span class="px-2 py-0.5 rounded text-[11px] font-medium border {{ $req->request_type->badgeClasses() }}">
                                    {{ $req->request_type->label() }}
                                </span>
                                <div class="text-[11px] text-slate-600 dark:text-slate-300 mt-1 max-w-xs truncate" title="{{ $req->reason }}">
                                    {{ $req->reason }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-sans">
                                <div class="font-medium text-slate-800 dark:text-slate-200">{{ $req->requester->name }}</div>
                                <div class="text-[10px] text-slate-400">
                                    {{ $req->department ? $req->department->name : 'Global / Campus IT' }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-sans">
                                <div class="flex items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-medium border {{ $req->priority->badgeClasses() }}">
                                        {{ $req->priority->label() }}
                                    </span>
                                </div>
                                <div class="mt-1 font-mono text-[10px]">
                                    @if($req->isOverdue())
                                        <span class="text-rose-600 font-bold flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            Breached ({{ $req->sla_due_at->diffForHumans() }})
                                        </span>
                                    @elseif($req->sla_due_at)
                                        <span class="text-slate-400">Due: {{ $req->sla_due_at->diffForHumans() }}</span>
                                    @else
                                        <span class="text-slate-400">N/A</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-sans">
                                <span class="px-2.5 py-1 rounded text-xs font-semibold border {{ $req->status->badgeClasses() }}">
                                    {{ $req->status->label() }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right font-sans space-x-1">
                                <button
                                    wire:click="openReviewDrawer({{ $req->id }})"
                                    class="px-2.5 py-1 text-xs rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 transition font-medium"
                                >
                                    Review
                                </button>
                                @can('provision', $req)
                                    <button
                                        wire:click="provision({{ $req->id }})"
                                        class="px-2.5 py-1 text-xs rounded bg-emerald-600 hover:bg-emerald-700 text-white transition font-medium shadow-2xs"
                                    >
                                        Provision
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400 font-sans">
                                No IP requests matching selected criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($requests->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-700">
                {{ $requests->links() }}
            </div>
        @endif
    </div>

    <!-- Review & Decision Slide-over Drawer -->
    @if($selectedRequest)
        <div class="fixed inset-0 z-50 overflow-hidden bg-slate-900/50 backdrop-blur-xs flex justify-end">
            <div class="w-full max-w-xl bg-white dark:bg-slate-800 h-full shadow-2xl flex flex-col justify-between overflow-y-auto">
                <div class="p-6 space-y-6">
                    <!-- Drawer Header -->
                    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-base font-bold text-slate-900 dark:text-white">{{ $selectedRequest->request_number }}</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold border {{ $selectedRequest->status->badgeClasses() }}">
                                    {{ $selectedRequest->status->label() }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">Submitted by {{ $selectedRequest->requester->name }} on {{ $selectedRequest->created_at->format('M d, Y H:i') }}</p>
                        </div>
                        <button wire:click="closeReviewDrawer" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Two-Person Rule Warning Banner if viewing own request (T-022) -->
                    @if($selectedRequest->requester_id === auth()->id() && $selectedRequest->isPending())
                        <div class="p-4 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-700 text-xs text-amber-800 dark:text-amber-300 space-y-1">
                            <div class="font-semibold flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>Separation of Duties Enforced (Two-Person Rule)</span>
                            </div>
                            <p>As the author of this request, you cannot approve or reject it. An independent approver or Network Administrator must review this change.</p>
                        </div>
                    @endif

                    <!-- Core Request Details -->
                    <div class="bg-slate-50 dark:bg-slate-900/60 rounded-xl p-4 border border-slate-200 dark:border-slate-700 space-y-3 text-xs">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase">Request Type</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $selectedRequest->request_type->label() }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase">Department</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $selectedRequest->department ? $selectedRequest->department->name : 'Global IT' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase">Priority</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $selectedRequest->priority->label() }} Priority</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase">SLA Target</span>
                                <span class="font-mono text-slate-800 dark:text-slate-200">
                                    {{ $selectedRequest->sla_due_at ? $selectedRequest->sla_due_at->format('M d, H:i') : 'N/A' }}
                                    @if($selectedRequest->isOverdue())
                                        <span class="text-rose-600 font-bold ml-1">(Overdue)</span>
                                    @endif
                                </span>
                            </div>
                        </div>

                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase">Business Justification</span>
                            <p class="text-slate-700 dark:text-slate-300 mt-0.5 italic bg-white dark:bg-slate-800 p-2.5 rounded border border-slate-200 dark:border-slate-700">
                                "{{ $selectedRequest->reason }}"
                            </p>
                        </div>

                        <!-- Payload breakdown -->
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase mb-1">Payload Specification</span>
                            <div class="bg-slate-900 text-emerald-400 p-3 rounded font-mono text-[11px] overflow-x-auto">
                                <pre>{{ json_encode($selectedRequest->payload_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                        </div>

                        @if($selectedRequest->rejection_reason)
                            <div class="p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded">
                                <span class="text-rose-700 dark:text-rose-400 block text-[10px] font-semibold uppercase">Rejection Reason</span>
                                <p class="text-rose-800 dark:text-rose-300 mt-0.5">{{ $selectedRequest->rejection_reason }}</p>
                            </div>
                        @endif

                        @if($selectedRequest->cancellation_reason)
                            <div class="p-3 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded">
                                <span class="text-slate-600 dark:text-slate-400 block text-[10px] font-semibold uppercase">Cancellation Reason</span>
                                <p class="text-slate-700 dark:text-slate-300 mt-0.5">{{ $selectedRequest->cancellation_reason }}</p>
                            </div>
                        @endif
                    </div>

                    <!-- Approvals & Change History Timeline -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-semibold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Workflow Timeline</h4>
                        <div class="space-y-2 font-sans text-xs">
                            <div class="p-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 flex items-start gap-3">
                                <div class="w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-500 flex items-center justify-center shrink-0 text-[10px] font-bold">1</div>
                                <div>
                                    <div class="font-medium text-slate-800 dark:text-slate-200">Request Submitted</div>
                                    <div class="text-[11px] text-slate-500">{{ $selectedRequest->requester->name }} ({{ $selectedRequest->requester->role->label() }}) at {{ $selectedRequest->created_at->format('M d, Y H:i:s') }}</div>
                                </div>
                            </div>

                            @foreach($selectedRequest->approvals as $approval)
                                <div class="p-3 rounded-lg border {{ $approval->decision->value === 'approved' ? 'border-emerald-200 dark:border-emerald-800 bg-emerald-50/30' : 'border-rose-200 dark:border-rose-800 bg-rose-50/30' }} flex items-start gap-3">
                                    <div class="w-6 h-6 rounded-full {{ $approval->decision->value === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }} flex items-center justify-center shrink-0 text-[10px] font-bold">2</div>
                                    <div>
                                        <div class="font-medium {{ $approval->decision->value === 'approved' ? 'text-emerald-900 dark:text-emerald-200' : 'text-rose-900 dark:text-rose-200' }}">
                                            Decision: {{ ucfirst($approval->decision->value) }}
                                        </div>
                                        <div class="text-[11px] text-slate-500">Decided by {{ $approval->approver->name }} on {{ $approval->decided_at->format('M d, Y H:i:s') }}</div>
                                        @if($approval->comments)
                                            <div class="text-[11px] text-slate-600 dark:text-slate-300 mt-1 italic">"{{ $approval->comments }}"</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                            @if($selectedRequest->isProvisioned())
                                <div class="p-3 rounded-lg border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-950/40 flex items-start gap-3">
                                    <div class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center shrink-0 text-[10px] font-bold">✓</div>
                                    <div>
                                        <div class="font-medium text-emerald-900 dark:text-emerald-200">Provisioned into Live IPAM</div>
                                        <div class="text-[11px] text-emerald-700 dark:text-emerald-300">
                                            Executed by {{ $selectedRequest->provisioner ? $selectedRequest->provisioner->name : 'System Admin' }} on {{ $selectedRequest->provisioned_at?->format('M d, Y H:i:s') }}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Drawer Footer Actions -->
                <div class="p-6 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 space-y-3">
                    @can('approve', $selectedRequest)
                        <div class="space-y-2">
                            <input
                                wire:model="approvalComments"
                                type="text"
                                placeholder="Optional approval notes or conditions..."
                                class="w-full py-1.5 px-3 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs"
                            />
                            <div class="flex items-center gap-2">
                                <button
                                    wire:click="approve({{ $selectedRequest->id }})"
                                    class="flex-1 py-2 px-4 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition"
                                >
                                    Approve Request
                                </button>
                                <button
                                    wire:click="$toggle('showRejectModal')"
                                    class="py-2 px-4 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-xs transition"
                                >
                                    Reject
                                </button>
                            </div>
                        </div>
                    @endcan

                    @can('provision', $selectedRequest)
                        <button
                            wire:click="provision({{ $selectedRequest->id }})"
                            class="w-full py-2.5 px-4 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-xs transition flex items-center justify-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Execute Provisioning (Idempotent)
                        </button>
                    @endcan

                    @can('cancel', $selectedRequest)
                        <button
                            wire:click="$toggle('showCancelModal')"
                            class="w-full py-1.5 px-3 rounded text-slate-500 hover:text-rose-600 text-[11px] transition text-center"
                        >
                            Cancel Request
                        </button>
                    @endcan
                </div>
            </div>
        </div>
    @endif

    <!-- Submit Request Modal -->
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-xl overflow-hidden">
                <div class="p-5 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Submit New IPAM Change Request</h3>
                        <p class="text-xs text-slate-500">Requires formal review before execution in network state.</p>
                    </div>
                    <button wire:click="closeCreateModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-5 space-y-4 text-xs">
                    <!-- Request Type & Priority -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Request Type</label>
                            <select wire:model.live="formRequestType" class="w-full py-1.5 px-3 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs">
                                <option value="ip_assignment">IP Address Assignment</option>
                                <option value="ip_release">IP Address Release</option>
                                <option value="ip_transfer">IP Interface Transfer</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Priority (SLA Target)</label>
                            <select wire:model="formPriority" class="w-full py-1.5 px-3 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs">
                                <option value="low">Low (120 hrs SLA)</option>
                                <option value="normal">Normal (48 hrs SLA)</option>
                                <option value="urgent">Urgent (8 hrs SLA)</option>
                                <option value="emergency">Emergency (2 hrs SLA)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Department -->
                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Department</label>
                        <select wire:model="formDepartmentId" class="w-full py-1.5 px-3 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs">
                            <option value="">Global / Campus Network</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }} ({{ $dept->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Conditional payload inputs for IP Assignment -->
                    @if($formRequestType === 'ip_assignment')
                        <div class="p-3 bg-slate-50 dark:bg-slate-900/50 rounded-lg border border-slate-200 dark:border-slate-700 space-y-3">
                            <div class="font-semibold text-slate-800 dark:text-slate-200">Assignment Parameters</div>
                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Target Subnet *</label>
                                <select wire:model="formSubnetId" class="w-full py-1.5 px-3 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-mono">
                                    <option value="">-- Choose Subnet --</option>
                                    @foreach($subnets as $sub)
                                        <option value="{{ $sub->id }}">{{ $sub->cidr }} [{{ $sub->vrf->code }}] ({{ $sub->name }})</option>
                                    @endforeach
                                </select>
                                @error('formSubnetId') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Specific IP (Optional)</label>
                                    <input wire:model="formIpAddress" type="text" placeholder="Auto-assign next free if blank" class="w-full py-1.5 px-3 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-mono" />
                                </div>
                                <div>
                                    <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Hostname / FQDN</label>
                                    <input wire:model="formHostname" type="text" placeholder="e.g. ky-med-icu04.local" class="w-full py-1.5 px-3 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-mono" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Target Device (Optional)</label>
                                    <select wire:model="formDeviceId" class="w-full py-1.5 px-3 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs">
                                        <option value="">-- Standalone / Unbound --</option>
                                        @foreach($devices as $dev)
                                            <option value="{{ $dev->id }}">{{ $dev->name }} ({{ $dev->code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Classification</label>
                                    <select wire:model="formClassification" class="w-full py-1.5 px-3 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs">
                                        <option value="static_host">Static Host Address</option>
                                        <option value="vip">Virtual IP (VIP)</option>
                                        <option value="gateway">Default Gateway</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Conditional payload inputs for IP Release -->
                    @if($formRequestType === 'ip_release')
                        <div class="p-3 bg-slate-50 dark:bg-slate-900/50 rounded-lg border border-slate-200 dark:border-slate-700 space-y-3">
                            <div class="font-semibold text-slate-800 dark:text-slate-200">Release Parameters</div>
                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Active IP to Release *</label>
                                <select wire:model="formIpAddressId" class="w-full py-1.5 px-3 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-mono">
                                    <option value="">-- Choose Assigned IP --</option>
                                    @foreach($assignedIps as $ip)
                                        <option value="{{ $ip->id }}">{{ $ip->address }} ({{ $ip->hostname ?: 'Unassigned Hostname' }})</option>
                                    @endforeach
                                </select>
                                @error('formIpAddressId') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Post-Release Quarantine Days</label>
                                <input wire:model="formQuarantineDays" type="number" min="0" max="90" class="w-full py-1.5 px-3 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-mono" />
                            </div>
                        </div>
                    @endif

                    <!-- Conditional payload inputs for IP Transfer -->
                    @if($formRequestType === 'ip_transfer')
                        <div class="p-3 bg-slate-50 dark:bg-slate-900/50 rounded-lg border border-slate-200 dark:border-slate-700 space-y-3">
                            <div class="font-semibold text-slate-800 dark:text-slate-200">Transfer Parameters</div>
                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Active IP to Transfer *</label>
                                <select wire:model="formIpAddressId" class="w-full py-1.5 px-3 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-mono">
                                    <option value="">-- Choose Assigned IP --</option>
                                    @foreach($assignedIps as $ip)
                                        <option value="{{ $ip->id }}">{{ $ip->address }}</option>
                                    @endforeach
                                </select>
                                @error('formIpAddressId') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Destination Device Interface *</label>
                                <select wire:model="formTargetInterfaceId" class="w-full py-1.5 px-3 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-mono">
                                    <option value="">-- Choose Interface --</option>
                                    @foreach($devices as $dev)
                                        <optgroup label="{{ $dev->name }}">
                                            @foreach($dev->interfaces as $iface)
                                                <option value="{{ $iface->id }}">{{ $dev->code }} / {{ $iface->name }} ({{ $iface->mac_address ?: 'No MAC' }})</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                @error('formTargetInterfaceId') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    @endif

                    <!-- Business Reason / Justification -->
                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Business Reason & Purpose *</label>
                        <textarea
                            wire:model="formReason"
                            rows="3"
                            placeholder="Provide specific medical, infrastructure, or operational justification for this change..."
                            class="w-full py-1.5 px-3 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs"
                        ></textarea>
                        @error('formReason') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="p-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 flex items-center justify-end gap-2">
                    <button wire:click="closeCreateModal" class="px-3.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-medium hover:bg-slate-100 transition">
                        Cancel
                    </button>
                    <button wire:click="submitRequest" class="px-4 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition">
                        Submit for Approval
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Rejection Modal -->
    @if($showRejectModal && $selectedRequest)
        <div class="fixed inset-0 z-60 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md p-5 space-y-4">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Reject Request {{ $selectedRequest->request_number }}</h3>
                <p class="text-xs text-slate-500">Please provide a mandatory reason for rejecting this change.</p>
                <div>
                    <textarea
                        wire:model="rejectionReason"
                        rows="3"
                        placeholder="State why this change request cannot be approved..."
                        class="w-full py-2 px-3 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs"
                    ></textarea>
                    @error('rejectionReason') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                </div>
                <div class="flex items-center justify-end gap-2">
                    <button wire:click="$toggle('showRejectModal')" class="px-3 py-1.5 rounded border border-slate-200 text-xs">Cancel</button>
                    <button wire:click="reject({{ $selectedRequest->id }})" class="px-3.5 py-1.5 rounded bg-rose-600 text-white text-xs font-semibold">Confirm Rejection</button>
                </div>
            </div>
        </div>
    @endif

    <!-- Cancellation Modal -->
    @if($showCancelModal && $selectedRequest)
        <div class="fixed inset-0 z-60 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md p-5 space-y-4">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Cancel Request {{ $selectedRequest->request_number }}</h3>
                <div>
                    <textarea
                        wire:model="cancellationReason"
                        rows="3"
                        placeholder="State reason for cancellation..."
                        class="w-full py-2 px-3 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs"
                    ></textarea>
                    @error('cancellationReason') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                </div>
                <div class="flex items-center justify-end gap-2">
                    <button wire:click="$toggle('showCancelModal')" class="px-3 py-1.5 rounded border border-slate-200 text-xs">Cancel</button>
                    <button wire:click="cancel({{ $selectedRequest->id }})" class="px-3.5 py-1.5 rounded bg-slate-700 text-white text-xs font-semibold">Confirm Cancellation</button>
                </div>
            </div>
        </div>
    @endif
</div>
