<?php

declare(strict_types=1);

namespace Tests\Support\StaticAnalysis;

use Exception;
use RuntimeException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

/**
 * Comprehensive type safety validator for enforcing strict type declarations,
 * null safety, generic usage, and advanced type system compliance across
 * all test files and application code.
 * 
 * @package Tests\Support\StaticAnalysis
 * @author Roo
 * @since 1.0.0
 */
final class TypeSafetyValidator
{
    /**
     * Type safety validation configuration.
     * 
     * @var array<string, mixed>
     */
    private static array $config = [
        'enforce_strict_types' => true,
        'enforce_return_types' => true,
        'enforce_parameter_types' => true,
        'enforce_property_types' => true,
        'enforce_null_safety' => true,
        'allow_mixed_types' => false,
        'require_generic_annotations' => true,
        'validate_array_shapes' => true,
        'validate_union_types' => true,
        'validate_intersection_types' => true,
    ];
    
    /**
     * Type safety validation results.
     * 
     * @var array<string, mixed>
     */
    private static array $validationResults = [];
    
    /**
     * Built-in PHP types for validation.
     * 
     * @var array<string>
     */
    private static array $builtinTypes = [
        'int', 'float', 'string', 'bool', 'array', 'object', 'callable', 'iterable',
        'void', 'null', 'false', 'true', 'never', 'mixed',
    ];
    
    /**
     * Common generic types and their expected patterns.
     * 
     * @var array<string, string>
     */
    private static array $genericPatterns = [
        'array' => 'array<\w+(?:,\s*\w+)*>',
        'Collection' => 'Collection<\w+>',
        'Iterator' => 'Iterator<\w+(?:,\s*\w+)*>',
        'Generator' => 'Generator<\w+(?:,\s*\w+)*>',
        'Promise' => 'Promise<\w+>',
        'Optional' => 'Optional<\w+>',
    ];
    
    /**
     * Type safety violation categories.
     * 
     * @var array<string, array<string, mixed>>
     */
    private static array $violationCategories = [
        'missing_strict_types' => [
            'severity' => 'error',
            'description' => 'File missing strict_types declaration',
            'fix_suggestion' => 'Add declare(strict_types=1) at the top of the file',
        ],
        'missing_return_type' => [
            'severity' => 'error',
            'description' => 'Method missing return type declaration',
            'fix_suggestion' => 'Add explicit return type declaration',
        ],
        'missing_parameter_type' => [
            'severity' => 'error',
            'description' => 'Parameter missing type declaration',
            'fix_suggestion' => 'Add explicit parameter type declaration',
        ],
        'missing_property_type' => [
            'severity' => 'warning',
            'description' => 'Property missing type declaration',
            'fix_suggestion' => 'Add explicit property type declaration',
        ],
        'mixed_type_usage' => [
            'severity' => 'warning',
            'description' => 'Mixed type usage detected',
            'fix_suggestion' => 'Replace mixed with specific type or union type',
        ],
        'unsafe_null_usage' => [
            'severity' => 'error',
            'description' => 'Unsafe null usage without proper nullable declaration',
            'fix_suggestion' => 'Use nullable type declaration (?Type) or union type (Type|null)',
        ],
        'missing_generic_annotation' => [
            'severity' => 'info',
            'description' => 'Generic type missing specific type annotation',
            'fix_suggestion' => 'Add specific generic type annotation in PHPDoc',
        ],
        'invalid_array_shape' => [
            'severity' => 'warning',
            'description' => 'Array should use shape declaration',
            'fix_suggestion' => 'Use array{key: type} shape declaration',
        ],
        'invalid_union_type' => [
            'severity' => 'warning',
            'description' => 'Invalid union type declaration',
            'fix_suggestion' => 'Use proper union type syntax (Type1|Type2)',
        ],
    ];
    
