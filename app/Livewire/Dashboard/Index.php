<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\AuditEvent;
use App\Models\IpAddress;
use App\Models\Subnet;
use App\Models\Vlan;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Operations Dashboard - KYAMCH IPAM')]
class Index extends Component
{
    public function render(): View
    {
        $subnetCount = Subnet::count();
        $ipv4SubnetCount = Subnet::where('ip_version', 4)->count();
        $ipv6SubnetCount = Subnet::where('ip_version', 6)->count();

        $vlanCount = Vlan::count();

        $totalIpCount = IpAddress::count();
        $assignedIpCount = IpAddress::where('allocation_status', 'assigned')->count();
        $reservedIpCount = IpAddress::where('allocation_status', 'reserved')->count();
        $quarantinedIpCount = IpAddress::where('allocation_status', 'quarantined')->count();

        // Subnet Capacity Watchlist (Top 5 utilized subnets)
        $subnets = Subnet::with('vlan')->take(50)->get();
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

        $recentAudits = AuditEvent::recent(6)->get();

        return view('livewire.dashboard.index', [
            'subnetCount' => $subnetCount,
            'ipv4SubnetCount' => $ipv4SubnetCount,
            'ipv6SubnetCount' => $ipv6SubnetCount,
            'vlanCount' => $vlanCount,
            'totalIpCount' => $totalIpCount,
            'assignedIpCount' => $assignedIpCount,
            'reservedIpCount' => $reservedIpCount,
            'quarantinedIpCount' => $quarantinedIpCount,
            'capacityWatchlist' => $capacityWatchlist,
            'recentAudits' => $recentAudits,
            'defaultTimezone' => config('ipam.default_timezone', 'Asia/Dhaka'),
            'databaseDriver' => config('database.default'),
        ]);
    }
}
