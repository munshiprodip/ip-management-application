<?php

declare(strict_types=1);

namespace App\Domain\Layer2\Enums;

enum VlanSecurityZone: string
{
    case INTERNAL = 'internal';
    case CLINICAL = 'clinical';
    case BIOMEDICAL = 'biomedical';
    case PACS = 'pacs';
    case WORKSTATIONS = 'workstations';
    case SERVERS = 'servers';
    case MANAGEMENT = 'management';
    case DMZ = 'dmz';
    case GUEST = 'guest';
    case VOIP = 'voip';

    public function label(): string
    {
        return match ($this) {
            self::INTERNAL => 'Internal Campus',
            self::CLINICAL => 'Clinical & Patient Care',
            self::BIOMEDICAL => 'Biomedical Instruments (Restricted)',
            self::PACS => 'PACS & Radiology Imaging',
            self::WORKSTATIONS => 'Hospital Staff Workstations',
            self::SERVERS => 'Core Infrastructure & Datacenter',
            self::MANAGEMENT => 'Out-of-Band Management (OOB)',
            self::DMZ => 'Perimeter DMZ',
            self::GUEST => 'Guest & Public WiFi',
            self::VOIP => 'Voice & Unified Comms',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::CLINICAL, self::BIOMEDICAL => 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 border-rose-200 dark:border-rose-800',
            self::PACS => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800',
            self::SERVERS => 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border-purple-200 dark:border-purple-800',
            self::MANAGEMENT => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border-amber-200 dark:border-amber-800',
            self::DMZ, self::GUEST => 'bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300 border-orange-200 dark:border-orange-800',
            self::WORKSTATIONS => 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border-blue-200 dark:border-blue-800',
            self::VOIP => 'bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-300 border-teal-200 dark:border-teal-800',
            self::INTERNAL => 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700',
        };
    }

    /**
     * Medical & clinical zones that must be protected from autonomous network probing.
     */
    public function isMedicalRestricted(): bool
    {
        return in_array($this, [self::CLINICAL, self::BIOMEDICAL], true);
    }
}

