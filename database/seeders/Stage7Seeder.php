<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Alerts\Enums\AlertRuleCode;
use App\Domain\Alerts\Enums\AlertSeverity;
use App\Domain\Alerts\Enums\ExportType;
use App\Models\Alert;
use App\Models\DiscoveryAgent;
use App\Models\ReconciliationFinding;
use App\Models\Subnet;
use App\Models\User;
use App\Services\Alerting\AlertDispatcherService;
use App\Services\Reporting\ReportExportService;
use Illuminate\Database\Seeder;

class Stage7Seeder extends Seeder
{
    public function run(): void
    {
        $alertService = app(AlertDispatcherService::class);
        $exportService = app(ReportExportService::class);

        $admin = User::where('email', 'admin@kyamch.local')->first();

        // 1. Seed Subnet Exhaustion Alert
        $subnet = Subnet::first();
        if ($subnet) {
            $alertService->dispatchAlert([
                'rule_code' => AlertRuleCode::SUBNET_EXHAUSTION,
                'dedupe_key' => sprintf('SUBNET_EXHAUSTION:subnet_%d', $subnet->id),
                'severity' => AlertSeverity::WARNING,
                'title' => sprintf('High Subnet Capacity: %s at 85.0%% utilization', $subnet->cidr),
                'description' => sprintf(
                    'Subnet %s (%s) has exceeded the 80%% allocation threshold. Immediate review recommended.',
                    $subnet->cidr,
                    $subnet->name
                ),
                'context' => [
                    'subnet_id' => $subnet->id,
                    'cidr' => $subnet->cidr,
                    'utilization_pct' => 85.0,
                ],
            ]);
        }

        // 2. Seed Finding-based Alert (Rogue IP)
        $finding = ReconciliationFinding::first();
        if ($finding) {
            $alertService->evaluateFromFinding($finding);
        } else {
            $alertService->dispatchAlert([
                'rule_code' => AlertRuleCode::ROGUE_IP,
                'dedupe_key' => 'FINDING:unknown_active_ip:10.10.10.99',
                'severity' => AlertSeverity::CRITICAL,
                'title' => 'Rogue IP Detected: 10.10.10.99 active without IPAM record',
                'description' => 'Discovered live ARP response from MAC 00:1A:2B:3C:4D:99 on interface Gi0/1 without authoritative IP assignment.',
                'context' => [
                    'address_text' => '10.10.10.99',
                    'observed_mac' => '00:1A:2B:3C:4D:99',
                ],
            ]);
        }

        // 3. Seed Agent Offline Alert (T-028)
        $staleAgent = DiscoveryAgent::first();
        $agentName = $staleAgent ? $staleAgent->name : 'HQ Zone Collector Agent';
        $alertService->dispatchAlert([
            'rule_code' => AlertRuleCode::AGENT_OFFLINE,
            'dedupe_key' => 'AGENT:offline:hq_zone_collector',
            'severity' => AlertSeverity::WARNING,
            'title' => sprintf('Collector Agent Stale: %s heartbeat missing (>15m)', $agentName),
            'description' => sprintf('Agent [%s] has missed scheduled heartbeats. Observations marked stale; authoritative allocations unchanged (T-028).', $agentName),
            'context' => [
                'agent_name' => $agentName,
                'last_heartbeat_at' => now()->subMinutes(25)->toIso8601String(),
            ],
        ]);

        // 4. Seed a Sample Completed Export Job
        if ($admin) {
            $exportService->generateExport($admin, ExportType::SUBNETS, []);
        }
    }
}
