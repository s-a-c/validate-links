<?php

declare(strict_types=1);

namespace .archive\tests.old\Support\Doubles\Contracts;

use App\Contracts\ExtensionInterface;

/**
 * Alternative test implementation of ExtensionInterface for testing multiple implementations.
 */
final class AlternativeExtensionImplementation implements ExtensionInterface
{
    public function getName(): string
    {
        return 'Alternative Extension';
    }

    public function getVersion(): string
    {
        return '2.0.0';
    }

    public function getDescription(): string
    {
        return 'An alternative test extension implementation';
    }

    public function register(): void
    {
        // Alternative implementation
    }

    public function boot(): void
    {
        // Alternative implementation
    }
}
