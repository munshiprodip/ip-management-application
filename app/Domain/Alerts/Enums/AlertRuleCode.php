<?php

declare(strict_types=1);

namespace App\Domain\Alerts\Enums;

enum AlertRuleCode: string
{
    case ROGUE_IP = 'ROGUE_IP';
    case MAC_DRIFT = 'MAC_DRIFT';
    case IP_CONFLICT = 'IP_CONFLICT';
    case SUBNET_EXHAUSTION = 'SUBNET_EXHAUSTION';
    case AGENT_OFFLINE = 'AGENT_OFFLINE';
    case SLA_BREACH = 'SLA_BREACH';

    public function label(): string
    {
        return match ($this) {
            self::ROGUE_IP => 'Rogue / Unmanaged IP Detected',
            self::MAC_DRIFT => 'Unexpected MAC Address Drift',
            self::IP_CONFLICT => 'IP Address Conflict Detected',
            self::SUBNET_EXHAUSTION => 'Subnet Capacity Near Exhaustion',
            self::AGENT_OFFLINE => 'Zone Collector Heartbeat Missed',
            self::SLA_BREACH => 'Change Request SLA Breached',
        };
    }
}

