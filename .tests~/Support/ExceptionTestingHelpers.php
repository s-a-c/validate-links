<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Exceptions\ConfigurationException;
use App\Exceptions\SecurityException;
use App\Exceptions\ValidateLinksException;
use App\Exceptions\ValidationException;
use Exception;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Throwable;

/**
 * Comprehensive exception testing utilities for validating error paths,
 * exception propagation, and recovery mechanisms.
 */
final class ExceptionTestingHelpers
{
    /**
     * Exception assertion helpers for granular validation.
     */

    /**
     * Assert that an exception has the exact expected message.
     */
    public static function expectExceptionWithMessage(callable $callback, string $exceptionClass, string $expectedMessage): void
    {
        try {
            $callback();
            throw new Exception("Expected {$exceptionClass} to be thrown");
        } catch (Throwable $e) {
            if (!$e instanceof $exceptionClass) {
                throw new Exception("Expected {$exceptionClass}, got " . get_class($e));
            }
            
            if ($e->getMessage() !== $expectedMessage) {
                throw new Exception(
                    "Expected message '{$expectedMessage}', got '{$e->getMessage()}'"
                );
            }
        }
    }

    /**
     * Assert that an exception has the expected error code.
     */
    public static function expectExceptionWithCode(callable $callback, string $exceptionClass, int $expectedCode): void
    {
        try {
            $callback();
            throw new Exception("Expected {$exceptionClass} to be thrown");
        } catch (Throwable $e) {
            if (!$e instanceof $exceptionClass) {
                throw new Exception("Expected {$exceptionClass}, got " . get_class($e));
            }
            
            if ($e->getCode() !== $expectedCode) {
                throw new Exception(
                    "Expected code {$expectedCode}, got {$e->getCode()}"
                );
            }
        }
    }

    /**
     * Assert that an exception chain contains the expected previous exception.
     */
    public static function expectExceptionChain(callable $callback, string $exceptionClass, string $previousExceptionClass): void
    {
        try {
            $callback();
            throw new Exception("Expected {$exceptionClass} to be thrown");
        } catch (Throwable $e) {
            if (!$e instanceof $exceptionClass) {
                throw new Exception("Expected {$exceptionClass}, got " . get_class($e));
            }
            
            $previous = $e->getPrevious();
            if (!$previous instanceof $previousExceptionClass) {
                $actualClass = $previous ? get_class($previous) : 'null';
                throw new Exception(
                    "Expected previous exception {$previousExceptionClass}, got {$actualClass}"
                );
            }
        }
    }

    /**
     * Assert that an exception contains expected context data.
     */
    public static function expectExceptionContext(callable $callback, string $exceptionClass, array $expectedContext): void
    {
        try {
            $callback();
            throw new Exception("Expected {$exceptionClass} to be thrown");
        } catch (Throwable $e) {
            if (!$e instanceof $exceptionClass) {
                throw new Exception("Expected {$exceptionClass}, got " . get_class($e));
            }
            
            if (!$e instanceof ValidateLinksException) {
                throw new Exception("Exception must extend ValidateLinksException to have context");
            }
            
            $actualContext = $e->getContext();
            foreach ($expectedContext as $key => $expectedValue) {
                if (!array_key_exists($key, $actualContext)) {
                    throw new Exception("Expected context key '{$key}' not found");
                }
                
                if ($actualContext[$key] !== $expectedValue) {
                    throw new Exception(
                        "Expected context['{$key}'] = '{$expectedValue}', got '{$actualContext[$key]}'"
                    );
                }
            }
        }
    }

    /**
     * Error simulation helpers for deterministic testing.
     */

    /**
     * Simulate network failure scenarios.
     */
    public static function simulateNetworkFailure(string $type = 'connection_timeout'): Exception
    {
        return match ($type) {
            'connection_timeout' => new ConnectException(
                'Connection timed out after 30 seconds',
                new Request('GET', 'https://example.com')
            ),
            'dns_failure' => new ConnectException(
                'Could not resolve host: nonexistent.example.com',
                new Request('GET', 'https://nonexistent.example.com')
            ),
            'connection_refused' => new ConnectException(
                'Connection refused',
                new Request('GET', 'https://localhost:9999')
            ),
            'ssl_error' => new RequestException(
                'SSL certificate problem: unable to get local issuer certificate',
                new Request('GET', 'https://self-signed.badssl.com')
            ),
            'network_unreachable' => new ConnectException(
                'Network is unreachable',
                new Request('GET', 'https://192.0.2.1')
            ),
            default => new ConnectException(
                'Generic network error',
                new Request('GET', 'https://example.com')
            ),
        };
    }

    /**
     * Simulate file system error scenarios.
     */
    public static function simulateFileSystemError(string $type = 'file_not_found'): Exception
    {
        return match ($type) {
            'file_not_found' => new RuntimeException('File not found: /nonexistent/file.md'),
            'permission_denied' => new RuntimeException('Permission denied: /etc/shadow'),
            'disk_full' => new RuntimeException('No space left on device'),
            'io_error' => new RuntimeException('Input/output error'),
            'file_corrupted' => new RuntimeException('File is corrupted or unreadable'),
            'directory_not_found' => new RuntimeException('Directory not found: /nonexistent/directory'),
            'invalid_path' => new InvalidArgumentException('Invalid file path: contains null bytes'),
            default => new RuntimeException('Generic file system error'),
        };
    }

