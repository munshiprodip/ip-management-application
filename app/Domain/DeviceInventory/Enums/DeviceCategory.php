<?php

declare(strict_types=1);

namespace App\Domain\DeviceInventory\Enums;

enum DeviceCategory: string
{
    case SERVER = 'server';
    case NETWORK = 'network';
    case MEDICAL = 'medical';
    case SECURITY = 'security';
    case STORAGE = 'storage';
    case WORKSTATION = 'workstation';
    case IOT = 'iot';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::SERVER => 'Server & Compute Host',
            self::NETWORK => 'Network Infrastructure (Switch/Router/AP)',
            self::MEDICAL => 'Medical Device / Clinical Modality',
            self::SECURITY => 'Security Appliance / Firewall',
            self::STORAGE => 'Storage Array / NAS / SAN',
            self::WORKSTATION => 'Workstation / Clinical Terminal',
            self::IOT => 'IoT / Facility Sensor',
            self::OTHER => 'Other Equipment',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::SERVER => 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border-blue-200 dark:border-blue-800',
            self::NETWORK => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800',
            self::MEDICAL => 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 border-rose-200 dark:border-rose-800',
            self::SECURITY => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border-amber-200 dark:border-amber-800',
            self::STORAGE => 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border-purple-200 dark:border-purple-800',
            self::WORKSTATION => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            self::IOT => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-950 dark:text-cyan-300 border-cyan-200 dark:border-cyan-800',
            self::OTHER => 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700',
        };
    }

    public function isMedical(): bool
    {
        return $this === self::MEDICAL;
    }
}
