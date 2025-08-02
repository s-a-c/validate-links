<?php

declare(strict_types=1);

namespace .archive\tests.old\Support\Doubles\Contracts;

use App\Enums\LinkStatus;
use App\Enums\OutputFormat;
use App\Services\Contracts\ReportingInterface;
use App\Services\ValueObjects\ValidationResult;
use stdClass;

/**
 * Test implementation of ReportingInterface for unit testing.
 */
final class TestReportingImplementation implements ReportingInterface
{
    /** @var array<OutputFormat, object> */
    private array $formatters = [];

    /**
     * @param  ValidationResult[]  $results
     */
    public function generateReport(array $results, OutputFormat $format = OutputFormat::CONSOLE): string
    {
        $brokenCount = 0;
        foreach ($results as $result) {
            if (! $result->isValid()) {
                $brokenCount++;
            }
        }

        return $brokenCount > 0 ? "Found {$brokenCount} broken links" : 'All links are valid';
    }

    /**
     * @param  ValidationResult[]  $results
     * @param  array<string, mixed>  $options
     */
    public function format(array $results, OutputFormat $format, array $options = []): string
    {
        return $this->generateReport($results, $format);
    }

    public function addFormatter(OutputFormat $format, object $formatter): void
    {
        $this->formatters[$format->value] = $formatter;
    }

    /**
     * @param  ValidationResult[]  $results
     */
    public function exportReport(array $results, string $filePath, ?OutputFormat $format = null): bool
    {
        $format = $format ?? OutputFormat::JSON;
        $content = $this->formatResults($results, $format->value);

        // In a real implementation, this would write to file
        return $content !== '' && $content !== '0' && ($filePath !== '' && $filePath !== '0');
    }

    /**
     * @return OutputFormat[]
     */
    public function getAvailableFormats(): array
    {
        return OutputFormat::cases();
    }

    public function supportsFormat(OutputFormat $format): bool
    {
        return in_array($format, $this->getAvailableFormats(), true);
    }

    /**
     * @param  ValidationResult[]  $results
     * @return array{total_links: int, broken_links: int, success_rate: float, status_breakdown: array<LinkStatus, int>}
     */
    public function generateSummary(array $results): array
    {
        $total = count($results);
        $broken = 0;
        $statusBreakdown = [];

        foreach ($results as $result) {
            if (! $result->isValid()) {
                $broken++;
            }

            $status = $result->getStatus();
            $statusBreakdown[$status->value] = ($statusBreakdown[$status->value] ?? 0) + 1;
        }

        return [
            'total_links' => $total,
            'broken_links' => $broken,
            'success_rate' => $total > 0 ? round((($total - $broken) / $total) * 100, 2) : 0.0,
            'status_breakdown' => $statusBreakdown,
        ];
    }

    public function getFormatter(OutputFormat $format): object
    {
        return $this->formatters[$format->value] ?? new stdClass();
    }

    public function saveReport(string $report, string $filePath): bool
    {
        // Simulate save operation
        return $report !== '' && $filePath !== '';
    }

    /**
     * @param  ValidationResult[]  $results
     */
    private function formatResults(array $results, string $format): string
    {
        return match ($format) {
            'json' => json_encode(array_map(fn ($r) => $r->toArray(), $results), JSON_PRETTY_PRINT) ?: '[]',
            'html' => '<html><body><h1>Validation Report</h1></body></html>',
            'markdown' => "# Validation Report\n\nResults summary...",
            'console' => "Validation Report\n================\n",
            default => '',
        };
    }
}
