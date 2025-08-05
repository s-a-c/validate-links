# Complete Matchers Reference

This reference provides comprehensive documentation for all 80+ custom matchers available in the PEST test suite, organized by category with detailed usage examples and parameter descriptions.

## Overview

The custom matchers are automatically loaded and extend PEST's expectation API with domain-specific assertions. They are organized into four main categories:

- **Link Validation Matchers** (20+ matchers) - For validating URLs and link validation results
- **Performance Matchers** (16+ matchers) - For performance testing and benchmarking
- **Security Matchers** (20+ matchers) - For security validation and checks
- **Reporting Matchers** (10+ matchers) - For report generation and format validation

## Link Validation Matchers

### Basic Link Validation

#### `toBeValidLink()`
**Description**: Assert that a validation result represents a valid link.

**Parameters**: None

**Usage**:
```php
expect($validationResult)->toBeValidLink();

// Example
it_should_identify_valid_links()
    ->expect(function () {
        $result = $this->linkValidation->validateLink('https://example.com');
        return $result;
    })->toBeValidLink();
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toBeBrokenLink()`
**Description**: Assert that a validation result represents a broken link.

**Parameters**: None

**Usage**:
```php
expect($validationResult)->toBeBrokenLink();

// Example
it_should_identify_broken_links()
    ->expect(function () {
        $result = $this->linkValidation->validateLink('https://404.example.com');
        return $result;
    })->toBeBrokenLink();
```

**Returns**: `Pest\Expectation` for method chaining

---

### Status Assertions

#### `toHaveStatus(string $expectedStatus)`
**Description**: Assert that a validation result has a specific status.

**Parameters**:
- `$expectedStatus` (string): Expected status value ('valid', 'broken', 'timeout', etc.)

**Usage**:
```php
expect($validationResult)->toHaveStatus('valid');
expect($validationResult)->toHaveStatus('broken');
expect($validationResult)->toHaveStatus('timeout');

// Example
it_should_have_correct_status()
    ->expect(function () {
        $result = $this->linkValidation->validateLink('https://example.com');
        return $result;
    })->toHaveStatus('valid');
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toHaveHttpStatus(int $expectedCode)`
**Description**: Assert that a validation result has a specific HTTP status code.

**Parameters**:
- `$expectedCode` (int): Expected HTTP status code (200, 404, 500, etc.)

**Usage**:
```php
expect($validationResult)->toHaveHttpStatus(200);
expect($validationResult)->toHaveHttpStatus(404);
expect($validationResult)->toHaveHttpStatus(500);

// Example
it_should_return_correct_http_status()
    ->expect(function () {
        $result = $this->linkValidation->validateLink('https://httpbin.org/status/200');
        return $result;
    })->toHaveHttpStatus(200);
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toBeSuccessfulHttpStatus()`
**Description**: Assert that a validation result has a successful HTTP status (2xx).

**Parameters**: None

**Usage**:
```php
expect($validationResult)->toBeSuccessfulHttpStatus();

// Example
it_should_have_successful_status()
    ->expect(function () {
        $result = $this->linkValidation->validateLink('https://httpbin.org/status/201');
        return $result;
    })->toBeSuccessfulHttpStatus();
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toBeRedirectStatus()`
**Description**: Assert that a validation result has a redirect HTTP status (3xx).

**Parameters**: None

**Usage**:
```php
expect($validationResult)->toBeRedirectStatus();

// Example
it_should_handle_redirects()
    ->expect(function () {
        $result = $this->linkValidation->validateLink('https://httpbin.org/status/301');
        return $result;
    })->toBeRedirectStatus();
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toBeClientErrorStatus()`
**Description**: Assert that a validation result has a client error HTTP status (4xx).

**Parameters**: None

**Usage**:
```php
expect($validationResult)->toBeClientErrorStatus();

// Example
it_should_detect_client_errors()
    ->expect(function () {
        $result = $this->linkValidation->validateLink('https://httpbin.org/status/404');
        return $result;
    })->toBeClientErrorStatus();
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toBeServerErrorStatus()`
**Description**: Assert that a validation result has a server error HTTP status (5xx).

**Parameters**: None

