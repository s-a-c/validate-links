<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Contracts\Foundation\Application;
use Exception;
use Throwable;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionException;

/**
 * Dependency graph validator for circular dependency detection and analysis.
 * 
 * Provides comprehensive dependency graph analysis, circular dependency detection,
 * dependency chain validation, and service resolution order optimization.
 * 
 * @author StandAloneComplex
 * @since 1.0.0
 */
final class DependencyGraphValidator
{
    /** @var array<string, array<string>> */
    private static array $dependencyGraph = [];
    
    /** @var array<string, array<string>> */
    private static array $reverseDependencyGraph = [];
    
    /** @var array<string> */
    private static array $circularDependencies = [];
    
    /** @var array<string, int> */
    private static array $dependencyDepth = [];
    
    /** @var array<string> */
    private static array $resolutionOrder = [];
    
    /** @var array<string> */
    private static array $validationErrors = [];

    /**
     * Build dependency graph from container bindings.
     */
    public static function buildDependencyGraph(Application $app): array
    {
        self::$dependencyGraph = [];
        self::$reverseDependencyGraph = [];
        self::$validationErrors = [];
        
        try {
            $bindings = self::getContainerBindings($app);
            
            foreach ($bindings as $abstract => $binding) {
                $dependencies = self::extractDependencies($app, $abstract, $binding);
                self::$dependencyGraph[$abstract] = $dependencies;
                
                // Build reverse dependency graph
                foreach ($dependencies as $dependency) {
                    if (!isset(self::$reverseDependencyGraph[$dependency])) {
                        self::$reverseDependencyGraph[$dependency] = [];
                    }
                    self::$reverseDependencyGraph[$dependency][] = $abstract;
                }
            }
            
            return [
                'dependency_graph' => self::$dependencyGraph,
                'reverse_dependency_graph' => self::$reverseDependencyGraph,
                'total_services' => count(self::$dependencyGraph),
                'total_dependencies' => array_sum(array_map('count', self::$dependencyGraph)),
            ];
            
        } catch (Throwable $e) {
            self::$validationErrors[] = "Failed to build dependency graph: " . $e->getMessage();
            throw new Exception("Dependency graph building failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Detect circular dependencies in the dependency graph.
     */
    public static function detectCircularDependencies(Application $app): array
    {
        if (empty(self::$dependencyGraph)) {
            self::buildDependencyGraph($app);
        }
        
        self::$circularDependencies = [];
        $visited = [];
        $recursionStack = [];
        
        foreach (array_keys(self::$dependencyGraph) as $service) {
            if (!isset($visited[$service])) {
                self::detectCircularDependenciesRecursive($service, $visited, $recursionStack);
            }
        }
        
        return [
            'has_circular_dependencies' => !empty(self::$circularDependencies),
            'circular_dependencies' => self::$circularDependencies,
            'affected_services' => array_unique(array_merge(...array_map(
                fn($cycle) => $cycle['cycle'],
                self::$circularDependencies
            ))),
        ];
    }

    /**
     * Validate dependency graph integrity.
     */
    public static function validateDependencyGraph(Application $app): array
    {
        $results = [
            'is_valid' => true,
            'missing_dependencies' => [],
            'unresolvable_dependencies' => [],
            'orphaned_services' => [],
            'dependency_conflicts' => [],
            'validation_errors' => [],
        ];
        
        try {
            if (empty(self::$dependencyGraph)) {
                self::buildDependencyGraph($app);
            }
            
            // Check for missing dependencies
            $missingDeps = self::findMissingDependencies($app);
            $results['missing_dependencies'] = $missingDeps;
            
            // Check for unresolvable dependencies
            $unresolvableDeps = self::findUnresolvableDependencies($app);
            $results['unresolvable_dependencies'] = $unresolvableDeps;
            
            // Check for orphaned services
            $orphanedServices = self::findOrphanedServices();
            $results['orphaned_services'] = $orphanedServices;
            
            // Check for dependency conflicts
            $conflicts = self::findDependencyConflicts($app);
            $results['dependency_conflicts'] = $conflicts;
            
            // Overall validation status
            $results['is_valid'] = empty($missingDeps) && 
                                  empty($unresolvableDeps) && 
                                  empty($conflicts) &&
                                  empty(self::$circularDependencies);
            
            $results['validation_errors'] = self::$validationErrors;
            
        } catch (Throwable $e) {
            $results['is_valid'] = false;
            $results['validation_errors'][] = $e->getMessage();
        }
        
        return $results;
    }

    /**
     * Calculate dependency depth for each service.
     */
    public static function calculateDependencyDepth(): array
    {
        self::$dependencyDepth = [];
        
        foreach (array_keys(self::$dependencyGraph) as $service) {
            self::$dependencyDepth[$service] = self::calculateServiceDepth($service, []);
        }
        
        return [
            'dependency_depths' => self::$dependencyDepth,
            'max_depth' => !empty(self::$dependencyDepth) ? max(self::$dependencyDepth) : 0,
            'services_by_depth' => self::groupServicesByDepth(),
        ];
    }

    /**
     * Generate optimal resolution order.
     */
    public static function generateResolutionOrder(): array
    {
        if (empty(self::$dependencyDepth)) {
            self::calculateDependencyDepth();
        }
        
        // Sort services by dependency depth (services with fewer dependencies first)
        $sortedServices = self::$dependencyDepth;
        asort($sortedServices);
        
        self::$resolutionOrder = array_keys($sortedServices);
        
        return [
            'resolution_order' => self::$resolutionOrder,
            'total_services' => count(self::$resolutionOrder),
            'resolution_levels' => self::groupServicesByDepth(),
        ];
    }

    /**
     * Analyze dependency complexity.
     */
    public static function analyzeDependencyComplexity(): array
    {
        if (empty(self::$dependencyGraph)) {
            throw new Exception("Dependency graph not built. Call buildDependencyGraph() first.");
        }
        
        $complexity = [
            'total_services' => count(self::$dependencyGraph),
            'total_dependencies' => 0,
            'average_dependencies_per_service' => 0,
            'max_dependencies' => 0,
            'services_with_no_dependencies' => 0,
            'services_with_many_dependencies' => [],
            'dependency_distribution' => [],
        ];
        
        $dependencyCounts = [];
        
        foreach (self::$dependencyGraph as $service => $dependencies) {
            $depCount = count($dependencies);
            $complexity['total_dependencies'] += $depCount;
            $dependencyCounts[] = $depCount;
            
            if ($depCount === 0) {
                $complexity['services_with_no_dependencies']++;
            }
            
            if ($depCount > 5) { // Threshold for "many dependencies"
                $complexity['services_with_many_dependencies'][] = [
                    'service' => $service,
                    'dependency_count' => $depCount,
                    'dependencies' => $dependencies,
                ];
            }
            
            // Build distribution
            if (!isset($complexity['dependency_distribution'][$depCount])) {
                $complexity['dependency_distribution'][$depCount] = 0;
            }
            $complexity['dependency_distribution'][$depCount]++;
        }
        
        $complexity['average_dependencies_per_service'] = $complexity['total_services'] > 0 
            ? $complexity['total_dependencies'] / $complexity['total_services'] 
            : 0;
        $complexity['max_dependencies'] = !empty($dependencyCounts) ? max($dependencyCounts) : 0;
        
        return $complexity;
    }

    /**
     * Find dependency bottlenecks.
     */
    public static function findDependencyBottlenecks(): array
    {
        if (empty(self::$reverseDependencyGraph)) {
            throw new Exception("Reverse dependency graph not built. Call buildDependencyGraph() first.");
        }
        
        $bottlenecks = [];
        
        foreach (self::$reverseDependencyGraph as $service => $dependents) {
            $dependentCount = count($dependents);
            
            if ($dependentCount > 3) { // Threshold for bottleneck
                $bottlenecks[] = [
                    'service' => $service,
                    'dependent_count' => $dependentCount,
                    'dependents' => $dependents,
                    'bottleneck_severity' => self::calculateBottleneckSeverity($dependentCount),
                ];
            }
        }
        
        // Sort by severity
        usort($bottlenecks, fn($a, $b) => $b['dependent_count'] <=> $a['dependent_count']);
        
        return [
            'bottlenecks' => $bottlenecks,
            'total_bottlenecks' => count($bottlenecks),
            'most_critical_bottleneck' => $bottlenecks[0] ?? null,
        ];
    }

    /**
     * Validate service resolution order.
     */
    public static function validateResolutionOrder(Application $app): array
    {
        if (empty(self::$resolutionOrder)) {
            self::generateResolutionOrder();
        }
        
        $results = [
            'is_valid_order' => true,
            'resolution_errors' => [],
            'dependency_violations' => [],
        ];
        
        $resolved = [];
        
        foreach (self::$resolutionOrder as $service) {
            try {
                // Check if all dependencies are already resolved
                $dependencies = self::$dependencyGraph[$service] ?? [];
                $unresolvedDeps = array_diff($dependencies, $resolved);
                
                if (!empty($unresolvedDeps)) {
                    $results['dependency_violations'][] = [
                        'service' => $service,
                        'unresolved_dependencies' => $unresolvedDeps,
                    ];
                    $results['is_valid_order'] = false;
                }
                
                // Try to resolve the service
                $app->make($service);
                $resolved[] = $service;
                
            } catch (Throwable $e) {
                $results['resolution_errors'][] = [
                    'service' => $service,
                    'error' => $e->getMessage(),
                ];
                $results['is_valid_order'] = false;
            }
        }
        
        return $results;
    }

    /**
     * Get dependency graph statistics.
     */
    public static function getDependencyGraphStatistics(): array
    {
        return [
            'total_services' => count(self::$dependencyGraph),
            'total_dependencies' => array_sum(array_map('count', self::$dependencyGraph)),
            'circular_dependencies' => count(self::$circularDependencies),
            'max_dependency_depth' => !empty(self::$dependencyDepth) ? max(self::$dependencyDepth) : 0,
            'validation_errors' => count(self::$validationErrors),
            'graph_built' => !empty(self::$dependencyGraph),
        ];
    }

    /**
     * Clear all cached data.
     */
    public static function clearCache(): void
    {
        self::$dependencyGraph = [];
        self::$reverseDependencyGraph = [];
        self::$circularDependencies = [];
        self::$dependencyDepth = [];
        self::$resolutionOrder = [];
        self::$validationErrors = [];
    }

    /**
     * Extract dependencies from a service binding.
     */
    private static function extractDependencies(Application $app, string $abstract, array $binding): array
    {
        $dependencies = [];
        
        try {
            // Get the concrete implementation
            $concrete = $binding['concrete'] ?? $abstract;
            
            if (is_string($concrete) && class_exists($concrete)) {
                $dependencies = self::extractClassDependencies($concrete);
            } elseif (is_callable($concrete)) {
                // For closures, we can't easily extract dependencies
                // Try to resolve and see what happens
                try {
                    $app->make($abstract);
                } catch (Throwable $e) {
                    // Parse error message for dependency hints
                    $dependencies = self::parseDependencyFromError($e->getMessage());
                }
            }
            
        } catch (Throwable $e) {
            self::$validationErrors[] = "Failed to extract dependencies for {$abstract}: " . $e->getMessage();
        }
        
        return $dependencies;
    }

    /**
     * Extract dependencies from a class constructor.
     */
    private static function extractClassDependencies(string $className): array
    {
        $dependencies = [];
        
        try {
            $reflection = new ReflectionClass($className);
            $constructor = $reflection->getConstructor();
            
            if ($constructor) {
                $parameters = $constructor->getParameters();
                
                foreach ($parameters as $parameter) {
                    $type = $parameter->getType();
                    
                    if ($type && !$type->isBuiltin()) {
                        $typeName = $type->getName();
                        if (interface_exists($typeName) || class_exists($typeName)) {
                            $dependencies[] = $typeName;
                        }
                    }
                }
            }
            
        } catch (ReflectionException $e) {
            self::$validationErrors[] = "Failed to reflect class {$className}: " . $e->getMessage();
        }
        
        return $dependencies;
    }

    /**
     * Parse dependency information from error messages.
     */
    private static function parseDependencyFromError(string $errorMessage): array
    {
        $dependencies = [];
        
        // Look for common dependency injection error patterns
        if (preg_match('/Target \[([^\]]+)\] is not instantiable/', $errorMessage, $matches)) {
            $dependencies[] = $matches[1];
        }
        
        if (preg_match('/Unresolvable dependency resolving \[([^\]]+)\]/', $errorMessage, $matches)) {
            $dependencies[] = $matches[1];
        }
        
        return $dependencies;
    }

    /**
     * Detect circular dependencies recursively.
     */
    private static function detectCircularDependenciesRecursive(string $service, array &$visited, array &$recursionStack): void
    {
        $visited[$service] = true;
        $recursionStack[$service] = true;
        
        $dependencies = self::$dependencyGraph[$service] ?? [];
        
        foreach ($dependencies as $dependency) {
            if (!isset($visited[$dependency])) {
                self::detectCircularDependenciesRecursive($dependency, $visited, $recursionStack);
            } elseif (isset($recursionStack[$dependency])) {
                // Found circular dependency
                $cycle = self::extractCycle($recursionStack, $dependency, $service);
                self::$circularDependencies[] = [
                    'cycle' => $cycle,
                    'length' => count($cycle),
                    'entry_point' => $dependency,
                ];
            }
        }
        
        unset($recursionStack[$service]);
    }

    /**
     * Extract circular dependency cycle.
     */
    private static function extractCycle(array $recursionStack, string $start, string $end): array
    {
        $cycle = [];
        $found = false;
        
        foreach (array_keys($recursionStack) as $service) {
            if ($service === $start) {
                $found = true;
            }
            
            if ($found) {
                $cycle[] = $service;
            }
            
            if ($service === $end) {
                break;
            }
        }
        
        $cycle[] = $start; // Complete the cycle
        
        return $cycle;
    }

    /**
     * Calculate dependency depth for a service.
     */
    private static function calculateServiceDepth(string $service, array $visited): int
    {
        if (in_array($service, $visited, true)) {
            return PHP_INT_MAX; // Circular dependency
        }
        
        $visited[] = $service;
        $dependencies = self::$dependencyGraph[$service] ?? [];
        
        if (empty($dependencies)) {
            return 0; // No dependencies
        }
        
        $maxDepth = 0;
        foreach ($dependencies as $dependency) {
            $depth = self::calculateServiceDepth($dependency, $visited);
            $maxDepth = max($maxDepth, $depth);
        }
        
        return $maxDepth + 1;
    }

    /**
     * Group services by dependency depth.
     */
    private static function groupServicesByDepth(): array
    {
        $grouped = [];
        
        foreach (self::$dependencyDepth as $service => $depth) {
            if (!isset($grouped[$depth])) {
                $grouped[$depth] = [];
            }
            $grouped[$depth][] = $service;
        }
        
        ksort($grouped);
        
        return $grouped;
    }

    /**
     * Find missing dependencies.
     */
    private static function findMissingDependencies(Application $app): array
    {
        $missing = [];
        
        foreach (self::$dependencyGraph as $service => $dependencies) {
            foreach ($dependencies as $dependency) {
                if (!$app->bound($dependency) && !class_exists($dependency)) {
                    $missing[] = [
                        'service' => $service,
                        'missing_dependency' => $dependency,
                    ];
                }
            }
        }
        
        return $missing;
    }

    /**
     * Find unresolvable dependencies.
     */
    private static function findUnresolvableDependencies(Application $app): array
    {
        $unresolvable = [];
        
        foreach (self::$dependencyGraph as $service => $dependencies) {
            foreach ($dependencies as $dependency) {
                try {
                    $app->make($dependency);
                } catch (Throwable $e) {
                    $unresolvable[] = [
                        'service' => $service,
                        'unresolvable_dependency' => $dependency,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }
        
        return $unresolvable;
    }

    /**
     * Find orphaned services (services that nothing depends on).
     */
    private static function findOrphanedServices(): array
    {
        $allServices = array_keys(self::$dependencyGraph);
        $referencedServices = array_unique(array_merge(...array_values(self::$dependencyGraph)));
        
        return array_diff($allServices, $referencedServices);
    }

    /**
     * Find dependency conflicts.
     */
    private static function findDependencyConflicts(Application $app): array
    {
        $conflicts = [];
        
        // This is a placeholder for more sophisticated conflict detection
        // Could include version conflicts, interface implementation conflicts, etc.
        
        return $conflicts;
    }

    /**
     * Calculate bottleneck severity.
     */
    private static function calculateBottleneckSeverity(int $dependentCount): string
    {
        return match (true) {
            $dependentCount >= 10 => 'critical',
            $dependentCount >= 7 => 'high',
            $dependentCount >= 5 => 'medium',
            default => 'low',
        };
    }

    /**
     * Get container bindings using reflection.
     */
    private static function getContainerBindings(Application $app): array
    {
        try {
            $reflection = new ReflectionClass($app);
            
            if ($reflection->hasProperty('bindings')) {
                $bindingsProperty = $reflection->getProperty('bindings');
                $bindingsProperty->setAccessible(true);
                return $bindingsProperty->getValue($app) ?? [];
            }
        } catch (ReflectionException $e) {
            self::$validationErrors[] = "Failed to get container bindings: " . $e->getMessage();
        }
        
        return [];
    }
}