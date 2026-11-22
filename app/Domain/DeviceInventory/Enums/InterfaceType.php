<?php

declare(strict_types=1);

namespace App\Domain\DeviceInventory\Enums;

enum InterfaceType: string
{
    case PHYSICAL = 'physical';
    case VIRTUAL = 'virtual';
    case LOOPBACK = 'loopback';
    case MANAGEMENT = 'management';
    case SUBINTERFACE = 'subinterface';
    case AGGREGATED = 'aggregated';

    public function label(): string
    {
        return match ($this) {
            self::PHYSICAL => 'Physical NIC / Port',
            self::VIRTUAL => 'Virtual Interface (vNIC / SVI)',
            self::LOOPBACK => 'Loopback / Router ID',
            self::MANAGEMENT => 'Out-of-Band Management (OOBM / iDRAC / ILO)',
            self::SUBINTERFACE => 'VLAN Sub-Interface (802.1Q tagged)',
            self::AGGREGATED => 'Bond / LACP Port-Channel',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::PHYSICAL => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            self::VIRTUAL => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800',
            self::LOOPBACK => 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border-purple-200 dark:border-purple-800',
            self::MANAGEMENT => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border-amber-200 dark:border-amber-800',
            self::SUBINTERFACE => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-950 dark:text-cyan-300 border-cyan-200 dark:border-cyan-800',
            self::AGGREGATED => 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border-blue-200 dark:border-blue-800',
        };
    }

    public function canHaveMac(): bool
    {
        return in_array($this, [self::PHYSICAL, self::VIRTUAL, self::MANAGEMENT, self::AGGREGATED], true);
    }
}

