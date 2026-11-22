<?php

declare(strict_types=1);

namespace App\Domain\AddressSpace\Enums;

enum PoolType: string
{
    case STATIC = 'static';
    case DHCP_DYNAMIC = 'dhcp_dynamic';
    case DHCP_RESERVED = 'dhcp_reserved';
    case VPN_POOL = 'vpn_pool';
    case QUARANTINE = 'quarantine';

    public function label(): string
    {
        return match ($this) {
            self::STATIC => 'Static Assignment Pool',
            self::DHCP_DYNAMIC => 'DHCP Dynamic Pool (Excluded from Static Allocation)',
            self::DHCP_RESERVED => 'DHCP Static Reservation Pool',
            self::VPN_POOL => 'VPN / Dial-in Client Pool',
            self::QUARANTINE => 'Quarantine Isolation Pool',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::STATIC => 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border-blue-200 dark:border-blue-800',
            self::DHCP_DYNAMIC => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border-amber-200 dark:border-amber-800',
            self::DHCP_RESERVED => 'bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-300 border-teal-200 dark:border-teal-800',
            self::VPN_POOL => 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border-purple-200 dark:border-purple-800',
            self::QUARANTINE => 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 border-rose-200 dark:border-rose-800',
        };
    }

    public function allowsStaticAllocation(): bool
    {
        return in_array($this, [self::STATIC, self::DHCP_RESERVED], true);
    }
}
