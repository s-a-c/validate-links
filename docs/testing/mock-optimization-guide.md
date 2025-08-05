# Mock Optimization Guide

This guide documents the comprehensive mock object optimization implemented across the test suite to improve test reliability, performance, and maintainability.

## Overview

The mock optimization introduces a centralized factory system with realistic behavior simulation, proper lifecycle management, and enhanced error handling capabilities.

## Key Components

### 1. MockFactory

The central factory for creating service mocks with realistic behavior patterns.

```php
use Tests\Support\MockFactory;

// Create a security validation service mock with realistic behavior
$securityMock = MockFactory::createSecurityValidationService([
    'safe_urls' => ['https://example.com', 'https://github.com'],
    'malicious_urls' => ['javascript:alert(1)', 'https://malicious.com'],
    'safe_paths' => ['docs/readme.md', 'src/config.php'],
    'dangerous_paths' => ['../../../etc/passwd', '/var/log/auth.log'],
]);

// Create a link validation service mock
$linkValidationMock = MockFactory::createLinkValidationService();

// Create a reporting service mock
$reportingMock = MockFactory::createReportingService();
```

### 2. HttpMockFactory

Specialized factory for HTTP client mocking with Guzzle MockHandler optimization.

```php
use Tests\Support\HttpMockFactory;

// Create a mock client with predefined responses
$client = HttpMockFactory::createMockClient([
    'success_200',
    'not_found_404',
    'server_error_500',
]);

// Use pre-configured clients for common scenarios
$successClient = HttpMockFactory::createSuccessfulValidationClient();
$mixedClient = HttpMockFactory::createMixedValidationClient();
$redirectClient = HttpMockFactory::createRedirectClient();
$timeoutClient = HttpMockFactory::createTimeoutClient();

// Bind to Laravel service container
HttpMockFactory::bindToContainer($client);
```

### 3. MockDataBuilder

Builder for creating realistic test data objects with proper relationships.

```php
use Tests\Support\MockDataBuilder;

// Create validation configuration with sensible defaults
$config = MockDataBuilder::createValidationConfig([
    'timeout' => 30,
    'max_redirects' => 5,
    'security_checks' => true,
]);

// Create validation results with realistic data
$result = MockDataBuilder::createValidationResult([
    'url' => 'https://example.com',
    'status' => LinkStatus::VALID,
]);

// Create collections with mixed statuses
$collection = MockDataBuilder::createValidationResultCollection([
    'count' => 100,
    'status_distribution' => [
        LinkStatus::VALID => 0.8,      // 80% valid
        LinkStatus::BROKEN => 0.15,    // 15% broken
        LinkStatus::TIMEOUT => 0.05,   // 5% timeout
    ],
]);

// Create report summaries with realistic statistics
$summary = MockDataBuilder::createReportSummary([
    'total_links' => 100,
    'broken_links' => 15,
    'execution_time' => 25.5,
]);
```

### 4. MockLifecycleManager

Manages mock object lifecycle with automatic verification and cleanup.

```php
use Tests\Support\MockLifecycleManager;

// Register a mock with lifecycle management
$mock = Mockery::mock(SomeInterface::class);
MockLifecycleManager::registerMock('service_name', $mock, [
    'call_count' => ['methodName' => 2],
    'unused_mocks' => true,
    'required_calls' => ['requiredMethod'],
]);

// Create managed mocks with automatic lifecycle
$mocks = MockLifecycleManager::createManagedMocks([
    'security' => [
        'interface' => SecurityValidationInterface::class,
        'expectations' => ['unused_mocks' => true],
        'setup' => function($mock) {
            $mock->shouldReceive('validateUrl')->andReturn(true);
        },
    ],
    'reporting' => [
        'interface' => ReportingInterface::class,
        'expectations' => [],
    ],
]);

// Verify and cleanup (handled automatically in Pest.php)
$errors = MockLifecycleManager::verifyAndCleanup();
```

## Enhanced Helper Functions

### Global Helper Functions (Available in all tests)