**Usage**:
```php
expect($validationResult)->toBeServerErrorStatus();

// Example
it_should_detect_server_errors()
    ->expect(function () {
        $result = $this->linkValidation->validateLink('https://httpbin.org/status/500');
        return $result;
    })->toBeServerErrorStatus();
```

**Returns**: `Pest\Expectation` for method chaining

---

### Link Type Assertions

#### `toHaveLinkType(string $expectedType)`
**Description**: Assert that a validation result has a specific link type.

**Parameters**:
- `$expectedType` (string): Expected link type ('external', 'internal', 'anchor', etc.)

**Usage**:
```php
expect($validationResult)->toHaveLinkType('external');
expect($validationResult)->toHaveLinkType('internal');
expect($validationResult)->toHaveLinkType('anchor');

// Example
it_should_identify_link_types()
    ->expect(function () {
        $result = $this->linkValidation->validateLink('https://external-site.com');
        return $result;
    })->toHaveLinkType('external');
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toBeExternalLink()`
**Description**: Assert that a validation result represents an external link.

**Parameters**: None

**Usage**:
```php
expect($validationResult)->toBeExternalLink();

// Example
it_should_identify_external_links()
    ->expect(function () {
        $result = $this->linkValidation->validateLink('https://external-site.com');
        return $result;
    })->toBeExternalLink();
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toBeInternalLink()`
**Description**: Assert that a validation result represents an internal link.

**Parameters**: None

**Usage**:
```php
expect($validationResult)->toBeInternalLink();

// Example
it_should_identify_internal_links()
    ->expect(function () {
        $result = $this->linkValidation->validateLink('/internal/page');
        return $result;
    })->toBeInternalLink();
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toBeAnchorLink()`
**Description**: Assert that a validation result represents an anchor link.

**Parameters**: None

**Usage**:
```php
expect($validationResult)->toBeAnchorLink();

// Example
it_should_identify_anchor_links()
    ->expect(function () {
        $result = $this->linkValidation->validateLink('#section-1');
        return $result;
    })->toBeAnchorLink();
```

**Returns**: `Pest\Expectation` for method chaining

---

### Performance Assertions

#### `toHaveResponseTime(float $expectedTime)`
**Description**: Assert that a validation result has a specific response time.

**Parameters**:
- `$expectedTime` (float): Expected response time in seconds

**Usage**:
```php
expect($validationResult)->toHaveResponseTime(0.5);

// Example
it_should_have_specific_response_time()
    ->expect(function () {
        $result = $this->linkValidation->validateLink('https://fast-site.com');
        return $result;
    })->toHaveResponseTime(0.3);
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toHaveResponseTimeBelow(float $threshold)`
**Description**: Assert that a validation result has response time below threshold.

**Parameters**:
- `$threshold` (float): Maximum allowed response time in seconds

**Usage**:
```php
expect($validationResult)->toHaveResponseTimeBelow(2.0);

// Example
it_should_have_fast_response_time()
    ->expect(function () {
        $result = $this->linkValidation->validateLink('https://fast-site.com');
        return $result;
    })->toHaveResponseTimeBelow(1.0);
```

**Returns**: `Pest\Expectation` for method chaining

---

### Error Handling

#### `toHaveValidationError(string $expectedError)`
**Description**: Assert that a validation result has a specific error message.

**Parameters**:
- `$expectedError` (string): Expected error message or pattern

**Usage**:
```php
expect($validationResult)->toHaveValidationError('Connection timeout');
expect($validationResult)->toHaveValidationError('DNS resolution failed');

// Example
it_should_have_timeout_error()
    ->expect(function () {
        $result = $this->linkValidation->validateLink('https://timeout-site.com');
        return $result;
    })->toHaveValidationError('Connection timeout');
```

**Returns**: `Pest\Expectation` for method chaining

---

### Collection Assertions

#### `toBeValidationCollection(?int $expectedCount = null)`
**Description**: Assert that value is a validation result collection with optional count check.

**Parameters**:
- `$expectedCount` (int|null): Expected number of results (optional)

**Usage**:
```php
expect($collection)->toBeValidationCollection();
expect($collection)->toBeValidationCollection(5);

// Example
it_should_return_validation_collection()
    ->expect(function () {
        $urls = ['https://example.com', 'https://test.com'];
        return $this->linkValidation->validateLinks($urls);
    })->toBeValidationCollection(2);
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toHaveValidResults(int $expectedCount)`
**Description**: Assert that a validation collection has a specific number of valid results.

