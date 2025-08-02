# 10. Enums Documentation

**Date:** July 22, 2025  
**Project:** Laravel Zero validate-links Implementation  
**Compliance:** `.ai/guidelines.md` and `.ai/guidelines/` standards

## 10.1. Overview

This document provides comprehensive documentation for PHP 8.4+ enums used in the validate-links Laravel Zero application. Enums provide type safety and better code organization for enumerated values throughout the application.

### 10.1.1. Enum Implementation Strategy

The application uses PHP 8.4+ backed enums to replace string constants and improve type safety:

**Benefits of Enum Usage:**
- **Type Safety:** Compile-time validation of enumerated values
- **IDE Support:** Better autocomplete and refactoring capabilities
- **Documentation:** Self-documenting code with clear value constraints
- **Extensibility:** Easy addition of methods and properties to enum cases

**Enum Categories:**
- **Validation Scopes:** Define types of link validation to perform
- **Output Formats:** Specify report generation formats
- **Link Status:** Represent validation results and error states
- **Command Options:** Standardize command-line option values

## 10.2. ValidationScope Enum

### 10.2.1. Purpose and Usage

**Purpose:** Define the scope of link validation operations with type safety.

**Location:** `app/Enums/ValidationScope.php`

**Complete Implementation:**

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum ValidationScope: string
{
    case INTERNAL = 'internal';
    case EXTERNAL = 'external';
    case ANCHOR = 'anchor';
    case IMAGE = 'image';
    case ALL = 'all';

    /**
     * Get all scope values as array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all scope names as array.
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    /**
     * Check if scope includes external validation.
     */
    public function includesExternal(): bool
    {
        return $this === self::EXTERNAL || $this === self::ALL;
    }

    /**
     * Check if scope includes internal validation.
     */
    public function includesInternal(): bool
    {
        return $this === self::INTERNAL || $this === self::ALL;
    }

    /**
     * Check if scope includes anchor validation.
     */
    public function includesAnchor(): bool
    {
        return $this === self::ANCHOR || $this === self::ALL;
    }

    /**
     * Check if scope includes image validation.
     */
    public function includesImage(): bool
    {
        return $this === self::IMAGE || $this === self::ALL;
    }

    /**
     * Get formatted options for Laravel Prompts select/multiselect.
     */
    public static function getSelectOptions(): array
    {
        return array_map(
            fn($case) => [
                'value' => $case->value,
                'label' => $case->getDescription()
            ],
            self::cases()
        );
    }

    /**
     * Check if a string value is a valid ValidationScope.
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }

    /**
     * Get human-readable description for each scope.
     */
    public function getDescription(): string
    {
        return match($this) {
            self::INTERNAL => 'Validate internal file and directory links',
            self::EXTERNAL => 'Validate external HTTP/HTTPS links',
            self::ANCHOR => 'Validate anchor links within documents',
            self::IMAGE => 'Validate image links and embedded media',
            self::ALL => 'Validate all link types (comprehensive validation)',
        };
    }

    /**
     * Get detailed explanation for help text.
     */
    public function getHelpText(): string
    {
        return match($this) {
            self::INTERNAL => 'Validates links to files and directories within the project',
            self::EXTERNAL => 'Validates HTTP/HTTPS links to external websites',
            self::ANCHOR => 'Validates anchor links pointing to sections within documents',
            self::IMAGE => 'Validates image links and embedded media files',
            self::ALL => 'Performs comprehensive validation of all link types',
        };
    }

    /**
     * Get scopes that should be included when this scope is selected.
     */
    public function getIncludedScopes(): array
    {
        return match($this) {
            self::ALL => [self::INTERNAL, self::EXTERNAL, self::ANCHOR, self::IMAGE],
            default => [$this],
        };
    }

    /**
     * Get validation priority (lower number = higher priority).
     */
    public function getPriority(): int
    {
        return match($this) {
            self::INTERNAL => 1,
            self::ANCHOR => 2,
            self::IMAGE => 3,
            self::EXTERNAL => 4,
            self::ALL => 0
        };
    }

    /**
     * Create from string value with validation.
     */
    public static function fromString(string $value): self
    {
        return self::from(strtolower($value));
    }


}
```

### 10.2.2. Usage Examples

```php
// Basic usage
$scope = ValidationScope::INTERNAL;
echo $scope->value; // 'internal'
echo $scope->getDescription(); // 'Validate internal file and directory links'

