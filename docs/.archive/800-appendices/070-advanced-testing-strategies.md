# Advanced Testing Strategies

**Date:** July 23, 2025
**Project:** Laravel Zero validate-links Implementation
**Framework:** Laravel 12.20+ (Current Stable)
**Compliance:** `.ai/guidelines.md` and `.ai/guidelines/` standards

## Overview

**⚠️ IMPORTANT: These testing strategies are OPTIONAL for validate-links development.**

This appendix covers advanced testing strategies beyond the essential unit and feature tests. While these testing approaches provide additional confidence and insights, they are not required for core validate-links functionality.

## Essential vs. Optional Testing

### ✅ **Essential Testing (Required)**
- **Unit Tests**: Test individual service methods and components
- **Feature Tests**: Test complete command workflows
- **Basic Integration**: Test service interactions

### 🧪 **Optional Testing (This Appendix)**
- **Performance Testing**: Load testing and benchmarking
- **Stress Testing**: Resource limits and failure scenarios
- **Accessibility Testing**: WCAG compliance for documentation
- **Documentation Testing**: Automated validation of code examples

## Performance Testing (Optional)

### Purpose
Performance testing ensures validate-links can handle large documentation sets efficiently and identifies bottlenecks before they impact users.

### When to Use
- **Large documentation projects** (1000+ files)
- **Enterprise environments** with performance requirements
- **CI/CD pipelines** that need predictable execution times
- **Resource-constrained environments**

### Pest Stressless Plugin Setup

**Install Pest Stressless:**
```bash
composer require pestphp/pest-plugin-stressless --dev
```

**Configure in tests/Pest.php:**
```php
<?php

use Pest\Stressless\Stressless;

// Configure stress testing
Stressless::configure()
    ->concurrency(10)
    ->duration(30) // seconds
    ->requests(1000);
```

### Performance Test Examples

**File Processing Performance:**
```php
<?php

use App\Services\LinkValidationService;
use Pest\Stressless\Stressless;

it('can process large documentation sets efficiently', function () {
    $service = app(LinkValidationService::class);

    // Create test files
    $testFiles = [];
    for ($i = 0; $i < 100; $i++) {
        $testFiles[] = "test-file-{$i}.md";
    }

    $startTime = microtime(true);
    $startMemory = memory_get_usage();

    foreach ($testFiles as $file) {
        $service->validateFile($file, ['internal', 'external']);
    }

    $endTime = microtime(true);
    $endMemory = memory_get_usage();

    $executionTime = $endTime - $startTime;
    $memoryUsage = $endMemory - $startMemory;

    // Performance assertions
    expect($executionTime)->toBeLessThan(10.0); // 10 seconds max
    expect($memoryUsage)->toBeLessThan(50 * 1024 * 1024); // 50MB max
});

it('handles concurrent external link validation', function () {
    $links = [
        'https://example.com',
        'https://github.com',
        'https://laravel.com',
        // ... more test URLs
    ];

    Stressless::fake([
        'https://example.com' => Stressless::response('OK', 200),
        'https://github.com' => Stressless::response('OK', 200),
        'https://laravel.com' => Stressless::response('OK', 200),
    ]);

    $service = app(LinkValidationService::class);

    $startTime = microtime(true);
    $results = $service->validateExternalLinks($links);
    $endTime = microtime(true);

    expect($endTime - $startTime)->toBeLessThan(5.0); // 5 seconds max
    expect($results)->toHaveCount(count($links));
});
```

**Memory Usage Testing:**
```php
<?php

it('maintains stable memory usage during large file processing', function () {
    $service = app(LinkValidationService::class);
    $initialMemory = memory_get_usage();

    // Process multiple large files
    for ($i = 0; $i < 50; $i++) {
        $largeContent = str_repeat("# Test Content\n[Link](test-{$i}.md)\n", 1000);
        $tempFile = tempnam(sys_get_temp_dir(), 'validate_test_');
        file_put_contents($tempFile, $largeContent);

        $service->validateFile($tempFile, ['internal']);

        unlink($tempFile);

        // Check for memory leaks
        $currentMemory = memory_get_usage();
        $memoryIncrease = $currentMemory - $initialMemory;

        expect($memoryIncrease)->toBeLessThan(10 * 1024 * 1024); // 10MB max increase
    }
});
```

