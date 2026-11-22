<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Models\AuditEvent;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Reports & Audit Trail - KYAMCH IPAM')]
class Index extends Component
{
    public function render()
    {
        $auditEvents = AuditEvent::orderByDesc('id')->paginate(15);

        return view('livewire.reports.index', [
            'auditEvents' => $auditEvents,
        ]);
    }
}
