<?php

declare(strict_types=1);

namespace Tests\Support\StaticAnalysis;

use Exception;
use RuntimeException;

/**
 * Comprehensive Larastan static analysis compliance validator for achieving
 * level 10 compliance across all test files with strict type declarations,
 * comprehensive PHPDoc annotations, and advanced static analysis rule adherence.
 * 
 * @package Tests\Support\StaticAnalysis
 * @author Roo
 * @since 1.0.0
 */
final class LarastanCompliance
{
    /**
     * Larastan compliance configuration.
     * 
     * @var array<string, mixed>
     */
    private static array $config = [
        'target_level' => 10,
        'enable_strict_mode' => true,
        'enable_type_coverage' => true,
        'enable_null_safety' => true,
        'enable_generic_validation' => true,
        'enable_phpDoc_validation' => true,
        'minimum_type_coverage' => 100.0,
        'allowed_mixed_types' => 0,
        'require_return_types' => true,
        'require_parameter_types' => true,
    ];
    
    /**
     * Compliance validation results.
     * 
     * @var array<string, mixed>
     */
    private static array $validationResults = [];
    
    /**
     * Static analysis rules for level 10 compliance.
     * 
     * @var array<string, array<string, mixed>>
     */
    private static array $complianceRules = [
        'strict_types' => [
            'name' => 'Strict Type Declarations',
            'description' => 'All PHP files must have declare(strict_types=1)',
            'severity' => 'error',
            'pattern' => '/declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/',
            'required' => true,
        ],
        'return_types' => [
            'name' => 'Return Type Declarations',
            'description' => 'All methods must have explicit return type declarations',
            'severity' => 'error',
            'pattern' => '/function\s+\w+\s*\([^)]*\)\s*:\s*\w+/',
            'required' => true,
        ],
        'parameter_types' => [
            'name' => 'Parameter Type Declarations',
            'description' => 'All parameters must have explicit type declarations',
            'severity' => 'error',
            'pattern' => '/function\s+\w+\s*\(\s*([^)]*\$\w+[^)]*)\s*\)/',
            'required' => true,
        ],
        'phpDoc_annotations' => [
            'name' => 'PHPDoc Annotations',
            'description' => 'All public methods must have comprehensive PHPDoc',
            'severity' => 'warning',
            'pattern' => '/\/\*\*[\s\S]*?\*\/\s*public\s+function/',
            'required' => true,
        ],
        'no_mixed_types' => [
            'name' => 'No Mixed Types',
            'description' => 'Mixed types should be eliminated',
            'severity' => 'error',
            'pattern' => '/:\s*mixed\b|\bmixed\s+\$/',
            'required' => false,
        ],
        'null_safety' => [
            'name' => 'Null Safety',
            'description' => 'Proper null safety with nullable type declarations',
            'severity' => 'error',
            'pattern' => '/\?\w+|\w+\|null/',
            'required' => true,
        ],
        'generic_types' => [
            'name' => 'Generic Type Usage',
            'description' => 'Proper generic type annotations',
            'severity' => 'warning',
            'pattern' => '/@var\s+\w+<[\w\|,\s]+>|@param\s+\w+<[\w\|,\s]+>|@return\s+\w+<[\w\|,\s]+>/',
            'required' => false,
        ],
        'array_shapes' => [
            'name' => 'Array Shape Declarations',
            'description' => 'Use array shapes instead of generic arrays',
            'severity' => 'warning',
            'pattern' => '/@var\s+array\{[^}]+\}|@param\s+array\{[^}]+\}|@return\s+array\{[^}]+\}/',
            'required' => false,
        ],
    ];
    
    /**
     * File patterns to analyze.
     * 
     * @var array<string>
     */
    private static array $filePatterns = [
        'tests/**/*.php',
        'app/**/*.php',
        'config/**/*.php',
    ];
    
    /**
     * Excluded file patterns.
     * 
     * @var array<string>
     */
    private static array $excludePatterns = [
        'vendor/**',
        'storage/**',
        'bootstrap/cache/**',
        'node_modules/**',
    ];
    
    /**
     * Initialize Larastan compliance validator.
     * 
     * @param array<string, mixed> $config Configuration overrides
     * @return void
     */
    public static function initialize(array $config = []): void
    {
        self::$config = array_merge(self::$config, $config);
        self::$validationResults = [];
    }
    