### Benchmarking Tests

**Create tests/Benchmark/ValidationBenchmark.php:**
```php
<?php

namespace Tests\Benchmark;

use App\Services\LinkValidationService;

class ValidationBenchmark
{
    private LinkValidationService $service;

    public function setUp(): void
    {
        $this->service = app(LinkValidationService::class);
    }

    public function benchInternalLinkValidation(): void
    {
        $content = str_repeat("[Link](test.md)\n", 100);
        $this->service->validateInternalLinks(
            $this->service->extractLinks($content),
            __DIR__
        );
    }

    public function benchExternalLinkValidation(): void
    {
        $links = array_fill(0, 10, 'https://example.com');
        $this->service->validateExternalLinks($links);
    }

    public function benchLargeFileProcessing(): void
    {
        $content = str_repeat("# Section\n[Link](file.md)\n", 1000);
        $tempFile = tempnam(sys_get_temp_dir(), 'bench_');
        file_put_contents($tempFile, $content);

        $this->service->validateFile($tempFile, ['internal']);

        unlink($tempFile);
    }
}
```

## Stress Testing (Optional)

### Purpose
Stress testing validates application behavior under extreme conditions and resource constraints.

### Resource Limit Testing

**CPU Stress Tests:**
```php
<?php

it('handles CPU-intensive operations gracefully', function () {
    $service = app(LinkValidationService::class);

    // Create CPU-intensive scenario
    $links = array_fill(0, 1000, 'https://example.com');

    $startTime = microtime(true);
    $results = $service->validateExternalLinks($links);
    $endTime = microtime(true);

    // Should complete within reasonable time even under load
    expect($endTime - $startTime)->toBeLessThan(60.0); // 1 minute max
    expect($results)->toHaveCount(1000);
});

it('recovers from memory pressure', function () {
    $service = app(LinkValidationService::class);

    // Simulate memory pressure
    $largeData = [];
    for ($i = 0; $i < 100; $i++) {
        $largeData[] = str_repeat('x', 1024 * 1024); // 1MB strings
    }

    // Should still function under memory pressure
    $result = $service->validateFile('test.md', ['internal']);

    expect($result)->toBeArray();

    // Clean up
    unset($largeData);
});
```

**Failure Scenario Testing:**
```php
<?php

it('handles network failures gracefully', function () {
    $service = app(LinkValidationService::class);

    // Simulate network failures
    Http::fake([
        '*' => Http::response('', 500), // All requests fail
    ]);

    $links = ['https://example.com', 'https://test.com'];
    $results = $service->validateExternalLinks($links);

    // Should handle failures without crashing
    expect($results)->toHaveCount(2);
    foreach ($results as $result) {
        expect($result['status'])->toBe('broken');
    }
});

it('handles file system errors', function () {
    $service = app(LinkValidationService::class);

    // Test with non-existent file
    $result = $service->validateFile('/non/existent/file.md', ['internal']);

    expect($result)->toBeArray();
    expect($result['error'])->toContain('file not found');
});
```

## Accessibility Testing (Optional)

### Purpose
Ensure generated HTML reports and documentation meet WCAG 2.1 AA accessibility standards.

### When to Use
- **Public-facing documentation** that must be accessible
- **Government or enterprise** projects with accessibility requirements
- **Inclusive design** initiatives

### Accessibility Test Setup

**Install accessibility testing tools:**
```bash
npm install --save-dev axe-core puppeteer
```

**Create tests/Feature/AccessibilityTest.php:**
```php
<?php

use App\Services\Formatters\HtmlFormatter;

it('generates accessible HTML reports', function () {
    $formatter = app(HtmlFormatter::class);
    $validationResult = createTestValidationResult();

    $html = $formatter->format($validationResult, []);

    // Save to temporary file for testing
    $tempFile = tempnam(sys_get_temp_dir(), 'accessibility_test_') . '.html';
    file_put_contents($tempFile, $html);

    // Run accessibility checks using Node.js script
    $output = shell_exec("node tests/accessibility-check.js {$tempFile}");
    $results = json_decode($output, true);

    expect($results['violations'])->toBeEmpty();
    expect($results['passes'])->not->toBeEmpty();

    unlink($tempFile);
});

function createTestValidationResult(): array
{
    return [
        'summary' => [
            'total_files' => 5,
            'total_links' => 20,
            'broken_links' => 2,
        ],
        'files' => [
            [
                'path' => 'test.md',
                'links' => [
                    ['url' => 'valid.md', 'status' => 'valid'],
                    ['url' => 'broken.md', 'status' => 'broken'],
                ],
            ],
        ],
    ];
}
```

