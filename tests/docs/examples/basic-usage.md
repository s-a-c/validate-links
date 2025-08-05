# Basic Usage Examples

This guide provides practical examples of using the advanced PEST test suite features. These examples demonstrate real-world usage patterns and best practices for writing effective tests.

## Getting Started

### Basic Test Structure

```php
<?php

declare(strict_types=1);

use Tests\Support\PestTestCase;

// Use the enhanced test case for advanced features
uses(PestTestCase::class);

// Apply tags for organization and filtering
uses()->group('validation', 'feature')->in(__FILE__);

describe('Link Validation', function () {
    
    it_should_validate_valid_urls()
        ->group('unit', 'fast')
        ->expect(function () {
            return $this->linkValidation->validateLink('https://example.com');
        })->toBeValidLink();
        
    it_should_identify_broken_links()
        ->group('unit', 'fast')
        ->expect(function () {
            return $this->linkValidation->validateLink('https://broken-link.com');
        })->toBeBrokenLink();
});
```

### Using Lazy Initialization

```php
describe('Lazy Initialization Examples', function () {
    
    beforeEach(function () {
        // Define expensive objects that are created only when needed
        $this->let('expensiveService', fn() => new ExpensiveValidationService());
        $this->let('largeDataset', fn() => $this->generateTestUrls(1000));
        $this->let('databaseConnection', fn() => $this->createTestDatabase());
    });
    
    it_should_create_objects_lazily()
        ->group('unit', 'performance')
        ->expect(function () {
            // Objects are created only when accessed
            $service = $this->expensiveService; // Created here
            $data = $this->largeDataset;       // Created here
            
            return $service->process($data);
        })->not->toBeNull();
        
    it_should_reuse_lazy_instances()
        ->group('unit', 'performance')
        ->expect(function () {
            $service1 = $this->expensiveService;
            $service2 = $this->expensiveService; // Same instance
            
            return $service1 === $service2;
        })->toBeTrue();
});
```

## Custom Matchers Examples

### Link Validation Matchers

```php
describe('Link Validation Matchers', function () {
    
    it_should_use_validation_matchers()
        ->group('validation', 'unit')
        ->expect(function () {
            $result = $this->linkValidation->validateLink('https://httpbin.org/status/200');
            return $result;
        })->toBeValidLink()
          ->toHaveHttpStatus(200)
          ->toBeSuccessfulHttpStatus()
          ->toBeExternalLink();
          
    it_should_validate_collections()
        ->group('validation', 'integration')
        ->expect(function () {
            $urls = [
                'https://example.com',
                'https://httpbin.org/status/200',
                'https://httpbin.org/status/404',
            ];
            return $this->linkValidation->validateLinks($urls);
        })->toBeValidationCollection(3)
          ->toHaveValidResults(2)
          ->toHaveBrokenResults(1)
          ->toHaveSuccessRate(0.67);
});
```

### Performance Matchers

```php
describe('Performance Testing Examples', function () {
    
    it_should_complete_quickly()
        ->group('performance', 'fast')
        ->expect(function () {
            return fn() => $this->linkValidation->validateLink('https://example.com');
        })->toCompleteWithin(0.5);
        
    it_should_use_memory_efficiently()
        ->group('performance', 'memory')
        ->expect(function () {
            return fn() => $this->processLargeDataset();
        })->toUseMemoryLessThan(50 * 1024 * 1024); // 50MB
        
    it_should_be_performant()
        ->group('performance', 'benchmark')
        ->expect(function () {
            return fn() => $this->linkValidation->validateLinks($this->testUrls);
        })->toBePerformant();
});
```

### Security Matchers

```php
describe('Security Testing Examples', function () {
    
    it_should_validate_secure_urls()
        ->group('security', 'validation')
        ->expect(function () {
            return 'https://secure-site.com';
        })->toBeSecure()
          ->toHaveSecureProtocol();
          
    it_should_detect_insecure_protocols()
        ->group('security', 'validation')
        ->expect(function () {
            return 'http://insecure-site.com';
        })->toHaveInsecureProtocol();
        
    it_should_validate_security_headers()
        ->group('security', 'integration')
        ->expect(function () {
            $response = $this->httpClient->get('https://secure-site.com');
            return $response;
        })->toHaveSecurityHeaders()
          ->toHaveStrictTransportSecurity()
          ->toHaveContentSecurityPolicy();
});
```

