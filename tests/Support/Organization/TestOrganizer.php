<?php

declare(strict_types=1);

namespace Tests\Support\Organization;

/**
 * Test Organizer for PEST
 * 
 * Provides utilities for organizing tests into logical groups,
 * managing test structure, and ensuring consistent organization
 * across the test suite.
 */
class TestOrganizer
{
    /**
     * Test organization levels
     */
    public const ORGANIZATION_LEVELS = [
        'suite' => 1,      // Top level: Unit, Feature, Integration, etc.
        'domain' => 2,     // Domain level: Validation, Reporting, Security
        'component' => 3,  // Component level: Service, Controller, Model
        'feature' => 4,    // Feature level: Specific functionality
    ];

    /**
     * Maximum nesting depth for describe blocks
     */
    public const MAX_NESTING_DEPTH = 3;

    /**
     * Recommended test file structure
     */
    public const FILE_STRUCTURE = [
        'imports' => 'Import statements and use declarations',
        'uses' => 'PEST uses() declarations',
        'setup' => 'beforeEach/afterEach hooks and setup',
        'describe_blocks' => 'Main describe blocks with tests',
        'helpers' => 'Helper functions (if any)',
    ];

    /**
     * Create organized test structure
     */
    public static function createTestStructure(array $config): string
    {
        $structure = [];
        
        // Add file header
        $structure[] = self::generateFileHeader($config);
        
        // Add imports
        if (!empty($config['imports'])) {
            $structure[] = self::generateImports($config['imports']);
        }
        
        // Add uses declarations
        if (!empty($config['uses'])) {
            $structure[] = self::generateUses($config['uses']);
        }
        
        // Add setup hooks
        if (!empty($config['setup'])) {
            $structure[] = self::generateSetup($config['setup']);
        }
        
        // Add describe blocks
        if (!empty($config['describe_blocks'])) {
            $structure[] = self::generateDescribeBlocks($config['describe_blocks']);
        }
        
        // Add helper functions
        if (!empty($config['helpers'])) {
            $structure[] = self::generateHelpers($config['helpers']);
        }
        
        return implode("\n\n", $structure);
    }

    /**
     * Generate file header
     */
    private static function generateFileHeader(array $config): string
    {
        $header = "<?php\n\ndeclare(strict_types=1);";
        
        if (!empty($config['description'])) {
            $header .= "\n\n/**\n * " . $config['description'] . "\n */";
        }
        
        return $header;
    }

    /**
     * Generate import statements
     */
    private static function generateImports(array $imports): string
    {
        $statements = [];
        
        foreach ($imports as $import) {
            if (is_array($import)) {
                $statements[] = "use {$import['namespace']}\\{$import['class']};";
            } else {
                $statements[] = "use {$import};";
            }
        }
        
        return implode("\n", $statements);
    }

    /**
     * Generate uses declarations
     */
    private static function generateUses(array $uses): string
    {
        $statements = [];
        
        foreach ($uses as $use) {
            if (is_array($use)) {
                $in = !empty($use['in']) ? "->in('{$use['in']}')" : '';
                $statements[] = "uses({$use['class']}::class){$in};";
            } else {
                $statements[] = "uses({$use}::class);";
            }
        }
        
        return implode("\n", $statements);
    }

    /**
     * Generate setup hooks
     */
    private static function generateSetup(array $setup): string
    {
        $hooks = [];
        
        if (!empty($setup['beforeEach'])) {
            $hooks[] = "beforeEach(function () {\n    " . implode("\n    ", $setup['beforeEach']) . "\n});";
        }
        
        if (!empty($setup['afterEach'])) {
            $hooks[] = "afterEach(function () {\n    " . implode("\n    ", $setup['afterEach']) . "\n});";
        }
        
        return implode("\n\n", $hooks);
    }

