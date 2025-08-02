<?php

declare(strict_types=1);

namespace .archive\tests.old\Support\Doubles\Contracts;

use App\Services\Contracts\GitHubAnchorInterface;

/**
 * Test implementation of GitHubAnchorInterface for testing purposes.
 *
 * Provides predictable responses for testing GitHub anchor validation functionality.
 */
final class TestGitHubAnchorImplementation implements GitHubAnchorInterface
{
    public function generateAnchor(string $headingText): string
    {
        return mb_strtolower(str_replace(' ', '-', $headingText));
    }

    public function validateAnchor(string $content, string $anchor): bool
    {
        return true; // Always valid for testing
    }

    public function extractAnchors(string $content): array
    {
        return [
            'section-1',
            'section-2',
            'conclusion',
        ];
    }

    public function normalizeAnchor(string $anchor): string
    {
        return mb_strtolower(mb_trim($anchor));
    }
}
