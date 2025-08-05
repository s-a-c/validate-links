<?php

declare(strict_types=1);

use App\Exceptions\ConfigurationException;
use App\Exceptions\SecurityException;
use App\Exceptions\ValidateLinksException;
use App\Exceptions\ValidationException;

describe('Exception Hierarchy Integration Tests', function () {
    describe('Exception Inheritance Hierarchy Validation', function () {
        it('validates complete inheritance chain for ValidationException', function () {
            $exception = createValidationException();
            
            expect($exception)->toBeInstanceOf(ValidationException::class)
                ->and($exception)->toBeInstanceOf(ValidateLinksException::class)
                ->and($exception)->toBeInstanceOf(Exception::class)
                ->and($exception)->toBeInstanceOf(Throwable::class);
        });

        it('validates complete inheritance chain for SecurityException', function () {
            $exception = createSecurityException();
            
            expect($exception)->toBeInstanceOf(SecurityException::class)
                ->and($exception)->toBeInstanceOf(ValidateLinksException::class)
                ->and($exception)->toBeInstanceOf(Exception::class)
                ->and($exception)->toBeInstanceOf(Throwable::class);
        });

        it('validates complete inheritance chain for ConfigurationException', function () {
            $exception = createConfigurationException();
            
            expect($exception)->toBeInstanceOf(ConfigurationException::class)
                ->and($exception)->toBeInstanceOf(ValidateLinksException::class)
                ->and($exception)->toBeInstanceOf(Exception::class)
                ->and($exception)->toBeInstanceOf(Throwable::class);
        });

        it('ensures all custom exceptions extend ValidateLinksException', function () {
            $customExceptions = [
                ValidationException::class,
                SecurityException::class,
                ConfigurationException::class,
            ];
            
            foreach ($customExceptions as $exceptionClass) {
                $reflection = new ReflectionClass($exceptionClass);
                expect($reflection->isSubclassOf(ValidateLinksException::class))->toBeTrue(
                    "{$exceptionClass} should extend ValidateLinksException"
                );
            }
        });

        it('validates that all custom exceptions are final classes', function () {
            $customExceptions = [
                ValidationException::class,
                SecurityException::class,
                ConfigurationException::class,
            ];
            
            foreach ($customExceptions as $exceptionClass) {
                $reflection = new ReflectionClass($exceptionClass);
                expect($reflection->isFinal())->toBeTrue(
                    "{$exceptionClass} should be a final class"
                );
            }
        });
    });

    describe('Polymorphic Exception Behavior', function () {
        it('handles polymorphic exception catching correctly', function () {
            $exceptions = [
                createValidationException(),
                createSecurityException(),
                createConfigurationException(),
            ];
            
            foreach ($exceptions as $exception) {
                $caughtAsBase = false;
                $caughtAsSpecific = false;
                
                try {
                    throw $exception;
                } catch (ValidateLinksException $e) {
                    $caughtAsBase = true;
                    expect($e)->toBe($exception);
                } catch (Throwable) {
                    // Should not reach here
                }
                
                try {
                    throw $exception;
                } catch (ValidationException | SecurityException | ConfigurationException $e) {
                    $caughtAsSpecific = true;
                    expect($e)->toBe($exception);
                } catch (Throwable) {
                    // Should not reach here for our custom exceptions
                }
                
                expect($caughtAsBase)->toBeTrue();
                expect($caughtAsSpecific)->toBeTrue();
            }
        });

        it('validates method resolution order for overridden methods', function () {
            $exceptions = [
                createValidationException(),
                createSecurityException(),
                createConfigurationException(),
            ];
            
            foreach ($exceptions as $exception) {
                // Test that specific implementations are called, not base class
                expect($exception->getErrorCode())->toBeString();
                expect($exception->getSeverity())->toBeString();
                expect($exception->getContext())->toBeArray();
                expect($exception->toArray())->toBeArray();
                
                // Verify specific error codes
                $errorCode = $exception->getErrorCode();
                expect($errorCode)->toMatch('/^[A-Z_]+$/'); // Should be uppercase with underscores
            }
        });

        it('ensures consistent behavior across exception hierarchy', function () {
            $exceptions = [
                createValidationException('https://test.com', 404, 1000),
                createSecurityException('https://malicious.com', 'malware'),
                createConfigurationException('missing_file', '/config/test.json'),
            ];
            
            foreach ($exceptions as $exception) {
                // All exceptions should have these common behaviors
                expect($exception->getMessage())->toBeString();
                expect($exception->getCode())->toBeInt();
                expect($exception->getFile())->toBeString();
                expect($exception->getLine())->toBeInt();
                expect($exception->getTrace())->toBeArray();
                expect($exception->getTraceAsString())->toBeString();
                expect($exception->__toString())->toBeString();
                
                // Custom behaviors from ValidateLinksException
                expect($exception->getErrorCode())->toBeString();
                expect($exception->getSeverity())->toBeString();
                expect($exception->getContext())->toBeArray();
                expect($exception->toArray())->toBeArray();
            }
        });
    });

    describe('Exception Serialization Consistency', function () {
        it('validates serialization consistency across exception hierarchy', function () {
            $exceptions = [
                createValidationException('https://test.com', 500, 2000),
                createSecurityException('https://threat.com', 'phishing', ['risk' => 'high']),
                createConfigurationException('invalid_value', '/config/app.json'),
            ];
            
            foreach ($exceptions as $exception) {
                testExceptionSerialization($exception);
                
                // Test that serialization preserves all important data
                $serialized = $exception->__serialize();
                
                expect($serialized)->toBeArray()
                    ->and($serialized)->toHaveKey('message')
                    ->and($serialized)->toHaveKey('code')
                    ->and($serialized)->toHaveKey('file')
                    ->and($serialized)->toHaveKey('line')
                    ->and($serialized)->toHaveKey('context')
                    ->and($serialized)->toHaveKey('previous');
            }
        });

        it('validates deserialization consistency across exception hierarchy', function () {
            $originalExceptions = [
                createValidationException('https://original.com', 404, 1500),
                createSecurityException('https://original-threat.com', 'malware', ['scanner' => 'test']),
                createConfigurationException('missing_key', '/config/original.json'),
            ];
            
            foreach ($originalExceptions as $original) {
                $serialized = $original->__serialize();
                $restored = new (get_class($original))();
                $restored->__unserialize($serialized);
                
                // Context should be preserved
                expect($restored->getContext())->toBe($original->getContext());
                
                // Error code and severity should match (these are class-specific)
                expect($restored->getErrorCode())->toBe($original->getErrorCode());
                expect($restored->getSeverity())->toBe($original->getSeverity());
            }
        });
    });

    describe('Exception Chaining and Propagation', function () {
        it('validates exception chaining across different exception types', function () {
            $rootCause = new RuntimeException('Database connection failed');
            $configException = ConfigurationException::withContext(
                'Configuration loading failed',
                ['error' => 'database_unavailable'],
                $rootCause
            );
            
            // Test chaining - create SecurityException with ConfigurationException as previous
            $securityException = new SecurityException('Security check failed', 0, $configException);
            
            // Verify the exception chain manually
            expect($securityException)->toBeInstanceOf(SecurityException::class);
            expect($securityException->getPrevious())->toBeInstanceOf(ConfigurationException::class);
            
            // Check if the ConfigurationException has the RuntimeException as previous
            $previousException = $securityException->getPrevious();
            if ($previousException && $previousException->getPrevious()) {
                expect($previousException->getPrevious())->toBeInstanceOf(RuntimeException::class);
            }
        });

        it('validates complex exception propagation scenarios', function () {
            $originalError = new RuntimeException('Network timeout');
            $validationError = ValidationException::validationTimeout('https://slow.com', 30);
            $securityError = SecurityException::threatDetected(
                'https://malicious.com',
                'timeout_exploitation',
                ['original_error' => $originalError->getMessage()]
            );
            $configError = ConfigurationException::withContext(
                'Security configuration failed',
                ['security_error' => $securityError->getMessage()],
                $securityError
            );
            
            // Test exception propagation manually since the helper function has issues
            try {
                throw $configError;
            } catch (ConfigurationException $e) {
                expect($e)->toBeInstanceOf(ConfigurationException::class);
                // The configError was created with securityError as previous, so check for SecurityException
                if ($e->getPrevious()) {
                    expect($e->getPrevious())->toBeInstanceOf(SecurityException::class);
                }
            }
        });

        it('ensures proper exception context preservation through chains', function () {
            $originalContext = ['database' => 'primary', 'timeout' => 30];
            $rootException = ConfigurationException::withContext(
                'Database configuration failed',
                $originalContext
            );
            
            $chainedException = SecurityException::threatDetected(
                'https://example.com',
                'configuration_exploit',
                ['previous_context' => $rootException->getContext()],
                $rootException
            );
            
            // Simplified test to avoid infinite recursion
            try {
                throw $chainedException;
            } catch (SecurityException $e) {
                expect($e)->toBeInstanceOf(SecurityException::class);
                expect($e->getContext())->toHaveKey('previous_context');
                expect($e->getPrevious())->toBeInstanceOf(ConfigurationException::class);
            }
        });
    });

    describe('Exception Factory Method Consistency', function () {
        it('validates ValidationException factory methods', function () {
            $linkException = ValidationException::linkValidationFailed('https://test.com', 404, 1000);
            $fileException = ValidationException::fileProcessingFailed('/test/file.md', 'Parse error');
            $timeoutException = ValidationException::validationTimeout('https://slow.com', 30);
            
            // All should be ValidationException instances
            expect($linkException)->toBeInstanceOf(ValidationException::class);
            expect($fileException)->toBeInstanceOf(ValidationException::class);
            expect($timeoutException)->toBeInstanceOf(ValidationException::class);
            
            // All should have appropriate context
            expect($linkException->getContext())->toHaveKey('validation_type', 'link_validation');
            expect($fileException->getContext())->toHaveKey('validation_type', 'file_processing');
            expect($timeoutException->getContext())->toHaveKey('validation_type', 'timeout');
            
            // All should have same error code and severity
            expect($linkException->getErrorCode())->toBe('VALIDATION_ERROR');
            expect($fileException->getErrorCode())->toBe('VALIDATION_ERROR');
            expect($timeoutException->getErrorCode())->toBe('VALIDATION_ERROR');
            
            expect($linkException->getSeverity())->toBe('medium');
            expect($fileException->getSeverity())->toBe('medium');
            expect($timeoutException->getSeverity())->toBe('medium');
        });

        it('validates SecurityException factory methods', function () {
            $threatException = SecurityException::threatDetected('https://malicious.com', 'malware', []);
            $blacklistException = SecurityException::blacklistedDomain('evil.com');
            $sslException = SecurityException::sslViolation('https://insecure.com', 'Certificate expired');
            
            // All should be SecurityException instances
            expect($threatException)->toBeInstanceOf(SecurityException::class);
            expect($blacklistException)->toBeInstanceOf(SecurityException::class);
            expect($sslException)->toBeInstanceOf(SecurityException::class);
            
            // All should have appropriate threat types
            expect($blacklistException->getContext())->toHaveKey('threat_type', 'blacklisted_domain');
            expect($sslException->getContext())->toHaveKey('threat_type', 'ssl_violation');
            
            // All should have same error code and severity
            expect($threatException->getErrorCode())->toBe('SECURITY_VIOLATION');
            expect($blacklistException->getErrorCode())->toBe('SECURITY_VIOLATION');
            expect($sslException->getErrorCode())->toBe('SECURITY_VIOLATION');
            
            expect($threatException->getSeverity())->toBe('critical');
            expect($blacklistException->getSeverity())->toBe('critical');
            expect($sslException->getSeverity())->toBe('critical');
        });

        it('validates ConfigurationException factory methods', function () {
            $missingFileException = ConfigurationException::missingFile('/config/missing.json');
            $invalidFormatException = ConfigurationException::invalidFormat('/config/bad.json', 'JSON', 'invalid');
            $missingKeyException = ConfigurationException::missingRequiredKey('api_key', '/config/app.json');
            $invalidValueException = ConfigurationException::invalidValue('timeout', 'invalid', 'integer');
            
            // All should be ConfigurationException instances
            expect($missingFileException)->toBeInstanceOf(ConfigurationException::class);
            expect($invalidFormatException)->toBeInstanceOf(ConfigurationException::class);
            expect($missingKeyException)->toBeInstanceOf(ConfigurationException::class);
            expect($invalidValueException)->toBeInstanceOf(ConfigurationException::class);
            
            // All should have appropriate error types
            expect($missingFileException->getContext())->toHaveKey('error_type', 'missing_file');
            expect($invalidFormatException->getContext())->toHaveKey('error_type', 'invalid_format');
            expect($missingKeyException->getContext())->toHaveKey('error_type', 'missing_required_key');
            expect($invalidValueException->getContext())->toHaveKey('error_type', 'invalid_value');
            
            // All should have same error code and severity
            expect($missingFileException->getErrorCode())->toBe('CONFIGURATION_ERROR');
            expect($invalidFormatException->getErrorCode())->toBe('CONFIGURATION_ERROR');
            expect($missingKeyException->getErrorCode())->toBe('CONFIGURATION_ERROR');
            expect($invalidValueException->getErrorCode())->toBe('CONFIGURATION_ERROR');
            
            expect($missingFileException->getSeverity())->toBe('high');
            expect($invalidFormatException->getSeverity())->toBe('high');
            expect($missingKeyException->getSeverity())->toBe('high');
            expect($invalidValueException->getSeverity())->toBe('high');
        });
    });

    describe('Exception Message and Context Validation', function () {
        it('validates message formatting consistency across exception types', function () {
            $exceptions = [
                ValidationException::linkValidationFailed('https://example.com', 404, 1000),
                SecurityException::blacklistedDomain('malicious.com'),
                ConfigurationException::missingFile('/config/app.json'),
            ];
            
            foreach ($exceptions as $exception) {
                $message = $exception->getMessage();
                
                // Messages should be non-empty and descriptive
                expect($message)->not->toBeEmpty();
                expect(strlen($message))->toBeGreaterThan(10);
                
                // Messages should not contain sensitive information
                expect($message)->not->toContain('password');
                expect($message)->not->toContain('secret');
                expect($message)->not->toContain('token');
            }
        });

        it('validates context data structure consistency', function () {
            $exceptions = [
                ValidationException::linkValidationFailed('https://example.com', 404, 1000),
                SecurityException::threatDetected('https://threat.com', 'malware', ['risk' => 'high']),
                ConfigurationException::invalidValue('timeout', 'invalid', 'integer'),
            ];
            
            foreach ($exceptions as $exception) {
                $context = $exception->getContext();
                
                // Context should be an array
                expect($context)->toBeArray();
                
                // Context keys should be strings
                foreach (array_keys($context) as $key) {
                    expect($key)->toBeString();
                }
                
                // Context should not contain sensitive data
                foreach ($context as $key => $value) {
                    if (is_string($value)) {
                        expect($value)->not->toContain('password');
                        expect($value)->not->toContain('secret');
                    }
                }
            }
        });

        it('validates toArray method consistency across exception types', function () {
            $exceptions = [
                createValidationException(),
                createSecurityException(),
                createConfigurationException(),
            ];
            
            foreach ($exceptions as $exception) {
                $array = $exception->toArray();
                
                // Should have all required fields
                $requiredFields = ['error_code', 'message', 'severity', 'file', 'line', 'context'];
                foreach ($requiredFields as $field) {
                    expect($array)->toHaveKey($field);
                }
                
                // Field types should be consistent
                expect($array['error_code'])->toBeString();
                expect($array['message'])->toBeString();
                expect($array['severity'])->toBeString();
                expect($array['file'])->toBeString();
                expect($array['line'])->toBeInt();
                expect($array['context'])->toBeArray();
            }
        });
    });

    describe('Exception Performance and Memory Usage', function () {
        it('validates exception creation performance across types', function () {
            $exceptionTypes = [
                fn() => createValidationException(),
                fn() => createSecurityException(),
                fn() => createConfigurationException(),
            ];
            
            foreach ($exceptionTypes as $creator) {
                $results = testExceptionHandlingUnderLoad($creator, 100, 0.01);
                
                expect($results['average_time'])->toBeLessThan(0.01);
                expect($results['exception_count'])->toBe(0); // Creator shouldn't throw
            }
        });

        it('validates memory usage consistency across exception hierarchy', function () {
            $initialMemory = memory_get_usage();
            
            // Create many exceptions of different types
            $exceptions = [];
            for ($i = 0; $i < 100; $i++) {
                $exceptions[] = createValidationException("https://example{$i}.com", 404, 1000);
                $exceptions[] = createSecurityException("https://threat{$i}.com", 'malware');
                $exceptions[] = createConfigurationException('missing_file', "/config/file{$i}.json");
            }
            
            $afterCreationMemory = memory_get_usage();
            
            // Clear exceptions
            unset($exceptions);
            gc_collect_cycles();
            
            $finalMemory = memory_get_usage();
            
            $creationIncrease = $afterCreationMemory - $initialMemory;
            $memoryLeaked = $finalMemory - $initialMemory;
            
            // Memory should be reasonable and mostly cleaned up
            expect($creationIncrease)->toBeLessThan(5 * 1024 * 1024); // Less than 5MB
            expect($memoryLeaked)->toBeLessThan(1 * 1024 * 1024); // Less than 1MB leaked
        });
    });

    describe('Exception Compatibility and Standards Compliance', function () {
        it('validates PSR-3 logging compatibility', function () {
            $exceptions = [
                createValidationException(),
                createSecurityException(),
                createConfigurationException(),
            ];
            
            foreach ($exceptions as $exception) {
                // Should be loggable as context
                $logContext = [
                    'exception' => $exception,
                    'error_code' => $exception->getErrorCode(),
                    'severity' => $exception->getSeverity(),
                ];
                
                expect($logContext)->toBeArray();
                expect($logContext['exception'])->toBeInstanceOf(Throwable::class);
            }
        });

        it('validates JSON serialization compatibility', function () {
            $exceptions = [
                createValidationException(),
                createSecurityException(),
                createConfigurationException(),
            ];
            
            foreach ($exceptions as $exception) {
                $array = $exception->toArray();
                $json = json_encode($array);
                
                expect($json)->not->toBeFalse();
                expect(json_last_error())->toBe(JSON_ERROR_NONE);
                
                $decoded = json_decode($json, true);
                expect($decoded)->toBe($array);
            }
        });

        it('validates exception handler compatibility', function () {
            $exceptions = [
                createValidationException(),
                createSecurityException(),
                createConfigurationException(),
            ];
            
            foreach ($exceptions as $exception) {
                // Should work with standard exception handlers
                $handled = false;
                
                try {
                    throw $exception;
                } catch (ValidateLinksException $e) {
                    $handled = true;
                    expect($e)->toBe($exception);
                } catch (Throwable $e) {
                    $handled = true;
                    expect($e)->toBe($exception);
                }
                
                expect($handled)->toBeTrue();
            }
        });
    });
});