    /**
     * Generate describe blocks
     */
    private static function generateDescribeBlocks(array $blocks, int $depth = 0): string
    {
        if ($depth > self::MAX_NESTING_DEPTH) {
            throw new \InvalidArgumentException('Maximum nesting depth exceeded');
        }
        
        $indent = str_repeat('    ', $depth);
        $content = [];
        
        foreach ($blocks as $block) {
            $blockContent = [];
            $blockContent[] = "{$indent}describe('{$block['name']}', function () {";
            
            // Add tags if specified
            if (!empty($block['tags'])) {
                $tags = implode("', '", $block['tags']);
                $blockContent[0] = "{$indent}describe('{$block['name']}', function () {"
                    . "->tags('{$tags}');";
            }
            
            // Add setup for this block
            if (!empty($block['setup'])) {
                $blockContent[] = "{$indent}    beforeEach(function () {";
                foreach ($block['setup'] as $setupLine) {
                    $blockContent[] = "{$indent}        {$setupLine}";
                }
                $blockContent[] = "{$indent}    });";
                $blockContent[] = "";
            }
            
            // Add tests
            if (!empty($block['tests'])) {
                foreach ($block['tests'] as $test) {
                    $testContent = self::generateTest($test, $depth + 1);
                    $blockContent[] = $testContent;
                }
            }
            
            // Add nested describe blocks
            if (!empty($block['describe_blocks'])) {
                $nestedBlocks = self::generateDescribeBlocks($block['describe_blocks'], $depth + 1);
                $blockContent[] = $nestedBlocks;
            }
            
            $blockContent[] = "{$indent}});";
            
            $content[] = implode("\n", $blockContent);
        }
        
        return implode("\n\n", $content);
    }

    /**
     * Generate individual test
     */
    private static function generateTest(array $test, int $depth): string
    {
        $indent = str_repeat('    ', $depth);
        $testContent = [];
        
        // Generate test declaration
        $testLine = "{$indent}it('{$test['name']}', function () {";
        
        // Add tags if specified
        if (!empty($test['tags'])) {
            $tags = implode("', '", $test['tags']);
            $testLine .= "->tags('{$tags}');";
        } else {
            $testLine .= ";";
        }
        
        $testContent[] = $testLine;
        
        // Add test body
        if (!empty($test['body'])) {
            foreach ($test['body'] as $line) {
                $testContent[] = "{$indent}    {$line}";
            }
        } else {
            $testContent[] = "{$indent}    // TODO: Implement test";
        }
        
        $testContent[] = "{$indent}});";
        
        return implode("\n", $testContent);
    }

    /**
     * Generate helper functions
     */
    private static function generateHelpers(array $helpers): string
    {
        $functions = [];
        
        foreach ($helpers as $helper) {
            $function = [];
            $function[] = "function {$helper['name']}({$helper['parameters']})";
            $function[] = "{";
            
            if (!empty($helper['body'])) {
                foreach ($helper['body'] as $line) {
                    $function[] = "    {$line}";
                }
            }
            
            $function[] = "}";
            
            $functions[] = implode("\n", $function);
        }
        
        return implode("\n\n", $functions);
    }

    /**
     * Validate test organization
     */
    public static function validateOrganization(array $structure): array
    {
        $issues = [];
        
        // Check nesting depth
        $maxDepth = self::calculateMaxDepth($structure);
        if ($maxDepth > self::MAX_NESTING_DEPTH) {
            $issues[] = "Maximum nesting depth ({$maxDepth}) exceeds recommended limit (" . self::MAX_NESTING_DEPTH . ")";
        }
        
        // Check for empty describe blocks
        $emptyBlocks = self::findEmptyDescribeBlocks($structure);
        if (!empty($emptyBlocks)) {
            $issues[] = "Found empty describe blocks: " . implode(', ', $emptyBlocks);
        }
        
        // Check test naming conventions
        $namingIssues = self::validateTestNaming($structure);
        $issues = array_merge($issues, $namingIssues);
        
        return $issues;
    }

    /**
     * Calculate maximum nesting depth
     */
    private static function calculateMaxDepth(array $structure, int $currentDepth = 0): int
    {
        $maxDepth = $currentDepth;
        
        foreach ($structure as $item) {
            if (isset($item['describe_blocks'])) {
                $depth = self::calculateMaxDepth($item['describe_blocks'], $currentDepth + 1);
                $maxDepth = max($maxDepth, $depth);
            }
        }
        
        return $maxDepth;
    }

