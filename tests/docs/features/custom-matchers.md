# Custom Matchers Reference

The PEST test suite includes 80+ custom matchers organized into four categories: Link Validation, Performance, Security, and Reporting. These matchers provide domain-specific assertions that make tests more expressive and maintainable.

## Overview

Custom matchers extend PEST's expectation API with specialized assertions for the validate-links project. They are automatically loaded and available in all tests.

```php
// Example usage
expect($validationResult)->toBeValidLink();
expect($operation)->toCompleteWithin(1.0);
expect($url)->toBeSecure();
expect($report)->toMatchReportFormat('json');
```

## Link Validation Matchers

### Basic Link Validation

#### `toBeValidLink()`
Assert that a validation result represents a valid link.

```php
expect($validationResult)->toBeValidLink();

// Usage in tests
it_should_identify_valid_links()
    ->expect(function () {
        $result = $this->linkValidation->validateLink('https://example.com');
        return $result;
    })->toBeValidLink();
```

#### `toBeBrokenLink()`
Assert that a validation result represents a broken link.

```php
expect($validationResult)->toBeBrokenLink();

// Usage in tests
it_should_identify_broken_links()
    ->expect(function () {
        $result = $this->linkValidation->validateLink('https://broken-link.com');
        return $result;
    })->toBeBrokenLink();
```

### Status Assertions

#### `toHaveStatus(string $expectedStatus)`
Assert that a validation result has a specific status.

```php
expect($validationResult)->toHaveStatus('valid');
expect($validationResult)->toHaveStatus('broken');
expect($validationResult)->toHaveStatus('timeout');

// Usage in tests
it_should_have_correct_status()
    ->expect(function () {
        $result = $this->linkValidation->validateLink('https://example.com');
        return $result;
    })->toHaveStatus('valid');
```

#### `toHaveHttpStatus(int $expectedCode)`
Assert that a validation result has a specific HTTP status code.

```php
expect($validationResult)->toHaveHttpStatus(200);
expect($validationResult)->toHaveHttpStatus(404);
expect($validationResult)->toHaveHttpStatus(500);

// Usage in tests
it_should_return_correct_http_status()
    ->expect(function () {
        $result = $this->linkValidation->validateLink('https://httpbin.org/status/200');
        return $result;
    })->toHaveHttpStatus(200);
```

#### HTTP Status Category Matchers

```php
// Success status (2xx)
expect($validationResult)->toBeSuccessfulHttpStatus();

// Redirect status (3xx)
expect($validationResult)->toBeRedirectStatus();

// Client error status (4xx)
expect($validationResult)->toBeClientErrorStatus();

// Server error status (5xx)
expect($validationResult)->toBeServerErrorStatus();
```

### Link Type Assertions

#### `toHaveLinkType(string $expectedType)`
Assert that a validation result has a specific link type.

```php
expect($validationResult)->toHaveLinkType('external');
expect($validationResult)->toHaveLinkType('internal');
expect($validationResult)->toHaveLinkType('anchor');

// Convenience methods
expect($validationResult)->toBeExternalLink();
expect($validationResult)->toBeInternalLink();
expect($validationResult)->toBeAnchorLink();
```

### Performance Assertions

#### `toHaveResponseTime(float $expectedTime)`
Assert that a validation result has a specific response time.

```php
expect($validationResult)->toHaveResponseTime(0.5);

// Usage in tests
it_should_have_fast_response_time()
    ->expect(function () {
        $result = $this->linkValidation->validateLink('https://fast-site.com');
        return $result;
    })->toHaveResponseTimeBelow(1.0);
```

#### `toHaveResponseTimeBelow(float $threshold)`
Assert that a validation result has response time below threshold.

```php
expect($validationResult)->toHaveResponseTimeBelow(2.0);
```

### Error Handling

#### `toHaveValidationError(string $expectedError)`
Assert that a validation result has a specific error message.

```php
expect($validationResult)->toHaveValidationError('Connection timeout');
expect($validationResult)->toHaveValidationError('DNS resolution failed');
```

### Collection Assertions

#### `toBeValidationCollection(int $expectedCount = null)`
Assert that value is a validation result collection with optional count check.

```php
expect($collection)->toBeValidationCollection();
expect($collection)->toBeValidationCollection(5);

// Usage in tests
it_should_return_validation_collection()
    ->expect(function () {
        $urls = ['https://example.com', 'https://test.com'];
        return $this->linkValidation->validateLinks($urls);
    })->toBeValidationCollection(2);
```

