<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\AddressSpace\CreateSubnetAction;
use App\Actions\Layer2\CreateVlanAction;
use App\Actions\Site\CreateSiteAction;
use App\Domain\AddressSpace\Enums\SubnetStatus;
use App\Domain\AddressSpace\Enums\SubnetType;
use App\Domain\AddressSpace\ValueObjects\IpPrefix;
use App\Domain\Layer2\Enums\VlanSecurityZone;
use App\Domain\Security\Enums\UserRole;
use App\Models\AuditEvent;
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

        // 3. Seed MikroTik Active / Enabled 802.1Q VLANs (Excluding Disabled VLANs)
        $vlanAction = app(CreateVlanAction::class);

        $enabledVlans = [
            // Screenshot 1
            [
                'vlan_id' => 2,
                'name' => '2.01-SW-MGMT-VLAN-2',
                'security_zone' => VlanSecurityZone::MANAGEMENT,
                'is_restricted' => false,
                'description' => 'Core switch management VLAN. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 3,
                'name' => 'KYAU-DATA Link - P2P_VLAN3',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'KYAU point-to-point data link. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 4,
                'name' => '3.0-Hotspot - Gateway - 4',
                'security_zone' => VlanSecurityZone::GUEST,
                'is_restricted' => false,
                'description' => 'Hotspot gateway. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 101,
                'name' => 'VLAN-101-IDRAC',
                'security_zone' => VlanSecurityZone::MANAGEMENT,
                'is_restricted' => false,
                'description' => 'IDRAC Network out-of-band server management. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 102,
                'name' => 'VLAN-102-VMware',
                'security_zone' => VlanSecurityZone::SERVERS,
                'is_restricted' => false,
                'description' => 'VMware Network hypervisor hosts. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 103,
                'name' => 'VLAN-103-NAS',
                'security_zone' => VlanSecurityZone::SERVERS,
                'is_restricted' => false,
                'description' => 'NAS Network storage cluster. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 106,
                'name' => 'VLAN-106-Veem',
                'security_zone' => VlanSecurityZone::SERVERS,
                'is_restricted' => false,
                'description' => 'Veem Network backup repository. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 200,
                'name' => 'vlan_200_OPD',
                'security_zone' => VlanSecurityZone::CLINICAL,
                'is_restricted' => false,
                'description' => 'Outpatient Department (OPD) clinical systems. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 202,
                'name' => 'vlan_202_Radiology_&_Imaging',
                'security_zone' => VlanSecurityZone::PACS,
                'is_restricted' => false,
                'description' => 'Radiology & Imaging modalities and PACS scanners. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 203,
                'name' => 'vlan_203_Central_Store_Pharmacy',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'Central Store & Pharmacy operations. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 204,
                'name' => 'vlan_204_Laboratory_Service',
                'security_zone' => VlanSecurityZone::CLINICAL,
                'is_restricted' => false,
                'description' => 'Pathology and laboratory diagnostic services. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 205,
                'name' => 'vlan_205_IPD_Emergency',
                'security_zone' => VlanSecurityZone::CLINICAL,
                'is_restricted' => true,
                'description' => 'Inpatient Department & Emergency critical care. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 206,
                'name' => 'vlan_206_IPD - NS_Doctors_others',
                'security_zone' => VlanSecurityZone::WORKSTATIONS,
                'is_restricted' => false,
                'description' => 'IPD nursing stations, doctor rooms and clinical workstations. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 207,
                'name' => 'vlan_207_Cancer-Center',
                'security_zone' => VlanSecurityZone::CLINICAL,
                'is_restricted' => false,
                'description' => 'Cancer Center oncology systems. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 400,
                'name' => 'vlan400_DOOR-ACCESS',
                'security_zone' => VlanSecurityZone::MANAGEMENT,
                'is_restricted' => false,
                'description' => 'Door access control and security turnstiles. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 500,
                'name' => 'vlan_500_Port_Ether4',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'For_SW_1 switch port ether4. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 502,
                'name' => 'vlan_502_KYAMCH-BRS',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'SW-1_P-1,2 (Temp - 4 - 100R) KYAMCH-BRS. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 503,
                'name' => 'vlan_503_for_100R',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'SW-1_P-17,18 for 100R. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 504,
                'name' => 'vlan_504_for_100R',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'SW-1_P-23,24 for 100R. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 506,
                'name' => 'vlan_506_Parking_Building',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'SW-1_P-11,12 Parking Building facility network. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 508,
                'name' => 'vlan_508_Guest_House',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'SW-1_P-15,16 Guest House network. Interface: 06-TO-CORE-SW_Bonding.',
            ],

            // Screenshot 2
            [
                'vlan_id' => 520,
                'name' => 'vlan_520__KYAMC_IP',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'KYAMC IP network segment. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 600,
                'name' => 'Vlan - 600_Management_Body',
                'security_zone' => VlanSecurityZone::MANAGEMENT,
                'is_restricted' => false,
                'description' => 'Executive & Management Body network. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 777,
                'name' => 'BDREN-Public-IP-VLAN-777',
                'security_zone' => VlanSecurityZone::DMZ,
                'is_restricted' => false,
                'description' => 'BDREN Public IP uplink VLAN 777. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 899,
                'name' => '0.4-AT-DATA-899',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'AT Data network. Interface: ether8.',
            ],
            [
                'vlan_id' => 901,
                'name' => '2.08-VLAN-901-VMWARE-MANAGEMENT',
                'security_zone' => VlanSecurityZone::MANAGEMENT,
                'is_restricted' => false,
                'description' => 'VMware management network. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 902,
                'name' => '2.03-VLAN-902_WEB_APPLICATION',
                'security_zone' => VlanSecurityZone::SERVERS,
                'is_restricted' => false,
                'description' => 'Web applications server network. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 903,
                'name' => '2.07-VLAN-903-DOOR-ACCESS-SERVER',
                'security_zone' => VlanSecurityZone::SERVERS,
                'is_restricted' => false,
                'description' => 'Door access server network. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 904,
                'name' => '2.05-VLAN-904-ALL-NVR',
                'security_zone' => VlanSecurityZone::SERVERS,
                'is_restricted' => false,
                'description' => 'Surveillance NVR video recording cluster. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 905,
                'name' => '2.04-VLAN-905-IP-PABX-SERVER',
                'security_zone' => VlanSecurityZone::VOIP,
                'is_restricted' => false,
                'description' => 'IP-PABX telephony server. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 906,
                'name' => 'vlan_906_PUBLIC-IP',
                'security_zone' => VlanSecurityZone::DMZ,
                'is_restricted' => false,
                'description' => 'Public IP gateway subnet. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 907,
                'name' => 'vlan_907_COSMO-PUBLIC-IP',
                'security_zone' => VlanSecurityZone::DMZ,
                'is_restricted' => false,
                'description' => 'COSMO Public IP interface. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 908,
                'name' => 'vlan_908_IT-ROOM',
                'security_zone' => VlanSecurityZone::MANAGEMENT,
                'is_restricted' => false,
                'description' => 'IT Room network and lab operations. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 913,
                'name' => '2.06-VLAN-913-NAS-SERVERS',
                'security_zone' => VlanSecurityZone::SERVERS,
                'is_restricted' => false,
                'description' => 'NAS Servers network. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1001,
                'name' => 'VLAN-1001_Public IP BRACNet_block2',
                'security_zone' => VlanSecurityZone::DMZ,
                'is_restricted' => false,
                'description' => 'Public IP BRACNet Block 2 uplink. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1101,
                'name' => 'VLAN-1101-MGBD-ALL-SERVICE',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'MGBD All Services. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1102,
                'name' => 'VLAN-1102-KYAMCH-IT-DEPARTMENT',
                'security_zone' => VlanSecurityZone::WORKSTATIONS,
                'is_restricted' => false,
                'description' => 'KYAMCH IT Department workstations & management. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1103,
                'name' => 'VLAN-1103-KYAMCH-ACC-HR',
                'security_zone' => VlanSecurityZone::WORKSTATIONS,
                'is_restricted' => false,
                'description' => 'KYAMCH Accounts & Human Resources. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1104,
                'name' => 'VLAN-1104-KYAMCH-NUR-ADMIN',
                'security_zone' => VlanSecurityZone::WORKSTATIONS,
                'is_restricted' => false,
                'description' => 'KYAMCH Nursing Administration. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1105,
                'name' => 'VLAN-1105-KYAMCH-CENTRAL-STORE',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'KYAMCH Central Store network. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1106,
                'name' => 'VLAN-1106-KYAMCH-CANCER-CENTER',
                'security_zone' => VlanSecurityZone::CLINICAL,
                'is_restricted' => false,
                'description' => 'KYAMCH Cancer Center clinical network. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1107,
                'name' => 'VLAN-1107-KYAMCH-IPD-EMERGENCY',
                'security_zone' => VlanSecurityZone::CLINICAL,
                'is_restricted' => true,
                'description' => 'KYAMCH IPD Emergency care. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1108,
                'name' => 'VLAN-1108-IPD-NS-DOCTOR',
                'security_zone' => VlanSecurityZone::WORKSTATIONS,
                'is_restricted' => false,
                'description' => 'IPD Nursing Stations & Doctor consultation rooms. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1109,
                'name' => 'VLAN-1109-KYAMCH-LABORATORY-SERVICE',
                'security_zone' => VlanSecurityZone::CLINICAL,
                'is_restricted' => false,
                'description' => 'KYAMCH Laboratory & Diagnostic Service. Interface: 06-TO-CORE-SW_Bonding.',
            ],

            // Screenshot 3
            [
                'vlan_id' => 1110,
                'name' => 'VLAN-1110-KYAMCH-OPD',
                'security_zone' => VlanSecurityZone::CLINICAL,
                'is_restricted' => false,
                'description' => 'KYAMCH OPD Outpatient Clinics. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1111,
                'name' => 'VLAN-1111-KYAMCH-CENTRAL-PHARMACY',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'KYAMCH Central Pharmacy dispensing & inventory. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1112,
                'name' => 'VLAN-1112-KYAMCH-RADIOLOGY-IMAGING',
                'security_zone' => VlanSecurityZone::PACS,
                'is_restricted' => false,
                'description' => 'KYAMCH Radiology & Imaging department. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1113,
                'name' => 'VLAN-1113-GUEST-HOUSE',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'Guest House network. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1114,
                'name' => 'VLAN-1114-PARKING-BUILDING',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'Parking Building facilities & control. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1115,
                'name' => 'VLAN-1115-RESIDENTIAL-BUILDING',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'Residential Building staff quarters. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1116,
                'name' => 'VLAN-1116-KYAMCH-BRS',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'KYAMCH BRS network. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1117,
                'name' => 'VLAN-1117-KYANC',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'KYANC network. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1118,
                'name' => 'VLAN-1118-KYALSC',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'KYALSC network. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1119,
                'name' => 'VLAN-1119-KYAMC',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'KYAMC network. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1121,
                'name' => 'VLAN-1121-ALHAJ-BARI-DARBAR-SHARIF',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'Alhaj Bari Darbar Sharif network. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1122,
                'name' => 'VLAN-1122-KYAMC-STUDENT-HOSTEL-MALE',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'KYAMC Male Student Hostel. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1123,
                'name' => 'VLAN-1123-KYAMC-STUDENT-HOSTEL-FEMALE',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'KYAMC Female Student Hostel. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1124,
                'name' => 'VLAN-1124-KYAMC-INTERNATIONAL-STUDENT-HOSTEL-MALE',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'KYAMC International Male Student Hostel. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1125,
                'name' => 'VLAN-1125-KYAMC-INTERNATIONAL-STUDENT-HOSTEL-FEMALE',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'KYAMC International Female Student Hostel. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1126,
                'name' => 'VLAN-1126-KYAMCH-INTERN-HOSTEL-MALE',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'KYAMCH Intern Doctor Male Hostel. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1127,
                'name' => 'VLAN-1127-KYAMCH-INTERN-HOSTEL-FEMALE',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'KYAMCH Intern Doctor Female Hostel. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1128,
                'name' => 'VLAN-1128-KYAMCH-DOCTORS-DORMITORY-MALE',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'KYAMCH Male Doctors Dormitory. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1129,
                'name' => 'VLAN-1129-KYAMCH-DOCTORS-DORMITORY-FEMALE',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'KYAMCH Female Doctors Dormitory. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1130,
                'name' => 'VLAN-1130-KYANC-STUDENT-HOSTEL-MALE',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'KYANC Male Student Hostel. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1131,
                'name' => 'VLAN-1131-KYANC-STUDENT-HOSTEL-FEMALE',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'KYANC Female Student Hostel. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1132,
                'name' => 'VLAN-1132-KYAU-STUDENT-HOSTEL-MALE',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'KYAU Male Student Hostel. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1133,
                'name' => 'VLAN-1133-KYAU-STUDENT-HOSTEL-FEMALE',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'KYAU Female Student Hostel. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1134,
                'name' => 'VLAN-1134-NURSE-DORMITORY',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'Nurse Dormitory. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1135,
                'name' => 'VLAN-1135-KYAMCH-AUDITORIAM',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'KYAMCH Auditorium. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1136,
                'name' => 'VLAN-1136-KYAMCH-Medical-Collage',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'KYAMCH Medical College academic network. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1137,
                'name' => 'VLAN-1137-KYAMCH-Medical-Collage-Library',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'KYAMCH Medical College Library network. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1138,
                'name' => 'VLAN-1138-KYANC-Library',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'KYANC Library network. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1201,
                'name' => 'VLAN-1201-Door Access Lan',
                'security_zone' => VlanSecurityZone::MANAGEMENT,
                'is_restricted' => false,
                'description' => 'Door Access Lan security network. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1491,
                'name' => '0.1_BN_vlan1491_INT',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'BN VLAN 1491 INT. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1492,
                'name' => '0.1_BN_vlan1492_FNA',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'BN VLAN 1492 FNA. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1493,
                'name' => '0.1_BN_vlan1493_GGC',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'BN VLAN 1493 GGC. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1600,
                'name' => 'VLAN-1600_SWITCH_MANAGEMENT',
                'security_zone' => VlanSecurityZone::MANAGEMENT,
                'is_restricted' => false,
                'description' => 'Switch management VLAN 1600. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1601,
                'name' => 'vlan1601_SW-2_MGT_Port25',
                'security_zone' => VlanSecurityZone::MANAGEMENT,
                'is_restricted' => false,
                'description' => 'SW-2 MGT Port 25. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1602,
                'name' => '2.02-VLAN-1602-HIS-SERVER',
                'security_zone' => VlanSecurityZone::SERVERS,
                'is_restricted' => false,
                'description' => 'Hospital Information System (HIS) server infrastructure. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 1603,
                'name' => 'vlan1603_SW_3',
                'security_zone' => VlanSecurityZone::MANAGEMENT,
                'is_restricted' => false,
                'description' => 'Switch 3 management. Interface: 06-TO-CORE-SW_Bonding.',
            ],

            // Screenshot 4
            [
                'vlan_id' => 1605,
                'name' => 'vlan1605_SW_5',
                'security_zone' => VlanSecurityZone::MANAGEMENT,
                'is_restricted' => false,
                'description' => 'Switch 5 management. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 2170,
                'name' => '0.2_BN_vlan2170_INT',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'BN VLAN 2170 INT. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 2171,
                'name' => '0.2_BN_vlan2171_FNA',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'BN VLAN 2171 FNA. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 2172,
                'name' => '0.2_BN_vlan2172_GGC',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'BN VLAN 2172 GGC. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 2222,
                'name' => 'VLAN-2222-AP_Management',
                'security_zone' => VlanSecurityZone::MANAGEMENT,
                'is_restricted' => false,
                'description' => 'Wireless Access Point (AP) management VLAN. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 2302,
                'name' => 'VLAN2302-IT-DEP-IPPHONE',
                'security_zone' => VlanSecurityZone::VOIP,
                'is_restricted' => false,
                'description' => 'IT Department IP Phones VoIP network. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 2401,
                'name' => 'VLAN2401-MGBD-IPCAM',
                'security_zone' => VlanSecurityZone::MANAGEMENT,
                'is_restricted' => false,
                'description' => 'MGBD IP Cameras surveillance network. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 2601,
                'name' => 'VLAN2601-MGBD-ALL-SERVICE',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'MGBD All Services. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 2602,
                'name' => 'VLAN2602-IT-DEP-ALL-SERVICE',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'IT Department All Services. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 2603,
                'name' => 'Management-Building-New-Mikrotik-2603',
                'security_zone' => VlanSecurityZone::MANAGEMENT,
                'is_restricted' => false,
                'description' => 'Management Building New MikroTik router. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 2615,
                'name' => 'VLAN 2615 - All Services (7 No Buiding)',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'All Services (7 No Building). Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 2708,
                'name' => 'VLAN 2708 - TAB_HIS',
                'security_zone' => VlanSecurityZone::SERVERS,
                'is_restricted' => false,
                'description' => 'Tablet Hospital Information System (TAB_HIS) wireless and backend. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 2715,
                'name' => 'VLAN-2715-RESIDENTIAL-BUILDING',
                'security_zone' => VlanSecurityZone::INTERNAL,
                'is_restricted' => false,
                'description' => 'Residential Building facility network. Interface: 06-TO-CORE-SW_Bonding.',
            ],
            [
                'vlan_id' => 3505,
                'name' => '0.0.VLAN-3505-BdREN-PRI',
                'security_zone' => VlanSecurityZone::DMZ,
                'is_restricted' => false,
                'description' => 'Bd-Ren_Primary BdREN Primary WAN. Interface: 02-sfp-sfpplus2_BD-Ren_Primary.',
            ],
        ];

        foreach ($enabledVlans as $cfg) {
            $cfg['site_id'] = $mainSite?->id;
            if (! Vlan::where('vlan_id', $cfg['vlan_id'])->exists()) {
                $vlanAction->execute($cfg);
            }
        }

        // 4. Seed Subnet Tree Hierarchy (Active Subnets from MikroTik WinBox)
        $subnetAction = app(CreateSubnetAction::class);

        $rootAggregates = [
            [
                'cidr' => '10.0.0.0/8',
                'name' => 'KYAMCH Enterprise Private Space (10.0.0.0/8)',
                'description' => 'Top-level enterprise aggregation container for all internal hospital networks.',
                'subnet_type' => SubnetType::AGGREGATE,
                'parent_cidr' => null,
            ],
            [
                'cidr' => '172.16.0.0/12',
                'name' => 'KYAMCH Infrastructure & Services Space (172.16.0.0/12)',
                'description' => 'Top-level aggregation container for datacenter services, P2P interconnects, and security systems.',
                'subnet_type' => SubnetType::AGGREGATE,
                'parent_cidr' => null,
            ],
            [
                'cidr' => '172.51.0.0/16',
                'name' => 'KYAMCH Door Access Server Space (172.51.0.0/16)',
                'description' => 'Dedicated server subnet container for door access security systems.',
                'subnet_type' => SubnetType::AGGREGATE,
                'parent_cidr' => null,
            ],
            [
                'cidr' => '192.168.0.0/16',
                'name' => 'KYAMCH Management & Server Space (192.168.0.0/16)',
                'description' => 'Top-level aggregation container for out-of-band management, core switch interconnects, and servers.',
                'subnet_type' => SubnetType::AGGREGATE,
                'parent_cidr' => null,
            ],
            [
                'cidr' => '103.157.0.0/16',
                'name' => 'BDREN Public WAN IP Space (103.157.0.0/16)',
                'description' => 'Public IPv4 allocation block assigned by BDREN.',
                'subnet_type' => SubnetType::AGGREGATE,
                'parent_cidr' => null,
            ],
            [
                'cidr' => '115.127.0.0/16',
                'name' => 'BRACNet Public WAN IP Space (115.127.0.0/16)',
                'description' => 'Public IPv4 allocation block assigned by BRACNet.',
                'subnet_type' => SubnetType::AGGREGATE,
                'parent_cidr' => null,
            ],
            [
                'cidr' => '163.47.0.0/16',
                'name' => 'BDREN Public Uplink Space (163.47.0.0/16)',
                'description' => 'Public IPv4 uplink interconnect space from BDREN.',
                'subnet_type' => SubnetType::AGGREGATE,
                'parent_cidr' => null,
            ],
        ];

        $secondLevelAggregates = [
            // Under 10.0.0.0/8
            ['cidr' => '10.10.0.0/16', 'name' => 'KYAMCH Core Operations & Applications (10.10.0.0/16)', 'parent_cidr' => '10.0.0.0/8'],
            ['cidr' => '10.20.0.0/16', 'name' => 'KYAMCH HIS & Internet Space (10.20.0.0/16)', 'parent_cidr' => '10.0.0.0/8'],
            ['cidr' => '10.22.0.0/16', 'name' => 'KYAU Data Link P2P Space (10.22.0.0/16)', 'parent_cidr' => '10.0.0.0/8'],
            ['cidr' => '10.30.0.0/16', 'name' => 'KYAMCH IP Telephony / IPPBX Space (10.30.0.0/16)', 'parent_cidr' => '10.0.0.0/8'],
            ['cidr' => '10.40.0.0/16', 'name' => 'KYAMCH Surveillance CCTV & Security (10.40.0.0/16)', 'parent_cidr' => '10.0.0.0/8'],
            ['cidr' => '10.50.0.0/16', 'name' => 'KYAMCH Door Access Control Space (10.50.0.0/16)', 'parent_cidr' => '10.0.0.0/8'],
            ['cidr' => '10.60.0.0/16', 'name' => 'KYAMCH Building & Facility Services (10.60.0.0/16)', 'parent_cidr' => '10.0.0.0/8'],
            ['cidr' => '10.70.0.0/16', 'name' => 'KYAMCH Hostels, Residential & Campus (10.70.0.0/16)', 'parent_cidr' => '10.0.0.0/8'],
            ['cidr' => '10.80.0.0/16', 'name' => 'KYAMCH Medical College Academic Space (10.80.0.0/16)', 'parent_cidr' => '10.0.0.0/8'],
            ['cidr' => '10.90.0.0/16', 'name' => 'KYAMCH Medical College Library Space (10.90.0.0/16)', 'parent_cidr' => '10.0.0.0/8'],
            ['cidr' => '10.200.0.0/16', 'name' => 'KYAMCH Agni Data P2P Space (10.200.0.0/16)', 'parent_cidr' => '10.0.0.0/8'],
            ['cidr' => '10.241.0.0/16', 'name' => 'KYAMCH Clinical HIS Subnets (10.241.0.0/16)', 'parent_cidr' => '10.0.0.0/8'],
            ['cidr' => '10.242.0.0/16', 'name' => 'KYAMCH Hospital Internet Subnets (10.242.0.0/16)', 'parent_cidr' => '10.0.0.0/8'],
            ['cidr' => '10.243.0.0/16', 'name' => 'KYAMCH Department All-Services & IPPBX (10.243.0.0/16)', 'parent_cidr' => '10.0.0.0/8'],
            ['cidr' => '10.244.0.0/16', 'name' => 'KYAMCH Department CCTV & Camera Subnets (10.244.0.0/16)', 'parent_cidr' => '10.0.0.0/8'],
            ['cidr' => '10.245.0.0/16', 'name' => 'KYAMCH Department Door Access Subnets (10.245.0.0/16)', 'parent_cidr' => '10.0.0.0/8'],
            ['cidr' => '10.246.0.0/16', 'name' => 'KYAMCH Clinical Internet & HIS Subnets (10.246.0.0/16)', 'parent_cidr' => '10.0.0.0/8'],
            ['cidr' => '10.247.0.0/16', 'name' => 'KYAMCH BDREN IT Department Space (10.247.0.0/16)', 'parent_cidr' => '10.0.0.0/8'],
            ['cidr' => '10.252.0.0/16', 'name' => 'KYAMCH BdREN WAN Interconnects (10.252.0.0/16)', 'parent_cidr' => '10.0.0.0/8'],
            ['cidr' => '10.254.0.0/16', 'name' => 'KYAMCH Management Building 600V Space (10.254.0.0/16)', 'parent_cidr' => '10.0.0.0/8'],

            // Under 172.16.0.0/12
            ['cidr' => '172.17.0.0/16', 'name' => 'KYAMCH ATI P2P Routing Space (172.17.0.0/16)', 'parent_cidr' => '172.16.0.0/12'],
            ['cidr' => '172.18.0.0/16', 'name' => 'KYAMCH KYANC Library Space (172.18.0.0/16)', 'parent_cidr' => '172.16.0.0/12'],
            ['cidr' => '172.21.0.0/16', 'name' => 'KYAMCH Datacenter NVR, NAS & Security (172.21.0.0/16)', 'parent_cidr' => '172.16.0.0/12'],
            ['cidr' => '172.22.0.0/16', 'name' => 'KYAMCH BRACNet Interconnect & Voice (172.22.0.0/16)', 'parent_cidr' => '172.16.0.0/12'],
            ['cidr' => '172.25.0.0/16', 'name' => 'KYAMCH OOB Router Management Space (172.25.0.0/16)', 'parent_cidr' => '172.16.0.0/12'],
            ['cidr' => '172.29.0.0/16', 'name' => 'KYAMCH MikroTik Management & Hotspot (172.29.0.0/16)', 'parent_cidr' => '172.16.0.0/12'],
            ['cidr' => '172.30.0.0/16', 'name' => 'KYAMCH Core IP-PABX Voice Space (172.30.0.0/16)', 'parent_cidr' => '172.16.0.0/12'],

            // Under 192.168.0.0/16
            ['cidr' => '192.168.254.0/24', 'name' => 'KYAMCH Core Management Infrastructure (192.168.254.0/24)', 'parent_cidr' => '192.168.0.0/16'],
            ['cidr' => '192.168.255.0/24', 'name' => 'KYAMCH Core Switch Interconnects (192.168.255.0/24)', 'parent_cidr' => '192.168.0.0/16'],
        ];

        $allAggregatesMap = [];
        foreach ($rootAggregates as $ra) {
            $allAggregatesMap[$ra['cidr']] = $ra;
        }
        foreach ($secondLevelAggregates as $sa) {
            $sa['subnet_type'] = SubnetType::AGGREGATE;
            $sa['description'] = $sa['name'];
            $allAggregatesMap[$sa['cidr']] = $sa;
        }

        // Helper closures
        $cidrToNetmask = function (int $prefixLength): string {
            $mask = (0xFFFFFFFF << (32 - $prefixLength)) & 0xFFFFFFFF;
            return long2ip($mask);
        };

        $findParentForCidr = function (string $cidr) use ($allAggregatesMap): ?string {
            $targetPrefix = IpPrefix::parse($cidr);
            $bestParent = null;
            $bestLen = -1;

            foreach ($allAggregatesMap as $aggCidr => $aggData) {
                $aggPrefix = IpPrefix::parse($aggCidr);
                if ($aggPrefix->containsPrefix($targetPrefix) && $aggPrefix->prefixLength() < $targetPrefix->prefixLength()) {
                    if ($aggPrefix->prefixLength() > $bestLen) {
                        $bestLen = $aggPrefix->prefixLength();
                        $bestParent = $aggCidr;
                    }
                }
            }

            return $bestParent;
        };

        // Create root aggregates
        $createdSubnets = [];
        foreach ($rootAggregates as $cfg) {
            $existing = Subnet::where('cidr', $cfg['cidr'])->first();
            if (!$existing) {
                $prefix = IpPrefix::parse($cfg['cidr']);
                $sub = $subnetAction->execute([
                    'site_id' => $mainSite?->id,
                    'name' => $cfg['name'],
                    'cidr' => $cfg['cidr'],
                    'subnet_mask' => $cidrToNetmask($prefix->prefixLength()),
                    'subnet_type' => SubnetType::AGGREGATE,
                    'description' => $cfg['description'],
                    'allocation_enabled' => false,
                    'status' => SubnetStatus::ACTIVE,
                ]);
                $createdSubnets[$cfg['cidr']] = $sub;
            } else {
                $createdSubnets[$cfg['cidr']] = $existing;
            }
        }

        // Create second-level aggregates
        foreach ($secondLevelAggregates as $cfg) {
            $existing = Subnet::where('cidr', $cfg['cidr'])->first();
            if (!$existing) {
                $prefix = IpPrefix::parse($cfg['cidr']);
                $parentSubnet = $createdSubnets[$cfg['parent_cidr']] ?? null;
                $sub = $subnetAction->execute([
                    'site_id' => $mainSite?->id,
                    'parent_id' => $parentSubnet?->id,
                    'name' => $cfg['name'],
                    'cidr' => $cfg['cidr'],
                    'subnet_mask' => $cidrToNetmask($prefix->prefixLength()),
                    'subnet_type' => SubnetType::AGGREGATE,
                    'description' => $cfg['name'],
                    'allocation_enabled' => false,
                    'status' => SubnetStatus::ACTIVE,
                ]);
                $createdSubnets[$cfg['cidr']] = $sub;
            } else {
                $createdSubnets[$cfg['cidr']] = $existing;
            }
        }

        // Create all 151 leaf active subnets from MikroTik WinBox
        $allVlans = Vlan::all()->keyBy('name');

        $activeEntries = [
            // Image 1: 192.168.254 - 255 infrastructure
            ['address' => '192.168.255.61/30', 'network' => '192.168.255.60', 'interface' => '2.01-SW-MGMT-VLAN-2', 'comment' => 'Backup-Core-sw-IP'],
            ['address' => '192.168.255.57/30', 'network' => '192.168.255.56', 'interface' => '2.01-SW-MGMT-VLAN-2', 'comment' => 'Backup-Core-sw-IP'],
            ['address' => '192.168.255.53/30', 'network' => '192.168.255.52', 'interface' => '2.08-VLAN-901-VMWARE-MANAGEMENT', 'comment' => 'VMware Core Link'],
            ['address' => '192.168.255.49/30', 'network' => '192.168.255.48', 'interface' => '2.08-VLAN-901-VMWARE-MANAGEMENT', 'comment' => 'VMware Core Link 2'],
            ['address' => '192.168.255.33/28', 'network' => '192.168.255.32', 'interface' => '2.08-VLAN-901-VMWARE-MANAGEMENT', 'comment' => 'VMware Management Link'],
            ['address' => '192.168.255.29/30', 'network' => '192.168.255.28', 'interface' => '2.01-SW-MGMT-VLAN-2', 'comment' => '@ KYAMCH-RESIDENTIAL-SW-MGMT'],
            ['address' => '192.168.255.25/30', 'network' => '192.168.255.24', 'interface' => '2.01-SW-MGMT-VLAN-2', 'comment' => '@ KYAMCH_DIS_SW-MGMT'],
            ['address' => '192.168.255.21/30', 'network' => '192.168.255.20', 'interface' => '2.01-SW-MGMT-VLAN-2', 'comment' => '@ KYAMCH_CORE-SW-MGMT'],
            ['address' => '192.168.255.17/30', 'network' => '192.168.255.16', 'interface' => 'vlan1605_SW_5', 'comment' => 'SW_5 Management'],
            ['address' => '192.168.255.13/30', 'network' => '192.168.255.12', 'interface' => 'vlan1603_SW_3', 'comment' => 'SW_3 Management'],
            ['address' => '192.168.254.81/28', 'network' => '192.168.254.80', 'interface' => 'VLAN-106-Veem', 'comment' => 'Veem-Backup-Management'],
            ['address' => '192.168.254.33/28', 'network' => '192.168.254.32', 'interface' => 'VLAN-103-NAS', 'comment' => 'Synology-Management'],
            ['address' => '192.168.254.17/28', 'network' => '192.168.254.16', 'interface' => 'VLAN-102-VMware', 'comment' => 'VMware-Management'],
            ['address' => '192.168.254.1/28', 'network' => '192.168.254.0', 'interface' => 'VLAN-101-IDRAC', 'comment' => 'iDRAC-Management'],
            ['address' => '192.168.253.1/24', 'network' => '192.168.253.0', 'interface' => 'VLAN-2222-AP_Management', 'comment' => 'VLAN-2222-AP_Management_Static IP'],
            ['address' => '192.168.252.1/24', 'network' => '192.168.252.0', 'interface' => 'VLAN-2222-AP_Management', 'comment' => 'VLAN-2222-AP_Management_Static IP 2'],
            ['address' => '192.168.251.1/24', 'network' => '192.168.251.0', 'interface' => 'VLAN-2222-AP_Management', 'comment' => 'VLAN-2222-AP_Management_DHCP'],
            ['address' => '192.168.250.1/24', 'network' => '192.168.250.0', 'interface' => '2.01-SW-MGMT-VLAN-2', 'comment' => '@ MGMT-ALL-Switch-Network'],
            ['address' => '192.168.249.1/24', 'network' => '192.168.249.0', 'interface' => 'VLAN-1201-Door Access Lan', 'comment' => 'Door Access Lan'],
            ['address' => '192.168.99.1/24', 'network' => '192.168.99.0', 'interface' => 'ether13', 'comment' => 'Management ether13'],

            // Image 2: Servers, 172.x infrastructure
            ['address' => '192.168.11.1/24', 'network' => '192.168.11.0', 'interface' => '2.03-VLAN-902_WEB_APPLICATION', 'comment' => 'WEB APPLICATION SERVERS'],
            ['address' => '192.168.10.1/24', 'network' => '192.168.10.0', 'interface' => '2.02-VLAN-1602-HIS-SERVER', 'comment' => 'HIS SERVERS'],
            ['address' => '192.168.2.1/24', 'network' => '192.168.2.0', 'interface' => 'vlan_502_KYAMCH-BRS', 'comment' => 'KYAMCH-BRS_INT'],
            ['address' => '192.168.1.1/24', 'network' => '192.168.1.0', 'interface' => 'vlan_207_Cancer-Center', 'comment' => 'INT_Cancer-Center'],
            ['address' => '172.51.51.49/28', 'network' => '172.51.51.48', 'interface' => '2.07-VLAN-903-DOOR-ACCESS-SERVER', 'comment' => 'Door Access Server'],
            ['address' => '172.30.30.1/26', 'network' => '172.30.30.0', 'interface' => '2.04-VLAN-905-IP-PABX-SERVER', 'comment' => 'IP-PABX Server Subnet'],
            ['address' => '172.29.100.1/30', 'network' => '172.29.100.0', 'interface' => '3.0-Hotspot - Gateway - 4', 'comment' => 'HOtspot-Gateway'],
            ['address' => '172.29.0.1/30', 'network' => '172.29.0.0', 'interface' => 'Management-Building-New-Mikrotik-2603', 'comment' => 'Management-Mkt'],
            ['address' => '172.25.0.1/30', 'network' => '172.25.0.0', 'interface' => 'ether10', 'comment' => 'Management ether10'],
            ['address' => '172.22.109.65/29', 'network' => '172.22.109.64', 'interface' => '2.04-VLAN-905-IP-PABX-SERVER', 'comment' => 'free IP / IP-PABX Voice'],
            ['address' => '172.22.47.106/30', 'network' => '172.22.47.104', 'interface' => '0.2_BN_vlan2172_GGC', 'comment' => 'BN VLAN 2172 GGC Link'],
            ['address' => '172.22.47.102/30', 'network' => '172.22.47.100', 'interface' => '0.2_BN_vlan2171_FNA', 'comment' => 'BN VLAN 2171 FNA Link'],
            ['address' => '172.22.47.98/30', 'network' => '172.22.47.96', 'interface' => '0.2_BN_vlan2170_INT', 'comment' => 'Brac-2nd INT Link'],
            ['address' => '172.22.47.94/30', 'network' => '172.22.47.92', 'interface' => '0.1_BN_vlan1493_GGC', 'comment' => 'BN VLAN 1493 GGC Link'],
            ['address' => '172.22.47.90/30', 'network' => '172.22.47.88', 'interface' => '0.1_BN_vlan1492_FNA', 'comment' => 'BN VLAN 1492 FNA Link'],
            ['address' => '172.22.47.86/30', 'network' => '172.22.47.84', 'interface' => '0.1_BN_vlan1491_INT', 'comment' => 'Brac_Primary INT Link'],
            ['address' => '172.21.215.29/30', 'network' => '172.21.215.28', 'interface' => 'ether13', 'comment' => 'To Security Router (Al hajj security)'],
            ['address' => '172.21.21.33/28', 'network' => '172.21.21.32', 'interface' => '2.06-VLAN-913-NAS-SERVERS', 'comment' => 'NAS Servers Link 2'],
            ['address' => '172.21.21.17/28', 'network' => '172.21.21.16', 'interface' => '2.06-VLAN-913-NAS-SERVERS', 'comment' => 'Synology NAS'],
            ['address' => '172.21.21.1/28', 'network' => '172.21.21.0', 'interface' => '2.05-VLAN-904-ALL-NVR', 'comment' => 'ALL NVR'],
            ['address' => '172.18.0.1/22', 'network' => '172.18.0.0', 'interface' => 'VLAN-1138-KYANC-Library', 'comment' => 'KYANC Library Network'],
            ['address' => '172.17.0.2/30', 'network' => '172.17.0.0', 'interface' => 'To-ATI', 'comment' => 'ATI-Route-P2P'],

            // Image 3: Public IPs, 10.254, 10.252, 10.247, 10.246, 10.245
            ['address' => '163.47.36.89/30', 'network' => '163.47.36.88', 'interface' => 'BDREN-Public-IP-VLAN-777', 'comment' => 'BdRen Public IP Block @ KYAMCH'],
            ['address' => '115.127.211.65/29', 'network' => '115.127.211.64', 'interface' => 'VLAN-1001_Public IP BRACNet_block2', 'comment' => 'BRACNet Public IP Block 2'],
            ['address' => '115.127.122.97/29', 'network' => '115.127.122.96', 'interface' => 'vlan_906_PUBLIC-IP', 'comment' => 'BRAC PUBLIC IP'],
            ['address' => '103.157.134.177/28', 'network' => '103.157.134.176', 'interface' => 'BDREN-Public-IP-VLAN-777', 'comment' => 'BdRen Public IP Block @ KYAMCH'],
            ['address' => '10.254.1.1/24', 'network' => '10.254.1.0', 'interface' => 'VLAN2601-MGBD-ALL-SERVICE', 'comment' => '29 No. Building (Management Body)Kashifa mam - 600V - 10.254.1.0'],
            ['address' => '10.254.0.1/24', 'network' => '10.254.0.0', 'interface' => 'VLAN2601-MGBD-ALL-SERVICE', 'comment' => '29 No. Building (Management Body) - 10.254.0.0'],
            ['address' => '10.252.2.146/30', 'network' => '10.252.2.144', 'interface' => '01-sfp-sfpplus1_BdREN_Sec-Summit', 'comment' => 'BdREN Sec Summit Link 2'],
            ['address' => '10.252.2.142/30', 'network' => '10.252.2.140', 'interface' => '0.0.VLAN-3505-BdREN-PRI', 'comment' => 'BdREN Pri Link 2'],
            ['address' => '10.252.2.130/30', 'network' => '10.252.2.128', 'interface' => '01-sfp-sfpplus1_BdREN_Sec-Summit', 'comment' => 'BdREN Sec Summit Link 1'],
            ['address' => '10.252.2.126/30', 'network' => '10.252.2.124', 'interface' => '0.0.VLAN-3505-BdREN-PRI', 'comment' => 'BdREN Pri Link 1'],
            ['address' => '10.247.138.1/24', 'network' => '10.247.138.0', 'interface' => 'VLAN-1102-KYAMCH-IT-DEPARTMENT', 'comment' => 'IT_New - For - BDREN'],
            ['address' => '10.246.5.1/24', 'network' => '10.246.5.0', 'interface' => 'vlan_506_Parking_Building', 'comment' => 'Parking Building Internet'],
            ['address' => '10.246.2.1/24', 'network' => '10.246.2.0', 'interface' => 'vlan_203_Central_Store_Pharmacy', 'comment' => 'Central Store and Central Pharmacy(HIS+INTERNET)'],
            ['address' => '10.246.1.1/24', 'network' => '10.246.1.0', 'interface' => 'vlan_204_Laboratory_Service', 'comment' => 'Laboratory Service(HIS+INTERNET)'],
            ['address' => '10.246.0.1/24', 'network' => '10.246.0.0', 'interface' => 'vlan_202_Radiology_&_Imaging', 'comment' => 'INT - HIS_Radiology&Imaging'],
            ['address' => '10.245.9.1/24', 'network' => '10.245.9.0', 'interface' => 'vlan_202_Radiology_&_Imaging', 'comment' => 'Door_Radiology&Imaging'],
            ['address' => '10.245.8.1/24', 'network' => '10.245.8.0', 'interface' => 'vlan_502_KYAMCH-BRS', 'comment' => 'KYAMCH-BRS_Door'],

            // Image 4: 10.245.x and 10.244.x
            ['address' => '10.245.4.1/24', 'network' => '10.245.4.0', 'interface' => 'vlan_207_Cancer-Center', 'comment' => 'Door_Cancer-Center'],
            ['address' => '10.245.3.1/24', 'network' => '10.245.3.0', 'interface' => 'vlan_206_IPD - NS_Doctors_others', 'comment' => 'IPD - NS_Doctors_Door'],
            ['address' => '10.245.2.1/24', 'network' => '10.245.2.0', 'interface' => 'VLAN-1107-KYAMCH-IPD-EMERGENCY', 'comment' => 'IPD_Emergency_Door_new'],
            ['address' => '10.245.1.1/24', 'network' => '10.245.1.0', 'interface' => 'vlan_203_Central_Store_Pharmacy', 'comment' => 'Central Store and Central Pharmacy(Door-Access)'],
            ['address' => '10.245.0.1/24', 'network' => '10.245.0.0', 'interface' => 'vlan_204_Laboratory_Service', 'comment' => 'Laboratory Service(Door-Access)'],
            ['address' => '10.244.25.1/24', 'network' => '10.244.25.0', 'interface' => 'VLAN-1102-KYAMCH-IT-DEPARTMENT', 'comment' => 'IT_New Cameras/Network'],
            ['address' => '10.244.18.1/24', 'network' => '10.244.18.0', 'interface' => '2.08-VLAN-901-VMWARE-MANAGEMENT', 'comment' => 'International Hostel Male_Camera'],
            ['address' => '10.244.17.1/24', 'network' => '10.244.17.0', 'interface' => 'vlan_202_Radiology_&_Imaging', 'comment' => 'Camera_Radiology & Imaging'],
            ['address' => '10.244.16.1/24', 'network' => '10.244.16.0', 'interface' => 'vlan_508_Guest_House', 'comment' => 'GUEST_HOUSE_Camera'],
            ['address' => '10.244.14.1/24', 'network' => '10.244.14.0', 'interface' => 'vlan_502_KYAMCH-BRS', 'comment' => 'KYAMCH-BRS_Camera'],
            ['address' => '10.244.13.1/24', 'network' => '10.244.13.0', 'interface' => 'vlan_207_Cancer-Center', 'comment' => 'Camera_Cancer-Center'],
            ['address' => '10.244.12.1/24', 'network' => '10.244.12.0', 'interface' => 'vlan_206_IPD - NS_Doctors_others', 'comment' => 'IPD - NS_Doctors_camera'],
            ['address' => '10.244.11.1/24', 'network' => '10.244.11.0', 'interface' => 'VLAN-1107-KYAMCH-IPD-EMERGENCY', 'comment' => 'IPD_Emergency_Camera_new'],
            ['address' => '10.244.8.1/24', 'network' => '10.244.8.0', 'interface' => 'vlan_200_OPD', 'comment' => 'OPD_Camera'],
            ['address' => '10.244.6.1/24', 'network' => '10.244.6.0', 'interface' => 'VLAN-1118-KYALSC', 'comment' => 'KYALSC-Camera_new'],

            // Image 5: 10.244.x, 10.243.x
            ['address' => '10.244.2.1/24', 'network' => '10.244.2.0', 'interface' => 'VLAN2601-MGBD-ALL-SERVICE', 'comment' => '29 No. Building (CCTV)'],
            ['address' => '10.244.1.1/24', 'network' => '10.244.1.0', 'interface' => 'vlan_203_Central_Store_Pharmacy', 'comment' => 'Central Store and Central Pharmacy(IP-Camera)'],
            ['address' => '10.244.0.1/24', 'network' => '10.244.0.0', 'interface' => 'vlan_204_Laboratory_Service', 'comment' => 'Laboratory Service(IP-Camera)'],
            ['address' => '10.243.138.1/24', 'network' => '10.243.138.0', 'interface' => 'VLAN-1102-KYAMCH-IT-DEPARTMENT', 'comment' => 'IT_New All Services'],
            ['address' => '10.243.136.1/24', 'network' => '10.243.136.0', 'interface' => 'vlan_502_KYAMCH-BRS', 'comment' => 'KYAMCH-BRS_All_Services'],
            ['address' => '10.243.132.1/24', 'network' => '10.243.132.0', 'interface' => 'vlan_207_Cancer-Center', 'comment' => 'All-Services_Cancer-Center'],
            ['address' => '10.243.131.1/24', 'network' => '10.243.131.0', 'interface' => 'vlan_206_IPD - NS_Doctors_others', 'comment' => 'IPD-NS_Doctors_All-Services'],
            ['address' => '10.243.130.1/24', 'network' => '10.243.130.0', 'interface' => 'VLAN-1107-KYAMCH-IPD-EMERGENCY', 'comment' => 'IPD_Emergency_All-Services_new'],
            ['address' => '10.243.129.1/24', 'network' => '10.243.129.0', 'interface' => 'vlan_200_OPD', 'comment' => 'OPD_All_services'],
            ['address' => '10.243.14.1/24', 'network' => '10.243.14.0', 'interface' => 'vlan_202_Radiology_&_Imaging', 'comment' => 'IPPBX_Radiology&Imaging'],
            ['address' => '10.243.13.1/24', 'network' => '10.243.13.0', 'interface' => 'vlan_508_Guest_House', 'comment' => 'GUEST_HOUSE_IPPBX'],
            ['address' => '10.243.12.1/24', 'network' => '10.243.12.0', 'interface' => 'vlan_502_KYAMCH-BRS', 'comment' => 'KYAMCH-BRS_IPPBX'],
            ['address' => '10.243.11.1/24', 'network' => '10.243.11.0', 'interface' => 'vlan_207_Cancer-Center', 'comment' => 'IPPBX_Cancer-Center'],
            ['address' => '10.243.10.1/24', 'network' => '10.243.10.0', 'interface' => 'vlan_206_IPD - NS_Doctors_others', 'comment' => 'IPD-NS_Doctors_IPPBX'],
            ['address' => '10.243.9.1/24', 'network' => '10.243.9.0', 'interface' => 'VLAN-1107-KYAMCH-IPD-EMERGENCY', 'comment' => 'IPD_Emergency_IPPBX_new'],
            ['address' => '10.243.6.1/24', 'network' => '10.243.6.0', 'interface' => 'vlan_200_OPD', 'comment' => 'OPD_IPPBX'],
            ['address' => '10.243.4.1/24', 'network' => '10.243.4.0', 'interface' => 'VLAN-1118-KYALSC', 'comment' => 'KYALSC-IPPBX_new'],
            ['address' => '10.243.3.1/24', 'network' => '10.243.3.0', 'interface' => 'VLAN-1119-KYAMC', 'comment' => 'KYAMC IPPBX-new?'],

            // Image 6: 10.243.x, 10.242.x, 10.241.x
            ['address' => '10.243.1.1/24', 'network' => '10.243.1.0', 'interface' => 'vlan_203_Central_Store_Pharmacy', 'comment' => 'Central Store and Central Pharmacy(IPPBX)'],
            ['address' => '10.243.0.1/24', 'network' => '10.243.0.0', 'interface' => 'vlan_204_Laboratory_Service', 'comment' => 'Laboratory Service(IPPBX)'],
            ['address' => '10.242.22.1/24', 'network' => '10.242.22.0', 'interface' => 'VLAN-1102-KYAMCH-IT-DEPARTMENT', 'comment' => 'IT_New Internet'],
            ['address' => '10.242.15.1/24', 'network' => '10.242.15.0', 'interface' => '2.08-VLAN-901-VMWARE-MANAGEMENT', 'comment' => 'VMware Hosts Management Subnet'],
            ['address' => '10.242.13.1/24', 'network' => '10.242.13.0', 'interface' => 'vlan_508_Guest_House', 'comment' => 'GUEST_HOUSE_INT'],
            ['address' => '10.242.12.129/25', 'network' => '10.242.12.128', 'interface' => 'vlan_502_KYAMCH-BRS', 'comment' => 'KYAMCH-BRS_Web-mail'],
            ['address' => '10.242.12.1/25', 'network' => '10.242.12.0', 'interface' => 'vlan_502_KYAMCH-BRS', 'comment' => 'KYAMCH-BRS_INT'],
            ['address' => '10.242.11.1/24', 'network' => '10.242.11.0', 'interface' => 'vlan_207_Cancer-Center', 'comment' => 'Int_Cancer-Center'],
            ['address' => '10.242.10.1/24', 'network' => '10.242.10.0', 'interface' => 'vlan_206_IPD - NS_Doctors_others', 'comment' => 'IPD - NS_Doctors_INT'],
            ['address' => '10.242.9.1/24', 'network' => '10.242.9.0', 'interface' => 'VLAN-1107-KYAMCH-IPD-EMERGENCY', 'comment' => 'IPD_Emergency_Internet_new'],
            ['address' => '10.242.2.1/24', 'network' => '10.242.2.0', 'interface' => 'VLAN-1118-KYALSC', 'comment' => 'KYALSC-INT_new'],
            ['address' => '10.241.6.1/24', 'network' => '10.241.6.0', 'interface' => 'vlan_207_Cancer-Center', 'comment' => 'HIS_Cancer-Center'],
            ['address' => '10.241.5.1/24', 'network' => '10.241.5.0', 'interface' => 'vlan_206_IPD - NS_Doctors_others', 'comment' => 'IPD-NS_Doctors_HIS'],

            // Image 7: 10.241.x, 10.200.x, 10.90, 10.80, 10.70.x, 10.60.x
            ['address' => '10.241.3.1/24', 'network' => '10.241.3.0', 'interface' => 'vlan_200_OPD', 'comment' => 'OPD_HIS'],
            ['address' => '10.241.2.1/24', 'network' => '10.241.2.0', 'interface' => 'vlan_203_Central_Store_Pharmacy', 'comment' => 'Central Store and Central Pharmacy(HIS)'],
            ['address' => '10.241.1.1/24', 'network' => '10.241.1.0', 'interface' => 'vlan_204_Laboratory_Service', 'comment' => 'Laboratory Service(HIS)'],
            ['address' => '10.241.0.1/24', 'network' => '10.241.0.0', 'interface' => 'vlan_202_Radiology_&_Imaging', 'comment' => 'HIS_Radiology & Imaging'],
            ['address' => '10.200.200.62/30', 'network' => '10.200.200.60', 'interface' => '0.4-AT-DATA-899', 'comment' => 'ATI-to-Agni-Data'],
            ['address' => '10.90.18.1/23', 'network' => '10.90.18.0', 'interface' => 'VLAN-1137-KYAMCH-Medical-Collage-Library', 'comment' => 'Medical College Library Subnet'],
            ['address' => '10.80.18.1/23', 'network' => '10.80.18.0', 'interface' => 'VLAN-1136-KYAMCH-Medical-Collage', 'comment' => 'Medical College Academic Subnet'],
            ['address' => '10.70.107.1/24', 'network' => '10.70.107.0', 'interface' => 'VLAN-1134-NURSE-DORMITORY', 'comment' => 'Nurse Dormitory Subnet'],
            ['address' => '10.70.106.1/24', 'network' => '10.70.106.0', 'interface' => 'VLAN-1133-KYAU-STUDENT-HOSTEL-FEMALE', 'comment' => 'KYAU Female Student Hostel Subnet'],
            ['address' => '10.70.105.1/24', 'network' => '10.70.105.0', 'interface' => 'VLAN-1132-KYAU-STUDENT-HOSTEL-MALE', 'comment' => 'Jamuna Hall / Male Student Hostel'],
            ['address' => '10.70.104.1/24', 'network' => '10.70.104.0', 'interface' => 'VLAN-1131-KYANC-STUDENT-HOSTEL-FEMALE', 'comment' => 'VLAN-1123-KYAMC_STD_Female / KYANC Female Hostel'],
            ['address' => '10.70.101.1/24', 'network' => '10.70.101.0', 'interface' => 'VLAN-1128-KYAMCH-DOCTORS-DORMITORY-MALE', 'comment' => 'Male Doctors Dormitory Subnet'],
            ['address' => '10.70.100.1/24', 'network' => '10.70.100.0', 'interface' => 'VLAN-1129-KYAMCH-DOCTORS-DORMITORY-FEMALE', 'comment' => 'Female Doctors Dormitory Subnet'],
            ['address' => '10.70.99.1/24', 'network' => '10.70.99.0', 'interface' => 'VLAN-1127-KYAMCH-INTERN-HOSTEL-FEMALE', 'comment' => 'Intern Female Doctors Hostel Subnet'],
            ['address' => '10.70.98.1/24', 'network' => '10.70.98.0', 'interface' => 'VLAN-1126-KYAMCH-INTERN-HOSTEL-MALE', 'comment' => '30 No Building / Intern Male Hostel Subnet'],
            ['address' => '10.70.97.1/24', 'network' => '10.70.97.0', 'interface' => 'VLAN-1125-KYAMC-INTERNATIONAL-STUDENT-HOSTEL-FEMALE', 'comment' => 'Foreign_SDT_Female Subnet'],
            ['address' => '10.70.96.1/24', 'network' => '10.70.96.0', 'interface' => 'VLAN-1124-KYAMC-INTERNATIONAL-STUDENT-HOSTEL-MALE', 'comment' => 'Foreign_SDT_Male Subnet'],
            ['address' => '10.70.95.1/24', 'network' => '10.70.95.0', 'interface' => 'VLAN-1123-KYAMC-STUDENT-HOSTEL-FEMALE', 'comment' => 'VLAN-1123-KYAMC_STD_Male / Female Hostel Subnet'],
            ['address' => '10.70.94.1/24', 'network' => '10.70.94.0', 'interface' => 'VLAN-1122-KYAMC-STUDENT-HOSTEL-MALE', 'comment' => 'Male Student Hostel Subnet'],
            ['address' => '10.70.35.1/24', 'network' => '10.70.35.0', 'interface' => 'VLAN-1135-KYAMCH-AUDITORIAM', 'comment' => 'KYAMCH Auditorium Subnet 70'],
            ['address' => '10.70.19.1/24', 'network' => '10.70.19.0', 'interface' => 'VLAN-1119-KYAMC', 'comment' => 'KYAMC Internet_new?'],
            ['address' => '10.70.8.1/24', 'network' => '10.70.8.0', 'interface' => 'VLAN 2708 - TAB_HIS', 'comment' => 'TAB_HIS_IP PHONE & Others Application with Internet'],
            ['address' => '10.70.6.1/24', 'network' => '10.70.6.0', 'interface' => 'vlan_207_Cancer-Center', 'comment' => 'Cancer Center Subnet 70'],
            ['address' => '10.60.35.1/24', 'network' => '10.60.35.0', 'interface' => 'VLAN-1135-KYAMCH-AUDITORIAM', 'comment' => 'KYAMCH_Auditorium_All Service'],
            ['address' => '10.60.19.1/24', 'network' => '10.60.19.0', 'interface' => 'VLAN-1119-KYAMC', 'comment' => 'KYAMC_All Service'],

            // Image 8: 10.60.x, 10.50.x, 10.40.x, 10.30.x
            ['address' => '10.60.17.1/24', 'network' => '10.60.17.0', 'interface' => 'VLAN-1117-KYANC', 'comment' => 'KYANC _ ALL_Services'],
            ['address' => '10.60.15.1/24', 'network' => '10.60.15.0', 'interface' => 'VLAN 2615 - All Services (7 No Buiding)', 'comment' => 'VLAN 2615 - All Services (7 No Building)'],
            ['address' => '10.50.23.1/24', 'network' => '10.50.23.0', 'interface' => 'VLAN-1123-KYAMC-STUDENT-HOSTEL-FEMALE', 'comment' => 'VLAN-1123-KYAMC - Door Access'],
            ['address' => '10.50.19.1/24', 'network' => '10.50.19.0', 'interface' => 'VLAN-1119-KYAMC', 'comment' => 'KYAMC Door_Access'],
            ['address' => '10.50.17.1/24', 'network' => '10.50.17.0', 'interface' => 'VLAN-1117-KYANC', 'comment' => 'KYANC - Door Access'],
            ['address' => '10.50.3.1/24', 'network' => '10.50.3.0', 'interface' => 'VLAN-1103-KYAMCH-ACC-HR', 'comment' => 'DOOR ACCESS - ACC & HR'],
            ['address' => '10.40.41.1/24', 'network' => '10.40.41.0', 'interface' => 'VLAN2401-MGBD-IPCAM', 'comment' => 'MGBD Surveillance IPCAM'],
            ['address' => '10.40.32.1/24', 'network' => '10.40.32.0', 'interface' => 'VLAN2302-IT-DEP-IPPHONE', 'comment' => 'IT Department IP Phones'],
            ['address' => '10.40.23.1/24', 'network' => '10.40.23.0', 'interface' => 'VLAN-1123-KYAMC-STUDENT-HOSTEL-FEMALE', 'comment' => 'VLAN-1123-KYAMC - Camera'],
            ['address' => '10.40.19.1/24', 'network' => '10.40.19.0', 'interface' => 'VLAN-1119-KYAMC', 'comment' => 'KYAMC_CAM - New'],
            ['address' => '10.40.17.1/24', 'network' => '10.40.17.0', 'interface' => 'VLAN-1117-KYANC', 'comment' => 'KYANC - Camera'],
            ['address' => '10.40.15.1/24', 'network' => '10.40.15.0', 'interface' => 'VLAN-1115-RESIDENTIAL-BUILDING', 'comment' => 'RES_IP_CAMERA'],
            ['address' => '10.40.3.1/24', 'network' => '10.40.3.0', 'interface' => 'VLAN-1103-KYAMCH-ACC-HR', 'comment' => 'IP CAMERA - ACC & HR'],
            ['address' => '10.30.17.1/24', 'network' => '10.30.17.0', 'interface' => 'VLAN-1117-KYANC', 'comment' => 'KYANC-IPPBX'],
            ['address' => '10.30.15.1/24', 'network' => '10.30.15.0', 'interface' => 'VLAN-1115-RESIDENTIAL-BUILDING', 'comment' => 'RES_IP_PHONE'],
            ['address' => '10.30.3.1/24', 'network' => '10.30.3.0', 'interface' => 'VLAN-1103-KYAMCH-ACC-HR', 'comment' => 'IP PHONE - ACC & HR'],

            // Image 9: 10.22, 10.20, 10.10
            ['address' => '10.22.0.1/30', 'network' => '10.22.0.0', 'interface' => 'KYAU-DATA Link - P2P_VLAN3', 'comment' => 'KYAU - DATA Link - P2P'],
            ['address' => '10.20.17.1/24', 'network' => '10.20.17.0', 'interface' => 'VLAN-1117-KYANC', 'comment' => 'HIS & Others Application with Internet (KYANC)'],
            ['address' => '10.20.3.1/24', 'network' => '10.20.3.0', 'interface' => 'VLAN-1103-KYAMCH-ACC-HR', 'comment' => 'HIS & Others Application with Internet (ACC-HR)'],
            ['address' => '10.10.35.1/24', 'network' => '10.10.35.0', 'interface' => 'VLAN-1135-KYAMCH-AUDITORIAM', 'comment' => 'HIS & Others Application (Auditorium)'],
            ['address' => '10.10.3.1/24', 'network' => '10.10.3.0', 'interface' => 'VLAN-1103-KYAMCH-ACC-HR', 'comment' => 'HIS & Others Application (ACC-HR)'],
        ];

        foreach ($activeEntries as $entry) {
            $mask = explode('/', $entry['address'])[1] ?? '32';
            $cidr = $entry['network'] . '/' . $mask;
            $prefix = IpPrefix::parse($cidr);
            $canonicalCidr = $prefix->cidr();

            if (!Subnet::where('cidr', $canonicalCidr)->exists()) {
                $gateway = explode('/', $entry['address'])[0];
                $netmask = $cidrToNetmask($prefix->prefixLength());

                // Resolve VLAN
                $vlan = $allVlans->get($entry['interface']);
                if (! $vlan) {
                    foreach ($allVlans as $name => $v) {
                        if (str_starts_with($name, $entry['interface']) || str_starts_with($entry['interface'], $name)) {
                            $vlan = $v;
                            break;
                        }
                    }
                }

                // Resolve parent in aggregate tree
                $parentCidr = $findParentForCidr($canonicalCidr);
                $parentSubnet = $parentCidr ? ($createdSubnets[$parentCidr] ?? null) : null;

                $name = !empty($entry['comment']) ? $entry['comment'] : "Subnet {$canonicalCidr}";
                $description = "MikroTik {$entry['interface']} | Gateway: {$gateway} | Comment: {$entry['comment']}";

                $subnetAction->execute([
                    'site_id' => $mainSite?->id,
                    'parent_id' => $parentSubnet?->id,
                    'vlan_id' => $vlan?->id,
                    'name' => $name,
                    'cidr' => $canonicalCidr,
                    'gateway' => $gateway,
                    'subnet_mask' => $netmask,
                    'subnet_type' => SubnetType::ASSIGNABLE,
                    'description' => $description,
                    'allocation_enabled' => true,
                    'status' => SubnetStatus::ACTIVE,
                ]);
            }
        }

        AuditEvent::record(
            eventType: 'system.seeded',
            reason: 'Production IPAM initialized with 94 enabled MikroTik VLANs and complete Subnet Tree hierarchy.',
            actor: $admin
        );
    }
}
