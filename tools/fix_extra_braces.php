<?php

declare(strict_types=1);

/**
 * Fix Extra Braces in Test Files v2.0.
 *
 * This script fixes the common issue where test functions have extra opening braces
 * after the function declaration, which is a common artifact from PHPUnit to Pest conversion.
 *
 * Enhanced Features:
 * - Smart detection to avoid processing test support files
 * - Improved pattern recognition for various brace issues
 * - Better validation and error reporting
 * - Comprehensive backup and recovery
 */
function fixExtraBraces(string $filePath): bool
{
    if (! file_exists($filePath)) {
        echo "File not found: {$filePath}\n";

        return false;
    }

    // Skip test support files - they're legitimate infrastructure
    if (isTestSupportFile($filePath)) {
        echo "Skipping test support file: {$filePath}\n";

        return true;
    }

    $content = file_get_contents($filePath);
    $lines = explode("\n", $content);
    $fixed = false;
    $fixCount = 0;

    for ($i = 0; $i < count($lines); $i++) {
        $line = $lines[$i];

        // Look for various patterns of extra braces

        // Pattern 1: function () { followed by standalone {
        if (preg_match('/function\s*\(\)\s*\{\s*$/', mb_trim($line))) {
            if (isset($lines[$i + 1]) && mb_trim($lines[$i + 1]) === '{') {
                echo '  Fixing extra brace on line ' . ($i + 2) . " (function pattern)\n";
                array_splice($lines, $i + 1, 1);
                $fixed = true;
                $fixCount++;
            }
        }

        // Pattern 2: test('...', function () { followed by standalone {
        if (preg_match('/test\s*\([^)]+\),?\s*function\s*\(\)\s*\{\s*$/', mb_trim($line))) {
            if (isset($lines[$i + 1]) && mb_trim($lines[$i + 1]) === '{') {
                echo '  Fixing extra brace on line ' . ($i + 2) . " (test pattern)\n";
                array_splice($lines, $i + 1, 1);
                $fixed = true;
                $fixCount++;
            }
        }

        // Pattern 3: describe('...', function () { followed by standalone {
        if (preg_match('/describe\s*\([^)]+\),?\s*function\s*\(\)\s*\{\s*$/', mb_trim($line))) {
            if (isset($lines[$i + 1]) && mb_trim($lines[$i + 1]) === '{') {
                echo '  Fixing extra brace on line ' . ($i + 2) . " (describe pattern)\n";
                array_splice($lines, $i + 1, 1);
                $fixed = true;
                $fixCount++;
            }
        }
    }

    if ($fixed) {
        // Create backup with timestamp
        $backupPath = "{$filePath}.backup." . date('Y-m-d-H-i-s');
        copy($filePath, $backupPath);

        // Write fixed content
        file_put_contents($filePath, implode("\n", $lines));

        echo "✅ Fixed {$fixCount} extra braces in {$filePath}\n";

        return true;
    }
    echo "ℹ️  No extra braces found in {$filePath}\n";

    return true;
}

/**
 * Check if file is legitimate test support infrastructure.
 */
function isTestSupportFile(string $filePath): bool
{
    return mb_strpos($filePath, 'tests/Support/') !== false ||
           mb_strpos($filePath, 'tests/Traits/') !== false ||
           mb_strpos($filePath, 'TestCase.php') !== false ||
           mb_strpos($filePath, 'CreatesApplication.php') !== false;
}

// Main execution
if (PHP_SAPI === 'cli') {
    if (isset($argv[1])) {
        // Fix specific file
        $targetFile = $argv[1];
        echo "🔧 Fixing extra braces in: {$targetFile}\n";

        if (fixExtraBraces($targetFile)) {
            // Validate syntax after fix
            $syntaxCheck = shell_exec('php -l ' . escapeshellarg($targetFile) . ' 2>&1');
            if (mb_strpos($syntaxCheck, 'No syntax errors') !== false) {
                echo "✅ Syntax validation passed\n";
            } else {
                echo '❌ Syntax errors remain: ' . mb_trim($syntaxCheck) . "\n";
            }
        }
    } else {
        // Process all test files
        echo "🔧 Fixing extra braces in all test files...\n\n";

        $testFiles = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator('tests/', RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php' &&
                mb_strpos($file->getFilename(), 'Test') !== false &&
                ! isTestSupportFile($file->getPathname())) {
                $testFiles[] = $file->getPathname();
            }
        }

        $fixedCount = 0;
        $totalFiles = count($testFiles);

        foreach ($testFiles as $file) {
            echo "Processing: {$file}\n";
            if (fixExtraBraces($file)) {
                $fixedCount++;
            }
        }

        echo "\n📊 Summary:\n";
        echo "- Total files processed: {$totalFiles}\n";
        echo "- Files with fixes applied: {$fixedCount}\n";
        echo '- Success rate: ' . ($totalFiles > 0 ? round(($fixedCount / $totalFiles) * 100, 1) : 0) . "%\n";
    }
}