```php
// Enhanced mock creation helpers
$securityMock = createSecurityValidationMock();
$linkValidationMock = createLinkValidationMock();
$reportingMock = createReportingMock();

// HTTP client helpers
$client = createMockHttpClient(['success_200', 'not_found_404']);
$successClient = createSuccessfulHttpClient();
$mixedClient = createMixedHttpClient();
$redirectClient = createRedirectHttpClient();
$timeoutClient = createTimeoutHttpClient();

// Enhanced data builders
$config = createTestValidationConfig(['timeout' => 30]);
$result = createTestValidationResult('https://example.com', LinkStatus::VALID);
$collection = createTestValidationResultCollection(['count' => 50]);
$summary = createTestReportSummary(['total_links' => 100]);
$statistics = createTestValidationStatistics(['total' => 100, 'valid' => 85]);

// Enhanced file system helpers with automatic cleanup
$filePath = createTestFile('test.md', 'Content here');
$tempDir = createIsolatedTempDirectory('my-test');

// Comprehensive URL test data
$urls = getTestUrls(); // Returns categorized test URLs
$categorizedUrls = getCategorizedTestUrls(); // Returns URLs by expected status
```

## Migration Examples

### Before: Manual Mockery Setup

```php
beforeEach(function () {
    $this->securityService = Mockery::mock(SecurityValidationInterface::class);
    
    // Manual setup of expectations
    $this->securityService->shouldReceive('validateUrl')
        ->with('https://example.com')
        ->andReturn(true)
        ->byDefault();
        
    $this->securityService->shouldReceive('validateUrl')
        ->with('https://malicious.com')
        ->andReturn(false)
        ->byDefault();
        
    // More manual setup...
    
    $this->service = new LinkValidationService($this->securityService);
});

afterEach(function () {
    Mockery::close();
    // Manual cleanup...
});
```

### After: Optimized Factory Setup

```php
beforeEach(function () {
    // Use enhanced mock factory with realistic behavior
    $this->securityService = createSecurityValidationMock([
        'safe_urls' => ['https://example.com'],
        'malicious_urls' => ['https://malicious.com'],
    ]);
    
    $this->service = new LinkValidationService($this->securityService);
});

afterEach(function () {
    // Cleanup handled automatically by MockLifecycleManager
});
```

### Before: Manual HTTP Mocking

```php
function setupHttpMocking(): void
{
    $this->mockResponses = [
        'success_200' => new Response(200, ['Content-Type' => 'text/html'], 'OK'),
        'not_found_404' => new Response(404, ['Content-Type' => 'text/html'], 'Not Found'),
        // More manual response creation...
    ];
}

function configureMockClient(array $responses): void
{
    $mockHandler = new MockHandler($responses);
    $handlerStack = HandlerStack::create($mockHandler);
    $mockClient = new Client(['handler' => $handlerStack]);
    
    app()->bind('http.client', fn() => $mockClient);
}
```

### After: Optimized HTTP Factory

```php
// Simple one-liner for common scenarios
createSuccessfulHttpClient();
createMixedHttpClient();
createRedirectHttpClient();

// Or custom responses
createMockHttpClient(['success_200', 'not_found_404', 'server_error_500']);
```

## Best Practices

### 1. Use Appropriate Factory Methods

```php
// ✅ Good: Use specific factory methods for common scenarios
createSuccessfulHttpClient();
createSecurityValidationMock(['safe_urls' => ['https://example.com']]);

// ❌ Avoid: Manual Mockery setup for common patterns
$mock = Mockery::mock(SecurityValidationInterface::class);
$mock->shouldReceive('validateUrl')->andReturn(true);
```

### 2. Leverage Realistic Data Builders

```php
// ✅ Good: Use data builders for realistic test data
$config = createTestValidationConfig(['timeout' => 30]);
$collection = createTestValidationResultCollection(['count' => 100]);

// ❌ Avoid: Manual object creation with unrealistic data
$config = new ValidationConfig(scopes: [ValidationScope::ALL]);
```

### 3. Use Enhanced File Helpers

```php
// ✅ Good: Use enhanced helpers with automatic cleanup
$testFile = createTestFile('test.md', 'Content');
$tempDir = createIsolatedTempDirectory('my-test');

// ❌ Avoid: Manual file creation without proper cleanup
$tempDir = sys_get_temp_dir() . '/test-' . uniqid();
mkdir($tempDir);
```

### 4. Leverage Categorized Test Data

```php
// ✅ Good: Use categorized test URLs
$urls = getCategorizedTestUrls();
foreach ($urls['valid'] as $url) {
    // Test valid URLs
}

// ❌ Avoid: Hardcoded test data
$urls = ['https://example.com', 'http://test.org'];
```

