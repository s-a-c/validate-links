# Exception Testing Guide

This guide provides comprehensive documentation for testing exception handling, error paths, and recovery mechanisms in the validate-links application.

## Overview

The validate-links application implements a robust exception handling system with comprehensive test coverage for all error paths, custom exception types, and failure scenarios. This guide covers the testing utilities, patterns, and best practices for validating exception handling behavior.

## Exception Hierarchy

### Base Exception Class

All custom exceptions extend `ValidateLinksException`, which provides:

- **Context Management**: Store additional error context data
- **Serialization Support**: Proper serialization/deserialization
- **Error Codes**: Standardized error classification
- **Severity Levels**: Error severity classification
- **Array Conversion**: Convert exceptions to structured arrays

### Custom Exception Types

1. **ValidationException** (`VALIDATION_ERROR`, `medium` severity)
   - Link validation failures
   - File processing errors
   - Timeout scenarios

2. **SecurityException** (`SECURITY_VIOLATION`, `critical` severity)
   - Security threats detected
   - Blacklisted domains
   - SSL violations

3. **ConfigurationException** (`CONFIGURATION_ERROR`, `high` severity)
   - Missing configuration files
   - Invalid configuration format
   - Missing required keys
   - Invalid configuration values

## Exception Testing Utilities

### Core Helper Functions

The exception testing framework provides comprehensive utilities available globally in tests:

#### Exception Assertion Helpers

```php
// Assert exact message matching
expectExceptionWithMessage(
    fn() => throw new ValidationException('Specific error'),
    ValidationException::class,
    'Specific error'
);

// Assert error code validation
expectExceptionWithCode(
    fn() => throw new ValidationException('Error', 123),
    ValidationException::class,
    123
);

// Assert exception chain validation
expectExceptionChain(
    fn() => throw new SecurityException('Error', 0, $previousException),
    SecurityException::class,
    RuntimeException::class
);

// Assert context data validation
expectExceptionContext(
    fn() => throw $exception,
    ValidationException::class,
    ['url' => 'https://example.com', 'status_code' => 404]
);
```

#### Error Simulation Helpers

```php
// Network failure simulation
$networkError = simulateNetworkFailure('connection_timeout');
$dnsError = simulateNetworkFailure('dns_failure');
$sslError = simulateNetworkFailure('ssl_error');

// File system error simulation
$fileError = simulateFileSystemError('file_not_found');
$permissionError = simulateFileSystemError('permission_denied');
$diskError = simulateFileSystemError('disk_full');

// Resource exhaustion simulation
$memoryError = simulateResourceExhaustion('memory_limit');
$threadError = simulateResourceExhaustion('thread_pool_exhausted');

// Timeout error simulation
$requestTimeout = simulateTimeoutError('request_timeout');
$connectTimeout = simulateTimeoutError('connect_timeout');
```

#### Recovery Testing Helpers

```php
// Test error recovery mechanisms
testErrorRecovery(
    operation: fn() => throw new RuntimeException('Service failed'),
    recovery: fn($e) => 'fallback_activated',
    expectedResult: 'fallback_activated'
);

// Test graceful degradation
$result = testGracefulDegradation(
    operation: fn() => throw new RuntimeException('Service unavailable'),
    fallbackValue: 'default_behavior'
);

// Test error cleanup procedures
testErrorCleanup(
    operation: fn() => throw new RuntimeException('Operation failed'),
    cleanupValidator: fn() => $this->assertResourcesCleaned()
);

// Test error reporting mechanisms
$reports = testErrorReporting(
    operation: fn() => throw new RuntimeException('Error occurred'),
    errorReporter: fn($e) => ['type' => get_class($e), 'message' => $e->getMessage()]
);
```

### Exception Factory Methods

```php
// Create validation exceptions with specific context
$validationException = createValidationException(
    url: 'https://example.com',
    statusCode: 404,
    responseTime: 1000
);

// Create security exceptions with threat context
$securityException = createSecurityException(
    url: 'https://malicious.com',
    threatType: 'malware',
    context: ['risk_level' => 'high']
);

// Create configuration exceptions with error context
$configException = createConfigurationException(
    type: 'missing_file',
    filePath: '/config/missing.json'
);
```

