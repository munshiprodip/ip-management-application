<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Safe IPAM Feature Flags (Mandatory Non-Negotiable Invariants)
    |--------------------------------------------------------------------------
    |
    | Default global safety switch: IPAM_DISCOVERY_ENABLED=false
    | Network scans and discovery cannot run without explicit operator enablement.
    | External writes (DHCP/DNS configuration modifications) are strictly locked.
    |
    */
    'discovery_enabled' => (bool) env('IPAM_DISCOVERY_ENABLED', false),
    'discovery_allow_live_targets' => (bool) env('IPAM_DISCOVERY_ALLOW_LIVE_TARGETS', false),
    'external_writes_enabled' => (bool) env('IPAM_EXTERNAL_WRITES_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Institutional Identity
    |--------------------------------------------------------------------------
    */
    'organization_name' => env('IPAM_ORG_NAME', 'Khwaja Yunus Ali Medical College & Hospital (KYAMCH)'),
    'system_code' => 'KYAMCH-IPAM',
    'default_timezone' => env('IPAM_DEFAULT_TIMEZONE', 'Asia/Dhaka'),

    /*
    |--------------------------------------------------------------------------
    | Default Routing Context (Mandatory Non-Null Default VRF)
    |--------------------------------------------------------------------------
    */
    'default_vrf_name' => 'Default VRF',
    'default_vrf_code' => 'VRF-GLOBAL',

    /*
    |--------------------------------------------------------------------------
    | Allocation, Quarantine & Concurrency Rules
    |--------------------------------------------------------------------------
    */
    'quarantine_duration_days' => (int) env('IPAM_QUARANTINE_DAYS', 14),
    'allocation_lock_timeout_seconds' => (int) env('IPAM_ALLOCATION_LOCK_TIMEOUT', 10),
    'max_allocation_retries' => (int) env('IPAM_MAX_ALLOCATION_RETRIES', 5),

    /*
    |--------------------------------------------------------------------------
    | Address Family Support
    |--------------------------------------------------------------------------
    */
    'supported_families' => [
        4 => 'IPv4',
        6 => 'IPv6',
    ],
];
