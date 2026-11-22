<?php

declare(strict_types=1);

namespace App\Domain\Layer2\Enums;

enum VlanStatus: string
{
    case ACTIVE = 'active';
    case RESERVED = 'reserved';
    case DEPRECATED = 'deprecated';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::RESERVED => 'Reserved',
            self::DEPRECATED => 'Deprecated',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::ACTIVE => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            self::RESERVED => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border-amber-200 dark:border-amber-800',
            self::DEPRECATED => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400 border-slate-300 dark:border-slate-700',
        };
    }
}

