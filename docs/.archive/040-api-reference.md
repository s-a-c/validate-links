# 4. API Reference

**Date:** July 22, 2025
**Project:** Laravel Zero validate-links Implementation
**Compliance:** `.ai/guidelines.md` and `.ai/guidelines/` standards

## 4.1. Command Reference

The Laravel Zero validate-links application provides a comprehensive set of commands for link validation, fixing, reporting, and configuration management. All commands follow Laravel Zero's command signature conventions and provide extensive options for customization.

### 4.1.1. Command Overview

```bash
# Main validation command
validate-links validate [paths...] [options]

# Link fixing command
validate-links fix [paths...] [options]

# Report generation command
validate-links report [paths...] [options]

# Configuration management
validate-links config [options]

# Application information
validate-links --version
validate-links --help

# Configuration management command
validate-links config [action] [options]

# Laravel Zero built-in commands
validate-links inspire
validate-links app:build [name] [options]
```

## 4.2. validate Command

The primary command for validating links in markdown files and directories.

### 4.2.1. Command Signature (Enum-Enhanced)

```bash
validate-links validate {path?} [options]
```

**Dynamic Signature with Enum Values:**
```bash
validate-links validate {path?}
    {--scope=all : Validation scope (internal, external, anchor, cross-reference, all)}
    {--format=console : Output format (console, json, html, markdown, xml, csv)}
    {--output= : Output file path}
    {--interactive : Run in interactive mode with guided setup}
    {--concurrent=10 : Number of concurrent requests (1-50)}
    {--timeout=30 : Request timeout in seconds (5-300)}
    {--max-redirects=5 : Maximum redirects to follow (0-10)}
    {--no-cache : Disable caching for this validation}
    {--follow-redirects : Follow HTTP redirects}
    {--user-agent=validate-links/1.0 : Custom User-Agent string}
    {--verbose : Show detailed output and error messages}
```

### 4.2.2. Arguments

| Argument | Type | Required | Description |
|----------|------|----------|-------------|
| `path` | array | Yes | One or more paths to validate (files or directories) |

**Examples:**
```bash
# Single file
validate-links validate README.md

# Multiple files
validate-links validate README.md docs/guide.md

# Directory
validate-links validate docs/

# Multiple paths
validate-links validate README.md docs/ examples/
```

### 4.2.3. Options

#### Validation Scope Options (Enum-Based)

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `--scope` | ValidationScope | `all` | Validation scope types (enum-validated) |
| `--interactive` | flag | `false` | Enable interactive mode with enum-driven prompts |

