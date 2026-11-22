<?php

declare(strict_types=1);

namespace App\Livewire\Alerts;

use App\Domain\Alerts\Enums\AlertRuleCode;
use App\Domain\Alerts\Enums\AlertSeverity;
use App\Domain\Alerts\Enums\AlertStatus;
use App\Models\Alert;
use App\Models\AuditEvent;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Alerts & Operational Event Management - KYAMCH IPAM')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $severityFilter = 'all';
    public string $statusFilter = 'open';

    public ?int $selectedAlertId = null;
    public string $resolutionNote = '';
    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSeverityFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function selectAlert(int $id): void
    {
        $this->selectedAlertId = $id;
        $this->resolutionNote = '';
        $this->successMessage = null;
        $this->errorMessage = null;
    }

    public function closeDrawer(): void
    {
        $this->selectedAlertId = null;
        $this->resolutionNote = '';
    }

    public function acknowledgeAlert(int $id): void
    {
        $alert = Alert::findOrFail($id);

        if (! Gate::allows('acknowledge', $alert)) {
            $this->errorMessage = 'Unauthorized: Only network administrators may acknowledge alerts.';
            return;
        }

        $alert->acknowledge(auth()->user());

        AuditEvent::record(
            eventType: 'alert.acknowledged',
            reason: sprintf('Alert #%d acknowledged by %s', $alert->id, auth()->user()->name),
            subject: $alert
        );

        $this->successMessage = sprintf('Alert #%d successfully acknowledged.', $alert->id);
    }

    public function resolveAlert(): void
    {
        if ($this->selectedAlertId === null) {
            return;
        }

        $alert = Alert::findOrFail($this->selectedAlertId);

        if (! Gate::allows('resolve', $alert)) {
            $this->errorMessage = 'Unauthorized: Only network administrators may resolve alerts.';
            return;
        }

        $this->validate([
            'resolutionNote' => 'required|string|min:5|max:1000',
        ]);

        $alert->resolve(auth()->user(), $this->resolutionNote);

        AuditEvent::record(
            eventType: 'alert.resolved',
            reason: sprintf('Alert #%d resolved by %s: %s', $alert->id, auth()->user()->name, $this->resolutionNote),
            subject: $alert,
            after: ['resolution_note' => $this->resolutionNote]
        );

        $this->successMessage = sprintf('Alert #%d marked as resolved.', $alert->id);
        $this->selectedAlertId = null;
        $this->resolutionNote = '';
    }

    public function suppressAlert(): void
    {
        if ($this->selectedAlertId === null) {
            return;
        }

        $alert = Alert::findOrFail($this->selectedAlertId);

        if (! Gate::allows('suppress', $alert)) {
            $this->errorMessage = 'Unauthorized: Only network administrators may suppress alerts.';
            return;
        }

        $this->validate([
            'resolutionNote' => 'required|string|min:5|max:1000',
        ]);

        $alert->suppress(auth()->user(), $this->resolutionNote);

        AuditEvent::record(
            eventType: 'alert.suppressed',
            reason: sprintf('Alert #%d suppressed by %s: %s', $alert->id, auth()->user()->name, $this->resolutionNote),
            subject: $alert,
            after: ['suppression_note' => $this->resolutionNote]
        );

        $this->successMessage = sprintf('Alert #%d suppressed.', $alert->id);
        $this->selectedAlertId = null;
        $this->resolutionNote = '';
    }

    public function render(): View
    {
        $openCount = Alert::open()->count();
        $criticalCount = Alert::open()->critical()->count();
        $exhaustionCount = Alert::open()->where('rule_code', AlertRuleCode::SUBNET_EXHAUSTION->value)->count();
        $acknowledgedCount = Alert::where('status', AlertStatus::ACKNOWLEDGED->value)->count();

        $query = Alert::with(['finding', 'assignee']);

        if ($this->search !== '') {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('dedupe_key', 'like', $term)
                    ->orWhere('rule_code', 'like', $term);
            });
        }

        if ($this->severityFilter !== 'all') {
            $query->where('severity', $this->severityFilter);
        }

        if ($this->statusFilter === 'open') {
            $query->open();
        } elseif ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $alerts = $query->orderByDesc('last_occurred_at')->paginate(12);

        $selectedAlert = $this->selectedAlertId !== null
            ? Alert::with(['finding', 'assignee', 'notifications.recipient'])->find($this->selectedAlertId)
            : null;

        return view('livewire.alerts.index', [
            'alerts' => $alerts,
            'selectedAlert' => $selectedAlert,
            'openCount' => $openCount,
            'criticalCount' => $criticalCount,
            'exhaustionCount' => $exhaustionCount,
            'acknowledgedCount' => $acknowledgedCount,
        ]);
    }
}
