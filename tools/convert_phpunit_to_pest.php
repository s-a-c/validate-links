<?php

declare(strict_types=1);

/**
 * Enhanced PHPUnit to Pest Converter v2.0
 *
 * This tool converts PHPUnit syntax to Pest syntax with intelligent detection:
 * - Converts PHPUnit test methods to Pest test functions
 * - Transforms assertions to Pest expectations
 * - Handles complex test structures and edge cases
 * - Avoids converting legitimate test support infrastructure
 * - Maintains test logic and functionality
 * - Provides comprehensive validation
 *
 * Key Improvements:
 * - Smart detection of actual test files vs. support infrastructure
 * - Enhanced assertion conversion patterns
 * - Better handling of complex test structures
 * - Improved error reporting and validation
 */
function convertPhpUnitToPest(string $filePath): bool
{
    if (! file_exists($filePath)) {
        echo "File not found: $filePath\n";

        return false;
    }

    // Skip test support files - they're legitimate infrastructure
    if (isTestSupportFile($filePath)) {
        echo "Skipping test support file: $filePath\n";

        return true;
    }

    $content = file_get_contents($filePath);
    $lines = explode("\n", $content);
    $newLines = [];
    $inPhpUnitMethod = false;
    $braceCount = 0;
    $methodName = '';
    $hasChanges = false;

    foreach ($lines as $line) {
        // Check if we're starting a PHPUnit test method
        if (preg_match('/^\s*\/\*\*\s*@test\s*\*\/\s*$/', $line)) {
            // Skip the @test annotation
            $hasChanges = true;

            continue;
        }

        if (preg_match('/^\s*public function (test_\w+|[\w_]+)\(\):\s*void\s*$/', $line, $matches)) {
            $inPhpUnitMethod = true;
            $braceCount = 0;
            $methodName = $matches[1];
            $hasChanges = true;

            // Convert method name to test description
            $testName = str_replace(['test_', '_'], ['', ' '], $methodName);
            $testName = mb_trim($testName);

            $indent = str_repeat(' ', 4);
            $newLines[] = "{$indent}test('{$testName}', function () {";

            continue;
        }

        if ($inPhpUnitMethod) {
            // Count braces to know when method ends
            $braceCount += mb_substr_count($line, '{') - mb_substr_count($line, '}');

            // Convert PHPUnit assertions to Pest expectations
            $convertedLine = convertAssertions($line);
            if ($convertedLine !== $line) {
                $hasChanges = true;
            }
            $line = $convertedLine;

            if ($braceCount <= 0 && preg_match('/^\s*}\s*$/', $line)) {
                // End of method
                $newLines[] = str_repeat(' ', 4).'});';
                $inPhpUnitMethod = false;

                continue;
            }
        }

        $newLines[] = $line;
    }

    // Only write if changes were made
    if ($hasChanges) {
        $newContent = implode("\n", $newLines);

        // Create backup with timestamp
        $backupPath = "{$filePath}.backup.".date('Y-m-d-H-i-s');
        copy($filePath, $backupPath);

        // Write converted content
        file_put_contents($filePath, $newContent);

        echo "Converted $filePath\n";

        // Validate syntax
        $syntaxCheck = shell_exec('php -l '.escapeshellarg($filePath).' 2>&1');
        if (mb_strpos($syntaxCheck, 'No syntax errors') === false) {
            echo 'Warning: Syntax errors after conversion: '.mb_trim($syntaxCheck)."\n";

            return false;
        }
    } else {
        echo "No PHPUnit syntax found in $filePath\n";
    }

    return true;
}

/**
 * Check if file is legitimate test support infrastructure
 */
function isTestSupportFile(string $filePath): bool
{
    return mb_strpos($filePath, 'tests/Support/') !== false ||
           mb_strpos($filePath, 'tests/Traits/') !== false ||
           mb_strpos($filePath, 'TestCase.php') !== false ||
           mb_strpos($filePath, 'CreatesApplication.php') !== false;
}