**ValidationScope Enum Values:**
- `internal` - Internal file and directory links
- `external` - External HTTP/HTTPS links
- `anchor` - Anchor links within documents (#section)
- `cross-reference` - Cross-references between files
- `all` - All link types (comprehensive validation)

**Scope Descriptions:**
- **Internal**: Validates links to files and directories within the project
- **External**: Validates HTTP/HTTPS links to external websites
- **Anchor**: Validates anchor links pointing to sections within documents
- **Cross-reference**: Validates references between different documentation files
- **All**: Performs comprehensive validation of all link types

**Examples:**
```bash
# Validate only internal links (enum-validated)
validate-links validate docs/ --scope=internal

# Validate multiple scopes (comma-separated)
validate-links validate docs/ --scope=internal,anchor,external

# Validate all link types (default)
validate-links validate docs/ --scope=all

# Interactive mode with enum-driven prompts
validate-links validate docs/ --interactive
```

#### File Discovery Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `--max-depth` | integer | `0` | Maximum directory traversal depth (0 = unlimited) |
| `--include-hidden` | flag | `false` | Include hidden files and directories |
| `--exclude` | array | `[]` | Exclude patterns (glob format) |

**Examples:**
```bash
# Limit directory depth
validate-links validate docs/ --max-depth=2

# Include hidden files
validate-links validate docs/ --include-hidden

# Exclude patterns
validate-links validate docs/ --exclude="*.tmp" --exclude="draft-*"
```

#### External Link Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `--timeout` | integer | `30` | External link timeout in seconds |
| `--user-agent` | string | `validate-links/1.0` | User agent for external requests |
| `--max-redirects` | integer | `5` | Maximum redirects to follow |

**Examples:**
```bash
# Custom timeout
validate-links validate docs/ --check-external --timeout=60

# Custom user agent
validate-links validate docs/ --check-external --user-agent="MyBot/1.0"
```

#### Output Options (Enum-Based)

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `--format` | OutputFormat | `console` | Output format (enum-validated) |
| `--output` | string | `null` | Output file path (auto-detects format from extension) |
| `--verbose` | flag | `false` | Verbose output with detailed error messages |

**OutputFormat Enum Values:**
- `console` - Console output with colors and formatting
- `json` - JSON format for programmatic use and APIs
- `html` - HTML report with styling and interactive features
- `markdown` - Markdown format for documentation integration
- `xml` - XML format for data exchange and processing
- `csv` - CSV format for spreadsheet analysis and data import

**Format Descriptions:**
- **Console**: Displays results directly in the terminal with colors and formatting
- **JSON**: Outputs structured JSON data suitable for APIs and programmatic processing
- **HTML**: Generates a styled HTML report with interactive features and charts
- **Markdown**: Creates markdown documentation that can be included in project docs
- **XML**: Produces XML output for data exchange and integration with other tools
- **CSV**: Exports data in CSV format for analysis in spreadsheet applications

**Format Use Cases:**
- **Console**: Interactive development, Quick validation, CI/CD logs
- **JSON**: API integration, Automated processing, Data analysis
- **HTML**: Team reports, Documentation sites, Executive summaries
- **Markdown**: Project documentation, README files, Wiki pages
- **XML**: Enterprise integration, Data exchange, Legacy systems
- **CSV**: Data analysis, Spreadsheet import, Statistical processing

**Examples:**
```bash
# JSON output with automatic file extension validation
validate-links validate docs/ --format=json --output=report.json

# HTML report with interactive features
validate-links validate docs/ --format=html --output=validation-report.html

# Markdown for documentation integration
validate-links validate docs/ --format=markdown --output=VALIDATION_REPORT.md

# CSV for data analysis
validate-links validate docs/ --format=csv --output=validation-data.csv

# Verbose console output with detailed errors
validate-links validate docs/ --format=console --verbose
```

#### Behavior Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `--max-broken` | integer | `50` | Maximum broken links before stopping |
| `--dry-run` | flag | `false` | Preview mode - show what would be validated |
| `--fix` | flag | `false` | Enable automatic link fixing |
| `--interactive` | flag | `false` | Interactive mode with prompts |
| `--cache` | flag | `false` | Enable caching for external links |

**Examples:**
```bash
# Preview mode
validate-links validate docs/ --dry-run

# Interactive mode
validate-links validate --interactive

# Enable caching
validate-links validate docs/ --check-external --cache

# Auto-fix mode
validate-links validate docs/ --fix
```

### 4.2.4. Exit Codes

| Code | Description |
|------|-------------|
| `0` | Success - no broken links found |
| `1` | Failure - broken links found |
| `2` | Error - invalid arguments or configuration |
| `3` | Error - file system or permission error |
| `4` | Error - network or timeout error |

### 4.2.5. Complete Examples

**Basic Validation:**
```bash
# Validate all markdown files in docs directory
validate-links validate docs/

# Validate specific files with verbose output
validate-links validate README.md CHANGELOG.md --verbose
```

**Advanced Validation:**
```bash
# Comprehensive validation with external links
validate-links validate docs/ \
  --scope=all \
  --check-external \
  --timeout=60 \
  --format=json \
  --output=validation-report.json \
  --cache

# Interactive validation setup
validate-links validate --interactive
```

**Production Validation:**
```bash
# CI/CD pipeline validation
validate-links validate docs/ \
  --scope=internal \
  --scope=anchor \
  --max-broken=0 \
  --format=json \
  --output=reports/validation.json \
  --no-progress
```

## 4.3. fix Command

Automatically fix broken links where possible.

### 4.3.1. Command Signature

```bash
validate-links fix {path*} [options]
```

### 4.3.2. Arguments

| Argument | Type | Required | Description |
|----------|------|----------|-------------|
| `path` | array | Yes | One or more paths to fix (files or directories) |

### 4.3.3. Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `--scope` | array | `['internal']` | Types of links to fix |
| `--dry-run` | flag | `false` | Preview fixes without applying |
| `--backup` | flag | `true` | Create backup files before fixing |
| `--interactive` | flag | `false` | Confirm each fix interactively |
| `--format` | string | `console` | Output format for fix report |
| `--output` | string | `null` | Output file for fix report |

**Fix Types:**
- `internal` - Fix broken internal links
- `anchor` - Fix broken anchor links
- `case` - Fix case sensitivity issues
- `extension` - Fix missing file extensions

### 4.3.4. Examples

```bash
# Fix internal links with backup
validate-links fix docs/ --backup

# Preview fixes without applying
validate-links fix docs/ --dry-run

# Interactive fixing
validate-links fix docs/ --interactive

# Fix specific types
validate-links fix docs/ --scope=internal --scope=case
```

## 4.4. report Command

Generate detailed validation reports.

### 4.4.1. Command Signature

```bash
validate-links report {path*} [options]
```

### 4.4.2. Arguments

| Argument | Type | Required | Description |
|----------|------|----------|-------------|
| `path` | array | Yes | One or more paths to report on |

### 4.4.3. Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `--format` | string | `html` | Report format |
| `--output` | string | `report.html` | Output file path |
| `--template` | string | `default` | Report template |
| `--include-valid` | flag | `false` | Include valid links in report |
| `--group-by` | string | `file` | Group results by file or type |
| `--sort-by` | string | `file` | Sort results by file, type, or status |

**Report Formats:**
- `html` - Interactive HTML report
- `markdown` - Markdown report
- `json` - JSON data export
- `csv` - CSV data export
- `pdf` - PDF report (requires additional dependencies)

### 4.4.4. Examples

```bash
# Generate HTML report
validate-links report docs/ --format=html --output=link-report.html

# Comprehensive report with valid links
validate-links report docs/ --include-valid --group-by=type

# CSV export for analysis
validate-links report docs/ --format=csv --output=links.csv
```

## 4.5. config Command

Manage application configuration.

### 4.5.1. Command Signature

```bash
validate-links config [action] [options]
```

### 4.5.2. Actions

| Action | Description |
|--------|-------------|
| `show` | Display current configuration |
| `set` | Set configuration value |
| `get` | Get configuration value |
| `reset` | Reset to default configuration |
| `validate` | Validate configuration |
| `export` | Export configuration to file |
| `import` | Import configuration from file |

### 4.5.3. Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `--key` | string | `null` | Configuration key |
| `--value` | string | `null` | Configuration value |
| `--file` | string | `null` | Configuration file path |
| `--format` | string | `json` | Configuration format |
| `--interactive` | flag | `false` | Interactive configuration setup |

### 4.5.4. Examples

```bash
# Show all configuration
validate-links config show

# Get specific value
validate-links config get --key=defaults.timeout

# Set configuration value
validate-links config set --key=defaults.timeout --value=60

# Interactive configuration
validate-links config --interactive

# Export configuration
validate-links config export --file=my-config.json

# Import configuration
validate-links config import --file=my-config.json
```

## 4.6. Configuration Reference

### 4.6.1. Configuration File Structure

The main configuration file is located at `config/validate-links.php` and follows Laravel's configuration conventions.

```php
<?php
// config/validate-links.php

return [
    'defaults' => [...],
    'security' => [...],
    'performance' => [...],
    'formats' => [...],
    'patterns' => [...],
    'extraction' => [...],
    'scopes' => [...],
];
```

### 4.6.2. Default Settings

```php
'defaults' => [
    'max_depth' => env('VALIDATE_LINKS_MAX_DEPTH', 0),
    'timeout' => env('VALIDATE_LINKS_TIMEOUT', 30),
    'max_broken' => env('VALIDATE_LINKS_MAX_BROKEN', 50),
    'concurrency' => env('VALIDATE_LINKS_CONCURRENCY', 10),
    'include_hidden' => env('VALIDATE_LINKS_INCLUDE_HIDDEN', false),
    'follow_symlinks' => env('VALIDATE_LINKS_FOLLOW_SYMLINKS', false),
    'case_sensitive' => env('VALIDATE_LINKS_CASE_SENSITIVE', false),
]
```

**Configuration Keys:**

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `max_depth` | integer | `0` | Maximum directory traversal depth |
| `timeout` | integer | `30` | External link timeout in seconds |
| `max_broken` | integer | `50` | Maximum broken links before stopping |
| `concurrency` | integer | `10` | Concurrent external link validation |
| `include_hidden` | boolean | `false` | Include hidden files |
| `follow_symlinks` | boolean | `false` | Follow symbolic links |
| `case_sensitive` | boolean | `false` | Case-sensitive link validation |

### 4.6.3. Security Configuration

```php
'security' => [
    'allowed_protocols' => ['http', 'https', 'ftp', 'ftps'],
    'blocked_domains' => [
        'localhost',
        '127.0.0.1',
        '0.0.0.0',
        '::1',
    ],
    'blocked_paths' => [
        '/etc/*',
        '/var/*',
        '/tmp/*',
        '/proc/*',
        '/sys/*',
    ],
    'max_file_size' => 10 * 1024 * 1024, // 10MB
    'path_traversal_protection' => true,
    'url_validation' => true,
    'domain_whitelist' => [],
    'domain_blacklist' => [],
]
```

**Security Keys:**

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `allowed_protocols` | array | `['http', 'https', 'ftp', 'ftps']` | Allowed URL protocols |
| `blocked_domains` | array | Local domains | Domains to block |
| `blocked_paths` | array | System paths | File paths to block |
| `max_file_size` | integer | `10485760` | Maximum file size in bytes |
| `path_traversal_protection` | boolean | `true` | Enable path traversal protection |
| `url_validation` | boolean | `true` | Enable URL format validation |

### 4.6.4. Performance Configuration

```php
'performance' => [
    'cache_enabled' => env('VALIDATE_LINKS_CACHE_ENABLED', true),
    'cache_ttl' => env('VALIDATE_LINKS_CACHE_TTL', 86400), // 24 hours
    'cache_store' => env('VALIDATE_LINKS_CACHE_STORE', 'file'),
    'memory_limit' => env('VALIDATE_LINKS_MEMORY_LIMIT', '512M'),
    'max_execution_time' => env('VALIDATE_LINKS_MAX_EXECUTION_TIME', 300),
    'batch_size' => env('VALIDATE_LINKS_BATCH_SIZE', 100),
    'concurrent_requests' => env('VALIDATE_LINKS_CONCURRENT_REQUESTS', 10),
    'request_delay' => env('VALIDATE_LINKS_REQUEST_DELAY', 0),
]
```

**Performance Keys:**

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `cache_enabled` | boolean | `true` | Enable result caching |
| `cache_ttl` | integer | `86400` | Cache time-to-live in seconds |
| `cache_store` | string | `file` | Cache storage driver |
| `memory_limit` | string | `512M` | PHP memory limit |
| `max_execution_time` | integer | `300` | Maximum execution time |
| `batch_size` | integer | `100` | File processing batch size |
| `concurrent_requests` | integer | `10` | Concurrent external requests |
| `request_delay` | integer | `0` | Delay between requests (ms) |

### 4.6.5. File Pattern Configuration

```php
'patterns' => [
    'include' => [
        '*.md',
        '*.markdown',
        '*.rst',
        '*.txt',
        '*.html',
        '*.htm',
    ],
    'exclude' => [
        'node_modules/**/*',
        'vendor/**/*',
        '.git/**/*',
        '.ai/**/*.md',
        'build/**/*',
        'dist/**/*',
        'coverage/**/*',
        'reports/**/*',
        '*.min.*',
        '*.lock',
    ],
    'case_sensitive' => false,
    'follow_symlinks' => false,
]
```

**Pattern Keys:**

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `include` | array | Markdown/text files | File patterns to include |
| `exclude` | array | Build/vendor dirs | File patterns to exclude |
| `case_sensitive` | boolean | `false` | Case-sensitive pattern matching |
| `follow_symlinks` | boolean | `false` | Follow symbolic links |

### 4.6.6. Link Extraction Configuration

```php
'extraction' => [
    'markdown_links' => true,
    'html_links' => true,
    'reference_links' => true,
    'image_links' => false,
    'email_links' => false,
    'autolinks' => true,
    'shortcut_links' => true,
    'inline_links' => true,
    'link_definitions' => true,
]
```

**Extraction Keys:**

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `markdown_links` | boolean | `true` | Extract Markdown links |
| `html_links` | boolean | `true` | Extract HTML links |
| `reference_links` | boolean | `true` | Extract reference-style links |
| `image_links` | boolean | `false` | Extract image links |
| `email_links` | boolean | `false` | Extract email links |
| `autolinks` | boolean | `true` | Extract autolinks |

### 4.6.7. Validation Scope Configuration

```php
'scopes' => [
    'internal' => [
        'enabled' => true,
        'check_existence' => true,
        'follow_symlinks' => false,
        'case_sensitive' => false,
        'resolve_relative' => true,
    ],
    'anchor' => [
        'enabled' => true,
        'github_style' => true,
        'case_sensitive' => false,
        'allow_duplicates' => false,
        'custom_patterns' => [],
    ],
    'cross_reference' => [
        'enabled' => true,
        'resolve_relative_paths' => true,
        'check_anchor_existence' => true,
        'follow_redirects' => false,
    ],
    'external' => [
        'enabled' => false,
        'timeout' => 30,
        'user_agent' => 'validate-links/1.0',
        'follow_redirects' => true,
        'max_redirects' => 5,
        'verify_ssl' => true,
        'allowed_status_codes' => [200, 201, 202, 204, 301, 302, 303, 307, 308],
    ],
]
```

**Scope Configuration:**

| Scope | Key | Type | Default | Description |
|-------|-----|------|---------|-------------|
| `internal` | `enabled` | boolean | `true` | Enable internal link validation |
| `internal` | `check_existence` | boolean | `true` | Check if target files exist |
| `internal` | `case_sensitive` | boolean | `false` | Case-sensitive file matching |
| `anchor` | `github_style` | boolean | `true` | Use GitHub anchor generation |
| `anchor` | `allow_duplicates` | boolean | `false` | Allow duplicate anchors |
| `external` | `timeout` | integer | `30` | Request timeout in seconds |
| `external` | `verify_ssl` | boolean | `true` | Verify SSL certificates |

## 4.7. Environment Variables

### 4.7.1. Core Settings

| Variable | Type | Default | Description |
|----------|------|---------|-------------|
| `VALIDATE_LINKS_MAX_DEPTH` | integer | `0` | Maximum directory depth |
| `VALIDATE_LINKS_TIMEOUT` | integer | `30` | External link timeout |
| `VALIDATE_LINKS_MAX_BROKEN` | integer | `50` | Maximum broken links |
| `VALIDATE_LINKS_CONCURRENCY` | integer | `10` | Concurrent requests |

### 4.7.2. Performance Settings

| Variable | Type | Default | Description |
|----------|------|---------|-------------|
| `VALIDATE_LINKS_CACHE_ENABLED` | boolean | `true` | Enable caching |
| `VALIDATE_LINKS_CACHE_TTL` | integer | `86400` | Cache TTL in seconds |
| `VALIDATE_LINKS_MEMORY_LIMIT` | string | `512M` | Memory limit |
| `VALIDATE_LINKS_BATCH_SIZE` | integer | `100` | Processing batch size |

### 4.7.3. Security Settings

| Variable | Type | Default | Description |
|----------|------|---------|-------------|
| `VALIDATE_LINKS_STRICT_MODE` | boolean | `false` | Enable strict validation |
| `VALIDATE_LINKS_AUDIT_LOG` | boolean | `false` | Enable audit logging |
| `VALIDATE_LINKS_MAX_FILE_SIZE` | integer | `10485760` | Max file size in bytes |

### 4.7.4. Example .env Configuration

```bash
# .env file example

# Core settings
VALIDATE_LINKS_MAX_DEPTH=3
VALIDATE_LINKS_TIMEOUT=45
VALIDATE_LINKS_MAX_BROKEN=25
VALIDATE_LINKS_CONCURRENCY=15

# Performance settings
VALIDATE_LINKS_CACHE_ENABLED=true
VALIDATE_LINKS_CACHE_TTL=43200
VALIDATE_LINKS_MEMORY_LIMIT=1024M
VALIDATE_LINKS_BATCH_SIZE=50

# Security settings
VALIDATE_LINKS_STRICT_MODE=true
VALIDATE_LINKS_AUDIT_LOG=true

# Logging
LOG_CHANNEL=single
LOG_LEVEL=info
```

## 4.8. Output Formats

### 4.8.1. Console Format

The default console format provides colored, human-readable output with progress indicators.

**Features:**
- Colored output (green for success, red for errors)
- Progress bars for long operations
- Real-time broken link alerts
- Summary statistics table
- Interactive prompts (when enabled)

**Example Output:**
```
🔗 Validating links in docs/

Validating: README.md ████████████████████ 100%
Validating: guide.md   ████████████████████ 100%

⚠️  Broken links found in: guide.md

📊 Validation Summary
┌─────────────────┬───────┐
│ Metric          │ Count │
├─────────────────┼───────┤
│ Total Files     │ 2     │
│ Total Links     │ 15    │
│ Broken Links    │ 1     │
│ External Links  │ 3     │
│ Internal Links  │ 10    │
│ Anchor Links    │ 2     │
└─────────────────┴───────┘

❌ Validation failed: 1 broken link found
```

### 4.8.2. JSON Format

Machine-readable JSON output suitable for CI/CD integration and programmatic processing.

**Structure:**
```json
{
  "metadata": {
    "version": "1.0.0",
    "timestamp": "2025-07-21T15:05:00Z",
    "command": "validate docs/",
    "options": {
      "scope": ["internal", "anchor"],
      "format": "json"
    }
  },
  "summary": {
    "total_files": 2,
    "total_links": 15,
    "broken_links": 1,
    "external_links": 3,
    "internal_links": 10,
    "anchor_links": 2,
    "validation_time": 2.34,
    "memory_usage": 12582912
  },
  "results": [
    {
      "file": "docs/README.md",
      "links": [
        {
          "url": "./guide.md",
          "type": "internal",
          "status": "valid",
          "line": 5,
          "column": 12,
          "text": "User Guide"
        }
      ],
      "summary": {
        "total": 8,
        "broken": 0,
        "valid": 8
      }
    }
  ],
  "errors": [],
  "warnings": []
}
```

### 4.8.3. Markdown Format

Markdown report format suitable for documentation and GitHub integration.

**Example Output:**
```markdown
# Link Validation Report

**Generated:** July 21, 2025 at 15:05:00 UTC  
**Command:** `validate docs/`  
**Status:** ❌ Failed (1 broken link)

## Summary

| Metric | Count |
|--------|-------|
| Total Files | 2 |
| Total Links | 15 |
| Broken Links | 1 |
| External Links | 3 |
| Internal Links | 10 |
| Anchor Links | 2 |

## Results by File

### ✅ docs/README.md

- **Total Links:** 8
- **Broken Links:** 0
- **Status:** Valid

### ❌ docs/guide.md

- **Total Links:** 7
- **Broken Links:** 1
- **Status:** Failed

#### Broken Links

1. **Line 23:** `[Missing Page](./missing.md)` - File not found

## Recommendations

- Fix the broken link in `docs/guide.md`
- Consider adding the missing file or updating the link
```

### 4.8.4. HTML Format

Interactive HTML report with filtering, sorting, and detailed views.

**Features:**
- Interactive filtering by status, type, file
- Sortable columns
- Expandable details
- Search functionality
- Export capabilities
- Responsive design
- Dark/light theme toggle

**Template Structure:**
```html
<!DOCTYPE html>
<html>
<head>
    <title>Link Validation Report</title>
    <style>/* Embedded CSS */</style>
</head>
<body>
    <header>
        <h1>Link Validation Report</h1>
        <div class="summary">/* Summary statistics */</div>
    </header>
    
    <main>
        <div class="filters">/* Interactive filters */</div>
        <div class="results">/* Results table */</div>
    </main>
    
    <script>/* Interactive functionality */</script>
</body>
</html>
```

## 4.9. Error Codes and Messages

### 4.9.1. Validation Error Codes

| Code | Category | Description |
|------|----------|-------------|
| `VL001` | File Error | File not found |
| `VL002` | File Error | File not readable |
| `VL003` | File Error | File too large |
| `VL004` | Link Error | Invalid link format |
| `VL005` | Link Error | Broken internal link |
| `VL006` | Link Error | Broken anchor link |
| `VL007` | Link Error | Broken external link |
| `VL008` | Security Error | Path traversal attempt |
| `VL009` | Security Error | Blocked domain |
| `VL010` | Network Error | Connection timeout |
| `VL011` | Network Error | DNS resolution failed |
| `VL012` | Config Error | Invalid configuration |

### 4.9.2. Error Message Format

```json
{
  "code": "VL005",
  "severity": "error",
  "message": "Broken internal link",
  "file": "docs/guide.md",
  "line": 23,
  "column": 12,
  "context": {
    "url": "./missing.md",
    "text": "Missing Page",
    "target": "docs/missing.md"
  },
  "suggestion": "Create the missing file or update the link"
}
```

## 4.10. API Integration

### 4.10.1. Programmatic Usage

The validate-links application can be integrated into other PHP applications using Laravel Zero's programmatic interface.

```php
<?php

use Illuminate\Support\Facades\Artisan;

// Run validation programmatically
$exitCode = Artisan::call('validate', [
    'path' => ['docs/'],
    '--format' => 'json',
    '--output' => 'validation-results.json'
]);

// Get command output
$output = Artisan::output();

// Check if validation passed
if ($exitCode === 0) {
    echo "Validation passed!";
} else {
    echo "Validation failed with exit code: {$exitCode}";
}
```

### 4.10.2. Service Integration

Access validation services directly for custom implementations:

```php
<?php

use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\ReportingInterface;

// Resolve services from container
$validator = app(LinkValidationInterface::class);
$reporter = app(ReportingInterface::class);

// Validate a single file
$results = $validator->validateFile('docs/README.md', ['internal', 'anchor']);

// Generate custom report
$report = $reporter->generateReport([$results], 'json');

echo $report;
```

### 4.10.3. Event Integration

Listen to validation events for custom processing:

```php
<?php

use Illuminate\Support\Facades\Event;
use App\Events\ValidationStarted;
use App\Events\ValidationCompleted;
use App\Events\BrokenLinkFound;

// Listen for validation events
Event::listen(ValidationStarted::class, function ($event) {
    logger()->info('Validation started', ['paths' => $event->paths]);
});

Event::listen(BrokenLinkFound::class, function ($event) {
    // Send notification, log to external service, etc.
    logger()->warning('Broken link found', [
        'file' => $event->file,
        'url' => $event->url,
        'line' => $event->line,
    ]);
});

Event::listen(ValidationCompleted::class, function ($event) {
    logger()->info('Validation completed', ['summary' => $event->summary]);
});
```

## 4.11. CI/CD Pipeline Integration

### 4.11.1. Pipeline Architecture Overview

The validate-links project implements a sophisticated CI/CD pipeline with 10 specialized GitHub Actions workflows covering testing, code quality, deployment, and validation processes.

**Workflow Categories:**
1. **Quality Assurance** - Code quality, linting, static analysis
2. **Testing** - Unit tests, integration tests, TDD validation
3. **Deployment** - Production deployment and asset building
4. **Validation** - Documentation validation, type checking
5. **Specialized** - Playwright testing, PR-specific workflows

### 4.11.2. Core GitHub Actions Workflows

#### Tests Workflow (`tests.yml`)

**Purpose:** Execute comprehensive test suite with coverage reporting

**Trigger Conditions:**
- Push to `develop` or `main` branches
- Pull requests targeting `develop` or `main` branches

```yaml
name: Tests

on:
  push:
    branches: [develop, main]
  pull_request:
    branches: [develop, main]

jobs:
  test:
    runs-on: ubuntu-latest
    environment: testing

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite, pdo_sqlite, bcmath, soap, intl, gd, exif, iconv
          coverage: xdebug

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '22'
          cache: 'npm'

      - name: Install Node dependencies
        run: npm ci

      - name: Install Composer dependencies
        run: composer install --prefer-dist --no-interaction

      - name: Copy .env.example to .env
        run: cp .env.example .env

      - name: Generate application key
        run: php artisan key:generate

      - name: Build assets
        run: npm run build

      - name: Execute tests
        run: composer test:coverage

      - name: Upload coverage reports
        uses: codecov/codecov-action@v4
        with:
          file: ./coverage.xml
          flags: unittests
          name: codecov-umbrella
```

#### Code Quality Workflow (`code-quality.yml`)

**Purpose:** Enforce code quality standards and static analysis

```yaml
name: Code Quality

on:
  push:
    branches: [010-ddl, develop]
  pull_request:
    branches: [010-ddl, develop]

jobs:
  code-quality:
    runs-on: ubuntu-latest

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite, pdo_sqlite, bcmath, soap, intl, gd, exif, iconv

      - name: Install Composer dependencies
        run: composer install --prefer-dist --no-interaction --no-dev

      - name: Run Laravel Pint
        run: composer cs-check

      - name: Run PHPStan
        run: composer analyse

      - name: Run Rector (dry-run)
        run: vendor/bin/rector process --dry-run

      - name: Run PHP Insights
        run: composer insights --min-quality=90 --min-complexity=90 --min-architecture=90 --min-style=90
```

#### Deploy Workflow (`deploy.yml`)

**Purpose:** Deploy application to production environment

```yaml
name: Deploy

on:
  push:
    branches: [010-ddl]

jobs:
  deploy:
    runs-on: ubuntu-latest
    environment: production

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite, pdo_sqlite, bcmath, soap, intl, gd, exif, iconv

      - name: Install Composer dependencies
        run: composer install --prefer-dist --no-interaction --no-dev --optimize-autoloader

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '20'

      - name: Install NPM dependencies
        run: npm ci

      - name: Build production assets
        run: npm run build

      - name: Deploy to production
        uses: deployphp/action@v1
        with:
          private-key: ${{ secrets.DEPLOY_KEY }}
          dep: deploy production
```

### 4.11.3. Specialized Workflows

#### Link Validation Workflow (`validate-links.yml`)

**Purpose:** Validate documentation links in CI/CD pipeline

```yaml
name: Validate Links

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]
  schedule:
    - cron: '0 2 * * 1'  # Weekly on Monday at 2 AM

jobs:
  validate-links:
    runs-on: ubuntu-latest

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'

      - name: Install validate-links
        run: composer global require your-org/validate-links

      - name: Validate internal links
        run: |
          validate-links validate docs/ \
            --scope=internal \
            --scope=anchor \
            --format=json \
            --output=internal-validation-report.json \
            --max-broken=0

      - name: Validate external links
        run: |
          validate-links validate docs/ \
            --scope=external \
            --check-external \
            --timeout=30 \
            --format=json \
            --output=external-validation-report.json \
            --max-broken=5
        continue-on-error: true

      - name: Generate HTML report
        run: |
          validate-links report docs/ \
            --format=html \
            --output=validation-report.html \
            --include-valid

      - name: Upload validation reports
        uses: actions/upload-artifact@v4
        if: always()
        with:
          name: validation-reports
          path: |
            internal-validation-report.json
            external-validation-report.json
            validation-report.html
          retention-days: 30
```

### 4.11.4. Environment Variables and Secrets

#### Required Secrets
```yaml
# GitHub Repository Secrets
DEPLOY_KEY: SSH private key for deployment
FLUX_USERNAME: Flux UI username (if applicable)
FLUX_LICENSE_KEY: Flux UI license key (if applicable)
CODECOV_TOKEN: Codecov upload token
```

#### Environment Variables
```yaml
# .env.example (create this file)
APP_NAME="validate-links"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=UTC

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# Add other environment variables as needed
```

### 4.11.5. Deployment Configuration

#### Deploy PHP Configuration (`deploy.php`)

```php
<?php

namespace Deployer;

require 'recipe/laravel.php';

// Project name
set('application', 'validate-links');

// Project repository
set('repository', 'git@github.com:your-org/validate-links.git');

// Shared files/dirs between deploys
add('shared_files', ['.env']);
add('shared_dirs', ['storage']);

// Writable dirs by web server
add('writable_dirs', ['storage']);

// Hosts
host('production')
    ->set('hostname', 'your-production-server.com')
    ->set('remote_user', 'deployer')
    ->set('deploy_path', '/var/www/validate-links');

// Tasks
task('build', function () {
    cd('{{release_path}}');
    run('composer install --no-dev --optimize-autoloader');
    run('npm ci && npm run build');
});

after('deploy:update_code', 'build');
```

### 4.11.6. Quality Gates and Monitoring

#### Coverage Requirements
- **Minimum Code Coverage:** 80%
- **Target Code Coverage:** 95%
- **Type Coverage:** 95% (enforced by Pest Type Coverage plugin)

#### Performance Monitoring
```yaml
# Add to workflow for performance tracking
- name: Performance benchmark
  run: |
    time validate-links validate docs/ --scope=all
    echo "Performance metrics logged"
```

#### Failure Notifications
```yaml
# Add to workflow for failure notifications
- name: Notify on failure
  if: failure()
  uses: 8398a7/action-slack@v3
  with:
    status: failure
    channel: '#ci-cd'
    webhook_url: ${{ secrets.SLACK_WEBHOOK }}
```

### 4.11.7. Troubleshooting Common Issues

#### Missing .env.example File
**Issue:** Workflows fail because `.env.example` doesn't exist
**Solution:** Create `.env.example` with required environment variables

#### Flux UI Dependencies
**Issue:** Workflows reference Flux UI which may not be relevant for CLI application
**Solution:** Remove Flux UI setup steps or make them conditional

#### Missing Composer Scripts
**Issue:** Workflows reference undefined Composer scripts
**Solution:** Add missing scripts to `composer.json`:

```json
{
  "scripts": {
    "cs-check": "pint --test",
    "cs-fix": "pint",
    "analyse": "phpstan analyse",
    "test": "pest",
    "test:coverage": "pest --coverage",
    "test:type-coverage": "pest --type-coverage",
    "insights": "phpinsights"
  }
}
```

### 4.11.8. GitLab CI Integration

```yaml
# .gitlab-ci.yml
stages:
  - test
  - quality
  - deploy

variables:
  PHP_VERSION: "8.4"

test:
  stage: test
  image: php:${PHP_VERSION}-cli
  before_script:
    - apt-get update -qq && apt-get install -y -qq git curl libmcrypt-dev libjpeg-dev libpng-dev libfreetype6-dev libbz2-dev
    - curl -sS https://getcomposer.org/installer | php
    - php composer.phar install --no-dev --no-interaction
  script:
    - composer test:coverage
  artifacts:
    reports:
      coverage_report:
        coverage_format: cobertura
        path: coverage.xml
    paths:
      - coverage/
    expire_in: 1 week

validate-links:
  stage: quality
  image: php:${PHP_VERSION}-cli
  before_script:
    - composer global require your-org/validate-links
    - export PATH="$PATH:$HOME/.composer/vendor/bin"
  script:
    - validate-links validate docs/ --scope=internal --scope=anchor --max-broken=0
  artifacts:
    when: always
    reports:
      junit: validation-report.xml
    paths:
      - validation-report.json
      - validation-report.html
    expire_in: 1 week

deploy:
  stage: deploy
  image: php:${PHP_VERSION}-cli
  script:
    - echo "Deploy to production"
  only:
    - main
```

### 4.11.9. Jenkins Pipeline Integration

```groovy
pipeline {
    agent any
    
    environment {
        PHP_VERSION = '8.4'
        COMPOSER_CACHE_DIR = '/tmp/composer-cache'
    }
    
    stages {
        stage('Setup') {
            steps {
                sh '''
                    php -v
                    composer --version
                    composer install --no-interaction --prefer-dist
                '''
            }
        }
        
        stage('Code Quality') {
            parallel {
                stage('Tests') {
                    steps {
                        sh 'composer test:coverage'
                    }
                    post {
                        always {
                            publishHTML([
                                allowMissing: false,
                                alwaysLinkToLastBuild: true,
                                keepAll: true,
                                reportDir: 'coverage',
                                reportFiles: 'index.html',
                                reportName: 'Coverage Report'
                            ])
                        }
                    }
                }
                
                stage('Static Analysis') {
                    steps {
                        sh 'composer analyse'
                    }
                }
                
                stage('Code Style') {
                    steps {
                        sh 'composer cs-check'
                    }
                }
            }
        }
        
        stage('Validate Links') {
            steps {
                sh '''
                    composer global require your-org/validate-links
                    validate-links validate docs/ \
                        --scope=internal \
                        --scope=anchor \
                        --format=json \
                        --output=validation-report.json \
                        --max-broken=0
                '''
            }
            
            post {
                always {
                    archiveArtifacts artifacts: 'validation-report.json'
                    publishHTML([
                        allowMissing: false,
                        alwaysLinkToLastBuild: true,
                        keepAll: true,
                        reportDir: '.',
                        reportFiles: 'validation-report.html',
                        reportName: 'Link Validation Report'
                    ])
                }
            }
        }
        
        stage('Deploy') {
            when {
                branch 'main'
            }
            steps {
                sh '''
                    composer install --no-dev --optimize-autoloader
                    # Add deployment commands here
                '''
            }
        }
    }
    
    post {
        always {
            cleanWs()
        }
        failure {
            emailext (
                subject: "Build Failed: ${env.JOB_NAME} - ${env.BUILD_NUMBER}",
                body: "Build failed. Check console output at ${env.BUILD_URL}",
                to: "${env.CHANGE_AUTHOR_EMAIL}"
            )
        }
    }
}
```

## 4.12. Advanced Usage Patterns

### 4.12.1. Custom Configuration Profiles

Create environment-specific configuration profiles:

```bash
# Development profile
validate-links validate docs/ \
  --config=config/validate-links-dev.php \
  --scope=internal \
  --cache

# Production profile  
validate-links validate docs/ \
  --config=config/validate-links-prod.php \
  --scope=all \
  --check-external \
  --max-broken=0
```

### 4.12.2. Batch Processing

Process multiple projects or directories:

```bash
#!/bin/bash
# batch-validate.sh

projects=("project1" "project2" "project3")
failed_projects=()

for project in "${projects[@]}"; do
    echo "Validating $project..."
    
    if ! validate-links validate "$project/docs/" \
        --format=json \
        --output="reports/$project-validation.json" \
        --max-broken=0; then
        failed_projects+=("$project")
    fi
done

if [ ${#failed_projects[@]} -gt 0 ]; then
    echo "Failed projects: ${failed_projects[*]}"
    exit 1
fi
```

### 4.12.3. Integration with Pre-commit Hooks

```bash
#!/bin/sh
# .git/hooks/pre-commit

# Get list of modified markdown files
modified_files=$(git diff --cached --name-only --diff-filter=ACM | grep -E '\.(md|markdown)$')

if [ -n "$modified_files" ]; then
    echo "Validating links in modified files..."
    
    # Create temporary file list
    echo "$modified_files" > /tmp/validate-files.txt
    
    # Validate only modified files
    if ! validate-links validate $(cat /tmp/validate-files.txt) \
        --scope=internal \
        --scope=anchor \
        --max-broken=0; then
        echo "Link validation failed. Commit aborted."
        rm /tmp/validate-files.txt
        exit 1
    fi
    
    rm /tmp/validate-files.txt
fi
```

---

## 📖 Navigation

**[⬅️ Previous: Architecture Overview](030-architecture-overview.md)** | **[Next: Usage Guide ➡️](050-usage-guide.md)** | **[🔝 Top](#4-api-reference)**
