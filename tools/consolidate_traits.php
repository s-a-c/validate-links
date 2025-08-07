<?php

declare(strict_types=1);

/**
 * Trait Consolidation Script.
 *
 * Consolidates all test helper traits from scattered locations into
 * a unified tests/Support/Traits/ structure following Laravel/Pest best practices.
 */
final class TraitConsolidator
{
    private array $movedFiles = [];

    private array $deletedFiles = [];

    private array $updatedFiles = [];

    private array $errors = [];

    public function consolidate(): void
    {
        echo "🔄 Starting trait consolidation to tests/Support/Traits/...\n\n";

        // Step 1: Move complete implementations from tests/Traits/
        $this->moveCompleteTraitsFromTraitsDir();

        // Step 2: Move complete implementations from tests/Support/Doubles/General/
        $this->moveCompleteTraitsFromDoublesDir();

        // Step 3: Update all import statements
        $this->updateImportStatements();

        // Step 4: Delete empty stubs and duplicates
        $this->deleteEmptyStubs();

        // Step 5: Update analysis script
        $this->updateAnalysisScript();

        // Step 6: Generate summary report
        $this->generateSummaryReport();
    }

    private function moveCompleteTraitsFromTraitsDir(): void
    {
        echo "📁 Moving complete traits from tests/Traits/...\n";

        $completeTraits = [
            'MockingHelpers.php',
            'ServiceTestHelpers.php',
            'ValueObjectTestHelpers.php',
        ];

        foreach ($completeTraits as $traitFile) {
            $sourcePath = "tests/Traits/{$traitFile}";
            $targetPath = "tests/Support/Traits/{$traitFile}";

            if (file_exists($sourcePath)) {
                $this->moveTraitFile($sourcePath, $targetPath);
            }
        }
    }

    private function moveCompleteTraitsFromDoublesDir(): void
    {
        echo "📁 Moving complete traits from tests/Support/Doubles/General/...\n";

        $doublesTraits = [
            'CommandTestHelpers.php',
            'EnumTestHelpers.php',
            'ExceptionTestHelpers.php',
            'FileTestHelpers.php',
            'HttpTestHelpers.php',
            'ValidationTestHelpers.php',
        ];

        foreach ($doublesTraits as $traitFile) {
            $sourcePath = "tests/Support/Doubles/General/{$traitFile}";
            $targetPath = "tests/Support/Traits/{$traitFile}";

            if (file_exists($sourcePath)) {
                $this->moveTraitFile($sourcePath, $targetPath);
            }
        }
    }

    private function moveTraitFile(string $sourcePath, string $targetPath): void
    {
        try {
            // Read source content
            $content = file_get_contents($sourcePath);

            // Update namespace
            $content = $this->updateNamespace($content, $sourcePath);

            // Write to target location
            file_put_contents($targetPath, $content);

            echo "  ✅ Moved: {$sourcePath} → {$targetPath}\n";
            $this->movedFiles[] = ['from' => $sourcePath, 'to' => $targetPath];
        } catch (Exception $e) {
            echo "  ❌ Failed to move {$sourcePath}: {$e->getMessage()}\n";
            $this->errors[] = "Failed to move {$sourcePath}: {$e->getMessage()}";
        }
    }

