<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\AuditEvent;
use App\Models\HostType;
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

        // Host Type Classification Statistics (Assigned Quantities)
        $hostTypes = HostType::where('is_active', true)->orderBy('name')->get();
        $assignedIpByHostType = (clone $ipQuery)
            ->where('allocation_status', 'assigned')
            ->whereNotNull('host_type')
            ->selectRaw('host_type, count(*) as total_count')
            ->groupBy('host_type')
            ->pluck('total_count', 'host_type')
            ->all();

        $unclassifiedAssignedCount = (clone $ipQuery)
            ->where('allocation_status', 'assigned')
            ->where(function ($q) {
                $q->whereNull('host_type')->orWhere('host_type', '');
            })
            ->count();

        $hostTypeStats = $hostTypes->map(function (HostType $ht) use ($assignedIpByHostType, $assignedIpCount) {
            $count = (int) ($assignedIpByHostType[$ht->slug] ?? 0);
            $percentage = $assignedIpCount > 0 ? round(($count / $assignedIpCount) * 100, 1) : 0.0;

            return [
                'id' => $ht->id,
                'name' => $ht->name,
                'slug' => $ht->slug,
                'description' => $ht->description,
                'color' => $ht->color,
                'badgeClasses' => $ht->badgeClasses(),
                'assignedCount' => $count,
                'percentage' => $percentage,
            ];
        });

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
            'hostTypeStats' => $hostTypeStats,
            'unclassifiedAssignedCount' => $unclassifiedAssignedCount,
            'capacityWatchlist' => $capacityWatchlist,
            'recentAudits' => $recentAudits,
            'defaultTimezone' => config('ipam.default_timezone', 'Asia/Dhaka'),
            'databaseDriver' => config('database.default'),
        ]);
    }
}
