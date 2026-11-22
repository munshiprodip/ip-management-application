<?php

declare(strict_types=1);

namespace App\Domain\Discovery\Enums;

enum DiscoverySourceType: string
{
    case ICMP = 'icmp';
    case ARP = 'arp';
    case NDP = 'ndp';
    case SNMP = 'snmp';
    case LLDP = 'lldp';
    case DHCP_LEASE = 'dhcp_lease';
    case DNS_PTR = 'dns_ptr';
    case FIXTURE = 'fixture';

    public function label(): string
    {
        return match ($this) {
            self::ICMP => 'ICMP Echo Ping',
            self::ARP => 'ARP Neighbor Cache',
            self::NDP => 'IPv6 NDP Discovery',
            self::SNMP => 'SNMPv3 Query',
            self::LLDP => 'LLDP / CDP Topology',
            self::DHCP_LEASE => 'DHCP Lease Table',
            self::DNS_PTR => 'DNS Reverse Lookup',
            self::FIXTURE => 'Synthetic Fixture Simulation',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::ICMP => 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/40 dark:text-blue-300 dark:border-blue-800',
            self::ARP, self::NDP => 'bg-teal-100 text-teal-800 border-teal-200 dark:bg-teal-900/40 dark:text-teal-300 dark:border-teal-800',
            self::SNMP => 'bg-purple-100 text-purple-800 border-purple-200 dark:bg-purple-900/40 dark:text-purple-300 dark:border-purple-800',
            self::LLDP => 'bg-indigo-100 text-indigo-800 border-indigo-200 dark:bg-indigo-900/40 dark:text-indigo-300 dark:border-indigo-800',
            self::DHCP_LEASE => 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900/40 dark:text-amber-300 dark:border-amber-800',
            self::DNS_PTR => 'bg-slate-100 text-slate-800 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
            self::FIXTURE => 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-800',
        };
    }
}
