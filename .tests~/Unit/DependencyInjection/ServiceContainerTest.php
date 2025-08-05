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
use App\Services\LinkValidationService;
use App\Services\SecurityValidationService;
use App\Services\ReportingService;
use App\Services\StatisticsService;
use App\Services\GitHubAnchorService;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;

/**
 * Service container dependency injection tests.
 * 
 * Tests service container functionality, binding resolution,
 * singleton behavior, and container state management.
 */

describe('Service Container', function () {
    beforeEach(function () {
        // Initialize test isolation
        TestIsolationManager::initializeIsolation($this);
        
        // Create isolated container for testing
        $this->isolatedContainer = ContainerTestHelper::createIsolatedContainer();
    });

    afterEach(function () {
        // Clean up test resources
        TestIsolationManager::cleanupTestResources();
    });

    describe('Service Binding', function () {
        it('binds all core services to their interfaces', function () {
            $validationResults = ContainerTestHelper::validateInterfaceBindings($this->isolatedContainer);
            
            expect($validationResults['missing_bindings'])->toBeEmpty()
                ->and($validationResults['incorrect_bindings'])->toBeEmpty()
                ->and($validationResults['correct_bindings'])->toHaveCount(5);
            
            // Verify each specific binding
            $correctBindings = collect($validationResults['correct_bindings'])
                ->keyBy('interface');
            
            expect($correctBindings->has(SecurityValidationInterface::class))->toBeTrue()
                ->and($correctBindings->has(ReportingInterface::class))->toBeTrue()
                ->and($correctBindings->has(StatisticsInterface::class))->toBeTrue()
                ->and($correctBindings->has(GitHubAnchorInterface::class))->toBeTrue()
                ->and($correctBindings->has(LinkValidationInterface::class))->toBeTrue();
        });

        it('validates service binding configuration', function () {
            $validationResults = ContainerTestHelper::validateContainerBindings($this->isolatedContainer);
            
            expect($validationResults['invalid_bindings'])->toBeEmpty()
                ->and($validationResults['missing_dependencies'])->toBeEmpty()
                ->and($validationResults['binding_conflicts'])->toBeEmpty();
            
            // Verify all expected bindings are valid
            expect($validationResults['valid_bindings'])->toHaveCount(5);
            
            foreach ($validationResults['valid_bindings'] as $binding) {
                expect($binding['matches'])->toBeTrue()
                    ->and($binding['is_singleton'])->toBeTrue();
            }
        });

        it('detects container binding conflicts', function () {
            // Add conflicting binding
            $this->isolatedContainer->bind(SecurityValidationInterface::class, \stdClass::class);
            
            $validationResults = ContainerTestHelper::validateContainerBindings($this->isolatedContainer);
            
            expect($validationResults['binding_conflicts'])->not->toBeEmpty();
            
            // Reset and verify clean state
            ContainerTestHelper::resetContainerState($this->isolatedContainer);
            
            $cleanResults = ContainerTestHelper::validateContainerBindings($this->isolatedContainer);
            expect($cleanResults['binding_conflicts'])->toBeEmpty();
        });
    });

    describe('Service Resolution', function () {
        it('resolves all registered services successfully', function () {
            $resolutionResults = ContainerTestHelper::testServiceResolution($this->isolatedContainer);
            
            expect($resolutionResults['failed_resolutions'])->toBeEmpty()
                ->and($resolutionResults['successful_resolutions'])->toHaveCount(5);
            
            // Verify resolution times are reasonable (< 100ms)
            foreach ($resolutionResults['resolution_times'] as $interface => $time) {
                expect($time)->toBeLessThan(0.1, "Service {$interface} took too long to resolve: {$time}s");
            }
        });

        it('resolves services with mock dependencies', function () {
            $mocks = ContainerTestHelper::createMockServiceBindings($this->isolatedContainer, [
                SecurityValidationInterface::class => [
                    'name' => 'security_mock',
                    'setup' => function ($mock) {
                        $mock->shouldReceive('validateUrl')->andReturn(true);
                        $mock->shouldReceive('validatePath')->andReturn(true);
                    },
                ],
            ]);
            
            expect($mocks)->toHaveKey(SecurityValidationInterface::class);
            
            // Resolve LinkValidationService which depends on SecurityValidationInterface
            $linkService = $this->isolatedContainer->make(LinkValidationInterface::class);
            
            expect($linkService)->toBeInstanceOf(LinkValidationService::class);
            
            // Verify mock is used
            $reflection = new \ReflectionClass($linkService);
            $securityProperty = $reflection->getProperty('security');
            $securityProperty->setAccessible(true);
            $injectedSecurity = $securityProperty->getValue($linkService);
            
            expect($injectedSecurity)->toBe($mocks[SecurityValidationInterface::class]);
        });

        it('handles dependency injection with LinkValidationService', function () {
            $linkValidationService = $this->isolatedContainer->make(LinkValidationInterface::class);
            
            expect($linkValidationService)->toBeInstanceOf(LinkValidationService::class);
            
            // Use reflection to verify SecurityValidationInterface is injected
            $reflection = new \ReflectionClass($linkValidationService);
            $securityProperty = $reflection->getProperty('security');
            $securityProperty->setAccessible(true);
            $securityService = $securityProperty->getValue($linkValidationService);
            
            expect($securityService)->toBeInstanceOf(SecurityValidationInterface::class)
                ->and($securityService)->toBeInstanceOf(SecurityValidationService::class);
        });
    });

    describe('Singleton Behavior', function () {
        it('configures services as singletons', function () {
            $lifecycleResults = ContainerTestHelper::testServiceLifecycle($this->isolatedContainer);
            
            expect($lifecycleResults['lifecycle_errors'])->toBeEmpty()
                ->and($lifecycleResults['singleton_services'])->toHaveCount(5)
                ->and($lifecycleResults['transient_services'])->toBeEmpty();
            
            // Verify singleton behavior by resolving twice
            $securityService1 = $this->isolatedContainer->make(SecurityValidationInterface::class);
            $securityService2 = $this->isolatedContainer->make(SecurityValidationInterface::class);
            
            expect($securityService1)->toBe($securityService2);
        });

        it('simulates thread safety for singleton services', function () {
            // Simulate concurrent access by resolving services rapidly
            $results = [];
            
            for ($i = 0; $i < 100; $i++) {
                $service1 = $this->isolatedContainer->make(SecurityValidationInterface::class);
                $service2 = $this->isolatedContainer->make(SecurityValidationInterface::class);
                
                $results[] = $service1 === $service2;
            }
            
            // All resolutions should return the same singleton instance
            expect(array_unique($results))->toBe([true]);
        });
    });

    describe('Container State Management', function () {
        it('maintains container state isolation between tests', function () {
            // Modify container state
            $this->isolatedContainer->bind('test.service', fn() => new \stdClass());
            
            // Reset container state
            ContainerTestHelper::resetContainerState($this->isolatedContainer);
            
            // Verify state is clean
            $isolationResults = TestIsolationManager::validateTestIsolation($this->isolatedContainer);
            
            expect($isolationResults['container_state_clean'])->toBeTrue()
                ->and($isolationResults['no_singleton_leakage'])->toBeTrue()
                ->and($isolationResults['no_binding_conflicts'])->toBeTrue();
        });

        it('maintains state consistency after multiple operations', function () {
            // Perform multiple container operations
            $service1 = $this->isolatedContainer->make(SecurityValidationInterface::class);
            $this->isolatedContainer->bind('temp.service', \stdClass::class);
            $service2 = $this->isolatedContainer->make(ReportingInterface::class);
            $this->isolatedContainer->forgetInstance('temp.service');
            $service3 = $this->isolatedContainer->make(StatisticsInterface::class);
            
            // Validate container state is still consistent
            $validationResults = ContainerTestHelper::validateContainerBindings($this->isolatedContainer);
            
            expect($validationResults['invalid_bindings'])->toBeEmpty()
                ->and($validationResults['missing_dependencies'])->toBeEmpty();
            
            // Verify services are still singletons
            $service1Again = $this->isolatedContainer->make(SecurityValidationInterface::class);
            $service2Again = $this->isolatedContainer->make(ReportingInterface::class);
            $service3Again = $this->isolatedContainer->make(StatisticsInterface::class);
            
            expect($service1)->toBe($service1Again)
                ->and($service2)->toBe($service2Again)
                ->and($service3)->toBe($service3Again);
        });
    });

    describe('Performance and Monitoring', function () {
        it('performs well under load', function () {
            $performanceResults = ContainerTestHelper::testContainerPerformance($this->isolatedContainer, 100);
            
            expect($performanceResults['average_resolution_time'])->toBeLessThan(0.01) // 10ms average
                ->and($performanceResults['peak_memory'])->toBeLessThan(10 * 1024 * 1024) // 10MB peak
                ->and($performanceResults['performance_issues'])->toBeEmpty();
        });

        it('provides container statistics and monitoring', function () {
            $stats = ContainerTestHelper::getContainerStatistics($this->isolatedContainer);
            
            expect($stats['total_bindings'])->toBeGreaterThan(0)
                ->and($stats['singleton_bindings'])->toBe(5)
                ->and($stats['container_errors'])->toBe(0)
                ->and($stats['memory_usage'])->toBeGreaterThan(0);
        });

        it('manages memory efficiently with cleanup', function () {
            $initialMemory = memory_get_usage(true);
            
            // Resolve all services multiple times
            for ($i = 0; $i < 10; $i++) {
                $this->isolatedContainer->make(SecurityValidationInterface::class);
                $this->isolatedContainer->make(ReportingInterface::class);
                $this->isolatedContainer->make(StatisticsInterface::class);
                $this->isolatedContainer->make(GitHubAnchorInterface::class);
                $this->isolatedContainer->make(LinkValidationInterface::class);
            }
            
            $afterResolutionMemory = memory_get_usage(true);
            
            // Reset container
            ContainerTestHelper::resetContainerState($this->isolatedContainer);
            
            $afterResetMemory = memory_get_usage(true);
            
            // Memory should not grow significantly due to singleton behavior
            $memoryGrowth = $afterResolutionMemory - $initialMemory;
            expect($memoryGrowth)->toBeLessThan(5 * 1024 * 1024); // Less than 5MB growth
            
            // Memory should be cleaned up after reset
            $memoryAfterReset = $afterResetMemory - $initialMemory;
            expect($memoryAfterReset)->toBeLessThan($memoryGrowth);
        });
    });

    describe('Error Handling', function () {
        it('handles container errors and recovers gracefully', function () {
            // Create invalid binding
            $this->isolatedContainer->bind('invalid.service', function () {
                throw new Exception('Service creation failed');
            });
            
            // Attempt to resolve invalid service
            expect(fn() => $this->isolatedContainer->make('invalid.service'))
                ->toThrow(Exception::class, 'Service creation failed');
            
            // Verify container is still functional for other services
            $securityService = $this->isolatedContainer->make(SecurityValidationInterface::class);
            expect($securityService)->toBeInstanceOf(SecurityValidationService::class);
        });
    });
});