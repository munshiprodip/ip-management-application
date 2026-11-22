<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Alert;
use App\Models\DiscoveryAgent;
use App\Models\ExportJob;
use App\Models\IpAddress;
use App\Models\Subnet;
use App\Models\Vrf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class IpamHealthCheckCommand extends Command
{
    protected $signature = 'ipam:health-check';
    protected $description = 'Executes comprehensive enterprise NOC health diagnostics on KYAMCH IPAM';

    public function handle(): int
    {
        $this->info('====================================================');
        $this->info('  KYAMCH Smart IPAM — Enterprise Health Diagnostics');
        $this->info('====================================================');

        // 1. Database Connectivity
        try {
            DB::connection()->getPdo();
            $dbStatus = '<info>OK (' . config('database.default') . ')</info>';
        } catch (\Throwable $e) {
            $dbStatus = '<error>FAILED: ' . $e->getMessage() . '</error>';
        }

        // 2. Safety Posture Flags
        $discoveryFlag = config('ipam.discovery_enabled')
            ? '<comment>ENABLED (ACTIVE PROBING)</comment>'
            : '<info>SAFE (DISABLED)</info>';

        $writesFlag = config('ipam.external_writes_enabled')
            ? '<error>ENABLED (ACTIVE WRITES)</error>'
            : '<info>SAFE (READ-ONLY)</info>';

        // 3. Operational Counts
        $vrfCount = Vrf::count();
        $subnetCount = Subnet::count();
        $ipCount = IpAddress::count();
        $openAlerts = Alert::open()->count();
        $criticalAlerts = Alert::open()->critical()->count();

        // 4. Stale Agents
        $staleAgents = DiscoveryAgent::stale()->count();

        // 5. Expired Exports
        $expiredExports = ExportJob::expired()->count();

        // Display Table
        $this->table(
            ['Diagnostic Component', 'Status / Metric', 'Enterprise Threshold'],
            [
                ['Database Engine', $dbStatus, 'Online (InnoDB / SQLite)'],
                ['Network Discovery Posture', $discoveryFlag, 'Disabled by default'],
                ['External Network Writes', $writesFlag, 'Locked (Read-only)'],
                ['Configured VRF Contexts', $vrfCount, '>= 1 (Mandatory Default)'],
                ['Managed Subnets', $subnetCount, 'Non-empty hierarchy'],
                ['Allocated IP Addresses', $ipCount, 'Authoritative state'],
                ['Open Alerts (Triage)', $openAlerts, 'Operational threshold'],
                ['Critical Incidents', $criticalAlerts > 0 ? "<error>{$criticalAlerts}</error>" : '<info>0</info>', '0 P0 Incidents'],
                ['Stale Discovery Agents', $staleAgents > 0 ? "<comment>{$staleAgents}</comment>" : '<info>0</info>', '0 Agents > 15m Heartbeat'],
                ['Expired Temp Exports', $expiredExports > 0 ? "<comment>{$expiredExports}</comment>" : '<info>0</info>', 'Purged <= 24h retention'],
            ]
        );

        $this->info('Diagnostic completed successfully.');

        return 0;
    }
}
