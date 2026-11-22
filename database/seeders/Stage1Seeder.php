<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\AddressSpace\Enums\VrfStatus;
use App\Domain\Layer2\Enums\VlanSecurityZone;
use App\Domain\Layer2\Enums\VlanStatus;
use App\Models\AuditEvent;
use App\Models\Department;
use App\Models\L2Domain;
use App\Models\Organization;
use App\Models\Site;
use App\Models\Vlan;
use App\Models\Vrf;
use Illuminate\Database\Seeder;

class Stage1Seeder extends Seeder
{
    public function run(): void
    {
        // 1. Primary Organization
        $org = Organization::firstOrCreate(
            ['code' => 'KYAMCH'],
            [
                'name' => 'Khwaja Yunus Ali Medical College & Hospital',
                'slug' => 'kyamch',
                'status' => 'active',
            ]
        );

        // 2. Sites / Campuses (Fictional test fixtures for local development)
        $mainSite = Site::firstOrCreate(
            ['organization_id' => $org->id, 'code' => 'MAIN-CAMPUS'],
            [
                'name' => 'KYAMCH Main Hospital Campus',
                'description' => 'Enayetpur, Sirajganj Central Hospital Facility',
                'timezone' => 'Asia/Dhaka',
                'status' => 'active',
            ]
        );

        $acadSite = Site::firstOrCreate(
            ['organization_id' => $org->id, 'code' => 'ACADEMIC'],
            [
                'name' => 'Medical College & Academic Complex',
                'description' => 'Academic buildings, auditoriums and student hostels',
                'timezone' => 'Asia/Dhaka',
                'status' => 'active',
            ]
        );

        // 3. Departments
        Department::firstOrCreate(
            ['organization_id' => $org->id, 'code' => 'IT-NET'],
            [
                'name' => 'IT & Network Operations',
                'contact_email' => 'netops@kyamch.local',
                'status' => 'active',
            ]
        );

        Department::firstOrCreate(
            ['organization_id' => $org->id, 'code' => 'BIOMED'],
            [
                'name' => 'Biomedical & Clinical Engineering',
                'contact_email' => 'biomed@kyamch.local',
                'status' => 'active',
            ]
        );

        Department::firstOrCreate(
            ['organization_id' => $org->id, 'code' => 'RADIOLOGY'],
            [
                'name' => 'Radiology & PACS Imaging',
                'contact_email' => 'radiology@kyamch.local',
                'status' => 'active',
            ]
        );

        // 4. Mandatory Default VRF (Enforce Non-Null Default)
        $defaultVrf = Vrf::firstOrCreate(
            ['is_default' => true],
            [
                'code' => config('ipam.default_vrf_code', 'VRF-GLOBAL'),
                'name' => config('ipam.default_vrf_name', 'Default VRF'),
                'description' => 'Authoritative default routing domain for all general hospital and campus infrastructure.',
                'status' => VrfStatus::ACTIVE,
            ]
        );

        // Additional VRFs for Multi-VRF Routing Isolation
        $clinicalVrf = Vrf::firstOrCreate(
            ['code' => 'VRF-CLINICAL'],
            [
                'name' => 'Biomedical & Patient Care Network',
                'description' => 'Strictly isolated VRF for intensive care monitors, dialysis, and patient instruments.',
                'route_distinguisher' => '65001:100',
                'site_id' => $mainSite->id,
                'is_default' => false,
                'status' => VrfStatus::ACTIVE,
            ]
        );

        $pacsVrf = Vrf::firstOrCreate(
            ['code' => 'VRF-PACS'],
            [
                'name' => 'PACS & Medical Imaging Fabric',
                'description' => 'High-throughput imaging fabric for MRI, CT scans and DICOM archives.',
                'route_distinguisher' => '65001:200',
                'site_id' => $mainSite->id,
                'is_default' => false,
                'status' => VrfStatus::ACTIVE,
            ]
        );

        // 5. Layer 2 Domains
        $mainL2 = L2Domain::firstOrCreate(
            ['site_id' => $mainSite->id, 'code' => 'L2-MAIN-CORE'],
            [
                'name' => 'Main Hospital Core Switching Fabric',
                'description' => 'Campus redundant spine-leaf architecture',
                'status' => 'active',
            ]
        );

        $acadL2 = L2Domain::firstOrCreate(
            ['site_id' => $acadSite->id, 'code' => 'L2-ACAD-DIST'],
            [
                'name' => 'Academic Campus Distribution Fabric',
                'description' => 'College campus and residential distribution network',
                'status' => 'active',
            ]
        );

        // 6. VLANs (Demonstrating distinct security zones and cross-domain VLAN reuse)
        Vlan::firstOrCreate(
            ['l2_domain_id' => $mainL2->id, 'vlan_id' => 10],
            [
                'name' => 'Hospital Out-of-Band Management',
                'description' => 'Switch and server management interfaces',
                'security_zone' => VlanSecurityZone::MANAGEMENT,
                'status' => VlanStatus::ACTIVE,
            ]
        );

        Vlan::firstOrCreate(
            ['l2_domain_id' => $mainL2->id, 'vlan_id' => 20],
            [
                'name' => 'ICU & CCU Bedside Patient Monitors',
                'description' => 'Critical life-support monitoring (Strictly protected)',
                'security_zone' => VlanSecurityZone::CLINICAL,
                'status' => VlanStatus::ACTIVE,
            ]
        );

        Vlan::firstOrCreate(
            ['l2_domain_id' => $mainL2->id, 'vlan_id' => 30],
            [
                'name' => 'Radiology & PACS Modalities',
                'description' => 'CT/MRI imaging modalities and DICOM stores',
                'security_zone' => VlanSecurityZone::PACS,
                'status' => VlanStatus::ACTIVE,
            ]
        );

        // Reusing VLAN ID 10 in a separate L2 domain (Valid under invariant)
        Vlan::firstOrCreate(
            ['l2_domain_id' => $acadL2->id, 'vlan_id' => 10],
            [
                'name' => 'Academic Switch Management',
                'description' => 'Independent academic building switch management',
                'security_zone' => VlanSecurityZone::MANAGEMENT,
                'status' => VlanStatus::ACTIVE,
            ]
        );

        AuditEvent::record(
            eventType: 'stage1.seeded',
            reason: 'Seeded Stage 1 baseline: Organization, Sites, Default VRF, L2 Domains and VLANs'
        );
    }
}

