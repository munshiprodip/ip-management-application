<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\AddressSpace\AllocateIpAction;
use App\Actions\AddressSpace\ReleaseIpAction;
use App\Domain\AddressSpace\Enums\AllocationStrategy;
use App\Domain\AddressSpace\Enums\IpClassification;
use App\Domain\AddressSpace\Enums\PoolType;
use App\Domain\AddressSpace\ValueObjects\IpAddress as IpAddressVO;
use App\Models\Device;
use App\Models\IpExclusion;
use App\Models\IpPool;
use App\Models\Subnet;
use App\Models\Vrf;
use Illuminate\Database\Seeder;

class Stage4Seeder extends Seeder
{
    public function run(): void
    {
        $globalVrf = Vrf::where('code', 'VRF-GLOBAL')->first();
        if (! $globalVrf) {
            return;
        }

        $radSubnet = Subnet::where('vrf_id', $globalVrf->id)->where('cidr', '10.10.10.0/24')->first();
        $icuSubnet = Subnet::where('vrf_id', $globalVrf->id)->where('cidr', '10.10.20.0/24')->first();

        if (! $radSubnet || ! $icuSubnet) {
            return;
        }

        // 1. Radiology Subnet: Default Gateway Exclusion
        $gwIp = IpAddressVO::parse('10.10.10.1');
        IpExclusion::firstOrCreate(
            ['subnet_id' => $radSubnet->id, 'start_address' => '10.10.10.1', 'end_address' => '10.10.10.1'],
            [
                'start_hex' => $gwIp->toHex(),
                'end_hex' => $gwIp->toHex(),
                'reason' => 'Default Gateway / SVI on Core Switch 1',
                'status' => 'active',
            ]
        );

        // 2. Radiology Subnet: Static Modalities Pool (10.10.10.10 - 10.10.10.100)
        $poolStart = IpAddressVO::parse('10.10.10.10');
        $poolEnd = IpAddressVO::parse('10.10.10.100');
        $modalityPool = IpPool::firstOrCreate(
            ['subnet_id' => $radSubnet->id, 'name' => 'Static Clinical Modalities Pool'],
            [
                'description' => 'Dedicated static addresses for CT, MRI, X-ray, and ultrasound equipment.',
                'start_address' => '10.10.10.10',
                'end_address' => '10.10.10.100',
                'start_hex' => $poolStart->toHex(),
                'end_hex' => $poolEnd->toHex(),
                'type' => PoolType::STATIC,
                'allocation_strategy' => AllocationStrategy::SEQUENTIAL,
                'status' => 'active',
                'priority' => 100,
            ]
        );

        // 3. Radiology Subnet: Dynamic DHCP Pool (10.10.10.101 - 10.10.10.200)
        $dhcpStart = IpAddressVO::parse('10.10.10.101');
        $dhcpEnd = IpAddressVO::parse('10.10.10.200');
        IpPool::firstOrCreate(
            ['subnet_id' => $radSubnet->id, 'name' => 'Mobile Modality DHCP Dynamic Pool'],
            [
                'description' => 'Dynamic DHCP pool for mobile C-arms and portable X-ray carts.',
                'start_address' => '10.10.10.101',
                'end_address' => '10.10.10.200',
                'start_hex' => $dhcpStart->toHex(),
                'end_hex' => $dhcpEnd->toHex(),
                'type' => PoolType::DHCP_DYNAMIC,
                'allocation_strategy' => AllocationStrategy::SEQUENTIAL,
                'status' => 'active',
                'priority' => 50,
            ]
        );

        // 4. ICU Subnet Gateway Exclusion
        $icuGw = IpAddressVO::parse('10.10.20.1');
        IpExclusion::firstOrCreate(
            ['subnet_id' => $icuSubnet->id, 'start_address' => '10.10.20.1', 'end_address' => '10.10.20.1'],
            [
                'start_hex' => $icuGw->toHex(),
                'end_hex' => $icuGw->toHex(),
                'reason' => 'ICU Default Gateway SVI',
                'status' => 'active',
            ]
        );

        // 5. Authoritative Allocations to Hospital Devices
        $allocateAction = app(AllocateIpAction::class);
        $releaseAction = app(ReleaseIpAction::class);

        // Bind CT Scanner Interfaces
        $ctDevice = Device::where('code', 'KY-RAD-CT01')->first();
        if ($ctDevice) {
            $eth0 = $ctDevice->interfaces()->where('name', 'eth0')->first();
            $eth1 = $ctDevice->interfaces()->where('name', 'eth1')->first();

            if ($eth0 && ! $radSubnet->ipAddresses()->where('address_text', '10.10.10.10')->exists()) {
                $allocateAction->execute([
                    'subnet_id' => $radSubnet->id,
                    'ip_address' => '10.10.10.10',
                    'interface_id' => $eth0->id,
                    'classification' => IpClassification::STATIC_HOST,
                    'hostname' => 'ct01-acq.rad.kyamch.local',
                    'description' => 'GE Revolution CT 512 Acquisition Control Console',
                ]);
            }

            if ($eth1 && ! $radSubnet->ipAddresses()->where('address_text', '10.10.10.11')->exists()) {
                $allocateAction->execute([
                    'subnet_id' => $radSubnet->id,
                    'ip_address' => '10.10.10.11',
                    'interface_id' => $eth1->id,
                    'classification' => IpClassification::STATIC_HOST,
                    'hostname' => 'ct01-pacs.rad.kyamch.local',
                    'description' => 'GE Revolution CT 512 10GbE High-Speed PACS Image Export',
                ]);
            }
        }

        // Bind ICU Patient Monitor
        $icuMon = Device::where('code', 'KY-ICU-MON01')->first();
        if ($icuMon) {
            $monEth0 = $icuMon->interfaces()->where('name', 'eth0')->first();
            if ($monEth0 && ! $icuSubnet->ipAddresses()->where('address_text', '10.10.20.10')->exists()) {
                $allocateAction->execute([
                    'subnet_id' => $icuSubnet->id,
                    'ip_address' => '10.10.20.10',
                    'interface_id' => $monEth0->id,
                    'classification' => IpClassification::STATIC_HOST,
                    'hostname' => 'mon01.icu.kyamch.local',
                    'description' => 'Philips IntelliVue MX800 Bed 01 Physiological Telemetry',
                ]);
            }
        }

        // Create a historical assignment and quarantine demo
        if (! $radSubnet->ipAddresses()->where('address_text', '10.10.10.99')->exists() && $ctDevice && $eth0) {
            $tempIp = $allocateAction->execute([
                'subnet_id' => $radSubnet->id,
                'ip_address' => '10.10.10.99',
                'interface_id' => $eth0->id,
                'classification' => IpClassification::STATIC_HOST,
                'description' => 'Decommissioned diagnostic laptop',
            ]);

            $releaseAction->execute($tempIp, 'Decommissioned and replaced with modern wireless telemetry cart', 3);
        }
    }
}