#### `toHaveValidResults(int $expectedCount)`
Assert that a validation collection has a specific number of valid results.

```php
expect($collection)->toHaveValidResults(3);
```

#### `toHaveBrokenResults(int $expectedCount)`
Assert that a validation collection has a specific number of broken results.

```php
expect($collection)->toHaveBrokenResults(1);
```

#### `toHaveSuccessRate(float $expectedRate)`
Assert that a validation collection has a specific success rate.

```php
expect($collection)->toHaveSuccessRate(0.95); // 95% success rate
expect($collection)->toHaveSuccessRate(1.0);  // 100% success rate
```

#### `toContainUrl(string $url)`
Assert that a validation collection contains a specific URL.

```php
expect($collection)->toContainUrl('https://example.com');
```

#### `toHaveAverageResponseTime(float $expectedTime)`
Assert that a validation collection has a specific average response time.

```php
expect($collection)->toHaveAverageResponseTime(0.5);
```

## Performance Matchers

### Execution Time Assertions

#### `toCompleteWithin(float $seconds)`
Assert that a callable completes within a specified time.

```php
expect(fn() => $this->fastOperation())->toCompleteWithin(0.1);
expect(fn() => $this->normalOperation())->toCompleteWithin(1.0);

// Usage in tests
it_should_complete_validation_quickly()
    ->group('performance')
    ->expect(function () {
        return fn() => $this->linkValidation->validateLink('https://example.com');
    })->toCompleteWithin(0.5);
```

#### `toExecuteInLessThan(float $seconds)`
Assert that execution time is less than specified duration.

```php
expect($executionTime)->toExecuteInLessThan(1.0);
```

#### `toExecuteInMoreThan(float $seconds)`
Assert that execution time is more than specified duration.

```php
expect($executionTime)->toExecuteInMoreThan(0.1);
```

#### `toExecuteInBetween(float $min, float $max)`
Assert that execution time is between specified durations.

```php
expect($executionTime)->toExecuteInBetween(0.1, 1.0);
```

### Memory Usage Assertions

#### `toUseMemoryLessThan(int $bytes)`
Assert that memory usage is less than specified amount.

```php
expect($memoryUsage)->toUseMemoryLessThan(10 * 1024 * 1024); // 10MB
expect($memoryUsage)->toUseMemoryLessThan(1024 * 1024);      // 1MB

// Usage in tests
it_should_use_memory_efficiently()
    ->group('performance', 'memory')
    ->expect(function () {
        $startMemory = memory_get_usage(true);
        $this->processLargeDataset();
        return memory_get_usage(true) - $startMemory;
    })->toUseMemoryLessThan(50 * 1024 * 1024); // 50MB
```

#### `toUseMemoryMoreThan(int $bytes)`
Assert that memory usage is more than specified amount.

```php
expect($memoryUsage)->toUseMemoryMoreThan(1024); // At least 1KB
```

#### `toUseMemoryBetween(int $min, int $max)`
Assert that memory usage is between specified amounts.

```php
expect($memoryUsage)->toUseMemoryBetween(1024, 10 * 1024 * 1024);
```

### Throughput Assertions

#### `toHaveThroughputAbove(float $operationsPerSecond)`
Assert that throughput is above specified operations per second.

```php
expect($throughput)->toHaveThroughputAbove(100); // 100 ops/sec
```

#### `toHaveThroughputBelow(float $operationsPerSecond)`
Assert that throughput is below specified operations per second.

```php
expect($throughput)->toHaveThroughputBelow(1000); // Less than 1000 ops/sec
```

### General Performance Assertions

#### `toBePerformant()`
Assert that operation meets general performance criteria.

```php
expect(fn() => $this->operation())->toBePerformant();

// Usage in tests
it_should_be_performant()
    ->group('performance')
    ->expect(function () {
        return fn() => $this->linkValidation->validateLinks($this->testUrls);
    })->toBePerformant();
```

#### `toScaleLinearly()`
Assert that operation scales linearly with input size.

```php
expect($scalingMetrics)->toScaleLinearly();
```

#### `toHaveConstantTime()`
Assert that operation has constant time complexity.

```php
expect($complexityMetrics)->toHaveConstantTime();
```

#### `toNotLeakMemory()`
Assert that operation doesn't leak memory.

