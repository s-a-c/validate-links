<?php

declare(strict_types=1);

namespace .archive\tests.old\Unit\Contracts;

/**
 * Test helper class that mirrors App\Enums\ValidationScope for unit testing.
 * This class provides the same constants and structure as the actual enum
 * but is designed specifically for use in contract tests.old.
 */
final class ValidationScope
{
    public const INTERNAL = 'internal';

    public const EXTERNAL = 'external';

    public const ANCHOR = 'anchor';

    public const IMAGE = 'image';

    public const ALL = 'all';

    /**
     * Private constructor to prevent instantiation.
     * This class should only be used for its constants.
     */
    private function __construct()
    {
        // Prevent instantiation
    }
}
