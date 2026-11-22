<?php

declare(strict_types=1);

namespace App\Domain\AddressSpace\Enums;

enum HostType: string
{
    case SERVER = 'server';
    case WORKSTATION = 'workstation';
    case NETWORK = 'network';
    case PRINTER = 'printer';
    case WIRELESS = 'wireless';
    case MEDICAL = 'medical';
    case SURVEILLANCE = 'surveillance';
    case OTHER_IOT = 'other_iot';

    public function label(): string
    {
        return match ($this) {
            self::SERVER => 'Servers',
            self::WORKSTATION => 'Workstations',
            self::NETWORK => 'Network equipment',
            self::PRINTER => 'Printers',
            self::WIRELESS => 'Wireless',
            self::MEDICAL => 'Medical devices',
            self::SURVEILLANCE => 'Surveillance',
            self::OTHER_IOT => 'Other / IoT',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SERVER => 'Database, HIS, application, VM hosts',
            self::WORKSTATION => 'PCs, laptops, thin clients',
            self::NETWORK => 'Switches, routers, firewalls',
            self::PRINTER => 'Office and clinical printers',
            self::WIRELESS => 'APs and controllers',
            self::MEDICAL => 'Connected clinical equipment',
            self::SURVEILLANCE => 'CCTV, NVR, access control',
            self::OTHER_IOT => 'UPS, sensors, virtual device',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::SERVER => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800 font-medium',
            self::WORKSTATION => 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200 dark:border-blue-800 font-medium',
            self::NETWORK => 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200 dark:border-purple-800 font-medium',
            self::PRINTER => 'bg-orange-50 text-orange-700 dark:bg-orange-950/60 dark:text-orange-300 border-orange-200 dark:border-orange-800 font-medium',
            self::WIRELESS => 'bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 border-sky-200 dark:border-sky-800 font-medium',
            self::MEDICAL => 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200 dark:border-rose-800 font-semibold',
            self::SURVEILLANCE => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800 font-medium',
            self::OTHER_IOT => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-300 dark:border-slate-700 font-medium',
        };
    }

    /**
     * @return array<int, array{value: string, label: string, description: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => [
                'value' => $case->value,
                'label' => $case->label(),
                'description' => $case->description(),
            ],
            self::cases()
        );
    }
}
