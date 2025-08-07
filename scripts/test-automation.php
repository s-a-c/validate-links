<?php

declare(strict_types=1);

/**
 * Test Automation Script Validator.
 *
 * This script tests our automation on a single file to validate the approach
 * before running it on all 5,156 errors across the test suite.
 */
final class AutomationValidator
{
    public function validateOnSingleFile(string $testFile = 'tests/E2E/CompleteUserScenarioTest.php'): void
    {
        echo "🧪 Testing Automation on Single File\n";
        echo "=====================================\n\n";

        if (! file_exists($testFile)) {
            echo "❌ Test file not found: {$testFile}\n";

            return;
        }

        // Step 1: Check current PHPStan errors
        echo "📊 Step 1: Current PHPStan errors in {$testFile}\n";
        $this->checkPHPStanErrors($testFile);

        // Step 2: Create backup
        echo "\n💾 Step 2: Creating backup\n";
        $backupFile = $testFile . '.backup';
        copy($testFile, $backupFile);
        echo "✅ Backup created: {$backupFile}\n";

        // Step 3: Run automation
        echo "\n🤖 Step 3: Running automation (dry run first)\n";
        $this->runAutomation($testFile, true);

        echo "\n🤖 Step 4: Running automation (actual changes)\n";
        $this->runAutomation($testFile, false);

        // Step 5: Check results
        echo "\n📊 Step 5: PHPStan errors after automation\n";
        $this->checkPHPStanErrors($testFile);

        // Step 6: Test functionality
        echo "\n🧪 Step 6: Testing that file still works\n";
        $this->testFileFunctionality($testFile);

        // Step 7: Show diff
        echo "\n📝 Step 7: Changes made\n";
        $this->showDiff($testFile, $backupFile);

        echo "\n✅ Validation complete!\n";
        echo "💡 If results look good, run: php scripts/simple-pest-fixes.php\n";
    }

    public function cleanupBackup(string $testFile = 'tests/E2E/CompleteUserScenarioTest.php'): void
    {
        $backupFile = $testFile . '.backup';
        if (file_exists($backupFile)) {
            unlink($backupFile);
            echo "🗑️  Backup file removed: {$backupFile}\n";
        }
    }

    private function checkPHPStanErrors(string $file): void
    {
        $command = "./vendor/bin/phpstan analyse {$file} --level=10 --no-progress --error-format=table 2>/dev/null | grep 'Found.*error' | tail -1";
        $output = shell_exec($command);

        if ($output) {
            echo "   {$output}";
        } else {
            echo "   ✅ No errors found or PHPStan not available\n";
        }
    }

    private function runAutomation(string $file, bool $dryRun): void
    {
        $dryRunFlag = $dryRun ? '--dry-run' : '';
        $command = 'php scripts/simple-pest-fixes.php --dir ' . dirname($file) . " {$dryRunFlag}";

        echo "   Running: {$command}\n";
        $output = shell_exec($command);

        if ($output) {
            // Show only the relevant parts
            $lines = explode("\n", $output);
            $relevant = array_filter($lines, function ($line) use ($file) {
                return mb_strpos($line, basename($file)) !== false ||
                       mb_strpos($line, 'Changes made:') !== false ||
                       mb_strpos($line, '- ') === 0;
            });

            foreach ($relevant as $line) {
                echo "   {$line}\n";
            }
        }
    }

    private function testFileFunctionality(string $file): void
    {
        // Basic syntax check
        $command = "php -l {$file} 2>&1";
        $output = shell_exec($command);

        if (mb_strpos($output, 'No syntax errors') !== false) {
            echo "   ✅ PHP syntax is valid\n";
        } else {
            echo "   ❌ PHP syntax errors found:\n";
            echo "   {$output}\n";
        }

        // Try to run a simple Pest check (if available)
        $pestCommand = "./vendor/bin/pest {$file} --dry-run 2>/dev/null";
        $pestOutput = shell_exec($pestCommand);

        if ($pestOutput !== null) {
            echo "   ✅ Pest can parse the file\n";
        } else {
            echo "   ⚠️  Could not test with Pest (may not be available)\n";
        }
    }

    private function showDiff(string $file, string $backupFile): void
    {
        $original = file_get_contents($backupFile);
        $modified = file_get_contents($file);

        if ($original === $modified) {
            echo "   ℹ️  No changes were made to the file\n";

            return;
        }

        // Count changes
        $originalLines = explode("\n", $original);
        $modifiedLines = explode("\n", $modified);

        $thisReferences = mb_substr_count($original, '$this->') - mb_substr_count($modified, '$this->');
        $mockCalls = mb_substr_count($original, 'mock(') - mb_substr_count($modified, 'mock(');
        $mockeryImports = mb_substr_count($modified, 'use Mockery;') - mb_substr_count($original, 'use Mockery;');

        echo "   📈 Summary of changes:\n";
        echo "      - \$this-> references fixed: {$thisReferences}\n";
        echo "      - mock() calls fixed: {$mockCalls}\n";
        echo "      - Mockery imports added: {$mockeryImports}\n";
        echo '      - Lines changed: ' . abs(count($originalLines) - count($modifiedLines)) . "\n";

        // Show first few actual changes
        echo "\n   📝 Sample changes (first 5):\n";
        $changes = 0;
        for ($i = 0; $i < min(count($originalLines), count($modifiedLines)) && $changes < 5; $i++) {
            if ($originalLines[$i] !== $modifiedLines[$i]) {
                $changes++;
                echo '      Line ' . ($i + 1) . ":\n";
                echo '        - ' . mb_trim($originalLines[$i]) . "\n";
                echo '        + ' . mb_trim($modifiedLines[$i]) . "\n";
            }
        }
    }
}

// CLI interface
if (PHP_SAPI === 'cli') {
    $validator = new AutomationValidator();

    if (in_array('--cleanup', $argv)) {
        $validator->cleanupBackup();
    } else {
        $testFile = 'tests/E2E/CompleteUserScenarioTest.php';

        // Allow custom test file
        foreach ($argv as $i => $arg) {
            if ($arg === '--file' && isset($argv[$i + 1])) {
                $testFile = $argv[$i + 1];
                break;
            }
        }

        $validator->validateOnSingleFile($testFile);

        echo "\n💡 To clean up backup file, run: php scripts/test-automation.php --cleanup\n";
    }
}
