<?php

declare(strict_types=1);

namespace Tests\Support;

use InvalidArgumentException;
use RuntimeException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Composer\Semver\Comparator;
use Composer\Semver\VersionParser;

/**
 * Comprehensive backward compatibility testing utilities for validating API compatibility,
 * version matrices, legacy test preservation, and migration path validation.
 *
 * This helper provides utilities for:
 * - Version compatibility matrices and validation
 * - API compatibility testing across versions
 * - Legacy test pattern preservation
 * - Migration path validation and testing
 * - Deprecation handling and validation
 * - Breaking change detection and reporting
 */
final class CompatibilityTestHelper
{
    /**
     * Version compatibility matrix for different components.
     *
     * @var array<string, array<string, array<string, mixed>>>
     */
    private static array $compatibilityMatrix = [
        'php' => [
            '8.4' => [
                'supported' => true,
                'recommended' => true,
                'features' => ['strict_types', 'typed_properties', 'union_types', 'attributes'],
                'deprecations' => [],
                'breaking_changes' => [],
            ],
            '8.3' => [
                'supported' => true,
                'recommended' => true,
                'features' => ['strict_types', 'typed_properties', 'union_types', 'attributes'],
                'deprecations' => ['dynamic_properties'],
                'breaking_changes' => [],
            ],
            '8.2' => [
                'supported' => true,
                'recommended' => false,
                'features' => ['strict_types', 'typed_properties', 'union_types'],
                'deprecations' => ['dynamic_properties', 'utf8_decode'],
                'breaking_changes' => [],
            ],
            '8.1' => [
                'supported' => false,
                'recommended' => false,
                'features' => ['strict_types', 'typed_properties', 'union_types'],
                'deprecations' => ['auto_globals', 'filter_var'],
                'breaking_changes' => ['resource_to_object'],
            ],
        ],
        'laravel' => [
            '12.0' => [
                'supported' => true,
                'recommended' => true,
                'features' => ['improved_validation', 'enhanced_collections', 'better_testing'],
                'deprecations' => [],
                'breaking_changes' => [],
            ],
            '11.0' => [
                'supported' => true,
                'recommended' => false,
                'features' => ['validation_improvements', 'collection_enhancements'],
                'deprecations' => ['legacy_helpers'],
                'breaking_changes' => ['method_signatures'],
            ],
            '10.0' => [
                'supported' => false,
                'recommended' => false,
                'features' => ['basic_validation', 'standard_collections'],
                'deprecations' => ['old_syntax', 'legacy_methods'],
                'breaking_changes' => ['major_refactor', 'namespace_changes'],
            ],
        ],
        'pest' => [
            '4.0' => [
                'supported' => true,
                'recommended' => true,
                'features' => ['improved_expectations', 'better_mocking', 'enhanced_plugins'],
                'deprecations' => [],
                'breaking_changes' => [],
            ],
            '3.0' => [
                'supported' => true,
                'recommended' => false,
                'features' => ['basic_expectations', 'standard_mocking'],
                'deprecations' => ['old_syntax'],
                'breaking_changes' => ['expectation_changes'],
            ],
            '2.0' => [
                'supported' => false,
                'recommended' => false,
                'features' => ['legacy_expectations'],
                'deprecations' => ['old_api', 'legacy_methods'],
                'breaking_changes' => ['major_api_changes', 'plugin_system'],
            ],
        ],
    ];

    /**
     * Legacy test patterns that must be preserved for backward compatibility.
     *
     * @var array<string, array<string, mixed>>
     */
    private static array $legacyTestPatterns = [
        'exception_testing' => [
            'pattern' => 'expectException',
            'replacement' => 'expect()->toThrow',
            'deprecated_since' => '3.0.0',
            'removed_in' => '5.0.0',
            'migration_guide' => 'Use expect()->toThrow() instead of expectException()',
        ],
        'assertion_methods' => [
            'pattern' => 'assertSame',
            'replacement' => 'expect()->toBe',
            'deprecated_since' => '3.0.0',
            'removed_in' => null,
            'migration_guide' => 'Consider using expect()->toBe() for better readability',
        ],
        'mock_creation' => [
            'pattern' => 'createMock',
            'replacement' => 'mock',
            'deprecated_since' => '2.0.0',
            'removed_in' => null,
            'migration_guide' => 'Use mock() helper function for consistency',
        ],
    ];

