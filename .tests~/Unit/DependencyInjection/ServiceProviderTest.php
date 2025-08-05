<?php

declare(strict_types=1);

use Tests\Support\TestIsolationManager;
use Tests\Support\ContainerTestHelper;
use App\Providers\AppServiceProvider;
use App\Providers\ValidateLinksServiceProvider;
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
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;


/**
 * Service provider dependency injection tests.
 * 
 * Tests service provider registration, boot methods, service binding,
 * provider dependencies, and provider lifecycle management.
 * 
 * @group unit
 * @group dependency-injection
 * @group service-providers
 */

describe('Service Provider', function () {
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

    describe('ValidateLinksServiceProvider Registration', function () {
        it('can register ValidateLinksServiceProvider successfully', function () {
            $provider = new ValidateLinksServiceProvider($this->isolatedContainer);
            
            // Test that provider can be instantiated
            expect($provider)->toBeInstanceOf(ValidateLinksServiceProvider::class)
                ->and($provider)->toBeInstanceOf(ServiceProvider::class);
            
            // Test register method
            $provider->register();
            
            // Verify all services are bound
            expect($this->isolatedContainer->bound(SecurityValidationInterface::class))->toBeTrue()
                ->and($this->isolatedContainer->bound(ReportingInterface::class))->toBeTrue()
                ->and($this->isolatedContainer->bound(StatisticsInterface::class))->toBeTrue()
                ->and($this->isolatedContainer->bound(GitHubAnchorInterface::class))->toBeTrue()
                ->and($this->isolatedContainer->bound(LinkValidationInterface::class))->toBeTrue();
        });

        it('provides correct services list', function () {
            $provider = new ValidateLinksServiceProvider($this->isolatedContainer);
            $providedServices = $provider->provides();
            
            expect($providedServices)->toBeArray()
                ->and($providedServices)->toContain(SecurityValidationInterface::class)
                ->and($providedServices)->toContain(ReportingInterface::class)
                ->and($providedServices)->toContain(StatisticsInterface::class)
                ->and($providedServices)->toContain(GitHubAnchorInterface::class)
                ->and($providedServices)->toContain(LinkValidationInterface::class)
                ->and($providedServices)->toHaveCount(5);
        });

        it('boots without throwing exceptions', function () {
            $provider = new ValidateLinksServiceProvider($this->isolatedContainer);
            
            // Register services first
            $provider->register();
            
            // Boot should not throw any exceptions
            $provider->boot();
            
            // Verify services are still accessible after boot
            expect($this->isolatedContainer->bound(SecurityValidationInterface::class))->toBeTrue()
                ->and($this->isolatedContainer->bound(LinkValidationInterface::class))->toBeTrue();
        });
    });

    describe('Service Binding and Dependencies', function () {
        it('resolves services in correct order with proper dependencies', function () {
            $provider = new ValidateLinksServiceProvider($this->isolatedContainer);
            $provider->register();
            
            // Test that services with no dependencies can be resolved first
            $securityService = $this->isolatedContainer->make(SecurityValidationInterface::class);
            expect($securityService)->toBeInstanceOf(SecurityValidationService::class);
            
            $reportingService = $this->isolatedContainer->make(ReportingInterface::class);
            expect($reportingService)->toBeInstanceOf(ReportingService::class);
            
            $statisticsService = $this->isolatedContainer->make(StatisticsInterface::class);
            expect($statisticsService)->toBeInstanceOf(StatisticsService::class);
            
            $githubService = $this->isolatedContainer->make(GitHubAnchorInterface::class);
            expect($githubService)->toBeInstanceOf(GitHubAnchorService::class);
            
            // Test that LinkValidationService can be resolved with its dependency
            $linkService = $this->isolatedContainer->make(LinkValidationInterface::class);
            expect($linkService)->toBeInstanceOf(LinkValidationService::class);
            
            // Verify dependency injection worked correctly
            $reflection = new ReflectionClass($linkService);
            $securityProperty = $reflection->getProperty('security');
            $securityProperty->setAccessible(true);
            $injectedSecurity = $securityProperty->getValue($linkService);
            
            expect($injectedSecurity)->toBeInstanceOf(SecurityValidationInterface::class)
                ->and($injectedSecurity)->toBe($securityService); // Should be the same singleton instance
        });

        it('maintains singleton binding behavior', function () {
            $provider = new ValidateLinksServiceProvider($this->isolatedContainer);
            $provider->register();
            
            // Resolve services multiple times
            $security1 = $this->isolatedContainer->make(SecurityValidationInterface::class);
            $security2 = $this->isolatedContainer->make(SecurityValidationInterface::class);
            
            $reporting1 = $this->isolatedContainer->make(ReportingInterface::class);
            $reporting2 = $this->isolatedContainer->make(ReportingInterface::class);
            
            $link1 = $this->isolatedContainer->make(LinkValidationInterface::class);
            $link2 = $this->isolatedContainer->make(LinkValidationInterface::class);
            
            // All should be the same instances (singletons)
            expect($security1)->toBe($security2)
                ->and($reporting1)->toBe($reporting2)
                ->and($link1)->toBe($link2);
        });

        it('handles registration order correctly', function () {
            $freshContainer = new \Illuminate\Container\Container();
            
            // Register LinkValidationService first (before its dependency)
            $freshContainer->singleton(LinkValidationInterface::class, function ($app) {
                return new LinkValidationService(
                    $app->make(SecurityValidationInterface::class)
                );
            });
            
            // Register SecurityValidationService after
            $freshContainer->singleton(SecurityValidationInterface::class, SecurityValidationService::class);
            
            // Should still work because Laravel resolves dependencies lazily
            $linkService = $freshContainer->make(LinkValidationInterface::class);
            expect($linkService)->toBeInstanceOf(LinkValidationService::class);
        });
    });

    describe('AppServiceProvider', function () {
        it('can register AppServiceProvider successfully', function () {
            $provider = new AppServiceProvider($this->isolatedContainer);
            
            expect($provider)->toBeInstanceOf(AppServiceProvider::class)
                ->and($provider)->toBeInstanceOf(ServiceProvider::class);
            
            // Register and boot should not throw exceptions
            $provider->register();
            $provider->boot();
            
            // AppServiceProvider doesn't register specific services, so just verify it works
            expect(true)->toBeTrue();
        });
    });

    describe('Error Handling and Edge Cases', function () {
        it('throws exception when resolving service with missing dependencies', function () {
            // Create a fresh container without any bindings
            $freshContainer = new \Illuminate\Container\Container();
            
            // Create provider that depends on SecurityValidationInterface
            $freshContainer->bind(LinkValidationInterface::class, function ($app) {
                return new LinkValidationService(
                    $app->make(SecurityValidationInterface::class) // This will fail
                );
            });
            
            // Attempting to resolve should fail
            expect(fn() => $freshContainer->make(LinkValidationInterface::class))
                ->toThrow(Exception::class);
        });

        it('handles service creation errors properly', function () {
            $freshContainer = new \Illuminate\Container\Container();
            
            // Create a provider that will fail during registration
            $freshContainer->bind('FailingService', function () {
                throw new Exception('Service creation failed');
            });
            
            // The binding should succeed (errors happen during resolution)
            expect($freshContainer->bound('FailingService'))->toBeTrue();
            
            // But resolution should fail
            expect(fn() => $freshContainer->make('FailingService'))
                ->toThrow(Exception::class, 'Service creation failed');
        });
    });

    describe('Advanced Service Provider Features', function () {
        it('works with custom factory methods', function () {
            $provider = new ValidateLinksServiceProvider($this->isolatedContainer);
            $provider->register();
            
            // Verify that ReportingService uses custom factory
            $reportingService = $this->isolatedContainer->make(ReportingInterface::class);
            expect($reportingService)->toBeInstanceOf(ReportingService::class);
            
            // Verify that LinkValidationService uses custom factory with dependency injection
            $linkService = $this->isolatedContainer->make(LinkValidationInterface::class);
            expect($linkService)->toBeInstanceOf(LinkValidationService::class);
            
            // Check that the factory properly injected the security service
            $reflection = new ReflectionClass($linkService);
            $securityProperty = $reflection->getProperty('security');
            $securityProperty->setAccessible(true);
            $injectedSecurity = $securityProperty->getValue($linkService);
            
            expect($injectedSecurity)->toBeInstanceOf(SecurityValidationInterface::class);
        });

        it('supports conditional bindings', function () {
            $freshContainer = new \Illuminate\Container\Container();
            
            // Simulate conditional binding based on environment
            $isTestEnvironment = true;
            
            if ($isTestEnvironment) {
                $freshContainer->bind('ConditionalService', function () {
                    return new class {
                        public function getType(): string { return 'test'; }
                    };
                });
            } else {
                $freshContainer->bind('ConditionalService', function () {
                    return new class {
                        public function getType(): string { return 'production'; }
                    };
                });
            }
            
            $service = $freshContainer->make('ConditionalService');
            expect($service->getType())->toBe('test');
        });

        it('handles deferred loading correctly', function () {
            // ValidateLinksServiceProvider is not deferred, but we can test the concept
            $provider = new ValidateLinksServiceProvider($this->isolatedContainer);
            
            // Check if provider is deferred (it shouldn't be)
            $reflection = new ReflectionClass($provider);
            $deferredProperty = $reflection->getProperty('defer');
            $deferredProperty->setAccessible(true);
            $isDeferred = $deferredProperty->getValue($provider);
            
            expect($isDeferred)->toBeFalse();
            
            // Since it's not deferred, services should be registered immediately
            $provider->register();
            expect($this->isolatedContainer->bound(SecurityValidationInterface::class))->toBeTrue();
        });

        it('maintains interface segregation', function () {
            $provider = new ValidateLinksServiceProvider($this->isolatedContainer);
            $provider->register();
            
            // Each service should implement only its specific interface
            $securityService = $this->isolatedContainer->make(SecurityValidationInterface::class);
            $reportingService = $this->isolatedContainer->make(ReportingInterface::class);
            $statisticsService = $this->isolatedContainer->make(StatisticsInterface::class);
            $githubService = $this->isolatedContainer->make(GitHubAnchorInterface::class);
            $linkService = $this->isolatedContainer->make(LinkValidationInterface::class);
            
            // Verify interface implementations
            expect($securityService)->toBeInstanceOf(SecurityValidationInterface::class)
                ->and($reportingService)->toBeInstanceOf(ReportingInterface::class)
                ->and($statisticsService)->toBeInstanceOf(StatisticsInterface::class)
                ->and($githubService)->toBeInstanceOf(GitHubAnchorInterface::class)
                ->and($linkService)->toBeInstanceOf(LinkValidationInterface::class);
            
            // Verify they don't implement each other's interfaces
            expect($securityService)->not->toBeInstanceOf(ReportingInterface::class)
                ->and($reportingService)->not->toBeInstanceOf(SecurityValidationInterface::class);
        });
    });

    describe('Performance and Optimization', function () {
        it('registers services with acceptable performance', function () {
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);
            
            // Register provider multiple times to test performance
            for ($i = 0; $i < 10; $i++) {
                $freshContainer = new \Illuminate\Container\Container();
                $provider = new ValidateLinksServiceProvider($freshContainer);
                $provider->register();
            }
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            
            $registrationTime = $endTime - $startTime;
            $memoryUsed = $endMemory - $startMemory;
            
            // Registration should be fast (< 100ms for 10 registrations)
            expect($registrationTime)->toBeLessThan(0.1)
                ->and($memoryUsed)->toBeLessThan(5 * 1024 * 1024); // Less than 5MB
        });

        it('supports tagged services', function () {
            $freshContainer = new \Illuminate\Container\Container();
            
            // Simulate tagged services
            $freshContainer->bind('ValidationService1', SecurityValidationService::class);
            $freshContainer->bind('ValidationService2', function () {
                return new class implements SecurityValidationInterface {
                    public function validateUrl(string $url): bool { return true; }
                    public function validatePath(string $path): bool { return true; }
                    public function isDomainBlocked(string $domain): bool { return false; }
                    public function isProtocolAllowed(string $protocol): bool { return true; }
                    public function validateFileSize(string $filePath): bool { return true; }
                    public function getSecurityConfig(): array { return []; }
                };
            });
            
            $freshContainer->tag(['ValidationService1', 'ValidationService2'], 'validators');
            
            // Get all tagged services
            $validators = $freshContainer->tagged('validators');
            $validatorArray = iterator_to_array($validators);
            
            expect($validatorArray)->toHaveCount(2)
                ->and($validatorArray[0])->toBeInstanceOf(SecurityValidationInterface::class)
                ->and($validatorArray[1])->toBeInstanceOf(SecurityValidationInterface::class);
        });

        it('supports contextual bindings', function () {
            $freshContainer = new \Illuminate\Container\Container();
            
            // Create different implementations
            $freshContainer->bind('FastSecurityValidator', SecurityValidationService::class);
            $freshContainer->bind('SlowSecurityValidator', function () {
                return new class implements SecurityValidationInterface {
                    public function validateUrl(string $url): bool { return true; }
                    public function validatePath(string $path): bool { return true; }
                    public function isDomainBlocked(string $domain): bool { return false; }
                    public function isProtocolAllowed(string $protocol): bool { return true; }
                    public function validateFileSize(string $filePath): bool { return true; }
                    public function getSecurityConfig(): array { return []; }
                };
            });
            
            // Create services that need different validators
            $freshContainer->bind('FastLinkValidator', function ($app) {
                return new LinkValidationService($app->make('FastSecurityValidator'));
            });
            
            $freshContainer->bind('SlowLinkValidator', function ($app) {
                return new LinkValidationService($app->make('SlowSecurityValidator'));
            });
            
            $fastValidator = $freshContainer->make('FastLinkValidator');
            $slowValidator = $freshContainer->make('SlowLinkValidator');
            
            expect($fastValidator)->toBeInstanceOf(LinkValidationService::class)
                ->and($slowValidator)->toBeInstanceOf(LinkValidationService::class)
                ->and($fastValidator)->not->toBe($slowValidator);
        });
    });
});