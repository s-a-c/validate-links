<?php

declare(strict_types=1);

namespace App\Commands;

use App\Enums\FileType;
use App\Enums\OutputFormat;
use App\Enums\PathType;
use App\Enums\ValidationScope;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;
use Exception;
use LaravelZero\Framework\Commands\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

final class ValidateCommand extends BaseValidationCommand
{
    // These properties are initialized in the parent constructor
    // Remove public declarations as they're already handled by parent

    /**
     * The signature of the command.
     */
    protected $signature = 'validate
                            {paths* : Paths to validate (files or directories)}
                            {--scope=all : Validation scope (internal, external, anchor, image, all)}
                            {--format=console : Output format (console, json, html, markdown)}
                            {--output= : Output file path}
                            {--interactive : Run in interactive mode with guided setup}
                            {--concurrent=10 : Number of concurrent requests (1-50)}
                            {--timeout=30 : Request timeout in seconds (5-300)}
                            {--max-redirects=5 : Maximum redirects to follow (0-10)}
                            {--no-cache : Disable caching for this validation}
                            {--follow-redirects : Follow HTTP redirects}
                            {--user-agent=validate-links/1.0 : Custom User-Agent string}
                            {--check-external : Enable external link validation}
                            {--filter= : Filter links by pattern or criteria}
                            {--ci : Enable CI mode with non-interactive behavior}
                            {--fail-fast : Stop validation on first failure}';

    /**
     * The description of the command.
     */
    protected $description = 'Validate links in documentation files with comprehensive scope and format options';

