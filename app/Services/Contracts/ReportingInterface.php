<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Enums\OutputFormat;
use App\Services\ValueObjects\ReportSummary;
use App\Services\ValueObjects\ValidationResultCollection;

interface ReportingInterface
{
    /**
     * Generate report from validation results using enum format.
     */
    public function generateReport(ValidationResultCollection $results, OutputFormat $format = OutputFormat::CONSOLE): string;

    /**
     * Format validation results with specific options.
     *
     * @param  array<string, mixed>  $options
     */
    public function format(ValidationResultCollection $results, OutputFormat $format, array $options = []): string;

    /**
     * Add formatter for specific output format.
     */
    public function addFormatter(OutputFormat $format, object $formatter): void;

    /**
     * Export report to file with automatic format detection.
     */
    public function exportReport(ValidationResultCollection $results, string $filePath, ?OutputFormat $format = null): bool;

    /**
     * Get available output formats.
     *
     * @return OutputFormat[]
     */
    public function getAvailableFormats(): array;

    /**
     * Check if format is supported.
     */
    public function supportsFormat(OutputFormat $format): bool;

    /**
     * Generate summary statistics with status breakdown.
     */
    public function generateSummary(ValidationResultCollection $results): ReportSummary;

    /**
     * Get formatter instance for specific format.
     */
    public function getFormatter(OutputFormat $format): object;

    /**
     * Save report to file.
     */
    public function saveReport(string $report, string $filePath): bool;
}
