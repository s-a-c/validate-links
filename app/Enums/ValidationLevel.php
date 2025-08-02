<?php

declare(strict_types=1);

namespace App\Enums;

enum ValidationLevel: string
{
    case STRICT = 'strict';
    case NORMAL = 'normal';
    case LENIENT = 'lenient';

    public function getTimeoutMultiplier(): float
    {
        return match ($this) {
            self::STRICT => 2.0,
            self::NORMAL => 1.0,
            self::LENIENT => 0.8,
        };
    }
}