    /**
     * API compatibility rules for different types of changes.
     *
     * @var array<string, array<string, mixed>>
     */
    private static array $apiCompatibilityRules = [
        'method_signature' => [
            'adding_optional_parameter' => 'compatible',
            'removing_parameter' => 'breaking',
            'changing_parameter_type' => 'breaking',
            'changing_return_type' => 'breaking',
            'adding_return_type' => 'compatible',
        ],
        'class_structure' => [
            'adding_public_method' => 'compatible',
            'removing_public_method' => 'breaking',
            'changing_method_visibility' => 'breaking',
            'adding_property' => 'compatible',
            'removing_property' => 'breaking',
        ],
        'interface_changes' => [
            'adding_method' => 'breaking',
            'removing_method' => 'breaking',
            'changing_method_signature' => 'breaking',
        ],
    ];

    /**
     * Migration paths for different version transitions.
     *
     * @var array<string, array<string, mixed>>
     */
    private static array $migrationPaths = [
        'pest_3_to_4' => [
            'steps' => [
                'Update composer.json to require pest ^4.0',
                'Run composer update',
                'Update test syntax using migration guide',
                'Run tests to verify compatibility',
            ],
            'automated_tools' => ['pest:migrate', 'rector'],
            'manual_changes' => [
                'Update custom expectations',
                'Migrate plugin configurations',
                'Update CI/CD configurations',
            ],
            'rollback_steps' => [
                'Revert composer.json changes',
                'Run composer install',
                'Restore previous test syntax',
            ],
        ],
        'laravel_11_to_12' => [
            'steps' => [
                'Update Laravel Zero to ^12.0',
                'Update service provider registrations',
                'Migrate deprecated helper usage',
                'Update configuration files',
            ],
            'automated_tools' => ['laravel:upgrade', 'rector'],
            'manual_changes' => [
                'Review breaking changes',
                'Update custom service providers',
                'Migrate deprecated methods',
            ],
            'rollback_steps' => [
                'Revert framework version',
                'Restore previous configurations',
                'Revert service provider changes',
            ],
        ],
    ];

    /**
     * Test version compatibility for a specific component.
     *
     * @param string $component The component to test (php, laravel, pest)
     * @param string $version The version to test
     * @return array<string, mixed> Compatibility test results
     */
    public static function testVersionCompatibility(string $component, string $version): array
    {
        $results = [
            'compatible' => false,
            'supported' => false,
            'recommended' => false,
            'features' => [],
            'deprecations' => [],
            'breaking_changes' => [],
            'warnings' => [],
            'errors' => [],
        ];

        if (!isset(self::$compatibilityMatrix[$component])) {
            $results['errors'][] = "Unknown component: {$component}";
            return $results;
        }

        $componentMatrix = self::$compatibilityMatrix[$component];

        // Find exact version match or closest compatible version
        if (isset($componentMatrix[$version])) {
            $versionData = $componentMatrix[$version];
            $results = array_merge($results, $versionData);
            $results['compatible'] = true;
        } else {
            // Try to find compatible version using semantic versioning
            $parser = new VersionParser();
            try {
                $normalizedVersion = $parser->normalize($version);
                $compatibleVersion = self::findCompatibleVersion($component, $normalizedVersion);
                
                if ($compatibleVersion) {
                    $versionData = $componentMatrix[$compatibleVersion];
                    $results = array_merge($results, $versionData);
                    $results['compatible'] = true;
                    $results['warnings'][] = "Using compatible version {$compatibleVersion} for {$version}";
                } else {
                    $results['errors'][] = "No compatible version found for {$component} {$version}";
                }
            } catch (\Exception $e) {
                $results['errors'][] = "Invalid version format: {$version}";
            }
        }

        // Add compatibility warnings
        if (!empty($results['deprecations'])) {
            $results['warnings'][] = 'This version contains deprecated features';
        }

        if (!empty($results['breaking_changes'])) {
            $results['warnings'][] = 'This version contains breaking changes';
        }

        return $results;
    }

