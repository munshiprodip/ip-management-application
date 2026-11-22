<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\AddressSpace\AllocateIpAction;
use App\Actions\AddressSpace\CreateSubnetAction;
use App\Actions\AddressSpace\ReserveIpAction;
use App\Actions\Layer2\CreateVlanAction;
use App\Domain\AddressSpace\Enums\IpClassification;
use App\Domain\AddressSpace\Enums\SubnetType;
use App\Domain\Layer2\Enums\VlanSecurityZone;
use App\Domain\Security\Enums\UserRole;
use App\Models\AuditEvent;
use App\Models\IpExclusion;
use App\Models\IpPool;
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

        // 2. Seed 802.1Q VLANs
        $vlanAction = app(CreateVlanAction::class);

        $vlanConfigs = [
            ['vlan_id' => 10, 'name' => 'CORE-MANAGEMENT', 'security_zone' => VlanSecurityZone::MANAGEMENT, 'is_restricted' => false, 'description' => 'Out-of-band management and core switch control plane.'],
            ['vlan_id' => 20, 'name' => 'ICU-CLINICAL-DEVICES', 'security_zone' => VlanSecurityZone::CLINICAL, 'is_restricted' => true, 'description' => 'Clinical patient monitors, ventilators, and bedside telemetry.'],
            ['vlan_id' => 30, 'name' => 'PACS-RADIOLOGY', 'security_zone' => VlanSecurityZone::PACS, 'is_restricted' => false, 'description' => 'Picture archiving, MRI, CT, and DICOM imaging network.'],
            ['vlan_id' => 40, 'name' => 'DATACENTER-SERVERS', 'security_zone' => VlanSecurityZone::SERVERS, 'is_restricted' => false, 'description' => 'Hospital ERP, pharmacy, lab informatics, and EHR database cluster.'],
            ['vlan_id' => 50, 'name' => 'STAFF-WORKSTATIONS', 'security_zone' => VlanSecurityZone::WORKSTATIONS, 'is_restricted' => false, 'description' => 'Nursing stations, doctor consulting desks, and administrative PCs.'],
            ['vlan_id' => 60, 'name' => 'GUEST-PUBLIC-WIFI', 'security_zone' => VlanSecurityZone::GUEST, 'is_restricted' => false, 'description' => 'Patient waiting area and visitor public wireless network.'],
        ];

        foreach ($vlanConfigs as $cfg) {
            if (! Vlan::where('vlan_id', $cfg['vlan_id'])->exists()) {
                $vlanAction->execute($cfg);
            }
        }

        $vlan10 = Vlan::where('vlan_id', 10)->first();
        $vlan20 = Vlan::where('vlan_id', 20)->first();
        $vlan30 = Vlan::where('vlan_id', 30)->first();

        // 3. Seed Subnet Hierarchy
        $subnetAction = app(CreateSubnetAction::class);

        // IPv4 Root Aggregate
        $rootSubnet = Subnet::where('cidr', '10.10.0.0/16')->first();
        if (! $rootSubnet) {
            $rootSubnet = $subnetAction->execute([
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
                'name' => 'KYAMCH Enterprise IPv6 Allocation',
                'cidr' => '2001:db8:1000::/48',
                'subnet_type' => SubnetType::AGGREGATE,
                'description' => 'Global IPv6 aggregate space.',
                'allocation_enabled' => false,
            ]);
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
            $reserveAction = app(ReserveIpAction::class);

            $allocateAction->execute([
                'subnet_id' => $icuSubnet->id,
                'ip_address' => '10.10.20.15',
                'classification' => IpClassification::STATIC_HOST,
                'hostname' => 'icu-mon-01.kyamch.local',
                'mac_address' => '00:1A:2B:3C:4D:5E',
                'assigned_to' => 'ICU Bed 1 Patient Monitor',
                'description' => 'Philips IntelliVue bedside patient monitor in ICU Bed 1',
            ], $admin);

            $allocateAction->execute([
                'subnet_id' => $icuSubnet->id,
                'ip_address' => '10.10.20.16',
                'classification' => IpClassification::STATIC_HOST,
                'hostname' => 'icu-mon-02.kyamch.local',
                'mac_address' => '00:1A:2B:3C:4D:5F',
                'assigned_to' => 'ICU Bed 2 Patient Monitor',
                'description' => 'Philips IntelliVue bedside patient monitor in ICU Bed 2',
            ], $admin);

            $reserveAction->execute([
                'subnet_id' => $icuSubnet->id,
                'ip_address' => '10.10.20.25',
                'reason' => 'Reserved for upcoming Dialysis Telemetry Gateway',
                'expires_at' => now()->addDays(30)->toDateString(),
            ], $admin);
        }

        AuditEvent::record(
            eventType: 'system.seeded',
            reason: 'Streamlined IPAM Core initialized with Subnets, VLANs, and IP allocations.',
            actor: $admin
        );
    }
}
