<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use App\Exceptions\ValidateLinksException;

/**
 * Concrete test implementation of the abstract ValidateLinksException class.
 */
final class TestValidateLinksException extends ValidateLinksException
{
    public function getErrorCode(): string
    {
        return 'TEST_ERROR';
    }

    public function getSeverity(): string
    {
        return 'medium';
    }
}