## Performance Benefits

### 1. Reduced Setup Time
- Factory methods eliminate repetitive mock configuration
- Pre-configured scenarios reduce test setup complexity
- Automatic lifecycle management reduces cleanup overhead

### 2. Memory Optimization
- Proper mock cleanup prevents memory leaks
- Efficient object reuse patterns
- Automatic garbage collection triggers

### 3. Test Execution Speed
- Optimized HTTP mocking with realistic response times
- Reduced reflection usage in command tests
- Streamlined data object creation

## Error Handling and Debugging

### 1. Mock Verification Errors

```php
// Verification errors are automatically logged
$errors = MockLifecycleManager::getVerificationErrors();
if (!empty($errors)) {
    $summary = MockLifecycleManager::getVerificationErrorSummary();
    error_log($summary);
}
```

### 2. HTTP Mock Debugging

```php
// Check remaining requests in mock handler
$remaining = HttpMockFactory::getRemainingRequests();
if ($remaining > 0) {
    error_log("Warning: {$remaining} unused HTTP mock responses");
}
```

### 3. File System Cleanup Issues

```php
// Enhanced cleanup with error handling
safeCleanupDirectory($tempDir); // Logs errors but doesn't fail tests
```

## Testing the Optimizations

### 1. Run Specific Test Groups

```bash
# Test HTTP mocking optimizations
./vendor/bin/pest --group=integration

# Test service layer optimizations
./vendor/bin/pest --group=unit tests/Unit/Services/

# Test command optimizations
./vendor/bin/pest --group=unit tests/Unit/Commands/
```

### 2. Verify Mock Lifecycle

```bash
# Run with verbose output to see mock verification
./vendor/bin/pest --verbose

# Check for memory leaks
./vendor/bin/pest --memory-limit=128M
```

### 3. Performance Benchmarking

```bash
# Compare execution times
time ./vendor/bin/pest tests/Integration/ExternalDependencyIntegrationTest.php
```

## Troubleshooting

### Common Issues

1. **Mock Verification Failures**
   - Check that all expected method calls are made
   - Verify mock expectations match actual usage
   - Use `MockLifecycleManager::getVerificationErrors()` for details

2. **HTTP Mock Exhaustion**
   - Ensure enough mock responses for all requests
   - Use `HttpMockFactory::getRemainingRequests()` to check
   - Consider using repeatable responses for large tests

3. **File System Cleanup Issues**
   - Check directory permissions
   - Verify temp directory paths are correct
   - Use `safeCleanupDirectory()` for error-tolerant cleanup

4. **Memory Issues**
   - Ensure proper mock cleanup in afterEach
   - Use `MockLifecycleManager::reset()` for emergency cleanup
   - Check for circular references in test data

### Debug Mode

Enable debug mode for detailed mock information:

```php
// In test setup
MockLifecycleManager::setVerificationEnabled(true);

// Get detailed statistics
$stats = MockLifecycleManager::getStatistics();
var_dump($stats);
```

## Future Enhancements

### Planned Improvements

1. **Advanced Mock Scenarios**
   - Conditional mock responses based on request parameters
   - Stateful mocks that change behavior over time
   - Mock recording and playback capabilities

2. **Performance Monitoring**
   - Automatic performance regression detection
   - Mock usage analytics and optimization suggestions
   - Memory usage tracking and alerts

3. **Enhanced Error Simulation**
   - More realistic network error patterns
   - Gradual degradation scenarios
   - Recovery testing capabilities

4. **Integration Improvements**
   - Better IDE integration for mock discovery
   - Automatic mock generation from interfaces
   - Visual mock relationship mapping

## Conclusion

The mock optimization provides a robust foundation for reliable, maintainable, and performant tests. By leveraging the factory pattern, realistic data builders, and proper lifecycle management, the test suite now offers:

- **Improved Reliability**: Consistent mock behavior and proper cleanup
- **Enhanced Performance**: Optimized setup/teardown and memory management
- **Better Maintainability**: Centralized mock configuration and reusable patterns
- **Realistic Testing**: Behavior patterns that closely match production scenarios

Continue to use these patterns for new tests and gradually migrate existing tests to take advantage of the optimizations.