// Type-safe command options
function validateWithScope(ValidationScope $scope): void
{
    if ($scope->includesExternal()) {
        // Handle external validation
    }
}

// Array operations
$allScopes = ValidationScope::values(); // ['internal', 'external', 'anchor', 'image', 'all']
$scopeNames = ValidationScope::names(); // ['INTERNAL', 'EXTERNAL', 'ANCHOR', 'IMAGE', 'ALL']

// Validation configuration
$config = ValidationConfig::withDefaults([
    'scope' => ValidationScope::ALL->getIncludedScopes()
]);

// Command line integration
$inputScope = ValidationScope::fromString($input); // Type-safe conversion
```

## 10.3. OutputFormat Enum

### 10.3.1. Purpose and Usage

**Purpose:** Define output formats for reports and validation results.

**Location:** `app/Enums/OutputFormat.php`

**Complete Implementation:**

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum OutputFormat: string
{
    case CONSOLE = 'console';
    case JSON = 'json';
    case HTML = 'html';
    case MARKDOWN = 'markdown';
    case XML = 'xml';
    case CSV = 'csv';

    /**
     * Get file extension for format.
     */
    public function getExtension(): string
    {
        return match($this) {
            self::JSON => 'json',
            self::HTML => 'html',
            self::MARKDOWN => 'md',
            self::XML => 'xml',
            self::CSV => 'csv',
            self::CONSOLE => 'txt'
        };
    }

    /**
     * Get MIME type for format.
     */
    public function getMimeType(): string
    {
        return match($this) {
            self::JSON => 'application/json',
            self::HTML => 'text/html',
            self::MARKDOWN => 'text/markdown',
            self::XML => 'application/xml',
            self::CSV => 'text/csv',
            self::CONSOLE => 'text/plain'
        };
    }

    /**
     * Check if format supports structured data.
     */
    public function isStructured(): bool
    {
        return match($this) {
            self::JSON, self::XML, self::CSV => true,
            self::HTML, self::MARKDOWN, self::CONSOLE => false
        };
    }

    /**
     * Check if format supports styling.
     */
    public function supportsFormatting(): bool
    {
        return match($this) {
            self::HTML, self::MARKDOWN, self::CONSOLE => true,
            self::JSON, self::XML, self::CSV => false
        };
    }

    /**
     * Get formatter class name.
     */
    public function getFormatterClass(): string
    {
        return match($this) {
            self::CONSOLE => 'App\\Services\\Formatters\\ConsoleFormatter',
            self::JSON => 'App\\Services\\Formatters\\JsonFormatter',
            self::HTML => 'App\\Services\\Formatters\\HtmlFormatter',
            self::MARKDOWN => 'App\\Services\\Formatters\\MarkdownFormatter',
            self::XML => 'App\\Services\\Formatters\\XmlFormatter',
            self::CSV => 'App\\Services\\Formatters\\CsvFormatter'
        };
    }

    /**
     * Get default filename for format.
     */
    public function getDefaultFilename(): string
    {
        return match($this) {
            self::CONSOLE => 'validation-output.txt',
            self::JSON => 'validation-report.json',
            self::HTML => 'validation-report.html',
            self::MARKDOWN => 'VALIDATION_REPORT.md',
            self::XML => 'validation-report.xml',
            self::CSV => 'validation-data.csv'
        };
    }

    /**
     * Check if format is suitable for CI/CD integration.
     */
    public function isCiCdFriendly(): bool
    {
        return match($this) {
            self::JSON, self::XML, self::CSV => true,
            self::HTML, self::MARKDOWN, self::CONSOLE => false
        };
    }

    /**
     * Get all format values as array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get formatted options for Laravel Prompts select/multiselect.
     */
    public static function getSelectOptions(): array
    {
        return array_map(
            fn($case) => [
                'value' => $case->value,
                'label' => $case->getDescription()
            ],
            self::cases()
        );
    }

    /**
     * Check if a string value is a valid OutputFormat.
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }

    /**
     * Get human-readable description for each format.
     */
    public function getDescription(): string
    {
        return match($this) {
            self::CONSOLE => 'Console output with colors and formatting',
            self::JSON => 'JSON format for programmatic use and APIs',
            self::HTML => 'HTML report with styling and interactive features',
            self::MARKDOWN => 'Markdown format for documentation integration',
            self::XML => 'XML format for data exchange and processing',
            self::CSV => 'CSV format for spreadsheet analysis and data import',
        };
    }

    /**
     * Get detailed explanation for help text.
     */
    public function getHelpText(): string
    {
        return match($this) {
            self::CONSOLE => 'Displays results directly in the terminal with colors and formatting',
            self::JSON => 'Outputs structured JSON data suitable for APIs and programmatic processing',
            self::HTML => 'Generates a styled HTML report with interactive features and charts',
            self::MARKDOWN => 'Creates markdown documentation that can be included in project docs',
            self::XML => 'Produces XML output for data exchange and integration with other tools',
            self::CSV => 'Exports data in CSV format for analysis in spreadsheet applications',
        };
    }

    /**
     * Get recommended use cases for each format.
     */
    public function getUseCases(): array
    {
        return match($this) {
            self::CONSOLE => ['Interactive development', 'Quick validation', 'CI/CD logs'],
            self::JSON => ['API integration', 'Automated processing', 'Data analysis'],
            self::HTML => ['Team reports', 'Documentation sites', 'Executive summaries'],
            self::MARKDOWN => ['Project documentation', 'README files', 'Wiki pages'],
            self::XML => ['Enterprise integration', 'Data exchange', 'Legacy systems'],
            self::CSV => ['Data analysis', 'Spreadsheet import', 'Statistical processing'],
        };
    }
}
```

