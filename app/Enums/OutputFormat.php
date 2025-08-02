<?php

declare(strict_types=1);

namespace App\Enums;

use App\Services\Formatters\ConsoleFormatter;
use App\Services\Formatters\HtmlFormatter;
use App\Services\Formatters\JsonFormatter;
use App\Services\Formatters\MarkdownFormatter;

enum OutputFormat: string
{
    case CONSOLE = 'console';
    case JSON = 'json';
    case HTML = 'html';
    case MARKDOWN = 'markdown';
    case XML = 'xml';
    case CSV = 'csv';

    /**
     * Get all format values as array.
     *
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get formatted options for Laravel Prompts select/multiselect.
     *
     * @return array<string, string>
     */
    public static function getSelectOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->getDescription();
        }

        return $options;
    }

    /**
     * Check if a string value is a valid OutputFormat.
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }

    /**
     * Get file extension for format.
     */
    public function getExtension(): string
    {
        return match ($this) {
            self::JSON => 'json',
            self::HTML => 'html',
            self::MARKDOWN => 'md',
            self::XML => 'xml',
            self::CSV => 'csv',
            self::CONSOLE => 'txt'
        };
    }

    /**
     * Get MIME type for format.
     */
    public function getMimeType(): string
    {
        return match ($this) {
            self::JSON => 'application/json',
            self::HTML => 'text/html',
            self::MARKDOWN => 'text/markdown',
            self::XML => 'application/xml',
            self::CSV => 'text/csv',
            self::CONSOLE => 'text/plain'
        };
    }

    /**
     * Check if format supports structured data.
     */
    public function isStructured(): bool
    {
        return match ($this) {
            self::JSON, self::XML, self::CSV => true,
            self::HTML, self::MARKDOWN, self::CONSOLE => false
        };
    }

    /**
     * Check if format supports styling.
     */
    public function supportsFormatting(): bool
    {
        return match ($this) {
            self::HTML, self::MARKDOWN, self::CONSOLE => true,
            self::JSON, self::XML, self::CSV => false
        };
    }

    /**
     * Get formatter class name.
     */
    public function getFormatterClass(): string
    {
        return match ($this) {
            self::CONSOLE => ConsoleFormatter::class,
            self::JSON => JsonFormatter::class,
            self::HTML => HtmlFormatter::class,
            self::MARKDOWN => MarkdownFormatter::class,
            self::XML => 'App\\Services\\Formatters\\XmlFormatter',
            self::CSV => 'App\\Services\\Formatters\\CsvFormatter'
        };
    }

    /**
     * Get default filename for format.
     */
    public function getDefaultFilename(): string
    {
        return match ($this) {
            self::CONSOLE => 'validation-output.txt',
            self::JSON => 'validation-report.json',
            self::HTML => 'validation-report.html',
            self::MARKDOWN => 'VALIDATION_REPORT.md',
            self::XML => 'validation-report.xml',
            self::CSV => 'validation-data.csv'
        };
    }

    /**
     * Check if format is suitable for CI/CD integration.
     */
    public function isCiCdFriendly(): bool
    {
        return match ($this) {
            self::JSON, self::XML, self::CSV => true,
            self::HTML, self::MARKDOWN, self::CONSOLE => false
        };
    }

    /**
     * Get human-readable description for each format.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::CONSOLE => 'Console output with colors and formatting',
            self::JSON => 'JSON format for programmatic use and APIs',
            self::HTML => 'HTML report with styling and interactive features',
            self::MARKDOWN => 'Markdown format for documentation integration',
            self::XML => 'XML format for data exchange and processing',
            self::CSV => 'CSV format for spreadsheet analysis and data import',
        };
    }

    /**
     * Get detailed explanation for help text.
     */
    public function getHelpText(): string
    {
        return match ($this) {
            self::CONSOLE => 'Displays results directly in the terminal with colors and formatting',
            self::JSON => 'Outputs structured JSON data suitable for APIs and programmatic processing',
            self::HTML => 'Generates a styled HTML report with interactive features and charts',
            self::MARKDOWN => 'Creates markdown documentation that can be included in project docs',
            self::XML => 'Produces XML output for data exchange and integration with other tools',
            self::CSV => 'Exports data in CSV format for analysis in spreadsheet applications',
        };
    }

    /**
     * Get recommended use cases for each format.
     *
     * @return string[]
     */
    public function getUseCases(): array
    {
        return match ($this) {
            self::CONSOLE => ['Interactive development', 'Quick validation', 'CI/CD logs'],
            self::JSON => ['API integration', 'Automated processing', 'Data analysis'],
            self::HTML => ['Team reports', 'Documentation sites', 'Executive summaries'],
            self::MARKDOWN => ['Project documentation', 'README files', 'Wiki pages'],
            self::XML => ['Enterprise integration', 'Data exchange', 'Legacy systems'],
            self::CSV => ['Data analysis', 'Spreadsheet import', 'Statistical processing'],
        };
    }
}
