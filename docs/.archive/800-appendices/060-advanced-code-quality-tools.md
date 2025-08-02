# Advanced Code Quality Tools

**Date:** July 23, 2025
**Project:** Laravel Zero validate-links Implementation
**Framework:** Laravel 12.20+ (Current Stable)
**Compliance:** `.ai/guidelines.md` and `.ai/guidelines/` standards

## Overview

**⚠️ IMPORTANT: These tools are OPTIONAL for validate-links development.**

This appendix covers advanced code quality tools that provide additional value beyond the essential PHPStan and Pint tools. While these tools enhance code quality and developer experience, they are not required for core validate-links functionality.

## Essential vs. Optional Tools

### ✅ **Essential Tools (Required)**
- **PHPStan**: Static analysis for type safety and bug detection
- **Pint**: Code formatting for consistent style
- **Pest**: Testing framework for quality assurance

### 🔧 **Optional Tools (This Appendix)**
- **Rector**: Automated code modernization and refactoring
- **PHP Insights**: Code quality metrics and architecture analysis
- **IDE Integration**: Enhanced development environment setup

## Rector Setup (Optional)

### Purpose
Rector provides automated code modernization to PHP 8.4+ standards and can automatically refactor code to use modern PHP features.

### When to Use
- **Large codebases** that need modernization
- **Legacy code** migration projects
- **Automated refactoring** for consistency
- **PHP version upgrades** with breaking changes

### Installation and Configuration

**Install Rector:**
```bash
composer require rector/rector --dev
```

**Create rector.php configuration:**
```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/app',
        __DIR__ . '/bootstrap',
        __DIR__ . '/config',
        __DIR__ . '/tests',
    ]);

    // Skip files/directories
    $rectorConfig->skip([
        __DIR__ . '/vendor',
        __DIR__ . '/node_modules',
        __DIR__ . '/storage',
        __DIR__ . '/bootstrap/cache',
    ]);

    // PHP 8.4+ modernization
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_84,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
        SetList::PRIVATIZATION,
    ]);

    // Performance and caching
    $rectorConfig->cacheDirectory(__DIR__ . '/reports/rector/cache');
    $rectorConfig->parallel();
};
```

**Add Composer Scripts:**
```json
{
    "scripts": {
        "refactor": "rector process",
        "refactor-dry": "rector process --dry-run",
        "refactor-diff": "rector process --dry-run --diff"
    }
}
```

### Usage Examples

```bash
# Preview changes without applying
composer refactor-dry

# See detailed diff of changes
composer refactor-diff

# Apply all modernizations
composer refactor

# Process specific directory
./vendor/bin/rector process app/Services --dry-run
```

### CI/CD Integration (Optional)

**GitHub Actions Workflow:**
```yaml
# .github/workflows/rector.yml - Optional
name: Rector Code Modernization

on:
  pull_request:
    branches: [ main, develop ]

jobs:
  rector:
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

    - name: Run Rector
      run: composer refactor-dry

    - name: Check for changes
      run: |
        if ! git diff --quiet; then
          echo "Rector suggests changes. Run 'composer refactor' locally."
          exit 1
        fi
```

## PHP Insights Integration (Optional)

### Purpose
PHP Insights provides comprehensive code quality metrics, architecture analysis, and quality scoring for better code maintainability.

### When to Use
- **Quality metrics** tracking over time
- **Architecture analysis** for large projects
- **Team quality standards** enforcement
- **Code review** automation

### Installation and Configuration

**Install PHP Insights:**
```bash
composer require nunomaduro/phpinsights --dev
```

**Create insights.php configuration:**
```php
<?php

declare(strict_types=1);

return [
    'preset' => 'laravel',

    'ide' => 'phpstorm',

    'exclude' => [
        'bootstrap/cache',
        'storage',
        'vendor',
        'node_modules',
    ],

    'add' => [
        // Additional metrics
    ],

    'remove' => [
        // Remove specific insights
        \SlevomatCodingStandard\Sniffs\TypeHints\DisallowMixedTypeHintSniff::class,
    ],

    'config' => [
        \PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff::class => [
            'lineLimit' => 120,
            'absoluteLineLimit' => 160,
        ],

        \PhpCsFixer\Fixer\Import\OrderedImportsFixer::class => [
            'imports_order' => ['class', 'function', 'const'],
        ],
    ],

    'requirements' => [
        'min-quality' => 90,
        'min-complexity' => 85,
        'min-architecture' => 85,
        'min-style' => 95,
        'disable-security-check' => false,
    ],
];
```

**Add Composer Scripts:**
```json
{
    "scripts": {
        "insights": "phpinsights",
        "insights-fix": "phpinsights --fix",
        "insights-summary": "phpinsights --summary"
    }
}
```

### Usage Examples

