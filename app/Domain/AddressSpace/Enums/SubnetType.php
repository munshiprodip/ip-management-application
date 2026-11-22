<?php

declare(strict_types=1);

namespace App\Domain\AddressSpace\Enums;

enum SubnetType: string
{
    case AGGREGATE = 'aggregate';
    case ASSIGNABLE = 'assignable';
    case POINT_TO_POINT = 'point_to_point';
    case LOOPBACK = 'loopback';

    public function label(): string
    {
        return match ($this) {
            self::AGGREGATE => 'Aggregate Container (Non-Allocating)',
            self::ASSIGNABLE => 'Assignable Leaf Subnet',
            self::POINT_TO_POINT => 'Point-to-Point Link (/31 or /127)',
            self::LOOPBACK => 'Loopback / Management Host (/32 or /128)',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::AGGREGATE => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800',
            self::ASSIGNABLE => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            self::POINT_TO_POINT => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border-amber-200 dark:border-amber-800',
            self::LOOPBACK => 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border-purple-200 dark:border-purple-800',
        };
    }

    public function canAllocate(): bool
    {
        return in_array($this, [self::ASSIGNABLE, self::POINT_TO_POINT, self::LOOPBACK], true);
    }
}