### Reporting Matchers

```php
describe('Reporting Examples', function () {
    
    it_should_generate_valid_json_report()
        ->group('reporting', 'feature')
        ->expect(function () {
            return $this->reportGenerator->generate('json');
        })->toMatchReportFormat('json')
          ->toHaveValidJsonStructure()
          ->toContainValidationResults()
          ->toHaveReportSummary();
          
    it_should_generate_complete_report()
        ->group('reporting', 'feature')
        ->expect(function () {
            return $this->reportGenerator->generateComplete();
        })->toBeValidReport()
          ->toContainStatistics()
          ->toHaveTimestamp()
          ->toHaveMetadata()
          ->toBeExportable();
});
```

## Builder Pattern Examples

### Validation Config Builder

```php
describe('Builder Pattern Examples', function () {
    
    it_should_build_simple_config()
        ->group('unit', 'builder')
        ->expect(function () {
            return ValidationConfigBuilder::create()
                ->withTimeout(30)
                ->withMaxRedirects(5)
                ->build();
        })->toBeInstanceOf(ValidationConfig::class);
        
    it_should_build_complex_config()
        ->group('unit', 'builder')
        ->expect(function () {
            return ValidationConfigBuilder::create()
                ->forFastTesting()
                ->withScopes([ValidationScope::EXTERNAL])
                ->addHeader('Authorization', 'Bearer token')
                ->withExcludePatterns(['*.pdf', '*.zip'])
                ->withUserAgent('validate-links/test')
                ->build();
        })->toBeInstanceOf(ValidationConfig::class);
        
    it_should_use_predefined_configs()
        ->group('unit', 'builder')
        ->expect(function () {
            $fastConfig = ValidationConfigBuilder::create()->forFastTesting()->build();
            $ciConfig = ValidationConfigBuilder::create()->forCI()->build();
            $thoroughConfig = ValidationConfigBuilder::create()->forThoroughTesting()->build();
            
            return [$fastConfig, $ciConfig, $thoroughConfig];
        })->each->toBeInstanceOf(ValidationConfig::class);
});
```

### Using Global Helper Functions

```php
describe('Global Helper Functions', function () {
    
    it_should_use_builder_helpers()
        ->group('unit', 'helpers')
        ->expect(function () {
            $config1 = buildValidationConfig()->forFastTesting()->build();
            $config2 = fastConfig();
            $config3 = ciConfig();
            
            return [$config1, $config2, $config3];
        })->each->toBeInstanceOf(ValidationConfig::class);
        
    it_should_use_test_double_helpers()
        ->group('unit', 'helpers')
        ->expect(function () {
            $linkValidation = linkValidationWithState([
                'valid_urls' => ['https://example.com'],
                'broken_urls' => ['https://broken.com'],
            ]);
            
            $security = securityValidationWithRules([
                'blocked_domains' => ['malicious.com'],
                'allowed_protocols' => ['https'],
            ]);
            
            return [$linkValidation, $security];
        })->each->toBeObject();
});
```

## Dependency Injection Examples

### Mock Creation and Binding

```php
describe('Dependency Injection Examples', function () {
    
    it_should_create_and_bind_mocks()
        ->group('unit', 'mocking')
        ->expect(function () {
            // Create and bind mock with simple configuration
            $mock = $this->bindMock(LinkValidationInterface::class, [
                'validateLink' => true,
                'isValidUrl' => true,
            ]);
            
            return $mock->validateLink('https://example.com');
        })->toBeTrue();
        
    it_should_create_complex_mocks()
        ->group('unit', 'mocking')
        ->expect(function () {
            // Create mock with complex behavior
            $mock = $this->bindMock(LinkValidationInterface::class, [
                'validateLink' => [
                    'with' => ['https://example.com'],
                    'andReturn' => $this->createValidationResult(['status' => 'valid']),
                    'times' => 1,
                ],
                'validateLinks' => $this->createValidationCollection(3),
            ]);
            
            $result = $mock->validateLink('https://example.com');
            return $result;
        })->toBeInstanceOf(ValidationResult::class);
        
    it_should_use_spies_and_partial_mocks()
        ->group('unit', 'mocking')
        ->expect(function () {
            // Create spy to record interactions
            $spy = $this->spy(ReportingInterface::class);
            
            // Create partial mock
            $partial = $this->partialMock(ConcreteService::class, [
                'expensiveMethod' => 'mocked_result',
            ]);
            
            return [$spy, $partial];
        })->each->toBeObject();
});
```