**Parameters**:
- `$expectedCount` (int): Expected number of valid results

**Usage**:
```php
expect($collection)->toHaveValidResults(3);

// Example
it_should_have_valid_results()
    ->expect(function () {
        $urls = ['https://example.com', 'https://test.com', 'https://broken.com'];
        return $this->linkValidation->validateLinks($urls);
    })->toHaveValidResults(2);
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toHaveBrokenResults(int $expectedCount)`
**Description**: Assert that a validation collection has a specific number of broken results.

**Parameters**:
- `$expectedCount` (int): Expected number of broken results

**Usage**:
```php
expect($collection)->toHaveBrokenResults(1);

// Example
it_should_have_broken_results()
    ->expect(function () {
        $urls = ['https://example.com', 'https://broken.com'];
        return $this->linkValidation->validateLinks($urls);
    })->toHaveBrokenResults(1);
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toHaveSuccessRate(float $expectedRate)`
**Description**: Assert that a validation collection has a specific success rate.

**Parameters**:
- `$expectedRate` (float): Expected success rate (0.0 to 1.0)

**Usage**:
```php
expect($collection)->toHaveSuccessRate(0.95); // 95% success rate
expect($collection)->toHaveSuccessRate(1.0);  // 100% success rate

// Example
it_should_have_high_success_rate()
    ->expect(function () {
        $urls = array_fill(0, 100, 'https://example.com');
        return $this->linkValidation->validateLinks($urls);
    })->toHaveSuccessRate(1.0);
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toContainUrl(string $url)`
**Description**: Assert that a validation collection contains a specific URL.

**Parameters**:
- `$url` (string): URL to search for in the collection

**Usage**:
```php
expect($collection)->toContainUrl('https://example.com');

// Example
it_should_contain_specific_url()
    ->expect(function () {
        $urls = ['https://example.com', 'https://test.com'];
        return $this->linkValidation->validateLinks($urls);
    })->toContainUrl('https://example.com');
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toHaveAverageResponseTime(float $expectedTime)`
**Description**: Assert that a validation collection has a specific average response time.

**Parameters**:
- `$expectedTime` (float): Expected average response time in seconds

**Usage**:
```php
expect($collection)->toHaveAverageResponseTime(0.5);

// Example
it_should_have_fast_average_response_time()
    ->expect(function () {
        $urls = ['https://fast1.com', 'https://fast2.com'];
        return $this->linkValidation->validateLinks($urls);
    })->toHaveAverageResponseTime(0.3);
```

**Returns**: `Pest\Expectation` for method chaining

---

## Performance Matchers

### Execution Time Assertions

#### `toCompleteWithin(float $seconds)`
**Description**: Assert that a callable completes within a specified time.

**Parameters**:
- `$seconds` (float): Maximum allowed execution time in seconds

**Usage**:
```php
expect(fn() => $this->fastOperation())->toCompleteWithin(0.1);
expect(fn() => $this->normalOperation())->toCompleteWithin(1.0);

// Example
it_should_complete_validation_quickly()
    ->group('performance')
    ->expect(function () {
        return fn() => $this->linkValidation->validateLink('https://example.com');
    })->toCompleteWithin(0.5);
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toExecuteInLessThan(float $seconds)`
**Description**: Assert that execution time is less than specified duration.

**Parameters**:
- `$seconds` (float): Maximum allowed execution time in seconds

**Usage**:
```php
expect($executionTime)->toExecuteInLessThan(1.0);

// Example
it_should_execute_quickly()
    ->expect(function () {
        $start = microtime(true);
        $this->operation();
        return microtime(true) - $start;
    })->toExecuteInLessThan(0.5);
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toExecuteInMoreThan(float $seconds)`
**Description**: Assert that execution time is more than specified duration.

**Parameters**:
- `$seconds` (float): Minimum expected execution time in seconds

