<?php

declare(strict_types=1);

namespace App\Enums;

enum LinkType: string
{
    case INTERNAL = 'internal';
    case EXTERNAL = 'external';
    case ANCHOR = 'anchor';
    case CROSS_REFERENCE = 'cross_reference';

    public function isLocal(): bool
    {
        return match ($this) {
            self::INTERNAL, self::ANCHOR, self::CROSS_REFERENCE => true,
            self::EXTERNAL => false,
        };
    }

    public function requiresNetworkAccess(): bool
    {
        return $this === self::EXTERNAL;
    }
}