    /**
     * Initialize type safety validator.
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
     * Validate type safety across all specified files.
     * 
     * @param array<string> $filePaths Files to validate
     * @return array<string, mixed> Comprehensive type safety results
     */
    public static function validateTypeSafety(array $filePaths = []): array
    {
        $startTime = microtime(true);
        
        $results = [
            'overall_type_safety' => false,
            'type_safety_score' => 0,
            'validation_timestamp' => date('Y-m-d H:i:s'),
            'files_analyzed' => 0,
            'type_safe_files' => 0,
            'unsafe_files' => 0,
            'total_violations' => 0,
            'violations_by_category' => [],
            'violations_by_severity' => [
                'error' => 0,
                'warning' => 0,
                'info' => 0,
            ],
            'file_results' => [],
            'type_coverage_metrics' => [
                'methods_with_return_types' => 0,
                'methods_without_return_types' => 0,
                'parameters_with_types' => 0,
                'parameters_without_types' => 0,
                'properties_with_types' => 0,
                'properties_without_types' => 0,
            ],
            'null_safety_metrics' => [
                'safe_null_usages' => 0,
                'unsafe_null_usages' => 0,
                'nullable_declarations' => 0,
            ],
            'generic_type_metrics' => [
                'proper_generic_usage' => 0,
                'missing_generic_annotations' => 0,
                'array_shape_usage' => 0,
            ],
            'recommendations' => [],
            'remediation_plan' => [],
            'execution_time' => 0,
        ];
        
        try {
            // Discover files if not provided
            $files = empty($filePaths) ? self::discoverPhpFiles() : $filePaths;
            $results['files_analyzed'] = count($files);
            
            // Validate each file
            foreach ($files as $filePath) {
                $fileResult = self::validateFileTypeSafety($filePath);
                $results['file_results'][$filePath] = $fileResult;
                
                if ($fileResult['type_safe']) {
                    $results['type_safe_files']++;
                } else {
                    $results['unsafe_files']++;
                }
                
                // Aggregate violations
                foreach ($fileResult['violations'] as $violation) {
                    $results['total_violations']++;
                    $results['violations_by_severity'][$violation['severity']]++;
                    
                    $category = $violation['category'];
                    if (!isset($results['violations_by_category'][$category])) {
                        $results['violations_by_category'][$category] = 0;
                    }
                    $results['violations_by_category'][$category]++;
                }
                
                // Aggregate metrics
                self::aggregateMetrics($results, $fileResult);
            }
            
            // Calculate type safety score
            $results['type_safety_score'] = self::calculateTypeSafetyScore($results);
            $results['overall_type_safety'] = $results['type_safety_score'] >= 90.0;
            
            // Generate recommendations
            $results['recommendations'] = self::generateTypeSafetyRecommendations($results);
            $results['remediation_plan'] = self::generateTypeSafetyRemediationPlan($results);
            
        } catch (Exception $e) {
            $results['error'] = $e->getMessage();
        }
        
        $results['execution_time'] = microtime(true) - $startTime;
        self::$validationResults = $results;
        
        return $results;
    }
    
