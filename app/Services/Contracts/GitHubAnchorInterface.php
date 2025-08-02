<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface GitHubAnchorInterface
{
    /**
     * Generate GitHub-style anchor from heading text.
     */
    public function generateAnchor(string $headingText): string;

    /**
     * Validate if anchor exists in content.
     */
    public function validateAnchor(string $content, string $anchor): bool;

    /**
     * Extract all anchors from markdown content.
     *
     * @return array<string>
     */
    public function extractAnchors(string $content): array;

    /**
     * Normalize anchor for comparison.
     */
    public function normalizeAnchor(string $anchor): string;
}
