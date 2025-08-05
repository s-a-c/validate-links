<?php

declare(strict_types=1);

namespace Tests\Support\Tags;

/**
 * Tag Filter for PEST Test Execution
 * 
 * Provides advanced filtering capabilities for test execution based on tags,
 * environment conditions, and execution context. Supports complex tag
 * combinations and conditional execution logic.
 */
class TagFilter
{
    private array $includeTags = [];
    private array $excludeTags = [];
    private array $environment = [];
    private bool $respectDefaults = true;

    public function __construct(array $environment = [])
    {
        $this->environment = array_merge($_ENV, $environment);
    }

    /**
     * Set tags to include in execution
     */
    public function include(array $tags): self
    {
        $this->includeTags = array_merge(
            $this->includeTags,
            array_map([TagManager::class, 'resolveAlias'], $tags)
        );
        return $this;
    }

    /**
     * Set tags to exclude from execution
     */
    public function exclude(array $tags): self
    {
        $this->excludeTags = array_merge(
            $this->excludeTags,
            array_map([TagManager::class, 'resolveAlias'], $tags)
        );
        return $this;
    }

    /**
     * Include only fast tests
     */
    public function onlyFast(): self
    {
        return $this->include(['fast'])->exclude(['slow', 'performance', 'load']);
    }

    /**
     * Include only slow tests
     */
    public function onlySlow(): self
    {
        return $this->include(['slow', 'performance', 'load'])->exclude(['fast']);
    }

    /**
     * Include only unit tests
     */
    public function onlyUnit(): self
    {
        return $this->include(['unit'])->exclude(['integration', 'e2e', 'feature']);
    }

    /**
     * Include only integration tests
     */
    public function onlyIntegration(): self
    {
        return $this->include(['integration'])->exclude(['unit', 'e2e']);
    }

    /**
     * Include only performance tests
     */
    public function onlyPerformance(): self
    {
        return $this->include(['performance', 'benchmark', 'memory', 'load']);
    }

    /**
     * Include only tests safe for parallel execution
     */
    public function onlyParallel(): self
    {
        return $this->include(['parallel'])->exclude(['sequential', 'external']);
    }

    /**
     * Include only tests that must run sequentially
     */
    public function onlySequential(): self
    {
        return $this->include(['sequential'])->exclude(['parallel']);
    }

    /**
     * Include only CI-appropriate tests
     */
    public function forCI(): self
    {
        return $this->include(['ci', 'fast', 'unit', 'integration'])
                   ->exclude(['external', 'slow', 'load', 'production']);
    }

    /**
     * Include only local development tests
     */
    public function forLocal(): self
    {
        return $this->include(['local', 'unit', 'integration', 'feature']);
    }

    /**
     * Include smoke tests only
     */
    public function smokeOnly(): self
    {
        return $this->include(['smoke', 'critical'])->exclude(['slow', 'external']);
    }

    /**
     * Disable respect for default exclusions
     */
    public function ignoreDefaults(): self
    {
        $this->respectDefaults = false;
        return $this;
    }

    /**
     * Enable respect for default exclusions
     */
    public function respectDefaults(): self
    {
        $this->respectDefaults = true;
        return $this;
    }

    /**
     * Check if a test with given tags should be executed
     */
    public function shouldExecute(array $testTags): bool
    {
        $resolvedTags = array_map([TagManager::class, 'resolveAlias'], $testTags);

        // Check explicit exclusions first
        if (!empty($this->excludeTags)) {
            foreach ($this->excludeTags as $excludeTag) {
                if (in_array($excludeTag, $resolvedTags, true)) {
                    return false;
                }
            }
        }

        // Check default exclusions if enabled
        if ($this->respectDefaults) {
            foreach ($resolvedTags as $tag) {
                if (TagManager::isExcludedByDefault($tag)) {
                    $envKey = 'INCLUDE_' . strtoupper($tag) . '_TESTS';
                    if (!($this->environment[$envKey] ?? false)) {
                        return false;
                    }
                }
            }
        }

        // Check setup requirements
        foreach ($resolvedTags as $tag) {
            if (TagManager::requiresSetup($tag)) {
                $requirements = TagManager::getSetupRequirements($tag);
                foreach ($requirements as $requirement) {
                    $envKey = strtoupper($requirement);
                    if (!($this->environment[$envKey] ?? false)) {
                        return false;
                    }
                }
            }
        }

        // Check explicit inclusions
        if (!empty($this->includeTags)) {
            foreach ($this->includeTags as $includeTag) {
                if (in_array($includeTag, $resolvedTags, true)) {
                    return true;
                }
            }
            return false; // No matching include tags
        }

        // If no explicit inclusions, include by default (unless excluded above)
        return true;
    }

