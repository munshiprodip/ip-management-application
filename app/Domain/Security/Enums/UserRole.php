<?php

declare(strict_types=1);

namespace App\Domain\Security\Enums;

/**
 * KYAMCH Smart IPAM - Personas & Role Definitions
 * Authoritative baseline from Section 2.1 of the Enterprise Development Plan.
 */
enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case NETWORK_ADMIN = 'network_admin';
    case OPERATOR = 'operator';
    case DEPARTMENT_OPERATOR = 'department_operator';
    case AUDITOR = 'auditor';
    case VIEWER = 'viewer';
    case AGENT = 'agent';
    case APPROVER = 'approver';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Platform Super Admin',
            self::NETWORK_ADMIN => 'Network Admin',
            self::OPERATOR => 'IP Operator',
            self::DEPARTMENT_OPERATOR => 'Department Operator',
            self::AUDITOR => 'Auditor',
            self::VIEWER => 'Viewer',
            self::AGENT => 'Service Agent',
            self::APPROVER => 'Independent Approver',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'bg-purple-100 text-purple-800 border-purple-200 dark:bg-purple-900/40 dark:text-purple-300 dark:border-purple-800',
            self::NETWORK_ADMIN => 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/40 dark:text-blue-300 dark:border-blue-800',
            self::OPERATOR => 'bg-teal-100 text-teal-800 border-teal-200 dark:bg-teal-900/40 dark:text-teal-300 dark:border-teal-800',
            self::DEPARTMENT_OPERATOR => 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-800',
            self::AUDITOR => 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900/40 dark:text-amber-300 dark:border-amber-800',
            self::VIEWER => 'bg-slate-100 text-slate-800 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
            self::AGENT => 'bg-cyan-100 text-cyan-800 border-cyan-200 dark:bg-cyan-900/40 dark:text-cyan-300 dark:border-cyan-800',
            self::APPROVER => 'bg-rose-100 text-rose-800 border-rose-200 dark:bg-rose-900/40 dark:text-rose-300 dark:border-rose-800',
        };
    }
}

