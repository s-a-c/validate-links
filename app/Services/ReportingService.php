<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OutputFormat;
use App\Services\Contracts\ReportingInterface;
use App\Services\Formatters\ConsoleFormatter;
use App\Services\Formatters\HtmlFormatter;
use App\Services\Formatters\JsonFormatter;
use App\Services\Formatters\MarkdownFormatter;
use App\Services\ValueObjects\ReportSummary;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;
use Exception;
use InvalidArgumentException;
use Override;

/**
 * Service for generating and managing validation reports in multiple formats.
 *
 * This service provides comprehensive reporting capabilities including format
 * management, report generation, export functionality, and summary statistics.
 */
class ReportingService implements ReportingInterface
{
    /**
     * @var array<string, object> Registered formatters indexed by format value
     */
    private array $formatters;

    /**
     * Initialize the reporting service with default formatters.
     */
    public function __construct()
    {
        $this->formatters = [
            OutputFormat::CONSOLE->value => new ConsoleFormatter(),
            OutputFormat::JSON->value => new JsonFormatter(),
            OutputFormat::HTML->value => new HtmlFormatter(),
            OutputFormat::MARKDOWN->value => new MarkdownFormatter(),
        ];
    }

    /**
     * Generate a formatted report from validation results.
     *
     * @param ValidationResultCollection $results Collection of validation results
     * @param OutputFormat $format Desired output format
     * @return string Formatted report content
     * @throws InvalidArgumentException If format is unsupported or formatter is invalid
     */
    public function generateReport(ValidationResultCollection $results, OutputFormat $format = OutputFormat::CONSOLE): string
    {
        if (! isset($this->formatters[$format->value])) {
            throw new InvalidArgumentException("Unsupported format: {$format->value}");
        }

        $formatter = $this->formatters[$format->value];
        $summary = $this->generateSummary($results);

        // Create an aggregate ValidationResult for the formatter
        $aggregateResult = ValidationResult::aggregate($results->toValidationResultArray(), $summary->toArray());

        if (! method_exists($formatter, 'format')) {
            throw new InvalidArgumentException("Invalid formatter for format: {$format->value}");
        }

        $result = $formatter->format($aggregateResult, $summary->toArray());

        return is_string($result) ? $result : '';
    }

    /**
     * Register a custom formatter for a specific output format.
     *
     * @param OutputFormat $format The output format to register
     * @param object $formatter The formatter instance (must have format() method)
     */
    public function addFormatter(OutputFormat $format, object $formatter): void
    {
        $this->formatters[$format->value] = $formatter;
    }

    /**
     * Export a validation report to a file.
     *
     * @param ValidationResultCollection $results Validation results to export
     * @param string $filePath Target file path for export
     * @param OutputFormat|null $format Output format (defaults to JSON)
     * @return bool True if export was successful, false otherwise
     */
    public function exportReport(ValidationResultCollection $results, string $filePath, ?OutputFormat $format = null): bool
    {
        try {
            $format ??= OutputFormat::JSON;
            $report = $this->generateReport($results, $format);

            $directory = dirname($filePath);

            if (! is_dir($directory)) {
                if (! mkdir($directory, 0755, true)) {
                    throw new Exception("Failed to create directory: {$directory}");
                }
            }

            return file_put_contents($filePath, $report) !== false;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Format validation results with specific options.
     *
     * @param ValidationResultCollection $results Results to format
     * @param OutputFormat $format Desired output format
     * @param array<string, mixed> $options Formatting options (currently unused)
     * @return string Formatted output
     */
    public function format(ValidationResultCollection $results, OutputFormat $format, array $options = []): string
    {
        return $this->generateReport($results, $format);
    }

    /**
     * Get all available output formats.
     *
     * @return OutputFormat[] Array of supported output formats
     */
    public function getAvailableFormats(): array
    {
        return array_map(fn ($key) => OutputFormat::from($key), array_keys($this->formatters));
    }

    /**
     * Check if a specific output format is supported.
     *
     * @param OutputFormat $format Format to check
     * @return bool True if format is supported
     */
    public function supportsFormat(OutputFormat $format): bool
    {
        return isset($this->formatters[$format->value]);
    }

    /**
     * Get the formatter instance for a specific format.
     *
     * @param OutputFormat $format The format to get formatter for
     * @return object The formatter instance
     * @throws InvalidArgumentException If format is unsupported or formatter not found
     */
    public function getFormatter(OutputFormat $format): object
    {
        if (! $this->supportsFormat($format)) {
            throw new InvalidArgumentException("Unsupported format: {$format->value}");
        }

        $formatter = $this->formatters[$format->value] ?? null;
        if ($formatter === null) {
            throw new InvalidArgumentException("Formatter not found for format: {$format->value}");
        }

        return $formatter;
    }

    /**
     * Generate summary statistics with status breakdown.
     */
    #[Override]
    public function generateSummary(ValidationResultCollection $results): ReportSummary
    {
        $total = $results->count();
        $broken = 0;
        $statusBreakdown = [];
        $executionTime = 0.0;
        $filesProcessed = 1;

        foreach ($results as $result) {
            $status = $result->getStatus();
            $statusBreakdown[$status->value] = ($statusBreakdown[$status->value] ?? 0) + 1;
            $executionTime += $result->getResponseTime();

            if (! $result->isValid()) {
                $broken++;
            }
        }

        return ReportSummary::create([
            'total_links' => $total,
            'broken_links' => $broken,
            'success_rate' => $total > 0 ? round((($total - $broken) / $total) * 100, 2) : 0.0,
            'status_breakdown' => $statusBreakdown,
            'execution_time' => $executionTime,
            'files_processed' => $filesProcessed,
        ]);
    }

    /**
     * Save report to file.
     */
    public function saveReport(string $report, string $filePath): bool
    {
        try {
            $directory = dirname($filePath);

            if (! is_dir($directory)) {
                if (! mkdir($directory, 0755, true)) {
                    throw new Exception("Failed to create directory: {$directory}");
                }
            }

            $result = file_put_contents($filePath, $report);

            return $result !== false;
        } catch (Exception) {
            return false;
        }
    }
}
