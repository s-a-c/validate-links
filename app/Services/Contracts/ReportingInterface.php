<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Enums\OutputFormat;
use App\Services\ValueObjects\ReportingOptions;
use App\Services\ValueObjects\ReportSummary;
use App\Services\ValueObjects\ValidationResultCollection;

interface ReportingInterface
{
    /**
     * Generate report from validation results using enum format.
     */
    public function generateReport(ValidationResultCollection $validationResultCollection, OutputFormat $outputFormat = OutputFormat::CONSOLE): string;

    /**
     * Format validation results with specific options.
     */
    public function format(ValidationResultCollection $validationResultCollection, OutputFormat $outputFormat, ?ReportingOptions $options = null): string;

    /**
     * Add formatter for specific output format.
     */
    public function addFormatter(OutputFormat $outputFormat, object $formatter): void;

    /**
     * Export report to file with automatic format detection.
     */
    public function exportReport(ValidationResultCollection $validationResultCollection, string $filePath, ?OutputFormat $outputFormat = null): bool;

    /**
     * Get available output formats.
     *
     * @return OutputFormat[]
     */
    public function getAvailableFormats(): array;

    /**
     * Check if format is supported.
     */
    public function supportsFormat(OutputFormat $outputFormat): bool;

    /**
     * Generate summary statistics with status breakdown.
     */
    public function generateSummary(ValidationResultCollection $validationResultCollection): ReportSummary;

    /**
     * Get formatter instance for specific format.
     */
    public function getFormatter(OutputFormat $outputFormat): object;

    /**
     * Save report to file.
     */
    public function saveReport(string $report, string $filePath): bool;
}
