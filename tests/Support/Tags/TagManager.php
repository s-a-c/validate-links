<?php

declare(strict_types=1);

namespace Tests\Support\Tags;

/**
 * Tag Manager for PEST Test Organization
 * 
 * Provides comprehensive tagging system for selective test execution,
 * filtering, and organization. Supports hierarchical tags, tag groups,
 * and conditional execution based on environment.
 */
class TagManager
{
    /**
     * Performance-related tags
     */
    public const PERFORMANCE_TAGS = [
        'performance' => 'General performance tests',
        'benchmark' => 'Benchmarking and comparison tests',
        'memory' => 'Memory usage and leak tests',
        'load' => 'Load and stress testing',
        'slow' => 'Tests that take longer than normal',
        'fast' => 'Quick tests for rapid feedback',
    ];

    /**
     * Functional testing tags
     */
    public const FUNCTIONAL_TAGS = [
        'unit' => 'Unit tests for individual components',
        'integration' => 'Integration tests between components',
        'feature' => 'Feature-level tests',
        'e2e' => 'End-to-end tests',
        'acceptance' => 'User acceptance tests',
        'regression' => 'Regression tests',
    ];

    /**
     * Component-specific tags
     */
    public const COMPONENT_TAGS = [
        'validation' => 'Link validation functionality',
        'reporting' => 'Report generation and formatting',
        'security' => 'Security validation and checks',
        'github' => 'GitHub-specific functionality',
        'config' => 'Configuration management',
        'cli' => 'Command-line interface tests',
    ];

    /**
     * Environment and execution tags
     */
    public const ENVIRONMENT_TAGS = [
        'ci' => 'Continuous integration environment',
        'local' => 'Local development environment',
        'production' => 'Production-like environment',
        'staging' => 'Staging environment',
        'docker' => 'Docker container environment',
        'parallel' => 'Safe for parallel execution',
        'sequential' => 'Must run sequentially',
    ];

    /**
     * Quality and maintenance tags
     */
    public const QUALITY_TAGS = [
        'smoke' => 'Smoke tests for basic functionality',
        'critical' => 'Critical path tests',
        'edge_case' => 'Edge case and boundary tests',
        'error_handling' => 'Error handling and exception tests',
        'cleanup' => 'Tests that require special cleanup',
        'external' => 'Tests requiring external dependencies',
    ];

    /**
     * All available tags grouped by category
     */
    public const ALL_TAGS = [
        'performance' => self::PERFORMANCE_TAGS,
        'functional' => self::FUNCTIONAL_TAGS,
        'component' => self::COMPONENT_TAGS,
        'environment' => self::ENVIRONMENT_TAGS,
        'quality' => self::QUALITY_TAGS,
    ];

    /**
     * Tag aliases for convenience
     */
    public const TAG_ALIASES = [
        'perf' => 'performance',
        'mem' => 'memory',
        'int' => 'integration',
        'sec' => 'security',
        'gh' => 'github',
        'ext' => 'external',
        'par' => 'parallel',
        'seq' => 'sequential',
    ];

    /**
     * Tags that exclude tests by default
     */
    public const EXCLUDED_BY_DEFAULT = [
        'performance',
        'benchmark',
        'load',
        'slow',
        'external',
        'production',
    ];

    /**
     * Tags that require special environment setup
     */
    public const REQUIRES_SETUP = [
        'external' => ['network', 'api_keys'],
        'github' => ['github_token'],
        'load' => ['performance_environment'],
        'production' => ['production_config'],
    ];

    /**
     * Get all tags in a specific category
     */
    public static function getTagsByCategory(string $category): array
    {
        return self::ALL_TAGS[$category] ?? [];
    }

    /**
     * Get all available tags as a flat array
     */
    public static function getAllTags(): array
    {
        $tags = [];
        foreach (self::ALL_TAGS as $category => $categoryTags) {
            $tags = array_merge($tags, array_keys($categoryTags));
        }
        return array_unique($tags);
    }

    /**
     * Resolve tag aliases to actual tag names
     */
    public static function resolveAlias(string $tag): string
    {
        return self::TAG_ALIASES[$tag] ?? $tag;
    }

    /**
     * Check if a tag is excluded by default
     */
    public static function isExcludedByDefault(string $tag): bool
    {
        $resolvedTag = self::resolveAlias($tag);
        return in_array($resolvedTag, self::EXCLUDED_BY_DEFAULT, true);
    }

