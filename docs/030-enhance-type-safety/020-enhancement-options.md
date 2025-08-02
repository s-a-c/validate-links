# 2. Type Safety Enhancement Options

<details>
<summary>Table of Contents</summary>

- [2. Type Safety Enhancement Options](#2-type-safety-enhancement-options)
  - [2.1. Overview](#21-overview)
  - [2.2. DTO Implementation Opportunities](#22-dto-implementation-opportunities)
  - [2.3. Enum Conversion Analysis](#23-enum-conversion-analysis)
  - [2.4. Interface and Trait Composition](#24-interface-and-trait-composition)
  - [2.5. Generics and Union Types](#25-generics-and-union-types)
  - [2.6. Nullable vs Non-nullable Types](#26-nullable-vs-non-nullable-types)

</details>

## 2.1. Overview

This document analyzes specific enhancement opportunities to maximize type safety in the validate-links application. Based on the current state analysis, we identify concrete improvements including DTO implementations, enum conversions, and advanced PHP 8+ type features.

**Enhancement Categories:**
- Data Transfer Objects (DTOs) to replace array structures
- Enum conversions for string constants
- Interface and trait composition patterns
- Modern PHP 8+ type features
- Strict typing improvements

## 2.2. DTO Implementation Opportunities

### 2.2.1. Critical Array Structures for DTO Conversion

**ValidationResultCollection DTO**
```php
// Current usage in LinkValidationService
public function validateFiles(array $files, ValidationConfig $config): array
{
    return array_map(fn($file) => $this->validateFile($file, $config), $files);
}

// Proposed DTO implementation
final readonly class ValidationResultCollection implements Countable, IteratorAggregate
{
    public function __construct(
        private array $results = []
    ) {
        $this->validateResults();
    }

    public static function fromArray(array $results): self
    {
        return new self(array_map(
            fn($result) => $result instanceof ValidationResult ? $result : ValidationResult::fromArray($result),
            $results
        ));
    }

    public function getSuccessful(): self
    {
        return new self(array_filter($this->results, fn($result) => $result->isSuccessful()));
    }

    public function getFailed(): self
    {
        return new self(array_filter($this->results, fn($result) => !$result->isSuccessful()));
    }

    public function count(): int
    {
        return count($this->results);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->results);
    }
}
```

**LinkCollection DTO**
```php
// Current usage - generic arrays
public function extractLinks(string $content): array
public function categorizeLinks(array $links): array

// Proposed DTO
final readonly class LinkCollection implements Countable, IteratorAggregate
{
    public function __construct(
        private array $links = []
    ) {
        $this->validateLinks();
    }

    public static function fromContent(string $content): self
    {
        // Extract links and create collection
    }

    public function getInternal(): self
    {
        return new self(array_filter($this->links, fn($link) => $link->isInternal()));
    }

    public function getExternal(): self
    {
        return new self(array_filter($this->links, fn($link) => $link->isExternal()));
    }

    public function getAnchors(): self
    {
        return new self(array_filter($this->links, fn($link) => $link->hasAnchor()));
    }
}
```

**ValidationStatistics DTO**
```php
// Current usage in StatisticsService
public function getStatusStatistics(): array
{
    return [
        'total' => $this->totalLinks,
        'valid' => $this->validLinks,
        'broken' => $this->brokenLinks,
        'timeouts' => $this->timeoutLinks,
        'security_risks' => $this->securityRisks,
    ];
}

// Proposed DTO
final readonly class ValidationStatistics
{
    public function __construct(
        private int $total,
        private int $valid,
        private int $broken,
        private int $timeouts,
        private int $securityRisks,
        private int $redirects = 0,
        private float $successRate = 0.0,
        private float $averageResponseTime = 0.0
    ) {
        $this->validateStatistics();
    }

    public static function fromCounts(
        int $total,
        int $valid,
        int $broken,
        int $timeouts,
        int $securityRisks,
        int $redirects = 0
    ): self {
        $successRate = $total > 0 ? ($valid + $redirects) / $total * 100 : 0.0;
        
        return new self(
            total: $total,
            valid: $valid,
            broken: $broken,
            timeouts: $timeouts,
            securityRisks: $securityRisks,
            redirects: $redirects,
            successRate: $successRate
        );
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getSuccessRate(): float
    {
        return $this->successRate;
    }

    public function hasIssues(): bool
    {
        return $this->broken > 0 || $this->timeouts > 0 || $this->securityRisks > 0;
    }

    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'valid' => $this->valid,
            'broken' => $this->broken,
            'timeouts' => $this->timeouts,
            'security_risks' => $this->securityRisks,
            'redirects' => $this->redirects,
            'success_rate' => $this->successRate,
            'average_response_time' => $this->averageResponseTime,
        ];
    }
}
```

**Link DTO**
```php
// Current usage - arrays for link data
$link = [
    'url' => $url,
    'type' => 'internal',
    'anchor' => $anchor,
    'line' => $lineNumber,
    'file' => $filePath
];

// Proposed DTO
final readonly class Link
{
    public function __construct(
        private string $url,
        private LinkType $type,
        private ?string $anchor = null,
        private ?int $line = null,
        private ?string $file = null,
        private ?string $text = null
    ) {
        $this->validateUrl();
    }

    public static function internal(
        string $url,
        ?string $anchor = null,
        ?int $line = null,
        ?string $file = null
    ): self {
        return new self(
            url: $url,
            type: LinkType::INTERNAL,
            anchor: $anchor,
            line: $line,
            file: $file
        );
    }

    public static function external(string $url, ?int $line = null, ?string $file = null): self
    {
        return new self(
            url: $url,
            type: LinkType::EXTERNAL,
            line: $line,
            file: $file
        );
    }

    public function isInternal(): bool
    {
        return $this->type === LinkType::INTERNAL;
    }

    public function isExternal(): bool
    {
        return $this->type === LinkType::EXTERNAL;
    }

    public function hasAnchor(): bool
    {
        return $this->anchor !== null;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getAnchor(): ?string
    {
        return $this->anchor;
    }
}
```

### 2.2.2. Formatter-Specific DTOs

**FormatterInput DTO**
```php
// Current usage - generic arrays passed to formatters
public function format(array $results): string

// Proposed DTO
final readonly class FormatterInput
{
    public function __construct(
        private ValidationResultCollection $results,
        private FormatterOptions $options,
        private ValidationStatistics $statistics
    ) {}

    public static function create(
        ValidationResultCollection $results,
        FormatterOptions $options
    ): self {
        $statistics = ValidationStatistics::fromResults($results);
        
        return new self($results, $options, $statistics);
    }

    public function getResults(): ValidationResultCollection
    {
        return $this->results;
    }

    public function getOptions(): FormatterOptions
    {
        return $this->options;
    }

    public function getStatistics(): ValidationStatistics
    {
        return $this->statistics;
    }
}
```

## 2.3. Enum Conversion Analysis

### 2.3.1. String Constants for Enum Conversion

**LinkType Enum**
```php
// Current usage - string constants or magic strings
if ($linkType === 'internal') {
    // handle internal link
}

// Proposed enum
enum LinkType: string
{
    case INTERNAL = 'internal';
    case EXTERNAL = 'external';
    case ANCHOR = 'anchor';
    case CROSS_REFERENCE = 'cross_reference';

    public function isLocal(): bool
    {
        return match ($this) {
            self::INTERNAL, self::ANCHOR, self::CROSS_REFERENCE => true,
            self::EXTERNAL => false,
        };
    }

    public function requiresNetworkAccess(): bool
    {
        return $this === self::EXTERNAL;
    }

    public function getValidationStrategy(): string
    {
        return match ($this) {
            self::INTERNAL => 'file_exists',
            self::EXTERNAL => 'http_request',
            self::ANCHOR => 'content_search',
            self::CROSS_REFERENCE => 'cross_file_validation',
        };
    }
}
```

**HttpMethod Enum**
```php
// Current usage - string constants in HTTP requests
$method = 'GET';
$method = 'HEAD';

// Proposed enum
enum HttpMethod: string
{
    case GET = 'GET';
    case HEAD = 'HEAD';
    case POST = 'POST';
    case OPTIONS = 'OPTIONS';

    public function isIdempotent(): bool
    {
        return match ($this) {
            self::GET, self::HEAD, self::OPTIONS => true,
            self::POST => false,
        };
    }

    public function hasBody(): bool
    {
        return match ($this) {
            self::POST => true,
            self::GET, self::HEAD, self::OPTIONS => false,
        };
    }
}
```

**FileType Enum**
```php
// Current usage - file extension checks
if (str_ends_with($file, '.md')) {
    // handle markdown
}

// Proposed enum
enum FileType: string
{
    case MARKDOWN = '.md';
    case HTML = '.html';
    case TEXT = '.txt';
    case PHP = '.php';
    case JSON = '.json';

    public static function fromExtension(string $extension): ?self
    {
        return match (strtolower($extension)) {
            '.md', '.markdown' => self::MARKDOWN,
            '.html', '.htm' => self::HTML,
            '.txt' => self::TEXT,
            '.php' => self::PHP,
            '.json' => self::JSON,
            default => null,
        };
    }

    public function supportsLinkExtraction(): bool
    {
        return match ($this) {
            self::MARKDOWN, self::HTML => true,
            self::TEXT, self::PHP, self::JSON => false,
        };
    }

    public function getParser(): string
    {
        return match ($this) {
            self::MARKDOWN => 'MarkdownParser',
            self::HTML => 'HtmlParser',
            self::TEXT => 'TextParser',
            self::PHP => 'PhpParser',
            self::JSON => 'JsonParser',
        };
    }
}
```

**ValidationLevel Enum**
```php
// Current usage - string or integer levels
$level = 'strict';
$level = 'normal';

// Proposed enum
enum ValidationLevel: string
{
    case STRICT = 'strict';
    case NORMAL = 'normal';
    case LENIENT = 'lenient';
    case MINIMAL = 'minimal';

    public function getTimeoutMultiplier(): float
    {
        return match ($this) {
            self::STRICT => 2.0,
            self::NORMAL => 1.0,
            self::LENIENT => 0.8,
            self::MINIMAL => 0.5,
        };
    }

    public function shouldValidateAnchors(): bool
    {
        return match ($this) {
            self::STRICT, self::NORMAL => true,
            self::LENIENT, self::MINIMAL => false,
        };
    }

    public function shouldFollowRedirects(): bool
    {
        return match ($this) {
            self::STRICT => false,
            self::NORMAL, self::LENIENT, self::MINIMAL => true,
        };
    }
}
```

### 2.3.2. Enum Usage Locations

**Files requiring enum conversion:**
1. **app/Services/LinkValidationService.php** - Lines with string type checks
2. **app/Services/Formatters/*.php** - Output format handling
3. **app/Commands/*.php** - Command option validation
4. **config/validate-links.php** - Configuration constants

## 2.4. Interface and Trait Composition

### 2.4.1. Trait-Based Composition Opportunities

**ValidationTrait**
```php
// Current pattern - methods duplicated across services
trait ValidationTrait
{
    protected function validateUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    protected function normalizeUrl(string $url): string
    {
        return rtrim($url, '/');
    }

    protected function extractDomain(string $url): string
    {
        return parse_url($url, PHP_URL_HOST) ?? '';
    }

    protected function isSecureUrl(string $url): bool
    {
        return str_starts_with($url, 'https://');
    }
}
```

**StatisticsTrait**
```php
trait StatisticsTrait
{
    private array $statistics = [];

    protected function incrementStat(string $key): void
    {
        $this->statistics[$key] = ($this->statistics[$key] ?? 0) + 1;
    }

    protected function setStat(string $key, mixed $value): void
    {
        $this->statistics[$key] = $value;
    }

    protected function getStatistics(): ValidationStatistics
    {
        return ValidationStatistics::fromArray($this->statistics);
    }

    protected function resetStatistics(): void
    {
        $this->statistics = [];
    }
}
```

**CacheableTrait**
```php
trait CacheableTrait
{
    private array $cache = [];

    protected function getCached(string $key): mixed
    {
        return $this->cache[$key] ?? null;
    }

    protected function setCached(string $key, mixed $value): void
    {
        $this->cache[$key] = $value;
    }

    protected function hasCached(string $key): bool
    {
        return array_key_exists($key, $this->cache);
    }

    protected function clearCache(): void
    {
        $this->cache = [];
    }
}
```

### 2.4.2. Interface Segregation Improvements

**Specialized Validation Interfaces**
```php
// Current - large interface
interface LinkValidationInterface
{
    // 15+ methods mixing different concerns
}

// Proposed - segregated interfaces
interface LinkExtractorInterface
{
    public function extractLinks(string $content): LinkCollection;
    public function extractAnchors(string $content): array;
}

interface LinkValidatorInterface
{
    public function validateLink(string $url, ValidationConfig $config): ValidationResult;
    public function validateLinks(LinkCollection $links, ValidationConfig $config): ValidationResultCollection;
}

interface CrossReferenceValidatorInterface
{
    public function validateCrossReferences(array $files): ValidationResultCollection;
    public function validateAnchorLinks(LinkCollection $links, string $content): ValidationResultCollection;
}

// Composite interface for backward compatibility
interface LinkValidationInterface extends 
    LinkExtractorInterface, 
    LinkValidatorInterface, 
    CrossReferenceValidatorInterface
{
    // Main orchestration methods only
}
```

## 2.5. Generics and Union Types

### 2.5.1. Union Type Elimination

**Critical Union Types to Remove**
```php
// Current problematic union types
public function validateFile(string $filePath, ValidationConfig|array $config): array
public function validateFileSummary(string $filePath, ValidationConfig|array $config): array
public function validateExternalLinks(array $urls, ValidationConfig|array $config): array

// Proposed strict typing
public function validateFile(string $filePath, ValidationConfig $config): ValidationResultCollection
public function validateFileSummary(string $filePath, ValidationConfig $config): ValidationSummary
public function validateExternalLinks(array $urls, ValidationConfig $config): ValidationResultCollection
```

**Factory Pattern for Configuration**
```php
// Helper for array-to-DTO conversion
final class ValidationConfigFactory
{
    public static function fromArray(array $config): ValidationConfig
    {
        return ValidationConfig::create($config);
    }

    public static function fromDefaults(array $overrides = []): ValidationConfig
    {
        return ValidationConfig::withDefaults($overrides);
    }
}

// Usage in services
public function validateFile(string $filePath, ValidationConfig $config): ValidationResultCollection
{
    // No more union types - always receive proper DTO
}

// Usage in commands (conversion at boundary)
$config = is_array($inputConfig) 
    ? ValidationConfigFactory::fromArray($inputConfig)
    : $inputConfig;
```

### 2.5.2. Generic Collection Implementation

**Generic Collection Pattern**
```php
/**
 * @template T
 */
abstract readonly class Collection implements Countable, IteratorAggregate
{
    /**
     * @param array<T> $items
     */
    public function __construct(
        protected array $items = []
    ) {}

    /**
     * @return T|null
     */
    public function first(): mixed
    {
        return $this->items[0] ?? null;
    }

    /**
     * @return T|null
     */
    public function last(): mixed
    {
        return end($this->items) ?: null;
    }

    /**
     * @param callable(T): bool $predicate
     * @return static
     */
    public function filter(callable $predicate): static
    {
        return new static(array_filter($this->items, $predicate));
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }
}

/**
 * @extends Collection<ValidationResult>
 */
final readonly class ValidationResultCollection extends Collection
{
    public function getSuccessful(): self
    {
        return $this->filter(fn(ValidationResult $result) => $result->isSuccessful());
    }

    public function getFailed(): self
    {
        return $this->filter(fn(ValidationResult $result) => !$result->isSuccessful());
    }
}
```

## 2.6. Nullable vs Non-nullable Types

### 2.6.1. Nullable Type Analysis

**Current Nullable Types That Could Be Eliminated**
```php
// Current - unnecessary nullable
public function getOutputFormat(): ?OutputFormat
{
    return $this->outputFormat;
}

// Proposed - with default
public function getOutputFormat(): OutputFormat
{
    return $this->outputFormat ?? OutputFormat::CONSOLE;
}
```

**Better Default Patterns**
```php
// Current - nullable with null checks
private ?string $userAgent = null;

public function getUserAgent(): ?string
{
    return $this->userAgent;
}

// Usage requires null checks
$agent = $config->getUserAgent() ?? 'default-agent';

// Proposed - non-nullable with default
private string $userAgent = 'validate-links/1.0';

public function getUserAgent(): string
{
    return $this->userAgent;
}

// Usage is simpler
$agent = $config->getUserAgent();
```

### 2.6.2. Strict Typing Improvements

**Constructor Parameter Validation**
```php
final readonly class ValidationConfig
{
    public function __construct(
        private array $scopes = [ValidationScope::ALL],
        private int $timeout = 30,
        private int $maxRedirects = 5,
        private int $concurrentRequests = 10,
        private bool $cacheEnabled = true,
        private bool $followRedirects = true,
        private string $userAgent = 'validate-links/1.0',
        private OutputFormat $outputFormat = OutputFormat::CONSOLE, // Non-nullable with default
        private array $additionalOptions = [],
        private bool $interactive = false,
        private bool $dryRun = false
    ) {
        $this->validateConfiguration();
    }

    private function validateConfiguration(): void
    {
        if ($this->timeout < 1) {
            throw new InvalidArgumentException('Timeout must be positive');
        }

        if ($this->maxRedirects < 0) {
            throw new InvalidArgumentException('Max redirects cannot be negative');
        }

        if ($this->concurrentRequests < 1) {
            throw new InvalidArgumentException('Concurrent requests must be positive');
        }

        if (empty($this->scopes)) {
            throw new InvalidArgumentException('At least one scope must be specified');
        }

        foreach ($this->scopes as $scope) {
            if (!$scope instanceof ValidationScope) {
                throw new InvalidArgumentException('Invalid scope type');
            }
        }
    }
}
```

**Method Return Type Strengthening**
```php
// Current - weak return types
public function extractLinks(string $content): array
public function getStatusStatistics(): array
public function categorizeLinks(array $links): array

// Proposed - strong return types
public function extractLinks(string $content): LinkCollection
public function getStatusStatistics(): ValidationStatistics
public function categorizeLinks(LinkCollection $links): CategorizedLinks
```

---

## Navigation

**← Previous:** [Current State Analysis](010-current-state-analysis.md)

**Next →** [Impact Analysis](030-impact-analysis.md)
