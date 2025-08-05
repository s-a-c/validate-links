<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Contracts\GitHubAnchorInterface;

class GitHubAnchorService implements GitHubAnchorInterface
{
    /**
     * Generate GitHub-style anchor from heading text.
     */
    public function generateAnchor(string $headingText): string
    {
        // Remove markdown formatting
        $text = preg_replace('/[*_`~]/', '', $headingText);

        // Convert to lowercase
        $text = mb_strtolower((string) $text);

        // Replace spaces and special characters with hyphens
        $text = preg_replace('/[^a-z0-9\-]/', '-', $text);

        // Remove multiple consecutive hyphens
        $text = preg_replace('/-+/', '-', (string) $text);

        // Remove leading and trailing hyphens
        $text = mb_trim((string) $text, '-');

        return $text;
    }

    /**
     * Validate if anchor exists in content.
     */
    public function validateAnchor(string $content, string $anchor): bool
    {
        $anchors = $this->extractAnchors($content);
        $normalizedAnchor = $this->normalizeAnchor($anchor);

        return in_array($normalizedAnchor, array_map($this->normalizeAnchor(...), $anchors));
    }

    /**
     * Extract all anchors from markdown content.
     */
    public function extractAnchors(string $content): array
    {
        $anchors = [];

        // Extract headings (# ## ### etc.)
        if (preg_match_all('/^(#{1,6})\s+(.+)$/m', $content, $matches)) {
            foreach ($matches[2] as $heading) {
                $anchors[] = $this->generateAnchor($heading);
            }
        }

        // Extract explicit anchor links [text](#anchor)
        if (preg_match_all('/\[([^\]]+)\]\(#([^)]+)\)/', $content, $matches)) {
            foreach ($matches[2] as $anchor) {
                $anchors[] = $this->normalizeAnchor($anchor);
            }
        }

        // Extract HTML anchor tags <a name="anchor">
        if (preg_match_all('/<a\s+(?:name|id)=["\']([^"\']+)["\'][^>]*>/', $content, $matches)) {
            foreach ($matches[1] as $anchor) {
                $anchors[] = $this->normalizeAnchor($anchor);
            }
        }

        return array_unique($anchors);
    }

    /**
     * Normalize anchor for comparison.
     */
    public function normalizeAnchor(string $anchor): string
    {
        // Remove leading # if present
        $anchor = mb_ltrim($anchor, '#');

        // Convert to lowercase for case-insensitive comparison
        return mb_strtolower($anchor);
    }
}