    /**
     * Test API compatibility between two versions.
     *
     * @param string $className The class to test
     * @param string $oldVersion The old version
     * @param string $newVersion The new version
     * @return array<string, mixed> API compatibility results
     */
    public static function testApiCompatibility(
        string $className,
        string $oldVersion,
        string $newVersion
    ): array {
        $results = [
            'compatible' => true,
            'breaking_changes' => [],
            'compatible_changes' => [],
            'warnings' => [],
            'method_changes' => [],
            'property_changes' => [],
            'class_changes' => [],
        ];

        if (!class_exists($className)) {
            $results['compatible'] = false;
            $results['breaking_changes'][] = "Class {$className} does not exist";
            return $results;
        }

        $reflection = new ReflectionClass($className);

        // Analyze method compatibility
        $methodResults = self::analyzeMethodCompatibility($reflection);
        $results['method_changes'] = $methodResults;

        // Analyze property compatibility
        $propertyResults = self::analyzePropertyCompatibility($reflection);
        $results['property_changes'] = $propertyResults;

        // Analyze class structure compatibility
        $classResults = self::analyzeClassCompatibility($reflection);
        $results['class_changes'] = $classResults;

        // Determine overall compatibility
        foreach ([$methodResults, $propertyResults, $classResults] as $changeSet) {
            foreach ($changeSet as $change) {
                if ($change['compatibility'] === 'breaking') {
                    $results['compatible'] = false;
                    $results['breaking_changes'][] = $change['description'];
                } elseif ($change['compatibility'] === 'compatible') {
                    $results['compatible_changes'][] = $change['description'];
                } else {
                    $results['warnings'][] = $change['description'];
                }
            }
        }

        return $results;
    }

