<?php

declare(strict_types=1);

use Tests\Support\TestIsolationManager;
use Tests\Support\ContainerTestHelper;
use Tests\Support\DependencyGraphValidator;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\SecurityValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Services\Contracts\StatisticsInterface;
use App\Services\Contracts\GitHubAnchorInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Container\CircularDependencyException;

/**
 * Circular dependency detection and validation tests.
 * 
 * Tests circular dependency detection, dependency graph analysis,
 * resolution order validation, and dependency chain integrity.
 * 
 * @group unit
 * @group dependency-injection
 * @group circular-dependency
 */

describe('Circular Dependency Detection', function () {
    beforeEach(function () {
        // Initialize test isolation
        TestIsolationManager::initializeIsolation($this);
        
        // Create isolated container for testing
        $this->isolatedContainer = ContainerTestHelper::createIsolatedContainer();
    });

    afterEach(function () {
        // Clear dependency graph cache
        DependencyGraphValidator::clearCache();
        
        // Clean up test resources
        TestIsolationManager::cleanupTestResources();
    });

    describe('Current Configuration Validation', function () {
        it('has no circular dependencies in current configuration', function () {
            $circularDeps = DependencyGraphValidator::detectCircularDependencies($this->isolatedContainer);
            
            expect($circularDeps['has_circular_dependencies'])->toBeFalse()
                ->and($circularDeps['circular_dependencies'])->toBeEmpty()
                ->and($circularDeps['affected_services'])->toBeEmpty();
        });

        it('builds and validates dependency graph correctly', function () {
            $graphData = DependencyGraphValidator::buildDependencyGraph($this->isolatedContainer);
            
            expect($graphData['total_services'])->toBeGreaterThan(0)
                ->and($graphData['dependency_graph'])->toBeArray()
                ->and($graphData['reverse_dependency_graph'])->toBeArray();
            
            // Validate the graph integrity
            $validation = DependencyGraphValidator::validateDependencyGraph($this->isolatedContainer);
            
            expect($validation['is_valid'])->toBeTrue()
                ->and($validation['missing_dependencies'])->toBeEmpty()
                ->and($validation['unresolvable_dependencies'])->toBeEmpty()
                ->and($validation['validation_errors'])->toBeEmpty();
        });
    });

    describe('Artificial Circular Dependency Detection', function () {
        it('detects circular dependencies with artificial cycles', function () {
            // Create a new container for this test to avoid affecting other tests
            $testContainer = ContainerTestHelper::createIsolatedContainer();
            
            // Create artificial circular dependency: ServiceA -> ServiceB -> ServiceA
            $testContainer->bind('ServiceA', function ($app) {
                return new class($app->make('ServiceB')) {
                    public function __construct($serviceB) {}
                };
            });
            
            $testContainer->bind('ServiceB', function ($app) {
                return new class($app->make('ServiceA')) {
                    public function __construct($serviceA) {}
                };
            });
            
            // Attempt to detect circular dependencies
            $circularDeps = DependencyGraphValidator::detectCircularDependencies($testContainer);
            
            expect($circularDeps['has_circular_dependencies'])->toBeTrue()
                ->and($circularDeps['circular_dependencies'])->not->toBeEmpty();
            
            // Verify the cycle is correctly identified
            $firstCycle = $circularDeps['circular_dependencies'][0];
            expect($firstCycle['cycle'])->toContain('ServiceA')
                ->and($firstCycle['cycle'])->toContain('ServiceB')
                ->and($firstCycle['length'])->toBeGreaterThan(1);
        });

        it('detects complex circular dependency chains', function () {
            $testContainer = ContainerTestHelper::createIsolatedContainer();
            
            // Create complex circular dependency: A -> B -> C -> D -> A
            $testContainer->bind('ServiceA', function ($app) {
                return new class($app->make('ServiceB')) {
                    public function __construct($serviceB) {}
                };
            });
            
            $testContainer->bind('ServiceB', function ($app) {
                return new class($app->make('ServiceC')) {
                    public function __construct($serviceC) {}
                };
            });
            
            $testContainer->bind('ServiceC', function ($app) {
                return new class($app->make('ServiceD')) {
                    public function __construct($serviceD) {}
                };
            });
            
            $testContainer->bind('ServiceD', function ($app) {
                return new class($app->make('ServiceA')) {
                    public function __construct($serviceA) {}
                };
            });
            
            $circularDeps = DependencyGraphValidator::detectCircularDependencies($testContainer);
            
            expect($circularDeps['has_circular_dependencies'])->toBeTrue();
            
            $cycle = $circularDeps['circular_dependencies'][0];
            expect($cycle['length'])->toBe(5) // A -> B -> C -> D -> A (5 elements including return to A)
                ->and($cycle['cycle'])->toContain('ServiceA')
                ->and($cycle['cycle'])->toContain('ServiceB')
                ->and($cycle['cycle'])->toContain('ServiceC')
                ->and($cycle['cycle'])->toContain('ServiceD');
        });

        it('detects self-referencing dependencies', function () {
            $testContainer = ContainerTestHelper::createIsolatedContainer();
            
            // Create self-referencing service
            $testContainer->bind('SelfReferencingService', function ($app) {
                return new class($app->make('SelfReferencingService')) {
                    public function __construct($self) {}
                };
            });
            
            $circularDeps = DependencyGraphValidator::detectCircularDependencies($testContainer);
            
            expect($circularDeps['has_circular_dependencies'])->toBeTrue();
            
            $cycle = $circularDeps['circular_dependencies'][0];
            expect($cycle['cycle'])->toContain('SelfReferencingService')
                ->and($cycle['length'])->toBe(2); // Service -> Service (2 elements)
        });

        it('detects multiple independent circular dependencies', function () {
            $testContainer = ContainerTestHelper::createIsolatedContainer();
            
            // Create first circular dependency: A1 -> B1 -> A1
            $testContainer->bind('ServiceA1', function ($app) {
                return new class($app->make('ServiceB1')) {
                    public function __construct($serviceB) {}
                };
            });
            
            $testContainer->bind('ServiceB1', function ($app) {
                return new class($app->make('ServiceA1')) {
                    public function __construct($serviceA) {}
                };
            });
            
            // Create second circular dependency: A2 -> B2 -> A2
            $testContainer->bind('ServiceA2', function ($app) {
                return new class($app->make('ServiceB2')) {
                    public function __construct($serviceB) {}
                };
            });
            
            $testContainer->bind('ServiceB2', function ($app) {
                return new class($app->make('ServiceA2')) {
                    public function __construct($serviceA) {}
                };
            });
            
            $circularDeps = DependencyGraphValidator::detectCircularDependencies($testContainer);
            
            expect($circularDeps['has_circular_dependencies'])->toBeTrue()
                ->and($circularDeps['circular_dependencies'])->toHaveCount(2);
            
            // Verify both cycles are detected
            $cycles = collect($circularDeps['circular_dependencies']);
            $allServices = $cycles->flatMap(fn($cycle) => $cycle['cycle'])->unique();
            
            expect($allServices)->toContain('ServiceA1')
                ->and($allServices)->toContain('ServiceB1')
                ->and($allServices)->toContain('ServiceA2')
                ->and($allServices)->toContain('ServiceB2');
        });
    });

    describe('Dependency Analysis', function () {
        it('calculates dependency depth correctly', function () {
            $depthData = DependencyGraphValidator::calculateDependencyDepth();
            
            expect($depthData['dependency_depths'])->toBeArray()
                ->and($depthData['max_depth'])->toBeGreaterThanOrEqual(0)
                ->and($depthData['services_by_depth'])->toBeArray();
            
            // Services with no dependencies should have depth 0
            $servicesAtDepth0 = $depthData['services_by_depth'][0] ?? [];
            expect($servicesAtDepth0)->toContain(SecurityValidationInterface::class)
                ->and($servicesAtDepth0)->toContain(ReportingInterface::class)
                ->and($servicesAtDepth0)->toContain(StatisticsInterface::class)
                ->and($servicesAtDepth0)->toContain(GitHubAnchorInterface::class);
            
            // LinkValidationService should have depth 1 (depends on SecurityValidationInterface)
            $servicesAtDepth1 = $depthData['services_by_depth'][1] ?? [];
            expect($servicesAtDepth1)->toContain(LinkValidationInterface::class);
        });

        it('generates valid resolution order', function () {
            $resolutionData = DependencyGraphValidator::generateResolutionOrder();
            
            expect($resolutionData['resolution_order'])->toBeArray()
                ->and($resolutionData['total_services'])->toBeGreaterThan(0)
                ->and($resolutionData['resolution_levels'])->toBeArray();
            
            // Validate resolution order
            $orderValidation = DependencyGraphValidator::validateResolutionOrder($this->isolatedContainer);
            
            expect($orderValidation['is_valid_order'])->toBeTrue()
                ->and($orderValidation['resolution_errors'])->toBeEmpty()
                ->and($orderValidation['dependency_violations'])->toBeEmpty();
        });

        it('analyzes dependency complexity correctly', function () {
            // Build dependency graph first
            DependencyGraphValidator::buildDependencyGraph($this->isolatedContainer);
            
            $complexity = DependencyGraphValidator::analyzeDependencyComplexity();
            
            expect($complexity['total_services'])->toBeGreaterThan(0)
                ->and($complexity['total_dependencies'])->toBeGreaterThanOrEqual(0)
                ->and($complexity['average_dependencies_per_service'])->toBeGreaterThanOrEqual(0)
                ->and($complexity['dependency_distribution'])->toBeArray();
            
            // Most services should have 0 or 1 dependencies in our simple setup
            expect($complexity['max_dependencies'])->toBeLessThanOrEqual(2);
        });

        it('detects dependency bottlenecks', function () {
            // Build dependency graph first
            DependencyGraphValidator::buildDependencyGraph($this->isolatedContainer);
            
            $bottlenecks = DependencyGraphValidator::findDependencyBottlenecks();
            
            expect($bottlenecks['total_bottlenecks'])->toBeGreaterThanOrEqual(0)
                ->and($bottlenecks['bottlenecks'])->toBeArray();
            
            // In our current setup, SecurityValidationInterface might be a bottleneck
            // since LinkValidationService depends on it
            if ($bottlenecks['total_bottlenecks'] > 0) {
                $criticalBottleneck = $bottlenecks['most_critical_bottleneck'];
                expect($criticalBottleneck)->toHaveKey('service')
                    ->and($criticalBottleneck)->toHaveKey('dependent_count')
                    ->and($criticalBottleneck)->toHaveKey('bottleneck_severity');
            }
        });

        it('provides dependency graph statistics', function () {
            // Build dependency graph first
            DependencyGraphValidator::buildDependencyGraph($this->isolatedContainer);
            
            $stats = DependencyGraphValidator::getDependencyGraphStatistics();
            
            expect($stats['total_services'])->toBeGreaterThan(0)
                ->and($stats['total_dependencies'])->toBeGreaterThanOrEqual(0)
                ->and($stats['circular_dependencies'])->toBe(0)
                ->and($stats['max_dependency_depth'])->toBeGreaterThanOrEqual(0)
                ->and($stats['validation_errors'])->toBe(0)
                ->and($stats['graph_built'])->toBeTrue();
        });
    });

    describe('Runtime Prevention', function () {
        it('prevents circular dependencies at runtime', function () {
            $testContainer = ContainerTestHelper::createIsolatedContainer();
            
            // Create circular dependency that should fail at runtime
            $testContainer->bind('RuntimeCircularA', function ($app) {
                return new class($app->make('RuntimeCircularB')) {
                    public function __construct($serviceB) {}
                };
            });
            
            $testContainer->bind('RuntimeCircularB', function ($app) {
                return new class($app->make('RuntimeCircularA')) {
                    public function __construct($serviceA) {}
                };
            });
            
            // Attempting to resolve should throw an exception
            expect(fn() => $testContainer->make('RuntimeCircularA'))->toThrow(Exception::class);
        });
    });

    describe('Dependency Chain Validation', function () {
        it('validates dependency chains with valid chains', function () {
            // Build dependency graph
            DependencyGraphValidator::buildDependencyGraph($this->isolatedContainer);
            
            // Validate the dependency graph
            $validation = DependencyGraphValidator::validateDependencyGraph($this->isolatedContainer);
            
            expect($validation['is_valid'])->toBeTrue()
                ->and($validation['missing_dependencies'])->toBeEmpty()
                ->and($validation['unresolvable_dependencies'])->toBeEmpty()
                ->and($validation['orphaned_services'])->toBeArray(); // Some services might be orphaned (not depended upon)
        });
    });

    describe('Cache Management', function () {
        it('manages dependency graph cache correctly', function () {
            // Build dependency graph
            $graphData1 = DependencyGraphValidator::buildDependencyGraph($this->isolatedContainer);
            
            // Get statistics (should use cached data)
            $stats1 = DependencyGraphValidator::getDependencyGraphStatistics();
            expect($stats1['graph_built'])->toBeTrue();
            
            // Clear cache
            DependencyGraphValidator::clearCache();
            
            // Statistics should show graph not built
            $stats2 = DependencyGraphValidator::getDependencyGraphStatistics();
            expect($stats2['graph_built'])->toBeFalse();
            
            // Rebuild graph
            $graphData2 = DependencyGraphValidator::buildDependencyGraph($this->isolatedContainer);
            
            // Should have same structure as before
            expect($graphData2['total_services'])->toBe($graphData1['total_services'])
                ->and($graphData2['total_dependencies'])->toBe($graphData1['total_dependencies']);
        });
    });

    describe('Error Handling', function () {
        it('handles errors in dependency graph validation gracefully', function () {
            $testContainer = ContainerTestHelper::createIsolatedContainer();
            
            // Create service with invalid dependency
            $testContainer->bind('ServiceWithInvalidDep', function ($app) {
                return new class($app->make('NonExistentService')) {
                    public function __construct($invalidDep) {}
                };
            });
            
            // Build dependency graph (should handle errors gracefully)
            $graphData = DependencyGraphValidator::buildDependencyGraph($testContainer);
            
            expect($graphData)->toBeArray()
                ->and($graphData['total_services'])->toBeGreaterThan(0);
            
            // Validate dependency graph (should detect missing dependencies)
            $validation = DependencyGraphValidator::validateDependencyGraph($testContainer);
            
            expect($validation['is_valid'])->toBeFalse()
                ->and($validation['missing_dependencies'])->not->toBeEmpty();
        });
    });
});