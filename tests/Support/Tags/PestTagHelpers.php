<?php

declare(strict_types=1);

use Tests\Support\Tags\TagManager;
use Tests\Support\Tags\TagFilter;

/**
 * PEST Tag Helper Functions
 * 
 * Provides convenient functions for applying tags to PEST tests,
 * creating tag-based test groups, and managing test execution.
 * These functions integrate seamlessly with PEST's syntax.
 */

if (!function_exists('tags')) {
    /**
     * Apply multiple tags to a test
     * 
     * @param string ...$tags Tags to apply
     * @return \Pest\PendingCalls\TestCall
     */
    function tags(string ...$tags): \Pest\PendingCalls\TestCall
    {
        $resolvedTags = array_map([TagManager::class, 'resolveAlias'], $tags);
        return test()->group(...$resolvedTags);
    }
}

if (!function_exists('performance')) {
    /**
     * Mark test as performance-related
     * 
     * @param string ...$additionalTags Additional tags to apply
     * @return \Pest\PendingCalls\TestCall
     */
    function performance(string ...$additionalTags): \Pest\PendingCalls\TestCall
    {
        return tags('performance', 'slow', 'sequential', ...$additionalTags);
    }
}

if (!function_exists('benchmark')) {
    /**
     * Mark test as benchmark
     * 
     * @param string ...$additionalTags Additional tags to apply
     * @return \Pest\PendingCalls\TestCall
     */
    function benchmark(string ...$additionalTags): \Pest\PendingCalls\TestCall
    {
        return tags('benchmark', 'performance', 'slow', 'sequential', ...$additionalTags);
    }
}

if (!function_exists('memory')) {
    /**
     * Mark test as memory-related
     * 
     * @param string ...$additionalTags Additional tags to apply
     * @return \Pest\PendingCalls\TestCall
     */
    function memory(string ...$additionalTags): \Pest\PendingCalls\TestCall
    {
        return tags('memory', 'performance', 'sequential', ...$additionalTags);
    }
}

if (!function_exists('load')) {
    /**
     * Mark test as load testing
     * 
     * @param string ...$additionalTags Additional tags to apply
     * @return \Pest\PendingCalls\TestCall
     */
    function load(string ...$additionalTags): \Pest\PendingCalls\TestCall
    {
        return tags('load', 'performance', 'slow', 'sequential', 'external', ...$additionalTags);
    }
}

if (!function_exists('unit')) {
    /**
     * Mark test as unit test
     * 
     * @param string ...$additionalTags Additional tags to apply
     * @return \Pest\PendingCalls\TestCall
     */
    function unit(string ...$additionalTags): \Pest\PendingCalls\TestCall
    {
        return tags('unit', 'fast', 'parallel', ...$additionalTags);
    }
}

if (!function_exists('integration')) {
    /**
     * Mark test as integration test
     * 
     * @param string ...$additionalTags Additional tags to apply
     * @return \Pest\PendingCalls\TestCall
     */
    function integration(string ...$additionalTags): \Pest\PendingCalls\TestCall
    {
        return tags('integration', 'sequential', ...$additionalTags);
    }
}

if (!function_exists('feature')) {
    /**
     * Mark test as feature test
     * 
     * @param string ...$additionalTags Additional tags to apply
     * @return \Pest\PendingCalls\TestCall
     */
    function feature(string ...$additionalTags): \Pest\PendingCalls\TestCall
    {
        return tags('feature', 'acceptance', ...$additionalTags);
    }
}

if (!function_exists('e2e')) {
    /**
     * Mark test as end-to-end test
     * 
     * @param string ...$additionalTags Additional tags to apply
     * @return \Pest\PendingCalls\TestCall
     */
    function e2e(string ...$additionalTags): \Pest\PendingCalls\TestCall
    {
        return tags('e2e', 'slow', 'sequential', 'external', ...$additionalTags);
    }
}

if (!function_exists('security')) {
    /**
     * Mark test as security-related
     * 
     * @param string ...$additionalTags Additional tags to apply
     * @return \Pest\PendingCalls\TestCall
     */
    function security(string ...$additionalTags): \Pest\PendingCalls\TestCall
    {
        return tags('security', 'critical', ...$additionalTags);
    }
}