    public function __construct(
        LinkValidationInterface $linkValidation,
        ReportingInterface $reporting,
    ) {
        parent::__construct($linkValidation, $reporting);
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            // Validate command options using enum validation
            $this->validateCommandOptions();

            return match (true) {
                $this->option('interactive') => $this->handleInteractive(),
                default => $this->handleNonInteractive(),
            };
        } catch (Throwable $e) {
            return $this->handleValidationError($e);
        }
    }

    /**
     * Display validation summary.
     *
     * @param \App\Services\ValueObjects\ReportSummary $summary
     */
    protected function displaySummary($summary): void
    {
        $this->newLine();
        $this->info('📊 Validation Summary:');

        // Handle ReportSummary object properly
        if ($summary instanceof \App\Services\ValueObjects\ReportSummary) {
            $this->line("Files processed: {$summary->filesProcessed}");
            $this->line("Links checked: {$summary->totalLinks}");
            $this->line("Valid links: {$summary->getValidLinks()}");

            if ($summary->brokenLinks > 0) {
                $this->error("Broken links: {$summary->brokenLinks}");
            } else {
                $this->info('Broken links: 0 ✅');
            }

            $this->line("Execution time: {$summary->executionTime}s");
        } else {
            // Fallback for array format
            $summaryArray = is_array($summary) ? $summary : [];
            $filesProcessedValue = $summaryArray['files_processed'] ?? 0;
            $filesProcessed = is_scalar($filesProcessedValue) ? (string) $filesProcessedValue : '0';
            $linksCheckedValue = $summaryArray['links_checked'] ?? 0;
            $linksChecked = is_scalar($linksCheckedValue) ? (string) $linksCheckedValue : '0';
            $validLinksValue = $summaryArray['valid_links'] ?? 0;
            $validLinks = is_scalar($validLinksValue) ? (string) $validLinksValue : '0';
            $this->line("Files processed: {$filesProcessed}");
            $this->line("Links checked: {$linksChecked}");
            $this->line("Valid links: {$validLinks}");

            $brokenLinksValue = $summaryArray['broken_links'] ?? 0;
            $brokenLinks = is_scalar($brokenLinksValue) ? (int) $brokenLinksValue : 0;
            if ($brokenLinks > 0) {
                $this->error("Broken links: {$brokenLinks}");
            } else {
                $this->info('Broken links: 0 ✅');
            }

            $executionTimeValue = $summaryArray['execution_time'] ?? 0;
            $executionTime = is_scalar($executionTimeValue) ? (string) $executionTimeValue : '0';
            $this->line("Execution time: {$executionTime}s");
        }
    }

    /**
     * Handle interactive mode with comprehensive configuration.
     */
    private function handleInteractive(): int
    {
        $this->info('🔗 Interactive Link Validation Setup');
        $this->newLine();

        // Gather paths
        $paths = $this->gatherPaths();

        // Gather validation scope
        $scope = $this->gatherValidationScope();

        // Gather output preferences
        $outputConfig = $this->gatherOutputConfiguration();

        // Gather advanced options
        $advancedOptions = $this->gatherAdvancedOptions();

        // Confirm configuration
        $scopeStrings = array_map(fn ($s) => $s->value, $scope);
        if (! $this->confirmConfiguration($paths, $scopeStrings, $outputConfig, $advancedOptions)) {
            $this->warn('Validation cancelled.');

            return self::FAILURE;
        }

        // Execute validation with real-time feedback
        return $this->executeInteractiveValidation($paths, $scopeStrings, $outputConfig);
    }

    /**
     * Gather paths to validate with validation.
     *
     * @return array<string>
     */
    private function gatherPaths(): array
    {
        $paths = [];

        do {
            $path = text(
                label      : 'Enter path to validate',
                placeholder: './docs',
                required   : true,
                validate   : fn (string $value): ?string => is_dir($value) || is_file($value)
                    ? null
                    : 'Path must exist',
            );

            $paths[] = $path;

            $addMore = confirm('Add another path?', false);
        } while ($addMore);

        return $paths;
    }

    /**
     * Gather validation scope using enum-driven options.
     *
     * @return ValidationScope[]
     */
    private function gatherValidationScope(): array
    {
        $this->info('📋 Select validation scopes:');
        $this->newLine();

        // Display scope descriptions
        foreach (ValidationScope::cases() as $scope) {
            if ($scope !== ValidationScope::ALL) {
                $this->line("  <fg=cyan>{$scope->value}</fg>: {$scope->getDescription()}");
            }
        }

        $this->newLine();

        $selectedValues = multiselect(
            label   : 'What types of links should be validated?',
            options : $this->getScopeOptions(),
            default : [ValidationScope::INTERNAL->value, ValidationScope::ANCHOR->value],
            required: true,
            hint    : 'Use space to select, enter to confirm',
        );

        // Convert selected values to enum instances
        return array_map(
            fn ($value) => ValidationScope::from($value),
            $selectedValues,
        );
    }

    /**
     * Get scope options formatted for multiselect.
     *
     * @return array<string, string>
     */
    private function getScopeOptions(): array
    {
        $options = [];

        foreach (ValidationScope::cases() as $scope) {
            if ($scope !== ValidationScope::ALL) {
                $options[$scope->value] = $scope->getDescription();
            }
        }

        return $options;
    }

    /**
     * Gather output configuration using enum-driven options.
     *
     * @return array<string, mixed>
     */
    private function gatherOutputConfiguration(): array
    {
        $this->info('📄 Select output format:');
        $this->newLine();

        // Display format descriptions and use cases
        foreach (OutputFormat::cases() as $format) {
            $this->line("  <fg=cyan>{$format->value}</fg>: {$format->getDescription()}");
            $useCases = implode(', ', $format->getUseCases());
            $this->line("    <fg=gray>Use cases: {$useCases}</fg>");
        }

        $this->newLine();

        $formatOptions = [];
        foreach (OutputFormat::cases() as $format) {
            $formatOptions[$format->value] = $format->getDescription();
        }

        $selectedFormat = select(
            label  : 'Choose output format',
            options: $formatOptions,
            default: OutputFormat::CONSOLE->value,
            hint   : 'Each format has different capabilities and use cases',
        );

        $format = OutputFormat::from($selectedFormat);

        $outputFile = null;
        if ($format !== OutputFormat::CONSOLE) {
            $saveToFile = confirm('Save output to file?', true);

            if ($saveToFile) {
                $defaultFilename = $format->getDefaultFilename();
                $outputFile = text(
                    label      : 'Output file path',
                    placeholder: "./reports/{$defaultFilename}",
                    default    : "./reports/{$defaultFilename}",
                    required   : true,
                    validate   : fn (string $value): ?string => $this->validateOutputPath($value, $format),
                );
            }
        }

        return [
            'format' => $format,
            'file' => $outputFile,
        ];
    }

    /**
     * Validate output file path and extension.
     */
    private function validateOutputPath(string $path, OutputFormat $format): ?string
    {
        $expectedExtension = $format->getExtension();
        $actualExtension = pathinfo($path, PATHINFO_EXTENSION);

        if ($actualExtension !== $expectedExtension) {
            return "File extension should be '.{$expectedExtension}' for {$format->value} format";
        }

        $directory = dirname($path);
        try {
            if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
                return "Cannot create directory: {$directory}";
            }
        } catch (Throwable $e) {
            return "Cannot create directory: {$directory}. Error: {$e->getMessage()}";
        }

        return null;
    }

    /**
     * Gather advanced validation options.
     *
     * @return array<string, mixed>
     */
    private function gatherAdvancedOptions(): array
    {
        $options = [];

        $options['concurrent'] = confirm('Enable concurrent validation?', true);

        if ($options['concurrent']) {
            $options['max_concurrent'] = (int) text(
                label      : 'Maximum concurrent requests',
                placeholder: '10',
                default    : '10',
                validate   : fn (string $value): ?string => is_numeric($value) && (int) $value > 0 && (int) $value <= 50
                    ? null
                    : 'Must be a number between 1 and 50',
            );
        }

        $options['timeout'] = (int) text(
            label      : 'Request timeout (seconds)',
            placeholder: '30',
            default    : '30',
            validate   : fn (string $value): ?string => is_numeric($value) && (int) $value > 0 && (int) $value <= 300
                ? null
                : 'Must be a number between 1 and 300',
        );

        $options['follow_redirects'] = confirm('Follow redirects?', true);

        if ($options['follow_redirects']) {
            $options['max_redirects'] = (int) text(
                label      : 'Maximum redirects to follow',
                placeholder: '5',
                default    : '5',
                validate   : fn (string $value): ?string => is_numeric($value) && (int) $value >= 0 && (int) $value <= 20
                    ? null
                    : 'Must be a number between 0 and 20',
            );
        }

        $options['cache_results'] = confirm('Cache validation results?', true);

        return $options;
    }

    /**
     * Confirm configuration before execution.
     *
     * @param array<string> $paths
     * @param array<string> $scope
     * @param array<string, mixed> $outputConfig
     * @param array<string, mixed> $options
     */
    private function confirmConfiguration(array $paths, array $scope, array $outputConfig, array $options): bool
    {
        $this->newLine();
        $this->info('📋 Configuration Summary:');
        $this->line('Paths: ' . implode(', ', $paths));
        $this->line('Scope: ' . implode(', ', $scope));
        $format = is_string($outputConfig['format'] ?? null) ? $outputConfig['format'] : 'console';
        $this->line("Format: {$format}");
        if (isset($outputConfig['file']) && $outputConfig['file']) {
            $file = is_string($outputConfig['file']) ? $outputConfig['file'] : '';
            $this->line("Output: {$file}");
        }
        $concurrent = isset($options['concurrent']) && $options['concurrent'];
        $maxConcurrent = is_string($options['max_concurrent'] ?? null) ? $options['max_concurrent'] : '10';
        $timeout = is_string($options['timeout'] ?? null) ? $options['timeout'] : '30';
        $this->line('Concurrent: ' . ($concurrent ? "Yes ({$maxConcurrent})" : 'No'));
        $this->line("Timeout: {$timeout}s");
        $this->newLine();

        return confirm('Proceed with validation?', true);
    }

    /**
     * Execute interactive validation with progress feedback.
     *
     * @param array<string> $paths
     * @param array<string> $scope
     * @param array<string, mixed> $outputConfig
     */
    private function executeInteractiveValidation(array $paths, array $scope, array $outputConfig): int
    {
        $totalFiles = $this->countFiles($paths);

        $progress = progress(
            label: 'Validating links',
            steps: $totalFiles,
        );

        $allResults = [];
        $fileCount = 0;

        foreach ($paths as $path) {
            $files = $this->getFilesFromPath($path);

            foreach ($files as $file) {
                $progress->label('Validating: ' . basename((string) $file));

                // Create ValidationConfig from scope array
                $scopeEnums = array_map(fn ($s) => ValidationScope::from($s), $scope);
                $config = ValidationConfig::create([
                    'scopes' => $scopeEnums,
                    'timeout' => 30,
                    'concurrent_requests' => 10,
                ]);

                $fileResults = $this->linkValidation->validateFile($file, $config);

                // Add ValidationResult objects to collection
                $allResults = array_merge($allResults, $fileResults->toValidationResultArray());

                $fileCount++;
                $progress->advance();

                // Show real-time broken link alerts
                if ($this->hasBrokenLinks($fileResults->toValidationResultArray())) {
                    $this->warn("⚠️  Broken links found in: {(string) {$file}}");
                }
            }
        }

        $progress->finish();

        // Create ValidationResultCollection from all results
        $resultsCollection = new ValidationResultCollection($allResults);

        // Generate and display/save report
        $formatValue = $outputConfig['format'] ?? 'console';
        $format = $formatValue instanceof OutputFormat
            ? $formatValue
            : OutputFormat::from(is_string($formatValue) ? $formatValue : 'console');
        $report = $this->reporting->generateReport($resultsCollection, $format);

        if (isset($outputConfig['file']) && $outputConfig['file']) {
            $filePath = is_string($outputConfig['file']) ? $outputConfig['file'] : '';
            $this->reporting->saveReport($report, $filePath);
            $this->info("Report saved to: {$filePath}");
        } else {
            $this->line($report);
        }

        // Show summary
        $summary = $this->reporting->generateSummary($resultsCollection);
        $this->displaySummary($summary);

        return $summary->brokenLinks > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Count total files to be processed.
     *
     * @param string[] $paths
     */
    private function countFiles(array $paths): int
    {
        $count = 0;
        foreach ($paths as $path) {
            if (is_file($path)) {
                $count++;
            } elseif (is_dir($path)) {
                $count += count($this->getFilesFromPath($path));
            }
        }

        return $count;
    }

    /**
     * Get files from a given path.
     *
     * @return string[]
     */
    private function getFilesFromPath(string $path): array
    {
        return match (PathType::detect($path)) {
            PathType::FILE => [$path],
            PathType::DIRECTORY => $this->collectFilesFromDirectory($path),
        };
    }

    /**
     * Collect files from a directory recursively.
     *
     * @return string[]
     */
    private function collectFilesFromDirectory(string $path): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo &&
                $file->isFile() &&
                FileType::isSupported($file->getExtension())) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Check if file results contain broken links.
     *
     * @param ValidationResult[] $fileResults
     */
    private function hasBrokenLinks(array $fileResults): bool
    {
        foreach ($fileResults as $result) {
            if (! $result->isValid()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle non-interactive mode.
     */
    private function handleNonInteractive(): int
    {
        $paths = $this->argument('paths') ?? ['./docs'];
        $scopeString = $this->option('scope');
        $formatString = $this->option('format');
        $outputFile = $this->option('output');
        $concurrent = (int) $this->option('concurrent');
        $timeout = (int) $this->option('timeout');

        // Convert string options to enums
        $scope = ValidationScope::from($scopeString ?? 'all');
        $format = OutputFormat::from($formatString ?? 'console');

        return $this->performValidation($paths, $scope, $format, $outputFile, $concurrent, $timeout);
    }

    /**
     * Perform the actual validation.
     *
     * @param array<string> $paths
     */
    private function performValidation(
        array $paths,
        ValidationScope $scope,
        OutputFormat $format,
        ?string $outputFile,
        int $concurrent,
        int $timeout,
    ): int {
        // Display validation info for all paths
        $pathsDisplay = implode(', ', $paths);
        $this->info("🔍 Validating links in: {$pathsDisplay}");
        $this->info("📋 Scope: {$scope->value}");
        $this->info("📄 Format: {$format->value}");

        if ($outputFile) {
            $this->info("💾 Output: {$outputFile}");
        }

        $this->newLine();

        try {
            // Create validation configuration
            $config = ValidationConfig::create([
                'scopes' => [$scope],
                'concurrent_requests' => $concurrent,
                'timeout' => $timeout,
            ]);

            // Collect all files from all paths (handles both files and directories)
            $allResults = [];
            foreach ($paths as $path) {
                $files = $this->getFilesFromPath($path);

                foreach ($files as $file) {
                    $fileResults = $this->linkValidation->validateFile($file, $config);
                    $allResults = array_merge($allResults, $fileResults->toValidationResultArray());
                }
            }

            // Create ValidationResultCollection from all results
            $results = new ValidationResultCollection($allResults);

            // Generate and display report
            $report = $this->reporting->generateReport($results, $format);

            if ($format === OutputFormat::CONSOLE) {
                $this->line($report);
            } else {
                $this->info($report);
            }

            // Save to file if requested
            if ($outputFile) {
                $success = $this->reporting->exportReport($results, $outputFile, $format);

                if ($success) {
                    $this->info("✅ Report saved to: {$outputFile}");
                } else {
                    $this->error("❌ Failed to save report to: {$outputFile}");

                    return 1;
                }
            }

            // Show summary
            $summary = $this->reporting->generateSummary($results);
            $this->newLine();
            $this->info('📊 Summary:');

            $this->info("   Total links: {$summary->totalLinks}");
            $this->info("   Valid: {$summary->getValidLinks()}");
            $this->info("   Invalid: {$summary->brokenLinks}");
            $this->info("   Success rate: {$summary->successRate}%");

            return $summary->brokenLinks > 0 ? 1 : 0;
        } catch (Exception $e) {
            $this->error("❌ Validation failed: {$e->getMessage()}");

            return 1;
        }
    }
}
