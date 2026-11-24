<?php

declare(strict_types=1);

namespace App\Services\Network;

class PingResult
{
    public function __construct(
        public readonly string $status, // 'reachable', 'no_reply', 'error'
        public readonly ?string $latency = null, // e.g. '<1ms', '5ms'
        public readonly string $checkedAt = '',
        public readonly ?string $message = null,
    ) {}

    public static function reachable(string $latency, ?string $checkedAt = null): self
    {
        return new self(
            status: 'reachable',
            latency: $latency,
            checkedAt: $checkedAt ?? now()->format('H:i:s'),
        );
    }

    public static function noReply(?string $message = null, ?string $checkedAt = null): self
    {
        return new self(
            status: 'no_reply',
            latency: null,
            checkedAt: $checkedAt ?? now()->format('H:i:s'),
            message: $message ?? 'No response / timed out',
        );
    }

    public static function error(string $message, ?string $checkedAt = null): self
    {
        return new self(
            status: 'error',
            latency: null,
            checkedAt: $checkedAt ?? now()->format('H:i:s'),
            message: $message,
        );
    }

    public function isReachable(): bool
    {
        return $this->status === 'reachable';
    }

    public function isNoReply(): bool
    {
        return $this->status === 'no_reply';
    }

    public function isError(): bool
    {
        return $this->status === 'error';
    }

    public function responseTimeMs(): ?float
    {
        if ($this->latency === null) {
            return null;
        }

        if (preg_match('/([0-9\.]+)/', $this->latency, $matches)) {
            return (float) $matches[1];
        }

        return null;
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'latency' => $this->latency,
            'checked_at' => $this->checkedAt,
            'message' => $this->message,
        ];
    }
}