**Usage**:
```php
expect($executionTime)->toExecuteInMoreThan(0.1);

// Example
it_should_take_minimum_time()
    ->expect(function () {
        $start = microtime(true);
        usleep(200000); // 200ms
        return microtime(true) - $start;
    })->toExecuteInMoreThan(0.1);
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toExecuteInBetween(float $min, float $max)`
**Description**: Assert that execution time is between specified durations.

**Parameters**:
- `$min` (float): Minimum expected execution time in seconds
- `$max` (float): Maximum allowed execution time in seconds

**Usage**:
```php
expect($executionTime)->toExecuteInBetween(0.1, 1.0);

// Example
it_should_execute_within_range()
    ->expect(function () {
        $start = microtime(true);
        usleep(500000); // 500ms
        return microtime(true) - $start;
    })->toExecuteInBetween(0.4, 0.6);
```

**Returns**: `Pest\Expectation` for method chaining

---

### Memory Usage Assertions

#### `toUseMemoryLessThan(int $bytes)`
**Description**: Assert that memory usage is less than specified amount.

**Parameters**:
- `$bytes` (int): Maximum allowed memory usage in bytes

**Usage**:
```php
expect($memoryUsage)->toUseMemoryLessThan(10 * 1024 * 1024); // 10MB
expect($memoryUsage)->toUseMemoryLessThan(1024 * 1024);      // 1MB

// Example
it_should_use_memory_efficiently()
    ->group('performance', 'memory')
    ->expect(function () {
        $startMemory = memory_get_usage(true);
        $this->processLargeDataset();
        return memory_get_usage(true) - $startMemory;
    })->toUseMemoryLessThan(50 * 1024 * 1024); // 50MB
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toUseMemoryMoreThan(int $bytes)`
**Description**: Assert that memory usage is more than specified amount.

**Parameters**:
- `$bytes` (int): Minimum expected memory usage in bytes

**Usage**:
```php
expect($memoryUsage)->toUseMemoryMoreThan(1024); // At least 1KB

// Example
it_should_use_significant_memory()
    ->expect(function () {
        $startMemory = memory_get_usage(true);
        $largeArray = array_fill(0, 10000, 'data');
        return memory_get_usage(true) - $startMemory;
    })->toUseMemoryMoreThan(1024);
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toUseMemoryBetween(int $min, int $max)`
**Description**: Assert that memory usage is between specified amounts.

**Parameters**:
- `$min` (int): Minimum expected memory usage in bytes
- `$max` (int): Maximum allowed memory usage in bytes

**Usage**:
```php
expect($memoryUsage)->toUseMemoryBetween(1024, 10 * 1024 * 1024);

// Example
it_should_use_reasonable_memory()
    ->expect(function () {
        $startMemory = memory_get_usage(true);
        $this->moderateOperation();
        return memory_get_usage(true) - $startMemory;
    })->toUseMemoryBetween(1024, 1024 * 1024); // 1KB to 1MB
```

**Returns**: `Pest\Expectation` for method chaining

---

### Throughput Assertions

#### `toHaveThroughputAbove(float $operationsPerSecond)`
**Description**: Assert that throughput is above specified operations per second.

**Parameters**:
- `$operationsPerSecond` (float): Minimum expected operations per second

**Usage**:
```php
expect($throughput)->toHaveThroughputAbove(100); // 100 ops/sec

// Example
it_should_have_high_throughput()
    ->expect(function () {
        $start = microtime(true);
        $operations = 0;
        while (microtime(true) - $start < 1.0) {
            $this->quickOperation();
            $operations++;
        }
        return $operations;
    })->toHaveThroughputAbove(1000);
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toHaveThroughputBelow(float $operationsPerSecond)`
**Description**: Assert that throughput is below specified operations per second.

**Parameters**:
- `$operationsPerSecond` (float): Maximum allowed operations per second

**Usage**:
```php
expect($throughput)->toHaveThroughputBelow(1000); // Less than 1000 ops/sec

// Example
it_should_have_controlled_throughput()
    ->expect(function () {
        $start = microtime(true);
        $operations = 0;
        while (microtime(true) - $start < 1.0) {
            $this->controlledOperation();
            $operations++;
        }
        return $operations;
    })->toHaveThroughputBelow(100);
```

**Returns**: `Pest\Expectation` for method chaining

---

### General Performance Assertions

