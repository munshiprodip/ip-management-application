<?php

declare(strict_types=1);

namespace App\Domain\DeviceInventory\Enums;

enum DeviceCriticality: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case MISSION_CRITICAL = 'mission_critical';
    case LIFE_SAFETY = 'life_safety';

    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Low (Standard Dev/Test)',
            self::MEDIUM => 'Medium (Operational / Administrative)',
            self::HIGH => 'High (Department Core)',
            self::MISSION_CRITICAL => 'Mission Critical (Hospital Core / HIS / PACS)',
            self::LIFE_SAFETY => 'Life Safety (ICU / OT / Clinical Modality)',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::LOW => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-300 dark:border-slate-700',
            self::MEDIUM => 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border-blue-200 dark:border-blue-800',
            self::HIGH => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border-amber-200 dark:border-amber-800',
            self::MISSION_CRITICAL => 'bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300 border-orange-200 dark:border-orange-800',
            self::LIFE_SAFETY => 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300 border-red-200 dark:border-red-800 animate-pulse',
        };
    }

    public function isLifeSafety(): bool
    {
        return $this === self::LIFE_SAFETY;
    }
}

