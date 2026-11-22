<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\AddressSpace\AllocateIpAction;
use App\Actions\AddressSpace\CreateSubnetAction;
use App\Actions\AddressSpace\ReleaseIpAction;
use App\Actions\AddressSpace\ReserveIpAction;
use App\Actions\Layer2\CreateVlanAction;
use App\Actions\Site\CreateSiteAction;
use App\Domain\AddressSpace\Enums\HostType;
use App\Domain\AddressSpace\Enums\IpClassification;
use App\Domain\AddressSpace\Enums\SubnetType;
use App\Domain\Layer2\Enums\VlanSecurityZone;
use App\Domain\Security\Enums\UserRole;
use App\Models\AuditEvent;
use App\Models\IpAddress;
use App\Models\IpExclusion;
use App\Models\IpPool;
use App\Models\Site;
use App\Models\Subnet;
use App\Models\User;
use App\Models\Vlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('Password123!');

        // 1. Seed Enterprise Users
        $users = [
            [
                'name' => 'System Administrator',
                'email' => 'admin@kyamch.local',
                'password' => $password,
                'role' => UserRole::SUPER_ADMIN,
                'status' => 'active',
                'department' => 'IT Infrastructure & Security',
                'timezone' => 'Asia/Dhaka',
            ],
            [
                'name' => 'Network Administrator',
                'email' => 'netadmin@kyamch.local',
                'password' => $password,
                'role' => UserRole::NETWORK_ADMIN,
                'status' => 'active',
                'department' => 'Network Operations',
                'timezone' => 'Asia/Dhaka',
            ],
            [
                'name' => 'IP Operations Lead',
                'email' => 'operator@kyamch.local',
                'password' => $password,
                'role' => UserRole::OPERATOR,
                'status' => 'active',
                'department' => 'Systems & Helpdesk',
                'timezone' => 'Asia/Dhaka',
            ],
            [
                'name' => 'Clinical Infrastructure Viewer',
                'email' => 'viewer@kyamch.local',
                'password' => $password,
                'role' => UserRole::VIEWER,
                'status' => 'active',
                'department' => 'Clinical Engineering',
                'timezone' => 'Asia/Dhaka',
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        $admin = User::where('email', 'admin@kyamch.local')->first();

        // 2. Seed Campus Sites
        $siteAction = app(CreateSiteAction::class);
        $campusConfigs = [
            [
                'code' => 'KYAMCH-MAIN',
                'name' => 'KYAMCH Main Hospital Campus',
                'description' => 'Main hospital facility, inpatient wards, surgical suites, and ICU.',
                'status' => 'active',
                'is_default' => true,
            ],
            [
                'code' => 'KYAMCH-ACAD',
                'name' => 'Academic & Medical College',
                'description' => 'Medical college classrooms, lecture halls, laboratories, and faculty offices.',
                'status' => 'active',
                'is_default' => false,
            ],
            [
                'code' => 'KYAMCH-NURS',
                'name' => 'Nursing Institute & Hostels',
                'description' => 'Nursing institute campus, student hostels, and residential quarters.',
                'status' => 'active',
                'is_default' => false,
            ],
        ];

        foreach ($campusConfigs as $cfg) {
            if (!Site::where('code', $cfg['code'])->exists()) {
                $siteAction->execute($cfg);
            }
        }

        $mainSite = Site::where('code', 'KYAMCH-MAIN')->first();
        $acadSite = Site::where('code', 'KYAMCH-ACAD')->first();

        // 3. Seed 802.1Q VLANs
        $vlanAction = app(CreateVlanAction::class);

        $vlanConfigs = [
            ['site_id' => $mainSite?->id, 'vlan_id' => 10, 'name' => 'CORE-MANAGEMENT', 'security_zone' => VlanSecurityZone::MANAGEMENT, 'is_restricted' => false, 'description' => 'Out-of-band management and core switch control plane.'],
            ['site_id' => $mainSite?->id, 'vlan_id' => 20, 'name' => 'ICU-CLINICAL-DEVICES', 'security_zone' => VlanSecurityZone::CLINICAL, 'is_restricted' => true, 'description' => 'Clinical patient monitors, ventilators, and bedside telemetry.'],
            ['site_id' => $mainSite?->id, 'vlan_id' => 30, 'name' => 'PACS-RADIOLOGY', 'security_zone' => VlanSecurityZone::PACS, 'is_restricted' => false, 'description' => 'Picture archiving, MRI, CT, and DICOM imaging network.'],
            ['site_id' => $mainSite?->id, 'vlan_id' => 40, 'name' => 'DATACENTER-SERVERS', 'security_zone' => VlanSecurityZone::SERVERS, 'is_restricted' => false, 'description' => 'Hospital ERP, pharmacy, lab informatics, and EHR database cluster.'],
            ['site_id' => $mainSite?->id, 'vlan_id' => 50, 'name' => 'STAFF-WORKSTATIONS', 'security_zone' => VlanSecurityZone::WORKSTATIONS, 'is_restricted' => false, 'description' => 'Nursing stations, doctor consulting desks, and administrative PCs.'],
            ['site_id' => $mainSite?->id, 'vlan_id' => 60, 'name' => 'GUEST-PUBLIC-WIFI', 'security_zone' => VlanSecurityZone::GUEST, 'is_restricted' => false, 'description' => 'Patient waiting area and visitor public wireless network.'],
            ['site_id' => $acadSite?->id, 'vlan_id' => 100, 'name' => 'COLLEGE-LABS', 'security_zone' => VlanSecurityZone::INTERNAL, 'is_restricted' => false, 'description' => 'Medical student anatomy, biochemistry and computer labs.'],
        ];

        foreach ($vlanConfigs as $cfg) {
            if (! Vlan::where('vlan_id', $cfg['vlan_id'])->exists()) {
                $vlanAction->execute($cfg);
            }
        }

        $vlan10 = Vlan::where('vlan_id', 10)->first();
        $vlan20 = Vlan::where('vlan_id', 20)->first();
        $vlan30 = Vlan::where('vlan_id', 30)->first();

        // 4. Seed Subnet Hierarchy
        $subnetAction = app(CreateSubnetAction::class);

        // IPv4 Root Aggregate
        $rootSubnet = Subnet::where('cidr', '10.10.0.0/16')->first();
        if (! $rootSubnet) {
            $rootSubnet = $subnetAction->execute([
                'site_id' => $mainSite?->id,
                'name' => 'KYAMCH Campus IPv4 Aggregate',
                'cidr' => '10.10.0.0/16',
                'subnet_type' => SubnetType::AGGREGATE,
                'description' => 'Top-level enterprise aggregation container for hospital campus.',
                'allocation_enabled' => false,
            ]);
        }

        // Child Subnet 1: Management
        $mgmtSubnet = Subnet::where('cidr', '10.10.10.0/24')->first();
        if (! $mgmtSubnet) {
            $mgmtSubnet = $subnetAction->execute([
                'parent_id' => $rootSubnet->id,
                'vlan_id' => $vlan10?->id,
                'name' => 'Core Management Subnet',
                'cidr' => '10.10.10.0/24',
                'subnet_type' => SubnetType::ASSIGNABLE,
                'description' => 'OOB switch and router management interfaces.',
                'allocation_enabled' => true,
            ]);
        }

        // Child Subnet 2: ICU Clinical
        $icuSubnet = Subnet::where('cidr', '10.10.20.0/24')->first();
        if (! $icuSubnet) {
            $icuSubnet = $subnetAction->execute([
                'parent_id' => $rootSubnet->id,
                'vlan_id' => $vlan20?->id,
                'name' => 'ICU & Clinical Telemetry Subnet',
                'cidr' => '10.10.20.0/24',
                'subnet_type' => SubnetType::ASSIGNABLE,
                'description' => 'Direct patient monitoring and bedside telemetry devices.',
                'allocation_enabled' => true,
            ]);
        }

        // Child Subnet 3: PACS Radiology
        $pacsSubnet = Subnet::where('cidr', '10.10.30.0/24')->first();
        if (! $pacsSubnet) {
            $pacsSubnet = $subnetAction->execute([
                'parent_id' => $rootSubnet->id,
                'vlan_id' => $vlan30?->id,
                'name' => 'PACS Imaging Subnet',
                'cidr' => '10.10.30.0/24',
                'subnet_type' => SubnetType::ASSIGNABLE,
                'description' => 'Radiology modalities and DICOM image servers.',
                'allocation_enabled' => true,
            ]);
        }

        // IPv6 Root Aggregate
        $rootIpv6 = Subnet::where('cidr', '2001:db8:1000::/48')->first();
        if (! $rootIpv6) {
            $rootIpv6 = $subnetAction->execute([
                'site_id' => $mainSite?->id,
                'name' => 'KYAMCH Enterprise IPv6 Allocation',
                'cidr' => '2001:db8:1000::/48',
                'subnet_type' => SubnetType::AGGREGATE,
                'description' => 'Global IPv6 aggregate space.',
                'allocation_enabled' => false,
            ]);
        }

        // Academic Medical College Aggregate & Subnet
        if ($acadSite) {
            $acadRoot = Subnet::where('cidr', '10.20.0.0/16')->first();
            if (! $acadRoot) {
                $acadRoot = $subnetAction->execute([
                    'site_id' => $acadSite->id,
                    'name' => 'KYAMCH Medical College IPv4 Aggregate',
                    'cidr' => '10.20.0.0/16',
                    'subnet_type' => SubnetType::AGGREGATE,
                    'description' => 'Medical college campus academic network container.',
                    'allocation_enabled' => false,
                ]);

                $collegeLabVlan = Vlan::where('vlan_id', 100)->first();
                $subnetAction->execute([
                    'site_id' => $acadSite->id,
                    'parent_id' => $acadRoot->id,
                    'vlan_id' => $collegeLabVlan?->id,
                    'name' => 'Student Computer & Pathology Lab',
                    'cidr' => '10.20.10.0/24',
                    'subnet_type' => SubnetType::ASSIGNABLE,
                    'description' => 'Academic computing laboratory workstations.',
                    'allocation_enabled' => true,
                ]);
            }
        }

        // IPv6 Leaf Subnet
        $leafIpv6 = Subnet::where('cidr', '2001:db8:1000:10::/64')->first();
        if (! $leafIpv6) {
            $leafIpv6 = $subnetAction->execute([
                'parent_id' => $rootIpv6->id,
                'vlan_id' => $vlan10?->id,
                'name' => 'Core Management IPv6 Subnet',
                'cidr' => '2001:db8:1000:10::/64',
                'subnet_type' => SubnetType::ASSIGNABLE,
                'description' => 'Core infrastructure IPv6 addressing.',
                'allocation_enabled' => true,
            ]);
        }

        // 4. Seed Exclusions and Pools in ICU Subnet
        if ($icuSubnet) {
            // Static Gateway Exclusion (.1 to .10)
            IpExclusion::firstOrCreate(
                ['subnet_id' => $icuSubnet->id, 'start_address' => '10.10.20.1'],
                [
                    'end_address' => '10.10.20.10',
                    'start_hex' => '0000000000000000000000000a0a1401',
                    'end_hex' => '0000000000000000000000000a0a140a',
                    'reason' => 'Default gateway and HSRP VIPs reserved',
                    'status' => 'active',
                ]
            );

            // Static IP Pool (.11 to .100)
            IpPool::firstOrCreate(
                ['subnet_id' => $icuSubnet->id, 'name' => 'ICU Bedside Monitors Pool'],
                [
                    'pool_type' => 'static',
                    'start_address' => '10.10.20.11',
                    'end_address' => '10.10.20.100',
                    'start_hex' => '0000000000000000000000000a0a140b',
                    'end_hex' => '0000000000000000000000000a0a1464',
                ]
            );

            // 5. Allocate Sample IPs
            $allocateAction = app(AllocateIpAction::class);
            $releaseAction = app(ReleaseIpAction::class);
            $reserveAction = app(ReserveIpAction::class);

            if (! IpAddress::where('subnet_id', $icuSubnet->id)->where('address_text', '10.10.20.15')->exists()) {
                $allocateAction->execute([
                    'subnet_id' => $icuSubnet->id,
                    'ip_address' => '10.10.20.15',
                    'classification' => IpClassification::STATIC_HOST,
                    'host_type' => HostType::MEDICAL,
                    'hostname' => 'icu-mon-01.kyamch.local',
                    'mac_address' => '00:1A:2B:3C:4D:5E',
                    'assigned_to' => 'ICU Bed 1 Patient Monitor',
                    'description' => 'Philips IntelliVue bedside patient monitor in ICU Bed 1',
                ], $admin);
            } else {
                IpAddress::where('subnet_id', $icuSubnet->id)->where('address_text', '10.10.20.15')->update([
                    'host_type' => HostType::MEDICAL->value,
                ]);
            }

            if (! IpAddress::where('subnet_id', $icuSubnet->id)->where('address_text', '10.10.20.16')->exists()) {
                $allocateAction->execute([
                    'subnet_id' => $icuSubnet->id,
                    'ip_address' => '10.10.20.16',
                    'classification' => IpClassification::STATIC_HOST,
                    'host_type' => HostType::MEDICAL,
                    'hostname' => 'icu-mon-02.kyamch.local',
                    'mac_address' => '00:1A:2B:3C:4D:5F',
                    'assigned_to' => 'ICU Bed 2 Patient Monitor',
                    'description' => 'Philips IntelliVue bedside patient monitor in ICU Bed 2',
                ], $admin);
            } else {
                IpAddress::where('subnet_id', $icuSubnet->id)->where('address_text', '10.10.20.16')->update([
                    'host_type' => HostType::MEDICAL->value,
                ]);
            }

            if (! IpAddress::where('subnet_id', $icuSubnet->id)->where('address_text', '10.10.20.17')->exists()) {
                $allocateAction->execute([
                    'subnet_id' => $icuSubnet->id,
                    'ip_address' => '10.10.20.17',
                    'classification' => IpClassification::STATIC_HOST,
                    'host_type' => HostType::WORKSTATION,
                    'hostname' => 'icu-nurse-pc.kyamch.local',
                    'mac_address' => '00:1A:2B:3C:4D:60',
                    'assigned_to' => 'ICU Central Nursing Station PC',
                    'description' => 'Dell OptiPlex workstation for nursing shift staff',
                ], $admin);
            }

            if (! IpAddress::where('subnet_id', $icuSubnet->id)->where('address_text', '10.10.20.18')->exists()) {
                $allocateAction->execute([
                    'subnet_id' => $icuSubnet->id,
                    'ip_address' => '10.10.20.18',
                    'classification' => IpClassification::STATIC_HOST,
                    'host_type' => HostType::PRINTER,
                    'hostname' => 'icu-label-prn.kyamch.local',
                    'mac_address' => '00:1A:2B:3C:4D:61',
                    'assigned_to' => 'ICU Medical Label & Rx Printer',
                    'description' => 'Zebra barcode and wristband thermal printer',
                ], $admin);
            }

            // Seed a Quarantined address to demonstrate "Make Unassigned" action
            if (! IpAddress::where('subnet_id', $icuSubnet->id)->where('address_text', '10.10.20.19')->exists()) {
                $quarantineCandidate = $allocateAction->execute([
                    'subnet_id' => $icuSubnet->id,
                    'ip_address' => '10.10.20.19',
                    'classification' => IpClassification::STATIC_HOST,
                    'host_type' => HostType::MEDICAL,
                    'hostname' => 'icu-old-mon.kyamch.local',
                    'mac_address' => '00:1A:2B:3C:4D:99',
                    'assigned_to' => 'Decommissioned ICU Monitor 9',
                    'description' => 'Decommissioned bedside telemetry hardware',
                ], $admin);
                $releaseAction->execute($quarantineCandidate, 'Decommissioned hardware; 5-day quarantine cooling period.', 5, $admin);
            }

            if (! IpAddress::where('subnet_id', $icuSubnet->id)->where('address_text', '10.10.20.25')->exists()) {
                $reserveAction->execute([
                    'subnet_id' => $icuSubnet->id,
                    'ip_address' => '10.10.20.25',
                    'reason' => 'Reserved for upcoming Dialysis Telemetry Gateway',
                    'expires_at' => now()->addDays(30)->toDateString(),
                ], $admin);
            }
        }

        AuditEvent::record(
            eventType: 'system.seeded',
            reason: 'Streamlined IPAM Core initialized with Subnets, VLANs, and IP allocations.',
            actor: $admin
        );
    }
}
