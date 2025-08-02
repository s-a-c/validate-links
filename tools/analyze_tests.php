<?php

declare(strict_types=1);

/**
 * Comprehensive Test File Analysis Script
 *
 * This script analyzes all test files in the tests/ directory to identify:
 * 1. Syntax errors
 * 2. Mixed PHPUnit/Pest syntax
 * 3. Embedded helper classes
 * 4. Missing imports
 * 5. Namespace issues
 */
function analyzeTestFile(string $filePath): array
{
    $analysis = [
        'file' => $filePath,
        'syntax_valid' => false,
        'issues' => [],
        'line_count' => 0,
        'has_phpunit_syntax' => false,
        'has_pest_syntax' => false,
        'has_embedded_classes' => false,
        'missing_imports' => [],
        'namespace_issues' => [],
    ];

    if (! file_exists($filePath)) {
        $analysis['issues'][] = 'File does not exist';

        return $analysis;
    }

    $content = file_get_contents($filePath);
    $lines = explode("\n", $content);
    $analysis['line_count'] = count($lines);

    // Check syntax
    $syntaxCheck = shell_exec('php -l '.escapeshellarg($filePath).' 2>&1');
    $analysis['syntax_valid'] = mb_strpos($syntaxCheck, 'No syntax errors') !== false;

    if (! $analysis['syntax_valid']) {
        $analysis['issues'][] = 'Syntax error: '.mb_trim($syntaxCheck);
    }

    // Analyze content patterns
    foreach ($lines as $lineNum => $line) {
        $lineNum++; // 1-based line numbers

        // Check for PHPUnit syntax
        if (preg_match('/\$this->(assert|expect)/', $line) ||
            preg_match('/public function test_/', $line) ||
            preg_match('/@test/', $line)) {
            $analysis['has_phpunit_syntax'] = true;
        }

        // Check for Pest syntax
        if (preg_match('/^(test|it|describe)\s*\(/', mb_trim($line))) {
            $analysis['has_pest_syntax'] = true;
        }

        // Check for embedded classes (but exclude legitimate test support files)
        if (preg_match('/^(class|interface|trait)\s+\w+/', mb_trim($line))) {
            // Skip test support directories - these are legitimate
            $isTestSupport = mb_strpos($filePath, 'tests/Support/') !== false ||
                mb_strpos($filePath, 'tests/Traits/') !== false ||
                mb_strpos($filePath, 'TestCase.php') !== false ||
                mb_strpos($filePath, 'CreatesApplication.php') !== false;

            if (! $isTestSupport) {
                $analysis['has_embedded_classes'] = true;
                $analysis['issues'][] = "Embedded class/interface/trait found at line $lineNum: ".mb_trim($line);
            }
        }

        // Check for malformed Pest syntax
        if (preg_match('/expect\([^)]+\)->\w+\(\)/', $line)) {
            $analysis['issues'][] = "Potential malformed Pest syntax at line $lineNum: ".mb_trim($line);
        }

        // Check for missing use statements (basic check)
        if (preg_match('/new\s+([A-Z]\w+)/', $line, $matches)) {
            $className = $matches[1];
            if (! preg_match("/use.*\\\\$className;/", $content) &&
                ! preg_match("/use.*$className;/", $content) &&
                ! in_array($className, ['Exception', 'ReflectionClass', 'DateTime', 'stdClass'])) {
                $analysis['missing_imports'][] = $className;
            }
        }
    }

    // Check namespace consistency
    if (preg_match('/^namespace\s+(.*);/m', $content, $matches)) {
        $namespace = mb_trim($matches[1]);
        $expectedNamespace = str_replace(['/', '.php'], ['\\', ''], $filePath);
        $expectedNamespace = str_replace('tests\\', 'Tests\\', $expectedNamespace);

        if (mb_strpos($expectedNamespace, $namespace) === false) {
            $analysis['namespace_issues'][] = "Namespace '$namespace' doesn't match expected path-based namespace";
        }
    }

    // Check for mixed syntax
    if ($analysis['has_phpunit_syntax'] && $analysis['has_pest_syntax']) {
        $analysis['issues'][] = 'Mixed PHPUnit and Pest syntax detected';
    }

    return $analysis;
}