### Advanced Test Doubles

```php
describe('Advanced Test Doubles', function () {
    
    it_should_use_stateful_test_doubles()
        ->group('integration', 'mocking')
        ->expect(function () {
            // Create test double with state management
            $linkValidation = TestDoubleFactory::linkValidationWithState([
                'valid_urls' => ['https://example.com', 'https://test.com'],
                'broken_urls' => ['https://broken.com'],
                'timeout_urls' => ['https://slow.com'],
            ]);
            
            $result1 = $linkValidation->validateLink('https://example.com');
            $result2 = $linkValidation->validateLink('https://broken.com');
            
            return [$result1->getStatus(), $result2->getStatus()];
        })->toBe(['valid', 'broken']);
        
    it_should_use_failing_services()
        ->group('unit', 'error_handling')
        ->expect(function () {
            // Create service that fails predictably
            $failing = TestDoubleFactory::failingService(ServiceInterface::class, [
                'process' => new ValidationException('Simulated failure'),
                'validate' => new TimeoutException('Connection timeout'),
            ]);
            
            return fn() => $failing->process('test');
        })->toThrow(ValidationException::class, 'Simulated failure');
        
    it_should_use_slow_services()
        ->group('performance', 'mocking')
        ->expect(function () {
            // Create service with artificial delays
            $slow = TestDoubleFactory::slowService(ServiceInterface::class, [
                'process' => 500, // 500ms delay
                'validate' => 200, // 200ms delay
            ]);
            
            return fn() => $slow->process('test');
        })->toExecuteInMoreThan(0.5);
});
```

## Performance Monitoring Examples

### Built-in Performance Tracking

```php
describe('Performance Monitoring Examples', function () {
    
    it_should_track_performance_automatically()
        ->group('performance', 'monitoring')
        ->expect(function () {
            // Performance is tracked automatically
            $result = $this->measurePerformance(function () {
                return $this->linkValidation->validateLinks($this->testUrls);
            }, 'batch_validation');
            
            // Check performance metrics
            $metrics = $this->getPerformanceMetrics();
            return $metrics['batch_validation'] ?? null;
        })->not->toBeNull();
        
    it_should_assert_performance_thresholds()
        ->group('performance', 'monitoring')
        ->expect(function () {
            $this->recordCheckpoint('start');
            
            $result = $this->linkValidation->validateLink('https://example.com');
            
            $this->recordCheckpoint('end');
            
            // Assert performance thresholds
            $this->assertPerformance('validation', 1.0, 10 * 1024 * 1024);
            
            return $result;
        })->toBeValidLink();
        
    it_should_use_performance_wrapper()
        ->group('performance', 'monitoring')
        ->expect(function () {
            // Use performance wrapper for automatic assertion
            return $this->assertPerformant(function () {
                return $this->linkValidation->validateLinks($this->createTestUrls('fast'));
            }, 0.5, 20 * 1024 * 1024); // 500ms, 20MB
        })->toBeInstanceOf(ValidationResultCollection::class);
});
```

## Tagging Examples

### Comprehensive Tagging Strategy

```php
// File-level tags
uses()->group('validation', 'feature')->in(__FILE__);

describe('Comprehensive Tagging Examples', function () {
    
    describe('Unit Tests', function () {
        uses()->group('unit', 'fast', 'parallel');
        
        it_should_validate_url_format()
            ->group('validation', 'format')
            ->expect(fn() => $this->validator->isValidUrl('https://example.com'))->toBeTrue();
            
        it_should_handle_edge_cases()
            ->group('edge_case', 'validation')
            ->expect(fn() => $this->validator->isValidUrl(''))->toBeFalse();
    });
    
    describe('Integration Tests', function () {
        uses()->group('integration', 'sequential');
        
        it_should_integrate_with_external_services()
            ->group('external', 'api')
            ->skip(!env('INCLUDE_EXTERNAL_TESTS'), 'External tests disabled')
            ->expect(fn() => $this->externalService->test())->toBeSuccessful();
    });
    
    describe('Performance Tests', function () {
        uses()->group('performance', 'benchmark');
        
        it_should_meet_performance_requirements()
            ->group('critical', 'fast')
            ->skip(!env('INCLUDE_PERFORMANCE_TESTS'), 'Performance tests disabled')
            ->expect(fn() => $this->performanceTest())->toCompleteWithin(1.0);
    });
});
```