if (!function_exists('validation')) {
    /**
     * Mark test as validation-related
     * 
     * @param string ...$additionalTags Additional tags to apply
     * @return \Pest\PendingCalls\TestCall
     */
    function validation(string ...$additionalTags): \Pest\PendingCalls\TestCall
    {
        return tags('validation', ...$additionalTags);
    }
}

if (!function_exists('reporting')) {
    /**
     * Mark test as reporting-related
     * 
     * @param string ...$additionalTags Additional tags to apply
     * @return \Pest\PendingCalls\TestCall
     */
    function reporting(string ...$additionalTags): \Pest\PendingCalls\TestCall
    {
        return tags('reporting', ...$additionalTags);
    }
}

if (!function_exists('github')) {
    /**
     * Mark test as GitHub-specific
     * 
     * @param string ...$additionalTags Additional tags to apply
     * @return \Pest\PendingCalls\TestCall
     */
    function github(string ...$additionalTags): \Pest\PendingCalls\TestCall
    {
        return tags('github', 'external', ...$additionalTags);
    }
}

if (!function_exists('smoke')) {
    /**
     * Mark test as smoke test
     * 
     * @param string ...$additionalTags Additional tags to apply
     * @return \Pest\PendingCalls\TestCall
     */
    function smoke(string ...$additionalTags): \Pest\PendingCalls\TestCall
    {
        return tags('smoke', 'critical', 'fast', ...$additionalTags);
    }
}

if (!function_exists('critical')) {
    /**
     * Mark test as critical
     * 
     * @param string ...$additionalTags Additional tags to apply
     * @return \Pest\PendingCalls\TestCall
     */
    function critical(string ...$additionalTags): \Pest\PendingCalls\TestCall
    {
        return tags('critical', ...$additionalTags);
    }
}

if (!function_exists('edge_case')) {
    /**
     * Mark test as edge case
     * 
     * @param string ...$additionalTags Additional tags to apply
     * @return \Pest\PendingCalls\TestCall
     */
    function edge_case(string ...$additionalTags): \Pest\PendingCalls\TestCall
    {
        return tags('edge_case', ...$additionalTags);
    }
}

if (!function_exists('error_handling')) {
    /**
     * Mark test as error handling
     * 
     * @param string ...$additionalTags Additional tags to apply
     * @return \Pest\PendingCalls\TestCall
     */
    function error_handling(string ...$additionalTags): \Pest\PendingCalls\TestCall
    {
        return tags('error_handling', ...$additionalTags);
    }
}

if (!function_exists('external')) {
    /**
     * Mark test as requiring external dependencies
     * 
     * @param string ...$additionalTags Additional tags to apply
     * @return \Pest\PendingCalls\TestCall
     */
    function external(string ...$additionalTags): \Pest\PendingCalls\TestCall
    {
        return tags('external', 'slow', 'sequential', ...$additionalTags);
    }
}

if (!function_exists('ci')) {
    /**
     * Mark test as CI-appropriate
     * 
     * @param string ...$additionalTags Additional tags to apply
     * @return \Pest\PendingCalls\TestCall
     */
    function ci(string ...$additionalTags): \Pest\PendingCalls\TestCall
    {
        return tags('ci', 'fast', ...$additionalTags);
    }
}

if (!function_exists('local')) {
    /**
     * Mark test as local development
     * 
     * @param string ...$additionalTags Additional tags to apply
     * @return \Pest\PendingCalls\TestCall
     */
    function local(string ...$additionalTags): \Pest\PendingCalls\TestCall
    {
        return tags('local', ...$additionalTags);
    }
}

if (!function_exists('parallel')) {
    /**
     * Mark test as safe for parallel execution
     * 
     * @param string ...$additionalTags Additional tags to apply
     * @return \Pest\PendingCalls\TestCall
     */
    function parallel(string ...$additionalTags): \Pest\PendingCalls\TestCall
    {
        return tags('parallel', ...$additionalTags);
    }
}

