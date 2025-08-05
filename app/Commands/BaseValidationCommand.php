<?php

declare(strict_types=1);

namespace App\Commands;

use App\Enums\LinkStatus;
use App\Enums\OutputFormat;
use App\Enums\ValidationScope;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;
use LaravelZero\Framework\Commands\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

abstract class BaseValidationCommand extends Command
{
    /**
     * Create a new console command instance.
     */
    public function __construct(
        protected LinkValidationInterface $linkValidation,
        protected ReportingInterface $reporting
    ) {
        parent::__construct();
    }

    /**
     * Create validation configuration from command options.
     */
    protected function createConfigFromOptions(): ValidationConfig
    {
        $scopeOption = $this->getOptionWithDefault('scope', 'all');
        $timeoutOption = $this->getOptionWithDefault('timeout', 30);
        $maxRedirectsOption = $this->getOptionWithDefault('max-redirects', 5);
        $concurrencyOption = $this->getOptionWithDefault('concurrency', 10);
        $noCacheOption = $this->getOptionWithDefault('no-cache', false);
        $followRedirectsOption = $this->getOptionWithDefault('follow-redirects', false);
        $userAgentOption = $this->getOptionWithDefault('user-agent', 'validate-links/1.0');

        return ValidationConfig::create([
            'scopes' => $this->parseScopes(is_string($scopeOption) ? $scopeOption : 'all'),
            'timeout' => is_numeric($timeoutOption) ? (int) $timeoutOption : 30,
            'max_redirects' => is_numeric($maxRedirectsOption) ? (int) $maxRedirectsOption : 5,
            'concurrent_requests' => is_numeric($concurrencyOption) ? (int) $concurrencyOption : 10,
            'cache_enabled' => ! (bool) $noCacheOption,
            'follow_redirects' => (bool) $followRedirectsOption,
            'user_agent' => is_string($userAgentOption) ? $userAgentOption : 'validate-links/1.0',
            'output_format' => $this->getOutputFormat(),
        ]);
    }

    /**
     * Get option value with default fallback if option doesn't exist.
     */
    protected function getOptionWithDefault(string $name, mixed $default = null): mixed
    {
        return $this->hasOption($name) ? $this->option($name) : $default;
    }

    /**
     * Parse validation scopes from command option.
     *
     * @return ValidationScope[]
     */
    protected function parseScopes(string $scope): array
    {
        if ($scope === 'all') {
            /** @var ValidationScope[] */
            return ValidationScope::ALL->getIncludedScopes();
        }

        $scopeValues = array_map('trim', explode(',', $scope));

        return array_map(
            fn ($s) => ValidationScope::from($s),
            $scopeValues
        );
    }

    /**
     * Get output format from command option.
     */
    protected function getOutputFormat(): OutputFormat
    {
        // Only try to get format option if it exists on this command
        if ($this->getDefinition()->hasOption('format')) {
            $format = $this->getOptionWithDefault('format', 'console');

            return OutputFormat::from(is_string($format) ? $format : 'console');
        }

        // Default to console format if no format option exists
        return OutputFormat::CONSOLE;
    }

    /**
     * Validate command options using enum validation.
     */
    protected function validateCommandOptions(): void
    {
        // Only validate options that exist on this specific command
        $definition = $this->getDefinition();

        // Validate scope option if it exists
        if ($definition->hasOption('scope')) {
            $scopeOption = $this->getOptionWithDefault('scope', null);
            if ($scopeOption !== null && is_string($scopeOption) && $scopeOption !== 'all' && ! $this->isValidScopeList($scopeOption)) {
                $this->error("Invalid scope: {$scopeOption}");
                $this->info('Valid scopes: '.implode(', ', ValidationScope::values()));
                $this->info('Use "all" for comprehensive validation');
                throw new \InvalidArgumentException("Invalid scope: {$scopeOption}");
            }
        }

        // Validate format option if it exists
        if ($definition->hasOption('format')) {
            $formatOption = $this->getOptionWithDefault('format', null);
            if ($formatOption !== null && is_string($formatOption) && ! OutputFormat::isValid($formatOption)) {
                $this->error("Invalid format: {$formatOption}");
                $this->info('Valid formats: '.implode(', ', OutputFormat::values()));
                throw new \InvalidArgumentException("Invalid format: {$formatOption}");
            }
        }
    }

    /**
     * Process validation results using collections.
     */
    protected function processValidationResults(ValidationResultCollection $results): int
    {
        $brokenResults = $results->getFailed();

        if ($brokenResults->isEmpty()) {
            $this->info('✅ All links are valid!');

            return 0;
        }

        // Group broken links by status using enum
        $statusGroups = $this->groupLinksByStatus($brokenResults);

        // Display results grouped by status
        $this->displayStatusGroups($statusGroups);

        // Display summary using reporting service
        $summary = $this->reporting->generateSummary($results);
        $this->displaySummary($summary);

        return 1;
    }

