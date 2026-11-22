<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\AddressSpace\Enums\IpAllocationStatus;
use App\Domain\AddressSpace\Enums\IpConnectivityStatus;
use App\Domain\Discovery\Enums\DiscoverySourceType;
use App\Domain\Discovery\Enums\FindingSeverity;
use App\Domain\Discovery\Enums\FindingStatus;
use App\Domain\Discovery\Enums\FindingType;
use App\Domain\Discovery\Enums\JobStatus;
use App\Models\DiscoveryAgent;
use App\Models\DiscoveryJob;
use App\Models\IpAddress;
use App\Models\IpObservation;
use App\Models\ReconciliationFinding;
use App\Models\Site;
use App\Models\Subnet;
use App\Models\User;
use App\Models\Vrf;
use Illuminate\Database\Seeder;

class Stage6Seeder extends Seeder
{
    /**
     * Run the database seeds for Stage 6.
     */
    public function run(): void
    {
        $site = Site::first();
        $admin = User::first();
        $vrf = Vrf::firstOrCreate(['code' => 'VRF-GLOBAL']);

        // 1. Create Zone Discovery Agent
        $agent = DiscoveryAgent::updateOrCreate(
            ['code' => 'AGENT-CORE-01'],
            [
                'site_id' => $site->id,
                'name' => 'Main Campus Core Discovery Agent',
                'identity_ref' => 'sha256:7f83b1657ff1fc53b92dc18148a1d65dfc2d4b1fa3d677284addd200126d9069',
                'enabled' => true,
                'rate_limit_pps' => 50,
                'last_seen_at' => now(),
            ]
        );

        $radSubnet = Subnet::where('cidr', '10.10.10.0/24')->first();
        $icuSubnet = Subnet::where('cidr', '10.10.20.0/24')->first();

        if ($radSubnet !== null) {
            // 2. Completed Discovery Job on Radiology Subnet
            $job1 = DiscoveryJob::create([
                'agent_id' => $agent->id,
                'subnet_id' => $radSubnet->id,
                'requested_by' => $admin->id,
                'source_type' => DiscoverySourceType::ARP,
                'status' => JobStatus::COMPLETED,
                'started_at' => now()->subHours(2),
                'finished_at' => now()->subHours(2)->addSeconds(45),
                'metrics_json' => [
                    'observations_count' => 3,
                    'duration_ms' => 45000,
                ],
            ]);

            // Observation 1: Known CT scanner
            IpObservation::create([
                'discovery_job_id' => $job1->id,
                'vrf_id' => $vrf->id,
                'subnet_id' => $radSubnet->id,
                'ip_version' => 4,
                'address_hex' => '0a0a0a0a',
                'address_text' => '10.10.10.10',
                'observed_mac' => '00:1A:2B:3C:4D:5E',
                'hostname' => 'ky-rad-ct01.kyamch.local',
                'source_type' => DiscoverySourceType::ARP,
                'confidence' => 95,
                'observed_at' => now()->subHours(2),
            ]);

            // Observation 2: Rogue / Unmanaged Device (10.10.10.99)
            IpObservation::create([
                'discovery_job_id' => $job1->id,
                'vrf_id' => $vrf->id,
                'subnet_id' => $radSubnet->id,
                'ip_version' => 4,
                'address_hex' => '0a0a0a63',
                'address_text' => '10.10.10.99',
                'observed_mac' => 'bc:24:11:99:88:77',
                'hostname' => 'unauthorized-laptop.local',
                'source_type' => DiscoverySourceType::ARP,
                'confidence' => 90,
                'observed_at' => now()->subHours(2),
            ]);

            // Finding 1: Unmanaged Active IP (T-018: never automatically allocated)
            ReconciliationFinding::firstOrCreate(
                [
                    'subnet_id' => $radSubnet->id,
                    'address_text' => '10.10.10.99',
                    'finding_type' => FindingType::UNKNOWN_ACTIVE_IP->value,
                ],
                [
                    'vrf_id' => $vrf->id,
                    'severity' => FindingSeverity::CRITICAL,
                    'status' => FindingStatus::OPEN,
                    'title' => 'Unmanaged Active IP Discovered: 10.10.10.99',
                    'details' => 'Active ARP responder on 10.10.10.99 (MAC: bc:24:11:99:88:77) with no authoritative allocation.',
                    'observed_mac' => 'bc:24:11:99:88:77',
                    'authoritative_mac' => null,
                    'evidence_refs_json' => ['discovery_job_id' => $job1->id, 'source' => 'arp'],
                ]
            );
        }

        // 3. Invariant T-020 verification: Silent device on ICU
        // We ensure KY-ICU-MON01 is marked observed_offline, but its allocation status remains strictly assigned
        if ($icuSubnet !== null) {
            $monIp = IpAddress::where('subnet_id', $icuSubnet->id)
                ->where('allocation_status', IpAllocationStatus::ASSIGNED)
                ->first();

            if ($monIp !== null) {
                $monIp->update([
                    'connectivity_status' => IpConnectivityStatus::OBSERVED_OFFLINE,
                    'last_reconciled_at' => now()->subMinutes(30),
                ]);
            }
        }
    }
}
