<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\AddressSpace\CreateSubnetAction;
use App\Domain\AddressSpace\Enums\SubnetStatus;
use App\Domain\AddressSpace\Enums\SubnetType;
use App\Models\Site;
use App\Models\Subnet;
use App\Models\Vlan;
use App\Models\Vrf;
use Illuminate\Database\Seeder;

class Stage2Seeder extends Seeder
{
    public function run(): void
    {
        $globalVrf = Vrf::where('code', 'VRF-GLOBAL')->first();
        $clinicalVrf = Vrf::where('code', 'VRF-CLINICAL')->first();
        $mainSite = Site::where('code', 'MAIN-CAMPUS')->first();

        if (! $globalVrf) {
            return;
        }

        $createSubnet = app(CreateSubnetAction::class);

        // 1. Campus Aggregate Container (10.10.0.0/16)
        if (! Subnet::where('vrf_id', $globalVrf->id)->where('cidr', '10.10.0.0/16')->exists()) {
            $campusContainer = $createSubnet->execute([
                'vrf_id' => $globalVrf->id,
                'site_id' => $mainSite?->id,
                'cidr' => '10.10.0.0/16',
                'name' => 'KYAMCH Main Campus IP Aggregate',
                'description' => 'Top-level 10.10.0.0/16 aggregate allocation for Sirajganj hospital facilities.',
                'subnet_type' => SubnetType::AGGREGATE->value,
                'status' => SubnetStatus::ACTIVE->value,
                'allocation_enabled' => false,
            ]);

            // 2. Hospital Core Block (10.10.0.0/19: covers 10.10.0.0 to 10.10.31.255)
            $coreBlock = $createSubnet->execute([
                'vrf_id' => $globalVrf->id,
                'parent_id' => $campusContainer->id,
                'site_id' => $mainSite?->id,
                'cidr' => '10.10.0.0/19',
                'name' => 'Hospital Core & Clinical Block',
                'description' => 'Intermediate aggregate for critical inpatient, ICU, and surgical subnets.',
                'subnet_type' => SubnetType::AGGREGATE->value,
                'status' => SubnetStatus::ACTIVE->value,
                'allocation_enabled' => false,
            ]);

            // Find VLAN 10 (Radiology)
            $vlan10 = Vlan::where('vlan_id', 10)->first();
            $createSubnet->execute([
                'vrf_id' => $globalVrf->id,
                'parent_id' => $coreBlock->id,
                'site_id' => $mainSite?->id,
                'vlan_id' => $vlan10?->id,
                'cidr' => '10.10.10.0/24',
                'name' => 'Radiology & Imaging Modality Subnet',
                'description' => 'Assignable subnet for CT scanners, MRI suites, and PACS digital storage.',
                'subnet_type' => SubnetType::ASSIGNABLE->value,
                'status' => SubnetStatus::ACTIVE->value,
                'allocation_enabled' => true,
            ]);

            // Find VLAN 20 (Cardiology ICU)
            $vlan20 = Vlan::where('vlan_id', 20)->first();
            $createSubnet->execute([
                'vrf_id' => $globalVrf->id,
                'parent_id' => $coreBlock->id,
                'site_id' => $mainSite?->id,
                'vlan_id' => $vlan20?->id,
                'cidr' => '10.10.20.0/24',
                'name' => 'Cardiology ICU & Cath Lab Subnet',
                'description' => 'Physiological monitors and cardiac telemetry equipment.',
                'subnet_type' => SubnetType::ASSIGNABLE->value,
                'status' => SubnetStatus::ACTIVE->value,
                'allocation_enabled' => true,
            ]);

            // Find VLAN 30 (Operating Theatres)
            $vlan30 = Vlan::where('vlan_id', 30)->first();
            $createSubnet->execute([
                'vrf_id' => $globalVrf->id,
                'parent_id' => $coreBlock->id,
                'site_id' => $mainSite?->id,
                'vlan_id' => $vlan30?->id,
                'cidr' => '10.10.30.0/24',
                'name' => 'Operating Theatre Complex Subnet',
                'description' => 'Anesthesia machines, surgical robotics, and OT telemetry.',
                'subnet_type' => SubnetType::ASSIGNABLE->value,
                'status' => SubnetStatus::ACTIVE->value,
                'allocation_enabled' => true,
            ]);

            // Point-to-Point core uplink /31 (RFC 3021)
            $createSubnet->execute([
                'vrf_id' => $globalVrf->id,
                'parent_id' => $campusContainer->id,
                'site_id' => $mainSite?->id,
                'cidr' => '10.10.254.0/31',
                'name' => 'Core-SW1 to FireWall-1 Uplink',
                'description' => 'RFC 3021 point-to-point /31 inter-chassis routed link.',
                'subnet_type' => SubnetType::POINT_TO_POINT->value,
                'status' => SubnetStatus::ACTIVE->value,
                'allocation_enabled' => true,
            ]);
        }

        // 3. Isolated Clinical VRF subnets (demonstrating VRF isolation and address reuse)
        if ($clinicalVrf && ! Subnet::where('vrf_id', $clinicalVrf->id)->where('cidr', '10.10.10.0/24')->exists()) {
            $createSubnet->execute([
                'vrf_id' => $clinicalVrf->id,
                'site_id' => $mainSite?->id,
                'cidr' => '10.10.10.0/24',
                'name' => 'Clinical Air-Gapped Diagnostic Subnet',
                'description' => 'Isolated diagnostic modality subnet inside VRF-CLINICAL with no external default route.',
                'subnet_type' => SubnetType::ASSIGNABLE->value,
                'status' => SubnetStatus::ACTIVE->value,
                'allocation_enabled' => true,
            ]);
        }

        // 4. IPv6 Subnet (fd00:65000:1::/64)
        if (! Subnet::where('vrf_id', $globalVrf->id)->where('cidr', 'fd00:6500:1::/64')->exists()) {
            $createSubnet->execute([
                'vrf_id' => $globalVrf->id,
                'site_id' => $mainSite?->id,
                'cidr' => 'fd00:6500:1::/64',
                'name' => 'Campus IPv6 Core Management',
                'description' => 'Campus infrastructure IPv6 management subnet.',
                'subnet_type' => SubnetType::ASSIGNABLE->value,
                'status' => SubnetStatus::ACTIVE->value,
                'allocation_enabled' => true,
            ]);
        }
    }
}
