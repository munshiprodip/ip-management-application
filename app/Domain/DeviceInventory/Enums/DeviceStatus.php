<?php

declare(strict_types=1);

namespace App\Domain\DeviceInventory\Enums;

enum DeviceStatus: string
{
    case PLANNED = 'planned';
    case ACTIVE = 'active';
    case MAINTENANCE = 'maintenance';
    case DECOMMISSIONED = 'decommissioned';
    case QUARANTINED = 'quarantined';

    public function label(): string
    {
        return match ($this) {
            self::PLANNED => 'Planned / Staged',
            self::ACTIVE => 'Active / Production',
            self::MAINTENANCE => 'Under Maintenance',
            self::DECOMMISSIONED => 'Decommissioned',
            self::QUARANTINED => 'Quarantined / Suspicious',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::PLANNED => 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border-blue-200 dark:border-blue-800',
            self::ACTIVE => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            self::MAINTENANCE => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border-amber-200 dark:border-amber-800',
            self::DECOMMISSIONED => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400 border-slate-300 dark:border-slate-700',
            self::QUARANTINED => 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 border-rose-200 dark:border-rose-800',
        };
    }
}

