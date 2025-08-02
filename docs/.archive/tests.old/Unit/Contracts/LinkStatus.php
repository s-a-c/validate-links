<?php

declare(strict_types=1);

namespace .archive\tests.old\Unit\Contracts;

/**
 * Test helper class that mirrors App\Enums\LinkStatus for unit testing.
 * This class provides the same constants and structure as the actual enum
 * but is designed specifically for use in contract tests.old.
 */
final class LinkStatus
{
    public const VALID = 'valid';

    public const BROKEN = 'broken';

    public const TIMEOUT = 'timeout';

    public const FORBIDDEN = 'forbidden';

    public const NOT_FOUND = 'not_found';

    public const INVALID_FORMAT = 'invalid_format';

    public const SECURITY_VIOLATION = 'security_violation';

    public const REDIRECT_LOOP = 'redirect_loop';

    public const SSL_ERROR = 'ssl_error';

    public const DNS_ERROR = 'dns_error';

    /**
     * Private constructor to prevent instantiation.
     * This class should only be used for its constants.
     */
    private function __construct()
    {
        // Prevent instantiation
    }
}
