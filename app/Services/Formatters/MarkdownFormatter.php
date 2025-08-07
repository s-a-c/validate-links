<?php

declare(strict_types=1);

namespace App\Services\Formatters;

use App\Services\ValueObjects\ValidationResult;
use Carbon\Carbon;

/**
 * Markdown formatter for validation results with GitHub-flavored markdown.
 *
 * This formatter generates markdown output suitable for documentation,
 * GitHub issues, pull requests, and other markdown-compatible platforms.
 */
final class MarkdownFormatter
{
    /**
     * Format validation result as GitHub-flavored markdown.
     *
     * @param ValidationResult $validationResult The validation result to format
     *
     * @return string Formatted markdown with tables and emoji icons
     */
    public function format(ValidationResult $validationResult): string
    {
        $output = [];

        $output[] = '# Link Validation Report';
        $output[] = '';
        $output[] = '**Generated:** ' . Carbon::now()->format('Y-m-d H:i:s');
        $output[] = '';

        // Statistics
        $output[] = '## Summary';
        $output[] = '';
        $stats = $validationResult->getStatisticsSummary();
        foreach ($stats as $label => $value) {
            $labelStr = (string) $label;
            $valueStr = is_scalar($value) ? (string) $value : 'unknown';
            $output[] = "- **{$labelStr}:** {$valueStr}";
        }
        $output[] = '';

        // Status
        if ($validationResult->isSuccessful()) {
            $output[] = '## Status: ✅ Success';
            $output[] = '';
            $output[] = 'All links validated successfully!';
        } else {
            $output[] = '## Status: ❌ Failed';
            $output[] = '';
            $brokenCount = $validationResult->getBrokenCount();
            $output[] = "Found {$brokenCount} broken link(s).";
        }
        $output[] = '';

        // Broken links details
        if ($validationResult->hasBrokenLinks()) {
            $output[] = '## Broken Links';
            $output[] = '';

            $brokenByType = $validationResult->getBrokenLinksByType();

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
