<?php

declare(strict_types=1);

namespace App\Domain\Workflows\Enums;

enum RequestType: string
{
    case IP_ASSIGNMENT = 'ip_assignment';
    case IP_RELEASE = 'ip_release';
    case IP_TRANSFER = 'ip_transfer';
    case SUBNET_CREATE = 'subnet_create';
    case DHCP_RESERVATION = 'dhcp_reservation';
    case POLICY_EXCEPTION = 'policy_exception';

    public function label(): string
    {
        return match ($this) {
            self::IP_ASSIGNMENT => 'IP Address Assignment',
            self::IP_RELEASE => 'IP Address Release',
            self::IP_TRANSFER => 'IP Interface Transfer',
            self::SUBNET_CREATE => 'Subnet Creation / Split',
            self::DHCP_RESERVATION => 'DHCP Static Reservation',
            self::POLICY_EXCEPTION => 'Security Policy Exception',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::IP_ASSIGNMENT => 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/40 dark:text-blue-300 dark:border-blue-800',
            self::IP_RELEASE => 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900/40 dark:text-amber-300 dark:border-amber-800',
            self::IP_TRANSFER => 'bg-indigo-100 text-indigo-800 border-indigo-200 dark:bg-indigo-900/40 dark:text-indigo-300 dark:border-indigo-800',
            self::SUBNET_CREATE => 'bg-purple-100 text-purple-800 border-purple-200 dark:bg-purple-900/40 dark:text-purple-300 dark:border-purple-800',
            self::DHCP_RESERVATION => 'bg-teal-100 text-teal-800 border-teal-200 dark:bg-teal-900/40 dark:text-teal-300 dark:border-teal-800',
            self::POLICY_EXCEPTION => 'bg-rose-100 text-rose-800 border-rose-200 dark:bg-rose-900/40 dark:text-rose-300 dark:border-rose-800',
        };
    }
}

