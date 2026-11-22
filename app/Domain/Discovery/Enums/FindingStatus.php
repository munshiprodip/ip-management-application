<?php

declare(strict_types=1);

namespace App\Domain\Discovery\Enums;

enum FindingStatus: string
{
    case OPEN = 'open';
    case ACKNOWLEDGED = 'acknowledged';
    case RESOLVED = 'resolved';
    case IGNORED = 'ignored';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::ACKNOWLEDGED => 'Acknowledged',
            self::RESOLVED => 'Resolved',
            self::IGNORED => 'Ignored',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::OPEN => 'bg-rose-100 text-rose-800 border-rose-200 dark:bg-rose-900/40 dark:text-rose-300 dark:border-rose-800',
            self::ACKNOWLEDGED => 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900/40 dark:text-amber-300 dark:border-amber-800',
            self::RESOLVED => 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-800',
            self::IGNORED => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
        };
    }
}