### Mock Failure Scenarios

```php
// Create mocks that fail on specific methods
$failingMock = createFailingMock(SecurityValidationService::class, [
    'validateUrl' => new SecurityException('URL validation failed'),
    'validatePath' => new SecurityException('Path validation failed'),
]);

// Create mocks that fail after certain number of calls
$intermittentMock = createIntermittentFailureMock(
    className: SecurityValidationService::class,
    method: 'validateUrl',
    failAfterCalls: 3,
    exception: new RuntimeException('Service temporarily unavailable')
);
```

## Testing Patterns

### 1. Network Error Scenarios

```php
describe('Network Error Handling', function () {
    it('handles connection timeouts gracefully', function () {
        $timeoutException = simulateNetworkFailure('connection_timeout');
        
        expectExceptionWithMessage(
            fn() => throw $timeoutException,
            ConnectException::class,
            'Connection timed out after 30 seconds'
        );
    });

    it('handles DNS resolution failures', function () {
        $dnsException = simulateNetworkFailure('dns_failure');
        
        $result = testGracefulDegradation(
            fn() => throw $dnsException,
            ValidationResult::failure($url, ValidationScope::EXTERNAL, LinkStatus::BROKEN, 'DNS failed')
        );
        
        expect($result->getStatus())->toBe(LinkStatus::BROKEN);
    });
});
```

### 2. File System Error Scenarios

```php
describe('File System Error Handling', function () {
    it('handles file not found errors', function () {
        $fileError = simulateFileSystemError('file_not_found');
        
        expectExceptionWithMessage(
            fn() => throw $fileError,
            RuntimeException::class,
            'File not found: /nonexistent/file.md'
        );
    });

    it('recovers from permission denied errors', function () {
        testErrorRecovery(
            operation: fn() => throw simulateFileSystemError('permission_denied'),
            recovery: fn($e) => 'alternative_path_used',
            expectedResult: 'alternative_path_used'
        );
    });
});
```

### 3. Configuration Error Scenarios

```php
describe('Configuration Error Handling', function () {
    it('handles missing configuration files', function () {
        $configException = createConfigurationException('missing_file', '/config/app.json');
        
        expectExceptionContext(
            fn() => throw $configException,
            ConfigurationException::class,
            ['file_path' => '/config/app.json', 'error_type' => 'missing_file']
        );
    });

    it('provides fallback configuration on errors', function () {
        $fallbackConfig = ValidationConfig::create(['scopes' => [ValidationScope::INTERNAL]]);
        
        $result = testGracefulDegradation(
            fn() => throw new RuntimeException('Config service unavailable'),
            $fallbackConfig
        );
        
        expect($result)->toBeInstanceOf(ValidationConfig::class);
    });
});
```

### 4. Exception Propagation Testing

```php
describe('Exception Propagation', function () {
    it('validates exception chains through call stack', function () {
        $rootCause = new RuntimeException('Database connection failed');
        $serviceError = new ValidationException('Service failed', 0, $rootCause);
        $commandError = new RuntimeException('Command failed', 0, $serviceError);
        
        testExceptionPropagation(
            fn() => throw $commandError,
            [RuntimeException::class, ValidationException::class, RuntimeException::class]
        );
    });
});
```

### 5. Performance Under Error Conditions

```php
describe('Performance Testing', function () {
    it('maintains performance during high error rates', function () {
        $operation = fn() => throw new RuntimeException('Simulated failure');
        
        $results = testExceptionHandlingUnderLoad($operation, 100, 0.01);
        
        expect($results['exception_count'])->toBe(100);
        expect($results['average_time'])->toBeLessThan(0.01);
    });
});
```

## Best Practices

### 1. Exception Testing Strategy

- **Test All Error Paths**: Ensure every exception throw point has corresponding test coverage
- **Validate Error Messages**: Use exact message matching for critical error scenarios
- **Test Exception Context**: Verify that context data is properly preserved and accessible
- **Test Recovery Mechanisms**: Validate that error recovery and fallback behaviors work correctly