    /**
     * Display validation results using the reporting service.
     */
    protected function displayResults(ValidationResultCollection $results): int
    {
        $format = $this->getOutputFormat();
        $outputPath = $this->getOptionWithDefault('output', null);

        // For console format, use our enhanced display
        if ($format === OutputFormat::CONSOLE && ! $outputPath) {
            return $this->processValidationResults($results);
        }

        // For other formats or file output, use reporting service
        $output = $this->reporting->format($results, $format, [
            'colors' => $format === OutputFormat::CONSOLE,
            'verbose' => $this->output->isVerbose(),
        ]);

        if ($outputPath) {
            $outputPathString = is_string($outputPath) ? $outputPath : '';
            if ($outputPathString !== '' && $outputPathString !== '0') {
                file_put_contents($outputPathString, $output);
                $this->info("Results written to: {$outputPathString}");
            }
        } else {
            $this->output->write($output);
        }

        // Return exit code based on failed results
        return $results->getFailed()->isEmpty() ? 0 : 1;
    }

    /**
     * Collect files from paths based on configuration.
     *
     * @param  string[]  $paths
     * @return string[]
     */
    protected function collectFiles(array $paths, ValidationConfig $config): array
    {
        $files = [];

        foreach ($paths as $path) {
            if (is_file($path)) {
                $files[] = $path;
            } elseif (is_dir($path)) {
                $files = array_merge($files, $this->getFilesFromDirectory($path));
            }
        }

        return $files;
    }

    /**
     * Execute validation on the provided files and return a collection.
     *
     * @param  string[]  $files
     */
    protected function executeValidation(array $files, ValidationConfig $config): ValidationResultCollection
    {
        $results = [];
        foreach ($files as $file) {
            $fileResults = $this->linkValidation->validateFile($file, $config);
            array_push($results, ...$fileResults);
        }

        return new ValidationResultCollection($results);
    }

    /**
     * Handle common validation errors.
     */
    protected function handleValidationError(Throwable $e): int
    {
        $this->error("Validation failed: {$e->getMessage()}");

        if ($this->output->isVerbose()) {
            $this->error($e->getTraceAsString());
        }

        return 2;
    }

    /**
     * Group broken links by LinkStatus enum.
     *
     * @return array<string, ValidationResult[]>
     */
    protected function groupLinksByStatus(ValidationResultCollection $results): array
    {
        $statusGroups = [];

        foreach ($results as $result) {
            $statusValue = $result->getStatus()->value;
            $statusGroups[$statusValue][] = $result;
        }

        return $statusGroups;
    }

    /**
     * Display status groups with enum-based formatting.
     *
     * @param  array<string, ValidationResult[]>  $statusGroups
     */
    protected function displayStatusGroups(array $statusGroups): void
    {
        foreach ($statusGroups as $statusValue => $results) {
            $status = LinkStatus::from($statusValue);
            $this->displayStatusGroup($status, $results);
        }
    }

    /**
     * Display validation summary using ReportSummary object.
     *
     * @param  \App\Services\ValueObjects\ReportSummary|array<string, mixed>  $summary
     */
    protected function displaySummary($summary): void
    {
        $this->newLine();
        $this->line('<fg=white;bg=red> VALIDATION FAILED </fg>');

        // If it's a ReportSummary object, use its methods
        if ($summary instanceof \App\Services\ValueObjects\ReportSummary) {
            $this->line("Total links: {$summary->getTotalLinks()}");
            $this->line("Broken links: {$summary->getBrokenLinks()}");
            $this->line("Success rate: {$summary->getSuccessRate()}%");
        } else {
            // Fallback for legacy array format
            $summaryArray = is_array($summary) ? $summary : [];
            $this->displayValidationSummary($summaryArray);
        }
    }

    /**
     * Legacy method for displaying validation summary with statistics.
     *
     * @param  array<string, mixed>  $summary
     */
    protected function displayValidationSummary(array $summary): void
    {
        $this->newLine();
        $this->line('<fg=white;bg=red> VALIDATION FAILED </fg>');

        $filesProcessed = isset($summary['files_processed']) && is_scalar($summary['files_processed']) ? (string) $summary['files_processed'] : '0';
        $totalLinks = isset($summary['total_links']) && is_scalar($summary['total_links']) ? (string) $summary['total_links'] : '0';
        $brokenLinks = isset($summary['broken_links']) && is_scalar($summary['broken_links']) ? (string) $summary['broken_links'] : '0';
        $successRate = isset($summary['success_rate']) && is_scalar($summary['success_rate']) ? (string) $summary['success_rate'] : '0';

        $this->line("Files processed: {$filesProcessed}");
        $this->line("Total links: {$totalLinks}");
        $this->line("Broken links: {$brokenLinks}");
        $this->line("Success rate: {$successRate}%");
    }

    /**
     * Display a specific status group.
     *
     * @param  ValidationResult[]  $results
     */
    private function displayStatusGroup(LinkStatus $status, array $results): void
    {
        $count = count($results);
        $this->newLine();
        $this->line("<fg=red>● {$status->getDisplayName()} ({$count})</fg>");

        foreach ($results as $result) {
            if ($result instanceof ValidationResult) {
                $this->line("  {$result->getUrl()} in {$result->getFilePath()}");
                if ($this->output->isVerbose() && $result->getError()) {
                    $this->line("    Error: {$result->getError()}");
                }
            }
        }
    }

    /**
     * Get files from directory recursively.
     *
     * @return string[]
     */
    private function getFilesFromDirectory(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Check if scope list is valid.
     */
    private function isValidScopeList(string $scopes): bool
    {
        $scopeValues = array_map('trim', explode(',', $scopes));

        foreach ($scopeValues as $scope) {
            if (! ValidationScope::isValid($scope)) {
                return false;
            }
        }

        return true;
    }
}
