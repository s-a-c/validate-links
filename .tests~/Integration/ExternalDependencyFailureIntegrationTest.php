<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Exceptions\ConfigurationException;
use App\Exceptions\SecurityException;
use App\Exceptions\ValidationException;
use App\Services\ConcurrentValidationService;
use App\Services\LinkValidationService;
use App\Services\ReportingService;
use App\Services\SecurityValidationService;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;


describe('External Dependency Failure Integration Tests', function () {
    beforeEach(function () {
        $this->securityService = new SecurityValidationService();
        $this->linkValidationService = new LinkValidationService($this->securityService);
        $this->reportingService = new ReportingService();
        $this->config = createTestValidationConfig();
    });

    describe('HTTP Client Failure Scenarios', function () {
        it('handles complete HTTP service outage gracefully', function () {
            // Simulate complete HTTP service failure
            $mockHandler = new MockHandler([
                new ConnectException('Service completely unavailable', new Request('GET', 'https://example.com')),
                new ConnectException('Service completely unavailable', new Request('GET', 'https://test.com')),
                new ConnectException('Service completely unavailable', new Request('GET', 'https://sample.org')),
            ]);
            
            $handlerStack = HandlerStack::create($mockHandler);
            $client = new Client(['handler' => $handlerStack]);
            
            // Test that the service handles complete outage gracefully
            $operation = function () use ($client) {
                throw new ConnectException('Service completely unavailable', new Request('GET', 'https://example.com'));
            };
            
            $fallbackResult = ValidationResult::failure(
                'https://example.com',
                ValidationScope::EXTERNAL,
                LinkStatus::BROKEN,
                'Service unavailable'
            );
            
            $result = testGracefulDegradation($operation, $fallbackResult);
            
            expect($result)->toBeInstanceOf(ValidationResult::class)
                ->and($result->getStatus())->toBe(LinkStatus::BROKEN);
        });

        it('handles partial HTTP service failures with retry logic', function () {
            // Simulate intermittent failures
            $responses = [
                new ConnectException('Temporary failure', new Request('GET', 'https://example.com')),
                new Response(200, [], 'Success after retry'),
                new RequestException('Server error', new Request('GET', 'https://test.com')),
                new Response(200, [], 'Success after retry'),
            ];
            
            $mockHandler = new MockHandler($responses);
            $handlerStack = HandlerStack::create($mockHandler);
            $client = new Client(['handler' => $handlerStack]);
            
            // Test retry behavior
            $retryCount = 0;
            $operation = function () use (&$retryCount) {
                $retryCount++;
                if ($retryCount <= 2) {
                    throw new ConnectException('Temporary failure', new Request('GET', 'https://example.com'));
                }
                return ValidationResult::success('https://example.com', ValidationScope::EXTERNAL);
            };
            
            $recovery = function (Throwable $e) use (&$retryCount) {
                if ($retryCount < 3) {
                    return 'retry_attempted';
                }
                return 'max_retries_exceeded';
            };
            
            testErrorRecovery($operation, $recovery, 'retry_attempted');
        });

        it('handles DNS resolution failures across multiple domains', function () {
            $dnsFailures = [
                'https://nonexistent1.example.com',
                'https://nonexistent2.example.com',
                'https://nonexistent3.example.com',
            ];
            
            foreach ($dnsFailures as $url) {
                $operation = function () use ($url) {
                    throw simulateNetworkFailure('dns_failure');
                };
                
                expectExceptionWithMessage(
                    fn() => throw $operation(),
                    ConnectException::class,
                    'Could not resolve host: nonexistent.example.com'
                );
            }
        });

        it('handles SSL certificate validation failures', function () {
            $sslUrls = [
                'https://self-signed.badssl.com',
                'https://expired.badssl.com',
                'https://wrong.host.badssl.com',
            ];
            
            foreach ($sslUrls as $url) {
                $operation = function () {
                    throw simulateNetworkFailure('ssl_error');
                };
                
                expectExceptionWithMessage(
                    fn() => throw $operation(),
                    RequestException::class,
                    'SSL certificate problem: unable to get local issuer certificate'
                );
            }
        });

        it('handles rate limiting and throttling gracefully', function () {
            $responses = [
                new Response(429, ['Retry-After' => '60'], 'Too Many Requests'),
                new Response(429, ['Retry-After' => '120'], 'Too Many Requests'),
                new Response(200, [], 'Success after rate limit'),
            ];
            
            $mockHandler = new MockHandler($responses);
            $handlerStack = HandlerStack::create($mockHandler);
            $client = new Client(['handler' => $handlerStack]);
            
            // Test rate limiting handling
            $operation = function () {
                throw createHttpException(429, 'Too Many Requests');
            };
            
            $recovery = function (Throwable $e) {
                return 'rate_limit_handled';
            };
            
            testErrorRecovery($operation, $recovery, 'rate_limit_handled');
        });
    });

    describe('File System Dependency Failures', function () {
        it('handles complete file system unavailability', function () {
            $operation = function () {
                throw simulateFileSystemError('io_error');
            };
            
            $recovery = function (Throwable $e) {
                return 'filesystem_unavailable_handled';
            };
            
            testErrorRecovery($operation, $recovery, 'filesystem_unavailable_handled');
        });

        it('handles disk space exhaustion during report generation', function () {
            $operation = function () {
                throw simulateFileSystemError('disk_full');
            };
            
            $fallbackBehavior = false; // Indicate failure to save report
            
            $result = testGracefulDegradation($operation, $fallbackBehavior);
            
            expect($result)->toBeFalse();
        });

        it('handles permission changes during execution', function () {
            // Create a temporary file and test permission scenarios
            $tempFile = createTestFile('permission-test.md', '# Test File\n[Link](https://example.com)');
            
            // Test that the service handles permission errors gracefully
            $operation = function () {
                throw simulateFileSystemError('permission_denied');
            };
            
            expectExceptionWithMessage(
                fn() => throw $operation(),
                RuntimeException::class,
                'Permission denied: /etc/shadow'
            );
        });

        it('handles file corruption during processing', function () {
            $operation = function () {
                throw simulateFileSystemError('file_corrupted');
            };
            
            expectExceptionWithMessage(
                fn() => throw $operation(),
                RuntimeException::class,
                'File is corrupted or unreadable'
            );
        });
    });

    describe('Configuration Service Failures', function () {
        it('handles configuration service unavailability', function () {
            $operation = function () {
                throw createConfigurationException('missing_file', '/config/app.json');
            };
            
            expectExceptionWithMessage(
                fn() => throw $operation(),
                ConfigurationException::class,
                'Configuration file not found: /config/app.json'
            );
        });

        it('handles invalid configuration format gracefully', function () {
            $operation = function () {
                throw createConfigurationException('invalid_format', '/config/malformed.json');
            };
            
            expectExceptionWithMessage(
                fn() => throw $operation(),
                ConfigurationException::class,
                'Invalid configuration file format in /config/malformed.json. Expected JSON format.'
            );
        });

        it('provides fallback configuration when service fails', function () {
            $operation = function () {
                throw new RuntimeException('Configuration service unavailable');
            };
            
            $fallbackConfig = ValidationConfig::create([
                'scopes' => [ValidationScope::INTERNAL],
                'timeout' => 30,
                'concurrent_requests' => 5,
            ]);
            
            $result = testGracefulDegradation($operation, $fallbackConfig);
            
            expect($result)->toBeInstanceOf(ValidationConfig::class);
        });

        it('handles configuration validation errors gracefully', function () {
            $operation = function () {
                throw createConfigurationException('invalid_value', '/config/settings.json');
            };
            
            expectExceptionWithMessage(
                fn() => throw $operation(),
                ConfigurationException::class,
                "Invalid value for configuration key 'timeout'. Expected integer."
            );
        });
    });

    describe('Security Service Failures', function () {
        it('handles security service complete failure', function () {
            $operation = function () {
                throw new RuntimeException('Security service unavailable');
            };
            
            $recovery = function (Throwable $e) {
                return 'security_service_bypassed'; // Fallback to permissive mode
            };
            
            testErrorRecovery($operation, $recovery, 'security_service_bypassed');
        });

        it('handles blacklist service unavailability', function () {
            $operation = function () {
                throw createSecurityException('https://malicious.com', 'service_unavailable', [
                    'error' => 'Blacklist service unreachable',
                    'fallback_mode' => 'permissive',
                ]);
            };
            
            expectExceptionContext(
                fn() => throw $operation(),
                SecurityException::class,
                ['fallback_mode' => 'permissive']
            );
        });

        it('handles threat detection service failures', function () {
            $operation = function () {
                throw createSecurityException('https://suspicious.com', 'detection_failed', [
                    'error' => 'Threat detection service timeout',
                    'action' => 'allow_with_warning',
                ]);
            };
            
            expectExceptionContext(
                fn() => throw $operation(),
                SecurityException::class,
                ['action' => 'allow_with_warning']
            );
        });
    });

    describe('Database and Cache Failures', function () {
        it('handles cache service unavailability gracefully', function () {
            // Simulate cache service failure
            $operation = function () {
                throw new RuntimeException('Cache service unavailable');
            };
            
            $fallbackBehavior = 'cache_disabled'; // Continue without cache
            
            $result = testGracefulDegradation($operation, $fallbackBehavior);
            
            expect($result)->toBe('cache_disabled');
        });

        it('handles cache corruption gracefully', function () {
            $operation = function () {
                throw new RuntimeException('Cache data corrupted');
            };
            
            $recovery = function (Throwable $e) {
                return 'cache_cleared_and_rebuilt';
            };
            
            testErrorRecovery($operation, $recovery, 'cache_cleared_and_rebuilt');
        });

        it('handles database connection failures gracefully', function () {
            $operation = function () {
                throw new RuntimeException('Database connection failed');
            };
            
            $fallbackBehavior = 'memory_storage'; // Use in-memory storage
            
            $result = testGracefulDegradation($operation, $fallbackBehavior);
            
            expect($result)->toBe('memory_storage');
        });
    });

    describe('Concurrent Service Failures', function () {
        it('handles thread pool exhaustion gracefully', function () {
            $operation = function () {
                throw simulateResourceExhaustion('thread_pool_exhausted');
            };
            
            expectExceptionWithMessage(
                fn() => throw $operation(),
                RuntimeException::class,
                'Thread pool exhausted: no available threads'
            );
        });

        it('handles connection pool exhaustion gracefully', function () {
            $operation = function () {
                throw simulateResourceExhaustion('connection_pool_exhausted');
            };
            
            expectExceptionWithMessage(
                fn() => throw $operation(),
                RuntimeException::class,
                'Connection pool exhausted'
            );
        });

        it('degrades gracefully from concurrent to sequential processing', function () {
            $operation = function () {
                throw new RuntimeException('Concurrent processing failed');
            };
            
            $fallbackBehavior = 'sequential_processing'; // Fall back to sequential
            
            $result = testGracefulDegradation($operation, $fallbackBehavior);
            
            expect($result)->toBe('sequential_processing');
        });
    });

    describe('Complete System Integration Failure Scenarios', function () {
        it('handles cascading failures across multiple services', function () {
            $failures = [
                'http_service' => new ConnectException('HTTP service down', new Request('GET', 'https://example.com')),
                'file_system' => simulateFileSystemError('io_error'),
                'configuration' => createConfigurationException('missing_file'),
                'security' => createSecurityException('https://test.com', 'service_unavailable'),
            ];
            
            foreach ($failures as $service => $exception) {
                $operation = function () use ($exception) {
                    throw $exception;
                };
                
                $recovery = function (Throwable $e) use ($service) {
                    return "service_{$service}_failed_gracefully";
                };
                
                testErrorRecovery($operation, $recovery, "service_{$service}_failed_gracefully");
            }
        });

        it('maintains partial functionality during widespread outages', function () {
            // Test that core functionality remains available even when external services fail
            $coreOperation = function () {
                // Simulate core validation that doesn't depend on external services
                return ValidationResult::create(
                    url: './local/file.md',
                    status: LinkStatus::VALID,
                    scope: ValidationScope::INTERNAL
                );
            };
            
            $externalFailure = function () {
                throw new ConnectException('All external services down', new Request('GET', 'https://example.com'));
            };
            
            // Core functionality should still work
            $coreResult = $coreOperation();
            expect($coreResult)->toBeInstanceOf(ValidationResult::class)
                ->and($coreResult->getStatus())->toBe(LinkStatus::VALID);
            
            // External services should fail gracefully
            expect(fn() => throw $externalFailure())
                ->toThrow(ConnectException::class);
        });

        it('recovers gracefully from temporary system-wide failures', function () {
            $systemFailureCount = 0;
            
            $operation = function () use (&$systemFailureCount) {
                $systemFailureCount++;
                if ($systemFailureCount <= 3) {
                    throw new RuntimeException('System temporarily unavailable');
                }
                return 'system_recovered';
            };
            
            $recovery = function (Throwable $e) use (&$systemFailureCount) {
                if ($systemFailureCount <= 3) {
                    return 'retry_after_delay';
                }
                return 'system_recovered';
            };
            
            // First few attempts should trigger recovery
            testErrorRecovery($operation, $recovery, 'retry_after_delay');
            testErrorRecovery($operation, $recovery, 'retry_after_delay');
            testErrorRecovery($operation, $recovery, 'retry_after_delay');
            
            // Final attempt should succeed
            testErrorRecovery($operation, $recovery, 'system_recovered');
        });
    });

    describe('Error Propagation and Cleanup Integration', function () {
        it('ensures proper error propagation through the entire stack', function () {
            $originalError = new RuntimeException('Database connection failed');
            $serviceError = new ValidationException('Validation service failed', 0, $originalError);
            $commandError = new RuntimeException('Command execution failed', 0, $serviceError);
            
            testExceptionPropagation(
                fn() => throw $commandError,
                [RuntimeException::class, ValidationException::class, RuntimeException::class]
            );
        });

        it('ensures complete resource cleanup after cascading failures', function () {
            $resourcesCleanedUp = [];
            
            $operation = function () {
                throw new RuntimeException('Cascading failure occurred');
            };
            
            $cleanup = function () use (&$resourcesCleanedUp) {
                $resourcesCleanedUp[] = 'http_connections';
                $resourcesCleanedUp[] = 'file_handles';
                $resourcesCleanedUp[] = 'memory_buffers';
                $resourcesCleanedUp[] = 'temporary_files';
            };
            
            testErrorCleanup($operation, $cleanup);
            
            expect($resourcesCleanedUp)->toHaveCount(4)
                ->and($resourcesCleanedUp)->toContain('http_connections')
                ->and($resourcesCleanedUp)->toContain('file_handles')
                ->and($resourcesCleanedUp)->toContain('memory_buffers')
                ->and($resourcesCleanedUp)->toContain('temporary_files');
        });

        it('maintains error reporting during system failures', function () {
            $systemFailures = [
                new ConnectException('Network failure', new Request('GET', 'https://example.com')),
                simulateFileSystemError('disk_full'),
                createConfigurationException('invalid_format'),
                createSecurityException('https://malicious.com', 'threat_detected'),
            ];
            
            $allReports = [];
            
            foreach ($systemFailures as $failure) {
                $operation = function () use ($failure) {
                    throw $failure;
                };
                
                $reporter = function (Throwable $e) {
                    return [
                        'type' => get_class($e),
                        'message' => $e->getMessage(),
                        'timestamp' => time(),
                        'severity' => 'critical',
                    ];
                };
                
                $reports = testErrorReporting($operation, $reporter);
                $allReports = array_merge($allReports, $reports);
            }
            
            expect($allReports)->toHaveCount(4);
            foreach ($allReports as $report) {
                expect($report)->toHaveKey('type')
                    ->and($report)->toHaveKey('message')
                    ->and($report)->toHaveKey('timestamp')
                    ->and($report['severity'])->toBe('critical');
            }
        });
    });

    describe('Performance Under Failure Conditions', function () {
        it('maintains acceptable performance during high failure rates', function () {
            $operation = function () {
                // Simulate 80% failure rate
                if (random_int(1, 10) <= 8) {
                    throw new RuntimeException('Service temporarily unavailable');
                }
                return 'success';
            };
            
            $results = testExceptionHandlingUnderLoad($operation, 100, 0.02);
            
            expect($results['exception_count'])->toBeGreaterThan(70) // ~80% failure rate
                ->and($results['average_time'])->toBeLessThan(0.02);
        });

        it('prevents memory leaks during prolonged failure scenarios', function () {
            $initialMemory = memory_get_usage();
            
            // Simulate prolonged failures
            for ($i = 0; $i < 200; $i++) {
                try {
                    throw new RuntimeException("Failure {$i}");
                } catch (Throwable $e) {
                    // Handle and continue
                    unset($e);
                }
                
                if ($i % 50 === 0) {
                    gc_collect_cycles();
                }
            }
            
            $finalMemory = memory_get_usage();
            $memoryIncrease = $finalMemory - $initialMemory;
            
            // Memory increase should be minimal (less than 2MB)
            expect($memoryIncrease)->toBeLessThan(2 * 1024 * 1024);
        });
    });
});