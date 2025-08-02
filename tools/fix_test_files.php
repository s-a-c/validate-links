<?php

declare(strict_types=1);

/**
 * Enhanced Test File Fixer v2.0
 *
 * This tool systematically fixes test files with intelligent detection by:
 * 1. Analyzing syntax errors and patterns with smart filtering
 * 2. Applying proven refactoring patterns from successful conversions
 * 3. Converting mixed PHPUnit/Pest syntax to pure Pest
 * 4. Handling trait method calls vs. direct Pest assertions
 * 5. Avoiding false positives for legitimate test infrastructure
 * 6. Validating fixes with comprehensive checks
 *
 * Key Improvements:
 * - Smart detection of legitimate test support vs. actual issues
 * - Enhanced pattern recognition for mixed syntax conversion
 * - Improved handling of trait-based test helpers
 * - Better validation and error reporting
 */
final class TestFileFixer
{
    private array $fixedFiles = [];

    private array $errors = [];

    private string $supportDir = 'tests/Support/Doubles';

    public function __construct()
    {
        if (! is_dir($this->supportDir)) {
            mkdir($this->supportDir, 0755, true);
        }
    }

    public function fixAllTestFiles(): void
    {
        echo "🔧 Starting systematic test file fixing...\n\n";

        $testFiles = $this->findTestFiles('tests/');
        $priorityFiles = $this->prioritizeFiles($testFiles);

        foreach ($priorityFiles as $priority => $files) {
            echo "📋 Processing {$priority} priority files:\n";
            foreach ($files as $file) {
                $this->fixTestFile($file);
            }
            echo "\n";
        }

        $this->generateReport();
    }

    public function fixTestFile(string $filePath): bool
    {
        echo "🔍 Analyzing: {$filePath}\n";

        if (! file_exists($filePath)) {
            $this->errors[] = "File not found: {$filePath}";

            return false;
        }

        $analysis = $this->analyzeFile($filePath);

        // Skip test support files - they're legitimate infrastructure
        if ($analysis['is_test_support']) {
            echo "  ℹ️  Skipping test support file (legitimate infrastructure)\n";

            return true;
        }

        // Create backup only if we need to make changes
        $needsChanges = ! $analysis['syntax_valid'] ||
                       $analysis['has_mixed_syntax'] ||
                       $analysis['has_embedded_classes'] ||
                       $analysis['has_trait_method_calls'];

        $backupPath = null;
        if ($needsChanges) {
            $backupPath = $filePath.'.backup.'.date('Y-m-d-H-i-s');
            copy($filePath, $backupPath);
        }

        if (! $analysis['syntax_valid']) {
            echo "  ❌ Syntax errors detected\n";

            return $this->fixSyntaxErrors($filePath, $analysis);
        }

        if ($analysis['has_mixed_syntax']) {
            echo "  🔄 Mixed syntax detected\n";

            return $this->fixMixedSyntax($filePath, $analysis);
        }

        if ($analysis['has_trait_method_calls']) {
            echo "  🔧 Converting trait method calls to pure Pest syntax\n";

            return $this->convertTraitMethodCalls($filePath, $analysis);
        }

        if ($analysis['has_embedded_classes']) {
            echo "  📦 Embedded classes detected\n";

            return $this->extractEmbeddedClasses($filePath, $analysis);
        }

        echo "  ✅ File is already clean\n";
        if ($backupPath && file_exists($backupPath)) {
            unlink($backupPath); // Remove backup if no changes needed
        }

        return true;
    }

    private function analyzeFile(string $filePath): array
    {
        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);

        $analysis = [
            'file' => $filePath,
            'syntax_valid' => false,
            'has_mixed_syntax' => false,
            'has_embedded_classes' => false,
            'has_phpunit_class' => false,
            'has_trait_method_calls' => false,
            'embedded_classes' => [],
            'line_count' => count($lines),
            'is_test_support' => $this->isTestSupportFile($filePath),
        ];

        // Check syntax
        $syntaxCheck = shell_exec('php -l '.escapeshellarg($filePath).' 2>&1');
        $analysis['syntax_valid'] = mb_strpos($syntaxCheck, 'No syntax errors') !== false;

