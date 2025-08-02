# 6. Complete Code Documentation

**Date:** July 22, 2025  
**Project:** Laravel Zero validate-links Implementation  
**Compliance:** `.ai/guidelines.md` and `.ai/guidelines/` standards

## 6.1. Overview

This document provides comprehensive documentation for all classes in the validate-links Laravel Zero application, including complete code examples, usage patterns, and implementation details. The documentation covers all class types: commands, contracts, enums, exceptions, providers, and services.

### 6.1.0. Navigation and Cross-References

**📖 Related Documentation:**
- **[Implementation Guide](020-implementation-guide.md)** - Complete source code listings (single source of truth)
- **[Testing Documentation](070-testing-documentation.md)** - Testing strategies for all documented classes
- **[Architecture Overview](030-architecture-overview.md)** - System design and architectural patterns
- **[Documentation Index](000-documentation-index.md)** - Comprehensive cross-reference guide

**🔗 Quick Links:**
- [Commands Implementation](020-implementation-guide.md#22-command-implementation-enhancement) → [Commands Documentation](#62-commands-documentation)
- [Services Implementation](020-implementation-guide.md#21-service-implementation-completion) → [Services Documentation](#63-service-contracts-documentation)
- [Exception Implementation](020-implementation-guide.md#23-value-objects-and-data-structures) → [Exception Documentation](#66-exception-classes-documentation)
- [Testing Examples](070-testing-documentation.md#72-unit-testing) → [Code Usage Patterns](#69-integration-examples)

### 6.1.1. Class Organization

The application follows a service-oriented architecture with clear separation of concerns:

- **Commands:** CLI command implementations with Laravel Zero integration
- **Contracts:** Service interfaces defining business logic contracts
- **Services:** Business logic implementations with dependency injection
- **Value Objects:** Immutable data structures for configuration and results
- **Formatters:** Output formatting services for different report types
- **Exceptions:** Custom exception classes for error handling
- **Providers:** Service providers for dependency injection configuration

## 6.2. Commands Documentation

### 6.2.1. BaseValidationCommand (Enum-Enhanced)

**Purpose:** Abstract base class providing shared functionality for validation commands with comprehensive enum integration.

**Location:** `app/Commands/BaseValidationCommand.php`

**Key Features:**
- **Enum-based validation** for command options using ValidationScope and OutputFormat
- **Type-safe configuration** creation with enum instances
- **Enhanced result processing** with LinkStatus enum grouping and colored output
- **Interactive prompts** using enum-driven Laravel Prompts integration
- **Automatic validation** with helpful error messages listing valid enum values

**Complete Implementation:**

```php
<?php

declare(strict_types=1);

namespace App\Commands;

use App\Services\ValueObjects\ValidationConfig;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Enums\ValidationScope;
use App\Enums\OutputFormat;
use App\Enums\LinkStatus;
use LaravelZero\Framework\Commands\Command;

abstract class BaseValidationCommand extends Command
{
    /**
     * Create validation configuration from command options.
     */
    protected function createConfigFromOptions(): ValidationConfig
    {
        return ValidationConfig::fromCommandOptions(
            $this->options(),
            $this->arguments()
        );
    }

    /**
     * Display validation configuration summary.
     */
    protected function displayValidationSummary(ValidationConfig $config, array $paths): void
    {
        $this->info('🔗 Link Validation Configuration');
        $this->newLine();

        $this->line("📁 Paths: " . implode(', ', $paths));
        $this->line("🎯 Scope: " . implode(', ', $config->scope));
        $this->line("🌐 External links: " . ($config->checkExternal ? '✅ Yes' : '❌ No'));
        $this->line("📊 Output format: {$config->format}");

        if ($config->maxBroken > 0) {
            $this->line("🛑 Max broken links: {$config->maxBroken}");
        }

        if ($config->dryRun) {
            $this->warn("🔍 DRY RUN MODE - No actual validation");
        }

        $this->newLine();
    }

    /**
     * Collect files from paths based on configuration.
     */
    protected function collectFiles(array $paths, ValidationConfig $config): array
    {
        $this->info('📋 Collecting files...');

        $files = [];
        $patterns = config('validate-links.files.default_patterns', ['**/*.md']);
        $excludePatterns = array_merge(
            config('validate-links.files.exclude_patterns', []),
            $config->excludePatterns
        );

        foreach ($paths as $path) {
            if (is_file($path)) {
                $files[] = $path;
            } elseif (is_dir($path)) {
                $files = array_merge($files, $this->findFilesInDirectory(
                    $path,
                    $patterns,
                    $excludePatterns,
                    $config
                ));
            }
        }

        return array_unique($files);
    }

    /**
     * Find files in directory matching patterns.
     */
    private function findFilesInDirectory(
        string $directory,
        array $patterns,
        array $excludePatterns,
        ValidationConfig $config
    ): array {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $filePath = $file->getPathname();
            $relativePath = str_replace($directory . '/', '', $filePath);

            // Check depth limit
            if ($config->maxDepth > 0) {
                $depth = substr_count($relativePath, '/');
                if ($depth > $config->maxDepth) {
                    continue;
                }
            }

            // Check hidden files
            if (!$config->includeHidden && $this->isHiddenFile($relativePath)) {
                continue;
            }

            // Check exclude patterns
            if ($this->matchesPatterns($relativePath, $excludePatterns)) {
                continue;
            }

            // Check include patterns
            if ($this->matchesPatterns($relativePath, $patterns)) {
                $files[] = $filePath;
            }
        }

        return $files;
    }

    /**
     * Check if file is hidden.
     */
    private function isHiddenFile(string $path): bool
    {
        $parts = explode('/', $path);
        foreach ($parts as $part) {
            if (str_starts_with($part, '.')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if path matches any of the given patterns.
     */
    private function matchesPatterns(string $path, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $path)) {
                return true;
            }
        }
        return false;
    }
}
```

**Usage Example:**

```php
// Extending BaseValidationCommand
class CustomValidationCommand extends BaseValidationCommand
{
    public function handle(): int
    {
        $config = $this->createConfigFromOptions();
        $paths = $this->argument('paths');
        
        $this->displayValidationSummary($config, $paths);
        $files = $this->collectFiles($paths, $config);
        
        // Custom validation logic here
        
        return self::SUCCESS;
    }
}
```

### 6.2.2. ValidateCommand

**Purpose:** Main validation command with interactive and standard modes.

**Location:** `app/Commands/ValidateCommand.php`

**Key Features:**
- Interactive mode with Laravel Prompts
- Multiple validation scopes (internal, external, anchor, cross-reference)
- Configurable output formats
- Progress tracking and statistics

**Command Signature:**

```bash
validate {path* : Paths to validate (files or directories)}
    {--scope=* : Validation scope (internal,anchor,cross-reference,external,all)}
    {--max-depth=0 : Maximum directory traversal depth (0 = unlimited)}
    {--include-hidden : Include hidden files and directories}
    {--exclude=* : Exclude patterns (glob format)}
    {--check-external : Validate external links (may be slow)}
    {--timeout=30 : External link timeout in seconds}
    {--format=console : Output format (console,json,markdown,html)}
    {--output= : Output file path (stdout if not specified)}
    {--max-broken=50 : Maximum broken links before stopping (0 = unlimited)}
    {--dry-run : Preview mode - show what would be validated}
    {--fix : Enable automatic link fixing where possible}
    {--interactive : Interactive mode with prompts}
    {--cache : Enable caching for external link validation}
    {--no-progress : Disable progress bars}
    {--verbose : Verbose output with detailed information}
```

**Usage Examples:**

```bash
# Basic validation
validate-links validate docs/

# Interactive mode
validate-links validate --interactive

# Comprehensive validation with external links
validate-links validate docs/ --scope=all --check-external

# Generate HTML report
validate-links validate docs/ --format=html --output=report.html

# Dry run with verbose output
validate-links validate docs/ --dry-run --verbose
```

### 6.2.3. FixCommand

**Purpose:** Automatic and interactive link fixing functionality.

**Location:** `app/Commands/FixCommand.php`

**Command Signature:**

```bash
fix {paths* : Paths to fix}
    {--backup : Create backup files before fixing}
    {--interactive : Interactive mode for fixes}
    {--dry-run : Preview fixes without applying them}
```

**Usage Examples:**

```bash
# Automatic fixing with backup
validate-links fix docs/ --backup

# Interactive fixing mode
validate-links fix docs/ --interactive

# Preview fixes without applying
validate-links fix docs/ --dry-run
```

### 6.2.4. ReportCommand

**Purpose:** Generate detailed validation reports in various formats.

**Location:** `app/Commands/ReportCommand.php`

**Command Signature:**

```bash
report {paths* : Paths to analyze}
    {--format=html : Report format (html, markdown, json)}
    {--output= : Output file path}
    {--detailed : Include detailed link analysis}
```

**Usage Examples:**

```bash
# Generate HTML report
validate-links report docs/ --format=html --output=link-report.html

# Detailed JSON report
validate-links report docs/ --format=json --detailed --output=analysis.json

# Markdown summary report
validate-links report docs/ --format=markdown --output=summary.md
```

### 6.2.5. ConfigCommand

**Purpose:** Configuration management and initialization.

**Location:** `app/Commands/ConfigCommand.php`

**Command Signature:**

```bash
config {--init : Initialize configuration file}
       {--show : Show current configuration}
```

**Usage Examples:**

```bash
# Initialize configuration
validate-links config --init

# Show current settings
validate-links config --show
```

## 6.3. Service Contracts Documentation

### 6.3.1. LinkValidationInterface

**Purpose:** Core interface for link validation functionality.

**Location:** `app/Services/Contracts/LinkValidationInterface.php`

**Complete Interface:**

```php
<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;

interface LinkValidationInterface
{
    /**
     * Validate all links in a file.
     */
    public function validateFile(string $filePath, array $scope): array;

    /**
     * Validate a collection of links.
     */
    public function validateLinks(array $links, array $scope): array;

    /**
     * Validate external HTTP/HTTPS links.
     */
    public function validateExternalLinks(array $links): array;

    /**
     * Validate internal file and directory links.
     */
    public function validateInternalLinks(array $links, string $basePath): array;

    /**
     * Validate anchor links within content.
     */
    public function validateAnchorLinks(array $links, string $content): array;

    /**
     * Validate cross-references between files.
     */
    public function validateCrossReferences(array $files): array;

    /**
     * Extract all links from content.
     */
    public function extractLinks(string $content): array;

    /**
     * Categorize links by type (internal, external, anchor).
     */
    public function categorizeLinks(array $links): array;
}
```

**Implementation Example:**

```php
class LinkValidationService implements LinkValidationInterface
{
    public function __construct(
        private SecurityValidationInterface $security,
        private GitHubAnchorInterface $anchorService,
        private StatisticsInterface $statistics
    ) {}

    public function validateFile(string $filePath, array $scope): array
    {
        // Validate file path security
        if (!$this->security->validatePath($filePath)) {
            throw new ValidateLinksException("Invalid file path: {$filePath}");
        }

        $content = file_get_contents($filePath);
        $links = $this->extractLinks($content);
        $categorized = $this->categorizeLinks($links);

        $results = [];

        if (in_array('internal', $scope) || in_array('all', $scope)) {
            $results['internal'] = $this->validateInternalLinks(
                $categorized['internal'], 
                dirname($filePath)
            );
        }

        if (in_array('external', $scope) || in_array('all', $scope)) {
            $results['external'] = $this->validateExternalLinks($categorized['external']);
        }

        if (in_array('anchor', $scope) || in_array('all', $scope)) {
            $results['anchor'] = $this->validateAnchorLinks($categorized['anchor'], $content);
        }

        return $results;
    }

    public function extractLinks(string $content): array
    {
        $links = [];
        
        // Extract markdown links [text](url)
        preg_match_all('/\[([^\]]*)\]\(([^)]+)\)/', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $links[] = [
                'text' => $match[1],
                'url' => $match[2],
                'type' => 'markdown'
            ];
        }

        // Extract HTML links <a href="url">text</a>
        preg_match_all('/<a\s+href=["\']([^"\']+)["\'][^>]*>([^<]*)<\/a>/i', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $links[] = [
                'text' => $match[2],
                'url' => $match[1],
                'type' => 'html'
            ];
        }

        return $links;
    }

    public function categorizeLinks(array $links): array
    {
        $categorized = [
            'internal' => [],
            'external' => [],
            'anchor' => []
        ];

        foreach ($links as $link) {
            $url = $link['url'];

            if (str_starts_with($url, '#')) {
                $categorized['anchor'][] = $link;
            } elseif (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
                $categorized['external'][] = $link;
            } else {
                $categorized['internal'][] = $link;
            }
        }

        return $categorized;
    }
}
```

### 6.3.2. SecurityValidationInterface

**Purpose:** Security validation for paths and URLs.

**Location:** `app/Services/Contracts/SecurityValidationInterface.php`

**Complete Interface:**

```php
<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface SecurityValidationInterface
{
    /**
     * Validate file path for security issues.
     */
    public function validatePath(string $path): bool;

    /**
     * Validate URL for security issues.
     */
    public function validateUrl(string $url): bool;

    /**
     * Validate file size limits.
     */
    public function validateFileSize(string $filePath): bool;

    /**
     * Sanitize path for safe usage.
     */
    public function sanitizePath(string $path): string;

    /**
     * Check for path traversal attempts.
     */
    public function isPathTraversalAttempt(string $path): bool;
}
```

### 6.3.3. StatisticsInterface

**Purpose:** Statistics collection and reporting.

**Location:** `app/Services/Contracts/StatisticsInterface.php`

**Complete Interface:**

```php
<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface StatisticsInterface
{
    /**
     * Reset all statistics counters.
     */
    public function reset(): void;

    /**
     * Increment file processing counter.
     */
    public function incrementFilesProcessed(): void;

    /**
     * Add link statistics for a specific type.
     */
    public function addLinkStats(string $type, int $total, int $broken): void;

    /**
     * Record a broken link with details.
     */
    public function recordBrokenLink(string $link, string $file, string $reason, string $type): void;

    /**
     * Get complete statistics array.
     */
    public function getStatistics(): array;

    /**
     * Get total broken links count.
     */
    public function getTotalBrokenLinks(): int;

    /**
     * Get processed files list.
     */
    public function getProcessedFiles(): array;
}
```

### 6.3.4. ReportingInterface

**Purpose:** Report generation and formatting for validation results.

**Location:** `app/Services/Contracts/ReportingInterface.php`

**Complete Interface:**

```php
<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Services\ValueObjects\ValidationResult;

interface ReportingInterface
{
    /**
     * Generate report in specified format.
     */
    public function generateReport(array $results, string $format): string;

    /**
     * Save report to file.
     */
    public function saveReport(string $report, string $filePath): bool;

    /**
     * Generate summary statistics from results.
     */
    public function generateSummary(array $results): array;

    /**
     * Format validation results for console output.
     */
    public function formatForConsole(array $results): string;

    /**
     * Format validation results as JSON.
     */
    public function formatAsJson(array $results): string;

    /**
     * Format validation results as HTML.
     */
    public function formatAsHtml(array $results): string;

    /**
     * Format validation results as Markdown.
     */
    public function formatAsMarkdown(array $results): string;

    /**
     * Get available output formats.
     */
    public function getAvailableFormats(): array;

    /**
     * Check if format is supported.
     */
    public function supportsFormat(string $format): bool;
}
```

### 6.3.5. GitHubAnchorInterface

**Purpose:** GitHub-style anchor generation and validation.

**Location:** `app/Services/Contracts/GitHubAnchorInterface.php`

**Complete Interface:**

```php
<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface GitHubAnchorInterface
{
    /**
     * Generate GitHub-style anchor from heading text.
     */
    public function generateAnchor(string $headingText): string;

    /**
     * Extract all anchors from markdown content.
     */
    public function extractAnchors(string $content): array;

    /**
     * Validate anchor exists in content.
     */
    public function validateAnchor(string $anchor, string $content): bool;

    /**
     * Normalize anchor for comparison.
     */
    public function normalizeAnchor(string $anchor): string;

    /**
     * Check if anchor follows GitHub conventions.
     */
    public function isValidGitHubAnchor(string $anchor): bool;

    /**
     * Generate anchor map for content.
     */
    public function generateAnchorMap(string $content): array;

    /**
     * Find duplicate anchors in content.
     */
    public function findDuplicateAnchors(string $content): array;
}
```

## 6.4. Value Objects Documentation

### 6.4.1. ValidationConfig

**Purpose:** Immutable configuration object for validation operations.

**Location:** `app/Services/ValueObjects/ValidationConfig.php`

**Complete Implementation:**

```php
<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

final readonly class ValidationConfig
{
    public function __construct(
        public array $paths,
        public array $scope,
        public int $maxDepth,
        public bool $includeHidden,
        public array $excludePatterns,
        public bool $checkExternal,
        public int $timeout,
        public string $format,
        public ?string $output,
        public int $maxBroken,
        public bool $dryRun,
        public bool $fix,
        public bool $interactive,
        public bool $cache,
        public bool $showProgress,
        public bool $verbose
    ) {}

    /**
     * Create configuration from command options.
     */
    public static function fromCommandOptions(array $options, array $arguments): self
    {
        return new self(
            paths: $arguments['path'] ?? [],
            scope: $options['scope'] ?? ['internal', 'anchor'],
            maxDepth: (int) ($options['max-depth'] ?? 0),
            includeHidden: (bool) ($options['include-hidden'] ?? false),
            excludePatterns: $options['exclude'] ?? [],
            checkExternal: (bool) ($options['check-external'] ?? false),
            timeout: (int) ($options['timeout'] ?? 30),
            format: $options['format'] ?? 'console',
            output: $options['output'] ?? null,
            maxBroken: (int) ($options['max-broken'] ?? 50),
            dryRun: (bool) ($options['dry-run'] ?? false),
            fix: (bool) ($options['fix'] ?? false),
            interactive: (bool) ($options['interactive'] ?? false),
            cache: (bool) ($options['cache'] ?? false),
            showProgress: !($options['no-progress'] ?? false),
            verbose: (bool) ($options['verbose'] ?? false)
        );
    }

    /**
     * Create configuration with default values.
     */
    public static function withDefaults(array $overrides = []): self
    {
        $defaults = [
            'paths' => [],
            'scope' => ['internal', 'anchor'],
            'maxDepth' => 0,
            'includeHidden' => false,
            'excludePatterns' => [],
            'checkExternal' => false,
            'timeout' => 30,
            'format' => 'console',
            'output' => null,
            'maxBroken' => 50,
            'dryRun' => false,
            'fix' => false,
            'interactive' => false,
            'cache' => false,
            'showProgress' => true,
            'verbose' => false
        ];

        $config = array_merge($defaults, $overrides);

        return new self(...$config);
    }

    /**
     * Create a copy with modified values.
     */
    public function with(array $changes): self
    {
        $current = [
            'paths' => $this->paths,
            'scope' => $this->scope,
            'maxDepth' => $this->maxDepth,
            'includeHidden' => $this->includeHidden,
            'excludePatterns' => $this->excludePatterns,
            'checkExternal' => $this->checkExternal,
            'timeout' => $this->timeout,
            'format' => $this->format,
            'output' => $this->output,
            'maxBroken' => $this->maxBroken,
            'dryRun' => $this->dryRun,
            'fix' => $this->fix,
            'interactive' => $this->interactive,
            'cache' => $this->cache,
            'showProgress' => $this->showProgress,
            'verbose' => $this->verbose
        ];

        return new self(...array_merge($current, $changes));
    }
}
```

**Usage Examples:**

```php
// Create from command options
$config = ValidationConfig::fromCommandOptions($options, $arguments);

// Create with defaults
$config = ValidationConfig::withDefaults([
    'paths' => ['docs/'],
    'checkExternal' => true,
    'format' => 'json'
]);

// Create modified copy
$newConfig = $config->with(['verbose' => true, 'dryRun' => true]);
```

### 6.4.2. ValidationResult

**Purpose:** Immutable result object containing validation outcomes.

**Location:** `app/Services/ValueObjects/ValidationResult.php`

**Complete Implementation:**

```php
<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

final readonly class ValidationResult
{
    public function __construct(
        public array $files,
        public array $links,
        public array $broken,
        public array $statistics,
        public float $duration,
        public bool $success
    ) {}

    /**
     * Create successful result.
     */
    public static function success(
        array $files,
        array $links,
        array $broken,
        array $statistics,
        float $duration
    ): self {
        return new self($files, $links, $broken, $statistics, $duration, empty($broken));
    }

    /**
     * Create failed result.
     */
    public static function failure(
        array $files,
        array $links,
        array $broken,
        array $statistics,
        float $duration,
        string $error = null
    ): self {
        $stats = $statistics;
        if ($error) {
            $stats['error'] = $error;
        }

        return new self($files, $links, $broken, $stats, $duration, false);
    }

    /**
     * Get total link count.
     */
    public function getTotalLinks(): int
    {
        return count($this->links);
    }

    /**
     * Get broken link count.
     */
    public function getBrokenCount(): int
    {
        return count($this->broken);
    }

    /**
     * Get success rate as percentage.
     */
    public function getSuccessRate(): float
    {
        $total = $this->getTotalLinks();
        if ($total === 0) {
            return 100.0;
        }

        return (($total - $this->getBrokenCount()) / $total) * 100;
    }

    /**
     * Check if validation passed.
     */
    public function isSuccessful(): bool
    {
        return $this->success && $this->getBrokenCount() === 0;
    }

    /**
     * Get formatted summary.
     */
    public function getSummary(): array
    {
        return [
            'files_processed' => count($this->files),
            'total_links' => $this->getTotalLinks(),
            'broken_links' => $this->getBrokenCount(),
            'success_rate' => round($this->getSuccessRate(), 2),
            'duration' => round($this->duration, 2),
            'status' => $this->isSuccessful() ? 'passed' : 'failed'
        ];
    }
}
```

**Usage Examples:**

```php
// Create successful result
$result = ValidationResult::success(
    files: $processedFiles,
    links: $allLinks,
    broken: [],
    statistics: $stats,
    duration: 2.5
);

// Create failed result
$result = ValidationResult::failure(
    files: $processedFiles,
    links: $allLinks,
    broken: $brokenLinks,
    statistics: $stats,
    duration: 3.2,
    error: 'External link validation failed'
);

// Use result data
echo "Success rate: " . $result->getSuccessRate() . "%\n";
echo "Total links: " . $result->getTotalLinks() . "\n";
echo "Status: " . ($result->isSuccessful() ? 'PASSED' : 'FAILED') . "\n";
```

## 6.5. Formatter Services Documentation

### 6.5.1. ConsoleFormatter

**Purpose:** Format validation results for console output with colors and formatting.

**Location:** `app/Services/Formatters/ConsoleFormatter.php`

**Complete Implementation:**

```php
<?php

declare(strict_types=1);

namespace App\Services\Formatters;

use App\Services\ValueObjects\ValidationResult;

final class ConsoleFormatter
{
    /**
     * Format validation result for console output.
     */
    public function format(ValidationResult $result, array $statistics): string
    {
        $output = [];

        // Header
        $output[] = $this->formatHeader($result);

        // Summary
        $output[] = $this->formatSummary($result->getSummary());

        // Broken links details
        if (!empty($result->broken)) {
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

    private function formatSummary(array $summary): string
    {
        $lines = [
            '<fg=blue;options=bold>📊 Validation Summary</>',
            "📁 Files processed: {$summary['files_processed']}",
            "🔗 Total links: {$summary['total_links']}",
            "❌ Broken links: {$summary['broken_links']}",
            "📈 Success rate: {$summary['success_rate']}%",
            "⏱️  Duration: {$summary['duration']}s"
        ];

        return implode("\n", $lines);
    }

    private function formatBrokenLinks(array $broken): string
    {
        $lines = ['<fg=red;options=bold>💥 Broken Links Details</>'];

        foreach ($broken as $link) {
            $lines[] = "  🔴 {$link['url']}";
            $lines[] = "     📄 File: {$link['file']}";
            $lines[] = "     ❗ Reason: {$link['reason']}";
            $lines[] = "";
        }

        return implode("\n", $lines);
    }

    private function formatStatistics(array $statistics): string
    {
        $lines = ['<fg=cyan;options=bold>📈 Detailed Statistics</>'];

        foreach ($statistics as $key => $value) {
            if (is_array($value)) {
                $lines[] = "  {$key}:";
                foreach ($value as $subKey => $subValue) {
                    $lines[] = "    {$subKey}: {$subValue}";
                }
            } else {
                $lines[] = "  {$key}: {$value}";
            }
        }

        return implode("\n", $lines);
    }
}
```

### 6.5.2. JsonFormatter

**Purpose:** Format validation results as JSON for programmatic consumption.

**Location:** `app/Services/Formatters/JsonFormatter.php`

**Complete Implementation:**

```php
<?php

declare(strict_types=1);

namespace App\Services\Formatters;

use App\Services\ValueObjects\ValidationResult;

final class JsonFormatter
{
    /**
     * Format validation result as JSON.
     */
    public function format(ValidationResult $result, array $statistics): string
    {
        $data = [
            'validation' => [
                'status' => $result->isSuccessful() ? 'passed' : 'failed',
                'timestamp' => date('c'),
                'summary' => $result->getSummary()
            ],
            'files' => $result->files,
            'links' => [
                'total' => $result->getTotalLinks(),
                'broken' => $result->getBrokenCount(),
                'success_rate' => $result->getSuccessRate(),
                'details' => $result->links
            ],
            'broken_links' => $result->broken,
            'statistics' => $statistics,
            'performance' => [
                'duration' => $result->duration,
                'files_per_second' => count($result->files) / max($result->duration, 0.001),
                'links_per_second' => $result->getTotalLinks() / max($result->duration, 0.001)
            ]
        ];

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
```

### 6.5.3. HtmlFormatter and MarkdownFormatter

**Purpose:** Generate HTML and Markdown reports with styling and formatting.

**Locations:**
- `app/Services/Formatters/HtmlFormatter.php`
- `app/Services/Formatters/MarkdownFormatter.php`

**Usage Examples:**

```php
// HTML Report Generation
$htmlFormatter = new HtmlFormatter();
$htmlReport = $htmlFormatter->format($validationResult, $statistics);
file_put_contents('validation-report.html', $htmlReport);

// Markdown Report Generation
$markdownFormatter = new MarkdownFormatter();
$markdownReport = $markdownFormatter->format($validationResult, $statistics);
file_put_contents('VALIDATION_REPORT.md', $markdownReport);
```

---

*This documentation provides comprehensive coverage of all core classes with complete implementation examples and usage patterns.*

---

## 📖 Navigation

**[⬅️ Previous: Usage Guide](050-usage-guide.md)** | **[Next: Testing Documentation ➡️](070-testing-documentation.md)** | **[🔝 Top](#6-complete-code-documentation)**
