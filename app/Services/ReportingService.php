<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ArrayKey;
use App\Enums\OutputFormat;
use App\Services\Contracts\ReportingInterface;
use App\Services\Formatters\ConsoleFormatter;
use App\Services\Formatters\HtmlFormatter;
use App\Services\Formatters\JsonFormatter;
use App\Services\Formatters\MarkdownFormatter;
use App\Services\ValueObjects\ReportingOptions;
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
final class ReportingService implements ReportingInterface
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
     * @param ValidationResultCollection $validationResultCollection Collection of validation results
     * @param OutputFormat $outputFormat Desired output format
     *
     * @return string Formatted report content
     *
     * @throws InvalidArgumentException If format is unsupported or formatter is invalid
     */
    public function generateReport(ValidationResultCollection $validationResultCollection, OutputFormat $outputFormat = OutputFormat::CONSOLE): string
    {
        if (! isset($this->formatters[$outputFormat->value])) {
            throw new InvalidArgumentException("Unsupported format: {$outputFormat->value}");
        }

        $formatter = $this->formatters[$outputFormat->value];
        $reportSummary = $this->generateSummary($validationResultCollection);

        // Create an aggregate ValidationResult for the formatter
        $validationResult = ValidationResult::aggregate($validationResultCollection->toValidationResultArray(), $reportSummary->toArray());

        if (! method_exists($formatter, 'format')) {
            throw new InvalidArgumentException("Invalid formatter for format: {$outputFormat->value}");
        }

        $result = $formatter->format($validationResult, $reportSummary->toArray());

        return is_string($result) ? $result : '';
    }

    /**
     * Register a custom formatter for a specific output format.
     *
     * @param OutputFormat $outputFormat The output format to register
     * @param object $formatter The formatter instance (must have format() method)
     */
    public function addFormatter(OutputFormat $outputFormat, object $formatter): void
    {
        $this->formatters[$outputFormat->value] = $formatter;
    }

    /**
     * Export a validation report to a file.
     *
     * @param ValidationResultCollection $validationResultCollection Validation results to export
     * @param string $filePath Target file path for export
     * @param OutputFormat|null $outputFormat Output format (defaults to JSON)
     *
     * @return bool True if export was successful, false otherwise
     */
    public function exportReport(ValidationResultCollection $validationResultCollection, string $filePath, ?OutputFormat $outputFormat = null): bool
    {
        try {
            $outputFormat ??= OutputFormat::JSON;
            $report = $this->generateReport($validationResultCollection, $outputFormat);

            $directory = dirname($filePath);

            if (! is_dir($directory) && ! mkdir($directory, 0755, true)) {
                throw new Exception("Failed to create directory: {$directory}");
            }

            return file_put_contents($filePath, $report) !== false;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Format validation results with specific options.
     *
     * @param ValidationResultCollection $validationResultCollection Results to format
     * @param OutputFormat $outputFormat Desired output format
     * @param ReportingOptions|null $options Formatting options
     *
     * @return string Formatted output
     */
    public function format(ValidationResultCollection $validationResultCollection, OutputFormat $outputFormat, ?ReportingOptions $options = null): string
    {
        // For now, we ignore the options and use the standard report generation
        // In the future, options could be used to customize the formatting
        return $this->generateReport($validationResultCollection, $outputFormat);
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
     * @param OutputFormat $outputFormat Format to check
     *
     * @return bool True if format is supported
     */
    public function supportsFormat(OutputFormat $outputFormat): bool
    {
        return isset($this->formatters[$outputFormat->value]);
    }

    /**
     * Get the formatter instance for a specific format.
     *
     * @param OutputFormat $outputFormat The format to get formatter for
     *
     * @return object The formatter instance
     *
     * @throws InvalidArgumentException If format is unsupported or formatter not found
     */
    public function getFormatter(OutputFormat $outputFormat): object
    {
        if (! $this->supportsFormat($outputFormat)) {
            throw new InvalidArgumentException("Unsupported format: {$outputFormat->value}");
        }

        $formatter = $this->formatters[$outputFormat->value] ?? null;
        if ($formatter === null) {
            throw new InvalidArgumentException("Formatter not found for format: {$outputFormat->value}");
        }

        return $formatter;
    }

    /**
     * Generate summary statistics with status breakdown.
     */
    #[Override]
    public function generateSummary(ValidationResultCollection $validationResultCollection): ReportSummary
    {
        $total = $validationResultCollection->count();
        $broken = 0;
        $statusBreakdown = [];
        $executionTime = 0.0;
        $filesProcessed = 1;

        foreach ($validationResultCollection as $result) {
            $status = $result->getStatus();
            $statusBreakdown[$status->value] = ($statusBreakdown[$status->value] ?? 0) + 1;
            $executionTime += $result->getResponseTime();

            if (! $result->isValid()) {
                $broken++;
            }
        }

        return ReportSummary::create([
            ArrayKey::TOTAL_LINKS->value => $total,
            ArrayKey::BROKEN_LINKS->value => $broken,
            ArrayKey::SUCCESS_RATE->value => $total > 0 ? round((($total - $broken) / $total) * 100, 2) : 0.0,
            ArrayKey::STATUS_BREAKDOWN->value => $statusBreakdown,
            ArrayKey::EXECUTION_TIME->value => $executionTime,
            ArrayKey::FILES_PROCESSED->value => $filesProcessed,
        ]);
    }

    /**
     * Save report to file.
     */
    public function saveReport(string $report, string $filePath): bool
    {
        try {
            $directory = dirname($filePath);

            if (! is_dir($directory) && ! mkdir($directory, 0755, true)) {
                throw new Exception("Failed to create directory: {$directory}");
            }

            $result = file_put_contents($filePath, $report);

            return $result !== false;
        } catch (Exception) {
            return false;
        }
    }
}
