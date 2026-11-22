<?php

declare(strict_types=1);

namespace App\Domain\AddressSpace\Enums;

enum IpConnectivityStatus: string
{
    case OBSERVED_ONLINE = 'observed_online';
    case OBSERVED_OFFLINE = 'observed_offline';
    case UNOBSERVED = 'unobserved';

    public function label(): string
    {
        return match ($this) {
            self::OBSERVED_ONLINE => 'Observed Online (Ping/ARP/SNMP)',
            self::OBSERVED_OFFLINE => 'Observed Offline / Silent',
            self::UNOBSERVED => 'Unobserved / Discovery Disabled',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::OBSERVED_ONLINE => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
            self::OBSERVED_OFFLINE => 'bg-slate-500/10 text-slate-500 dark:text-slate-400 border-slate-500/20',
            self::UNOBSERVED => 'bg-indigo-500/10 text-indigo-500 dark:text-indigo-400 border-indigo-500/20',
        };
    }
}