if (!function_exists('sequential')) {
    /**
     * Mark test as requiring sequential execution
     * 
     * @param string ...$additionalTags Additional tags to apply
     * @return \Pest\PendingCalls\TestCall
     */
    function sequential(string ...$additionalTags): \Pest\PendingCalls\TestCall
    {
        return tags('sequential', ...$additionalTags);
    }
}

if (!function_exists('fast')) {
    /**
     * Mark test as fast
     * 
     * @param string ...$additionalTags Additional tags to apply
     * @return \Pest\PendingCalls\TestCall
     */
    function fast(string ...$additionalTags): \Pest\PendingCalls\TestCall
    {
        return tags('fast', 'parallel', ...$additionalTags);
    }
}

if (!function_exists('slow')) {
    /**
     * Mark test as slow
     * 
     * @param string ...$additionalTags Additional tags to apply
     * @return \Pest\PendingCalls\TestCall
     */
    function slow(string ...$additionalTags): \Pest\PendingCalls\TestCall
    {
        return tags('slow', 'sequential', ...$additionalTags);
    }
}

if (!function_exists('skipIf')) {
    /**
     * Skip test based on tag conditions
     * 
     * @param string $tag Tag to check
     * @param bool $condition Condition to evaluate
     * @return \Pest\PendingCalls\TestCall
     */
    function skipIf(string $tag, bool $condition = true): \Pest\PendingCalls\TestCall
    {
        if ($condition && TagManager::isExcludedByDefault($tag)) {
            $envKey = 'INCLUDE_' . strtoupper($tag) . '_TESTS';
            if (!($_ENV[$envKey] ?? false)) {
                return test()->skip("Skipping {$tag} test - not enabled in environment");
            }
        }
        
        return test();
    }
}

if (!function_exists('onlyIf')) {
    /**
     * Only run test if condition is met
     * 
     * @param bool $condition Condition to evaluate
     * @param string $reason Reason for skipping
     * @return \Pest\PendingCalls\TestCall
     */
    function onlyIf(bool $condition, string $reason = 'Condition not met'): \Pest\PendingCalls\TestCall
    {
        if (!$condition) {
            return test()->skip($reason);
        }
        
        return test();
    }
}

if (!function_exists('requiresSetup')) {
    /**
     * Skip test if required setup is not available
     * 
     * @param string $tag Tag that requires setup
     * @return \Pest\PendingCalls\TestCall
     */
    function requiresSetup(string $tag): \Pest\PendingCalls\TestCall
    {
        if (TagManager::requiresSetup($tag)) {
            $requirements = TagManager::getSetupRequirements($tag);
            foreach ($requirements as $requirement) {
                $envKey = strtoupper($requirement);
                if (!($_ENV[$envKey] ?? false)) {
                    return test()->skip("Skipping test - missing required setup: {$requirement}");
                }
            }
        }
        
        return test();
    }
}

if (!function_exists('taggedAs')) {
    /**
     * Create a describe block with specific tags
     * 
     * @param string $description Test description
     * @param string ...$tags Tags to apply to all tests in the block
     * @return \Pest\PendingCalls\DescribeCall
     */
    function taggedAs(string $description, string ...$tags): \Pest\PendingCalls\DescribeCall
    {
        $resolvedTags = array_map([TagManager::class, 'resolveAlias'], $tags);
        return describe($description)->group(...$resolvedTags);
    }
}

if (!function_exists('getTestTags')) {
    /**
     * Get all tags applied to current test context
     * 
     * @return array
     */
    function getTestTags(): array
    {
        // This would need to be implemented based on PEST's internal API
        // For now, return empty array as placeholder
        return [];
    }
}

if (!function_exists('hasTag')) {
    /**
     * Check if current test has a specific tag
     * 
     * @param string $tag Tag to check for
     * @return bool
     */
    function hasTag(string $tag): bool
    {
        $resolvedTag = TagManager::resolveAlias($tag);
        return in_array($resolvedTag, getTestTags(), true);
    }
}