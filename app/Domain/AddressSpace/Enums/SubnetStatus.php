<?php

declare(strict_types=1);

namespace App\Domain\AddressSpace\Enums;

enum SubnetStatus: string
{
    case PLANNED = 'planned';
    case ACTIVE = 'active';
    case DEPRECATED = 'deprecated';
    case RETIRED = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::PLANNED => 'Planned',
            self::ACTIVE => 'Active',
            self::DEPRECATED => 'Deprecated',
            self::RETIRED => 'Retired',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::PLANNED => 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border-blue-200 dark:border-blue-800',
            self::ACTIVE => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            self::DEPRECATED => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border-amber-200 dark:border-amber-800',
            self::RETIRED => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400 border-slate-300 dark:border-slate-700',
        };
    }
}