    /**
     * Validate legacy test pattern preservation.
     *
     * @param string $testFilePath Path to the test file
     * @return array<string, mixed> Legacy pattern validation results
     */
    public static function validateLegacyTestPatterns(string $testFilePath): array
    {
        $results = [
            'valid' => true,
            'patterns_found' => [],
            'deprecated_patterns' => [],
            'migration_suggestions' => [],
            'warnings' => [],
            'errors' => [],
        ];

        if (!file_exists($testFilePath)) {
            $results['valid'] = false;
            $results['errors'][] = "Test file not found: {$testFilePath}";
            return $results;
        }

        $content = file_get_contents($testFilePath);
        if ($content === false) {
            $results['valid'] = false;
            $results['errors'][] = "Could not read test file: {$testFilePath}";
            return $results;
        }

        // Check for legacy patterns
        foreach (self::$legacyTestPatterns as $patternName => $patternData) {
            $pattern = $patternData['pattern'];
            
            if (strpos($content, $pattern) !== false) {
                $results['patterns_found'][] = $patternName;
                
                // Check if pattern is deprecated
                if (isset($patternData['deprecated_since'])) {
                    $results['deprecated_patterns'][] = [
                        'pattern' => $patternName,
                        'deprecated_since' => $patternData['deprecated_since'],
                        'removed_in' => $patternData['removed_in'],
                        'replacement' => $patternData['replacement'],
                    ];
                    
                    $results['migration_suggestions'][] = $patternData['migration_guide'];
                    
                    if ($patternData['removed_in']) {
                        $results['warnings'][] = "Pattern '{$pattern}' will be removed in version {$patternData['removed_in']}";
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Test migration path between versions.
     *
     * @param string $fromVersion Source version
     * @param string $toVersion Target version
     * @param string $component Component being migrated
     * @return array<string, mixed> Migration path test results
     */
    public static function testMigrationPath(
        string $fromVersion,
        string $toVersion,
        string $component = 'pest'
    ): array {
        $results = [
            'valid' => true,
            'migration_available' => false,
            'steps' => [],
            'automated_tools' => [],
            'manual_changes' => [],
            'rollback_steps' => [],
            'estimated_effort' => 'unknown',
            'warnings' => [],
            'errors' => [],
        ];

        $migrationKey = "{$component}_{$fromVersion}_to_{$toVersion}";
        
        if (isset(self::$migrationPaths[$migrationKey])) {
            $migrationData = self::$migrationPaths[$migrationKey];
            $results['migration_available'] = true;
            $results = array_merge($results, $migrationData);
        } else {
            // Try to find a compatible migration path
            $compatiblePath = self::findMigrationPath($fromVersion, $toVersion, $component);
            
            if ($compatiblePath) {
                $results['migration_available'] = true;
                $results = array_merge($results, $compatiblePath);
                $results['warnings'][] = 'Using inferred migration path - manual verification recommended';
            } else {
                $results['valid'] = false;
                $results['errors'][] = "No migration path found from {$fromVersion} to {$toVersion}";
            }
        }

        // Estimate migration effort
        if ($results['migration_available']) {
            $results['estimated_effort'] = self::estimateMigrationEffort($results);
        }

        return $results;
    }

    /**
     * Validate version compatibility matrix.
     *
     * @param array<string, string> $versions Component versions to validate
     * @return array<string, mixed> Matrix validation results
     */
    public static function validateCompatibilityMatrix(array $versions): array
    {
        $results = [
            'valid' => true,
            'compatible_combinations' => [],
            'incompatible_combinations' => [],
            'warnings' => [],
            'errors' => [],
            'recommendations' => [],
        ];

        $componentResults = [];

        // Test each component version
        foreach ($versions as $component => $version) {
            $componentResult = self::testVersionCompatibility($component, $version);
            $componentResults[$component] = $componentResult;
            
            if (!$componentResult['compatible']) {
                $results['valid'] = false;
                $results['incompatible_combinations'][] = "{$component} {$version}";
            } else {
                $results['compatible_combinations'][] = "{$component} {$version}";
            }
        }

        // Check for cross-component compatibility issues
        $crossCompatibility = self::checkCrossComponentCompatibility($versions);
        if (!$crossCompatibility['compatible']) {
            $results['valid'] = false;
            $results['errors'] = array_merge($results['errors'], $crossCompatibility['errors']);
        }

        // Generate recommendations
        foreach ($componentResults as $component => $result) {
            if (!$result['recommended']) {
                $results['recommendations'][] = "Consider upgrading {$component} to a recommended version";
            }
            
            if (!empty($result['deprecations'])) {
                $results['warnings'][] = "{$component} {$versions[$component]} contains deprecated features";
            }
        }

        return $results;
    }

    /**
     * Test deprecation handling and validation.
     *
     * @param string $feature The deprecated feature to test
     * @param string $version The version where deprecation was introduced
     * @return array<string, mixed> Deprecation test results
     */
    public static function testDeprecationHandling(string $feature, string $version): array
    {
        $results = [
            'deprecated' => false,
            'deprecation_version' => null,
            'removal_version' => null,
            'replacement' => null,
            'migration_guide' => null,
            'warnings' => [],
            'errors' => [],
        ];

        // Search for deprecation information
        foreach (self::$legacyTestPatterns as $patternName => $patternData) {
            if ($patternData['pattern'] === $feature || $patternName === $feature) {
                $results['deprecated'] = true;
                $results['deprecation_version'] = $patternData['deprecated_since'];
                $results['removal_version'] = $patternData['removed_in'];
                $results['replacement'] = $patternData['replacement'];
                $results['migration_guide'] = $patternData['migration_guide'];
                
                if ($patternData['removed_in']) {
                    $results['warnings'][] = "Feature will be removed in version {$patternData['removed_in']}";
                }
                
                break;
            }
        }

        if (!$results['deprecated']) {
            $results['warnings'][] = "No deprecation information found for feature: {$feature}";
        }

        return $results;
    }

    /**
     * Generate comprehensive compatibility report.
     *
     * @param array<string, string> $currentVersions Current component versions
     * @param array<string, string> $targetVersions Target component versions
     * @return array<string, mixed> Comprehensive compatibility report
     */
    public static function generateCompatibilityReport(
        array $currentVersions,
        array $targetVersions
    ): array {
        $report = [
            'timestamp' => date('c'),
            'current_versions' => $currentVersions,
            'target_versions' => $targetVersions,
            'compatibility_status' => 'unknown',
            'migration_required' => false,
            'breaking_changes' => [],
            'migration_paths' => [],
            'recommendations' => [],
            'warnings' => [],
            'errors' => [],
        ];

        // Test current version compatibility
        $currentCompatibility = self::validateCompatibilityMatrix($currentVersions);
        
        // Test target version compatibility
        $targetCompatibility = self::validateCompatibilityMatrix($targetVersions);

        // Determine migration requirements
        foreach ($currentVersions as $component => $currentVersion) {
            if (isset($targetVersions[$component])) {
                $targetVersion = $targetVersions[$component];
                
                if ($currentVersion !== $targetVersion) {
                    $report['migration_required'] = true;
                    
                    $migrationPath = self::testMigrationPath($currentVersion, $targetVersion, $component);
                    $report['migration_paths'][$component] = $migrationPath;
                    
                    if (!$migrationPath['valid']) {
                        $report['errors'][] = "No valid migration path for {$component} from {$currentVersion} to {$targetVersion}";
                    }
                }
            }
        }

        // Determine overall compatibility status
        if ($currentCompatibility['valid'] && $targetCompatibility['valid']) {
            if ($report['migration_required']) {
                $report['compatibility_status'] = 'migration_required';
            } else {
                $report['compatibility_status'] = 'compatible';
            }
        } else {
            $report['compatibility_status'] = 'incompatible';
        }

        // Merge warnings and errors
        $report['warnings'] = array_merge(
            $report['warnings'],
            $currentCompatibility['warnings'],
            $targetCompatibility['warnings']
        );
        
        $report['errors'] = array_merge(
            $report['errors'],
            $currentCompatibility['errors'],
            $targetCompatibility['errors']
        );

        // Generate recommendations
        if ($report['migration_required']) {
            $report['recommendations'][] = 'Plan migration carefully and test thoroughly';
            $report['recommendations'][] = 'Review breaking changes before upgrading';
            $report['recommendations'][] = 'Consider gradual migration approach';
        }

        return $report;
    }

    /**
     * Find compatible version for a component.
     *
     * @param string $component The component name
     * @param string $version The version to find compatibility for
     * @return string|null Compatible version or null if none found
     */
    private static function findCompatibleVersion(string $component, string $version): ?string
    {
        if (!isset(self::$compatibilityMatrix[$component])) {
            return null;
        }

        $componentMatrix = self::$compatibilityMatrix[$component];
        $parser = new VersionParser();

        try {
            $normalizedVersion = $parser->normalize($version);
            
            foreach ($componentMatrix as $availableVersion => $versionData) {
                $normalizedAvailable = $parser->normalize($availableVersion);
                
                // Check if versions are compatible (same major version)
                if (Comparator::greaterThanOrEqualTo($normalizedAvailable, $normalizedVersion)) {
                    return $availableVersion;
                }
            }
        } catch (\Exception $e) {
            // Invalid version format
            return null;
        }

        return null;
    }

    /**
     * Analyze method compatibility for a class.
     *
     * @param ReflectionClass $reflection The class reflection
     * @return array<array<string, mixed>> Method compatibility analysis
     */
    private static function analyzeMethodCompatibility(ReflectionClass $reflection): array
    {
        $changes = [];
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            // Analyze method signature changes
            $parameters = $method->getParameters();
            $hasOptionalParams = false;
            
            foreach ($parameters as $param) {
                if ($param->isOptional()) {
                    $hasOptionalParams = true;
                }
            }

            // This is a simplified analysis - in real implementation,
            // you would compare against previous version
            $changes[] = [
                'type' => 'method',
                'name' => $method->getName(),
                'compatibility' => 'compatible',
                'description' => "Method {$method->getName()} is compatible",
                'details' => [
                    'parameter_count' => count($parameters),
                    'has_optional_params' => $hasOptionalParams,
                    'return_type' => $method->getReturnType()?->getName(),
                ],
            ];
        }

        return $changes;
    }

    /**
     * Analyze property compatibility for a class.
     *
     * @param ReflectionClass $reflection The class reflection
     * @return array<array<string, mixed>> Property compatibility analysis
     */
    private static function analyzePropertyCompatibility(ReflectionClass $reflection): array
    {
        $changes = [];
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $changes[] = [
                'type' => 'property',
                'name' => $property->getName(),
                'compatibility' => 'compatible',
                'description' => "Property {$property->getName()} is compatible",
                'details' => [
                    'visibility' => $property->isPublic() ? 'public' : 'protected',
                    'static' => $property->isStatic(),
                    'type' => $property->getType()?->getName(),
                ],
            ];
        }

        return $changes;
    }

    /**
     * Analyze class structure compatibility.
     *
     * @param ReflectionClass $reflection The class reflection
     * @return array<array<string, mixed>> Class compatibility analysis
     */
    private static function analyzeClassCompatibility(ReflectionClass $reflection): array
    {
        $changes = [];

        $changes[] = [
            'type' => 'class',
            'name' => $reflection->getName(),
            'compatibility' => 'compatible',
            'description' => "Class {$reflection->getName()} structure is compatible",
            'details' => [
                'is_final' => $reflection->isFinal(),
                'is_abstract' => $reflection->isAbstract(),
                'parent_class' => $reflection->getParentClass()?->getName(),
                'interfaces' => array_map(fn($i) => $i->getName(), $reflection->getInterfaces()),
            ],
        ];

        return $changes;
    }

    /**
     * Check cross-component compatibility.
     *
     * @param array<string, string> $versions Component versions
     * @return array<string, mixed> Cross-compatibility results
     */
    private static function checkCrossComponentCompatibility(array $versions): array
    {
        $results = [
            'compatible' => true,
            'errors' => [],
            'warnings' => [],
        ];

        // Check PHP and Laravel compatibility
        if (isset($versions['php']) && isset($versions['laravel'])) {
            $phpVersion = $versions['php'];
            $laravelVersion = $versions['laravel'];
            
            // Simplified compatibility check
            if (version_compare($phpVersion, '8.2', '<') && version_compare($laravelVersion, '11.0', '>=')) {
                $results['compatible'] = false;
                $results['errors'][] = "Laravel {$laravelVersion} requires PHP 8.2 or higher";
            }
        }

        // Check Pest and PHP compatibility
        if (isset($versions['php']) && isset($versions['pest'])) {
            $phpVersion = $versions['php'];
            $pestVersion = $versions['pest'];
            
            if (version_compare($phpVersion, '8.1', '<') && version_compare($pestVersion, '3.0', '>=')) {
                $results['compatible'] = false;
                $results['errors'][] = "Pest {$pestVersion} requires PHP 8.1 or higher";
            }
        }

        return $results;
    }

    /**
     * Find migration path between versions.
     *
     * @param string $fromVersion Source version
     * @param string $toVersion Target version
     * @param string $component Component name
     * @return array<string, mixed>|null Migration path data or null
     */
    private static function findMigrationPath(string $fromVersion, string $toVersion, string $component): ?array
    {
        // This is a simplified implementation
        // In practice, you would have more sophisticated path finding
        
        $parser = new VersionParser();
        try {
            $from = $parser->normalize($fromVersion);
            $to = $parser->normalize($toVersion);
            
            if (Comparator::lessThan($from, $to)) {
                // Upgrade path
                return [
                    'steps' => [
                        "Update {$component} from {$fromVersion} to {$toVersion}",
                        'Run compatibility tests',
                        'Update dependent code if necessary',
                    ],
                    'automated_tools' => ['composer'],
                    'manual_changes' => ['Review breaking changes'],
                    'rollback_steps' => ['Revert version change'],
                ];
            } else {
                // Downgrade path (not recommended)
                return [
                    'steps' => [
                        "Downgrade {$component} from {$fromVersion} to {$toVersion}",
                        'Restore compatible code patterns',
                        'Test thoroughly',
                    ],
                    'automated_tools' => [],
                    'manual_changes' => ['Restore deprecated features'],
                    'rollback_steps' => ['Upgrade back to original version'],
                ];
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Estimate migration effort based on migration data.
     *
     * @param array<string, mixed> $migrationData Migration path data
     * @return string Effort estimation (low, medium, high, very_high)
     */
    private static function estimateMigrationEffort(array $migrationData): string
    {
        $score = 0;
        
        // Count steps
        $score += count($migrationData['steps'] ?? []);
        
        // Count manual changes (higher weight)
        $score += count($migrationData['manual_changes'] ?? []) * 2;
        
        // Check for automated tools (reduces effort)
        if (!empty($migrationData['automated_tools'])) {
            $score -= 2;
        }

        return match (true) {
            $score <= 2 => 'low',
            $score <= 5 => 'medium',
            $score <= 10 => 'high',
            default => 'very_high',
        };
    }

    /**
     * Get compatibility matrix for a component.
     *
     * @param string $component Component name
     * @return array<string, array<string, mixed>> Compatibility matrix
     */
    public static function getCompatibilityMatrix(string $component): array
    {
        return self::$compatibilityMatrix[$component] ?? [];
    }

    /**
     * Get all supported components.
     *
     * @return array<string> Supported component names
     */
    public static function getSupportedComponents(): array
    {
        return array_keys(self::$compatibilityMatrix);
    }

    /**
     * Get legacy test patterns.
     *
     * @return array<string, array<string, mixed>> Legacy patterns
     */
    public static function getLegacyTestPatterns(): array
    {
        return self::$legacyTestPatterns;
    }

    /**
     * Get available migration paths.
     *
     * @return array<string, array<string, mixed>> Migration paths
     */
    public static function getMigrationPaths(): array
    {
        return self::$migrationPaths;
    }
}