    /**
     * Generate PEST command line arguments for current filter
     */
    public function toPestArgs(): array
    {
        $args = [];

        if (!empty($this->includeTags)) {
            $args[] = '--group=' . implode(',', $this->includeTags);
        }

        if (!empty($this->excludeTags)) {
            $args[] = '--exclude-group=' . implode(',', $this->excludeTags);
        }

        return $args;
    }

    /**
     * Generate PHPUnit XML filter configuration
     */
    public function toXmlFilter(): string
    {
        $xml = '<filter>';

        if (!empty($this->includeTags)) {
            $xml .= '<whitelist>';
            foreach ($this->includeTags as $tag) {
                $xml .= "<group>{$tag}</group>";
            }
            $xml .= '</whitelist>';
        }

        if (!empty($this->excludeTags)) {
            $xml .= '<blacklist>';
            foreach ($this->excludeTags as $tag) {
                $xml .= "<group>{$tag}</group>";
            }
            $xml .= '</blacklist>';
        }

        $xml .= '</filter>';

        return $xml;
    }

    /**
     * Get current filter configuration
     */
    public function getConfiguration(): array
    {
        return [
            'include_tags' => $this->includeTags,
            'exclude_tags' => $this->excludeTags,
            'respect_defaults' => $this->respectDefaults,
            'environment' => array_keys(array_filter($this->environment)),
        ];
    }

    /**
     * Create filter from environment variables
     */
    public static function fromEnvironment(): self
    {
        $filter = new self();

        // Check for PEST_TAGS environment variable
        if ($tags = $_ENV['PEST_TAGS'] ?? null) {
            $filter->include(explode(',', $tags));
        }

        // Check for PEST_EXCLUDE_TAGS environment variable
        if ($excludeTags = $_ENV['PEST_EXCLUDE_TAGS'] ?? null) {
            $filter->exclude(explode(',', $excludeTags));
        }

        // Check for common CI patterns
        if ($_ENV['CI'] ?? false) {
            $filter->forCI();
        }

        // Check for performance testing flag
        if ($_ENV['INCLUDE_PERFORMANCE_TESTS'] ?? false) {
            $filter->include(['performance', 'benchmark']);
        }

        // Check for external testing flag
        if ($_ENV['INCLUDE_EXTERNAL_TESTS'] ?? false) {
            $filter->include(['external']);
        }

        return $filter;
    }

    /**
     * Create filter for specific test suite
     */
    public static function forSuite(string $suite): self
    {
        $filter = new self();

        switch ($suite) {
            case 'unit':
                return $filter->onlyUnit();
            case 'integration':
                return $filter->onlyIntegration();
            case 'feature':
                return $filter->include(['feature', 'acceptance']);
            case 'performance':
                return $filter->onlyPerformance()->ignoreDefaults();
            case 'e2e':
                return $filter->include(['e2e'])->ignoreDefaults();
            case 'smoke':
                return $filter->smokeOnly();
            case 'parallel':
                return $filter->onlyParallel();
            case 'sequential':
                return $filter->onlySequential();
            default:
                return $filter;
        }
    }

    /**
     * Combine multiple filters with AND logic
     */
    public static function combine(self ...$filters): self
    {
        $combined = new self();

        foreach ($filters as $filter) {
            $combined->includeTags = array_merge($combined->includeTags, $filter->includeTags);
            $combined->excludeTags = array_merge($combined->excludeTags, $filter->excludeTags);
        }

        $combined->includeTags = array_unique($combined->includeTags);
        $combined->excludeTags = array_unique($combined->excludeTags);

        return $combined;
    }

    /**
     * Get statistics about filtered tests
     */
    public function getFilterStats(): array
    {
        return [
            'included_tags' => count($this->includeTags),
            'excluded_tags' => count($this->excludeTags),
            'total_available_tags' => count(TagManager::getAllTags()),
            'respect_defaults' => $this->respectDefaults,
            'environment_variables' => count(array_filter($this->environment)),
        ];
    }
}