### Environment-Specific Tests

```php
describe('Environment-Specific Examples', function () {
    
    it_should_run_in_ci_environment()
        ->group('ci', 'smoke')
        ->skip(!env('CI'), 'Only runs in CI')
        ->expect(function () {
            return $this->ciSpecificValidation();
        })->toBeSuccessful();
        
    it_should_require_github_token()
        ->group('github', 'external')
        ->skip(!env('GITHUB_TOKEN'), 'GitHub token required')
        ->expect(function () {
            return $this->githubService->validateRepositoryLinks();
        })->toBeSuccessful();
        
    it_should_test_production_config()
        ->group('production', 'config')
        ->skip(!env('PRODUCTION_CONFIG'), 'Production config required')
        ->expect(function () {
            return $this->productionConfigTest();
        })->toBeSuccessful();
});
```

## Parallel Testing Examples

### Parallel-Safe Tests

```php
describe('Parallel Testing Examples', function () {
    
    it_should_be_parallel_safe()
        ->group('unit', 'parallel', 'fast')
        ->expect(function () {
            // This test can run safely in parallel
            $workerId = ParallelTestManager::getWorkerIdentifier();
            $data = ParallelTestManager::createTestData('validation', [
                'url' => 'https://example.com',
                'worker' => $workerId,
            ]);
            
            return $this->validator->validate($data);
        })->toBeSuccessful();
        
    it_should_use_exclusive_access()
        ->group('integration', 'parallel')
        ->expect(function () {
            // Use exclusive access for shared resources
            return ParallelTestManager::withExclusiveAccess('shared_file', function () {
                $workerId = ParallelTestManager::getWorkerIdentifier();
                $content = "Worker {$workerId} was here\n";
                
                file_put_contents('/tmp/shared_test_file.txt', $content, FILE_APPEND | LOCK_EX);
                return true;
            });
        })->toBeTrue();
        
    it_should_require_sequential_execution()
        ->group('integration', 'sequential', 'database')
        ->expect(function () {
            // This test must run sequentially
            return $this->databaseMigrationTest();
        })->toBeSuccessful();
});
```

## Error Handling Examples

### Exception Testing

```php
describe('Error Handling Examples', function () {
    
    it_should_handle_validation_errors()
        ->group('error_handling', 'validation')
        ->expect(function () {
            return fn() => $this->validator->validateUrl('invalid-url');
        })->toThrow(ValidationException::class);
        
    it_should_handle_timeout_errors()
        ->group('error_handling', 'timeout')
        ->expect(function () {
            $slow = TestDoubleFactory::slowService(ServiceInterface::class, [
                'validate' => 5000, // 5 second delay
            ]);
            
            return fn() => $slow->validate('https://example.com');
        })->toThrow(TimeoutException::class);
        
    it_should_handle_security_errors()
        ->group('error_handling', 'security')
        ->expect(function () {
            return fn() => $this->securityValidator->validate('http://malicious.com');
        })->toThrow(SecurityException::class);
});
```

## Complete Example Test File

