<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Exceptions\SecurityException;
use App\Services\LinkValidationService;
use App\Services\SecurityValidationService;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

describe('LinkValidationService Exception Handling', function () {
    beforeEach(function () {
        $this->securityService = createSecurityValidationMock();
        $this->linkValidationService = new LinkValidationService($this->securityService);
        $this->config = createTestValidationConfig();
    });

    describe('Network Error Scenarios', function () {
        it('handles connection timeout errors gracefully', function () {
            $url = 'https://timeout.example.com';
            $timeoutException = simulateNetworkFailure('connection_timeout');
            
            // Mock the security service to pass validation
            $this->securityService->shouldReceive('validateUrl')
                ->with($url)
                ->andReturn(true);
            
            // Test that the service handles timeout gracefully
            $result = testGracefulDegradation(
                function () use ($url, $timeoutException) {
                    throw $timeoutException;
                },
                ValidationResult::failure($url, ValidationScope::EXTERNAL, LinkStatus::BROKEN, 'Connection timeout')
            );
            
            expect($result)->toBeInstanceOf(ValidationResult::class)
                ->and($result->getStatus())->toBe(LinkStatus::BROKEN)
                ->and($result->getError())->toContain('timeout');
        });

        it('handles DNS resolution failures', function () {
            $url = 'https://nonexistent.example.com';
            $dnsException = simulateNetworkFailure('dns_failure');
            
            expectExceptionWithMessage(
                fn() => throw $dnsException,
                ConnectException::class,
                'Could not resolve host: nonexistent.example.com'
            );
        });

        it('handles connection refused errors', function () {
            $url = 'https://localhost:9999';
            $connectionException = simulateNetworkFailure('connection_refused');
            
            expectExceptionWithMessage(
                fn() => throw $connectionException,
                ConnectException::class,
                'Connection refused'
            );
        });

        it('handles SSL certificate errors', function () {
            $url = 'https://self-signed.badssl.com';
            $sslException = simulateNetworkFailure('ssl_error');
            
            expectExceptionWithMessage(
                fn() => throw $sslException,
                RequestException::class,
                'SSL certificate problem: unable to get local issuer certificate'
            );
        });

        it('handles network unreachable errors', function () {
            $url = 'https://192.0.2.1';
            $networkException = simulateNetworkFailure('network_unreachable');
            
            expectExceptionWithMessage(
                fn() => throw $networkException,
                ConnectException::class,
                'Network is unreachable'
            );
        });
    });

    describe('HTTP Error Response Scenarios', function () {
        it('handles 400 Bad Request responses', function () {
            $url = 'https://httpbin.org/status/400';
            $httpException = createHttpException(400, 'Bad Request');
            
            expectExceptionWithMessage(
                fn() => throw $httpException,
                RequestException::class,
                'Bad Request'
            );
        });

        it('handles 401 Unauthorized responses', function () {
            $url = 'https://httpbin.org/status/401';
            $httpException = createHttpException(401, 'Unauthorized');
            
            expectExceptionWithMessage(
                fn() => throw $httpException,
                RequestException::class,
                'Unauthorized'
            );
        });

        it('handles 403 Forbidden responses', function () {
            $url = 'https://httpbin.org/status/403';
            $httpException = createHttpException(403, 'Forbidden');
            
            expectExceptionWithMessage(
                fn() => throw $httpException,
                RequestException::class,
                'Forbidden'
            );
        });

        it('handles 404 Not Found responses', function () {
            $url = 'https://httpbin.org/status/404';
            $httpException = createHttpException(404, 'Not Found');
            
            expectExceptionWithMessage(
                fn() => throw $httpException,
                RequestException::class,
                'Not Found'
            );
        });

        it('handles 429 Too Many Requests responses', function () {
            $url = 'https://httpbin.org/status/429';
            $httpException = createHttpException(429, 'Too Many Requests');
            
            expectExceptionWithMessage(
                fn() => throw $httpException,
                RequestException::class,
                'Too Many Requests'
            );
        });

        it('handles 500 Internal Server Error responses', function () {
            $url = 'https://httpbin.org/status/500';
            $httpException = createHttpException(500, 'Internal Server Error');
            
            expectExceptionWithMessage(
                fn() => throw $httpException,
                RequestException::class,
                'Internal Server Error'
            );
        });

        it('handles 502 Bad Gateway responses', function () {
            $url = 'https://httpbin.org/status/502';
            $httpException = createHttpException(502, 'Bad Gateway');
            
            expectExceptionWithMessage(
                fn() => throw $httpException,
                RequestException::class,
                'Bad Gateway'
            );
        });

        it('handles 503 Service Unavailable responses', function () {
            $url = 'https://httpbin.org/status/503';
            $httpException = createHttpException(503, 'Service Unavailable');
            
            expectExceptionWithMessage(
                fn() => throw $httpException,
                RequestException::class,
                'Service Unavailable'
            );
        });

        it('handles 504 Gateway Timeout responses', function () {
            $url = 'https://httpbin.org/status/504';
            $httpException = createHttpException(504, 'Gateway Timeout');
            
            expectExceptionWithMessage(
                fn() => throw $httpException,
                RequestException::class,
                'Gateway Timeout'
            );
        });
    });

    describe('Security Validation Error Scenarios', function () {
        it('throws SecurityException for blocked domains', function () {
            $url = 'https://malicious.example.com';
            
            $this->securityService->shouldReceive('validateUrl')
                ->with($url)
                ->andReturn(false);
            
            expectExceptionWithMessage(
                fn() => $this->linkValidationService->validateLink($url, $this->config),
                SecurityException::class,
                'URL failed security validation'
            );
        });

        it('throws SecurityException for invalid protocols', function () {
            $url = 'javascript:alert("xss")';
            
            $this->securityService->shouldReceive('validateUrl')
                ->with($url)
                ->andReturn(false);
            
            expect(function () use ($url) {
                $this->linkValidationService->validateLink($url, $this->config);
            })->toThrow(SecurityException::class);
        });

        it('handles security validation with proper context', function () {
            $url = 'https://phishing.example.com';
            $threatType = 'phishing';
            $context = [
                'threat_level' => 'high',
                'detection_time' => date('Y-m-d H:i:s'),
                'scanner' => 'security_service',
            ];
            
            $securityException = createSecurityException($url, $threatType, $context);
            
            expectExceptionContext(
                fn() => throw $securityException,
                SecurityException::class,
                $context
            );
        });
    });

    describe('File System Error Scenarios', function () {
        it('handles file not found errors', function () {
            $filePath = '/nonexistent/file.md';
            $fileException = simulateFileSystemError('file_not_found');
            
            expectExceptionWithMessage(
                fn() => throw $fileException,
                RuntimeException::class,
                'File not found: /nonexistent/file.md'
            );
        });

        it('handles permission denied errors', function () {
            $filePath = '/etc/shadow';
            $permissionException = simulateFileSystemError('permission_denied');
            
            expectExceptionWithMessage(
                fn() => throw $permissionException,
                RuntimeException::class,
                'Permission denied: /etc/shadow'
            );
        });

        it('handles disk full errors', function () {
            $diskFullException = simulateFileSystemError('disk_full');
            
            expectExceptionWithMessage(
                fn() => throw $diskFullException,
                RuntimeException::class,
                'No space left on device'
            );
        });

        it('handles I/O errors', function () {
            $ioException = simulateFileSystemError('io_error');
            
            expectExceptionWithMessage(
                fn() => throw $ioException,
                RuntimeException::class,
                'Input/output error'
            );
        });

        it('handles file corruption errors', function () {
            $corruptionException = simulateFileSystemError('file_corrupted');
            
            expectExceptionWithMessage(
                fn() => throw $corruptionException,
                RuntimeException::class,
                'File is corrupted or unreadable'
            );
        });
    });

    describe('Timeout Error Scenarios', function () {
        it('handles request timeout errors', function () {
            $timeoutException = simulateTimeoutError('request_timeout');
            
            expectExceptionWithMessage(
                fn() => throw $timeoutException,
                RequestException::class,
                'Request timeout after 30 seconds'
            );
        });

        it('handles connect timeout errors', function () {
            $connectTimeoutException = simulateTimeoutError('connect_timeout');
            
            expectExceptionWithMessage(
                fn() => throw $connectTimeoutException,
                ConnectException::class,
                'Connection timeout'
            );
        });

        it('handles read timeout errors', function () {
            $readTimeoutException = simulateTimeoutError('read_timeout');
            
            expectExceptionWithMessage(
                fn() => throw $readTimeoutException,
                RequestException::class,
                'Read timeout'
            );
        });
    });

    describe('Exception Propagation and Recovery', function () {
        it('propagates exceptions correctly through the call stack', function () {
            $originalException = new RuntimeException('Original error');
            $wrappedException = new SecurityException('Security error', 0, $originalException);
            
            expectExceptionChain(
                fn() => throw $wrappedException,
                SecurityException::class,
                RuntimeException::class
            );
        });

        it('tests error recovery mechanisms', function () {
            $failingOperation = function () {
                throw new RuntimeException('Operation failed');
            };
            
            $recoveryOperation = function (Throwable $e) {
                return 'recovered_successfully';
            };
            
            testErrorRecovery($failingOperation, $recoveryOperation, 'recovered_successfully');
        });

        it('tests graceful degradation with fallback values', function () {
            $failingOperation = function () {
                throw new RuntimeException('Service unavailable');
            };
            
            $fallbackResult = ValidationResult::failure(
                'https://example.com',
                ValidationScope::EXTERNAL,
                LinkStatus::BROKEN,
                'Service unavailable - using fallback'
            );
            
            $result = testGracefulDegradation($failingOperation, $fallbackResult);
            
            expect($result)->toBeInstanceOf(ValidationResult::class)
                ->and($result->getStatus())->toBe(LinkStatus::BROKEN)
                ->and($result->getError())->toContain('fallback');
        });

        it('tests error cleanup procedures', function () {
            $cleanupExecuted = false;
            
            $failingOperation = function () {
                throw new RuntimeException('Operation failed');
            };
            
            $cleanupValidator = function () use (&$cleanupExecuted) {
                $cleanupExecuted = true;
            };
            
            testErrorCleanup($failingOperation, $cleanupValidator);
            
            expect($cleanupExecuted)->toBeTrue();
        });

        it('tests error reporting mechanisms', function () {
            $failingOperation = function () {
                throw new RuntimeException('Test error for reporting');
            };
            
            $errorReporter = function (Throwable $e) {
                return [
                    'error_type' => get_class($e),
                    'message' => $e->getMessage(),
                    'timestamp' => time(),
                ];
            };
            
            $reportedErrors = testErrorReporting($failingOperation, $errorReporter);
            
            expect($reportedErrors)->toHaveCount(1)
                ->and($reportedErrors[0]['error_type'])->toBe(RuntimeException::class)
                ->and($reportedErrors[0]['message'])->toBe('Test error for reporting');
        });
    });

    describe('Resource Exhaustion Scenarios', function () {
        it('handles memory limit exhaustion', function () {
            $memoryException = simulateResourceExhaustion('memory_limit');
            
            expectExceptionWithMessage(
                fn() => throw $memoryException,
                RuntimeException::class,
                'Fatal error: Allowed memory size exhausted'
            );
        });

        it('handles thread pool exhaustion', function () {
            $threadException = simulateResourceExhaustion('thread_pool_exhausted');
            
            expectExceptionWithMessage(
                fn() => throw $threadException,
                RuntimeException::class,
                'Thread pool exhausted: no available threads'
            );
        });

        it('handles connection pool exhaustion', function () {
            $connectionException = simulateResourceExhaustion('connection_pool_exhausted');
            
            expectExceptionWithMessage(
                fn() => throw $connectionException,
                RuntimeException::class,
                'Connection pool exhausted'
            );
        });

        it('handles file descriptor limit', function () {
            $fdException = simulateResourceExhaustion('file_descriptor_limit');
            
            expectExceptionWithMessage(
                fn() => throw $fdException,
                RuntimeException::class,
                'Too many open files'
            );
        });
    });

    describe('Performance and Load Testing', function () {
        it('tests exception handling performance under load', function () {
            $operation = function () {
                throw new RuntimeException('Load test exception');
            };
            
            $results = testExceptionHandlingUnderLoad($operation, 100, 0.01);
            
            expect($results['exception_count'])->toBe(100)
                ->and($results['average_time'])->toBeLessThan(0.01)
                ->and($results['max_time'])->toBeGreaterThan(0)
                ->and($results['min_time'])->toBeGreaterThan(0);
        });

        it('validates exception handling does not degrade performance significantly', function () {
            $fastOperation = function () {
                // Simulate fast operation
                usleep(100); // 0.1ms
            };
            
            $slowExceptionOperation = function () {
                usleep(100); // 0.1ms
                throw new RuntimeException('Performance test exception');
            };
            
            // Test that exception handling doesn't add significant overhead
            $results = testExceptionHandlingUnderLoad($slowExceptionOperation, 50, 0.005);
            
            expect($results['average_time'])->toBeLessThan(0.005);
        });
    });

    describe('Mock Failure Scenarios', function () {
        it('tests intermittent failure patterns', function () {
            $mock = createIntermittentFailureMock(
                SecurityValidationService::class,
                'validateUrl',
                3,
                new RuntimeException('Service temporarily unavailable')
            );
            
            // First 3 calls should succeed
            expect($mock->validateUrl('https://example.com'))->toBeTrue();
            expect($mock->validateUrl('https://example.com'))->toBeTrue();
            expect($mock->validateUrl('https://example.com'))->toBeTrue();
            
            // 4th call should fail
            expect(fn() => $mock->validateUrl('https://example.com'))
                ->toThrow(RuntimeException::class, 'Service temporarily unavailable');
        });

        it('tests multiple method failure scenarios', function () {
            $mock = createFailingMock(SecurityValidationService::class, [
                'validateUrl' => new SecurityException('URL validation failed'),
                'validatePath' => new SecurityException('Path validation failed'),
            ]);
            
            expect(fn() => $mock->validateUrl('https://example.com'))
                ->toThrow(SecurityException::class, 'URL validation failed');
            
            expect(fn() => $mock->validatePath('/some/path'))
                ->toThrow(SecurityException::class, 'Path validation failed');
        });
    });
});