<?php

declare(strict_types=1);

namespace .archive\tests.old\Support\Doubles\Contracts;

use App\Contracts\ExtensionInterface;

/**
 * Test implementation of ExtensionInterface for unit testing.
 */
final class TestExtensionImplementation implements ExtensionInterface
{
    public function getName(): string
    {
        return 'Test Extension';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDescription(): string
    {
        return 'A test extension for unit testing';
    }

    public function register(): void
    {
        // Test implementation - no actual registration needed
    }

    public function boot(): void
    {
        // Test implementation - no actual booting needed
    }
}
