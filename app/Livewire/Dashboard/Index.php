<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\AuditEvent;
use App\Models\IpAddress;
use App\Models\Subnet;
use App\Models\Vlan;
use App\Services\SiteContext;
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
        $siteContext = app(SiteContext::class);
        $activeSiteId = $siteContext->getActiveSiteId();
        $activeSite = $siteContext->getActiveSite();

        $subnetQuery = Subnet::query();
        $vlanQuery = Vlan::query();
        $ipQuery = IpAddress::query();

        if ($activeSiteId) {
            $subnetQuery->where('site_id', $activeSiteId);
            $vlanQuery->where(function ($q) use ($activeSiteId) {
                $q->where('site_id', $activeSiteId)->orWhereNull('site_id');
            });
            $ipQuery->whereHas('subnet', function ($q) use ($activeSiteId) {
                $q->where('site_id', $activeSiteId);
            });
        }

        $subnetCount = (clone $subnetQuery)->count();
        $ipv4SubnetCount = (clone $subnetQuery)->where('ip_version', 4)->count();
        $ipv6SubnetCount = (clone $subnetQuery)->where('ip_version', 6)->count();

        $vlanCount = $vlanQuery->count();

        $totalIpCount = (clone $ipQuery)->count();
        $assignedIpCount = (clone $ipQuery)->where('allocation_status', 'assigned')->count();
        $reservedIpCount = (clone $ipQuery)->where('allocation_status', 'reserved')->count();
        $quarantinedIpCount = (clone $ipQuery)->where('allocation_status', 'quarantined')->count();

        // Subnet Capacity Watchlist (Top 5 utilized subnets)
        $subnets = (clone $subnetQuery)->with('vlan')->take(50)->get();
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
            'activeSite' => $activeSite,
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
