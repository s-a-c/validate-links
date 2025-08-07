<?php

declare(strict_types=1);

/**
 * Simple Pest Test Fixes for PHPStan Level 10 Compliance.
 *
 * This script handles the most common and safe bulk replacements:
 * 1. $this->property → $property (variable references)
 * 2. mock(Class::class) → Mockery::mock(Class::class)
 * 3. Add missing Mockery imports
 */
final class SimplePestFixer
{
    private array $stats = [
        'files_processed' => 0,
        'this_references_fixed' => 0,
        'mock_calls_fixed' => 0,
        'imports_added' => 0,
    ];

    public function __construct(
        private string $testsDirectory = 'tests',
        private bool $dryRun = false,
    ) {}

    public function fixAllTestFiles(): void
    {
        echo "🚀 Starting Simple Pest Fixes for PHPStan Level 10...\n";
        echo $this->dryRun ? "🔍 DRY RUN MODE - No files will be modified\n" : "✏️  WRITE MODE - Files will be modified\n";
        echo "\n";

        $testFiles = $this->findTestFiles();

        foreach ($testFiles as $file) {
            echo "📁 Processing: {$file}\n";
            $this->fixTestFile($file);
            $this->stats['files_processed']++;
        }

        $this->printSummary();
    }

    private function findTestFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->testsDirectory),
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function fixTestFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            echo "❌ Could not read file: {$filePath}\n";

            return;
        }

        $originalContent = $content;
        $changes = [];

        // Fix 1: Replace $this->property references
        $content = $this->fixThisPropertyReferences($content, $changes);

        // Fix 2: Replace mock() calls with Mockery::mock()
        $content = $this->fixMockCalls($content, $changes);

        // Fix 3: Add missing Mockery import if needed
        $content = $this->addMockeryImport($content, $changes);

        // Report changes
        if (! empty($changes)) {
            echo "  📝 Changes made:\n";
            foreach ($changes as $change) {
                echo "    - {$change}\n";
            }
        }

        // Write file if changes were made and not in dry run mode
        if ($content !== $originalContent) {
            if (! $this->dryRun) {
                file_put_contents($filePath, $content);
                echo "  ✅ File updated\n";
            } else {
                echo "  🔍 Would update file (dry run)\n";
            }
        } else {
            echo "  ⏭️  No changes needed\n";
        }

        echo "\n";
    }

    private function fixThisPropertyReferences(string $content, array &$changes): string
    {
        $patterns = [
            '$this->projectDir' => '$projectDir',
            '$this->docsDir' => '$docsDir',
            '$this->reportsDir' => '$reportsDir',
            '$this->performanceDir' => '$performanceDir',
            '$this->metricsFile' => '$metricsFile',
            '$this->largeDocsDir' => '$largeDocsDir',
            '$this->tempDir' => '$tempDir',
            '$this->configFile' => '$configFile',
            '$this->linkValidation' => '$linkValidation',
            '$this->reporting' => '$reporting',
            '$this->command' => '$command',
        ];

        foreach ($patterns as $search => $replace) {
            $count = mb_substr_count($content, $search);
            if ($count > 0) {
                $content = str_replace($search, $replace, $content);
                $changes[] = "Replaced {$count} instances of '{$search}' with '{$replace}'";
                $this->stats['this_references_fixed'] += $count;
            }
        }

        return $content;
    }

    private function fixMockCalls(string $content, array &$changes): string
    {
        // Pattern: mock(ClassName::class) → Mockery::mock(ClassName::class)
        $pattern = '/\bmock\(([^)]+::class)\)/';
        $count = preg_match_all($pattern, $content);

        if ($count > 0) {
            $content = preg_replace($pattern, 'Mockery::mock($1)', $content);
            $changes[] = "Fixed {$count} mock() calls to use Mockery::mock()";
            $this->stats['mock_calls_fixed'] += $count;
        }

        return $content;
    }

    private function addMockeryImport(string $content, array &$changes): string
    {
        // Only add if Mockery::mock is used but import is missing
        if (mb_strpos($content, 'Mockery::mock') !== false &&
            mb_strpos($content, 'use Mockery;') === false) {
            // Find the position after the last use statement
            preg_match_all('/^use [^;]+;$/m', $content, $matches, PREG_OFFSET_CAPTURE);

            if (! empty($matches[0])) {
                $lastUse = end($matches[0]);
                $insertPos = $lastUse[1] + mb_strlen($lastUse[0]);
                $content = substr_replace($content, "\nuse Mockery;", $insertPos, 0);
                $changes[] = "Added 'use Mockery;' import";
                $this->stats['imports_added']++;
            }
        }

        return $content;
    }

    private function printSummary(): void
    {
        echo str_repeat('=', 60) . "\n";
        echo "📊 Simple Pest Fixes Summary\n";
        echo str_repeat('=', 60) . "\n";
        echo "Files processed: {$this->stats['files_processed']}\n";
        echo "\$this-> references fixed: {$this->stats['this_references_fixed']}\n";
        echo "Mock calls fixed: {$this->stats['mock_calls_fixed']}\n";
        echo "Imports added: {$this->stats['imports_added']}\n";
        echo str_repeat('=', 60) . "\n";

        if ($this->dryRun) {
            echo "🔍 This was a DRY RUN - no files were modified\n";
            echo "💡 Run without --dry-run to apply changes\n";
        } else {
            echo "✅ Fixes applied! Next steps:\n";
            echo "   1. Run: ./vendor/bin/phpstan analyse tests/ --level=10\n";
            echo "   2. Manually add 'use' clauses to test functions\n";
            echo "   3. Fix remaining type safety issues\n";
        }
    }
}

// CLI interface
if (PHP_SAPI === 'cli') {
    $dryRun = in_array('--dry-run', $argv);
    $testsDir = 'tests';

    // Allow custom tests directory
    foreach ($argv as $i => $arg) {
        if ($arg === '--dir' && isset($argv[$i + 1])) {
            $testsDir = $argv[$i + 1];
            break;
        }
    }

    echo "Simple Pest Fixes for PHPStan Level 10 Compliance\n";
    echo "================================================\n\n";

    if ($dryRun) {
        echo "🔍 DRY RUN MODE: Use --dry-run to preview changes without modifying files\n\n";
    }

    $fixer = new SimplePestFixer($testsDir, $dryRun);
    $fixer->fixAllTestFiles();
}
