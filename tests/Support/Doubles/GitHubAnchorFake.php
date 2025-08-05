<?php

declare(strict_types=1);

namespace Tests\Support\Doubles;

use App\Services\Contracts\GitHubAnchorInterface;

/**
 * Simple fake that extracts anchors by naive regex suitable for unit tests.
 */
final class GitHubAnchorFake implements GitHubAnchorInterface
{
    /**
     * @return string[]
     */
    public function extractAnchors(string $markdownOrHtml): array
    {
        $anchors = [];

        // Match id="..." and name="..." in HTML
        if (preg_match_all('/\s(?:id|name)=["\']([^"\']+)["\']/', $markdownOrHtml, $m)) {
            $anchors = array_merge($anchors, $m[1]);
        }

        // Match Markdown headers like: ## Title -> anchor "title"
        if (preg_match_all('/^\s{0,3}#{1,6}\s+([^\n#]+)$/m', $markdownOrHtml, $m2)) {
            foreach ($m2[1] as $title) {
                $slug = strtolower(trim((string) $title));
                $slug = preg_replace('/[^a-z0-9\- ]/i', '', (string) $slug) ?? '';
                $slug = preg_replace('/\s+/', '-', (string) $slug) ?? '';
                if ($slug !== null && $slug !== '') {
                    $anchors[] = $slug;
                }
            }
        }

        return array_values(array_unique($anchors));
    }
}