**Create tests/accessibility-check.js:**
```javascript
const axe = require('axe-core');
const puppeteer = require('puppeteer');
const fs = require('fs');

async function checkAccessibility(filePath) {
    const browser = await puppeteer.launch();
    const page = await browser.newPage();

    const html = fs.readFileSync(filePath, 'utf8');
    await page.setContent(html);

    const results = await page.evaluate(async () => {
        return await axe.run();
    });

    await browser.close();

    console.log(JSON.stringify({
        violations: results.violations,
        passes: results.passes.length
    }));
}

const filePath = process.argv[2];
checkAccessibility(filePath);
```

## Documentation Testing (Optional)

### Purpose
Automated validation of code examples and documentation integrity to ensure all examples work correctly.

### When to Use
- **Documentation-heavy projects** with many code examples
- **API documentation** that must stay current with code changes
- **Tutorial content** that needs to be verified regularly

### Code Example Validation

**Create tests/Feature/DocumentationTest.php:**
```php
<?php

it('validates all code examples in documentation', function () {
    $docFiles = glob(__DIR__ . '/../../docs/**/*.md');

    foreach ($docFiles as $file) {
        $content = file_get_contents($file);
        $codeBlocks = extractCodeBlocks($content);

        foreach ($codeBlocks as $block) {
            if ($block['language'] === 'php') {
                validatePhpCode($block['code'], $file);
            } elseif ($block['language'] === 'bash') {
                validateBashCommand($block['code'], $file);
            }
        }
    }
});

function extractCodeBlocks(string $content): array
{
    $blocks = [];
    $pattern = '/```(\w+)\n(.*?)\n```/s';

    preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $blocks[] = [
            'language' => $match[1],
            'code' => $match[2],
        ];
    }

    return $blocks;
}

function validatePhpCode(string $code, string $file): void
{
    // Skip incomplete examples
    if (str_contains($code, '// ...') || str_contains($code, '...')) {
        return;
    }

    // Add PHP opening tag if missing
    if (!str_starts_with($code, '<?php')) {
        $code = "<?php\n" . $code;
    }

    // Validate syntax
    $tempFile = tempnam(sys_get_temp_dir(), 'php_syntax_check_');
    file_put_contents($tempFile, $code);

    $output = shell_exec("php -l {$tempFile} 2>&1");

    expect($output)->toContain('No syntax errors detected')
        ->or->toContain('Errors parsing')
        ->withMessage("PHP syntax error in {$file}: {$output}");

    unlink($tempFile);
}

function validateBashCommand(string $command, string $file): void
{
    // Skip dangerous or environment-specific commands
    $skipPatterns = [
        'rm -rf',
        'sudo',
        'composer global',
        'npm install -g',
    ];

    foreach ($skipPatterns as $pattern) {
        if (str_contains($command, $pattern)) {
            return;
        }
    }

    // Validate command syntax (basic check)
    $lines = explode("\n", $command);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || str_starts_with($line, '#')) {
            continue;
        }

        // Check for basic command structure
        expect($line)->toMatch('/^[a-zA-Z0-9_\-\.\/\s]+$/')
            ->withMessage("Invalid bash command in {$file}: {$line}");
    }
}
```

### Link Integrity Testing