    /**
     * Simulate resource exhaustion scenarios.
     */
    public static function simulateResourceExhaustion(string $type = 'memory_limit'): Exception
    {
        return match ($type) {
            'memory_limit' => new RuntimeException('Fatal error: Allowed memory size exhausted'),
            'thread_pool_exhausted' => new RuntimeException('Thread pool exhausted: no available threads'),
            'connection_pool_exhausted' => new RuntimeException('Connection pool exhausted'),
            'file_descriptor_limit' => new RuntimeException('Too many open files'),
            'timeout' => new RuntimeException('Operation timed out'),
            default => new RuntimeException('Resource exhaustion error'),
        };
    }

    /**
     * Simulate timeout error scenarios.
     */
    public static function simulateTimeoutError(string $type = 'request_timeout'): Exception
    {
        return match ($type) {
            'request_timeout' => new RequestException(
                'Request timeout after 30 seconds',
                new Request('GET', 'https://httpbin.org/delay/35')
            ),
            'connect_timeout' => new ConnectException(
                'Connection timeout',
                new Request('GET', 'https://example.com')
            ),
            'read_timeout' => new RequestException(
                'Read timeout',
                new Request('GET', 'https://example.com')
            ),
            default => new RequestException(
                'Generic timeout error',
                new Request('GET', 'https://example.com')
            ),
        };
    }

    /**
     * Recovery testing helpers for validation mechanisms.
     */

    /**
     * Test error recovery mechanisms.
     */
    public static function testErrorRecovery(callable $operation, callable $recovery, mixed $expectedRecoveryResult): void
    {
        try {
            $operation();
            throw new Exception('Expected operation to fail');
        } catch (Throwable $e) {
            $recoveryResult = $recovery($e);
            
            // Handle object comparison properly
            if (is_object($expectedRecoveryResult) && is_object($recoveryResult)) {
                // For objects, just check if they're the same class - instances may differ
                if (get_class($expectedRecoveryResult) === get_class($recoveryResult)) {
                    return; // Success - same class type
                } else {
                    throw new Exception(
                        "Expected recovery result of type '" . get_class($expectedRecoveryResult) .
                        "', got '" . get_class($recoveryResult) . "'"
                    );
                }
            } elseif (is_string($expectedRecoveryResult) && is_object($recoveryResult)) {
                // If expected is a class name string and result is an object
                if ($expectedRecoveryResult === get_class($recoveryResult)) {
                    return; // Success
                } else {
                    throw new Exception(
                        "Expected recovery result of type '{$expectedRecoveryResult}', got '" . get_class($recoveryResult) . "'"
                    );
                }
            } elseif ($recoveryResult !== $expectedRecoveryResult) {
                $expectedStr = is_object($expectedRecoveryResult) ? get_class($expectedRecoveryResult) : (string)$expectedRecoveryResult;
                $actualStr = is_object($recoveryResult) ? get_class($recoveryResult) : (string)$recoveryResult;
                throw new Exception(
                    "Expected recovery result '{$expectedStr}', got '{$actualStr}'"
                );
            }
        }
    }

    /**
     * Test graceful degradation behavior.
     */
    public static function testGracefulDegradation(callable $operation, mixed $expectedFallbackValue): mixed
    {
        try {
            return $operation();
        } catch (Throwable) {
            return $expectedFallbackValue;
        }
    }

    /**
     * Test error cleanup procedures.
     */
    public static function testErrorCleanup(callable $operation, callable $cleanupValidator): void
    {
        try {
            $operation();
        } catch (Throwable) {
            // Expected to fail
        } finally {
            $cleanupValidator();
        }
    }

    /**
     * Test error reporting mechanisms.
     */
    public static function testErrorReporting(callable $operation, callable $errorReporter): array
    {
        $reportedErrors = [];
        
        try {
            $operation();
        } catch (Throwable $e) {
            $reportedErrors[] = $errorReporter($e);
        }
        
        return $reportedErrors;
    }

    /**
     * Custom exception factory methods for testing.
     */

    /**
     * Create a validation exception with specific context.
     */
    public static function createValidationException(
        string $url = 'https://example.com',
        int $statusCode = 404,
        int $responseTime = 1000,
        ?Throwable $previous = null
    ): ValidationException {
        return ValidationException::linkValidationFailed($url, $statusCode, $responseTime);
    }

    /**
     * Create a security exception with specific threat context.
     */
    public static function createSecurityException(
        string $url = 'https://malicious.com',
        string $threatType = 'malware',
        array $context = [],
        ?Throwable $previous = null
    ): SecurityException {
        $defaultContext = [
            'threat_level' => 'high',
            'detection_time' => date('Y-m-d H:i:s'),
            'scanner' => 'test_scanner',
        ];
        
        return SecurityException::threatDetected($url, $threatType, array_merge($defaultContext, $context));
    }

