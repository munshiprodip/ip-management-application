<?php

declare(strict_types=1);

namespace App\Domain\Alerts\Enums;

enum ExportStatus: string
{
    case QUEUED = 'queued';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::QUEUED => 'Processing...',
            self::COMPLETED => 'Ready for Download',
            self::FAILED => 'Generation Failed',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::QUEUED => 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900/40 dark:text-amber-300 dark:border-amber-800 animate-pulse',
            self::COMPLETED => 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-800',
            self::FAILED => 'bg-rose-100 text-rose-800 border-rose-200 dark:bg-rose-900/40 dark:text-rose-300 dark:border-rose-800',
        };
    }
}