    /**
     * Validate type safety for individual file.
     * 
     * @param string $filePath File path to validate
     * @return array<string, mixed> File type safety results
     */
    public static function validateFileTypeSafety(string $filePath): array
    {
        $result = [
            'file_path' => $filePath,
            'type_safe' => true,
            'violations' => [],
            'metrics' => [
                'strict_types_declared' => false,
                'methods_analyzed' => 0,
                'methods_with_return_types' => 0,
                'parameters_analyzed' => 0,
                'parameters_with_types' => 0,
                'properties_analyzed' => 0,
                'properties_with_types' => 0,
                'null_safe_usages' => 0,
                'unsafe_null_usages' => 0,
                'generic_annotations' => 0,
                'array_shapes' => 0,
            ],
            'validation_timestamp' => date('Y-m-d H:i:s'),
        ];
        
        if (!file_exists($filePath)) {
            $result['type_safe'] = false;
            $result['violations'][] = [
                'category' => 'file_not_found',
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
            
            // Validate strict types declaration
            $result['metrics']['strict_types_declared'] = self::validateStrictTypes($content);
            if (!$result['metrics']['strict_types_declared'] && self::$config['enforce_strict_types']) {
                $result['violations'][] = [
                    'category' => 'missing_strict_types',
                    'severity' => 'error',
                    'message' => 'Missing declare(strict_types=1) declaration',
                    'line' => 1,
                ];
            }
            
            // Validate using reflection if class exists
            $className = self::extractClassName($content, $filePath);
            if ($className && class_exists($className)) {
                $reflectionResult = self::validateClassWithReflection($className);
                $result['metrics'] = array_merge($result['metrics'], $reflectionResult['metrics']);
                $result['violations'] = array_merge($result['violations'], $reflectionResult['violations']);
            } else {
                // Validate using content analysis
                $contentResult = self::validateContentTypeSafety($content);
                $result['metrics'] = array_merge($result['metrics'], $contentResult['metrics']);
                $result['violations'] = array_merge($result['violations'], $contentResult['violations']);
            }
            
            // Determine overall type safety
            $errorViolations = array_filter($result['violations'], fn($v) => $v['severity'] === 'error');
            $result['type_safe'] = empty($errorViolations);
            
        } catch (Exception $e) {
            $result['type_safe'] = false;
            $result['violations'][] = [
                'category' => 'validation_error',
                'severity' => 'error',
                'message' => 'Type safety validation failed: ' . $e->getMessage(),
                'line' => 0,
            ];
        }
        
        return $result;
    }
    
    /**
     * Validate class using reflection for accurate type information.
     * 
     * @param string $className Class name to validate
     * @return array<string, mixed> Reflection validation results
     */
    public static function validateClassWithReflection(string $className): array
    {
        $result = [
            'metrics' => [
                'methods_analyzed' => 0,
                'methods_with_return_types' => 0,
                'parameters_analyzed' => 0,
                'parameters_with_types' => 0,
                'properties_analyzed' => 0,
                'properties_with_types' => 0,
            ],
            'violations' => [],
        ];
        
        try {
            $reflection = new ReflectionClass($className);
            
            // Validate methods
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED | ReflectionMethod::IS_PRIVATE);
            foreach ($methods as $method) {
                $result['metrics']['methods_analyzed']++;
                
                // Check return type
                if ($method->hasReturnType()) {
                    $result['metrics']['methods_with_return_types']++;
                } elseif (self::$config['enforce_return_types'] && !$method->isConstructor() && !$method->isDestructor()) {
                    $result['violations'][] = [
                        'category' => 'missing_return_type',
                        'severity' => 'error',
                        'message' => "Method {$method->getName()} missing return type",
                        'line' => $method->getStartLine(),
                    ];
                }
                
                // Check parameters
                $parameters = $method->getParameters();
                foreach ($parameters as $parameter) {
                    $result['metrics']['parameters_analyzed']++;
                    
                    if ($parameter->hasType()) {
                        $result['metrics']['parameters_with_types']++;
                        
                        // Validate null safety
                        $type = $parameter->getType();
                        if ($type && $type->allowsNull() && !str_contains($type->__toString(), '?')) {
                            $result['violations'][] = [
                                'category' => 'unsafe_null_usage',
                                'severity' => 'warning',
                                'message' => "Parameter {$parameter->getName()} allows null but not explicitly nullable",
                                'line' => $method->getStartLine(),
                            ];
                        }
                    } elseif (self::$config['enforce_parameter_types']) {
                        $result['violations'][] = [
                            'category' => 'missing_parameter_type',
                            'severity' => 'error',
                            'message' => "Parameter {$parameter->getName()} in method {$method->getName()} missing type",
                            'line' => $method->getStartLine(),
                        ];
                    }
                }
            }
            
            // Validate properties
            $properties = $reflection->getProperties();
            foreach ($properties as $property) {
                $result['metrics']['properties_analyzed']++;
                
                if ($property->hasType()) {
                    $result['metrics']['properties_with_types']++;
                } elseif (self::$config['enforce_property_types']) {
                    $result['violations'][] = [
                        'category' => 'missing_property_type',
                        'severity' => 'warning',
                        'message' => "Property {$property->getName()} missing type declaration",
                        'line' => 0, // Properties don't have line numbers in reflection
                    ];
                }
            }
            
        } catch (Exception $e) {
            $result['violations'][] = [
                'category' => 'reflection_error',
                'severity' => 'error',
                'message' => 'Reflection validation failed: ' . $e->getMessage(),
                'line' => 0,
            ];
        }
        
        return $result;
    }
    
