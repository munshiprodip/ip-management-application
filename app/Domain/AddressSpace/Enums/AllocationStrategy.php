<?php

declare(strict_types=1);

namespace App\Domain\AddressSpace\Enums;

enum AllocationStrategy: string
{
    case SEQUENTIAL = 'sequential';
    case RANDOM = 'random';
    case SPARSE = 'sparse';

    public function label(): string
    {
        return match ($this) {
            self::SEQUENTIAL => 'Sequential (Lowest Free Address First)',
            self::RANDOM => 'Random / Unpredictable',
            self::SPARSE => 'Sparse (Distributed Across Prefix)',
        };
    }
}