```php
expect(fn() => $this->repeatedOperation())->toNotLeakMemory();
```

#### `toBeFasterThan(callable $baseline)`
Assert that one operation is faster than another.

```php
expect(fn() => $this->optimizedOperation())
    ->toBeFasterThan(fn() => $this->originalOperation());
```

#### `toUseMemoryEfficiently()`
Assert that operation uses memory efficiently compared to baseline.

```php
expect(fn() => $this->efficientOperation())->toUseMemoryEfficiently();
```

#### `toMeetPerformanceBenchmark(array $benchmark)`
Assert that operation meets a comprehensive performance benchmark.

```php
expect($performanceMetrics)->toMeetPerformanceBenchmark([
    'max_time' => 1.0,
    'max_memory' => 10 * 1024 * 1024,
    'min_throughput' => 100,
]);
```

## Security Matchers

### Basic Security Assertions

#### `toBeSecure()`
Assert that a URL or validation result is secure.

```php
expect($url)->toBeSecure();
expect($validationResult)->toBeSecure();

// Usage in tests
it_should_identify_secure_urls()
    ->expect(function () {
        return 'https://secure-site.com';
    })->toBeSecure();
```

#### `toPassSecurityCheck()`
Assert that a security validation passes.

```php
expect($securityResult)->toPassSecurityCheck();
```

#### `toFailSecurityCheck()`
Assert that a security validation fails.

```php
expect($securityResult)->toFailSecurityCheck();
```

### Protocol Security

#### `toHaveSecureProtocol()`
Assert that URL uses a secure protocol.

```php
expect('https://example.com')->toHaveSecureProtocol();
expect('ftps://example.com')->toHaveSecureProtocol();
```

#### `toHaveInsecureProtocol()`
Assert that URL uses an insecure protocol.

```php
expect('http://example.com')->toHaveInsecureProtocol();
expect('ftp://example.com')->toHaveInsecureProtocol();
```

### Domain Security

#### `toBeBlockedDomain()`
Assert that domain is in blocked list.

```php
expect('malicious.com')->toBeBlockedDomain();
```

#### `toBeAllowedDomain()`
Assert that domain is in allowed list.

```php
expect('trusted.com')->toBeAllowedDomain();
```

#### `toHaveSuspiciousPath()`
Assert that URL path contains suspicious patterns.

```php
expect('https://example.com/admin/../../etc/passwd')->toHaveSuspiciousPath();
```

### Certificate Security

#### `toHaveValidCertificate()`
Assert that URL has a valid SSL certificate.

```php
expect($validationResult)->toHaveValidCertificate();
```

#### `toHaveExpiredCertificate()`
Assert that URL has an expired SSL certificate.

```php
expect($validationResult)->toHaveExpiredCertificate();
```

### Content Security

#### `toContainMaliciousContent()`
Assert that content contains malicious patterns.

```php
expect($content)->toContainMaliciousContent();
```

### Security Headers

#### `toHaveSecurityHeaders()`
Assert that response has required security headers.

```php
expect($response)->toHaveSecurityHeaders();
```

#### `toHaveStrictTransportSecurity()`
Assert that response has HSTS header.

```php
expect($response)->toHaveStrictTransportSecurity();
```

#### `toHaveContentSecurityPolicy()`
Assert that response has CSP header.

```php
expect($response)->toHaveContentSecurityPolicy();
```

#### `toPreventClickjacking()`
Assert that response prevents clickjacking.

```php
expect($response)->toPreventClickjacking();
```

#### `toPreventMimeSniffing()`
Assert that response prevents MIME sniffing.

```php
expect($response)->toPreventMimeSniffing();
```

#### `toHaveXSSProtection()`
Assert that response has XSS protection enabled.

```php
expect($response)->toHaveXSSProtection();
```

### Vulnerability Testing

#### `toBeVulnerableToInjection()`
Assert that input is vulnerable to injection attacks.

```php
expect($userInput)->toBeVulnerableToInjection();
```

#### `toHaveSecureRandomness()`
Assert that value has sufficient randomness for security.

```php
expect($randomValue)->toHaveSecureRandomness();
```

#### `toPassSecurityScan()`
Assert that security scan results pass all checks.

```php
expect($scanResults)->toPassSecurityScan();
```

## Reporting Matchers

### Format Validation

#### `toMatchReportFormat(string $format)`
Assert that report matches expected format.