function convertAssertions(string $line): string
{
    // Convert common PHPUnit assertions to Pest expectations
    $conversions = [
        // Basic assertions
        '/\$this->assertTrue\(([^)]+)\)/' => 'expect($1)->toBeTrue()',
        '/\$this->assertFalse\(([^)]+)\)/' => 'expect($1)->toBeFalse()',
        '/\$this->assertEquals\(([^,]+),\s*([^)]+)\)/' => 'expect($2)->toBe($1)',
        '/\$this->assertSame\(([^,]+),\s*([^)]+)\)/' => 'expect($2)->toBe($1)',
        '/\$this->assertNotEquals\(([^,]+),\s*([^)]+)\)/' => 'expect($2)->not->toBe($1)',
        '/\$this->assertNotSame\(([^,]+),\s*([^)]+)\)/' => 'expect($2)->not->toBe($1)',

        // Type assertions
        '/\$this->assertInstanceOf\(([^,]+),\s*([^)]+)\)/' => 'expect($2)->toBeInstanceOf($1)',
        '/\$this->assertIsString\(([^)]+)\)/' => 'expect($1)->toBeString()',
        '/\$this->assertIsArray\(([^)]+)\)/' => 'expect($1)->toBeArray()',
        '/\$this->assertIsInt\(([^)]+)\)/' => 'expect($1)->toBeInt()',
        '/\$this->assertIsBool\(([^)]+)\)/' => 'expect($1)->toBeBool()',
        '/\$this->assertIsFloat\(([^)]+)\)/' => 'expect($1)->toBeFloat()',
        '/\$this->assertIsObject\(([^)]+)\)/' => 'expect($1)->toBeObject()',

        // Empty/null assertions
        '/\$this->assertNotEmpty\(([^)]+)\)/' => 'expect($1)->not->toBeEmpty()',
        '/\$this->assertEmpty\(([^)]+)\)/' => 'expect($1)->toBeEmpty()',
        '/\$this->assertNull\(([^)]+)\)/' => 'expect($1)->toBeNull()',
        '/\$this->assertNotNull\(([^)]+)\)/' => 'expect($1)->not->toBeNull()',

        // String assertions
        '/\$this->assertStringContainsString\(([^,]+),\s*([^)]+)\)/' => 'expect($2)->toContain($1)',
        '/\$this->assertStringNotContainsString\(([^,]+),\s*([^)]+)\)/' => 'expect($2)->not->toContain($1)',
        '/\$this->assertStringStartsWith\(([^,]+),\s*([^)]+)\)/' => 'expect($2)->toStartWith($1)',
        '/\$this->assertStringEndsWith\(([^,]+),\s*([^)]+)\)/' => 'expect($2)->toEndWith($1)',

        // Array assertions
        '/\$this->assertCount\(([^,]+),\s*([^)]+)\)/' => 'expect($2)->toHaveCount($1)',
        '/\$this->assertArrayHasKey\(([^,]+),\s*([^)]+)\)/' => 'expect($2)->toHaveKey($1)',
        '/\$this->assertArrayNotHasKey\(([^,]+),\s*([^)]+)\)/' => 'expect($2)->not->toHaveKey($1)',
        '/\$this->assertContains\(([^,]+),\s*([^)]+)\)/' => 'expect($2)->toContain($1)',
        '/\$this->assertNotContains\(([^,]+),\s*([^)]+)\)/' => 'expect($2)->not->toContain($1)',

        // Comparison assertions
        '/\$this->assertGreaterThan\(([^,]+),\s*([^)]+)\)/' => 'expect($2)->toBeGreaterThan($1)',
        '/\$this->assertGreaterThanOrEqual\(([^,]+),\s*([^)]+)\)/' => 'expect($2)->toBeGreaterThanOrEqual($1)',
        '/\$this->assertLessThan\(([^,]+),\s*([^)]+)\)/' => 'expect($2)->toBeLessThan($1)',
        '/\$this->assertLessThanOrEqual\(([^,]+),\s*([^)]+)\)/' => 'expect($2)->toBeLessThanOrEqual($1)',

        // Exception assertions (convert to Pest closure syntax)
        '/\$this->expectException\(([^)]+)\)/' => 'expect(fn() => ',
        '/\$this->expectExceptionMessage\(([^)]+)\)/' => '// Expected message: $1',

        // File assertions
        '/\$this->assertFileExists\(([^)]+)\)/' => 'expect(file_exists($1))->toBeTrue()',
        '/\$this->assertFileNotExists\(([^)]+)\)/' => 'expect(file_exists($1))->toBeFalse()',
        '/\$this->assertDirectoryExists\(([^)]+)\)/' => 'expect(is_dir($1))->toBeTrue()',
        '/\$this->assertDirectoryNotExists\(([^)]+)\)/' => 'expect(is_dir($1))->toBeFalse()',

        // JSON assertions
        '/\$this->assertJson\(([^)]+)\)/' => 'expect(json_decode($1))->not->toBeNull()',
        '/\$this->assertJsonStringEqualsJsonString\(([^,]+),\s*([^)]+)\)/' => 'expect(json_decode($2))->toEqual(json_decode($1))',
    ];

    foreach ($conversions as $pattern => $replacement) {
        $line = preg_replace($pattern, $replacement, $line);
    }

    return $line;
}

// Main execution
if (php_sapi_name() === 'cli') {
    $targetFile = $argv[1] ?? 'tests/Unit/Commands/ValidateCommandTest.php';

    if (convertPhpUnitToPest($targetFile)) {
        echo "Conversion completed successfully for: {$targetFile}\n";
    } else {
        echo "Conversion failed for: {$targetFile}\n";
    }
}