### 10.3.2. Laravel Prompts Integration Examples

```php
use function Laravel\Prompts\select;
use function Laravel\Prompts\multiselect;

// Interactive format selection using enum
$selectedFormat = select(
    'Select output format:',
    OutputFormat::getSelectOptions(),
    default: OutputFormat::CONSOLE->value
);

$format = OutputFormat::from($selectedFormat);

// Validation with helpful error messages
if (!OutputFormat::isValid($userInput)) {
    $this->error("Invalid format: {$userInput}");
    $this->info('Valid formats: ' . implode(', ', OutputFormat::values()));
    return 1;
}

// Static command signature (dynamic generation not supported in signature strings)
protected $signature = 'validate
    {--format=console : Output format (console, json, html, markdown, xml, csv)}';
```

### 10.3.3. Usage Examples

```php
// Format selection
$format = OutputFormat::JSON;
$filename = $format->getDefaultFilename(); // 'validation-report.json'
$mimeType = $format->getMimeType(); // 'application/json'

// Conditional logic based on format capabilities
if ($format->isStructured()) {
    // Handle structured data output
    $data = $result->toArray();
} else {
    // Handle formatted text output
    $data = $result->toFormattedString();
}

// Formatter instantiation
$formatterClass = $format->getFormatterClass();
$formatter = app($formatterClass);

// CI/CD integration check
if ($format->isCiCdFriendly()) {
    // Use for automated processing
    $this->info('Format suitable for CI/CD integration');
}
```

## 10.4. LinkStatus Enum

### 10.4.1. Purpose and Usage

**Purpose:** Represent the validation status of individual links with detailed error information.

**Location:** `app/Enums/LinkStatus.php`