#### `toBePerformant()`
**Description**: Assert that operation meets general performance criteria.

**Parameters**: None

**Usage**:
```php
expect(fn() => $this->operation())->toBePerformant();

// Example
it_should_be_performant()
    ->group('performance')
    ->expect(function () {
        return fn() => $this->linkValidation->validateLinks($this->testUrls);
    })->toBePerformant();
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toScaleLinearly()`
**Description**: Assert that operation scales linearly with input size.

**Parameters**: None

**Usage**:
```php
expect($scalingMetrics)->toScaleLinearly();

// Example
it_should_scale_linearly()
    ->expect(function () {
        $metrics = [];
        foreach ([10, 20, 40] as $size) {
            $start = microtime(true);
            $this->processItems($size);
            $metrics[] = microtime(true) - $start;
        }
        return $metrics;
    })->toScaleLinearly();
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toHaveConstantTime()`
**Description**: Assert that operation has constant time complexity.

**Parameters**: None

**Usage**:
```php
expect($complexityMetrics)->toHaveConstantTime();

// Example
it_should_have_constant_time()
    ->expect(function () {
        $metrics = [];
        foreach ([100, 1000, 10000] as $size) {
            $start = microtime(true);
            $this->constantTimeOperation($size);
            $metrics[] = microtime(true) - $start;
        }
        return $metrics;
    })->toHaveConstantTime();
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toNotLeakMemory()`
**Description**: Assert that operation doesn't leak memory.

**Parameters**: None

**Usage**:
```php
expect(fn() => $this->repeatedOperation())->toNotLeakMemory();

// Example
it_should_not_leak_memory()
    ->expect(function () {
        return function () {
            for ($i = 0; $i < 1000; $i++) {
                $this->createAndDestroyObject();
            }
        };
    })->toNotLeakMemory();
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toBeFasterThan(callable $baseline)`
**Description**: Assert that one operation is faster than another.

**Parameters**:
- `$baseline` (callable): Baseline operation to compare against

**Usage**:
```php
expect(fn() => $this->optimizedOperation())
    ->toBeFasterThan(fn() => $this->originalOperation());

// Example
it_should_be_faster_than_baseline()
    ->expect(function () {
        return fn() => $this->optimizedSort($this->data);
    })->toBeFasterThan(fn() => $this->bubbleSort($this->data));
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toUseMemoryEfficiently()`
**Description**: Assert that operation uses memory efficiently compared to baseline.

**Parameters**: None

**Usage**:
```php
expect(fn() => $this->efficientOperation())->toUseMemoryEfficiently();

// Example
it_should_use_memory_efficiently()
    ->expect(function () {
        return fn() => $this->streamProcessor->process($this->largeFile);
    })->toUseMemoryEfficiently();
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toMeetPerformanceBenchmark(array $benchmark)`
**Description**: Assert that operation meets a comprehensive performance benchmark.

**Parameters**:
- `$benchmark` (array): Benchmark criteria with keys like 'max_time', 'max_memory', 'min_throughput'

**Usage**:
```php
expect($performanceMetrics)->toMeetPerformanceBenchmark([
    'max_time' => 1.0,
    'max_memory' => 10 * 1024 * 1024,
    'min_throughput' => 100,
]);

// Example
it_should_meet_performance_benchmark()
    ->expect(function () {
        $start = microtime(true);
        $startMemory = memory_get_usage(true);
        
        $result = $this->complexOperation();
        
        return [
            'execution_time' => microtime(true) - $start,
            'memory_usage' => memory_get_usage(true) - $startMemory,
            'throughput' => $result['operations_per_second'],
        ];
    })->toMeetPerformanceBenchmark([
        'max_time' => 2.0,
        'max_memory' => 50 * 1024 * 1024,
        'min_throughput' => 50,
    ]);
```

**Returns**: `Pest\Expectation` for method chaining

---

## Security Matchers

### Basic Security Assertions

#### `toBeSecure()`
**Description**: Assert that a URL or validation result is secure.

**Parameters**: None

**Usage**:
```php
expect($url)->toBeSecure();
expect($validationResult)->toBeSecure();

// Example
it_should_identify_secure_urls()
    ->expect(function () {
        return 'https://secure-site.com';
    })->toBeSecure();
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toPassSecurityCheck()`
**Description**: Assert that a security validation passes.

