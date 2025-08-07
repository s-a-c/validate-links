<?php

declare(strict_types=1);

/**
 * Test Suite Validation Tool.
 *
 * Validates the entire test suite for syntax errors and generates comprehensive reports.
 * Saves results in organized reports/validation/ directory.
 */
final class TestSuiteValidator
{
    private array $results = [];

    private int $totalFiles = 0;

    private int $validFiles = 0;

    private int $errorFiles = 0;

    public function validateTestSuite(): void
    {
        echo "🔍 Starting Test Suite Validation\n";
        echo "=================================\n\n";

        $testFiles = $this->findTestFiles();
        $this->totalFiles = count($testFiles);

        echo "Validating {$this->totalFiles} test files...\n\n";

        foreach ($testFiles as $file) {
            $this->validateFile($file);
        }

        $this->generateReport();
        $this->showSummary();
    }

    private function findTestFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator('tests/', RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php' &&
                mb_strpos($file->getFilename(), 'Test') !== false) {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    private function validateFile(string $filePath): void
    {
        echo "Validating: {$filePath}";

        $syntaxCheck = shell_exec('php -l ' . escapeshellarg($filePath) . ' 2>&1');
        $isValid = mb_strpos($syntaxCheck, 'No syntax errors') !== false;

        if ($isValid) {
            echo " ✅\n";
            $this->validFiles++;
            $this->results[$filePath] = [
                'status' => 'valid',
                'message' => 'No syntax errors detected',
            ];
        } else {
            echo " ❌\n";
            $this->errorFiles++;
            $this->results[$filePath] = [
                'status' => 'error',
                'message' => mb_trim($syntaxCheck),
            ];
            echo '  Error: ' . mb_trim($syntaxCheck) . "\n";
        }
    }

    private function generateReport(): void
    {
        // Ensure reports directory exists
        if (! is_dir('reports/validation')) {
            mkdir('reports/validation', 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $dateStamp = date('Y-m-d_H-i-s');

        $report = "# Test Suite Validation Report\n";
        $report .= "Generated: {$timestamp}\n\n";

        // Summary
        $successRate = $this->totalFiles > 0 ?
            round(($this->validFiles / $this->totalFiles) * 100, 1) : 0;

        $report .= "## Summary\n";
        $report .= "- **Total Files:** {$this->totalFiles}\n";
        $report .= "- **Valid Files:** {$this->validFiles}\n";
        $report .= "- **Files with Errors:** {$this->errorFiles}\n";
        $report .= "- **Success Rate:** {$successRate}%\n\n";

        // Status indicator
        if ($this->errorFiles === 0) {
            $report .= "🎉 **All tests have valid syntax!** The test suite is ready for execution.\n\n";
        } else {
            $report .= "⚠️ **{$this->errorFiles} files have syntax errors** that need to be resolved.\n\n";
        }

        // Valid files
        if ($this->validFiles > 0) {
            $report .= "## Valid Files ({$this->validFiles})\n";
            foreach ($this->results as $file => $result) {
                if ($result['status'] === 'valid') {
                    $report .= "- ✅ `{$file}`\n";
                }
            }
            $report .= "\n";
        }

        // Error files
        if ($this->errorFiles > 0) {
            $report .= "## Files with Syntax Errors ({$this->errorFiles})\n";
            foreach ($this->results as $file => $result) {
                if ($result['status'] === 'error') {
                    $report .= "- ❌ `{$file}`\n";
                    $report .= "  ```\n";
                    $report .= "  {$result['message']}\n";
                    $report .= "  ```\n\n";
                }
            }
        }

        // Recommendations
        $report .= "## Recommendations\n";
        if ($this->errorFiles === 0) {
            $report .= "- ✅ Test suite is ready for execution\n";
            $report .= "- ✅ All syntax validation passed\n";
            $report .= "- 🚀 Consider running the actual test suite to verify functionality\n";
        } else {
            $report .= "- 🔧 Fix syntax errors using `php tools/fix_test_files.php`\n";
            $report .= "- 📊 Run analysis using `php tools/analyze_tests.php`\n";
            $report .= "- 🔄 Re-run validation after fixes\n";
        }
        $report .= "\n";

        // Statistics
        $report .= "## Statistics\n\n";
        $report .= "| Metric | Count | Percentage |\n";
        $report .= "|--------|-------|------------|\n";
        $report .= "| Total Files | {$this->totalFiles} | 100% |\n";
        $report .= "| Valid Files | {$this->validFiles} | {$successRate}% |\n";
        $report .= "| Error Files | {$this->errorFiles} | " . round(($this->errorFiles / $this->totalFiles) * 100, 1) . "% |\n";

        // Save reports
        $timestampedFile = "reports/validation/validation_{$dateStamp}.md";
        $latestFile = 'reports/validation/latest_validation.md';

        file_put_contents($timestampedFile, $report);
        file_put_contents($latestFile, $report);

        echo "\nValidation reports saved:\n";
        echo "  - Timestamped: {$timestampedFile}\n";
        echo "  - Latest: {$latestFile}\n";
    }

    private function showSummary(): void
    {
        echo "\n📊 Validation Summary\n";
        echo "====================\n";
        echo "Total Files: {$this->totalFiles}\n";
        echo "Valid Files: {$this->validFiles}\n";
        echo "Error Files: {$this->errorFiles}\n";

        $successRate = $this->totalFiles > 0 ?
            round(($this->validFiles / $this->totalFiles) * 100, 1) : 0;
        echo "Success Rate: {$successRate}%\n";

        if ($this->errorFiles === 0) {
            echo "\n🎉 All tests have valid syntax!\n";
        } else {
            echo "\n⚠️ {$this->errorFiles} files need attention.\n";
        }
    }
}

// Main execution
if (PHP_SAPI === 'cli') {
    $validator = new TestSuiteValidator();
    $validator->validateTestSuite();
}
