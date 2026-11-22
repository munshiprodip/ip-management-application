<?php

declare(strict_types=1);

namespace App\Domain\AddressSpace\Enums;

enum VrfStatus: string
{
    case ACTIVE = 'active';
    case RETIRED = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::RETIRED => 'Retired',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::ACTIVE => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            self::RETIRED => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400 border-slate-300 dark:border-slate-700',
        };
    }
}
