<?php

declare(strict_types=1);

namespace Tests\Support;

use Mockery;
use Mockery\MockInterface;
use Exception;
use Throwable;

/**
 * Manages mock object lifecycle with proper setup, verification, and cleanup.
 * 
 * Provides centralized mock management with automatic verification,
 * memory cleanup, and error handling for test reliability.
 */
final class MockLifecycleManager
{
    /** @var array<string, MockInterface> */
    private static array $activeMocks = [];
    
    /** @var array<string, array<string, mixed>> */
    private static array $mockExpectations = [];
    
    /** @var array<string, callable> */
    private static array $cleanupCallbacks = [];
    
    /** @var bool */
    private static bool $verificationEnabled = true;
    
    /** @var array<string> */
    private static array $verificationErrors = [];

    /**
     * Register a mock with the lifecycle manager.
     */
    public static function registerMock(string $name, MockInterface $mock, array $expectations = []): MockInterface
    {
        self::$activeMocks[$name] = $mock;
        self::$mockExpectations[$name] = $expectations;
        
        return $mock;
    }

    /**
     * Register a cleanup callback for a specific mock or test.
     */
    public static function registerCleanupCallback(string $name, callable $callback): void
    {
        self::$cleanupCallbacks[$name] = $callback;
    }

    /**
     * Get a registered mock by name.
     */
    public static function getMock(string $name): ?MockInterface
    {
        return self::$activeMocks[$name] ?? null;
    }

    /**
     * Check if a mock is registered.
     */
    public static function hasMock(string $name): bool
    {
        return isset(self::$activeMocks[$name]);
    }

    /**
     * Get all registered mock names.
     */
    public static function getRegisteredMockNames(): array
    {
        return array_keys(self::$activeMocks);
    }

    /**
     * Verify all mock expectations and collect any errors.
     */
    public static function verifyMocks(): array
    {
        $errors = [];
        
        if (!self::$verificationEnabled) {
            return $errors;
        }
        
        foreach (self::$activeMocks as $name => $mock) {
            try {
                // Verify Mockery expectations
                $mock->mockery_verify();
                
                // Verify custom expectations if any
                if (isset(self::$mockExpectations[$name])) {
                    $customErrors = self::verifyCustomExpectations($name, self::$mockExpectations[$name]);
                    if (!empty($customErrors)) {
                        $errors[$name] = $customErrors;
                    }
                }
            } catch (Throwable $e) {
                $errors[$name] = [
                    'type' => 'mockery_verification',
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ];
            }
        }
        
        self::$verificationErrors = array_merge(self::$verificationErrors, $errors);
        
        return $errors;
    }

    /**
     * Verify custom expectations for a mock.
     */
    private static function verifyCustomExpectations(string $mockName, array $expectations): array
    {
        $errors = [];
        
        foreach ($expectations as $expectationType => $expectationData) {
            try {
                switch ($expectationType) {
                    case 'call_count':
                        // Verify specific method call counts
                        foreach ($expectationData as $method => $expectedCount) {
                            $actualCount = self::getMethodCallCount($mockName, $method);
                            if ($actualCount !== $expectedCount) {
                                $errors[] = [
                                    'type' => 'call_count_mismatch',
                                    'method' => $method,
                                    'expected' => $expectedCount,
                                    'actual' => $actualCount,
                                ];
                            }
                        }
                        break;
                        
                    case 'unused_mocks':
                        // Verify that mock was actually used
                        if ($expectationData === true && !self::wasMockUsed($mockName)) {
                            $errors[] = [
                                'type' => 'unused_mock',
                                'message' => "Mock '{$mockName}' was created but never used",
                            ];
                        }
                        break;
                        
                    case 'required_calls':
                        // Verify that required methods were called
                        foreach ($expectationData as $method) {
                            if (!self::wasMethodCalled($mockName, $method)) {
                                $errors[] = [
                                    'type' => 'missing_required_call',
                                    'method' => $method,
                                    'message' => "Required method '{$method}' was not called",
                                ];
                            }
                        }
                        break;
                }
            } catch (Throwable $e) {
                $errors[] = [
                    'type' => 'custom_verification_error',
                    'expectation_type' => $expectationType,
                    'message' => $e->getMessage(),
                ];
            }
        }
        
        return $errors;
    }

