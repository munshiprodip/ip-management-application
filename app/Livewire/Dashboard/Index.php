<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Domain\Alerts\Enums\AlertSeverity;
use App\Domain\Alerts\Enums\AlertStatus;
use App\Models\Alert;
use App\Models\AuditEvent;
use App\Models\Device;
use App\Models\IpAddress;
use App\Models\IpRequest;
use App\Models\Subnet;
use App\Models\User;
use App\Models\Vrf;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Operations Dashboard - KYAMCH IPAM')]
class Index extends Component
{
    public function render(): View
    {
        $userCount = User::count();
        $auditCount = AuditEvent::count();
        $vrfCount = Vrf::count();
        $subnetCount = Subnet::count();
        $deviceCount = Device::count();

        $totalIpCount = IpAddress::count();
        $assignedIpCount = IpAddress::where('allocation_status', 'assigned')->count();
        $reservedIpCount = IpAddress::where('allocation_status', 'reserved')->count();
        $quarantinedIpCount = IpAddress::where('allocation_status', 'quarantined')->count();

        $openAlertCount = Alert::open()->count();
        $criticalAlertCount = Alert::open()->critical()->count();
        $pendingRequestCount = DB::table('ip_requests')->where('status', 'pending_approval')->count();

        // Subnet Capacity Watchlist (Subnets sorted by utilization)
        $subnets = Subnet::with(['vrf', 'site'])->take(50)->get();
        $capacityWatchlist = $subnets->map(function ($subnet) {
            $total = (int) (string) $subnet->totalAddresses();
            $allocated = $subnet->allocatedCount();
            $utilization = $total > 0 ? round(($allocated / $total) * 100, 1) : 0.0;

            return [
                'subnet' => $subnet,
                'total' => $total,
                'allocated' => $allocated,
                'utilization' => $utilization,
            ];
        })->sortByDesc('utilization')->take(5);

        // Active Alerts
        $activeAlerts = Alert::with(['assignee', 'finding'])
            ->open()
            ->orderByDesc('last_occurred_at')
            ->take(5)
            ->get();

        $recentAudits = AuditEvent::recent(6)->get();

        return view('livewire.dashboard.index', [
            'userCount' => $userCount,
            'auditCount' => $auditCount,
            'vrfCount' => $vrfCount,
            'subnetCount' => $subnetCount,
            'deviceCount' => $deviceCount,
            'totalIpCount' => $totalIpCount,
            'assignedIpCount' => $assignedIpCount,
            'reservedIpCount' => $reservedIpCount,
            'quarantinedIpCount' => $quarantinedIpCount,
            'openAlertCount' => $openAlertCount,
            'criticalAlertCount' => $criticalAlertCount,
            'pendingRequestCount' => $pendingRequestCount,
            'capacityWatchlist' => $capacityWatchlist,
            'activeAlerts' => $activeAlerts,
            'recentAudits' => $recentAudits,
            'discoveryEnabled' => config('ipam.discovery_enabled'),
            'writesEnabled' => config('ipam.external_writes_enabled'),
            'databaseDriver' => config('database.default'),
            'defaultTimezone' => config('ipam.default_timezone'),
            'quarantineDays' => config('ipam.quarantine_duration_days'),
        ]);
    }
}