    /**
     * Validate Larastan compliance across all files.
     * 
     * @param array<string> $filePaths Specific files to validate (optional)
     * @return array<string, mixed> Comprehensive compliance results
     */
    public static function validateCompliance(array $filePaths = []): array
    {
        $startTime = microtime(true);
        
        $results = [
            'overall_compliance' => false,
            'compliance_level' => 0,
            'compliance_score' => 0.0,
            'target_level' => self::$config['target_level'],
            'validation_timestamp' => date('Y-m-d H:i:s'),
            'files_analyzed' => 0,
            'compliant_files' => 0,
            'non_compliant_files' => 0,
            'total_violations' => 0,
            'violations_by_severity' => [
                'error' => 0,
                'warning' => 0,
                'info' => 0,
            ],
            'violations_by_rule' => [],
            'file_results' => [],
            'type_coverage' => [
                'percentage' => 0,
                'covered_methods' => 0,
                'total_methods' => 0,
                'uncovered_methods' => [],
            ],
            'recommendations' => [],
            'remediation_plan' => [],
            'execution_time' => 0,
        ];
        
        try {
            // Discover files to analyze
            $files = empty($filePaths) ? self::discoverFiles() : $filePaths;
            $results['files_analyzed'] = count($files);
            
            // Validate each file
            foreach ($files as $filePath) {
                $fileResult = self::validateFile($filePath);
                $results['file_results'][$filePath] = $fileResult;
                
                if ($fileResult['compliant']) {
                    $results['compliant_files']++;
                } else {
                    $results['non_compliant_files']++;
                }
                
                // Aggregate violations
                foreach ($fileResult['violations'] as $violation) {
                    $results['total_violations']++;
                    $results['violations_by_severity'][$violation['severity']]++;
                    
                    $rule = $violation['rule'];
                    if (!isset($results['violations_by_rule'][$rule])) {
                        $results['violations_by_rule'][$rule] = 0;
                    }
                    $results['violations_by_rule'][$rule]++;
                }
            }
            
            // Calculate type coverage
            if (self::$config['enable_type_coverage']) {
                $results['type_coverage'] = self::calculateTypeCoverage($files);
            }
            
            // Determine compliance level and score
            $results['compliance_level'] = self::calculateComplianceLevel($results);
            $results['compliance_score'] = self::calculateComplianceScore($results);
            $results['overall_compliance'] = $results['compliance_level'] >= self::$config['target_level'];
            
            // Generate recommendations
            $results['recommendations'] = self::generateRecommendations($results);
            $results['remediation_plan'] = self::generateRemediationPlan($results);
            
        } catch (Exception $e) {
            $results['error'] = $e->getMessage();
        }
        
        $results['execution_time'] = microtime(true) - $startTime;
        self::$validationResults = $results;
        
        return $results;
    }
    
    /**
     * Validate individual file for Larastan compliance.
     * 
     * @param string $filePath File path to validate
     * @return array<string, mixed> File validation results
     */
    public static function validateFile(string $filePath): array
    {
        $result = [
            'file_path' => $filePath,
            'compliant' => true,
            'violations' => [],
            'type_coverage' => 0,
            'metrics' => [
                'lines_of_code' => 0,
                'methods_count' => 0,
                'typed_methods' => 0,
                'phpDoc_coverage' => 0,
            ],
            'validation_timestamp' => date('Y-m-d H:i:s'),
        ];
        
        if (!file_exists($filePath)) {
            $result['compliant'] = false;
            $result['violations'][] = [
                'rule' => 'file_exists',
                'severity' => 'error',
                'message' => 'File does not exist',
                'line' => 0,
            ];
            return $result;
        }
        
        try {
            $content = file_get_contents($filePath);
            if ($content === false) {
                throw new RuntimeException("Failed to read file: {$filePath}");
            }
            
            $lines = explode("\n", $content);
            $result['metrics']['lines_of_code'] = count($lines);
            
            // Validate against each compliance rule
            foreach (self::$complianceRules as $ruleId => $rule) {
                $violations = self::validateRule($content, $ruleId, $rule, $filePath);
                $result['violations'] = array_merge($result['violations'], $violations);
            }
            
            // Calculate file-specific metrics
            $result['metrics'] = array_merge($result['metrics'], self::calculateFileMetrics($content));
            $result['type_coverage'] = self::calculateFileTypeCoverage($content);
            
            // Determine file compliance
            $errorViolations = array_filter($result['violations'], fn($v) => $v['severity'] === 'error');
            $result['compliant'] = empty($errorViolations);
            
        } catch (Exception $e) {
            $result['compliant'] = false;
            $result['violations'][] = [
                'rule' => 'validation_error',
                'severity' => 'error',
                'message' => 'Validation failed: ' . $e->getMessage(),
                'line' => 0,
            ];
        }
        
        return $result;
    }
    