```php
<?php

declare(strict_types=1);

use Tests\Support\PestTestCase;
use Tests\Support\Builders\ValidationConfigBuilder;
use Tests\Support\Doubles\TestDoubleFactory;
use Tests\Support\Parallel\ParallelTestManager;
use App\Exceptions\ValidationException;
use App\Services\ValueObjects\ValidationResult;

uses(PestTestCase::class);
uses()->group('validation', 'comprehensive')->in(__FILE__);

describe('Complete Link Validation Example', function () {
    
    beforeEach(function () {
        // Setup lazy initialization
        $this->let('fastConfig', fn() => ValidationConfigBuilder::create()->forFastTesting()->build());
        $this->let('testUrls', fn() => $this->createTestUrls('mixed'));
        $this->let('mockValidation', fn() => $this->mockLinkValidation([
            'validateLink' => $this->createValidationResult(['status' => 'valid']),
        ]));
    });
    
    describe('Basic Validation', function () {
        uses()->group('unit', 'fast', 'parallel');
        
        it_should_validate_single_url()
            ->group('smoke', 'critical')
            ->expect(function () {
                return $this->linkValidation->validateLink('https://example.com');
            })->toBeValidLink()
              ->toHaveHttpStatus(200)
              ->toBeExternalLink();
              
        it_should_validate_multiple_urls()
            ->group('batch', 'integration')
            ->expect(function () {
                return $this->linkValidation->validateLinks($this->testUrls);
            })->toBeValidationCollection()
              ->toHaveValidResults(expect()->toBeGreaterThan(0));
    });
    
    describe('Performance Validation', function () {
        uses()->group('performance', 'benchmark');
        
        it_should_validate_quickly()
            ->group('fast', 'critical')
            ->skip(!env('INCLUDE_PERFORMANCE_TESTS'), 'Performance tests disabled')
            ->expect(function () {
                return fn() => $this->linkValidation->validateLink('https://httpbin.org/status/200');
            })->toCompleteWithin(1.0);
            
        it_should_handle_batch_efficiently()
            ->group('memory', 'load')
            ->skip(!env('INCLUDE_PERFORMANCE_TESTS'), 'Performance tests disabled')
            ->expect(function () {
                return $this->assertPerformant(function () {
                    return $this->linkValidation->validateLinks($this->createTestUrls('fast'));
                }, 2.0, 50 * 1024 * 1024);
            })->toBeInstanceOf(ValidationResultCollection::class);
    });
    
    describe('Security Validation', function () {
        uses()->group('security', 'critical');
        
        it_should_validate_secure_urls()
            ->group('protocol', 'https')
            ->expect(function () {
                return $this->securityValidation->validateUrl('https://secure-site.com');
            })->toBeSecure()
              ->toPassSecurityCheck();
              
        it_should_block_insecure_protocols()
            ->group('protocol', 'http')
            ->expect(function () {
                return $this->securityValidation->validateUrl('http://insecure-site.com');
            })->toHaveInsecureProtocol();
    });
    
    describe('Error Handling', function () {
        uses()->group('error_handling', 'edge_case');
        
        it_should_handle_invalid_urls()
            ->group('validation', 'format')
            ->expect(function () {
                return fn() => $this->linkValidation->validateLink('invalid-url');
            })->toThrow(ValidationException::class);
            
        it_should_handle_timeout_gracefully()
            ->group('timeout', 'resilience')
            ->expect(function () {
                $slow = TestDoubleFactory::slowService(LinkValidationInterface::class, [
                    'validateLink' => 5000, // 5 second delay
                ]);
                
                return fn() => $slow->validateLink('https://slow-site.com');
            })->toThrow(TimeoutException::class);
    });
    
    describe('Reporting', function () {
        uses()->group('reporting', 'feature');
        
        it_should_generate_validation_report()
            ->group('json', 'format')
            ->expect(function () {
                $results = $this->linkValidation->validateLinks($this->testUrls);
                return $this->reportGenerator->generateFromResults($results, 'json');
            })->toMatchReportFormat('json')
              ->toContainValidationResults()
              ->toHaveReportSummary();
    });
    
    describe('Parallel Execution', function () {
        uses()->group('parallel', 'integration');
        
        it_should_work_in_parallel()
            ->group('worker', 'isolation')
            ->expect(function () {
                $workerId = ParallelTestManager::getWorkerIdentifier();
                $testData = ParallelTestManager::createTestData('validation', [
                    'url' => 'https://example.com',
                ]);
                
                return $this->linkValidation->validateLink($testData['url']);
            })->toBeValidLink();
    });
});
```

This comprehensive example demonstrates:

- **Lazy initialization** for expensive objects
- **Custom matchers** for domain-specific assertions
- **Builder patterns** for complex object creation
- **Dependency injection** with mocks and test doubles
- **Performance monitoring** with automatic tracking
- **Comprehensive tagging** for organization and filtering
- **Parallel testing** with proper isolation
- **Error handling** with exception testing
- **Complete test organization** with describe blocks

## Related Documentation

- [Feature-Specific Guides](../features/)
- [Advanced Patterns Examples](advanced-patterns.md)
- [Performance Testing Examples](performance-testing.md)
- [Developer Guides](../guides/)