**Complete Implementation:**

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum LinkStatus: string
{
    case VALID = 'valid';
    case BROKEN = 'broken';
    case TIMEOUT = 'timeout';
    case FORBIDDEN = 'forbidden';
    case NOT_FOUND = 'not_found';
    case INVALID_FORMAT = 'invalid_format';
    case SECURITY_VIOLATION = 'security_violation';
    case REDIRECT_LOOP = 'redirect_loop';
    case SSL_ERROR = 'ssl_error';
    case DNS_ERROR = 'dns_error';

    /**
     * Check if status indicates a problem.
     */
    public function isBroken(): bool
    {
        return $this !== self::VALID;
    }

    /**
     * Check if status indicates a temporary issue.
     */
    public function isTemporary(): bool
    {
        return match($this) {
            self::TIMEOUT, self::DNS_ERROR => true,
            default => false
        };
    }

    /**
     * Check if status indicates a security issue.
     */
    public function isSecurityIssue(): bool
    {
        return match($this) {
            self::SECURITY_VIOLATION, self::SSL_ERROR => true,
            default => false
        };
    }

    /**
     * Get human-readable description.
     */
    public function getDescription(): string
    {
        return match($this) {
            self::VALID => 'Link is valid and accessible',
            self::BROKEN => 'Link is broken or inaccessible',
            self::TIMEOUT => 'Request timed out',
            self::FORBIDDEN => 'Access forbidden (403)',
            self::NOT_FOUND => 'Resource not found (404)',
            self::INVALID_FORMAT => 'Invalid URL format',
            self::SECURITY_VIOLATION => 'Security policy violation',
            self::REDIRECT_LOOP => 'Redirect loop detected',
            self::SSL_ERROR => 'SSL/TLS certificate error',
            self::DNS_ERROR => 'DNS resolution failed'
        };
    }

    /**
     * Get severity level (1 = low, 5 = critical).
     */
    public function getSeverity(): int
    {
        return match($this) {
            self::VALID => 0,
            self::TIMEOUT, self::DNS_ERROR => 2,
            self::NOT_FOUND, self::INVALID_FORMAT => 3,
            self::FORBIDDEN, self::REDIRECT_LOOP => 4,
            self::BROKEN, self::SECURITY_VIOLATION, self::SSL_ERROR => 5
        };
    }

    /**
     * Get recommended action.
     */
    public function getRecommendedAction(): string
    {
        return match($this) {
            self::VALID => 'No action required',
            self::TIMEOUT => 'Retry validation or increase timeout',
            self::FORBIDDEN => 'Check access permissions or authentication',
            self::NOT_FOUND => 'Update link URL or remove broken link',
            self::INVALID_FORMAT => 'Fix URL syntax and format',
            self::SECURITY_VIOLATION => 'Review security policies and URL safety',
            self::REDIRECT_LOOP => 'Check redirect configuration',
            self::SSL_ERROR => 'Verify SSL certificate validity',
            self::DNS_ERROR => 'Check domain name and DNS configuration',
            self::BROKEN => 'Investigate and fix underlying issue'
        };
    }

    /**
     * Get console color for status display.
     */
    public function getConsoleColor(): string
    {
        return match($this) {
            self::VALID => 'green',
            self::TIMEOUT, self::DNS_ERROR => 'yellow',
            self::NOT_FOUND, self::INVALID_FORMAT => 'magenta',
            self::FORBIDDEN, self::REDIRECT_LOOP => 'cyan',
            default => 'red'
        };
    }

    /**
     * Get all status values as array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get status icon for console display.
     */
    public function getIcon(): string
    {
        return match($this) {
            self::VALID => '✅',
            self::TIMEOUT => '⏱️',
            self::FORBIDDEN => '🚫',
            self::NOT_FOUND => '❌',
            self::INVALID_FORMAT => '⚠️',
            self::SECURITY_VIOLATION => '🔒',
            self::REDIRECT_LOOP => '🔄',
            self::SSL_ERROR => '🔐',
            self::DNS_ERROR => '🌐',
            self::BROKEN => '💥'
        };
    }

    /**
     * Get status group for result organization.
     */
    public function getGroup(): string
    {
        return match($this) {
            self::VALID => 'success',
            self::TIMEOUT, self::DNS_ERROR => 'temporary',
            self::FORBIDDEN, self::NOT_FOUND => 'client_error',
            self::INVALID_FORMAT, self::SECURITY_VIOLATION => 'format_error',
            self::REDIRECT_LOOP, self::SSL_ERROR => 'protocol_error',
            self::BROKEN => 'unknown_error'
        };
    }

    /**
     * Get formatted display text with icon and color.
     */
    public function getFormattedDisplay(): string
    {
        $color = $this->getConsoleColor();
        $icon = $this->getIcon();
        $description = $this->getDescription();

        return "<fg={$color}>{$icon} {$description}</fg>";
    }

    /**
     * Check if status should be retried automatically.
     */
    public function shouldRetry(): bool
    {
        return match($this) {
            self::TIMEOUT, self::DNS_ERROR => true,
            default => false
        };
    }

    /**
     * Get HTTP status code equivalent (if applicable).
     */
    public function getHttpStatusCode(): ?int
    {
        return match($this) {
            self::VALID => 200,
            self::FORBIDDEN => 403,
            self::NOT_FOUND => 404,
            self::TIMEOUT => 408,
            self::REDIRECT_LOOP => 310,
            self::SSL_ERROR => 526,
            default => null
        };
    }
}
```

### 10.4.3. Usage Examples

```php
// Status evaluation
$status = LinkStatus::NOT_FOUND;
if ($status->isBroken()) {
    $this->error("Link validation failed: " . $status->getDescription());
    $this->info("Recommended action: " . $status->getRecommendedAction());
}