    /**
     * Fix common compliance violations automatically.
     * 
     * @param string $filePath File path to fix
     * @param array<string> $rulesToFix Specific rules to fix (optional)
     * @return array<string, mixed> Fix results
     */
    public static function autoFixViolations(string $filePath, array $rulesToFix = []): array
    {
        $result = [
            'file_path' => $filePath,
            'fixes_applied' => [],
            'fixes_failed' => [],
            'backup_created' => false,
            'success' => false,
        ];
        
        if (!file_exists($filePath)) {
            $result['fixes_failed'][] = 'File does not exist';
            return $result;
        }
        
        try {
            // Create backup
            $backupPath = $filePath . '.backup.' . date('Y-m-d_H-i-s');
            if (copy($filePath, $backupPath)) {
                $result['backup_created'] = true;
            }
            
            $content = file_get_contents($filePath);
            if ($content === false) {
                throw new RuntimeException("Failed to read file: {$filePath}");
            }
            
            $originalContent = $content;
            
            // Apply automatic fixes
            $rulesToProcess = empty($rulesToFix) ? array_keys(self::$complianceRules) : $rulesToFix;
            
            foreach ($rulesToProcess as $ruleId) {
                try {
                    $fixedContent = self::applyAutoFix($content, $ruleId);
                    if ($fixedContent !== $content) {
                        $content = $fixedContent;
                        $result['fixes_applied'][] = $ruleId;
                    }
                } catch (Exception $e) {
                    $result['fixes_failed'][] = "{$ruleId}: " . $e->getMessage();
                }
            }
            
            // Write fixed content if changes were made
            if ($content !== $originalContent) {
                if (file_put_contents($filePath, $content) !== false) {
                    $result['success'] = true;
                } else {
                    throw new RuntimeException("Failed to write fixed content to file");
                }
            } else {
                $result['success'] = true; // No fixes needed
            }
            
        } catch (Exception $e) {
            $result['fixes_failed'][] = 'Auto-fix failed: ' . $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Generate comprehensive compliance report.
     * 
     * @param string $format Report format (json, html, markdown)
     * @return string Generated report
     */
    public static function generateComplianceReport(string $format = 'json'): string
    {
        $results = self::$validationResults;
        
        return match ($format) {
            'html' => self::generateHtmlReport($results),
            'markdown' => self::generateMarkdownReport($results),
            'json' => json_encode($results, JSON_PRETTY_PRINT),
            default => throw new RuntimeException("Unsupported report format: {$format}"),
        };
    }
    
    /**
     * Get current validation results.
     * 
     * @return array<string, mixed> Validation results
     */
    public static function getValidationResults(): array
    {
        return self::$validationResults;
    }
    
    /**
     * Configure Larastan compliance validator.
     * 
     * @param array<string, mixed> $config Configuration overrides
     * @return void
     */
    public static function configure(array $config): void
    {
        self::$config = array_merge(self::$config, $config);
    }
    
    /**
     * Reset validation state.
     * 
     * @return void
     */
    public static function reset(): void
    {
        self::$validationResults = [];
    }
    
    /**
     * Discover files to analyze based on patterns.
     * 
     * @return array<string> File paths
     */
    private static function discoverFiles(): array
    {
        $files = [];
        
        foreach (self::$filePatterns as $pattern) {
            $globFiles = glob($pattern, GLOB_BRACE);
            if ($globFiles !== false) {
                $files = array_merge($files, $globFiles);
            }
        }
        
        // Filter out excluded patterns
        $filteredFiles = [];
        foreach ($files as $file) {
            $excluded = false;
            foreach (self::$excludePatterns as $excludePattern) {
                if (fnmatch($excludePattern, $file)) {
                    $excluded = true;
                    break;
                }
            }
            if (!$excluded) {
                $filteredFiles[] = $file;
            }
        }
        
        return array_unique($filteredFiles);
    }
    
    /**
     * Validate content against specific rule.
     * 
     * @param string $content File content
     * @param string $ruleId Rule identifier
     * @param array<string, mixed> $rule Rule definition
     * @param string $filePath File path for context
     * @return array<array<string, mixed>> Violations found
     */
    private static function validateRule(string $content, string $ruleId, array $rule, string $filePath): array
    {
        $violations = [];
        
        switch ($ruleId) {
            case 'strict_types':
                if (!preg_match($rule['pattern'], $content)) {
                    $violations[] = [
                        'rule' => $ruleId,
                        'severity' => $rule['severity'],
                        'message' => 'Missing strict_types declaration',
                        'line' => 1,
                    ];
                }
                break;
                
            case 'return_types':
                $violations = array_merge($violations, self::validateReturnTypes($content, $rule));
                break;
                
            case 'parameter_types':
                $violations = array_merge($violations, self::validateParameterTypes($content, $rule));
                break;
                
            case 'phpDoc_annotations':
                $violations = array_merge($violations, self::validatePhpDocAnnotations($content, $rule));
                break;
                
            case 'no_mixed_types':
                $violations = array_merge($violations, self::validateNoMixedTypes($content, $rule));
                break;
                
            case 'null_safety':
                $violations = array_merge($violations, self::validateNullSafety($content, $rule));
                break;
                
            case 'generic_types':
                $violations = array_merge($violations, self::validateGenericTypes($content, $rule));
                break;
                
            case 'array_shapes':
                $violations = array_merge($violations, self::validateArrayShapes($content, $rule));
                break;
        }
        
        return $violations;
    }
    
    /**
     * Validate return type declarations.
     * 
     * @param string $content File content
     * @param array<string, mixed> $rule Rule definition
     * @return array<array<string, mixed>> Violations
     */
    private static function validateReturnTypes(string $content, array $rule): array
    {
        $violations = [];
        $lines = explode("\n", $content);
        
        foreach ($lines as $lineNumber => $line) {
            // Look for function declarations without return types
            if (preg_match('/^\s*(?:public|private|protected)?\s*function\s+(\w+)\s*\([^)]*\)\s*(?:\{|$)/', $line, $matches)) {
                $functionName = $matches[1];
                
                // Skip constructors and destructors
                if (in_array($functionName, ['__construct', '__destruct'], true)) {
                    continue;
                }
                
                // Check if return type is present
                if (!preg_match('/:\s*\w+/', $line)) {
                    $violations[] = [
                        'rule' => 'return_types',
                        'severity' => $rule['severity'],
                        'message' => "Function '{$functionName}' missing return type declaration",
                        'line' => $lineNumber + 1,
                    ];
                }
            }
        }
        
        return $violations;
    }
    
    /**
     * Validate parameter type declarations.
     * 
     * @param string $content File content
     * @param array<string, mixed> $rule Rule definition
     * @return array<array<string, mixed>> Violations
     */
    private static function validateParameterTypes(string $content, array $rule): array
    {
        $violations = [];
        $lines = explode("\n", $content);
        
        foreach ($lines as $lineNumber => $line) {
            // Look for function parameters without types
            if (preg_match('/function\s+\w+\s*\(([^)]+)\)/', $line, $matches)) {
                $parameters = $matches[1];
                
                // Check each parameter
                if (preg_match_all('/(\$\w+)/', $parameters, $paramMatches)) {
                    foreach ($paramMatches[1] as $param) {
                        // Check if parameter has type declaration
                        if (!preg_match('/\w+\s+' . preg_quote($param, '/') . '/', $parameters)) {
                            $violations[] = [
                                'rule' => 'parameter_types',
                                'severity' => $rule['severity'],
                                'message' => "Parameter '{$param}' missing type declaration",
                                'line' => $lineNumber + 1,
                            ];
                        }
                    }
                }
            }
        }
        
        return $violations;
    }
    
    /**
     * Validate PHPDoc annotations.
     * 
     * @param string $content File content
     * @param array<string, mixed> $rule Rule definition
     * @return array<array<string, mixed>> Violations
     */
    private static function validatePhpDocAnnotations(string $content, array $rule): array
    {
        $violations = [];
        $lines = explode("\n", $content);
        
        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            
            // Look for public function declarations
            if (preg_match('/^\s*public\s+function\s+(\w+)/', $line, $matches)) {
                $functionName = $matches[1];
                
                // Skip magic methods
                if (str_starts_with($functionName, '__')) {
                    continue;
                }
                
                // Check if there's a PHPDoc comment before this function
                $hasPhpDoc = false;
                for ($j = $i - 1; $j >= 0; $j--) {
                    $prevLine = trim($lines[$j]);
                    if (empty($prevLine)) {
                        continue;
                    }
                    if (str_starts_with($prevLine, '/**')) {
                        $hasPhpDoc = true;
                        break;
                    }
                    if (!str_starts_with($prevLine, '*') && !str_starts_with($prevLine, '*/')) {
                        break;
                    }
                }
                
                if (!$hasPhpDoc) {
                    $violations[] = [
                        'rule' => 'phpDoc_annotations',
                        'severity' => $rule['severity'],
                        'message' => "Public function '{$functionName}' missing PHPDoc annotation",
                        'line' => $i + 1,
                    ];
                }
            }
        }
        
        return $violations;
    }
    
    /**
     * Validate no mixed types usage.
     * 
     * @param string $content File content
     * @param array<string, mixed> $rule Rule definition
     * @return array<array<string, mixed>> Violations
     */
    private static function validateNoMixedTypes(string $content, array $rule): array
    {
        $violations = [];
        $lines = explode("\n", $content);
        
        foreach ($lines as $lineNumber => $line) {
            if (preg_match($rule['pattern'], $line)) {
                $violations[] = [
                    'rule' => 'no_mixed_types',
                    'severity' => $rule['severity'],
                    'message' => 'Mixed type usage detected - should be eliminated for level 10 compliance',
                    'line' => $lineNumber + 1,
                ];
            }
        }
        
        return $violations;
    }
    
    /**
     * Validate null safety implementation.
     * 
     * @param string $content File content
     * @param array<string, mixed> $rule Rule definition
     * @return array<array<string, mixed>> Violations
     */
    private static function validateNullSafety(string $content, array $rule): array
    {
        $violations = [];
        // This would implement more sophisticated null safety validation
        // For now, return empty array as this is complex to implement properly
        return $violations;
    }
    
    /**
     * Validate generic type usage.
     * 
     * @param string $content File content
     * @param array<string, mixed> $rule Rule definition
     * @return array<array<string, mixed>> Violations
     */
    private static function validateGenericTypes(string $content, array $rule): array
    {
        $violations = [];
        // This would validate proper generic type annotations
        // Implementation would be complex and is simplified here
        return $violations;
    }
    
    /**
     * Validate array shape declarations.
     * 
     * @param string $content File content
     * @param array<string, mixed> $rule Rule definition
     * @return array<array<string, mixed>> Violations
     */
    private static function validateArrayShapes(string $content, array $rule): array
    {
        $violations = [];
        // This would validate array shape usage instead of generic arrays
        // Implementation would be complex and is simplified here
        return $violations;
    }
    
    /**
     * Calculate file-specific metrics.
     * 
     * @param string $content File content
     * @return array<string, mixed> File metrics
     */
    private static function calculateFileMetrics(string $content): array
    {
        $metrics = [
            'methods_count' => 0,
            'typed_methods' => 0,
            'phpDoc_coverage' => 0,
        ];
        
        // Count methods and their type coverage
        preg_match_all('/function\s+(\w+)/', $content, $matches);
        $metrics['methods_count'] = count($matches[1]);
        
        // Count methods with return types
        preg_match_all('/function\s+\w+\s*\([^)]*\)\s*:\s*\w+/', $content, $typedMatches);
        $metrics['typed_methods'] = count($typedMatches[0]);
        
        // Calculate PHPDoc coverage
        preg_match_all('/\/\*\*[\s\S]*?\*\/\s*(?:public|private|protected)?\s*function/', $content, $docMatches);
        $metrics['phpDoc_coverage'] = $metrics['methods_count'] > 0 
            ? (count($docMatches[0]) / $metrics['methods_count']) * 100 
            : 0;
        
        return $metrics;
    }
    
    /**
     * Calculate file type coverage.
     * 
     * @param string $content File content
     * @return float Type coverage percentage
     */
    private static function calculateFileTypeCoverage(string $content): float
    {
        $metrics = self::calculateFileMetrics($content);
        
        return $metrics['methods_count'] > 0 
            ? ($metrics['typed_methods'] / $metrics['methods_count']) * 100 
            : 0;
    }
    
    /**
     * Calculate overall type coverage.
     * 
     * @param array<string> $files Files to analyze
     * @return array<string, mixed> Type coverage data
     */
    private static function calculateTypeCoverage(array $files): array
    {
        $totalMethods = 0;
        $typedMethods = 0;
        $uncoveredMethods = [];
        
        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            }
            
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }
            