        // Skip detailed analysis for test support files - they're legitimate
        if ($analysis['is_test_support']) {
            return $analysis;
        }

        // Analyze patterns for actual test files
        $hasPest = false;
        $hasPhpUnit = false;
        $hasTraitMethods = false;

        foreach ($lines as $lineNum => $line) {
            // Check for Pest syntax
            if (preg_match('/^(test|it|describe)\s*\(/', mb_trim($line))) {
                $hasPest = true;
            }

            // Check for PHPUnit syntax (but distinguish trait methods)
            if (preg_match('/\$this->assert(?!ValueObject)/', $line) ||
                preg_match('/public function test_/', $line) ||
                preg_match('/@test/', $line)) {
                $hasPhpUnit = true;
            }

            // Check for trait method calls (these are legitimate in Pest)
            if (preg_match('/\$this->assertValueObject/', $line)) {
                $hasTraitMethods = true;
            }

            // Check for PHPUnit class
            if (preg_match('/class\s+\w+Test\s+extends\s+TestCase/', $line)) {
                $analysis['has_phpunit_class'] = true;
            }

            // Check for embedded classes (only in actual test files)
            if (preg_match('/^(class|interface|trait)\s+(\w+)/', mb_trim($line), $matches)) {
                if (! preg_match('/extends\s+TestCase/', $line)) {
                    $analysis['embedded_classes'][] = [
                        'name' => $matches[2],
                        'type' => $matches[1],
                        'line' => $lineNum + 1,
                    ];
                }
            }
        }

        $analysis['has_mixed_syntax'] = $hasPest && $hasPhpUnit;
        $analysis['has_trait_method_calls'] = $hasTraitMethods;
        $analysis['has_embedded_classes'] = ! empty($analysis['embedded_classes']);