// Severity-based handling
$severity = $status->getSeverity();
if ($severity >= 4) {
    // Handle critical issues
    $this->logCriticalError($status);
}

// Console output with colors
$color = $status->getConsoleColor();
$this->line("<fg={$color}>{$status->value}</>");

// Temporary issue retry logic
if ($status->isTemporary()) {
    // Implement retry mechanism
    $this->retryValidation($link);
}
```

## 10.5. Enum Testing Patterns

### 10.5.1. Testing Strategy Overview

**Purpose:** Comprehensive testing patterns for PHP 8.4+ backed enums ensuring type safety, business logic validation, and complete coverage.

**Testing Approach:**
- **Complete Case Coverage**: Verify all enum cases and their values
- **Method Validation**: Test all custom methods return expected values
- **Business Logic**: Validate enum-specific rules and relationships
- **Type Safety**: Ensure proper type handling and validation
- **Edge Cases**: Test invalid inputs and boundary conditions
- **Serialization**: Verify JSON serialization/deserialization

**Testing Trait:** `tests/Traits/EnumTestHelpers.php`

### 10.5.2. ValidationScope Testing Example

**Test File:** `tests/Unit/Enums/ValidationScopeTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\ValidationScope;
use Tests\Traits\EnumTestHelpers;

uses(EnumTestHelpers::class);

describe('ValidationScope Enum', function () {

    test('has all expected cases with correct values', function () {
        $expectedCases = [
            'INTERNAL' => 'internal',
            'EXTERNAL' => 'external',
            'ANCHOR' => 'anchor',
            'IMAGE' => 'image',
            'ALL' => 'all',
        ];

        $this->assertEnumCasesMatch(ValidationScope::class, $expectedCases);
    });

    test('ALL scope includes all other scopes', function () {
        $allScope = ValidationScope::ALL;

        expect($allScope->includesInternal())->toBeTrue();
        expect($allScope->includesExternal())->toBeTrue();
        expect($allScope->includesAnchor())->toBeTrue();
        expect($allScope->includesImage())->toBeTrue();

        $includedScopes = $allScope->getIncludedScopes();
        expect($includedScopes)->toHaveCount(4);
        expect($includedScopes)->toContain(ValidationScope::INTERNAL);
        expect($includedScopes)->toContain(ValidationScope::EXTERNAL);
        expect($includedScopes)->toContain(ValidationScope::ANCHOR);
        expect($includedScopes)->toContain(ValidationScope::IMAGE);
    });

    test('fromString() handles case insensitivity correctly', function () {
        $testCases = [
            'internal' => ValidationScope::INTERNAL,
            'INTERNAL' => ValidationScope::INTERNAL,
            'Internal' => ValidationScope::INTERNAL,
            'all' => ValidationScope::ALL,
            'ALL' => ValidationScope::ALL,
        ];

        foreach ($testCases as $input => $expectedScope) {
            $actualScope = ValidationScope::fromString($input);
            expect($actualScope)->toBe($expectedScope);
        }
    });
});
```

### 10.5.3. LinkStatus Testing Example

**Test File:** `tests/Unit/Enums/LinkStatusTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\LinkStatus;
use Tests\Traits\EnumTestHelpers;

uses(EnumTestHelpers::class);

describe('LinkStatus Enum', function () {

    test('VALID is the only non-broken status', function () {
        expect(LinkStatus::VALID->isBroken())->toBeFalse();

        foreach (LinkStatus::cases() as $case) {
            if ($case !== LinkStatus::VALID) {
                expect($case->isBroken())->toBeTrue("Case {$case->name} should be broken");
            }
        }
    });

    test('only temporary statuses should be retried', function () {
        $temporaryStatuses = [LinkStatus::TIMEOUT, LinkStatus::DNS_ERROR];
        $nonTemporaryStatuses = array_filter(
            LinkStatus::cases(),
            fn($case) => !in_array($case, $temporaryStatuses, true)
        );

        foreach ($temporaryStatuses as $status) {
            expect($status->isTemporary())->toBeTrue();
            expect($status->shouldRetry())->toBeTrue();
        }

        foreach ($nonTemporaryStatuses as $status) {
            expect($status->isTemporary())->toBeFalse();
            expect($status->shouldRetry())->toBeFalse();
        }
    });

    test('security issues have high severity', function () {
        $securityStatuses = [LinkStatus::SECURITY_VIOLATION, LinkStatus::SSL_ERROR];

        foreach ($securityStatuses as $status) {
            expect($status->isSecurityIssue())->toBeTrue();
            expect($status->getSeverity())->toBe(5, "Security issue {$status->name} should have maximum severity");
        }
    });
});
```

### 10.5.4. OutputFormat Testing Example

**Test File:** `tests/Unit/Enums/OutputFormatTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\OutputFormat;
use Tests\Traits\EnumTestHelpers;