    /**
     * Find empty describe blocks
     */
    private static function findEmptyDescribeBlocks(array $structure, array &$emptyBlocks = []): array
    {
        foreach ($structure as $item) {
            if (empty($item['tests']) && empty($item['describe_blocks'])) {
                $emptyBlocks[] = $item['name'] ?? 'unnamed';
            }
            
            if (!empty($item['describe_blocks'])) {
                self::findEmptyDescribeBlocks($item['describe_blocks'], $emptyBlocks);
            }
        }
        
        return $emptyBlocks;
    }

    /**
     * Validate test naming in structure
     */
    private static function validateTestNaming(array $structure): array
    {
        $issues = [];
        
        foreach ($structure as $item) {
            if (!empty($item['tests'])) {
                foreach ($item['tests'] as $test) {
                    $testName = $test['name'] ?? '';
                    $namingIssues = TestNamingConventions::validateTestName($testName);
                    foreach ($namingIssues as $issue) {
                        $issues[] = "Test '{$testName}': {$issue}";
                    }
                }
            }
            
            if (!empty($item['describe_blocks'])) {
                $nestedIssues = self::validateTestNaming($item['describe_blocks']);
                $issues = array_merge($issues, $nestedIssues);
            }
        }
        
        return $issues;
    }

    /**
     * Generate test organization report
     */
    public static function generateOrganizationReport(array $structure): array
    {
        return [
            'total_describe_blocks' => self::countDescribeBlocks($structure),
            'total_tests' => self::countTests($structure),
            'max_nesting_depth' => self::calculateMaxDepth($structure),
            'empty_blocks' => self::findEmptyDescribeBlocks($structure),
            'organization_score' => self::calculateOrganizationScore($structure),
        ];
    }

    /**
     * Count describe blocks
     */
    private static function countDescribeBlocks(array $structure): int
    {
        $count = count($structure);
        
        foreach ($structure as $item) {
            if (!empty($item['describe_blocks'])) {
                $count += self::countDescribeBlocks($item['describe_blocks']);
            }
        }
        
        return $count;
    }

    /**
     * Count tests
     */
    private static function countTests(array $structure): int
    {
        $count = 0;
        
        foreach ($structure as $item) {
            if (!empty($item['tests'])) {
                $count += count($item['tests']);
            }
            
            if (!empty($item['describe_blocks'])) {
                $count += self::countTests($item['describe_blocks']);
            }
        }
        
        return $count;
    }

    /**
     * Calculate organization score (0-100)
     */
    private static function calculateOrganizationScore(array $structure): int
    {
        $score = 100;
        
        // Deduct points for issues
        $issues = self::validateOrganization($structure);
        $score -= count($issues) * 10;
        
        // Deduct points for excessive nesting
        $maxDepth = self::calculateMaxDepth($structure);
        if ($maxDepth > self::MAX_NESTING_DEPTH) {
            $score -= ($maxDepth - self::MAX_NESTING_DEPTH) * 15;
        }
        
        // Deduct points for empty blocks
        $emptyBlocks = self::findEmptyDescribeBlocks($structure);
        $score -= count($emptyBlocks) * 5;
        
        return max(0, $score);
    }

    /**
     * Get organization recommendations
     */
    public static function getRecommendations(array $structure): array
    {
        $recommendations = [];
        
        $issues = self::validateOrganization($structure);
        if (!empty($issues)) {
            $recommendations[] = 'Fix organization issues: ' . implode(', ', $issues);
        }
        
        $maxDepth = self::calculateMaxDepth($structure);
        if ($maxDepth > self::MAX_NESTING_DEPTH) {
            $recommendations[] = 'Reduce nesting depth by flattening describe blocks';
        }
        
        $emptyBlocks = self::findEmptyDescribeBlocks($structure);
        if (!empty($emptyBlocks)) {
            $recommendations[] = 'Remove or populate empty describe blocks';
        }
        
        $testCount = self::countTests($structure);
        if ($testCount === 0) {
            $recommendations[] = 'Add tests to the structure';
        } elseif ($testCount < 3) {
            $recommendations[] = 'Consider adding more comprehensive test coverage';
        }
        
        return $recommendations;
    }
}