        return $analysis;
    }

    /**
     * Check if file is legitimate test support infrastructure
     */
    private function isTestSupportFile(string $filePath): bool
    {
        return mb_strpos($filePath, 'tests/Support/') !== false ||
               mb_strpos($filePath, 'tests/Traits/') !== false ||
               mb_strpos($filePath, 'TestCase.php') !== false ||
               mb_strpos($filePath, 'CreatesApplication.php') !== false;
    }

    private function fixSyntaxErrors(string $filePath, array $analysis): bool
    {
        echo "  🔧 Fixing syntax errors...\n";

        $content = file_get_contents($filePath);
        $originalContent = $content;

        // Apply systematic fixes based on discovered patterns
        $content = $this->fixMissingSemicolons($content);
        $content = $this->fixExtraOpeningBraces($content);
        $content = $this->fixMalformedPestExpectations($content);
        $content = $this->fixMissingDescribeClosing($content);

        // Handle duplicate PHPUnit classes
        if ($analysis['has_phpunit_class'] && mb_strpos($content, 'test(') !== false) {
            echo "    📝 Removing duplicate PHPUnit class...\n";
            $content = $this->removeDuplicatePhpUnitClass($content);
        }

        // Only write if content changed
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            echo "    📝 Applied systematic fixes\n";
        }

        // Validate fix
        $syntaxCheck = shell_exec('php -l '.escapeshellarg($filePath).' 2>&1');
        $isFixed = mb_strpos($syntaxCheck, 'No syntax errors') !== false;

        if ($isFixed) {
            echo "  ✅ Syntax errors fixed\n";
            $this->fixedFiles[] = $filePath;

            return true;
        }
        echo "  ❌ Could not fix syntax errors automatically\n";
        echo '    Error: '.mb_trim($syntaxCheck)."\n";

        return false;

    }

    private function fixMissingSemicolons(string $content): string
    {
        // Fix missing semicolons after expect statements
        $content = preg_replace('/expect\([^)]+\)->to[A-Z][a-zA-Z]*\([^)]*\)\s*$/m', '$0;', $content);

        return $content;
    }

    private function fixExtraOpeningBraces(string $content): string
    {
        $lines = explode("\n", $content);
        $fixed = false;

        for ($i = 0; $i < count($lines) - 1; $i++) {
            // Look for function () { followed by standalone {
            if (preg_match('/function\s*\(\)\s*\{\s*$/', mb_trim($lines[$i]))) {
                if (isset($lines[$i + 1]) && mb_trim($lines[$i + 1]) === '{') {
                    // Remove the extra brace line
                    array_splice($lines, $i + 1, 1);
                    $fixed = true;
                }
            }
        }

        return $fixed ? implode("\n", $lines) : $content;
    }

    private function fixMalformedPestExpectations(string $content): string
    {
        // Fix ->method() patterns to proper expectations
        $patterns = [
            '/expect\(([^)]+)\)->method\(\);/' => 'expect($1)->toBeTrue();',
            '/expect\(method_exists\(([^)]+)\)\)->method\(\);/' => 'expect(method_exists($1))->toBeTrue();',
            '/expect\(\$([^-]+)->isPublic\(\)\)->method\(\);/' => 'expect($$1->isPublic())->toBeTrue();',
            '/expect\(\$([^-]+)->getReturnType\(\)\)->method\(\)/' => 'expect($$1->getReturnType())->toBeNull();',
            '/expect\(\$([^-]+)->bound\([^)]+\)\)->method\(\);/' => 'expect($$1->bound($2))->toBeTrue();',
            '/expect\(\$([^-]+)->isBooted\(\)\)->method\(\)/' => 'expect($$1->isBooted())->toBeTrue();',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    private function fixMissingDescribeClosing(string $content): string
    {
        $lines = explode("\n", $content);
        $lastLine = mb_trim(end($lines));

        // If file ends with } instead of });, fix it
        if ($lastLine === '}' && mb_strpos($content, 'describe(') !== false) {
            $lines[count($lines) - 1] = '});';

            return implode("\n", $lines);
        }

        return $content;
    }

    private function removeDuplicatePhpUnitClass(string $content): string
    {
        // Find the end of Pest tests (look for the last });)
        $lines = explode("\n", $content);
        $pestEndLine = -1;

        for ($i = count($lines) - 1; $i >= 0; $i--) {
            if (mb_trim($lines[$i]) === '});' && $pestEndLine === -1) {
                // Look backwards to find the matching test( or describe(
                $braceCount = 0;
                for ($j = $i; $j >= 0; $j--) {
                    if (mb_strpos($lines[$j], '});') !== false) {
                        $braceCount++;
                    }
                    if (preg_match('/^(test|describe|it)\s*\(/', mb_trim($lines[$j]))) {
                        $braceCount--;
                        if ($braceCount === 1) {
                            $pestEndLine = $i;
                            break 2;
                        }
                    }
                }
            }
        }

        if ($pestEndLine > 0) {
            // Remove everything after the Pest section
            $pestLines = array_slice($lines, 0, $pestEndLine + 1);

            return implode("\n", $pestLines)."\n";
        }

        return $content;
    }

    private function fixMixedSyntax(string $filePath, array $analysis): bool
    {
        echo "  🔄 Converting PHPUnit to Pest syntax...\n";

        // Use the existing conversion tool
        $result = shell_exec('cd '.dirname(__DIR__).' && php tools/convert_phpunit_to_pest.php '.escapeshellarg($filePath).' 2>&1');

        // Validate conversion
        $syntaxCheck = shell_exec('php -l '.escapeshellarg($filePath).' 2>&1');
        $isFixed = mb_strpos($syntaxCheck, 'No syntax errors') !== false;

        if ($isFixed) {
            echo "  ✅ Mixed syntax converted\n";
            $this->fixedFiles[] = $filePath;

            return true;
        }
        echo '  ❌ Conversion failed: '.mb_trim($syntaxCheck)."\n";

        return false;

    }

    /**
     * Convert trait method calls to inline Pest syntax
     * Based on successful patterns from ValidationConfigTest.php and ValidationResultTest.php
     */
    private function convertTraitMethodCalls(string $filePath, array $analysis): bool
    {
        echo "  🔧 Converting trait method calls to pure Pest syntax...\n";

        $content = file_get_contents($filePath);
        $originalContent = $content;

        // Convert common trait method patterns to inline Pest syntax
        $patterns = [
            // assertValueObjectImmutable
            '/\$this->assertValueObjectImmutable\(([^)]+)\);/' => $this->generateImmutabilityCheck('$1'),

            // assertValueObjectConstructor
            '/\$this->assertValueObjectConstructor\(([^,]+),\s*(\[[^\]]+\])\);/' => $this->generateConstructorCheck('$1', '$2'),

            // assertValueObjectGetters
            '/\$this->assertValueObjectGetters\(([^,]+),\s*(\[[^\]]+\])\);/' => $this->generateGettersCheck('$1', '$2'),

            // assertValueObjectToArray
            '/\$this->assertValueObjectToArray\(([^,]+),\s*(\[[^\]]+\])\);/' => $this->generateToArrayCheck('$1', '$2'),

            // assertValueObjectSerializable
            '/\$this->assertValueObjectSerializable\(([^)]+)\);/' => $this->generateSerializableCheck('$1'),

            // assertValueObjectValidation
            '/\$this->assertValueObjectValidation\(([^,]+),\s*(\[[^\]]+\])\);/' => $this->generateValidationCheck('$1', '$2'),

            // assertValueObjectEquality
            '/\$this->assertValueObjectEquality\(([^,]+),\s*([^,]+),\s*(true|false)\);/' => $this->generateEqualityCheck('$1', '$2', '$3'),

            // assertValueObjectStringRepresentation
            '/\$this->assertValueObjectStringRepresentation\(([^)]+)\);/' => $this->generateStringRepresentationCheck('$1'),

            // assertValueObjectJsonRepresentation
            '/\$this->assertValueObjectJsonRepresentation\(([^)]+)\);/' => $this->generateJsonRepresentationCheck('$1'),
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace_callback($pattern, function ($matches) use ($replacement) {
                // Replace placeholders with actual captured groups
                $result = $replacement;
                for ($i = 1; $i < count($matches); $i++) {
                    $result = str_replace('$'.$i, $matches[$i], $result);
                }

                return $result;
            }, $content);
        }

        // Only write if content changed
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            echo "    📝 Converted trait method calls to inline Pest syntax\n";
        }

        // Validate conversion
        $syntaxCheck = shell_exec('php -l '.escapeshellarg($filePath).' 2>&1');
        $isFixed = mb_strpos($syntaxCheck, 'No syntax errors') !== false;

        if ($isFixed) {
            echo "  ✅ Trait method calls converted successfully\n";
            $this->fixedFiles[] = $filePath;

            return true;
        }
        echo '  ❌ Conversion failed: '.mb_trim($syntaxCheck)."\n";

        return false;

    }

    private function extractEmbeddedClasses(string $filePath, array $analysis): bool
    {
        echo "  📦 Extracting embedded classes...\n";

        foreach ($analysis['embedded_classes'] as $classInfo) {
            $this->extractClass($filePath, $classInfo);
        }

        $this->fixedFiles[] = $filePath;

        return true;
    }

    private function extractClass(string $filePath, array $classInfo): void
    {
        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);

        // Find class definition and extract it
        $classStart = $classInfo['line'] - 1;
        $classEnd = $this->findClassEnd($lines, $classStart);

        if ($classEnd === -1) {
            echo "    ⚠️  Could not find end of class {$classInfo['name']}\n";

            return;
        }

        $classLines = array_slice($lines, $classStart, $classEnd - $classStart + 1);
        $classContent = implode("\n", $classLines);

        // Determine target directory based on file path
        $targetDir = $this->determineTargetDirectory($filePath);
        $targetFile = $targetDir.'/'.$classInfo['name'].'.php';

        // Create target directory
        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Generate proper class file
        $namespace = $this->generateNamespace($targetDir);
        $fullClassContent = $this->generateClassFile($namespace, $classContent, $filePath);

        file_put_contents($targetFile, $fullClassContent);
        echo "    ✅ Extracted {$classInfo['name']} to {$targetFile}\n";

        // Remove class from original file and add import
        $this->removeClassAndAddImport($filePath, $classStart, $classEnd, $namespace, $classInfo['name']);
    }

    private function findClassEnd(array $lines, int $start): int
    {
        $braceCount = 0;
        $inClass = false;

        for ($i = $start; $i < count($lines); $i++) {
            $line = $lines[$i];

            if (mb_strpos($line, '{') !== false) {
                $braceCount += mb_substr_count($line, '{');
                $inClass = true;
            }

            if (mb_strpos($line, '}') !== false) {
                $braceCount -= mb_substr_count($line, '}');

                if ($inClass && $braceCount === 0) {
                    return $i;
                }
            }
        }

        return -1;
    }

    private function determineTargetDirectory(string $filePath): string
    {
        if (mb_strpos($filePath, 'Contracts') !== false) {
            return $this->supportDir.'/Contracts';
        }
        if (mb_strpos($filePath, 'Commands') !== false) {
            return $this->supportDir.'/Commands';
        }
        if (mb_strpos($filePath, 'Services') !== false) {
            return $this->supportDir.'/Services';
        }

        return $this->supportDir.'/General';
    }

    private function generateNamespace(string $targetDir): string
    {
        $relativePath = str_replace('tests/', 'Tests/', $targetDir);

        return str_replace('/', '\\', $relativePath);
    }

    private function generateClassFile(string $namespace, string $classContent, string $originalFile): string
    {
        $template = "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$namespace};\n\n";

        // Add common imports based on original file
        $originalContent = file_get_contents($originalFile);
        if (mb_strpos($originalContent, 'ExtensionInterface') !== false) {
            $template .= "use App\\Contracts\\ExtensionInterface;\n";
        }
        if (mb_strpos($originalContent, 'ReportingInterface') !== false) {
            $template .= "use App\\Services\\Contracts\\ReportingInterface;\n";
        }
        if (mb_strpos($originalContent, 'ValidationConfig') !== false) {
            $template .= "use App\\Services\\ValueObjects\\ValidationConfig;\n";
        }

        $template .= "\n/**\n * Test implementation extracted from {$originalFile}\n */\n";
        $template .= $classContent;

        return $template;
    }

    private function removeClassAndAddImport(string $filePath, int $start, int $end, string $namespace, string $className): void
    {
        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);

        // Remove class lines
        array_splice($lines, $start, $end - $start + 1);

        // Add import after existing use statements
        $importLine = "use {$namespace}\\{$className};";
        $insertPosition = $this->findImportInsertPosition($lines);

        if ($insertPosition !== -1) {
            array_splice($lines, $insertPosition, 0, [$importLine]);
        }

        file_put_contents($filePath, implode("\n", $lines));
    }

    private function findImportInsertPosition(array $lines): int
    {
        $lastUseStatement = -1;

        foreach ($lines as $i => $line) {
            if (preg_match('/^use\s+/', mb_trim($line))) {
                $lastUseStatement = $i;
            }
        }

        return $lastUseStatement !== -1 ? $lastUseStatement + 1 : -1;
    }

    /**
     * Generate inline immutability check
     */
    private function generateImmutabilityCheck(string $object): string
    {
        return "
        // Assert immutability
        \$reflection = new \\ReflectionClass({$object});
        \$properties = \$reflection->getProperties();
        foreach (\$properties as \$property) {
            expect(
                \$property->isReadOnly() || \$property->isPrivate() || \$property->isProtected()
            )->toBeTrue(\"Property {\$property->getName()} should be readonly or private/protected for immutability\");
        }";
    }

    /**
     * Generate inline constructor check
     */
    private function generateConstructorCheck(string $className, string $expectedParams): string
    {
        return "
        \$reflection = new \\ReflectionClass({$className});
        \$constructor = \$reflection->getConstructor();

        expect(\$constructor)->not->toBeNull(\"Value object should have a constructor\");

        \$parameters = \$constructor->getParameters();
        \$parameterNames = array_map(fn(\$param) => \$param->getName(), \$parameters);

        \$expectedParameters = {$expectedParams};
        foreach (\$expectedParameters as \$expectedParam) {
            expect(\$parameterNames)->toContain(
                \$expectedParam,
                \"Constructor should have parameter: {\$expectedParam}\"
            );
        }";
    }

    /**
     * Generate inline getters check
     */
    private function generateGettersCheck(string $object, string $expectedGetters): string
    {
        return "
        \$reflection = new \\ReflectionClass({$object});
        \$expectedGetters = {$expectedGetters};

        foreach (\$expectedGetters as \$getter) {
            expect(\$reflection->hasMethod(\$getter))->toBeTrue(
                \"Value object should have getter method: {\$getter}\"
            );

            \$method = \$reflection->getMethod(\$getter);
            expect(\$method->isPublic())->toBeTrue(
                \"Getter method {\$getter} should be public\"
            );
        }";
    }

    /**
     * Generate inline toArray check
     */
    private function generateToArrayCheck(string $object, string $expectedKeys): string
    {
        return "
        expect(method_exists({$object}, 'toArray'))->toBeTrue(
            \"Value object should have toArray method\"
        );

        \$array = {$object}->toArray();
        expect(\$array)->toBeArray();

        \$expectedKeys = {$expectedKeys};
        foreach (\$expectedKeys as \$key) {
            expect(\$array)->toHaveKey(\$key);
        }";
    }

    /**
     * Generate inline serializable check
     */
    private function generateSerializableCheck(string $object): string
    {
        return "
        \$serialized = serialize({$object});
        \$unserialized = unserialize(\$serialized);

        expect(\$unserialized)->toBeInstanceOf(get_class({$object}));
        expect(\$unserialized)->toEqual({$object});";
    }

    /**
     * Generate inline validation check
     */
    private function generateValidationCheck(string $className, string $invalidData): string
    {
        return "
        \$invalidData = {$invalidData};

        expect(fn() => {$className}::create(\$invalidData))
            ->toThrow(\\InvalidArgumentException::class);";
    }

    /**
     * Generate inline equality check
     */
    private function generateEqualityCheck(string $object1, string $object2, string $shouldBeEqual): string
    {
        if ($shouldBeEqual === 'true') {
            return "expect({$object1})->toEqual({$object2});";
        }

        return "expect({$object1})->not->toEqual({$object2});";

    }

    /**
     * Generate inline string representation check
     */
    private function generateStringRepresentationCheck(string $object): string
    {
        return "
        expect(method_exists({$object}, '__toString'))->toBeTrue(
            \"Value object should have __toString method\"
        );

        \$string = (string) {$object};
        expect(\$string)->toBeString();
        expect(\$string)->not->toBeEmpty();";
    }

    /**
     * Generate inline JSON representation check
     */
    private function generateJsonRepresentationCheck(string $object): string
    {
        return "
        expect(
            method_exists({$object}, 'jsonSerialize') || method_exists({$object}, 'toArray')
        )->toBeTrue(\"Value object should be JSON serializable\");

        \$json = json_encode({$object});
        expect(\$json)->toBeString();
        expect(\$json)->not->toBeFalse();

        \$decoded = json_decode(\$json, true);
        expect(\$decoded)->toBeArray();";
    }

    private function findTestFiles(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php' &&
                mb_strpos($file->getFilename(), 'Test') !== false &&
                mb_strpos($file->getPathname(), 'Support') === false) {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    private function prioritizeFiles(array $files): array
    {
        $priority = [
            'critical' => [],
            'high' => [],
            'medium' => [],
            'low' => [],
        ];

        foreach ($files as $file) {
            $analysis = $this->analyzeFile($file);

            if (! $analysis['syntax_valid']) {
                $priority['critical'][] = $file;
            } elseif ($analysis['has_mixed_syntax']) {
                $priority['high'][] = $file;
            } elseif ($analysis['has_embedded_classes']) {
                $priority['medium'][] = $file;
            } else {
                $priority['low'][] = $file;
            }
        }

        return array_filter($priority); // Remove empty categories
    }

    private function generateReport(): void
    {
        echo "\n📊 Refactoring Complete!\n";
        echo '✅ Fixed files: '.count($this->fixedFiles)."\n";
        echo '❌ Errors: '.count($this->errors)."\n";

        // Generate detailed report
        $this->saveDetailedReport();

        if (! empty($this->fixedFiles)) {
            echo "\n🔧 Fixed Files:\n";
            foreach ($this->fixedFiles as $file) {
                echo "  - {$file}\n";
            }
        }

        if (! empty($this->errors)) {
            echo "\n❌ Errors:\n";
            foreach ($this->errors as $error) {
                echo "  - {$error}\n";
            }
        }

        // Run final validation
        echo "\n🔍 Running final validation...\n";
        $this->validateAllFiles();
    }

    private function saveDetailedReport(): void
    {
        // Ensure reports directory exists
        if (! is_dir('reports/refactoring')) {
            mkdir('reports/refactoring', 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $dateStamp = date('Y-m-d_H-i-s');

        $report = "# Test File Refactoring Report\n";
        $report .= "Generated: {$timestamp}\n\n";

        // Summary
        $report .= "## Summary\n";
        $report .= '- Files processed: '.(count($this->fixedFiles) + count($this->errors))."\n";
        $report .= '- Successfully fixed: '.count($this->fixedFiles)."\n";
        $report .= '- Errors encountered: '.count($this->errors)."\n";
        $report .= '- Success rate: '.(count($this->fixedFiles) > 0 ? round((count($this->fixedFiles) / (count($this->fixedFiles) + count($this->errors))) * 100, 1) : 0)."%\n\n";

        // Fixed files
        if (! empty($this->fixedFiles)) {
            $report .= "## Successfully Fixed Files\n";
            foreach ($this->fixedFiles as $file) {
                $report .= "- `{$file}`\n";
            }
            $report .= "\n";
        }

        // Errors
        if (! empty($this->errors)) {
            $report .= "## Errors Encountered\n";
            foreach ($this->errors as $error) {
                $report .= "- {$error}\n";
            }
            $report .= "\n";
        }

        // Patterns fixed
        $report .= "## Patterns Fixed\n";
        $report .= "- Missing semicolons after expect statements\n";
        $report .= "- Extra opening braces from function declarations\n";
        $report .= "- Malformed Pest expectations (->method() patterns)\n";
        $report .= "- Missing describe block closings\n";
        $report .= "- Duplicate PHPUnit class sections\n";
        $report .= "- Embedded class extractions\n\n";

        // Save reports
        $timestampedFile = "reports/refactoring/refactoring_{$dateStamp}.md";
        $latestFile = 'reports/refactoring/latest_refactoring.md';

        file_put_contents($timestampedFile, $report);
        file_put_contents($latestFile, $report);

        echo "\nRefactoring reports saved:\n";
        echo "  - Timestamped: {$timestampedFile}\n";
        echo "  - Latest: {$latestFile}\n";
    }

    private function validateAllFiles(): void
    {
        $testFiles = $this->findTestFiles('tests/');
        $syntaxErrors = 0;

        foreach ($testFiles as $file) {
            $syntaxCheck = shell_exec('php -l '.escapeshellarg($file).' 2>&1');
            if (mb_strpos($syntaxCheck, 'No syntax errors') === false) {
                echo "  ❌ {$file}: ".mb_trim($syntaxCheck)."\n";
                $syntaxErrors++;
            }
        }

        if ($syntaxErrors === 0) {
            echo "  ✅ All test files have valid syntax!\n";
        } else {
            echo "  ⚠️  {$syntaxErrors} files still have syntax errors\n";
        }
    }
}

// Main execution
if (php_sapi_name() === 'cli') {
    $fixer = new TestFileFixer();

    if (isset($argv[1])) {
        // Fix specific file
        $fixer->fixTestFile($argv[1]);
    } else {
        // Fix all files
        $fixer->fixAllTestFiles();
    }
}