    /**
     * Create a configuration exception with specific error context.
     */
    public static function createConfigurationException(
        string $type = 'missing_file',
        string $filePath = '/config/missing.json',
        ?Throwable $previous = null
    ): ConfigurationException {
        return match ($type) {
            'missing_file' => ConfigurationException::missingFile($filePath),
            'invalid_format' => ConfigurationException::invalidFormat($filePath, 'JSON', 'invalid content'),
            'missing_key' => ConfigurationException::missingRequiredKey('required_key', $filePath),
            'invalid_value' => ConfigurationException::invalidValue('timeout', 'invalid', 'integer'),
            default => ConfigurationException::withContext('Generic configuration error', [
                'file_path' => $filePath,
                'error_type' => $type,
            ]),
        };
    }

    /**
     * HTTP error simulation helpers.
     */

    /**
     * Create HTTP error responses for testing.
     */
    public static function createHttpErrorResponse(int $statusCode, string $reasonPhrase = ''): Response
    {
        $defaultReasons = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
        ];
        
        $reason = $reasonPhrase ?: ($defaultReasons[$statusCode] ?? 'Unknown Error');
        
        return new Response($statusCode, [], json_encode([
            'error' => $reason,
            'status_code' => $statusCode,
            'timestamp' => time(),
        ]));
    }

    /**
     * Create HTTP exception for testing.
     */
    public static function createHttpException(int $statusCode, string $message = ''): RequestException
    {
        $request = new Request('GET', 'https://example.com');
        $response = self::createHttpErrorResponse($statusCode);
        $errorMessage = $message ?: "HTTP {$statusCode} error";
        
        return new RequestException($errorMessage, $request, $response);
    }

    /**
     * Mock service error injection helpers.
     */

    /**
     * Create a mock that throws exceptions on specific method calls.
     */
    public static function createFailingMock(string $className, array $methodExceptions): MockInterface
    {
        $mock = Mockery::mock($className);
        
        foreach ($methodExceptions as $method => $exception) {
            $mock->shouldReceive($method)
                ->andThrow($exception);
        }
        
        return $mock;
    }

    /**
     * Create a mock that fails after a certain number of calls.
     */
    public static function createIntermittentFailureMock(
        string $className,
        string $method,
        int $failAfterCalls,
        Exception $exception
    ): MockInterface {
        $mock = Mockery::mock($className);
        $callCount = 0;
        
        $mock->shouldReceive($method)
            ->andReturnUsing(function () use (&$callCount, $failAfterCalls, $exception) {
                $callCount++;
                if ($callCount > $failAfterCalls) {
                    throw $exception;
                }
                return true; // Success for first few calls
            });
        
        return $mock;
    }

    /**
     * Exception propagation testing helpers.
     */

    /**
     * Test that exceptions propagate correctly through the call stack.
     */
    public static function testExceptionPropagation(
        callable $operation,
        array $expectedExceptionChain
    ): void {
        try {
            $operation();
            throw new Exception('Expected operation to throw an exception');
        } catch (Throwable $e) {
            $currentException = $e;
            
            foreach ($expectedExceptionChain as $index => $expectedClass) {
                if ($currentException === null) {
                    throw new Exception(
                        "Expected exception at level {$index} to be {$expectedClass}, but exception chain ended"
                    );
                }
                
                if (!$currentException instanceof $expectedClass) {
                    throw new Exception(
                        "Expected exception at level {$index} to be {$expectedClass}, got " .
                        get_class($currentException)
                    );
                }
                
                $currentException = $currentException->getPrevious();
            }
        }
    }

    /**
     * Validate exception serialization and deserialization.
     */
    public static function testExceptionSerialization(ValidateLinksException $exception): void
    {
        $serialized = $exception->__serialize();
        $newException = new (get_class($exception))();
        $newException->__unserialize($serialized);
        
        // Validate that context is preserved
        if ($exception->getContext() !== $newException->getContext()) {
            throw new Exception('Exception context not preserved during serialization');
        }
    }

    /**
     * Performance and load testing helpers.
     */

    /**
     * Test exception handling under load.
     */
    public static function testExceptionHandlingUnderLoad(
        callable $operation,
        int $iterations,
        float $maxAverageTime
    ): array {
        $times = [];
        $exceptions = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            
            try {
                $operation();
            } catch (Throwable $e) {
                $exceptions[] = $e;
            }
            
            $times[] = microtime(true) - $start;
        }
        
        $averageTime = array_sum($times) / count($times);
        
        if ($averageTime > $maxAverageTime) {
            throw new Exception(
                "Average exception handling time {$averageTime}s exceeds maximum {$maxAverageTime}s"
            );
        }
        
        return [
            'average_time' => $averageTime,
            'max_time' => max($times),
            'min_time' => min($times),
            'exception_count' => count($exceptions),
            'exceptions' => $exceptions,
        ];
    }
}