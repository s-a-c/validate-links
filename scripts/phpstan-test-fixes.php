<?php

declare(strict_types=1);

/**
 * PHPStan Level 10 Test Compliance Automation Script.
 *
 * This script automates the bulk fixes needed for PHPStan Level 10 compliance
 * in Pest test files by handling common patterns like $this-> property access.
 */
final class PHPStanTestFixer
{
    private array $stats = [
        'files_processed' => 0,
        'this_references_fixed' => 0,
        'use_clauses_added' => 0,
        'helper_calls_fixed' => 0,
        'json_operations_fixed' => 0,
    ];

    public function __construct(
        private string $testsDirectory = 'tests',
    ) {}

    public function fixAllTestFiles(): void
    {
        echo "🚀 Starting PHPStan Level 10 Test Compliance Fixes...\n\n";

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

        // Apply all fixes
        $content = $this->fixPestPropertyAccess($content);
        $content = $this->fixHelperMethodCalls($content);
        $content = $this->fixJsonOperations($content);
        $content = $this->addMissingImports($content);

        // Only write if changes were made
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            echo "✅ Fixed: {$filePath}\n";
        } else {
            echo "⏭️  No changes needed: {$filePath}\n";
        }
    }

    private function fixPestPropertyAccess(string $content): string
    {
        // Pattern 1: Fix $this->property references in test functions
        $patterns = [
            // Common property patterns
            '/\$this->projectDir/' => '$projectDir',
            '/\$this->docsDir/' => '$docsDir',
            '/\$this->reportsDir/' => '$reportsDir',
            '/\$this->performanceDir/' => '$performanceDir',
            '/\$this->metricsFile/' => '$metricsFile',
            '/\$this->largeDocsDir/' => '$largeDocsDir',
            '/\$this->tempDir/' => '$tempDir',
            '/\$this->configFile/' => '$configFile',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $oldCount = mb_substr_count($content, mb_trim($pattern, '/'));
            $content = preg_replace($pattern, $replacement, $content);
            $newCount = mb_substr_count($content, $replacement);
            $this->stats['this_references_fixed'] += ($oldCount - mb_substr_count($content, mb_trim($pattern, '/')));
        }

        // Pattern 2: Add use clauses to test functions that need them
        $content = $this->addUseClauses($content);

        return $content;
    }

    private function addUseClauses(string $content): string
    {
        // This is a complex pattern that needs manual review
        // For now, we'll focus on the simpler replacements
        // and let developers add use clauses manually with guidance

        return $content;
    }

    private function fixHelperMethodCalls(string $content): string
    {
        // Pattern: Fix $this->helperMethod() calls
        $patterns = [
            '/\$this->createRealisticDocumentation\(\)/' => 'createRealisticDocumentation($docsDir)',
            '/\$this->createLargeDocumentationSet\(\)/' => 'createLargeDocumentationSet($docsDir)',
            '/\$this->createPerformanceTestData\(\)/' => 'createPerformanceTestData($performanceDir)',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $oldCount = mb_substr_count($content, mb_trim($pattern, '/'));
            $content = preg_replace($pattern, $replacement, $content);
            $this->stats['helper_calls_fixed'] += $oldCount - mb_substr_count($content, $replacement);
        }

        return $content;
    }

    private function fixJsonOperations(string $content): string
    {
        // Pattern 1: Fix json_encode() with File::put()
        $pattern = '/File::put\(([^,]+),\s*json_encode\(([^)]+)\)\)/';
        $replacement = '$jsonData = json_encode($2);' . "\n" .
                      '            expect($jsonData)->toBeString();' . "\n" .
                      '            File::put($1, $jsonData)';

        $oldCount = preg_match_all($pattern, $content);
        $content = preg_replace($pattern, $replacement, $content);
        $this->stats['json_operations_fixed'] += $oldCount;

        // Pattern 2: Add type assertions for json_decode
        $pattern = '/(\$\w+)\s*=\s*json_decode\(([^)]+)\);\s*\n/';
        $replacement = '$1 = json_decode($2);' . "\n" .
                      '            expect($1)->toBeArray();' . "\n";

        $content = preg_replace($pattern, $replacement, $content);

        return $content;
    }

    private function addMissingImports(string $content): string
    {
        // Check if Mockery import is needed
        if (mb_strpos($content, 'Mockery::mock') !== false &&
            mb_strpos($content, 'use Mockery;') === false) {
            // Find the last use statement and add Mockery after it
            $pattern = '/(use [^;]+;)(\s*\n)/';
            preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);

            if (! empty($matches[0])) {
                $lastMatch = end($matches[0]);
                $insertPos = $lastMatch[1] + mb_strlen($lastMatch[0]);
                $content = substr_replace($content, "use Mockery;\n", $insertPos, 0);
            }
        }

        return $content;
    }

    private function printSummary(): void
    {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "📊 PHPStan Test Fixes Summary\n";
        echo str_repeat('=', 60) . "\n";
        echo "Files processed: {$this->stats['files_processed']}\n";
        echo "\$this-> references fixed: {$this->stats['this_references_fixed']}\n";
        echo "Use clauses added: {$this->stats['use_clauses_added']}\n";
        echo "Helper calls fixed: {$this->stats['helper_calls_fixed']}\n";
        echo "JSON operations fixed: {$this->stats['json_operations_fixed']}\n";
        echo str_repeat('=', 60) . "\n";
        echo "✅ Automation complete! Run PHPStan to check remaining issues.\n";
    }
}

// Run the fixer
if (PHP_SAPI === 'cli') {
    $fixer = new PHPStanTestFixer();
    $fixer->fixAllTestFiles();
}
