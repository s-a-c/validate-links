<?php

declare(strict_types=1);

namespace App\Services\Formatters;

use App\Services\ValueObjects\ValidationResult;

final class ConsoleFormatter
{
    /**
     * Format validation result for console output.
     *
     * @param  array<string, mixed>  $statistics
     */
    public function format(ValidationResult $result, array $statistics): string
    {
        $output = [];

        // Header
        $output[] = $this->formatHeader($result);

        // Summary
        $output[] = $this->formatSummary($result->getSummary());

        // Broken links details
        if ($result->broken !== []) {
            $output[] = $this->formatBrokenLinks($result->broken);
        }

        // Statistics
        $output[] = $this->formatStatistics($statistics);

        return implode("\n\n", $output);
    }

    private function formatHeader(ValidationResult $result): string
    {
        $status = $result->isSuccessful() ? '✅ PASSED' : '❌ FAILED';
        $color = $result->isSuccessful() ? 'green' : 'red';

        return "<fg={$color};options=bold>🔗 Link Validation {$status}</>";
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    private function formatSummary(array $summary): string
    {
        $filesProcessedValue = $summary['files_processed'] ?? 0;
        $filesProcessed = is_scalar($filesProcessedValue) ? (string) $filesProcessedValue : '0';
        $totalLinksValue = $summary['total_links'] ?? 0;
        $totalLinks = is_scalar($totalLinksValue) ? (string) $totalLinksValue : '0';
        $brokenLinksValue = $summary['broken_links'] ?? 0;
        $brokenLinks = is_scalar($brokenLinksValue) ? (string) $brokenLinksValue : '0';
        $successRateValue = $summary['success_rate'] ?? 0;
        $successRate = is_scalar($successRateValue) ? (string) $successRateValue : '0';
        $durationValue = $summary['duration'] ?? 0;
        $duration = is_scalar($durationValue) ? (string) $durationValue : '0';

        $lines = [
            '<fg=blue;options=bold>📊 Validation Summary</>',
            "📁 Files processed: {$filesProcessed}",
            "🔗 Total links: {$totalLinks}",
            "❌ Broken links: {$brokenLinks}",
            "📈 Success rate: {$successRate}%",
            "⏱️  Duration: {$duration}s",
        ];

        return implode("\n", $lines);
    }

    /**
     * @param  array<mixed>  $broken
     */
    private function formatBrokenLinks(array $broken): string
    {
        $lines = ['<fg=red;options=bold>💥 Broken Links Details</>'];

        foreach ($broken as $link) {
            if (! is_array($link)) {
                continue;
            }
            $url = isset($link['url']) && is_scalar($link['url']) ? (string) $link['url'] : '';
            $file = isset($link['file']) && is_scalar($link['file']) ? (string) $link['file'] : '';
            $reason = isset($link['reason']) && is_scalar($link['reason']) ? (string) $link['reason'] : '';
            $lines[] = "  🔴 {$url}";
            $lines[] = "     📄 File: {$file}";
            $lines[] = "     ❗ Reason: {$reason}";
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $statistics
     */
    private function formatStatistics(array $statistics): string
    {
        $lines = ['<fg=cyan;options=bold>📈 Detailed Statistics</>'];

        foreach ($statistics as $key => $value) {
            $keyStr = (string) $key;
            if (is_array($value)) {
                $lines[] = "  {$keyStr}:";
                foreach ($value as $subKey => $subValue) {
                    $subKeyStr = (string) $subKey;
                    $subValueStr = is_scalar($subValue) ? (string) $subValue : 'unknown';
                    $lines[] = "    {$subKeyStr}: {$subValueStr}";
                }
            } else {
                $valueStr = is_scalar($value) ? (string) $value : 'unknown';
                $lines[] = "  {$keyStr}: {$valueStr}";
            }
        }

        return implode("\n", $lines);
    }
}