```php
expect($report)->toMatchReportFormat('json');
expect($report)->toMatchReportFormat('html');
expect($report)->toMatchReportFormat('markdown');

// Usage in tests
it_should_generate_json_report()
    ->expect(function () {
        return $this->reportGenerator->generate('json');
    })->toMatchReportFormat('json');
```

### Content Validation

#### `toContainValidationResults()`
Assert that report contains validation results.

```php
expect($report)->toContainValidationResults();
```

#### `toHaveReportSummary()`
Assert that report has a summary section.

```php
expect($report)->toHaveReportSummary();
```

### Structure Validation

#### `toHaveValidJsonStructure()`
Assert that JSON report has valid structure.

```php
expect($jsonReport)->toHaveValidJsonStructure();
```

#### `toHaveValidHtmlStructure()`
Assert that HTML report has valid structure.

```php
expect($htmlReport)->toHaveValidHtmlStructure();
```

#### `toHaveValidMarkdownStructure()`
Assert that Markdown report has valid structure.

```php
expect($markdownReport)->toHaveValidMarkdownStructure();
```

### Data Validation

#### `toContainStatistics()`
Assert that report contains statistics.

```php
expect($report)->toContainStatistics();
```

#### `toHaveTimestamp()`
Assert that report has a timestamp.

```php
expect($report)->toHaveTimestamp();
```

#### `toHaveMetadata()`
Assert that report has metadata.

```php
expect($report)->toHaveMetadata();
```

#### `toBeValidReport()`
Assert that value is a valid report with all required sections.

```php
expect($report)->toBeValidReport();
```

#### `toHaveErrorDetails()`
Assert that report contains error details for broken links.

```php
expect($report)->toHaveErrorDetails();
```

#### `toHaveSuccessRate(float $expectedRate)`
Assert that report shows a specific success rate.

```php
expect($report)->toHaveSuccessRate(0.95);
```

#### `toBeExportable()`
Assert that report can be exported to a file.

```php
expect($report)->toBeExportable();
```

## Usage Examples

### Combining Matchers

```php
it_should_validate_and_perform_well()
    ->group('integration', 'performance')
    ->expect(function () {
        $startTime = microtime(true);
        $result = $this->linkValidation->validateLink('https://example.com');
        $executionTime = microtime(true) - $startTime;
        
        return [$result, $executionTime];
    })->toMatchArray([
        expect()->toBeValidLink()->toHaveHttpStatus(200),
        expect()->toExecuteInLessThan(1.0),
    ]);
```

### Chaining Matchers

```php
it_should_chain_multiple_assertions()
    ->expect(function () {
        return $this->linkValidation->validateLink('https://secure-example.com');
    })->toBeValidLink()
      ->toHaveHttpStatus(200)
      ->toBeSecure()
      ->toHaveResponseTimeBelow(0.5);
```

### Custom Matcher Combinations

```php
it_should_meet_all_requirements()
    ->expect(function () {
        $report = $this->reportGenerator->generate('json');
        return $report;
    })->toMatchReportFormat('json')
      ->toContainValidationResults()
      ->toHaveReportSummary()
      ->toContainStatistics()
      ->toBeValidReport();
```

## Configuration

Custom matchers are automatically loaded based on configuration in [`pest.config.php`](../../../pest.config.php:190):

```php
'custom_matchers' => [
    'enabled' => true,
    'auto_load' => true,
    'categories' => [
        'link_validation' => 'Tests\\Support\\Matchers\\LinkValidationMatchers',
        'performance' => 'Tests\\Support\\Matchers\\PerformanceMatchers',
        'security' => 'Tests\\Support\\Matchers\\SecurityMatchers',
        'reporting' => 'Tests\\Support\\Matchers\\ReportingMatchers',
    ],
],
```

## Extending Matchers

To add new custom matchers:

1. Create matcher functions in the appropriate category file
2. Register them in [`MatcherRegistry`](../../Support/Matchers/MatcherRegistry.php:13)
3. Update the configuration if needed

```php
// Example: Adding a new matcher
expect()->extend('toBeCustomMatcher', function () {
    // Matcher implementation
    return $this;
});
```

## Related Documentation

- [Performance Testing Guide](performance-testing.md)
- [Security Testing Guide](security-testing.md)
- [Test Organization Guide](test-organization.md)
- [Configuration Reference](../reference/configuration.md)