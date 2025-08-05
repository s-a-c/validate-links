<?php

declare(strict_types=1);

namespace App\Services\Formatters;

use App\Services\ValueObjects\ValidationResult;

/**
 * Markdown formatter for validation results with GitHub-flavored markdown.
 *
 * This formatter generates markdown output suitable for documentation,
 * GitHub issues, pull requests, and other markdown-compatible platforms.
 */
class MarkdownFormatter
{
    /**
     * Format validation result as GitHub-flavored markdown.
     *
     * @param ValidationResult $result The validation result to format
     * @return string Formatted markdown with tables and emoji icons
     */
    public function format(ValidationResult $result): string
    {
        $output = [];

        $output[] = '# Link Validation Report';
        $output[] = '';
        $output[] = '**Generated:** '.date('Y-m-d H:i:s');
        $output[] = '';

        // Statistics
        $output[] = '## Summary';
        $output[] = '';
        $stats = $result->getStatisticsSummary();
        foreach ($stats as $label => $value) {
            $labelStr = (string) $label;
            $valueStr = is_scalar($value) ? (string) $value : 'unknown';
            $output[] = "- **{$labelStr}:** {$valueStr}";
        }
        $output[] = '';

        // Status
        if ($result->isSuccessful()) {
            $output[] = '## Status: ✅ Success';
            $output[] = '';
            $output[] = 'All links validated successfully!';
        } else {
            $output[] = '## Status: ❌ Failed';
            $output[] = '';
            $brokenCount = $result->getBrokenCount();
            $output[] = "Found {$brokenCount} broken link(s).";
        }
        $output[] = '';

        // Broken links details
        if ($result->hasBrokenLinks()) {
            $output[] = '## Broken Links';
            $output[] = '';

            $brokenByType = $result->getBrokenLinksByType();

            foreach ($brokenByType as $type => $links) {
                $linkCount = count($links);
                $output[] = "### {$type} Links ({$linkCount})";
                $output[] = '';
                $output[] = '| File | Link | Reason |';
                $output[] = '|------|------|--------|';

                foreach ($links as $link) {
                    $file = basename($link['file']);
                    $url = $link['url'];
                    $reason = $link['reason'];
                    $output[] = "| {$file} | `{$url}` | {$reason} |";
                }
                $output[] = '';
            }
        }

        return implode("\n", $output);
    }
}