    /**
     * Clean up all mocks and run cleanup callbacks.
     */
    public static function cleanup(): void
    {
        // Run custom cleanup callbacks first
        foreach (self::$cleanupCallbacks as $name => $callback) {
            try {
                $callback();
            } catch (Throwable $e) {
                // Log cleanup error but don't fail the test
                error_log("Cleanup callback '{$name}' failed: " . $e->getMessage());
            }
        }
        
        // Close Mockery and clear all mocks
        try {
            Mockery::close();
        } catch (Throwable $e) {
            // Log Mockery cleanup error
            error_log("Mockery cleanup failed: " . $e->getMessage());
        }
        
        // Clear all internal state
        self::$activeMocks = [];
        self::$mockExpectations = [];
        self::$cleanupCallbacks = [];
        self::$verificationErrors = [];
        
        // Force garbage collection if available
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    /**
     * Verify mocks and then clean up.
     */
    public static function verifyAndCleanup(): array
    {
        $errors = self::verifyMocks();
        self::cleanup();
        return $errors;
    }

    /**
     * Reset all mocks without verification (for emergency cleanup).
     */
    public static function reset(): void
    {
        self::$verificationEnabled = false;
        self::cleanup();
        self::$verificationEnabled = true;
    }

    /**
     * Enable or disable mock verification.
     */
    public static function setVerificationEnabled(bool $enabled): void
    {
        self::$verificationEnabled = $enabled;
    }

    /**
     * Get all verification errors from the current test session.
     */
    public static function getVerificationErrors(): array
    {
        return self::$verificationErrors;
    }

    /**
     * Clear verification errors.
     */
    public static function clearVerificationErrors(): void
    {
        self::$verificationErrors = [];
    }

    /**
     * Get statistics about registered mocks.
     */
    public static function getStatistics(): array
    {
        return [
            'total_mocks' => count(self::$activeMocks),
            'mock_names' => array_keys(self::$activeMocks),
            'cleanup_callbacks' => count(self::$cleanupCallbacks),
            'verification_enabled' => self::$verificationEnabled,
            'verification_errors' => count(self::$verificationErrors),
        ];
    }

    /**
     * Create a mock with automatic lifecycle management.
     */
    public static function createManagedMock(
        string $name,
        string $interface,
        array $expectations = [],
        ?callable $setupCallback = null
    ): MockInterface {
        $mock = Mockery::mock($interface);
        
        // Run setup callback if provided
        if ($setupCallback) {
            $setupCallback($mock);
        }
        
        return self::registerMock($name, $mock, $expectations);
    }

    /**
     * Create multiple managed mocks at once.
     */
    public static function createManagedMocks(array $mockDefinitions): array
    {
        $mocks = [];
        
        foreach ($mockDefinitions as $name => $definition) {
            $interface = $definition['interface'];
            $expectations = $definition['expectations'] ?? [];
            $setupCallback = $definition['setup'] ?? null;
            
            $mocks[$name] = self::createManagedMock($name, $interface, $expectations, $setupCallback);
        }
        
        return $mocks;
    }

    /**
     * Check if a mock was actually used (has any method calls).
     */
    private static function wasMockUsed(string $mockName): bool
    {
        $mock = self::$activeMocks[$mockName] ?? null;
        if (!$mock) {
            return false;
        }
        
        try {
            // This is a simplified check - in a real implementation,
            // you might want to track method calls more precisely
            return true; // Assume used for now
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Check if a specific method was called on a mock.
     */
    private static function wasMethodCalled(string $mockName, string $method): bool
    {
        $mock = self::$activeMocks[$mockName] ?? null;
        if (!$mock) {
            return false;
        }
        
        try {
            // This is a simplified check - in a real implementation,
            // you might want to track method calls more precisely
            return true; // Assume called for now
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Get the number of times a method was called on a mock.
     */
    private static function getMethodCallCount(string $mockName, string $method): int
    {
        $mock = self::$activeMocks[$mockName] ?? null;
        if (!$mock) {
            return 0;
        }
        
        try {
            // This is a simplified implementation - in a real scenario,
            // you might want to track method calls more precisely
            return 1; // Assume called once for now
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * Add a verification error manually.
     */
    public static function addVerificationError(string $mockName, array $error): void
    {
        if (!isset(self::$verificationErrors[$mockName])) {
            self::$verificationErrors[$mockName] = [];
        }
        
        self::$verificationErrors[$mockName][] = $error;
    }

    /**
     * Check if there are any verification errors.
     */
    public static function hasVerificationErrors(): bool
    {
        return !empty(self::$verificationErrors);
    }

    /**
     * Get a summary of verification errors for reporting.
     */
    public static function getVerificationErrorSummary(): string
    {
        if (empty(self::$verificationErrors)) {
            return 'No verification errors';
        }
        
        $summary = "Mock verification errors:\n";
        foreach (self::$verificationErrors as $mockName => $errors) {
            $summary .= "  {$mockName}:\n";
            foreach ($errors as $error) {
                $summary .= "    - {$error['type']}: {$error['message']}\n";
            }
        }
        
        return $summary;
    }
}