**Parameters**: None

**Usage**:
```php
expect($securityResult)->toPassSecurityCheck();

// Example
it_should_pass_security_validation()
    ->expect(function () {
        return $this->securityValidator->validate('https://secure-site.com');
    })->toPassSecurityCheck();
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toFailSecurityCheck()`
**Description**: Assert that a security validation fails.

**Parameters**: None

**Usage**:
```php
expect($securityResult)->toFailSecurityCheck();

// Example
it_should_fail_security_validation()
    ->expect(function () {
        return $this->securityValidator->validate('http://insecure-site.com');
    })->toFailSecurityCheck();
```

**Returns**: `Pest\Expectation` for method chaining

---

### Protocol Security

#### `toHaveSecureProtocol()`
**Description**: Assert that URL uses a secure protocol.

**Parameters**: None

**Usage**:
```php
expect('https://example.com')->toHaveSecureProtocol();
expect('ftps://example.com')->toHaveSecureProtocol();

// Example
it_should_use_secure_protocol()
    ->expect(function () {
        return 'https://secure-banking.com';
    })->toHaveSecureProtocol();
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toHaveInsecureProtocol()`
**Description**: Assert that URL uses an insecure protocol.

**Parameters**: None

**Usage**:
```php
expect('http://example.com')->toHaveInsecureProtocol();
expect('ftp://example.com')->toHaveInsecureProtocol();

// Example
it_should_detect_insecure_protocol()
    ->expect(function () {
        return 'http://legacy-site.com';
    })->toHaveInsecureProtocol();
```

**Returns**: `Pest\Expectation` for method chaining

---

### Domain Security

#### `toBeBlockedDomain()`
**Description**: Assert that domain is in blocked list.

**Parameters**: None

**Usage**:
```php
expect('malicious.com')->toBeBlockedDomain();

// Example
it_should_identify_blocked_domains()
    ->expect(function () {
        return $this->securityValidator->checkDomain('known-malware.com');
    })->toBeBlockedDomain();
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toBeAllowedDomain()`
**Description**: Assert that domain is in allowed list.

**Parameters**: None

**Usage**:
```php
expect('trusted.com')->toBeAllowedDomain();

// Example
it_should_identify_allowed_domains()
    ->expect(function () {
        return $this->securityValidator->checkDomain('trusted-partner.com');
    })->toBeAllowedDomain();
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toHaveSuspiciousPath()`
**Description**: Assert that URL path contains suspicious patterns.

**Parameters**: None

**Usage**:
```php
expect('https://example.com/admin/../../etc/passwd')->toHaveSuspiciousPath();

// Example
it_should_detect_suspicious_paths()
    ->expect(function () {
        return 'https://example.com/../../../sensitive/file';
    })->toHaveSuspiciousPath();
```

**Returns**: `Pest\Expectation` for method chaining

---

### Certificate Security

#### `toHaveValidCertificate()`
**Description**: Assert that URL has a valid SSL certificate.

**Parameters**: None

**Usage**:
```php
expect($validationResult)->toHaveValidCertificate();

// Example
it_should_have_valid_certificate()
    ->expect(function () {
        return $this->sslValidator->checkCertificate('https://secure-site.com');
    })->toHaveValidCertificate();
```

**Returns**: `Pest\Expectation` for method chaining

---

#### `toHaveExpiredCertificate()`
**Description**: Assert that URL has an expired SSL certificate.

**Parameters**: None

**Usage**:
```php
expect($validationResult)->toHaveExpiredCertificate();

// Example
it_should_detect_expired_certificate()
    ->expect(function () {
        return $this->sslValidator->checkCertificate('https://expired-cert.com');
    })->toHaveExpiredCertificate();
```

**Returns**: `Pest\Expectation` for method chaining

---

### Content Security

#### `toContainMaliciousContent()`
**Description**: Assert that content contains malicious patterns.

**Parameters**: None

**Usage**:
```php
expect($content)->toContainMaliciousContent();

// Example
it_should_detect_malicious_content()
    ->expect(function () {
        return '<script>alert("xss")</script>';
    })->toContainMaliciousContent();
```

**