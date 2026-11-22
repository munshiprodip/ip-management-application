<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Domain\Alerts\Enums\ExportType;
use App\Models\AuditEvent;
use App\Models\Department;
use App\Models\ExportJob;
use App\Models\Vrf;
use App\Services\Reporting\ReportExportService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Reports & Audit Trail - KYAMCH IPAM')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $eventTypeFilter = 'all';

    // Export generation modal state
    public bool $showExportModal = false;
    public string $exportType = 'subnets';
    public ?int $exportDepartmentId = null;
    public ?int $exportVrfId = null;

    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedEventTypeFilter(): void
    {
        $this->resetPage();
    }

    public function openExportModal(): void
    {
        $user = auth()->user();
        $this->exportType = 'subnets';

        // Pre-fill department for department operator
        if ($user->isDepartmentOperator()) {
            $allowed = $user->allowedDepartmentIds();
            $this->exportDepartmentId = ! empty($allowed) ? $allowed[0] : null;
        } else {
            $this->exportDepartmentId = null;
        }

        $this->exportVrfId = null;
        $this->showExportModal = true;
        $this->successMessage = null;
        $this->errorMessage = null;
    }

    public function closeExportModal(): void
    {
        $this->showExportModal = false;
    }

    public function generateExport(ReportExportService $service): void
    {
        $user = auth()->user();

        $filters = [];
        if ($this->exportDepartmentId !== null) {
            $filters['department_id'] = $this->exportDepartmentId;
        }
        if ($this->exportVrfId !== null) {
            $filters['vrf_id'] = $this->exportVrfId;
        }

        try {
            $job = $service->generateExport($user, $this->exportType, $filters);
            $this->showExportModal = false;
            $this->successMessage = sprintf(
                'Export generated successfully: %s (%d records, %s). Available for download for 24 hours.',
                $job->file_name,
                $job->row_count,
                number_format($job->file_size_bytes) . ' bytes'
            );
        } catch (AuthorizationException $e) {
            $this->errorMessage = $e->getMessage();
        } catch (\Throwable $e) {
            $this->errorMessage = 'Export failed: ' . $e->getMessage();
        }
    }

    public function render(): View
    {
        $user = auth()->user();

        // Audit Events query
        $auditQuery = AuditEvent::with('actor');

        if ($this->search !== '') {
            $term = '%' . $this->search . '%';
            $auditQuery->where(function ($q) use ($term) {
                $q->where('event_type', 'like', $term)
                    ->orWhere('reason', 'like', $term)
                    ->orWhere('ip_address', 'like', $term);
            });
        }

        if ($this->eventTypeFilter !== 'all') {
            $auditQuery->where('event_type', $this->eventTypeFilter);
        }

        $auditEvents = $auditQuery->orderByDesc('id')->paginate(12, ['*'], 'auditPage');

        // Export Jobs query: requester or admin
        $jobsQuery = ExportJob::with('requester');
        if (! $user->isNetworkAdmin()) {
            $jobsQuery->where('requested_by', $user->id);
        }
        $exportJobs = $jobsQuery->orderByDesc('id')->take(6)->get();

        $distinctEventTypes = AuditEvent::select('event_type')->distinct()->pluck('event_type');

        $departments = $user->isDepartmentOperator()
            ? Department::whereIn('id', $user->allowedDepartmentIds())->get()
            : Department::all();

        $vrfs = Vrf::all();

        return view('livewire.reports.index', [
            'auditEvents' => $auditEvents,
            'exportJobs' => $exportJobs,
            'distinctEventTypes' => $distinctEventTypes,
            'departments' => $departments,
            'vrfs' => $vrfs,
            'isDepartmentOperator' => $user->isDepartmentOperator(),
        ]);
    }
}