    /**
     * Check if a tag requires special setup
     */
    public static function requiresSetup(string $tag): bool
    {
        $resolvedTag = self::resolveAlias($tag);
        return array_key_exists($resolvedTag, self::REQUIRES_SETUP);
    }

    /**
     * Get setup requirements for a tag
     */
    public static function getSetupRequirements(string $tag): array
    {
        $resolvedTag = self::resolveAlias($tag);
        return self::REQUIRES_SETUP[$resolvedTag] ?? [];
    }

    /**
     * Validate that tags exist
     */
    public static function validateTags(array $tags): array
    {
        $allTags = self::getAllTags();
        $invalid = [];
        
        foreach ($tags as $tag) {
            $resolvedTag = self::resolveAlias($tag);
            if (!in_array($resolvedTag, $allTags, true)) {
                $invalid[] = $tag;
            }
        }
        
        return $invalid;
    }

    /**
     * Get tag description
     */
    public static function getTagDescription(string $tag): ?string
    {
        $resolvedTag = self::resolveAlias($tag);
        
        foreach (self::ALL_TAGS as $category => $categoryTags) {
            if (isset($categoryTags[$resolvedTag])) {
                return $categoryTags[$resolvedTag];
            }
        }
        
        return null;
    }

    /**
     * Get category for a tag
     */
    public static function getTagCategory(string $tag): ?string
    {
        $resolvedTag = self::resolveAlias($tag);
        
        foreach (self::ALL_TAGS as $category => $categoryTags) {
            if (array_key_exists($resolvedTag, $categoryTags)) {
                return $category;
            }
        }
        
        return null;
    }

    /**
     * Filter tags based on environment conditions
     */
    public static function filterTagsForEnvironment(array $tags, array $environment = []): array
    {
        $filtered = [];
        
        foreach ($tags as $tag) {
            $resolvedTag = self::resolveAlias($tag);
            
            // Check if tag should be excluded by default
            if (self::isExcludedByDefault($resolvedTag)) {
                // Only include if explicitly enabled in environment
                $envKey = 'INCLUDE_' . strtoupper($resolvedTag) . '_TESTS';
                if (!($environment[$envKey] ?? false)) {
                    continue;
                }
            }
            
            // Check setup requirements
            if (self::requiresSetup($resolvedTag)) {
                $requirements = self::getSetupRequirements($resolvedTag);
                $hasAllRequirements = true;
                
                foreach ($requirements as $requirement) {
                    $envKey = strtoupper($requirement);
                    if (!($environment[$envKey] ?? false)) {
                        $hasAllRequirements = false;
                        break;
                    }
                }
                
                if (!$hasAllRequirements) {
                    continue;
                }
            }
            
            $filtered[] = $resolvedTag;
        }
        
        return $filtered;
    }

    /**
     * Generate PEST groups configuration from tags
     */
    public static function generatePestGroups(): array
    {
        $groups = [];
        
        foreach (self::ALL_TAGS as $category => $categoryTags) {
            foreach (array_keys($categoryTags) as $tag) {
                $groups[$tag] = [
                    'description' => self::getTagDescription($tag),
                    'category' => $category,
                    'excluded_by_default' => self::isExcludedByDefault($tag),
                    'requires_setup' => self::requiresSetup($tag),
                ];
            }
        }
        
        return $groups;
    }

    /**
     * Create tag combinations for complex filtering
     */
    public static function createTagCombinations(array $include = [], array $exclude = []): array
    {
        return [
            'include' => array_map([self::class, 'resolveAlias'], $include),
            'exclude' => array_map([self::class, 'resolveAlias'], $exclude),
        ];
    }

    /**
     * Get recommended tags for a test type
     */
    public static function getRecommendedTags(string $testType): array
    {
        $recommendations = [
            'unit' => ['unit', 'fast', 'parallel'],
            'integration' => ['integration', 'sequential'],
            'feature' => ['feature', 'acceptance'],
            'performance' => ['performance', 'benchmark', 'slow', 'sequential'],
            'security' => ['security', 'critical'],
            'e2e' => ['e2e', 'slow', 'sequential', 'external'],
        ];
        
        return $recommendations[$testType] ?? [];
    }
}