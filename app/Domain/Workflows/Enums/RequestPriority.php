<?php

declare(strict_types=1);

namespace App\Domain\Workflows\Enums;

enum RequestPriority: string
{
    case LOW = 'low';
    case NORMAL = 'normal';
    case URGENT = 'urgent';
    case EMERGENCY = 'emergency';

    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Low',
            self::NORMAL => 'Normal',
            self::URGENT => 'Urgent',
            self::EMERGENCY => 'Emergency',
        };
    }

    public function slaHours(): int
    {
        return match ($this) {
            self::EMERGENCY => 2,
            self::URGENT => 8,
            self::NORMAL => 48,
            self::LOW => 120,
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::LOW => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
            self::NORMAL => 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/40 dark:text-blue-300 dark:border-blue-800',
            self::URGENT => 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900/40 dark:text-amber-300 dark:border-amber-800',
            self::EMERGENCY => 'bg-rose-100 text-rose-800 border-rose-200 dark:bg-rose-900/40 dark:text-rose-300 dark:border-rose-800 font-bold animate-pulse',
        };
    }
}

