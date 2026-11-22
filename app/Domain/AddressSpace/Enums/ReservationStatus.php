<?php

declare(strict_types=1);

namespace App\Domain\AddressSpace\Enums;

enum ReservationStatus: string
{
    case ACTIVE = 'active';
    case FULFILLED = 'fulfilled';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active Reservation',
            self::FULFILLED => 'Fulfilled / Converted to Assignment',
            self::CANCELLED => 'Cancelled by Operator',
            self::EXPIRED => 'Expired',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::ACTIVE => 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border-purple-200 dark:border-purple-800 font-semibold',
            self::FULFILLED => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            self::CANCELLED => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400 border-slate-300 dark:border-slate-700',
            self::EXPIRED => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border-amber-200 dark:border-amber-800',
        };
    }
}
