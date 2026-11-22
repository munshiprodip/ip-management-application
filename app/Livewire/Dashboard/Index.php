<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Operations Dashboard - KYAMCH IPAM')]
class Index extends Component
{
    public function render()
    {
        // Compute genuine database statistics (no mock pre-filled KPI values)
        $userCount = User::count();
        $auditCount = AuditEvent::count();

        // Check if database tables from later stages exist yet or are pending migration
        $hasVrfsTable = DB::getSchemaBuilder()->hasTable('vrfs');
        $vrfCount = $hasVrfsTable ? DB::table('vrfs')->count() : 0;

        $hasSubnetsTable = DB::getSchemaBuilder()->hasTable('subnets');
        $subnetCount = $hasSubnetsTable ? DB::table('subnets')->count() : 0;

        $hasIpAddressesTable = DB::getSchemaBuilder()->hasTable('ip_addresses');
        $ipCount = $hasIpAddressesTable ? DB::table('ip_addresses')->count() : 0;

        $hasDevicesTable = DB::getSchemaBuilder()->hasTable('devices');
        $deviceCount = $hasDevicesTable ? DB::table('devices')->count() : 0;

        $recentAudits = AuditEvent::recent(8)->get();

        return view('livewire.dashboard.index', [
            'userCount' => $userCount,
            'auditCount' => $auditCount,
            'vrfCount' => $vrfCount,
            'subnetCount' => $subnetCount,
            'ipCount' => $ipCount,
            'deviceCount' => $deviceCount,
            'hasVrfsTable' => $hasVrfsTable,
            'hasSubnetsTable' => $hasSubnetsTable,
            'hasIpAddressesTable' => $hasIpAddressesTable,
            'hasDevicesTable' => $hasDevicesTable,
            'recentAudits' => $recentAudits,
            'discoveryEnabled' => config('ipam.discovery_enabled'),
            'writesEnabled' => config('ipam.external_writes_enabled'),
            'databaseDriver' => config('database.default'),
            'defaultTimezone' => config('ipam.default_timezone'),
            'quarantineDays' => config('ipam.quarantine_duration_days'),
        ]);
    }
}

