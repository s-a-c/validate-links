<?php

declare(strict_types=1);

/**
 * Test Refactoring Runner
 *
 * Orchestrates the complete test suite refactoring process using all available tools.
 */
final class TestRefactoringRunner
{
    private string $toolsDir;

    private string $projectRoot;

    public function __construct()
    {
        $this->toolsDir = __DIR__;
        $this->projectRoot = dirname(__DIR__);
    }

    public function run(): void
    {
        echo "🚀 Starting Comprehensive Test Suite Refactoring\n";
        echo "================================================\n\n";

        $this->showCurrentStatus();
        $this->runAnalysis();
        $this->runSystematicFixes();
        $this->generateFinalReport();
    }

    private function showCurrentStatus(): void
    {
        echo "📊 Current Status Check\n";
        echo "-----------------------\n";

        $this->runCommand("php {$this->toolsDir}/analyze_tests.php", 'Running analysis...');

        if (file_exists('test_analysis_report.md')) {
            echo "✅ Analysis report generated\n\n";
        }
    }

    private function runAnalysis(): void
    {
        echo "🔍 Detailed Analysis Phase\n";
        echo "--------------------------\n";

        // Check for critical syntax errors
        $criticalFiles = $this->findCriticalFiles();

        if (! empty($criticalFiles)) {
            echo '❌ Critical syntax errors found in '.count($criticalFiles)." files:\n";
            foreach ($criticalFiles as $file) {
                echo "  - {$file}\n";
            }
            echo "\n";
        } else {
            echo "✅ No critical syntax errors found\n\n";
        }
    }

    private function runSystematicFixes(): void
    {
        echo "🔧 Systematic Fixes Phase\n";
        echo "-------------------------\n";

        // Run the comprehensive fixer
        $this->runCommand("php {$this->toolsDir}/fix_test_files.php", 'Running systematic fixes...');
    }

    private function generateFinalReport(): void
    {
        echo "\n📋 Final Validation and Report\n";
        echo "==============================\n";

        // Run final syntax check on all files
        $this->validateAllTestFiles();

        // Generate updated analysis
        $this->runCommand("php {$this->toolsDir}/analyze_tests.php", 'Generating final analysis...');

        // Show summary
        $this->showFinalSummary();

        // Save comprehensive workflow report
        $this->saveWorkflowReport();
    }

    private function findCriticalFiles(): array
    {
        $criticalFiles = [];
        $testFiles = $this->findTestFiles();

        foreach ($testFiles as $file) {
            $syntaxCheck = shell_exec('php -l '.escapeshellarg($file).' 2>&1');
            if (mb_strpos($syntaxCheck, 'No syntax errors') === false) {
                $criticalFiles[] = $file;
            }
        }

        return $criticalFiles;
    }

    private function findTestFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator('tests/', RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php' &&
                mb_strpos($file->getFilename(), 'Test') !== false &&
                mb_strpos($file->getPathname(), 'Support') === false) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function validateAllTestFiles(): void
    {
        echo "🔍 Validating all test files...\n";

        $testFiles = $this->findTestFiles();
        $syntaxErrors = 0;
        $totalFiles = count($testFiles);

        foreach ($testFiles as $file) {
            $syntaxCheck = shell_exec('php -l '.escapeshellarg($file).' 2>&1');
            if (mb_strpos($syntaxCheck, 'No syntax errors') === false) {
                echo "  ❌ {$file}\n";
                $syntaxErrors++;
            }
        }

        $validFiles = $totalFiles - $syntaxErrors;
        echo "\n📊 Validation Results:\n";
        echo "  ✅ Valid files: {$validFiles}/{$totalFiles}\n";
        echo "  ❌ Files with errors: {$syntaxErrors}/{$totalFiles}\n";

        if ($syntaxErrors === 0) {
            echo "  🎉 All test files have valid syntax!\n";
        }
    }

    private function showFinalSummary(): void
    {
        echo "\n🎯 Refactoring Summary\n";
        echo "=====================\n";

        // Count different file types
        $stats = $this->gatherStatistics();

        echo "📈 Statistics:\n";
        echo "  - Total test files: {$stats['total_files']}\n";
        echo "  - Files with valid syntax: {$stats['valid_files']}\n";
        echo "  - Pure Pest files: {$stats['pest_files']}\n";
        echo "  - Mixed syntax files: {$stats['mixed_files']}\n";
        echo "  - Support classes created: {$stats['support_classes']}\n";

        $successRate = $stats['total_files'] > 0 ?
            round(($stats['valid_files'] / $stats['total_files']) * 100, 1) : 0;

        echo "\n🎯 Success Rate: {$successRate}%\n";

        if ($successRate >= 90) {
            echo "🏆 Excellent! Test suite is in great shape.\n";
        } elseif ($successRate >= 75) {
            echo "👍 Good progress! Most issues resolved.\n";
        } else {
            echo "⚠️  More work needed. Check remaining issues.\n";
        }
    }

