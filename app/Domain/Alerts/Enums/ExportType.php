<?php

declare(strict_types=1);

namespace App\Domain\Alerts\Enums;

enum ExportType: string
{
    case SUBNETS = 'subnets';
    case IP_ADDRESSES = 'ip_addresses';
    case DEVICES = 'devices';
    case AUDIT_EVENTS = 'audit_events';

    public function label(): string
    {
        return match ($this) {
            self::SUBNETS => 'Subnets & CIDR Hierarchy',
            self::IP_ADDRESSES => 'Authoritative IP Addresses',
            self::DEVICES => 'Hardware & Device Inventory',
            self::AUDIT_EVENTS => 'Immutable Audit Event Log',
        };
    }
}