uses(EnumTestHelpers::class);

describe('OutputFormat Enum', function () {

    test('structured formats are CI/CD friendly', function () {
        foreach (OutputFormat::cases() as $format) {
            if ($format->isStructured()) {
                expect($format->isCiCdFriendly())->toBeTrue(
                    "Structured format {$format->name} should be CI/CD friendly"
                );
            }
        }
    });

    test('default filenames include appropriate extensions', function () {
        foreach (OutputFormat::cases() as $format) {
            $filename = $format->getDefaultFilename();
            $extension = $format->getExtension();

            expect($filename)->toEndWith(".{$extension}");
        }
    });

    test('formatter class names follow naming convention', function () {
        foreach (OutputFormat::cases() as $format) {
            $className = $format->getFormatterClass();

            expect($className)->toStartWith('App\\Services\\Formatters\\');
            expect($className)->toEndWith('Formatter');
            expect($className)->toContain(ucfirst($format->value));
        }
    });
});
```

### 10.5.5. Testing Best Practices

**1. Complete Coverage:**
```php
// Test all enum cases exist with correct values
$this->assertEnumCasesMatch(MyEnum::class, $expectedCases);

// Test all static methods
$this->assertEnumValuesMethod(MyEnum::class, $expectedValues);
$this->assertEnumNamesMethod(MyEnum::class, $expectedNames);
```

**2. Business Logic Validation:**
```php
// Test enum-specific business rules
test('business logic is consistent', function () {
    foreach (MyEnum::cases() as $case) {
        // Validate relationships between methods
        if ($case->isSpecialType()) {
            expect($case->getSpecialValue())->not->toBeNull();
        }
    }
});
```

**3. Type Safety:**
```php
// Test method return types are consistent
$this->assertEnumMethodConsistentType(MyEnum::class, 'getDescription', 'string');
$this->assertEnumMethodConsistentType(MyEnum::class, 'isActive', 'bool');
$this->assertEnumMethodConsistentType(MyEnum::class, 'getPriority', 'int');
```

**4. Edge Cases:**
```php
// Test invalid inputs
test('handles invalid inputs correctly', function () {
    expect(fn() => MyEnum::from('invalid'))->toThrow(\ValueError::class);
    expect(MyEnum::tryFrom('invalid'))->toBeNull();
    expect(MyEnum::isValid('invalid'))->toBeFalse();
});
```

### 10.5.6. Cross-References

**📖 Related Documentation:**
- **[Testing Documentation](070-testing-documentation.md#enum-testing)** - Complete enum testing patterns and utilities
- **[Implementation Guide](020-implementation-guide.md)** - Enum implementation details and usage
- **[Architecture Overview](030-architecture-overview.md)** - Enum role in application architecture

**🔗 Test Files:**
- **[LinkStatus Tests](../tests/Unit/Enums/LinkStatusTest.php)** - Complete LinkStatus enum test implementation
- **[OutputFormat Tests](../tests/Unit/Enums/OutputFormatTest.php)** - OutputFormat enum test patterns
- **[ValidationScope Tests](../tests/Unit/Enums/ValidationScopeTest.php)** - ValidationScope enum business logic tests
- **[EnumTestHelpers Trait](../tests/Traits/EnumTestHelpers.php)** - Reusable enum testing utilities

**🧪 Running Enum Tests:**
```bash
# Run all enum tests
./vendor/bin/pest tests/Unit/Enums/

# Run specific enum test
./vendor/bin/pest tests/Unit/Enums/LinkStatusTest.php

# Run with coverage
./vendor/bin/pest tests/Unit/Enums/ --coverage --min=95
```

---

*This enums documentation provides comprehensive type-safe enumeration implementations for the validate-links application.*

---

## 📖 Navigation

**[⬅️ Previous: Implementation Plan](090-implementation-plan.md)** | **[🏠 Documentation Home](README.md)** | **[🔝 Top](#10-enums-documentation)**