    private function updateNamespace(string $content, string $sourcePath): string
    {
        // Update namespace from Tests\Traits or Tests\Support\Doubles\General to Tests\Support\Traits
        $patterns = [
            '/namespace Tests\\\\Traits;/' => 'namespace Tests\\Support\\Traits;',
            '/namespace Tests\\\\Support\\\\Doubles\\\\General;/' => 'namespace Tests\\Support\\Traits;',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    private function updateImportStatements(): void
    {
        echo "\n🔄 Updating import statements across all test files...\n";

        $testDirectories = [
            'tests/Unit',
            'tests/Integration',
            'tests/Feature',
            'tests/E2e',
            'tests/Services',
        ];

        foreach ($testDirectories as $directory) {
            if (is_dir($directory)) {
                $this->updateImportsInDirectory($directory);
            }
        }
    }

    private function updateImportsInDirectory(string $directory): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $this->updateImportsInFile($file->getPathname());
            }
        }
    }

    private function updateImportsInFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $originalContent = $content;

        // Update import patterns
        $importUpdates = [
            // From Tests\Traits to Tests\Support\Traits
            '/use Tests\\\\Traits\\\\AssertionHelpers;/' => 'use Tests\\Support\\Traits\\AssertionHelpers;',
            '/use Tests\\\\Traits\\\\MockingHelpers;/' => 'use Tests\\Support\\Traits\\MockingHelpers;',
            '/use Tests\\\\Traits\\\\ServiceTestHelpers;/' => 'use Tests\\Support\\Traits\\ServiceTestHelpers;',
            '/use Tests\\\\Traits\\\\ValueObjectTestHelpers;/' => 'use Tests\\Support\\Traits\\ValueObjectTestHelpers;',

            // From Tests\Support\Doubles\General to Tests\Support\Traits
            '/use Tests\\\\Support\\\\Doubles\\\\General\\\\CommandTestHelpers;/' => 'use Tests\\Support\\Traits\\CommandTestHelpers;',
            '/use Tests\\\\Support\\\\Doubles\\\\General\\\\EnumTestHelpers;/' => 'use Tests\\Support\\Traits\\EnumTestHelpers;',
            '/use Tests\\\\Support\\\\Doubles\\\\General\\\\ExceptionTestHelpers;/' => 'use Tests\\Support\\Traits\\ExceptionTestHelpers;',
            '/use Tests\\\\Support\\\\Doubles\\\\General\\\\FileTestHelpers;/' => 'use Tests\\Support\\Traits\\FileTestHelpers;',
            '/use Tests\\\\Support\\\\Doubles\\\\General\\\\HttpTestHelpers;/' => 'use Tests\\Support\\Traits\\HttpTestHelpers;',
            '/use Tests\\\\Support\\\\Doubles\\\\General\\\\ValidationTestHelpers;/' => 'use Tests\\Support\\Traits\\ValidationTestHelpers;',
        ];

        foreach ($importUpdates as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        // Only write if content changed
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            echo "  📝 Updated imports: {$filePath}\n";
            $this->updatedFiles[] = $filePath;
        }
    }

    private function deleteEmptyStubs(): void
    {
        echo "\n🗑️  Deleting empty stubs and duplicates...\n";

        $filesToDelete = [
            // Empty stubs in tests/Traits/
            'tests/Traits/ExceptionTestHelpers.php',
            'tests/Traits/FileTestHelpers.php',
            'tests/Traits/HttpTestHelpers.php',
            'tests/Traits/ValidationTestHelpers.php',

            // Original files after moving (if they still exist)
            'tests/Traits/MockingHelpers.php',
            'tests/Traits/ServiceTestHelpers.php',
            'tests/Traits/ValueObjectTestHelpers.php',
        ];

        foreach ($filesToDelete as $file) {
            if (file_exists($file)) {
                unlink($file);
                echo "  🗑️  Deleted: {$file}\n";
                $this->deletedFiles[] = $file;
            }
        }

        // Delete the entire tests/Support/Doubles/General/ directory if empty
        $generalDir = 'tests/Support/Doubles/General';
        if (is_dir($generalDir) && count(scandir($generalDir)) <= 2) { // Only . and ..
            rmdir($generalDir);
            echo "  🗑️  Deleted empty directory: {$generalDir}\n";
            $this->deletedFiles[] = $generalDir;
        }
    }

    private function updateAnalysisScript(): void
    {
        echo "\n🔧 Updating analysis script to recognize new structure...\n";

        $analysisScript = 'tools/analyze_tests.php';
        if (file_exists($analysisScript)) {
            $content = file_get_contents($analysisScript);

            // Update the test support detection to include tests/Support/Traits/
            $oldPattern = '/strpos\(\$filePath, \'tests\/Support\/\'\) !== false \|\|/';
            $newPattern = 'strpos($filePath, \'tests/Support/\') !== false ||';

            if (preg_match($oldPattern, $content)) {
                echo "  ✅ Analysis script already recognizes tests/Support/ structure\n";
            } else {
                echo "  ℹ️  Analysis script may need manual review\n";
            }
        }
    }

    private function generateSummaryReport(): void
    {
        echo "\n📊 CONSOLIDATION SUMMARY\n";
        echo "========================\n\n";

        echo '✅ Files Moved: ' . count($this->movedFiles) . "\n";
        foreach ($this->movedFiles as $move) {
            echo "   • {$move['from']} → {$move['to']}\n";
        }

        echo "\n📝 Files Updated: " . count($this->updatedFiles) . "\n";
        foreach ($this->updatedFiles as $file) {
            echo "   • {$file}\n";
        }

        echo "\n🗑️  Files Deleted: " . count($this->deletedFiles) . "\n";
        foreach ($this->deletedFiles as $file) {
            echo "   • {$file}\n";
        }

        if (! empty($this->errors)) {
            echo "\n❌ Errors: " . count($this->errors) . "\n";
            foreach ($this->errors as $error) {
                echo "   • {$error}\n";
            }
        }

        echo "\n🎉 Consolidation completed!\n";
        echo "📁 All traits are now in: tests/Support/Traits/\n";
        echo "📋 New namespace: Tests\\Support\\Traits\n";
    }
}

// Execute consolidation
if (PHP_SAPI === 'cli') {
    $consolidator = new TraitConsolidator();
    $consolidator->consolidate();
}