    private function gatherStatistics(): array
    {
        $testFiles = $this->findTestFiles();
        $stats = [
            'total_files' => count($testFiles),
            'valid_files' => 0,
            'pest_files' => 0,
            'mixed_files' => 0,
            'support_classes' => 0,
        ];

        foreach ($testFiles as $file) {
            // Check syntax
            $syntaxCheck = shell_exec('php -l '.escapeshellarg($file).' 2>&1');
            if (mb_strpos($syntaxCheck, 'No syntax errors') !== false) {
                $stats['valid_files']++;
            }

            // Check file type
            $content = file_get_contents($file);
            $hasPest = mb_strpos($content, 'test(') !== false || mb_strpos($content, 'describe(') !== false;
            $hasPhpUnit = mb_strpos($content, '$this->assert') !== false || mb_strpos($content, '@test') !== false;

            if ($hasPest && ! $hasPhpUnit) {
                $stats['pest_files']++;
            } elseif ($hasPest && $hasPhpUnit) {
                $stats['mixed_files']++;
            }
        }

        // Count support classes
        if (is_dir('tests/Support/Doubles')) {
            $supportIterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator('tests/Support/Doubles', RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($supportIterator as $file) {
                if ($file->getExtension() === 'php') {
                    $stats['support_classes']++;
                }
            }
        }

        return $stats;
    }

    private function saveWorkflowReport(): void
    {
        // Ensure reports directory exists
        if (! is_dir('reports/refactoring')) {
            mkdir('reports/refactoring', 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $dateStamp = date('Y-m-d_H-i-s');
        $stats = $this->gatherStatistics();

        $report = "# Complete Test Refactoring Workflow Report\n";
        $report .= "Generated: {$timestamp}\n\n";

        // Executive Summary
        $report .= "## Executive Summary\n";
        $report .= "Complete systematic refactoring workflow executed with the following results:\n\n";

        $successRate = $stats['total_files'] > 0 ?
            round(($stats['valid_files'] / $stats['total_files']) * 100, 1) : 0;

        $report .= "- **Total Files Processed:** {$stats['total_files']}\n";
        $report .= "- **Files with Valid Syntax:** {$stats['valid_files']}\n";
        $report .= "- **Success Rate:** {$successRate}%\n";
        $report .= "- **Pure Pest Files:** {$stats['pest_files']}\n";
        $report .= "- **Mixed Syntax Files:** {$stats['mixed_files']}\n";
        $report .= "- **Support Classes Created:** {$stats['support_classes']}\n\n";

        // Workflow Steps
        $report .= "## Workflow Steps Executed\n";
        $report .= "1. **Initial Analysis** - Comprehensive test file analysis\n";
        $report .= "2. **Critical File Identification** - Located files with syntax errors\n";
        $report .= "3. **Systematic Fixes** - Applied pattern-based automated fixes\n";
        $report .= "4. **Final Validation** - Confirmed all fixes with syntax checking\n";
        $report .= "5. **Report Generation** - Created comprehensive documentation\n\n";

        // Results Assessment
        $report .= "## Results Assessment\n";
        if ($successRate >= 90) {
            $report .= "🏆 **Excellent Results** - Test suite is in great shape with minimal issues remaining.\n\n";
        } elseif ($successRate >= 75) {
            $report .= "👍 **Good Progress** - Most issues resolved, minor cleanup may be needed.\n\n";
        } else {
            $report .= "⚠️ **Additional Work Needed** - Significant issues remain, manual intervention required.\n\n";
        }

        // Recommendations
        $report .= "## Next Steps\n";
        $report .= "1. Review any remaining syntax errors in individual file reports\n";
        $report .= "2. Run test suite to ensure functionality is preserved\n";
        $report .= "3. Consider additional refactoring for mixed syntax files\n";
        $report .= "4. Maintain regular analysis using the provided tools\n\n";

        // Tool Usage
        $report .= "## Tools Used\n";
        $report .= "- `analyze_tests.php` - Comprehensive analysis and reporting\n";
        $report .= "- `fix_test_files.php` - Systematic pattern-based fixing\n";
        $report .= "- `convert_phpunit_to_pest.php` - PHPUnit to Pest conversion\n";
        $report .= "- `run_test_refactoring.php` - Complete workflow orchestration\n\n";

        // Save reports
        $timestampedFile = "reports/refactoring/workflow_{$dateStamp}.md";
        $latestFile = 'reports/refactoring/latest_workflow.md';

        file_put_contents($timestampedFile, $report);
        file_put_contents($latestFile, $report);

        echo "\nWorkflow reports saved:\n";
        echo "  - Timestamped: {$timestampedFile}\n";
        echo "  - Latest: {$latestFile}\n";
    }

    private function runCommand(string $command, string $description): void
    {
        echo "  {$description}\n";

        $output = shell_exec("cd {$this->projectRoot} && {$command} 2>&1");

        if ($output) {
            // Show only important output (errors or summaries)
            $lines = explode("\n", mb_trim($output));
            foreach ($lines as $line) {
                if (mb_strpos($line, '❌') !== false ||
                    mb_strpos($line, '✅') !== false ||
                    mb_strpos($line, 'Error') !== false ||
                    mb_strpos($line, 'Summary') !== false) {
                    echo "    {$line}\n";
                }
            }
        }
    }
}

// Main execution
if (php_sapi_name() === 'cli') {
    $runner = new TestRefactoringRunner();
    $runner->run();
}