    /**
     * Validate content using text analysis when reflection is not available.
     * 
     * @param string $content File content
     * @return array<string, mixed> Content validation results
     */
    public static function validateContentTypeSafety(string $content): array
    {
        $result = [
            'metrics' => [
                'methods_analyzed' => 0,
                'methods_with_return_types' => 0,
                'parameters_analyzed' => 0,
                'parameters_with_types' => 0,
                'properties_analyzed' => 0,
                'properties_with_types' => 0,
                'null_safe_usages' => 0,
                'unsafe_null_usages' => 0,
                'generic_annotations' => 0,
                'array_shapes' => 0,
            ],
            'violations' => [],
        ];
        
        $lines = explode("\n", $content);
        
        foreach ($lines as $lineNumber => $line) {
            $lineNum = $lineNumber + 1;
            
            // Validate method declarations
            if (preg_match('/^\s*(?:public|private|protected)?\s*function\s+(\w+)\s*\(([^)]*)\)(?:\s*:\s*(\w+(?:\|\w+)*))?\s*/', $line, $matches)) {
                $functionName = $matches[1];
                $parameters = $matches[2];
                $returnType = $matches[3] ?? null;
                
                $result['metrics']['methods_analyzed']++;
                
                // Check return type
                if ($returnType) {
                    $result['metrics']['methods_with_return_types']++;
                    
                    // Check for mixed type usage
                    if ($returnType === 'mixed' && !self::$config['allow_mixed_types']) {
                        $result['violations'][] = [
                            'category' => 'mixed_type_usage',
                            'severity' => 'warning',
                            'message' => "Method {$functionName} uses mixed return type",
                            'line' => $lineNum,
                        ];
                    }
                } elseif (self::$config['enforce_return_types'] && !in_array($functionName, ['__construct', '__destruct'], true)) {
                    $result['violations'][] = [
                        'category' => 'missing_return_type',
                        'severity' => 'error',
                        'message' => "Method {$functionName} missing return type",
                        'line' => $lineNum,
                    ];
                }
                
                // Check parameters
                if (!empty(trim($parameters))) {
                    $paramList = array_map('trim', explode(',', $parameters));
                    foreach ($paramList as $param) {
                        if (preg_match('/\$\w+/', $param)) {
                            $result['metrics']['parameters_analyzed']++;
                            
                            // Check if parameter has type
                            if (preg_match('/^\s*(\w+(?:\|\w+)*)\s+\$\w+/', $param)) {
                                $result['metrics']['parameters_with_types']++;
                            } elseif (self::$config['enforce_parameter_types']) {
                                $result['violations'][] = [
                                    'category' => 'missing_parameter_type',
                                    'severity' => 'error',
                                    'message' => "Parameter in method {$functionName} missing type",
                                    'line' => $lineNum,
                                ];
                            }
                        }
                    }
                }
            }
            
            // Validate property declarations
            if (preg_match('/^\s*(?:public|private|protected)\s+(?:static\s+)?(?:(\w+(?:\|\w+)*)\s+)?\$(\w+)/', $line, $matches)) {
                $propertyType = $matches[1] ?? null;
                $propertyName = $matches[2];
                
                $result['metrics']['properties_analyzed']++;
                
                if ($propertyType) {
                    $result['metrics']['properties_with_types']++;
                } elseif (self::$config['enforce_property_types']) {
                    $result['violations'][] = [
                        'category' => 'missing_property_type',
                        'severity' => 'warning',
                        'message' => "Property {$propertyName} missing type declaration",
                        'line' => $lineNum,
                    ];
                }
            }
            
            // Validate PHPDoc annotations for generics
            if (preg_match('/@(?:var|param|return)\s+(\w+)<([^>]+)>/', $line)) {
                $result['metrics']['generic_annotations']++;
            }
            
            // Validate array shapes
            if (preg_match('/@(?:var|param|return)\s+array\{[^}]+\}/', $line)) {
                $result['metrics']['array_shapes']++;
            }
            
            // Check for mixed type usage in PHPDoc
            if (preg_match('/@(?:var|param|return)\s+mixed\b/', $line) && !self::$config['allow_mixed_types']) {
                $result['violations'][] = [
                    'category' => 'mixed_type_usage',
                    'severity' => 'warning',
                    'message' => 'Mixed type usage in PHPDoc annotation',
                    'line' => $lineNum,
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * Generate comprehensive type safety report.
     * 
     * @param string $format Report format (json, html, markdown)
     * @return string Generated report
     */
    public static function generateTypeSafetyReport(string $format = 'json'): string
    {
        $results = self::$validationResults;
        
        return match ($format) {
            'html' => self::generateHtmlTypeSafetyReport($results),
            'markdown' => self::generateMarkdownTypeSafetyReport($results),
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
     * Configure type safety validator.
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
     * Validate strict types declaration in content.
     * 
     * @param string $content File content
     * @return bool Whether strict types is declared
     */
    private static function validateStrictTypes(string $content): bool
    {
        return preg_match('/declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/', $content) === 1;
    }
    
    /**
     * Extract class name from file content.
     * 
     * @param string $content File content
     * @param string $filePath File path for context
     * @return string|null Class name if found
     */
    private static function extractClassName(string $content, string $filePath): ?string
    {
        // Extract namespace
        $namespace = '';
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $namespace = $matches[1] . '\\';
        }
        
        // Extract class name
        if (preg_match('/(?:class|interface|trait)\s+(\w+)/', $content, $matches)) {
            return $namespace . $matches[1];
        }
        
        return null;
    }
    
    /**
     * Discover PHP files for analysis.
     * 
     * @return array<string> PHP file paths
     */
    private static function discoverPhpFiles(): array
    {
        $files = [];
        $directories = ['tests', 'app'];
        
        foreach ($directories as $directory) {
            if (is_dir($directory)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
                );
                
                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getExtension() === 'php') {
                        $files[] = $file->getPathname();
                    }
                }
            }
        }
        
        return $files;
    }
    
    /**
     * Aggregate metrics from file results.
     * 
     * @param array<string, mixed> $results Overall results
     * @param array<string, mixed> $fileResult File result
     * @return void
     */
    private static function aggregateMetrics(array &$results, array $fileResult): void
    {
        $metrics = $fileResult['metrics'];
        
        $results['type_coverage_metrics']['methods_with_return_types'] += $metrics['methods_with_return_types'] ?? 0;
        $results['type_coverage_metrics']['methods_without_return_types'] += 
            ($metrics['methods_analyzed'] ?? 0) - ($metrics['methods_with_return_types'] ?? 0);
        
        $results['type_coverage_metrics']['parameters_with_types'] += $metrics['parameters_with_types'] ?? 0;
        $results['type_coverage_metrics']['parameters_without_types'] += 
            ($metrics['parameters_analyzed'] ?? 0) - ($metrics['parameters_with_types'] ?? 0);
        
        $results['type_coverage_metrics']['properties_with_types'] += $metrics['properties_with_types'] ?? 0;
        $results['type_coverage_metrics']['properties_without_types'] += 
            ($metrics['properties_analyzed'] ?? 0) - ($metrics['properties_with_types'] ?? 0);
        
        $results['null_safety_metrics']['safe_null_usages'] += $metrics['null_safe_usages'] ?? 0;
        $results['null_safety_metrics']['unsafe_null_usages'] += $metrics['unsafe_null_usages'] ?? 0;
        
        $results['generic_type_metrics']['proper_generic_usage'] += $metrics['generic_annotations'] ?? 0;
        $results['generic_type_metrics']['array_shape_usage'] += $metrics['array_shapes'] ?? 0;
    }
    
    /**
     * Calculate overall type safety score.
     * 
     * @param array<string, mixed> $results Validation results
     * @return float Type safety score (0-100)
     */
    private static function calculateTypeSafetyScore(array $results): float
    {
        if ($results['files_analyzed'] === 0) {
            return 0;
        }
        
        // Base score from file compliance
        $fileComplianceScore = ($results['type_safe_files'] / $results['files_analyzed']) * 100;
        
        // Type coverage score
        $typeCoverage = $results['type_coverage_metrics'];
        $totalMethods = $typeCoverage['methods_with_return_types'] + $typeCoverage['methods_without_return_types'];
        $totalParameters = $typeCoverage['parameters_with_types'] + $typeCoverage['parameters_without_types'];
        
        $methodTypeScore = $totalMethods > 0 ? ($typeCoverage['methods_with_return_types'] / $totalMethods) * 100 : 100;
        $parameterTypeScore = $totalParameters > 0 ? ($typeCoverage['parameters_with_types'] / $totalParameters) * 100 : 100;
        
        // Weighted average
        return ($fileComplianceScore * 0.4) + ($methodTypeScore * 0.3) + ($parameterTypeScore * 0.3);
    }
    
    /**
     * Generate type safety recommendations.
     * 
     * @param array<string, mixed> $results Validation results
     * @return array<string> Recommendations
     */
    private static function generateTypeSafetyRecommendations(array $results): array
    {
        $recommendations = [];
        
        if ($results['overall_type_safety']) {
            $recommendations[] = 'Excellent type safety compliance achieved!';
            $recommendations[] = 'Consider implementing advanced generic type annotations';
        } else {
            if ($results['violations_by_severity']['error'] > 0) {
                $recommendations[] = 'Priority: Fix all error-level type safety violations';
                $recommendations[] = 'Add strict_types declarations to all files';
                $recommendations[] = 'Add return type declarations to all methods';
                $recommendations[] = 'Add parameter type declarations';
            }
            
            if ($results['violations_by_category']['mixed_type_usage'] ?? 0 > 0) {
                $recommendations[] = 'Replace mixed types with specific type declarations';
            }
            
            if ($results['type_coverage_metrics']['methods_without_return_types'] > 0) {
                $recommendations[] = 'Improve method return type coverage';
            }
            
            if ($results['type_coverage_metrics']['parameters_without_types'] > 0) {
                $recommendations[] = 'Improve parameter type coverage';
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Generate type safety remediation plan.
     * 
     * @param array<string, mixed> $results Validation results
     * @return array<string, mixed> Remediation plan
     */
    private static function generateTypeSafetyRemediationPlan(array $results): array
    {
        $plan = [
            'immediate_actions' => [],
            'short_term_actions' => [],
            'long_term_actions' => [],
            'estimated_effort' => 'medium',
        ];
        
        // Immediate actions for critical violations
        if ($results['violations_by_severity']['error'] > 0) {
            $plan['immediate_actions'][] = 'Add declare(strict_types=1) to all files';
            $plan['immediate_actions'][] = 'Add return type declarations to methods';
            $plan['immediate_actions'][] = 'Add parameter type declarations';
        }
        
        // Short-term improvements
        if ($results['violations_by_severity']['warning'] > 0) {
            $plan['short_term_actions'][] = 'Add property type declarations';
            $plan['short_term_actions'][] = 'Replace mixed types with specific types';
            $plan['short_term_actions'][] = 'Improve null safety declarations';
        }
        
        // Long-term enhancements
        $plan['long_term_actions'][] = 'Implement comprehensive generic type annotations';
        $plan['long_term_actions'][] = 'Use array shapes for complex array structures';
        $plan['long_term_actions'][] = 'Regular type safety monitoring and validation';
        
        // Estimate effort
        $totalViolations = $results['total_violations'];
        $plan['estimated_effort'] = match (true) {
            $totalViolations === 0 => 'none',
            $totalViolations <= 20 => 'low',
            $totalViolations <= 100 => 'medium',
            default => 'high',
        };
        
        return $plan;
    }
    
    /**
     * Generate HTML type safety report.
     *
     * @param array<string, mixed> $results Validation results
     * @return string HTML report
     */
    private static function generateHtmlTypeSafetyReport(array $results): string
    {
        $html = "<!DOCTYPE html>\n<html>\n<head>\n<title>Type Safety Report</title>\n";
        $html .= "<style>body{font-family:Arial,sans-serif;margin:20px;}table{border-collapse:collapse;width:100%;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background-color:#f2f2f2;}.safe{color:green;}.unsafe{color:red;}.error{color:red;}.warning{color:orange;}.info{color:blue;}</style>\n";
        $html .= "</head>\n<body>\n";
        $html .= "<h1>Type Safety Validation Report</h1>\n";
        $html .= "<p>Generated at: {$results['validation_timestamp']}</p>\n";
        
        $safetyStatus = $results['overall_type_safety'] ? 'TYPE SAFE' : 'TYPE UNSAFE';
        $safetyClass = $results['overall_type_safety'] ? 'safe' : 'unsafe';
        $html .= "<p>Overall Status: <span class='{$safetyClass}'>{$safetyStatus}</span></p>\n";
        $html .= "<p>Type Safety Score: " . number_format($results['type_safety_score'], 1) . "%</p>\n";
        
        $html .= "<h2>Summary</h2>\n";
        $html .= "<table>\n";
        $html .= "<tr><th>Metric</th><th>Value</th></tr>\n";
        $html .= "<tr><td>Files Analyzed</td><td>{$results['files_analyzed']}</td></tr>\n";
        $html .= "<tr><td>Type Safe Files</td><td>{$results['type_safe_files']}</td></tr>\n";
        $html .= "<tr><td>Unsafe Files</td><td>{$results['unsafe_files']}</td></tr>\n";
        $html .= "<tr><td>Total Violations</td><td>{$results['total_violations']}</td></tr>\n";
        $html .= "</table>\n";
        
        $html .= "<h2>Type Coverage Metrics</h2>\n";
        $html .= "<table>\n";
        $html .= "<tr><th>Category</th><th>With Types</th><th>Without Types</th><th>Coverage</th></tr>\n";
        
        $methodsTotal = $results['type_coverage_metrics']['methods_with_return_types'] + $results['type_coverage_metrics']['methods_without_return_types'];
        $methodsCoverage = $methodsTotal > 0 ? ($results['type_coverage_metrics']['methods_with_return_types'] / $methodsTotal) * 100 : 0;
        $html .= "<tr><td>Methods</td><td>{$results['type_coverage_metrics']['methods_with_return_types']}</td><td>{$results['type_coverage_metrics']['methods_without_return_types']}</td><td>" . number_format($methodsCoverage, 1) . "%</td></tr>\n";
        
        $parametersTotal = $results['type_coverage_metrics']['parameters_with_types'] + $results['type_coverage_metrics']['parameters_without_types'];
        $parametersCoverage = $parametersTotal > 0 ? ($results['type_coverage_metrics']['parameters_with_types'] / $parametersTotal) * 100 : 0;
        $html .= "<tr><td>Parameters</td><td>{$results['type_coverage_metrics']['parameters_with_types']}</td><td>{$results['type_coverage_metrics']['parameters_without_types']}</td><td>" . number_format($parametersCoverage, 1) . "%</td></tr>\n";
        
        $propertiesTotal = $results['type_coverage_metrics']['properties_with_types'] + $results['type_coverage_metrics']['properties_without_types'];
        $propertiesCoverage = $propertiesTotal > 0 ? ($results['type_coverage_metrics']['properties_with_types'] / $propertiesTotal) * 100 : 0;
        $html .= "<tr><td>Properties</td><td>{$results['type_coverage_metrics']['properties_with_types']}</td><td>{$results['type_coverage_metrics']['properties_without_types']}</td><td>" . number_format($propertiesCoverage, 1) . "%</td></tr>\n";
        
        $html .= "</table>\n";
        
        if (!empty($results['violations_by_category'])) {
            $html .= "<h2>Violations by Category</h2>\n";
            $html .= "<table>\n";
            $html .= "<tr><th>Category</th><th>Count</th></tr>\n";
            foreach ($results['violations_by_category'] as $category => $count) {
                $html .= "<tr><td>{$category}</td><td>{$count}</td></tr>\n";
            }
            $html .= "</table>\n";
        }
        
        $html .= "</body>\n</html>";
        
        return $html;
    }
    
    /**
     * Generate Markdown type safety report.
     *
     * @param array<string, mixed> $results Validation results
     * @return string Markdown report
     */
    private static function generateMarkdownTypeSafetyReport(array $results): string
    {
        $md = "# Type Safety Validation Report\n\n";
        $md .= "**Generated at:** {$results['validation_timestamp']}\n";
        
        $safetyStatus = $results['overall_type_safety'] ? 'TYPE SAFE' : 'TYPE UNSAFE';
        $md .= "**Overall Status:** {$safetyStatus}\n";
        $md .= "**Type Safety Score:** " . number_format($results['type_safety_score'], 1) . "%\n\n";
        
        $md .= "## Summary\n\n";
        $md .= "- Files Analyzed: {$results['files_analyzed']}\n";
        $md .= "- Type Safe Files: {$results['type_safe_files']}\n";
        $md .= "- Unsafe Files: {$results['unsafe_files']}\n";
        $md .= "- Total Violations: {$results['total_violations']}\n\n";
        
        $md .= "## Type Coverage Metrics\n\n";
        $md .= "| Category | With Types | Without Types | Coverage |\n";
        $md .= "|----------|------------|---------------|----------|\n";
        
        $methodsTotal = $results['type_coverage_metrics']['methods_with_return_types'] + $results['type_coverage_metrics']['methods_without_return_types'];
        $methodsCoverage = $methodsTotal > 0 ? ($results['type_coverage_metrics']['methods_with_return_types'] / $methodsTotal) * 100 : 0;
        $md .= "| Methods | {$results['type_coverage_metrics']['methods_with_return_types']} | {$results['type_coverage_metrics']['methods_without_return_types']} | " . number_format($methodsCoverage, 1) . "% |\n";
        
        $parametersTotal = $results['type_coverage_metrics']['parameters_with_types'] + $results['type_coverage_metrics']['parameters_without_types'];
        $parametersCoverage = $parametersTotal > 0 ? ($results['type_coverage_metrics']['parameters_with_types'] / $parametersTotal) * 100 : 0;
        $md .= "| Parameters | {$results['type_coverage_metrics']['parameters_with_types']} | {$results['type_coverage_metrics']['parameters_without_types']} | " . number_format($parametersCoverage, 1) . "% |\n";
        
        $propertiesTotal = $results['type_coverage_metrics']['properties_with_types'] + $results['type_coverage_metrics']['properties_without_types'];
        $propertiesCoverage = $propertiesTotal > 0 ? ($results['type_coverage_metrics']['properties_with_types'] / $propertiesTotal) * 100 : 0;
        $md .= "| Properties | {$results['type_coverage_metrics']['properties_with_types']} | {$results['type_coverage_metrics']['properties_without_types']} | " . number_format($propertiesCoverage, 1) . "% |\n\n";
        
        if (!empty($results['violations_by_category'])) {
            $md .= "## Violations by Category\n\n";
            $md .= "| Category | Count |\n";
            $md .= "|----------|-------|\n";
            foreach ($results['violations_by_category'] as $category => $count) {
                $md .= "| {$category} | {$count} |\n";
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
        
        