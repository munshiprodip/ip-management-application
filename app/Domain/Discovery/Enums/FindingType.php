<?php

declare(strict_types=1);

namespace App\Domain\Discovery\Enums;

enum FindingType: string
{
    case UNKNOWN_ACTIVE_IP = 'unknown_active_ip';
    case UNEXPECTED_MAC = 'unexpected_mac';
    case REPORTED_DUPLICATE_IP = 'reported_duplicate_ip';
    case DNS_MISMATCH = 'dns_mismatch';
    case STALE_ASSIGNMENT = 'stale_assignment';
    case AGENT_DOWN = 'agent_down';

    public function label(): string
    {
        return match ($this) {
            self::UNKNOWN_ACTIVE_IP => 'Unmanaged Active IP (Rogue)',
            self::UNEXPECTED_MAC => 'Unexpected MAC Address',
            self::REPORTED_DUPLICATE_IP => 'IP Conflict / Duplicate',
            self::DNS_MISMATCH => 'Reverse DNS Pointer Mismatch',
            self::STALE_ASSIGNMENT => 'Stale Assigned IP (Silent Device)',
            self::AGENT_DOWN => 'Discovery Collector Unreachable',
        };
    }

    public function defaultSeverity(): FindingSeverity
    {
        return match ($this) {
            self::UNKNOWN_ACTIVE_IP, self::REPORTED_DUPLICATE_IP => FindingSeverity::CRITICAL,
            self::UNEXPECTED_MAC, self::AGENT_DOWN => FindingSeverity::WARNING,
            self::DNS_MISMATCH, self::STALE_ASSIGNMENT => FindingSeverity::INFO,
        };
    }
}
