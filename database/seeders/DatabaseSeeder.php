<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Layer2\CreateVlanAction;
use App\Actions\Site\CreateSiteAction;
use App\Domain\Layer2\Enums\VlanSecurityZone;
use App\Domain\Security\Enums\UserRole;
use App\Models\AuditEvent;
use App\Models\Site;
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

        AuditEvent::record(
            eventType: 'system.seeded',
            reason: 'Production IPAM initialized with 94 enabled MikroTik VLANs.',
            actor: $admin
        );
    }
}
