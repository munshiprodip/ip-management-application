<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ApiToken;
use App\Models\Integration;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;

class Stage8Seeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@kyamch.local')->first();
        $site = Site::first();

        // 1. Seed Scoped Admin / NOC API Token
        if ($admin && ApiToken::where('name', 'NOC Orchestration Token')->doesntExist()) {
            ApiToken::createToken(
                name: 'NOC Orchestration Token',
                scopes: ['*'],
                user: $admin,
                expiresInDays: 365,
                rateLimit: 120
            );
        }

        // 2. Seed Collector Agent Token
        if ($admin && ApiToken::where('name', 'HQ Collector Agent Token')->doesntExist()) {
            ApiToken::createToken(
                name: 'HQ Collector Agent Token',
                scopes: ['read:subnets', 'write:discovery'],
                user: $admin,
                expiresInDays: 365,
                rateLimit: 300
            );
        }

        // 3. Seed Read-Only DHCP Integration
        if (Integration::where('name', 'Hospital Core DHCP Server')->doesntExist()) {
            Integration::create([
                'type' => 'dhcp_server',
                'name' => 'Hospital Core DHCP Server',
                'site_id' => $site?->id,
                'config_json' => [
                    'server_ip' => '10.10.1.15',
                    'scope_range' => '10.10.10.0/24',
                    'fixtures' => [
                        [
                            'ip_address' => '10.10.10.10',
                            'mac_address' => '00:1A:2B:3C:4D:10',
                            'hostname' => 'nurse-desk-01.kyamch.local',
                        ],
                        [
                            'ip_address' => '10.10.10.11',
                            'mac_address' => '00:1A:2B:3C:4D:11',
                            'hostname' => 'nurse-desk-02.kyamch.local',
                        ],
                        [
                            'ip_address' => '10.10.10.99',
                            'mac_address' => '00:1A:2B:3C:4D:99',
                            'hostname' => 'unregistered-laptop.kyamch.local',
                        ],
                    ],
                ],
                'credential_ref' => 'vault:secrets/dhcp_api_key',
                'enabled' => true,
                'status' => 'idle',
            ]);
        }

        // 4. Seed Read-Only DNS Integration
        if (Integration::where('name', 'Enterprise Active Directory DNS')->doesntExist()) {
            Integration::create([
                'type' => 'dns_server',
                'name' => 'Enterprise Active Directory DNS',
                'site_id' => $site?->id,
                'config_json' => [
                    'server_ip' => '10.10.1.10',
                    'zone' => 'kyamch.local',
                    'fixtures' => [
                        [
                            'ip_address' => '10.10.10.1',
                            'hostname' => 'gateway-icu.kyamch.local',
                            'record_type' => 'PTR',
                        ],
                    ],
                ],
                'credential_ref' => 'vault:secrets/dns_api_key',
                'enabled' => true,
                'status' => 'idle',
            ]);
        }
    }
}