```bash
# Run full analysis
composer insights

# Get summary report
composer insights-summary

# Fix automatically fixable issues
composer insights-fix

# Analyze specific directory
./vendor/bin/phpinsights analyse app/Services

# Generate JSON report
./vendor/bin/phpinsights --format=json > insights-report.json
```

### Quality Metrics Interpretation

**Score Categories:**
- **Code Quality (90%+)**: Complexity, maintainability, readability
- **Architecture (85%+)**: Design patterns, dependencies, structure
- **Style (95%+)**: Code formatting, naming conventions
- **Security**: Vulnerability detection and best practices

## IDE Integration (Optional)

### Purpose
Enhanced IDE configuration for improved development experience with integrated tool support.

### PHPStorm Configuration

**Create .idea/php.xml:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<project version="4">
  <component name="PhpProjectSharedConfiguration" php_language_level="8.4">
    <option name="suggestChangeDefaultLanguageLevel" value="false" />
  </component>
  <component name="PhpUnit">
    <phpunit_settings>
      <PhpUnitSettings configuration_file_path="$PROJECT_DIR$/phpunit.xml"
                       custom_loader_path="$PROJECT_DIR$/vendor/autoload.php"
                       use_configuration_file="true" />
    </phpunit_settings>
  </component>
</project>
```

**Create .idea/inspectionProfiles/Project_Default.xml:**
```xml
<component name="InspectionProjectProfileManager">
  <profile version="1.0">
    <option name="myName" value="Project Default" />
    <inspection_tool class="PhpCSValidationInspection" enabled="true" level="WEAK WARNING" enabled_by_default="true">
      <option name="CODING_STANDARD" value="Custom" />
      <option name="CUSTOM_RULESET_PATH" value="$PROJECT_DIR$/pint.json" />
    </inspection_tool>
  </profile>
</component>
```

### VSCode Configuration

**Create .vscode/settings.json:**
```json
{
    "php.validate.executablePath": "/usr/local/bin/php",
    "php.suggest.basic": false,
    "phpcs.enable": true,
    "phpcs.standard": "./pint.json",
    "phpunit.php": "/usr/local/bin/php",
    "phpunit.phpunit": "./vendor/bin/pest",
    "phpunit.args": [
        "--configuration", "./phpunit.xml"
    ],
    "files.associations": {
        "*.php": "php"
    },
    "emmet.includeLanguages": {
        "php": "html"
    }
}
```

**Create .vscode/tasks.json:**
```json
{
    "version": "2.0.0",
    "tasks": [
        {
            "label": "PHPStan Analysis",
            "type": "shell",
            "command": "composer",
            "args": ["phpstan"],
            "group": "build",
            "presentation": {
                "echo": true,
                "reveal": "always",
                "focus": false,
                "panel": "shared"
            }
        },
        {
            "label": "Pint Format",
            "type": "shell",
            "command": "composer",
            "args": ["format"],
            "group": "build"
        },
        {
            "label": "Run Tests",
            "type": "shell",
            "command": "composer",
            "args": ["test"],
            "group": "test"
        }
    ]
}
```

## Implementation Tasks (Optional)

**⚠️ Only implement these if you need advanced code quality features:**

| Task | Description | Estimated Time | Prerequisites |
|------|-------------|----------------|---------------|
| Rector Setup | Install and configure Rector for PHP 8.4+ modernization | 1.5 hours | Basic PHP setup |
| PHP Insights Integration | Set up code quality metrics and architecture analysis | 2 hours | Rector setup |
| IDE Configuration | Configure PHPStorm/VSCode for integrated tool usage | 1 hour | PHP Insights |
| CI/CD Integration | Add optional workflows for advanced tools | 1 hour | IDE configuration |

## Best Practices

### When to Use Advanced Tools

- ✅ **Large teams** that need consistent quality standards
- ✅ **Legacy codebases** requiring modernization
- ✅ **Enterprise projects** with strict quality requirements
- ✅ **Long-term maintenance** projects

### When to Skip Advanced Tools

- ✅ **Small projects** with basic requirements
- ✅ **Rapid prototyping** or proof-of-concept work
- ✅ **Learning projects** where simplicity is preferred
- ✅ **Resource-constrained** development environments

## Troubleshooting

### Common Issues

**Rector conflicts with existing code:**
- Start with smaller rule sets
- Use `--dry-run` to preview changes
- Apply changes incrementally

**PHP Insights low scores:**
- Focus on one category at a time
- Adjust thresholds in configuration
- Use `--fix` for automatically fixable issues

**IDE integration problems:**
- Verify tool paths in IDE settings
- Check PHP executable configuration
- Restart IDE after configuration changes

---

*This appendix covers optional advanced code quality tools. The core validate-links application works perfectly with just PHPStan and Pint.*

---

## 📖 Navigation

**[⬅️ Previous: Optional Database Integration](050-optional-database-integration.md)** | **[🏠 Documentation Home](../README.md)** | **[🔝 Top](#advanced-code-quality-tools)**
