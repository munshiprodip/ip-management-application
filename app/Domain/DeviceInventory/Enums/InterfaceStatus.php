<?php

declare(strict_types=1);

namespace App\Domain\DeviceInventory\Enums;

enum InterfaceStatus: string
{
    case UP = 'up';
    case DOWN = 'down';
    case DISABLED = 'disabled';
    case PLANNED = 'planned';

    public function label(): string
    {
        return match ($this) {
            self::UP => 'Up / Operational',
            self::DOWN => 'Down / Inactive',
            self::DISABLED => 'Administratively Disabled',
            self::PLANNED => 'Planned',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::UP => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            self::DOWN => 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 border-rose-200 dark:border-rose-800',
            self::DISABLED => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400 border-slate-300 dark:border-slate-700',
            self::PLANNED => 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border-blue-200 dark:border-blue-800',
        };
    }
}
