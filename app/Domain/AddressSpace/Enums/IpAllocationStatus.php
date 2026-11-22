<?php

declare(strict_types=1);

namespace App\Domain\AddressSpace\Enums;

enum IpAllocationStatus: string
{
    case UNASSIGNED = 'unassigned';
    case ASSIGNED = 'assigned';
    case RESERVED = 'reserved';
    case QUARANTINED = 'quarantined';
    case BLOCKED = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::UNASSIGNED => 'Available / Unassigned',
            self::ASSIGNED => 'Authoritatively Assigned',
            self::RESERVED => 'Reserved for Future Allocation',
            self::QUARANTINED => 'Quarantined (Post-Release Hold)',
            self::BLOCKED => 'Blocked / Security Hold',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::UNASSIGNED => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            self::ASSIGNED => 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border-blue-200 dark:border-blue-800 font-semibold',
            self::RESERVED => 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border-purple-200 dark:border-purple-800',
            self::QUARANTINED => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border-amber-200 dark:border-amber-800',
            self::BLOCKED => 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 border-rose-200 dark:border-rose-800',
        };
    }

    public function isAllocated(): bool
    {
        return in_array($this, [self::ASSIGNED, self::RESERVED, self::QUARANTINED, self::BLOCKED], true);
    }
}
