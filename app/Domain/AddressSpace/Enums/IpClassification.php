<?php

declare(strict_types=1);

namespace App\Domain\AddressSpace\Enums;

enum IpClassification: string
{
    case STATIC_HOST = 'static_host';
    case DHCP_RESERVED = 'dhcp_reserved';
    case GATEWAY = 'gateway';
    case VIP = 'vip';
    case LOOPBACK = 'loopback';
    case MANAGEMENT = 'management';
    case INFRASTRUCTURE = 'infrastructure';

    public function label(): string
    {
        return match ($this) {
            self::STATIC_HOST => 'Static Host Address',
            self::DHCP_RESERVED => 'DHCP Static Reservation',
            self::GATEWAY => 'Default Gateway / Router Interface',
            self::VIP => 'Virtual IP (HSRP/VRRP/Cluster VIP)',
            self::LOOPBACK => 'Loopback / Management Anchor',
            self::MANAGEMENT => 'Out-of-Band Management (OOBM)',
            self::INFRASTRUCTURE => 'Network Infrastructure Link',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::STATIC_HOST => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-300 dark:border-slate-700',
            self::DHCP_RESERVED => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-950 dark:text-cyan-300 border-cyan-200 dark:border-cyan-800',
            self::GATEWAY => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border-amber-200 dark:border-amber-800 font-semibold',
            self::VIP => 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border-purple-200 dark:border-purple-800',
            self::LOOPBACK => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800',
            self::MANAGEMENT => 'bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-300 border-teal-200 dark:border-teal-800',
            self::INFRASTRUCTURE => 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border-blue-200 dark:border-blue-800',
        };
    }
}

