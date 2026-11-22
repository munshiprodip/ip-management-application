<?php

declare(strict_types=1);

namespace App\Domain\Workflows\Enums;

enum RequestStatus: string
{
    case SUBMITTED = 'submitted';
    case PENDING_APPROVAL = 'pending_approval';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case PROVISIONED = 'provisioned';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::SUBMITTED => 'Submitted',
            self::PENDING_APPROVAL => 'Pending Approval',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::PROVISIONED => 'Provisioned',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::SUBMITTED, self::PENDING_APPROVAL => 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900/40 dark:text-amber-300 dark:border-amber-800',
            self::APPROVED => 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/40 dark:text-blue-300 dark:border-blue-800',
            self::PROVISIONED => 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-800',
            self::REJECTED => 'bg-rose-100 text-rose-800 border-rose-200 dark:bg-rose-900/40 dark:text-rose-300 dark:border-rose-800',
            self::CANCELLED => 'bg-slate-100 text-slate-800 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::PROVISIONED, self::REJECTED, self::CANCELLED], true);
    }
}