### 2. Error Simulation Guidelines

- **Use Deterministic Errors**: Use the provided simulation helpers for consistent, reproducible errors
- **Test Edge Cases**: Include boundary conditions, null values, and extreme inputs
- **Validate Cleanup**: Ensure resources are properly cleaned up after errors
- **Test Cascading Failures**: Validate behavior when multiple systems fail simultaneously

### 3. Mock Configuration

- **Realistic Behavior**: Configure mocks to simulate realistic failure patterns
- **Intermittent Failures**: Test both consistent and intermittent failure scenarios
- **Resource Limits**: Simulate resource exhaustion and recovery scenarios
- **Timeout Handling**: Test various timeout scenarios and recovery mechanisms

### 4. Integration Testing

- **End-to-End Scenarios**: Test complete error flows from input to output
- **External Dependencies**: Test behavior when external services are unavailable
- **Partial Failures**: Test scenarios where some components fail while others succeed
- **Recovery Validation**: Ensure the system can recover from temporary failures

## Error Categories and Testing Focus

### Critical Error Paths

1. **Network Connectivity**
   - Connection timeouts
   - DNS resolution failures
   - SSL certificate errors
   - Rate limiting responses

2. **File System Operations**
   - File not found errors
   - Permission denied scenarios
   - Disk space exhaustion
   - File corruption handling

3. **Configuration Management**
   - Missing configuration files
   - Invalid configuration formats
   - Missing required keys
   - Type validation errors

4. **Security Validations**
   - Threat detection failures
   - Blacklist service unavailability
   - SSL validation errors
   - Security policy violations

### Performance Considerations

- **Memory Usage**: Monitor memory consumption during error handling
- **Response Times**: Ensure error handling doesn't significantly impact performance
- **Resource Cleanup**: Validate that resources are properly released after errors
- **Concurrent Access**: Test error handling under concurrent load

## Troubleshooting Common Issues

### Test Failures

1. **Flaky Tests**: Use deterministic error simulation instead of real network calls
2. **Memory Leaks**: Ensure proper cleanup in test teardown methods
3. **Timing Issues**: Use controlled timeouts and avoid real-time dependencies
4. **Mock Conflicts**: Use isolated mock instances for each test

### Exception Handling Issues

1. **Missing Context**: Ensure all exceptions include relevant context data
2. **Inconsistent Messages**: Use standardized message formats across exception types
3. **Poor Error Propagation**: Validate that root causes are preserved in exception chains
4. **Resource Leaks**: Implement proper cleanup in finally blocks or using statements

## Examples and Templates

### Basic Exception Test Template

```php
describe('Service Exception Handling', function () {
    beforeEach(function () {
        $this->service = new YourService();
    });

    it('handles specific error scenario', function () {
        // Arrange
        $errorCondition = simulateSpecificError();
        
        // Act & Assert
        expectExceptionWithMessage(
            fn() => $this->service->methodThatShouldFail($errorCondition),
            ExpectedException::class,
            'Expected error message'
        );
    });

    it('recovers gracefully from errors', function () {
        // Arrange
        $failingOperation = fn() => throw new RuntimeException('Service failed');
        $recoveryOperation = fn($e) => 'fallback_result';
        
        // Act & Assert
        testErrorRecovery($failingOperation, $recoveryOperation, 'fallback_result');
    });
});
```

### Integration Test Template

```php
describe('Integration Error Scenarios', function () {
    it('handles complete system failure gracefully', function () {
        // Simulate multiple system failures
        $failures = [
            'network' => simulateNetworkFailure('connection_timeout'),
            'filesystem' => simulateFileSystemError('disk_full'),
            'config' => createConfigurationException('missing_file'),
        ];
        
        foreach ($failures as $system => $failure) {
            $result = testGracefulDegradation(
                fn() => throw $failure,
                "system_{$system}_degraded"
            );
            
            expect($result)->toBe("system_{$system}_degraded");
        }
    });
});
```

This comprehensive exception testing framework ensures robust error handling throughout the validate-links application, providing confidence in the system's ability to handle failures gracefully and recover appropriately.