**Enhanced link validation for documentation:**
```php
<?php

it('validates all internal links in documentation', function () {
    $service = app(LinkValidationService::class);
    $docFiles = glob(__DIR__ . '/../../docs/**/*.md');

    $allBrokenLinks = [];

    foreach ($docFiles as $file) {
        $results = $service->validateFile($file, ['internal', 'anchor']);

        foreach ($results as $result) {
            if ($result['status'] !== 'valid') {
                $allBrokenLinks[] = [
                    'file' => $file,
                    'link' => $result['url'],
                    'status' => $result['status'],
                    'error' => $result['error'] ?? null,
                ];
            }
        }
    }

    if (!empty($allBrokenLinks)) {
        $errorMessage = "Found broken links in documentation:\n";
        foreach ($allBrokenLinks as $link) {
            $errorMessage .= "- {$link['file']}: {$link['link']} ({$link['status']})\n";
        }

        fail($errorMessage);
    }

    expect($allBrokenLinks)->toBeEmpty();
});

it('validates documentation cross-references', function () {
    $docFiles = glob(__DIR__ . '/../../docs/**/*.md');
    $allFiles = array_map('basename', $docFiles);

    foreach ($docFiles as $file) {
        $content = file_get_contents($file);

        // Find all markdown links
        preg_match_all('/\[([^\]]+)\]\(([^)]+)\)/', $content, $matches);

        foreach ($matches[2] as $link) {
            // Skip external links
            if (str_starts_with($link, 'http')) {
                continue;
            }

            // Check if referenced file exists
            $referencedFile = basename($link);
            if (str_ends_with($referencedFile, '.md')) {
                expect($allFiles)->toContain($referencedFile)
                    ->withMessage("Referenced file not found: {$link} in {$file}");
            }
        }
    }
});
```

## Implementation Tasks (Optional)

**⚠️ Only implement these if you need advanced testing capabilities:**

| Task | Description | Estimated Time | Prerequisites |
|------|-------------|----------------|---------------|
| Performance Testing Setup | Install and configure Pest Stressless plugin | 1 hour | Basic Pest setup |
| Benchmarking Suite | Create performance benchmarks for core operations | 3 hours | Performance setup |
| Stress Testing | Implement resource limit and failure scenario tests | 2 hours | Benchmarking |
| Accessibility Testing | Set up WCAG compliance testing for HTML output | 2 hours | Node.js environment |
| Documentation Testing | Automated validation of code examples and links | 2 hours | Documentation structure |

## Best Practices

### When to Use Advanced Testing

- ✅ **Performance-critical applications** with SLA requirements
- ✅ **Large-scale deployments** serving many users
- ✅ **Accessibility-required projects** (government, public sector)
- ✅ **Documentation-heavy projects** with many code examples

### When to Skip Advanced Testing

- ✅ **Small internal tools** with limited scope
- ✅ **Prototype or proof-of-concept** projects
- ✅ **Resource-constrained environments** with limited CI/CD time
- ✅ **Simple validation tasks** without performance requirements

## CI/CD Integration (Optional)

**GitHub Actions for advanced testing:**
```yaml
# .github/workflows/advanced-testing.yml - Optional
name: Advanced Testing Suite

on:
  schedule:
    - cron: '0 2 * * 0' # Weekly on Sunday at 2 AM
  workflow_dispatch: # Manual trigger

jobs:
  performance-testing:
    runs-on: ubuntu-latest

    steps:
    - uses: actions/checkout@v3

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: 8.4
        extensions: dom, curl, libxml, mbstring, zip

    - name: Install dependencies
      run: composer install --no-progress --prefer-dist --optimize-autoloader

    - name: Run performance tests
      run: ./vendor/bin/pest --group=performance

    - name: Run stress tests
      run: ./vendor/bin/pest --group=stress

  accessibility-testing:
    runs-on: ubuntu-latest

    steps:
    - uses: actions/checkout@v3

    - name: Setup Node.js
      uses: actions/setup-node@v3
      with:
        node-version: '20'

    - name: Install accessibility tools
      run: npm install axe-core puppeteer

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: 8.4

    - name: Install dependencies
      run: composer install --no-progress --prefer-dist --optimize-autoloader

    - name: Run accessibility tests
      run: ./vendor/bin/pest --group=accessibility
```

## Troubleshooting

### Common Issues

**Performance tests failing:**
- Adjust time/memory thresholds based on environment
- Use smaller test datasets for CI/CD
- Consider environment-specific configurations

**Accessibility tests not working:**
- Verify Node.js and npm installation
- Check puppeteer browser dependencies
- Ensure HTML output is valid

**Documentation tests breaking:**
- Update code examples when APIs change
- Use version-specific documentation
- Skip environment-specific examples

---

*This appendix covers optional advanced testing strategies. The core validate-links application works perfectly with basic unit and feature tests.*

---

## 📖 Navigation

**[⬅️ Previous: Advanced Code Quality Tools](060-advanced-code-quality-tools.md)** | **[🏠 Documentation Home](../README.md)** | **[🔝 Top](#advanced-testing-strategies)**
