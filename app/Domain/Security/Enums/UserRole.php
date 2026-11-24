<?php

declare(strict_types=1);

namespace App\Domain\Security\Enums;

/**
 * KYAMCH Smart IPAM - Personas & Role Definitions
 * Authoritative baseline from Section 2.1 of the Enterprise Development Plan.
 */
enum UserRole: string
{
    // The Three Core System Roles
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case USER = 'user';

    // Legacy / Backward-Compatibility Aliases
    case NETWORK_ADMIN = 'network_admin';
    case OPERATOR = 'operator';
    case DEPARTMENT_OPERATOR = 'department_operator';
    case AUDITOR = 'auditor';
    case VIEWER = 'viewer';
    case AGENT = 'agent';
    case APPROVER = 'approver';

    /**
     * Get the three official system roles.
     *
     * @return array<self>
     */
    public static function coreRoles(): array
    {
        return [
            self::SUPER_ADMIN,
            self::ADMIN,
            self::USER,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::ADMIN, self::NETWORK_ADMIN => 'Admin',
            self::USER, self::OPERATOR, self::VIEWER, self::DEPARTMENT_OPERATOR, self::AUDITOR, self::AGENT, self::APPROVER => 'User',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'bg-purple-100 text-purple-800 border-purple-200 dark:bg-purple-900/40 dark:text-purple-300 dark:border-purple-800',
            self::ADMIN, self::NETWORK_ADMIN => 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/40 dark:text-blue-300 dark:border-blue-800',
            default => 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-800',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'All access. Unrestricted platform authority across IPAM, infrastructure, user administration, and role security management.',
            self::ADMIN, self::NETWORK_ADMIN => 'All access without role management. Full IPAM & infrastructure management and user administration; cannot create or alter Super Admin accounts.',
            default => 'All access without user and role management. Full operational access across all core IPAM features, subnets, VLANs, IP allocations, ping, and scanning.',
        };
    }
}