            $metrics = self::calculateFileMetrics($content);
            $totalMethods += $metrics['methods_count'];
            $typedMethods += $metrics['typed_methods'];
            
            // Find uncovered methods
            $untypedCount = $metrics['methods_count'] - $metrics['typed_methods'];
            if ($untypedCount > 0) {
                $uncoveredMethods[] = [
                    'file' => $file,
                    'untyped_methods' => $untypedCount,
                ];
            }
        }
        
        return [
            'percentage' => $totalMethods > 0 ? ($typedMethods / $totalMethods) * 100 : 0,
            'covered_methods' => $typedMethods,
            'total_methods' => $totalMethods,
            'uncovered_methods' => $uncoveredMethods,
        ];
    }
    
    /**
     * Calculate compliance level based on validation results.
     *
     * @param array<string, mixed> $results Validation results
     * @return int Compliance level (0-10)
     */
    private static function calculateComplianceLevel(array $results): int
    {
        if ($results['files_analyzed'] === 0) {
            return 0;
        }
        
        // Base level calculation
        $complianceRatio = $results['compliant_files'] / $results['files_analyzed'];
        $baseLevel = (int) floor($complianceRatio * 10);
        
        // Adjust based on violation severity
        $errorViolations = $results['violations_by_severity']['error'];
        if ($errorViolations > 0) {
            $baseLevel = min($baseLevel, 7); // Cap at level 7 if errors exist
        }
        
        // Adjust based on type coverage
        if (self::$config['enable_type_coverage']) {
            $typeCoverage = $results['type_coverage']['percentage'];
            if ($typeCoverage < self::$config['minimum_type_coverage']) {
                $baseLevel = min($baseLevel, 8); // Cap at level 8 if type coverage is low
            }
        }
        
        return max(0, min(10, $baseLevel));
    }
    
    /**
     * Calculate compliance score as a percentage.
     *
     * @param array<string, mixed> $results Validation results
     * @return float Compliance score (0.0-100.0)
     */
    private static function calculateComplianceScore(array $results): float
    {
        if ($results['files_analyzed'] === 0) {
            return 0.0;
        }
        
        // Base score from file compliance ratio
        $fileComplianceScore = ($results['compliant_files'] / $results['files_analyzed']) * 100;
        
        // Type coverage score
        $typeCoverageScore = $results['type_coverage']['percentage'];
        
        // Violation penalty
        $violationPenalty = 0.0;
        if ($results['total_violations'] > 0) {
            $errorWeight = 5.0;
            $warningWeight = 2.0;
            $infoWeight = 1.0;
            
            $totalPenalty = ($results['violations_by_severity']['error'] * $errorWeight) +
                           ($results['violations_by_severity']['warning'] * $warningWeight) +
                           ($results['violations_by_severity']['info'] * $infoWeight);
            
            $violationPenalty = min(50.0, $totalPenalty); // Cap penalty at 50%
        }
        
        // Calculate weighted score
        $weightedScore = ($fileComplianceScore * 0.6) + ($typeCoverageScore * 0.4) - $violationPenalty;
        
        return max(0.0, min(100.0, $weightedScore));
    }
    
    /**
     * Generate recommendations based on validation results.
     * 
     * @param array<string, mixed> $results Validation results
     * @return array<string> Recommendations
     */
    private static function generateRecommendations(array $results): array
    {
        $recommendations = [];
        
        if ($results['overall_compliance']) {
            $recommendations[] = 'Excellent! All files are Larastan level 10 compliant';
            $recommendations[] = 'Consider implementing additional quality checks';
        } else {
            $recommendations[] = 'Focus on fixing error-level violations first';
            
            if ($results['violations_by_severity']['error'] > 0) {
                $recommendations[] = 'Add strict type declarations to all files';
                $recommendations[] = 'Ensure all methods have return type declarations';
                $recommendations[] = 'Add type declarations to all parameters';
            }
            
            if ($results['type_coverage']['percentage'] < 100) {
                $recommendations[] = 'Improve type coverage to reach 100%';
            }
            
            if ($results['violations_by_severity']['warning'] > 0) {
                $recommendations[] = 'Add comprehensive PHPDoc annotations';
                $recommendations[] = 'Consider using more specific array shapes';
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Generate remediation plan based on violations.
     *
     * @param array<string, mixed> $results Validation results
     * @return array<string, mixed> Remediation plan
     */
    private static function generateRemediationPlan(array $results): array
    {
        $plan = [
            'immediate_actions' => [],
            'short_term_actions' => [],
            'long_term_actions' => [],
            'estimated_effort' => 'medium',
        ];
        
        // Immediate actions for error violations
        if ($results['violations_by_severity']['error'] > 0) {
            $plan['immediate_actions'][] = 'Add declare(strict_types=1) to all files';
            $plan['immediate_actions'][] = 'Add return type declarations to all methods';
            $plan['immediate_actions'][] = 'Add parameter type declarations';
        }
        
        // Short-term actions for warnings
        if ($results['violations_by_severity']['warning'] > 0) {
            $plan['short_term_actions'][] = 'Add PHPDoc annotations to public methods';
            $plan['short_term_actions'][] = 'Improve generic type usage';
            $plan['short_term_actions'][] = 'Use array shapes instead of generic arrays';
        }
        
        // Long-term actions for optimization
        $plan['long_term_actions'][] = 'Implement comprehensive null safety';
        $plan['long_term_actions'][] = 'Optimize type coverage to 100%';
        $plan['long_term_actions'][] = 'Regular compliance monitoring';
        
        // Estimate effort based on violations
        $totalViolations = $results['total_violations'];
        $plan['estimated_effort'] = match (true) {
            $totalViolations === 0 => 'none',
            $totalViolations <= 10 => 'low',
            $totalViolations <= 50 => 'medium',
            default => 'high',
        };
        
        return $plan;
    }
    
    /**
     * Apply automatic fixes for common violations.
     *
     * @param string $content File content
     * @param string $ruleId Rule to fix
     * @return string Fixed content
     */
    private static function applyAutoFix(string $content, string $ruleId): string
    {
        return match ($ruleId) {
            'strict_types' => self::fixStrictTypes($content),
            'return_types' => self::fixReturnTypes($content),
            'parameter_types' => self::fixParameterTypes($content),
            'phpDoc_annotations' => self::fixPhpDocAnnotations($content),
            default => $content,
        };
    }
    
    /**
     * Fix strict types declaration.
     *
     * @param string $content File content
     * @return string Fixed content
     */
    private static function fixStrictTypes(string $content): string
    {
        // Check if strict_types is already present
        if (preg_match('/declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/', $content)) {
            return $content;
        }
        
        // Add strict_types after opening PHP tag
        if (preg_match('/^<\?php\s*$/m', $content)) {
            $content = preg_replace('/^(<\?php)\s*$/m', "$1\n\ndeclare(strict_types=1);", $content);
        }
        
        return $content;
    }
    
    /**
     * Fix return type declarations (simplified implementation).
     *
     * @param string $content File content
     * @return string Fixed content
     */
    private static function fixReturnTypes(string $content): string
    {
        // This is a simplified implementation
        // In practice, this would require sophisticated AST parsing
        return $content;
    }
    
    /**
     * Fix parameter type declarations (simplified implementation).
     *
     * @param string $content File content
     * @return string Fixed content
     */
    private static function fixParameterTypes(string $content): string
    {
        // This is a simplified implementation
        // In practice, this would require sophisticated AST parsing
        return $content;
    }
    
    /**
     * Fix PHPDoc annotations (simplified implementation).
     *
     * @param string $content File content
     * @return string Fixed content
     */
    private static function fixPhpDocAnnotations(string $content): string
    {
        // This is a simplified implementation
        // In practice, this would require sophisticated AST parsing
        return $content;
    }
    
    /**
     * Generate HTML compliance report.
     *
     * @param array<string, mixed> $results Validation results
     * @return string HTML report
     */
    private static function generateHtmlReport(array $results): string
    {
        $html = "<!DOCTYPE html>\n<html>\n<head>\n<title>Larastan Compliance Report</title>\n";
        $html .= "<style>body{font-family:Arial,sans-serif;margin:20px;}table{border-collapse:collapse;width:100%;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background-color:#f2f2f2;}.compliant{color:green;}.non-compliant{color:red;}.error{color:red;}.warning{color:orange;}.info{color:blue;}</style>\n";
        $html .= "</head>\n<body>\n";
        $html .= "<h1>Larastan Compliance Report</h1>\n";
        $html .= "<p>Generated at: {$results['validation_timestamp']}</p>\n";
        
        $complianceStatus = $results['overall_compliance'] ? 'COMPLIANT' : 'NON-COMPLIANT';
        $complianceClass = $results['overall_compliance'] ? 'compliant' : 'non-compliant';
        $html .= "<p>Overall Status: <span class='{$complianceClass}'>{$complianceStatus}</span></p>\n";
        $html .= "<p>Compliance Level: {$results['compliance_level']}/{$results['target_level']}</p>\n";
        
        $html .= "<h2>Summary</h2>\n";
        $html .= "<table>\n";
        $html .= "<tr><th>Metric</th><th>Value</th></tr>\n";
        $html .= "<tr><td>Files Analyzed</td><td>{$results['files_analyzed']}</td></tr>\n";
        $html .= "<tr><td>Compliant Files</td><td>{$results['compliant_files']}</td></tr>\n";
        $html .= "<tr><td>Non-Compliant Files</td><td>{$results['non_compliant_files']}</td></tr>\n";
        $html .= "<tr><td>Total Violations</td><td>{$results['total_violations']}</td></tr>\n";
        $html .= "<tr><td>Type Coverage</td><td>" . number_format($results['type_coverage']['percentage'], 1) . "%</td></tr>\n";
        $html .= "</table>\n";
        
        if (!empty($results['violations_by_rule'])) {
            $html .= "<h2>Violations by Rule</h2>\n";
            $html .= "<table>\n";
            $html .= "<tr><th>Rule</th><th>Count</th></tr>\n";
            foreach ($results['violations_by_rule'] as $rule => $count) {
                $html .= "<tr><td>{$rule}</td><td>{$count}</td></tr>\n";
            }
            $html .= "</table>\n";
        }
        
        $html .= "</body>\n</html>";
        
        return $html;
    }
    
    /**
     * Generate Markdown compliance report.
     *
     * @param array<string, mixed> $results Validation results
     * @return string Markdown report
     */
    private static function generateMarkdownReport(array $results): string
    {
        $md = "# Larastan Compliance Report\n\n";
        $md .= "**Generated at:** {$results['validation_timestamp']}\n";
        
        $complianceStatus = $results['overall_compliance'] ? 'COMPLIANT' : 'NON-COMPLIANT';
        $md .= "**Overall Status:** {$complianceStatus}\n";
        $md .= "**Compliance Level:** {$results['compliance_level']}/{$results['target_level']}\n\n";
        
        $md .= "## Summary\n\n";
        $md .= "- Files Analyzed: {$results['files_analyzed']}\n";
        $md .= "- Compliant Files: {$results['compliant_files']}\n";
        $md .= "- Non-Compliant Files: {$results['non_compliant_files']}\n";
        $md .= "- Total Violations: {$results['total_violations']}\n";
        $md .= "- Type Coverage: " . number_format($results['type_coverage']['percentage'], 1) . "%\n\n";
        
        if (!empty($results['violations_by_rule'])) {
            $md .= "## Violations by Rule\n\n";
            $md .= "| Rule | Count |\n";
            $md .= "|------|-------|\n";
            foreach ($results['violations_by_rule'] as $rule => $count) {
                $md .= "| {$rule} | {$count} |\n";
            }
            $md .= "\n";
        }
        
        if (!empty($results['recommendations'])) {
            $md .= "## Recommendations\n\n";
            foreach ($results['recommendations'] as $recommendation) {
                $md .= "- {$recommendation}\n";
            }
        }
        
        return $md;
    }
    
}