function findTestFiles(string $directory): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
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

function generateReport(array $analyses): string
{
    $report = "# Test File Analysis Report\n";
    $report .= 'Generated: '.date('Y-m-d H:i:s')."\n\n";

    $totalFiles = count($analyses);
    $syntaxErrors = array_filter($analyses, fn ($a) => ! $a['syntax_valid']);
    $mixedSyntax = array_filter($analyses, fn ($a) => $a['has_phpunit_syntax'] && $a['has_pest_syntax']);
    $embeddedClasses = array_filter($analyses, fn ($a) => $a['has_embedded_classes']);

    $report .= "## Summary\n";
    $report .= "- Total files analyzed: $totalFiles\n";
    $report .= '- Files with syntax errors: '.count($syntaxErrors)."\n";
    $report .= '- Files with mixed PHPUnit/Pest syntax: '.count($mixedSyntax)."\n";
    $report .= '- Files with embedded classes: '.count($embeddedClasses)."\n\n";

    $report .= "## Priority Issues\n\n";

    if (! empty($syntaxErrors)) {
        $report .= "### Syntax Errors (Critical)\n";
        foreach ($syntaxErrors as $analysis) {
            $report .= "- **{$analysis['file']}**\n";
            foreach ($analysis['issues'] as $issue) {
                if (mb_strpos($issue, 'Syntax error') === 0) {
                    $report .= "  - $issue\n";
                }
            }
            $report .= "\n";
        }
    }

    if (! empty($mixedSyntax)) {
        $report .= "### Mixed Syntax Issues (High Priority)\n";
        foreach ($mixedSyntax as $analysis) {
            $report .= "- **{$analysis['file']}** ({$analysis['line_count']} lines)\n";
        }
        $report .= "\n";
    }

    if (! empty($embeddedClasses)) {
        $report .= "### Embedded Classes (Medium Priority)\n";
        foreach ($embeddedClasses as $analysis) {
            $report .= "- **{$analysis['file']}**\n";
            foreach ($analysis['issues'] as $issue) {
                if (mb_strpos($issue, 'Embedded') === 0) {
                    $report .= "  - $issue\n";
                }
            }
            $report .= "\n";
        }
    }

    return $report;
}

// Main execution
$testFiles = findTestFiles('tests/');
$analyses = [];

echo 'Analyzing '.count($testFiles)." test files...\n";

foreach ($testFiles as $file) {
    echo "Analyzing: $file\n";
    $analyses[] = analyzeTestFile($file);
}

// Ensure reports directory exists
if (! is_dir('reports/analysis')) {
    mkdir('reports/analysis', 0755, true);
}

$report = generateReport($analyses);

// Generate timestamped and latest reports
$timestamp = date('Y-m-d_H-i-s');
$timestampedFile = "reports/analysis/test_analysis_{$timestamp}.md";
$latestFile = 'reports/analysis/latest_analysis.md';

file_put_contents($timestampedFile, $report);
file_put_contents($latestFile, $report);

echo "\nAnalysis complete. Reports saved:\n";
echo "  - Timestamped: {$timestampedFile}\n";
echo "  - Latest: {$latestFile}\n";
echo "\nSummary:\n";
echo '- Total files: '.count($analyses)."\n";
echo '- Syntax errors: '.count(array_filter($analyses, fn ($a) => ! $a['syntax_valid']))."\n";
echo '- Mixed syntax: '.count(array_filter($analyses, fn ($a) => $a['has_phpunit_syntax'] && $a['has_pest_syntax']))."\n";
echo '- Embedded classes: '.count(array_filter($analyses, fn ($a) => $a['has_embedded_classes']))."\n";
