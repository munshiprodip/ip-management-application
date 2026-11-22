<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\DeviceInventory\CreateDeviceAction;
use App\Actions\DeviceInventory\CreateInterfaceAction;
use App\Domain\DeviceInventory\Enums\DeviceCategory;
use App\Domain\DeviceInventory\Enums\DeviceCriticality;
use App\Domain\DeviceInventory\Enums\DeviceStatus;
use App\Domain\DeviceInventory\Enums\InterfaceStatus;
use App\Domain\DeviceInventory\Enums\InterfaceType;
use App\Models\Department;
use App\Models\Device;
use App\Models\DeviceModel;
use App\Models\DeviceType;
use App\Models\Manufacturer;
use App\Models\Site;
use App\Models\Vlan;
use Illuminate\Database\Seeder;

class Stage3Seeder extends Seeder
{
    public function run(): void
    {
        // 1. Hardware Manufacturers
        $cisco = Manufacturer::firstOrCreate(['slug' => 'cisco'], ['name' => 'Cisco Systems', 'website' => 'https://cisco.com']);
        $fortinet = Manufacturer::firstOrCreate(['slug' => 'fortinet'], ['name' => 'Fortinet Inc.', 'website' => 'https://fortinet.com']);
        $ge = Manufacturer::firstOrCreate(['slug' => 'ge-healthcare'], ['name' => 'GE Healthcare', 'website' => 'https://gehealthcare.com']);
        $philips = Manufacturer::firstOrCreate(['slug' => 'philips-healthcare'], ['name' => 'Philips Healthcare', 'website' => 'https://philips.com/healthcare']);
        $siemens = Manufacturer::firstOrCreate(['slug' => 'siemens-healthineers'], ['name' => 'Siemens Healthineers', 'website' => 'https://siemens-healthineers.com']);
        $dell = Manufacturer::firstOrCreate(['slug' => 'dell'], ['name' => 'Dell Technologies', 'website' => 'https://dell.com']);

        // 2. Device Types
        $coreSwitchType = DeviceType::firstOrCreate(
            ['code' => 'CORE-SW'],
            ['name' => 'Core Network Switch', 'category' => DeviceCategory::NETWORK, 'description' => 'High-capacity backbone chassis and aggregation switches.']
        );
        $firewallType = DeviceType::firstOrCreate(
            ['code' => 'FW-NGFW'],
            ['name' => 'Next-Gen Firewall', 'category' => DeviceCategory::SECURITY, 'description' => 'Perimeter and internal segmentation security gateways.']
        );
        $modalityType = DeviceType::firstOrCreate(
            ['code' => 'MED-MOD'],
            ['name' => 'Medical Diagnostic Modality', 'category' => DeviceCategory::MEDICAL, 'description' => 'Diagnostic imaging scanners (CT, MRI, X-Ray, CathLab).']
        );
        $patientMonitorType = DeviceType::firstOrCreate(
            ['code' => 'MED-MON'],
            ['name' => 'ICU Patient Physiological Monitor', 'category' => DeviceCategory::MEDICAL, 'description' => 'Bedside and central cardiac telemetry units.']
        );
        $serverType = DeviceType::firstOrCreate(
            ['code' => 'SRV-COMP'],
            ['name' => 'Enterprise Compute Server', 'category' => DeviceCategory::SERVER, 'description' => 'Virtualization hosts and bare-metal database nodes.']
        );

        // 3. Hardware Models
        $c9500 = DeviceModel::firstOrCreate(
            ['manufacturer_id' => $cisco->id, 'name' => 'Catalyst 9500-24Y4C'],
            ['device_type_id' => $coreSwitchType->id, 'model_number' => 'C9500-24Y4C']
        );
        $fg600f = DeviceModel::firstOrCreate(
            ['manufacturer_id' => $fortinet->id, 'name' => 'FortiGate 600F'],
            ['device_type_id' => $firewallType->id, 'model_number' => 'FG-600F']
        );
        $geCt = DeviceModel::firstOrCreate(
            ['manufacturer_id' => $ge->id, 'name' => 'Revolution Apex CT 512'],
            ['device_type_id' => $modalityType->id, 'model_number' => 'REV-APEX-512']
        );
        $philipsMx800 = DeviceModel::firstOrCreate(
            ['manufacturer_id' => $philips->id, 'name' => 'IntelliVue MX800 Bedside'],
            ['device_type_id' => $patientMonitorType->id, 'model_number' => 'MX800-ICU']
        );
        $dellR760 = DeviceModel::firstOrCreate(
            ['manufacturer_id' => $dell->id, 'name' => 'PowerEdge R760 Rack Server'],
            ['device_type_id' => $serverType->id, 'model_number' => 'PER760-2U']
        );

        // Look up campus and departments
        $mainSite = Site::where('code', 'MAIN-CAMPUS')->first() ?? Site::first();
        if (! $mainSite) {
            return;
        }

        $itDept = Department::where('code', 'IT-NET')->first() ?? Department::first();
        $radDept = Department::where('code', 'RAD-IMG')->first() ?? Department::first();
        $vlan10 = Vlan::where('vlan_id', 10)->first();
        $vlan20 = Vlan::where('vlan_id', 20)->first();
        $vlan30 = Vlan::where('vlan_id', 30)->first();

        $createDevice = app(CreateDeviceAction::class);
        $createInterface = app(CreateInterfaceAction::class);

        // 4. Register Sample Hospital Devices & Multi-NIC Interfaces

        // Device A: Campus Core Switch 1
        if (! Device::where('code', 'KY-CORE-SW01')->exists()) {
            $coreSw = $createDevice->execute([
                'code' => 'KY-CORE-SW01',
                'name' => 'KYAMCH Main Core Switch 01',
                'hostname' => 'core-sw01.net.kyamch.local',
                'device_type_id' => $coreSwitchType->id,
                'manufacturer_id' => $cisco->id,
                'device_model_id' => $c9500->id,
                'asset_tag' => 'KY-AST-0101',
                'serial_number' => 'FOC2405ABCD',
                'site_id' => $mainSite->id,
                'department_id' => $itDept?->id,
                'rack_location' => 'Data Center 1, Rack A01, U40-U42',
                'criticality' => DeviceCriticality::MISSION_CRITICAL,
                'status' => DeviceStatus::ACTIVE,
                'environment' => 'production',
                'os_firmware' => 'Cisco IOS-XE 17.9.4a',
                'is_monitored' => true,
                'notes' => 'Central Layer-3 routing chassis for Sirajganj hospital campus.',
            ]);

            $createInterface->execute($coreSw, [
                'name' => 'mgmt0',
                'type' => InterfaceType::MANAGEMENT,
                'mac_address' => '00:14:22:AA:00:01',
                'speed_mbps' => 1000,
                'status' => InterfaceStatus::UP,
                'description' => 'Dedicated Out-of-band management console port',
            ]);

            $createInterface->execute($coreSw, [
                'name' => 'Loopback0',
                'type' => InterfaceType::LOOPBACK,
                'status' => InterfaceStatus::UP,
                'description' => 'OSPF / BGP Router-ID Anchor interface',
            ]);

            $createInterface->execute($coreSw, [
                'name' => 'TenGigabitEthernet1/0/1',
                'type' => InterfaceType::PHYSICAL,
                'mac_address' => '00:14:22:AA:01:01',
                'speed_mbps' => 10000,
                'switch_port_label' => 'UPLINK-TO-FIREWALL-01',
                'status' => InterfaceStatus::UP,
                'description' => 'Primary routed uplink to perimeter firewall cluster',
            ]);

            $createInterface->execute($coreSw, [
                'name' => 'Vlan10',
                'type' => InterfaceType::VIRTUAL,
                'vlan_id' => $vlan10?->id,
                'status' => InterfaceStatus::UP,
                'description' => 'SVI Gateway for Radiology Imaging Modalities',
            ]);
        }

        // Device B: GE Revolution CT Scanner 01 (Life Safety, Multi-NIC)
        if (! Device::where('code', 'KY-RAD-CT01')->exists()) {
            $ct01 = $createDevice->execute([
                'code' => 'KY-RAD-CT01',
                'name' => 'GE Revolution Apex 512 CT Scanner 01',
                'hostname' => 'ct01.rad.kyamch.local',
                'device_type_id' => $modalityType->id,
                'manufacturer_id' => $ge->id,
                'device_model_id' => $geCt->id,
                'asset_tag' => 'KY-AST-0205',
                'serial_number' => 'GE-REV-998811',
                'site_id' => $mainSite->id,
                'department_id' => $radDept?->id,
                'rack_location' => 'Main Building, Floor 1, Radiology Suite CT-1',
                'criticality' => DeviceCriticality::LIFE_SAFETY,
                'status' => DeviceStatus::ACTIVE,
                'environment' => 'clinical_live',
                'os_firmware' => 'GE Healthcare HealthOS v4.2',
                'is_monitored' => true,
                'notes' => 'High-speed cardiac and neuro diagnostic scanner. Air-gapped medical VLAN.',
            ]);

            $createInterface->execute($ct01, [
                'name' => 'eth0',
                'type' => InterfaceType::PHYSICAL,
                'mac_address' => '00:50:56:A1:02:01',
                'vlan_id' => $vlan10?->id,
                'speed_mbps' => 1000,
                'switch_port_label' => 'RAD-SW01:Gi1/0/5',
                'status' => InterfaceStatus::UP,
                'description' => 'Primary Modality Acquisition & Console Link',
            ]);

            $createInterface->execute($ct01, [
                'name' => 'eth1',
                'type' => InterfaceType::PHYSICAL,
                'mac_address' => '00:50:56:A1:02:02',
                'vlan_id' => $vlan10?->id,
                'speed_mbps' => 10000,
                'switch_port_label' => 'RAD-SW01:Te1/0/23',
                'status' => InterfaceStatus::UP,
                'description' => '10GbE High-Throughput PACS Image Archive Export',
            ]);
        }

        // Device C: Philips IntelliVue MX800 Bedside Monitor (Life Safety)
        if (! Device::where('code', 'KY-ICU-MON01')->exists()) {
            $icuMon = $createDevice->execute([
                'code' => 'KY-ICU-MON01',
                'name' => 'Philips IntelliVue MX800 Bed 01',
                'hostname' => 'mon01.icu.kyamch.local',
                'device_type_id' => $patientMonitorType->id,
                'manufacturer_id' => $philips->id,
                'device_model_id' => $philipsMx800->id,
                'asset_tag' => 'KY-AST-0310',
                'serial_number' => 'PH-MX800-4422',
                'site_id' => $mainSite->id,
                'rack_location' => 'ICU Complex Floor 3, Bed Unit 01',
                'criticality' => DeviceCriticality::LIFE_SAFETY,
                'status' => DeviceStatus::ACTIVE,
                'environment' => 'clinical_live',
                'os_firmware' => 'Philips IntelliVue Firmware H.02',
                'is_monitored' => true,
                'notes' => 'Patient physiological monitoring station.',
            ]);

            $createInterface->execute($icuMon, [
                'name' => 'eth0',
                'type' => InterfaceType::PHYSICAL,
                'mac_address' => '00:1B:77:22:33:01',
                'vlan_id' => $vlan20?->id,
                'speed_mbps' => 100,
                'switch_port_label' => 'ICU-SW01:FastEthernet0/1',
                'status' => InterfaceStatus::UP,
                'description' => 'Real-time Cardiac & Vitals Telemetry Uplink',
            ]);
        }

        // Device D: Hospital Information System Database Node (Server)
        if (! Device::where('code', 'KY-HIS-DB01')->exists()) {
            $hisServer = $createDevice->execute([
                'code' => 'KY-HIS-DB01',
                'name' => 'Hospital Information System Database Server 01',
                'hostname' => 'his-db01.srv.kyamch.local',
                'device_type_id' => $serverType->id,
                'manufacturer_id' => $dell->id,
                'device_model_id' => $dellR760->id,
                'asset_tag' => 'KY-AST-0401',
                'serial_number' => '4X998877',
                'site_id' => $mainSite->id,
                'department_id' => $itDept?->id,
                'rack_location' => 'Data Center 1, Rack B04, U14-U16',
                'criticality' => DeviceCriticality::MISSION_CRITICAL,
                'status' => DeviceStatus::ACTIVE,
                'environment' => 'production',
                'os_firmware' => 'Ubuntu 24.04 LTS (Kernel 6.8)',
                'is_monitored' => true,
                'notes' => 'Primary electronic medical record (EMR/HIS) database server.',
            ]);

            $createInterface->execute($hisServer, [
                'name' => 'idrac0',
                'type' => InterfaceType::MANAGEMENT,
                'mac_address' => '18:66:DA:77:88:01',
                'speed_mbps' => 1000,
                'status' => InterfaceStatus::UP,
                'description' => 'Dell iDRAC9 Enterprise Remote Management Port',
            ]);

            $createInterface->execute($hisServer, [
                'name' => 'bond0',
                'type' => InterfaceType::AGGREGATED,
                'mac_address' => '18:66:DA:77:88:02',
                'speed_mbps' => 25000,
                'status' => InterfaceStatus::UP,
                'description' => 'Dual 25G LACP Aggregated Production Database Traffic',
            ]);
        }
    }
}

