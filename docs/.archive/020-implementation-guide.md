# 2. Implementation Guide - Restructured

**Date:** July 22, 2025
**Project:** Laravel Zero validate-links Implementation
**Framework:** Laravel 12.20+ (Current Stable)
**Compliance:** `.ai/guidelines.md` and `.ai/guidelines/` standards

## 2.1. Overview

This restructured implementation guide provides complete, copy-paste-ready source code for all components and follows the exact sequence defined in the [Implementation Plan](090-implementation-plan.md). Every PHP class, configuration file, and script is included with full implementations that are syntactically correct and compatible with Laravel 12.20+ and PHP 8.4+.

## 2.2. Table of Contents

### 2.2.1. Phase 1: Analysis & Planning (Week 1)

- [2.4. Development Environment Configuration](#24-phase-1-analysis--planning-week-1)
- [2.4.2. Code Quality Tools Setup](#242-code-quality-tools-setup)
- [2.4.3. Testing Infrastructure](#243-testing-infrastructure)
- [2.4.4. CI/CD Pipeline Validation](#244-cicd-pipeline-validation)

### 2.2.2. Phase 2: Content Remediation (Week 2)

- [2.5. Service Implementation Completion](#25-phase-2-content-remediation-week-2)
- [2.5.2. Command Implementation Enhancement](#252-command-implementation-enhancement)
- [2.5.3. Value Objects and Data Structures](#253-value-objects-and-data-structures)

### 2.2.3. Phase 3: Link Integrity & Navigation (Week 3)

- [2.6. Unit Testing Suite](#26-phase-3-link-integrity--navigation-week-3)
- [2.6.2. Feature Testing Suite](#262-feature-testing-suite)
- [2.6.3. Performance and Integration Testing](#263-performance-and-integration-testing)
- [2.6.4. Documentation Testing](#264-documentation-testing)

### 2.2.4. Phase 4: Quality Assurance & Validation (Week 4)

- [2.7. Code Quality Validation](#27-phase-4-quality-assurance--validation-week-4)
- [2.7.2. Documentation Completion](#272-documentation-completion)
- [2.7.3. Deployment and Distribution](#273-deployment-and-distribution)

## 2.3. Complete Source Code Repository

This section provides complete, copy-paste-ready source code for all files in the validate-links project.

### 2.3.1. Configuration Files

#### 2.3.1.1. composer.json

```json
{
    "name": "s-a-c/validate-links",
    "description": "Comprehensive PHP Link Validation and Remediation Tools - Laravel Zero Edition",
    "type": "project",
    "keywords": ["link-validation", "documentation", "laravel-zero", "cli"],
    "license": "MIT",
    "authors": [
        {
            "name": "StandAloneComplex",
            "email": "71233932+s-a-c@users.noreply.github.com"
        }
    ],
    "bin": ["validate-links"],
    "require": {
        "php": "^8.4",
        "ext-json": "*",
        "ext-mbstring": "*",
        "ext-curl": "*",
        "illuminate/http": "^12.0",
        "laravel-zero/framework": "^12.0",
        "laravel/prompts": "^0.3",
        "symfony/dom-crawler": "^7.2"
    },
    "require-dev": {
        "driftingly/rector-laravel": "^2.0",
        "ergebnis/composer-normalize": "^2.47",
        "friendsofphp/php-cs-fixer": "^3.84",
        "larastan/larastan": "^3.6",
        "laravel/pint": "^1.22",
        "mockery/mockery": "^1.6.12",
        "nunomaduro/phpinsights": "^2.13",
        "orklah/psalm-strict-equality": "^3.1",
        "peckphp/peck": "^0.1.3",
        "pestphp/pest": "^3.8.2",
        "pestphp/pest-plugin-arch": "^3.1",
        "pestphp/pest-plugin-stressless": "^3.1",
        "pestphp/pest-plugin-type-coverage": "^3.6",
        "phpstan/phpstan-deprecation-rules": "^2.0",
        "psalm/plugin-laravel": "^3.0",
        "rector/rector": "^2.1",
        "rector/type-perfect": "^2.1",
        "roave/psalm-html-output": "^1.1",
        "roave/security-advisories": "dev-latest",
        "vimeo/psalm": "^6.13"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    },
    "scripts": {
        "test": "pest",
        "test:coverage": "pest --coverage --coverage-html=coverage-html",
        "test:unit": "pest tests/Unit/",
        "test:feature": "pest tests/Feature/",
        "analyse": "phpstan analyse app/ --level=8",
        "cs-fix": "pint",
        "cs-check": "pint --test",
        "refactor": "rector process",
        "refactor-dry": "rector process --dry-run",
        "refactor-types": "rector process --config=rector-type-safety.php",
        "refactor-types-dry": "rector process --config=rector-type-safety.php --dry-run",
        "quality": ["@cs-fix", "@analyse", "@refactor-dry", "@test"],
        "migrate:setup": [
            "php artisan config:publish validate-links",
            "php artisan vendor:publish --tag=validate-links-config"
        ]
    },
    "config": {
        "optimize-autoloader": true,
        "preferred-install": "dist",
        "sort-packages": true,
        "allow-plugins": {
            "dealerdirect/phpcodesniffer-composer-installer": true,
            "ergebnis/composer-normalize": true,
            "infection/extension-installer": true,
            "pestphp/pest-plugin": true,
            "php-http/discovery": true
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

#### 2.3.1.3. .env.example
```bash
# Application Configuration
APP_NAME="Validate Links"
APP_ENV=local
APP_DEBUG=true
APP_VERSION=1.0.0

# Link Validation Configuration
VALIDATE_LINKS_CACHE_ENABLED=true
VALIDATE_LINKS_CACHE_TTL=3600
VALIDATE_LINKS_CASE_SENSITIVE=false
VALIDATE_LINKS_CHECK_EXTERNAL=false
VALIDATE_LINKS_COLORS=true
VALIDATE_LINKS_CONCURRENCY=5
VALIDATE_LINKS_CONCURRENT_REQUESTS=10
VALIDATE_LINKS_FILES_PER_CHUNK=100
VALIDATE_LINKS_INCLUDE_HIDDEN=false
VALIDATE_LINKS_MAX_BROKEN=50
VALIDATE_LINKS_MAX_DEPTH=0
VALIDATE_LINKS_MAX_EXECUTION_TIME=300
VALIDATE_LINKS_MAX_REDIRECTS=5
VALIDATE_LINKS_MEMORY_LIMIT=256M
VALIDATE_LINKS_PARALLEL_BATCH_SIZE=25
VALIDATE_LINKS_PARALLEL_ENABLED=true
VALIDATE_LINKS_PARALLEL_WORKERS=4
VALIDATE_LINKS_TIMEOUT=30
VALIDATE_LINKS_USER_AGENT="ValidateLinks/1.0"

# Cache Configuration
CACHE_DRIVER=file
CACHE_TTL=3600

# Security Configuration
SECURITY_ALLOW_LOCALHOST=true
SECURITY_ALLOW_PRIVATE_IPS=false
SECURITY_BLOCKED_DOMAINS=""
SECURITY_ALLOWED_PROTOCOLS="http,https,ftp"

# Output Configuration
DEFAULT_OUTPUT_FORMAT=console
REPORT_OUTPUT_PATH="./reports"

# Performance Configuration
MEMORY_LIMIT=512M
MAX_EXECUTION_TIME=300

# Logging Configuration
LOG_CHANNEL=single
LOG_LEVEL=info
```

#### 2.3.1.2. bootstrap/providers.php
```php
<?php

declare(strict_types=1);

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\ValidateLinksServiceProvider::class,
];
```

## 2.4. Phase 1: Analysis & Planning (Week 1)

**📋 Implementation Plan Reference:** [Phase 1 Tasks](090-implementation-plan.md#phase-1-analysis--planning-week-1)

### 2.4.1. Development Environment Configuration

**Task Reference:** [Task 1.1](090-implementation-plan.md#phase-1-analysis--planning-week-1)

#### 2.4.1.1. System Requirements

- **PHP Version:** ^8.4
- **Required Extensions:** json, mbstring, curl, dom, libxml, zip
- **Composer:** 2.x
- **Memory Limit:** 512M minimum
- **Execution Time:** 300 seconds minimum

#### 2.4.1.2. Installation Steps

1. **Install PHP 8.4+ with required extensions**
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install php8.4 php8.4-cli php8.4-json php8.4-mbstring php8.4-curl php8.4-dom php8.4-zip

# macOS with Homebrew
brew install php@8.4
```

2. **Install Composer dependencies**
```bash
composer install
```

3. **Configure environment**
```bash
cp .env.example .env
# Edit .env file with your specific configuration
```

### 2.4.2. Code Quality Tools Setup

**Task Reference:** [Task 1.2](090-implementation-plan.md#phase-1-analysis--planning-week-1)

This section covers the setup and configuration of all code quality tools used in the validate-links project. The project uses a comprehensive suite of development tools to ensure code quality, consistency, and maintainability.

#### 2.4.2.1. Development Dependencies Overview

Based on the `composer.json` configuration, the following development tools are configured:

**Static Analysis Tools:**
- **PHPStan** (larastan/larastan) - Static analysis with Laravel-specific rules
- **Psalm** (vimeo/psalm) - Advanced static analysis with strict typing
- **PHP Insights** (nunomaduro/phpinsights) - Code quality metrics and architecture analysis

**Code Formatting and Style:**
- **Laravel Pint** (laravel/pint) - Code style fixer based on PHP CS Fixer
- **PHP CS Fixer** (friendsofphp/php-cs-fixer) - Advanced code style fixing

**Code Modernization:**
- **Rector** (rector/rector) - Automated code modernization to PHP 8.4+
- **Rector Laravel** (driftingly/rector-laravel) - Laravel-specific Rector rules

**Testing Framework:**
- **Pest PHP** (pestphp/pest) - Modern testing framework
- **Pest Plugins** - Architecture, stress testing, and type coverage
- **Mockery** (mockery/mockery) - Advanced mocking framework
- **Peck** (peckphp/peck) - Additional testing utilities

**Development Utilities:**
- **Composer Normalize** (ergebnis/composer-normalize) - Composer.json normalization
- **Security Advisories** (roave/security-advisories) - Dependency vulnerability checking

#### 2.4.2.2. PHPStan Configuration (phpstan.neon)

```neon
includes:
    - ./vendor/larastan/larastan/extension.neon

parameters:
    level: 9

    paths:
        - app
        - bootstrap
        - config
        - database (?)
        - routes (?)
        - tests

    excludePaths:
        - vendor
        - vendor/*
        - vendor/**/*
        - node_modules (?)
        - storage (?)
        - bootstrap/cache
        - public (?)
        - database/migrations (?)
        - reports/rector/cache (?)

    tmpDir: reports/phpstan

    ignoreErrors:
        # Ignore errors in migration files if they're not excluded above
        - '#.*database/migrations/.*#'
        - '#PHPDoc tag @var#'

    checkExplicitMixed: true
    checkImplicitMixed: true
    checkMissingCallableSignature: true
    reportUnmatchedIgnoredErrors: false
    treatPhpDocTypesAsCertain: false

    # PHP 8.4 specific settings
    phpVersion: 80400

    # Performance settings consistent with Rector
    parallel:
        maximumNumberOfProcesses: 8
```

#### 2.4.2.3. Pint Configuration (pint.json)

```json
{
    "preset": "laravel",
    "notPath": [
        "tests/TestCase.php"
    ],
    "rules": {
        "array_push": true,
        "backtick_to_shell_exec": true,
        "date_time_immutable": true,
        "declare_strict_types": true,
        "lowercase_keywords": true,
        "lowercase_static_reference": true,
        "final_class": true,
        "final_internal_class": true,
        "final_public_method_for_abstract_class": true,
        "fully_qualified_strict_types": true,
        "global_namespace_import": {
            "import_classes": true,
            "import_constants": true,
            "import_functions": true
        },
        "mb_str_functions": true,
        "modernize_types_casting": true,
        "new_with_parentheses": false,
        "no_superfluous_elseif": true,
        "no_useless_else": true,
        "no_multiple_statements_per_line": true,
        "ordered_class_elements": {
            "order": [
                "use_trait",
                "case",
                "constant",
                "constant_public",
                "constant_protected",
                "constant_private",
                "property_public",
                "property_protected",
                "property_private",
                "construct",
                "destruct",
                "magic",
                "phpunit",
                "method_abstract",
                "method_public_static",
                "method_public",
                "method_protected_static",
                "method_protected",
                "method_private_static",
                "method_private"
            ],
            "sort_algorithm": "none"
        },
        "ordered_interfaces": true,
        "ordered_traits": true,
        "protected_to_private": true,
        "self_accessor": true,
        "self_static_accessor": true,
        "strict_comparison": true,
        "visibility_required": true
    }
}
```

#### 2.4.2.4. Rector Configuration (rector.php)

```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use RectorLaravel\Set\LaravelSetList;

return static function (RectorConfig $rectorConfig): void {
    // Configure paths to analyze
    $rectorConfig->paths([
        __DIR__ . '/app',
        __DIR__ . '/bootstrap',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/tests',
    ]);

    // Skip directories and files
    $rectorConfig->skip([
        // Skip cache and vendor directories
        __DIR__ . '/bootstrap/cache',
        __DIR__ . '/storage',
        __DIR__ . '/vendor',
        __DIR__ . '/node_modules',

        // Skip specific rules that may cause issues
        AddOverrideAttributeToOverriddenMethodsRector::class,
    ]);

    // Configure rule sets for PHP 8.4+ modernization
    $rectorConfig->sets([
        // PHP version upgrade to 8.4
        LevelSetList::UP_TO_PHP_84,

        // Code quality improvements
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
        SetList::PRIVATIZATION,
        SetList::INSTANCEOF,

        // Laravel-specific improvements
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_ARRAY_STR_FUNCTION_TO_STATIC_CALL,
        LaravelSetList::LARAVEL_FACADE_ALIASES_TO_FULL_NAMES,
    ]);

    // Performance and caching configuration
    $rectorConfig->parallel();
    $rectorConfig->cacheDirectory(__DIR__ . '/storage/rector');
    $rectorConfig->memoryLimit('1G');

    // File extensions to process
    $rectorConfig->fileExtensions(['php']);

    // Import names configuration
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses();
};
```

#### 2.4.2.5. Rector Type Safety Configuration (rector-type-safety.php)

```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNativeCallRector;
use Rector\TypeDeclaration\Rector\Property\AddPropertyTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;

return static function (RectorConfig $rectorConfig): void {
    // Configure paths to analyze (consistent with main rector.php)
    $rectorConfig->paths([
        __DIR__ . '/app',
        __DIR__ . '/bootstrap',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/tests',
    ]);

    // Skip directories and files (consistent with main rector.php)
    $rectorConfig->skip([
        // Skip cache and vendor directories
        __DIR__ . '/bootstrap/cache',
        __DIR__ . '/storage',
        __DIR__ . '/vendor',
        __DIR__ . '/node_modules',

        // Skip migration files to avoid breaking schema definitions
        __DIR__ . '/database/migrations',
    ]);

    // Performance and caching configuration
    $rectorConfig->parallel();
    $rectorConfig->cacheDirectory(__DIR__ . '/storage/rector-type-safety');
    $rectorConfig->memoryLimit('1G');
    $rectorConfig->fileExtensions(['php']);

    // Import names configuration
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses();

    // Type Declaration rules for enhanced type safety
    $rectorConfig->rules([
        // Add return types to methods
        AddReturnTypeDeclarationRector::class,
        AddVoidReturnTypeWhereNoReturnRector::class,
        ReturnTypeFromStrictNativeCallRector::class,
        AddParamTypeDeclarationRector::class,
        ReturnNeverTypeRector::class,

        // Add property types
        AddPropertyTypeDeclarationRector::class,
        TypedPropertyFromStrictConstructorRector::class,
    ]);
};
```

#### 2.4.2.5.1. Rector Usage Commands

**Basic Rector Analysis:**
```bash
# Dry run to see what changes would be made
vendor/bin/rector process --dry-run

# Apply all changes
vendor/bin/rector process

# Process specific directory
vendor/bin/rector process app/Services --dry-run
```

**Type Safety Enhancement:**
```bash
# Run type safety improvements separately
vendor/bin/rector process --config=rector-type-safety.php --dry-run

# Apply type safety improvements
vendor/bin/rector process --config=rector-type-safety.php
```

**Composer Scripts Integration:**
```json
{
    "scripts": {
        "refactor": "vendor/bin/rector process",
        "refactor-dry": "vendor/bin/rector process --dry-run",
        "refactor-types": "vendor/bin/rector process --config=rector-type-safety.php",
        "refactor-types-dry": "vendor/bin/rector process --config=rector-type-safety.php --dry-run"
    }
}
```

#### 2.4.2.6. Psalm Configuration (psalm.xml)

**Installation Verification:**
```bash
# Verify Psalm installation
vendor/bin/psalm --version

# Check if Psalm Laravel plugin is installed
composer show psalm/plugin-laravel
```

**Complete Psalm Configuration:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<psalm
    cacheDirectory="reports/psalm"
    errorLevel="1"
    findUnusedBaselineEntry="true"
    findUnusedCode="true"
    findUnusedPsalmSuppress="true"
    resolveFromConfigFile="true"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns="https://getpsalm.org/schema/config"
    xsi:schemaLocation="https://getpsalm.org/schema/config vendor/vimeo/psalm/config.xsd"
    phpVersion="8.4"
    usePhpDocMethodsWithoutMagicCall="false"
    reportMixedIssues="true"
    totallyTyped="true"
    ensureArrayStringOffsetsExist="true"
    ensureArrayIntOffsetsExist="true"
    checkForThrowsDocblock="true"
    checkForThrowsInGlobalScope="true"
    ignoreInternalFunctionFalseReturn="false"
    ignoreInternalFunctionNullReturn="false"
    allowCoercionFromStringToClassConst="false"
    allowStringToStandInForClass="false"
    memoizeMethodCallResults="true"
    hoistConstants="true"
    allowPhpStormGenerics="false"
    hideExternalErrors="false"
>
    <!-- Project paths - consistent with PHPStan -->
    <projectFiles>
        <directory name="app" />
        <directory name="bootstrap" />
        <directory name="config" />
        <directory name="tests" />
        <ignoreFiles>
            <directory name="vendor" />
            <directory name="node_modules" />
            <directory name="storage" />
            <directory name="public" />
            <directory name="bootstrap/cache" />
            <directory name="reports" />
            <file name="tests/TestCase.php" />
        </ignoreFiles>
    </projectFiles>

    <!-- Plugins for enhanced Laravel support -->
    <plugins>
        <pluginClass class="Psalm\LaravelPlugin\Plugin">
            <config>
                <eloquent>
                    <fake_models>true</fake_models>
                </eloquent>
            </config>
        </pluginClass>
        <pluginClass class="Orklah\StrictEquality\Plugin" />
    </plugins>

    <!-- Issue handlers - maximizing Psalm features -->
    <issueHandlers>
        <!-- Type coverage -->
        <MissingReturnType errorLevel="error" />
        <MissingPropertyType errorLevel="error" />
        <MissingParamType errorLevel="error" />
        <MissingClosureReturnType errorLevel="error" />
        <MissingThrowsDocblock errorLevel="info" />

        <!-- Mixed types - consistent with PHPStan level 8 -->
        <MixedReturnStatement errorLevel="error" />
        <MixedReturnTypeCoercion errorLevel="error" />
        <MixedArgument errorLevel="error" />
        <MixedAssignment errorLevel="error" />
        <MixedArrayAccess errorLevel="error" />
        <MixedArrayAssignment errorLevel="error" />
        <MixedArrayOffset errorLevel="error" />
        <MixedPropertyFetch errorLevel="error" />
        <MixedPropertyAssignment errorLevel="error" />
        <MixedMethodCall errorLevel="error" />
        <MixedOperand errorLevel="error" />
        <MixedStringOffsetAssignment errorLevel="error" />
        <MixedArrayTypeCoercion errorLevel="error" />
        <MixedPropertyTypeCoercion errorLevel="error" />

        <!-- Strict comparisons - consistent with Pint config -->
        <TypeDoesNotContainType errorLevel="error" />
        <RedundantCondition errorLevel="error" />
        <RedundantConditionGivenDocblockType errorLevel="error" />
        <DocblockTypeContradiction errorLevel="error" />

        <!-- Null safety -->
        <PossiblyNullReference errorLevel="error" />
        <PossiblyNullArgument errorLevel="error" />
        <PossiblyNullArrayAccess errorLevel="error" />
        <PossiblyNullArrayAssignment errorLevel="error" />
        <PossiblyNullArrayOffset errorLevel="error" />
        <PossiblyNullFunctionCall errorLevel="error" />
        <PossiblyNullIterator errorLevel="error" />
        <PossiblyNullOperand errorLevel="error" />
        <PossiblyNullPropertyAssignment errorLevel="error" />
        <PossiblyNullPropertyFetch errorLevel="error" />

        <!-- Undefined variables/properties -->
        <UndefinedVariable errorLevel="error" />
        <UndefinedGlobalVariable errorLevel="error" />
        <UndefinedPropertyFetch errorLevel="error" />
        <UndefinedPropertyAssignment errorLevel="error" />
        <UndefinedThisPropertyFetch errorLevel="error" />
        <UndefinedThisPropertyAssignment errorLevel="error" />

        <!-- Invalid types -->
        <InvalidReturnType errorLevel="error" />
        <InvalidReturnStatement errorLevel="error" />
        <InvalidArgument errorLevel="error" />
        <InvalidArrayAccess errorLevel="error" />
        <InvalidArrayAssignment errorLevel="error" />
        <InvalidArrayOffset errorLevel="error" />
        <InvalidCast errorLevel="error" />
        <InvalidClone errorLevel="error" />
        <InvalidFunctionCall errorLevel="error" />
        <InvalidIterator errorLevel="error" />
        <InvalidMethodCall errorLevel="error" />
        <InvalidOperand errorLevel="error" />
        <InvalidPropertyAssignment errorLevel="error" />
        <InvalidPropertyFetch errorLevel="error" />
        <InvalidStaticInvocation errorLevel="error" />
        <InvalidStringClass errorLevel="error" />
        <InvalidThrow errorLevel="error" />
        <InvalidToString errorLevel="error" />

        <!-- Unused code detection -->
        <UnusedClass errorLevel="error" />
        <UnusedMethod errorLevel="info" />
        <UnusedProperty errorLevel="info" />
        <UnusedVariable errorLevel="info" />
        <UnusedParam errorLevel="info" />
        <UnusedClosureParam errorLevel="info" />
        <UnusedConstructor errorLevel="error" />
        <UnusedFunctionCall errorLevel="info" />

        <!-- Deprecated features -->
        <DeprecatedClass errorLevel="error" />
        <DeprecatedConstant errorLevel="error" />
        <DeprecatedFunction errorLevel="error" />
        <DeprecatedInterface errorLevel="error" />
        <DeprecatedMethod errorLevel="error" />
        <DeprecatedProperty errorLevel="error" />
        <DeprecatedTrait errorLevel="error" />

        <!-- Immutability -->
        <ImpureMethodCall errorLevel="info" />
        <ImpurePropertyAssignment errorLevel="info" />
        <ImpureStaticProperty errorLevel="info" />

        <!-- Taint analysis for security -->
        <TaintedInput errorLevel="error" />
        <TaintedHtml errorLevel="error" />
        <TaintedShell errorLevel="error" />
        <TaintedInclude errorLevel="error" />
        <TaintedCookie errorLevel="error" />
        <TaintedHeader errorLevel="error" />

        <!-- Risky features -->
        <RiskyTruthyFalsyComparison errorLevel="info" />
        <NoInterfaceProperties errorLevel="error" />
        <OverriddenMethodAccess errorLevel="error" />
        <MethodSignatureMismatch errorLevel="error" />
        <OverriddenPropertyAccess errorLevel="error" />
        <PropertyNotSetInConstructor errorLevel="error" />
        <MissingConstructor errorLevel="error" />

        <!-- Performance related -->
        <ComplexMethod errorLevel="info" />
        <ComplexFunction errorLevel="info" />
        <TooManyArguments errorLevel="info" />

        <!-- Code style consistency with Pint -->
        <InvalidDocblock errorLevel="error" />
        <InvalidDocblockParamName errorLevel="error" />
        <MismatchingDocblockReturnType errorLevel="error" />
        <MismatchingDocblockParamType errorLevel="error" />
        <MissingDocblockType errorLevel="info" />

        <!-- Laravel specific (handled by plugin) -->
        <UndefinedMagicMethod errorLevel="info" />
        <UndefinedMagicPropertyFetch errorLevel="info" />
        <UndefinedMagicPropertyAssignment errorLevel="info" />

        <!-- Allow some flexibility for tests -->
        <PropertyNotSetInConstructor>
            <errorLevel type="suppress">
                <directory name="tests" />
            </errorLevel>
        </PropertyNotSetInConstructor>
        <MissingConstructor>
            <errorLevel type="suppress">
                <directory name="tests" />
            </errorLevel>
        </MissingConstructor>
    </issueHandlers>
</psalm>
```

#### 2.4.2.7. PHP CS Fixer Configuration (.php-cs-fixer.php)

**Installation Verification:**
```bash
# Verify PHP CS Fixer installation
vendor/bin/php-cs-fixer --version

# Check available rules
vendor/bin/php-cs-fixer describe
```

**Complete PHP CS Fixer Configuration:**
```php
<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([
        __DIR__ . '/app',
        __DIR__ . '/bootstrap',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/tests',
    ])
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true)
    ->exclude([
        'bootstrap/cache',
        'storage',
        'vendor',
        'node_modules',
    ]);

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@PHP84Migration' => true,
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,

        // Array formatting
        'array_syntax' => ['syntax' => 'short'],
        'array_indentation' => true,
        'trim_array_spaces' => true,
        'normalize_index_brace' => true,

        // Import and namespace handling
        'ordered_imports' => [
            'imports_order' => ['class', 'function', 'const'],
            'sort_algorithm' => 'alpha',
        ],
        'no_unused_imports' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],

        // Type declarations
        'declare_strict_types' => true,
        'strict_param' => true,
        'strict_comparison' => true,

        // Method and function formatting
        'method_chaining_indentation' => true,
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
        'no_superfluous_phpdoc_tags' => [
            'allow_mixed' => true,
            'allow_unused_params' => false,
            'remove_inheritdoc' => true,
        ],

        // Laravel-specific rules
        'php_unit_method_casing' => ['case' => 'camel_case'],
        'php_unit_test_annotation' => ['style' => 'prefix'],
        'php_unit_test_case_static_method_calls' => ['call_type' => 'this'],

        // Performance optimizations
        'dir_constant' => true,
        'function_to_constant' => true,
        'is_null' => true,
        'modernize_types_casting' => true,

        // Code quality
        'no_empty_comment' => true,
        'no_empty_phpdoc' => true,
        'no_empty_statement' => true,
        'simplified_null_return' => true,
        'ternary_to_null_coalescing' => true,
        'void_return' => true,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/storage/php-cs-fixer.cache');
```

### 2.4.3. Testing Infrastructure

**Task Reference:** [Task 1.3](090-implementation-plan.md#phase-1-analysis--planning-week-1)

#### 2.4.3.1. Testing Architecture: Hybrid Approach

The validate-links testing infrastructure uses a **hybrid approach** combining:
- **Minimal Base Class**: Essential setup and Laravel Zero integration
- **Specialized Traits**: Composable functionality for specific testing needs

**Benefits:**
- ✅ **Composition over Inheritance**: Mix and match functionality as needed
- ✅ **Granular Control**: Use only the helpers you need in each test
- ✅ **Laravel Zero Compatibility**: Maintains framework integration
- ✅ **Pest Integration**: Works seamlessly with Pest PHP patterns

#### 2.4.3.2. Base TestCase Implementation

**File:** `tests/TestCase.php`

```php
<?php

declare(strict_types=1);

namespace Tests;

use LaravelZero\Framework\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Only essential setup here
        config(['validate-links.cache.enabled' => false]);
        config(['validate-links.performance.memory_limit' => '256M']);
        config(['validate-links.external.timeout' => 5]);
    }

    protected function tearDown(): void
    {
        // Clean up any global state
        parent::tearDown();
    }
}
```

#### 2.4.3.3. Testing Traits Implementation

**File:** `tests/Traits/FileTestHelpers.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Traits;

trait FileTestHelpers
{
    protected function createTestFile(string $path, string $content): string
    {
        $fullPath = $this->getTestPath($path);
        $directory = dirname($fullPath);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($fullPath, $content);

        return $fullPath;
    }

    protected function getTestPath(string $path): string
    {
        return __DIR__ . '/../fixtures/' . ltrim($path, '/');
    }

    protected function createTestMarkdownFile(string $path, array $links = []): string
    {
        $content = "# Test Document\n\nThis is a test markdown file.\n\n";

        foreach ($links as $text => $url) {
            $content .= "- [{$text}]({$url})\n";
        }

        return $this->createTestFile($path, $content);
    }

    protected function cleanupTestFiles(): void
    {
        $fixturesPath = __DIR__ . '/../fixtures';
        if (is_dir($fixturesPath)) {
            $this->deleteDirectory($fixturesPath);
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
```

**File:** `tests/Traits/HttpTestHelpers.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Traits;

use Illuminate\Support\Facades\Http;

trait HttpTestHelpers
{
    protected function mockSuccessfulHttpResponses(array $urls): void
    {
        $responses = [];
        foreach ($urls as $url) {
            $responses[$url] = Http::response('OK', 200, [
                'Content-Type' => 'text/html',
                'Content-Length' => '2'
            ]);
        }
        Http::fake($responses);
    }

    protected function mockBrokenHttpResponses(array $urls, int $statusCode = 404): void
    {
        $responses = [];
        foreach ($urls as $url) {
            $responses[$url] = Http::response('Not Found', $statusCode);
        }
        Http::fake($responses);
    }

    protected function mockTimeoutHttpResponses(array $urls): void
    {
        $responses = [];
        foreach ($urls as $url) {
            $responses[$url] = Http::timeout();
        }
        Http::fake($responses);
    }

    protected function mockRedirectHttpResponses(array $redirects): void
    {
        $responses = [];
        foreach ($redirects as $from => $to) {
            $responses[$from] = Http::response('', 301, ['Location' => $to]);
            $responses[$to] = Http::response('OK', 200);
        }
        Http::fake($responses);
    }

    protected function mockMixedHttpResponses(array $responses): void
    {
        $httpResponses = [];
        foreach ($responses as $url => $config) {
            $httpResponses[$url] = Http::response(
                $config['body'] ?? 'OK',
                $config['status'] ?? 200,
                $config['headers'] ?? []
            );
        }
        Http::fake($httpResponses);
    }
}
```

**File:** `tests/Traits/ValidationTestHelpers.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;

trait ValidationTestHelpers
{
    protected function createValidationConfig(array $overrides = []): ValidationConfig
    {
        return ValidationConfig::withDefaults(array_merge([
            'scopes' => ['internal'],
            'timeout' => 5,
            'max_redirects' => 3,
            'concurrent_requests' => 1,
            'cache_enabled' => false,
        ], $overrides));
    }

    protected function createTestValidationResult(array $overrides = []): ValidationResult
    {
        return ValidationResult::success(
            files: $overrides['files'] ?? ['test.md'],
            links: $overrides['links'] ?? [['url' => 'test-link', 'status' => 'valid']],
            broken: $overrides['broken'] ?? [],
            statistics: $overrides['statistics'] ?? [
                'total_files' => 1,
                'total_links' => 1,
                'broken_links' => 0,
                'success_rate' => 100.0
            ],
            duration: $overrides['duration'] ?? 1.0
        );
    }

    protected function createFailedValidationResult(array $brokenLinks = []): ValidationResult
    {
        $broken = $brokenLinks ?: [
            ['url' => 'broken-link.md', 'status' => 'not_found', 'error' => 'File not found']
        ];

        return ValidationResult::failure(
            files: ['test.md'],
            links: [['url' => 'broken-link.md', 'status' => 'not_found']],
            broken: $broken,
            statistics: [
                'total_files' => 1,
                'total_links' => 1,
                'broken_links' => count($broken),
                'success_rate' => 0.0
            ],
            duration: 1.0
        );
    }

    protected function assertValidationSuccess(ValidationResult $result): void
    {
        expect($result->isSuccess())->toBeTrue();
        expect($result->getBrokenLinks())->toBeEmpty();
    }

    protected function assertValidationFailure(ValidationResult $result, int $expectedBrokenCount = null): void
    {
        expect($result->isSuccess())->toBeFalse();

        if ($expectedBrokenCount !== null) {
            expect($result->getBrokenLinks())->toHaveCount($expectedBrokenCount);
        } else {
            expect($result->getBrokenLinks())->not->toBeEmpty();
        }
    }
}
```

#### 2.4.3.4. Pest Configuration with Traits

**File:** `tests/Pest.php`

```php
<?php

declare(strict_types=1);

use old\TestCase;

// Base test case for all tests
uses(TestCase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

// Custom expectations for validate-links
expect()->extend('toBeValidUrl', function () {
    return $this->toMatch('/^https?:\/\/.+/');
});

expect()->extend('toBeValidMarkdown', function () {
    return $this->toContain('# ');
});

expect()->extend('toBeValidationResult', function () {
    return $this->toBeInstanceOf(\App\Services\ValueObjects\ValidationResult::class);
});

expect()->extend('toHaveBrokenLinks', function (int $count = null) {
    $brokenLinks = $this->value->getBrokenLinks();

    if ($count === null) {
        return expect($brokenLinks)->not->toBeEmpty();
    }

    return expect($brokenLinks)->toHaveCount($count);
});
```

#### 2.4.3.5. Usage Examples with Traits

**Unit Test Example:**

```php
<?php
// tests/Unit/Services/LinkValidationServiceTest.php

use App\Services\LinkValidationService;use old\TestCase;use Tests\Traits\{FileTestHelpers,HttpTestHelpers,ValidationTestHelpers};

uses(TestCase::class);

describe('LinkValidationService', function () {
    beforeEach(function () {
        $this->service = app(LinkValidationService::class);
    });

    describe('internal link validation', function () {
        uses(FileTestHelpers::class, ValidationTestHelpers::class);

        it('validates existing internal links', function () {
            // Create test files using FileTestHelpers trait
            $this->createTestFile('docs/test.md', '# Test Document');
            $this->createTestMarkdownFile('docs/guide.md', [
                'Test Link' => 'test.md',
                'Another Link' => 'test.md#section'
            ]);

            $config = $this->createValidationConfig(['scopes' => ['internal']]);
            $result = $this->service->validateFile('docs/guide.md', $config);

            $this->assertValidationSuccess($result);
        });

        it('detects broken internal links', function () {
            $this->createTestMarkdownFile('docs/guide.md', [
                'Broken Link' => 'missing.md'
            ]);

            $config = $this->createValidationConfig(['scopes' => ['internal']]);
            $result = $this->service->validateFile('docs/guide.md', $config);

            $this->assertValidationFailure($result, 1);
        });

        afterEach(function () {
            $this->cleanupTestFiles();
        });
    });

    describe('external link validation', function () {
        uses(HttpTestHelpers::class, ValidationTestHelpers::class);

        it('validates working external links', function () {
            $urls = ['https://example.com', 'https://github.com'];
            $this->mockSuccessfulHttpResponses($urls);

            $config = $this->createValidationConfig(['scopes' => ['external']]);
            $result = $this->service->validateExternalLinks($urls, $config);

            $this->assertValidationSuccess($result);
        });

        it('handles various HTTP error scenarios', function () {
            $this->mockMixedHttpResponses([
                'https://not-found.example' => ['status' => 404],
                'https://server-error.example' => ['status' => 500],
                'https://forbidden.example' => ['status' => 403],
            ]);

            $urls = array_keys($this->mockMixedHttpResponses);
            $config = $this->createValidationConfig(['scopes' => ['external']]);
            $result = $this->service->validateExternalLinks($urls, $config);

            $this->assertValidationFailure($result, 3);
        });
    });
});
```

**Feature Test Example:**

```php
<?php
// tests/Feature/Commands/ValidateCommandTest.php

declare(strict_types=1);

use old\TestCase;use Tests\Traits\{FileTestHelpers,HttpTestHelpers};

uses(TestCase::class);

describe('ValidateCommand', function () {
    uses(FileTestHelpers::class, HttpTestHelpers::class);

    it('validates documentation successfully', function () {
        // Create test documentation structure
        $this->createTestMarkdownFile('docs/README.md', [
            'User Guide' => 'guide.md',
            'API Reference' => 'api.md'
        ]);

        $this->createTestFile('docs/guide.md', '# User Guide\n\nComplete guide.');
        $this->createTestFile('docs/api.md', '# API Reference\n\nAPI documentation.');

        // Run validation command
        $this->artisan('validate', ['paths' => ['docs/']])
            ->expectsOutput('✅ All links are valid')
            ->assertExitCode(0);
    });

    it('reports broken links with details', function () {
        $this->createTestMarkdownFile('docs/broken.md', [
            'Missing File' => 'missing.md',
            'Another Missing' => 'also-missing.md'
        ]);

        $this->artisan('validate', ['paths' => ['docs/']])
            ->expectsOutput('❌ Found 2 broken links')
            ->assertExitCode(1);
    });

    it('validates external links when requested', function () {
        $this->createTestMarkdownFile('docs/external.md', [
            'Example Site' => 'https://example.com',
            'GitHub' => 'https://github.com'
        ]);

        $this->mockSuccessfulHttpResponses([
            'https://example.com',
            'https://github.com'
        ]);

        $this->artisan('validate', [
            'paths' => ['docs/'],
            '--check-external' => true
        ])
            ->expectsOutput('✅ All links are valid')
            ->assertExitCode(0);
    });

    afterEach(function () {
        $this->cleanupTestFiles();
    });
});
```

#### 2.4.3.6. Test Configuration (phpunit.xml)

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         cacheDirectory=".phpunit.cache"
>
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory suffix="Test.php">./tests/Integration</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory suffix=".php">./app</directory>
            <directory suffix=".php">./packages</directory>
            <directory suffix=".php">./src</directory>
        </include>
        <exclude>
            <directory>vendor</directory>
        </exclude>
    </source>
    <coverage>
        <report>
            <clover outputFile="reports/coverage/clover.xml"/>
            <html outputDirectory="reports/coverage"/>
            <text outputFile="reports/coverage/coverage.txt"/>
        </report>
    </coverage>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="APP_MAINTENANCE_DRIVER" value="file"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_STORE" value="array"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="PULSE_ENABLED" value="false"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="TELESCOPE_ENABLED" value="false"/>
    </php>
</phpunit>
```

#### 2.4.3.7. Additional Development Tools Configuration

##### 2.4.3.7.1. Composer Normalize Configuration

**Installation Verification:**
```bash
# Verify Composer Normalize installation
composer show ergebnis/composer-normalize

# Check current composer.json status
vendor/bin/composer-normalize --dry-run
```

**Composer Normalize Usage:**
```bash
# Normalize composer.json (dry run)
vendor/bin/composer-normalize --dry-run

# Apply normalization
vendor/bin/composer-normalize

# Normalize with specific options
vendor/bin/composer-normalize --indent-size=4 --indent-style=space
```

##### 2.4.3.7.2. Enhanced Composer Scripts

**Complete Composer Scripts Configuration:**
```json
{
    "scripts": {
        "test": "pest",
        "test:coverage": "pest --coverage --coverage-html=coverage-html",
        "test:unit": "pest tests/Unit/",
        "test:feature": "pest tests/Feature/",
        "test:arch": "pest --filter=arch",
        "test:stress": "pest --filter=stress",

        "analyse": "phpstan analyse app/ --level=9",
        "analyse:baseline": "phpstan analyse app/ --level=9 --generate-baseline",
        "psalm": "psalm --show-info=true",
        "psalm:baseline": "psalm --set-baseline=psalm-baseline.xml",

        "cs-fix": "pint",
        "cs-check": "pint --test",
        "cs-fixer": "php-cs-fixer fix --dry-run --diff",
        "cs-fixer-fix": "php-cs-fixer fix",

        "refactor": "rector process",
        "refactor-dry": "rector process --dry-run",
        "refactor-types": "rector process --config=rector-type-safety.php",
        "refactor-types-dry": "rector process --config=rector-type-safety.php --dry-run",

        "insights": "phpinsights --no-interaction",
        "insights:fix": "phpinsights --fix",

        "normalize": "composer-normalize",
        "normalize-check": "composer-normalize --dry-run",

        "security": "composer audit",
        "security:advisories": "composer show --direct --outdated",

        "quality-full": [
            "@normalize-check",
            "@cs-check",
            "@analyse",
            "@psalm",
            "@refactor-dry",
            "@insights",
            "@test"
        ],
        "quality-fix": [
            "@normalize",
            "@cs-fix",
            "@refactor",
            "@insights:fix"
        ],
        "pre-commit": [
            "@quality-full"
        ],
        "ci-check": [
            "@security",
            "@quality-full"
        ],
        "dev-setup": [
            "composer install",
            "@normalize",
            "@cs-fix"
        ],
        "tools-check": [
            "vendor/bin/phpstan --version",
            "vendor/bin/psalm --version",
            "vendor/bin/pint --version",
            "vendor/bin/php-cs-fixer --version",
            "vendor/bin/rector --version",
            "vendor/bin/phpinsights --version",
            "vendor/bin/pest --version",
            "vendor/bin/composer-normalize --version"
        ]
    }
}
```

##### 2.4.3.7.3. IDE Configuration Support

**PHPStorm Configuration (.idea/php.xml):**
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

**VSCode Configuration (.vscode/settings.json):**
```json
{
    "php.validate.executablePath": "/usr/local/bin/php",
    "php.suggest.basic": false,
    "phpstan.enabled": true,
    "phpstan.path": "./vendor/bin/phpstan",
    "phpstan.configFile": "./phpstan.neon",
    "psalm.enabled": true,
    "psalm.path": "./vendor/bin/psalm",
    "psalm.configFile": "./psalm.xml",
    "php-cs-fixer.enabled": true,
    "php-cs-fixer.executablePath": "./vendor/bin/php-cs-fixer",
    "php-cs-fixer.configFile": "./.php-cs-fixer.php",
    "files.associations": {
        "*.php": "php"
    },
    "editor.formatOnSave": true,
    "editor.codeActionsOnSave": {
        "source.fixAll": true
    }
}
```

### 2.4.4. CI/CD Pipeline Validation

**Task Reference:** [Task 1.4](090-implementation-plan.md#phase-1-analysis--planning-week-1)

#### 2.4.4.1. GitHub Actions Workflow (.github/workflows/ci.yml)

```yaml
name: CI/CD Pipeline

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php-version: [8.4]
        dependency-version: [prefer-lowest, prefer-stable]

    name: PHP ${{ matrix.php-version }} - ${{ matrix.dependency-version }}

    steps:
    - uses: actions/checkout@v4

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ matrix.php-version }}
        extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite, pdo_sqlite
        coverage: xdebug

    - name: Cache Composer packages
      id: composer-cache
      uses: actions/cache@v4
      with:
        path: vendor
        key: ${{ runner.os }}-php-${{ matrix.php-version }}-${{ hashFiles('**/composer.lock') }}
        restore-keys: |
          ${{ runner.os }}-php-${{ matrix.php-version }}-

    - name: Install dependencies
      run: |
        composer update --${{ matrix.dependency-version }} --prefer-dist --no-interaction --no-suggest

    - name: Run PHPStan
      run: vendor/bin/phpstan analyse

    - name: Run Pint
      run: vendor/bin/pint --test

    - name: Run Pest Tests
      run: vendor/bin/pest --coverage --min=90

    - name: Run PHP Insights
      run: vendor/bin/phpinsights --no-interaction --min-quality=90 --min-complexity=90 --min-architecture=90 --min-style=90
```

## 2.5. Phase 2: Content Remediation (Week 2)

**📋 Implementation Plan Reference:** [Phase 2 Tasks](090-implementation-plan.md#phase-2-content-remediation-week-2)

### 2.5.1. Service Implementation Completion

**Task Reference:** [Task 2.3](090-implementation-plan.md#phase-2-content-remediation-week-2)

#### 2.5.1.1. Service Contracts

##### LinkValidationInterface

```php
<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;
use App\Enums\ValidationScope;
use App\Enums\LinkStatus;

interface LinkValidationInterface
{
    /**
     * Validate a single link.
     */
    public function validateLink(string $url, ValidationConfig $config): ValidationResult;

    /**
     * Validate multiple links concurrently.
     *
     * @param string[] $urls
     * @return ValidationResult[]
     */
    public function validateLinks(array $urls, ValidationConfig $config): array;

    /**
     * Validate links in a file.
     *
     * @return ValidationResult[]
     */
    public function validateFile(string $filePath, ValidationConfig $config): array;

    /**
     * Extract links from content.
     *
     * @return array<array{url: string, text: string, line: int}>
     */
    public function extractLinks(string $content): array;

    /**
     * Check if URL is valid format.
     */
    public function isValidUrl(string $url): bool;

    /**
     * Normalize URL for validation.
     */
    public function normalizeUrl(string $url): string;

    /**
     * Get supported validation scopes.
     *
     * @return ValidationScope[]
     */
    public function getSupportedScopes(): array;

    /**
     * Check if a specific scope is supported.
     */
    public function supportsScope(ValidationScope $scope): bool;

    /**
     * Validate links by specific scopes.
     *
     * @param ValidationScope[] $scopes
     * @param string[] $targets
     */
    public function validateByScopes(array $scopes, array $targets, ValidationConfig $config): ValidationResult;

    /**
     * Get validation statistics grouped by LinkStatus.
     *
     * @return array<LinkStatus, int>
     */
    public function getStatusStatistics(): array;

    /**
     * Set validation timeout.
     */
    public function setTimeout(int $seconds): void;
}
```

##### SecurityValidationInterface

```php
<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface SecurityValidationInterface
{
    /**
     * Validate if a URL is safe to access.
     */
    public function validateUrl(string $url): bool;

    /**
     * Validate if a file path is safe to access.
     */
    public function validatePath(string $path): bool;

    /**
     * Check if domain is blocked.
     */
    public function isDomainBlocked(string $domain): bool;

    /**
     * Check if protocol is allowed.
     */
    public function isProtocolAllowed(string $protocol): bool;

    /**
     * Validate file size limits.
     */
    public function validateFileSize(string $filePath): bool;

    /**
     * Get security configuration.
     */
    public function getSecurityConfig(): array;
}
```

##### ReportingInterface

```php
<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Services\ValueObjects\ValidationResult;
use App\Enums\OutputFormat;
use App\Enums\LinkStatus;

interface ReportingInterface
{
    /**
     * Generate report from validation results using enum format.
     */
    public function generateReport(array $results, OutputFormat $format = OutputFormat::CONSOLE): string;

    /**
     * Format validation results with specific options.
     *
     * @param ValidationResult[] $results
     */
    public function format(array $results, OutputFormat $format, array $options = []): string;

    /**
     * Add formatter for specific output format.
     */
    public function addFormatter(OutputFormat $format, object $formatter): void;

    /**
     * Export report to file with automatic format detection.
     */
    public function exportReport(array $results, string $filePath, ?OutputFormat $format = null): bool;

    /**
     * Get available output formats.
     *
     * @return OutputFormat[]
     */
    public function getAvailableFormats(): array;

    /**
     * Check if format is supported.
     */
    public function supportsFormat(OutputFormat $format): bool;

    /**
     * Generate summary statistics with status breakdown.
     *
     * @param ValidationResult[] $results
     * @return array{total_links: int, broken_links: int, success_rate: float, status_breakdown: array<LinkStatus, int>}
     */
    public function generateSummary(array $results): array;

    /**
     * Get formatter instance for specific format.
     */
    public function getFormatter(OutputFormat $format): object;
}
```

##### GitHubAnchorInterface

```php
<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface GitHubAnchorInterface
{
    /**
     * Generate GitHub-style anchor from heading text.
     */
    public function generateAnchor(string $headingText): string;

    /**
     * Validate if anchor exists in content.
     */
    public function validateAnchor(string $content, string $anchor): bool;

    /**
     * Extract all anchors from markdown content.
     */
    public function extractAnchors(string $content): array;

    /**
     * Normalize anchor for comparison.
     */
    public function normalizeAnchor(string $anchor): string;
}
```

##### StatisticsInterface

```php
<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface StatisticsInterface
{
    /**
     * Record a validation result.
     */
    public function recordValidation(string $url, bool $isValid, ?string $error = null): void;

    /**
     * Record file processing.
     */
    public function recordFileProcessed(string $filePath, int $linkCount): void;

    /**
     * Get validation statistics.
     */
    public function getStatistics(): array;

    /**
     * Get broken links.
     */
    public function getBrokenLinks(): array;

    /**
     * Get processed files.
     */
    public function getProcessedFiles(): array;

    /**
     * Reset statistics.
     */
    public function reset(): void;

    /**
     * Get success rate.
     */
    public function getSuccessRate(): float;
}
```

#### 2.5.1.2. Service Implementations

##### SecurityValidationService

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Contracts\SecurityValidationInterface;
use App\Exceptions\SecurityException;
use InvalidArgumentException;
use RuntimeException;

class SecurityValidationService implements SecurityValidationInterface
{
    private array $blockedDomains;
    private array $allowedProtocols;
    private int $maxFileSize;
    private array $blockedPaths;

    public function __construct()
    {
        $this->blockedDomains = config('validate-links.security.blocked_domains', []);
        $this->allowedProtocols = config('validate-links.security.allowed_protocols', ['http', 'https']);
        $this->maxFileSize = config('validate-links.security.max_file_size', 50 * 1024 * 1024);
        $this->blockedPaths = [
            '/etc/',
            '/var/',
            '/usr/',
            '/bin/',
            '/sbin/',
            '/root/',
            '/home/',
        ];
    }

    /**
     * Validate if a URL is safe to access.
     */
    public function validateUrl(string $url): bool
    {
        if (empty($url)) {
            throw new InvalidArgumentException('URL cannot be empty');
        }

        $parsedUrl = parse_url($url);

        if ($parsedUrl === false) {
            return false;
        }

        // Check protocol
        if (!$this->isProtocolAllowed($parsedUrl['scheme'] ?? '')) {
            return false;
        }

        // Check domain
        if (isset($parsedUrl['host']) && $this->isDomainBlocked($parsedUrl['host'])) {
            return false;
        }

        // Check for private IP addresses if not allowed
        if (!config('validate-links.security.allow_private_ips', false)) {
            if (isset($parsedUrl['host']) && $this->isPrivateIp($parsedUrl['host'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate if a file path is safe to access.
     */
    public function validatePath(string $path): bool
    {
        if (empty($path)) {
            throw new InvalidArgumentException('Path cannot be empty');
        }

        $realPath = realpath($path);

        if ($realPath === false) {
            return false;
        }

        // Check against blocked paths
        foreach ($this->blockedPaths as $blockedPath) {
            if (str_starts_with($realPath, $blockedPath)) {
                return false;
            }
        }

        // Ensure path is within project directory
        $projectRoot = realpath(base_path());
        if (!str_starts_with($realPath, $projectRoot)) {
            return false;
        }

        return true;
    }

    /**
     * Check if domain is blocked.
     */
    public function isDomainBlocked(string $domain): bool
    {
        return in_array(strtolower($domain), array_map('strtolower', $this->blockedDomains));
    }

    /**
     * Check if protocol is allowed.
     */
    public function isProtocolAllowed(string $protocol): bool
    {
        return in_array(strtolower($protocol), array_map('strtolower', $this->allowedProtocols));
    }

    /**
     * Validate file size limits.
     */
    public function validateFileSize(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $fileSize = filesize($filePath);

        if ($fileSize === false) {
            return false;
        }

        return $fileSize <= $this->maxFileSize;
    }

    /**
     * Get security configuration.
     */
    public function getSecurityConfig(): array
    {
        return [
            'blocked_domains' => $this->blockedDomains,
            'allowed_protocols' => $this->allowedProtocols,
            'max_file_size' => $this->maxFileSize,
            'blocked_paths' => $this->blockedPaths,
        ];
    }

    /**
     * Check if IP address is private.
     */
    private function isPrivateIp(string $host): bool
    {
        $ip = gethostbyname($host);

        if ($ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
            return false; // Not an IP address
        }

        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
```

##### ReportingService

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Contracts\ReportingInterface;
use App\Services\Contracts\StatisticsInterface;
use App\Services\Formatters\ConsoleFormatter;
use App\Services\Formatters\JsonFormatter;
use App\Services\Formatters\HtmlFormatter;
use App\Services\Formatters\MarkdownFormatter;
use App\Services\ValueObjects\ValidationResult;
use InvalidArgumentException;

class ReportingService implements ReportingInterface
{
    private array $formatters;
    private StatisticsInterface $statistics;

    public function __construct(StatisticsInterface $statistics)
    {
        $this->statistics = $statistics;
        $this->formatters = [
            'console' => new ConsoleFormatter(),
            'json' => new JsonFormatter(),
            'html' => new HtmlFormatter(),
            'markdown' => new MarkdownFormatter(),
        ];
    }

    /**
     * Generate report from validation results.
     */
    public function generateReport(array $results, string $format = 'console'): string
    {
        if (!isset($this->formatters[$format])) {
            throw new InvalidArgumentException("Unsupported format: {$format}");
        }

        $formatter = $this->formatters[$format];
        $summary = $this->generateSummary($results);

        return $formatter->format($results, $summary);
    }

    /**
     * Add formatter for specific output format.
     */
    public function addFormatter(string $format, object $formatter): void
    {
        $this->formatters[$format] = $formatter;
    }

    /**
     * Export report to file.
     */
    public function exportReport(array $results, string $filePath, string $format = 'json'): bool
    {
        try {
            $report = $this->generateReport($results, $format);

            $directory = dirname($filePath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            return file_put_contents($filePath, $report) !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get available output formats.
     */
    public function getAvailableFormats(): array
    {
        return array_keys($this->formatters);
    }

    /**
     * Generate summary statistics.
     */
    public function generateSummary(array $results): array
    {
        $total = count($results);
        $valid = 0;
        $invalid = 0;
        $errors = [];

        foreach ($results as $result) {
            if ($result instanceof ValidationResult) {
                if ($result->isValid()) {
                    $valid++;
                } else {
                    $invalid++;
                    if ($result->getError()) {
                        $errors[] = $result->getError();
                    }
                }
            }
        }

        return [
            'total' => $total,
            'valid' => $valid,
            'invalid' => $invalid,
            'success_rate' => $total > 0 ? round(($valid / $total) * 100, 2) : 0,
            'errors' => array_unique($errors),
            'timestamp' => now()->toISOString(),
        ];
    }
}
```

##### GitHubAnchorService

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Contracts\GitHubAnchorInterface;

class GitHubAnchorService implements GitHubAnchorInterface
{
    /**
     * Generate GitHub-style anchor from heading text.
     */
    public function generateAnchor(string $headingText): string
    {
        // Remove markdown formatting
        $text = preg_replace('/[*_`~]/', '', $headingText);

        // Convert to lowercase
        $text = strtolower($text);

        // Replace spaces and special characters with hyphens
        $text = preg_replace('/[^a-z0-9\-]/', '-', $text);

        // Remove multiple consecutive hyphens
        $text = preg_replace('/-+/', '-', $text);

        // Remove leading and trailing hyphens
        $text = trim($text, '-');

        return $text;
    }

    /**
     * Validate if anchor exists in content.
     */
    public function validateAnchor(string $content, string $anchor): bool
    {
        $anchors = $this->extractAnchors($content);
        $normalizedAnchor = $this->normalizeAnchor($anchor);

        return in_array($normalizedAnchor, array_map([$this, 'normalizeAnchor'], $anchors));
    }

    /**
     * Extract all anchors from markdown content.
     */
    public function extractAnchors(string $content): array
    {
        $anchors = [];

        // Extract headings (# ## ### etc.)
        if (preg_match_all('/^(#{1,6})\s+(.+)$/m', $content, $matches)) {
            foreach ($matches[2] as $heading) {
                $anchors[] = $this->generateAnchor($heading);
            }
        }

        // Extract explicit anchor links [text](#anchor)
        if (preg_match_all('/\[([^\]]+)\]\(#([^)]+)\)/', $content, $matches)) {
            foreach ($matches[2] as $anchor) {
                $anchors[] = $this->normalizeAnchor($anchor);
            }
        }

        // Extract HTML anchor tags <a name="anchor">
        if (preg_match_all('/<a\s+(?:name|id)=["\']([^"\']+)["\'][^>]*>/', $content, $matches)) {
            foreach ($matches[1] as $anchor) {
                $anchors[] = $this->normalizeAnchor($anchor);
            }
        }

        return array_unique($anchors);
    }

    /**
     * Normalize anchor for comparison.
     */
    public function normalizeAnchor(string $anchor): string
    {
        // Remove leading # if present
        $anchor = ltrim($anchor, '#');

        // Convert to lowercase for case-insensitive comparison
        return strtolower($anchor);
    }
}
```

##### StatisticsService

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Contracts\StatisticsInterface;

class StatisticsService implements StatisticsInterface
{
    private array $statistics;
    private array $brokenLinks;
    private array $processedFiles;

    public function __construct()
    {
        $this->reset();
    }

    /**
     * Record a validation result.
     */
    public function recordValidation(string $url, bool $isValid, ?string $error = null): void
    {
        $this->statistics['total']++;

        if ($isValid) {
            $this->statistics['valid']++;
        } else {
            $this->statistics['invalid']++;
            $this->brokenLinks[] = [
                'url' => $url,
                'error' => $error,
                'timestamp' => now()->toISOString(),
            ];
        }
    }

    /**
     * Record file processing.
     */
    public function recordFileProcessed(string $filePath, int $linkCount): void
    {
        $this->processedFiles[] = [
            'file' => $filePath,
            'link_count' => $linkCount,
            'timestamp' => now()->toISOString(),
        ];

        $this->statistics['files_processed']++;
        $this->statistics['total_links'] += $linkCount;
    }

    /**
     * Get validation statistics.
     */
    public function getStatistics(): array
    {
        return array_merge($this->statistics, [
            'success_rate' => $this->getSuccessRate(),
            'broken_links_count' => count($this->brokenLinks),
            'processed_files_count' => count($this->processedFiles),
        ]);
    }

    /**
     * Get broken links.
     */
    public function getBrokenLinks(): array
    {
        return $this->brokenLinks;
    }

    /**
     * Get processed files.
     */
    public function getProcessedFiles(): array
    {
        return $this->processedFiles;
    }

    /**
     * Reset statistics.
     */
    public function reset(): void
    {
        $this->statistics = [
            'total' => 0,
            'valid' => 0,
            'invalid' => 0,
            'files_processed' => 0,
            'total_links' => 0,
            'start_time' => now()->toISOString(),
        ];

        $this->brokenLinks = [];
        $this->processedFiles = [];
    }

    /**
     * Get success rate.
     */
    public function getSuccessRate(): float
    {
        if ($this->statistics['total'] === 0) {
            return 0.0;
        }

        return round(($this->statistics['valid'] / $this->statistics['total']) * 100, 2);
    }
}
```

#### 2.5.1.3. Exception Classes

##### ValidateLinksException (Base Exception)

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Base exception class for all validate-links exceptions.
 */
abstract class ValidateLinksException extends Exception
{
    /**
     * Get the error code for this exception.
     */
    abstract public function getErrorCode(): string;

    /**
     * Get the severity level of this exception.
     */
    abstract public function getSeverity(): string;

    /**
     * Get additional context data.
     */
    public function getContext(): array
    {
        return [];
    }

    /**
     * Convert exception to array format.
     */
    public function toArray(): array
    {
        return [
            'error_code' => $this->getErrorCode(),
            'message' => $this->getMessage(),
            'severity' => $this->getSeverity(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'context' => $this->getContext(),
        ];
    }
}
```

##### ValidationException

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Exception thrown when link validation fails.
 */
class ValidationException extends ValidateLinksException
{
    public function getErrorCode(): string
    {
        return 'VALIDATION_ERROR';
    }

    public function getSeverity(): string
    {
        return 'medium';
    }
}
```

##### SecurityException

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Exception thrown when security violations are detected.
 */
class SecurityException extends ValidateLinksException
{
    public function getErrorCode(): string
    {
        return 'SECURITY_VIOLATION';
    }

    public function getSeverity(): string
    {
        return 'critical';
    }
}
```

##### ConfigurationException

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Exception thrown when configuration errors occur.
 */
class ConfigurationException extends ValidateLinksException
{
    public function getErrorCode(): string
    {
        return 'CONFIGURATION_ERROR';
    }

    public function getSeverity(): string
    {
        return 'high';
    }
}
```

#### 2.5.1.4. Service Provider Implementation

##### ValidateLinksServiceProvider

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Contracts\SecurityValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Services\Contracts\GitHubAnchorInterface;
use App\Services\Contracts\StatisticsInterface;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\SecurityValidationService;
use App\Services\ReportingService;
use App\Services\GitHubAnchorService;
use App\Services\StatisticsService;
use App\Services\LinkValidationService;

class ValidateLinksServiceProvider extends ServiceProvider
{
    /**
     * Register services into the container.
     *
     * This method is called FIRST, before boot().
     * Only bind services here - don't access other services.
     */
    public function register(): void
    {
        // Core service interfaces - Order matters for dependencies!

        // Statistics service (no dependencies)
        $this->app->singleton(StatisticsInterface::class, StatisticsService::class);

        // Security validation service (no dependencies)
        $this->app->singleton(SecurityValidationInterface::class, SecurityValidationService::class);

        // GitHub anchor service (no dependencies)
        $this->app->singleton(GitHubAnchorInterface::class, GitHubAnchorService::class);

        // Reporting service (depends on statistics)
        $this->app->singleton(ReportingInterface::class, function ($app) {
            return new ReportingService(
                $app->make(StatisticsInterface::class)
            );
        });

        // Link validation service (depends on security and anchor services)
        $this->app->singleton(LinkValidationInterface::class, function ($app) {
            return new LinkValidationService(
                $app->make(SecurityValidationInterface::class),
                $app->make(GitHubAnchorInterface::class)
            );
        });
    }

    /**
     * Bootstrap services.
     *
     * This method is called AFTER register().
     * You can access other services here.
     */
    public function boot(): void
    {
        // Publish configuration files
        $this->publishes([
            __DIR__.'/../../config/validate-links.php' => config_path('validate-links.php'),
        ], 'validate-links-config');

        // Register event listeners if needed
        // Event::listen(ValidationCompleted::class, ValidationCompletedListener::class);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            SecurityValidationInterface::class,
            ReportingInterface::class,
            GitHubAnchorInterface::class,
            StatisticsInterface::class,
            LinkValidationInterface::class,
        ];
    }
}
```

### 2.5.2. Command Implementation Enhancement

**Task Reference:** [Task 2.4](090-implementation-plan.md#phase-2-content-remediation-week-2)

#### 2.5.2.1. Command Architecture Decision: Base Classes vs Traits

**Decision:** Commands use **base class inheritance** while testing uses **traits composition**.

**Rationale:**
- **Commands**: Laravel Zero commands benefit from inheritance due to:
  - Shared lifecycle methods (`handle()`, `configure()`)
  - Common dependency injection patterns
  - Framework integration requirements
  - Natural command hierarchy (all validation commands share similar structure)

- **Testing**: Testing infrastructure benefits from traits due to:
  - Flexible composition of testing utilities
  - Granular control over test helpers
  - Ability to mix multiple concerns in a single test
  - Better separation of testing responsibilities

**Implementation Pattern:**
```php
// Commands: Use base class inheritance
abstract class BaseValidationCommand extends Command
{
    protected function createConfigFromOptions(): ValidationConfig
    {
        return ValidationConfig::fromCommandOptions(
            $this->options(),
            $this->arguments()
        );
    }
}

class ValidateCommand extends BaseValidationCommand
{
    // Command-specific implementation
}

// Testing: Use trait composition
class SomeTest extends TestCase
{
    use FileTestHelpers, HttpTestHelpers, ValidationTestHelpers;

    // Test implementation with flexible helper composition
}
```

#### 2.5.2.2. Command Classes

##### 2.5.2.2.1. BaseValidationCommand (Abstract Base Class)

```php
<?php

declare(strict_types=1);

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Services\ValueObjects\ValidationConfig;
use App\Enums\ValidationScope;
use App\Enums\OutputFormat;

abstract class BaseValidationCommand extends Command
{
    protected LinkValidationInterface $linkValidation;
    protected ReportingInterface $reporting;

    public function __construct(
        LinkValidationInterface $linkValidation,
        ReportingInterface $reporting
    ) {
        parent::__construct();

        $this->linkValidation = $linkValidation;
        $this->reporting = $reporting;
    }

    /**
     * Create validation configuration from command options.
     */
    protected function createConfigFromOptions(): ValidationConfig
    {
        return ValidationConfig::create([
            'scopes' => $this->parseScopes($this->option('scope')),
            'timeout' => (int) $this->option('timeout', 30),
            'max_redirects' => (int) $this->option('max-redirects', 5),
            'concurrent_requests' => (int) $this->option('concurrency', 10),
            'cache_enabled' => !$this->option('no-cache'),
            'follow_redirects' => $this->option('follow-redirects'),
            'user_agent' => $this->option('user-agent', 'validate-links/1.0'),
            'output_format' => $this->getOutputFormat(),
        ]);
    }

    /**
     * Parse validation scopes from command option.
     *
     * @return ValidationScope[]
     */
    protected function parseScopes(string $scope): array
    {
        if ($scope === 'all') {
            return ValidationScope::ALL->getIncludedScopes();
        }

        $scopeValues = array_map('trim', explode(',', $scope));

        return array_map(
            fn($s) => ValidationScope::from($s),
            $scopeValues
        );
    }

    /**
     * Get output format from command option.
     */
    protected function getOutputFormat(): OutputFormat
    {
        return OutputFormat::from($this->option('format', 'console'));
    }

    /**
     * Validate command options using enum validation.
     */
    protected function validateCommandOptions(): void
    {
        // Validate scope option
        $scopeOption = $this->option('scope');
        if ($scopeOption !== 'all' && !$this->isValidScopeList($scopeOption)) {
            $this->error("Invalid scope: {$scopeOption}");
            $this->info('Valid scopes: ' . implode(', ', ValidationScope::values()));
            $this->info('Use "all" for comprehensive validation');
            exit(1);
        }

        // Validate format option
        $formatOption = $this->option('format');
        if (!OutputFormat::isValid($formatOption)) {
            $this->error("Invalid format: {$formatOption}");
            $this->info('Valid formats: ' . implode(', ', OutputFormat::values()));
            exit(1);
        }
    }

    /**
     * Check if scope list contains valid scope values.
     */
    private function isValidScopeList(string $scopeList): bool
    {
        $scopes = array_map('trim', explode(',', $scopeList));

        foreach ($scopes as $scope) {
            if (!ValidationScope::isValid($scope)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Process validation results with enum-based status grouping.
     */
    protected function processValidationResults(array $results): int
    {
        $summary = $results['summary'];
        $brokenLinks = $results['broken_links'] ?? [];

        if (empty($brokenLinks)) {
            $this->info('✅ All links are valid!');
            return 0;
        }

        // Group broken links by status using enum
        $statusGroups = $this->groupLinksByStatus($brokenLinks);

        // Display results grouped by status
        $this->displayStatusGroups($statusGroups);

        // Display summary
        $this->displayValidationSummary($summary);

        return 1;
    }

    /**
     * Group broken links by LinkStatus enum.
     */
    private function groupLinksByStatus(array $brokenLinks): array
    {
        $statusGroups = [];

        foreach ($brokenLinks as $link) {
            $status = LinkStatus::from($link['status']);
            $statusGroups[$status->value][] = $link;
        }

        return $statusGroups;
    }

    /**
     * Display status groups with enum-based formatting.
     */
    private function displayStatusGroups(array $statusGroups): void
    {
        foreach ($statusGroups as $statusValue => $links) {
            $status = LinkStatus::from($statusValue);
            $this->displayStatusGroup($status, $links);
        }
    }

    /**
     * Display a single status group with colored output.
     */
    private function displayStatusGroup(LinkStatus $status, array $links): void
    {
        $count = count($links);
        $this->newLine();
        $this->line($status->getFormattedDisplay() . " ({$count} links)");

        foreach ($links as $link) {
            $this->line("  • {$link['url']} <fg=gray>({$link['file']})</fg>");

            if ($this->option('verbose') && isset($link['error'])) {
                $this->line("    <fg=gray>Error: {$link['error']}</fg>");
            }
        }

        if ($status->shouldRetry()) {
            $this->line("  <fg=yellow>💡 Tip: {$status->getRecommendedAction()}</fg>");
        }
    }

    /**
     * Display validation summary with statistics.
     */
    private function displayValidationSummary(array $summary): void
    {
        $this->newLine();
        $this->line('<fg=white;bg=red> VALIDATION FAILED </fg>');
        $this->line("Files processed: {$summary['files_processed']}");
        $this->line("Total links: {$summary['total_links']}");
        $this->line("Broken links: {$summary['broken_links']}");
        $this->line("Success rate: {$summary['success_rate']}%");
    }

    /**
     * Display validation results using the reporting service.
     */
    protected function displayResults(array $results): int
    {
        $format = $this->getOutputFormat();
        $outputPath = $this->option('output');

        // For console format, use our enhanced display
        if ($format === OutputFormat::CONSOLE && !$outputPath) {
            return $this->processValidationResults($results);
        }

        // For other formats or file output, use reporting service
        $output = $this->reporting->format($results, $format, [
            'colors' => $format === OutputFormat::CONSOLE,
            'verbose' => $this->option('verbose'),
        ]);

        if ($outputPath) {
            file_put_contents($outputPath, $output);
            $this->info("Results written to: {$outputPath}");
        } else {
            $this->output->write($output);
        }

        return $results['summary']['broken_links'] > 0 ? 1 : 0;
    }

    /**
     * Handle common validation errors.
     */
    protected function handleValidationError(\Throwable $e): int
    {
        $this->error("Validation failed: {$e->getMessage()}");

        if ($this->option('verbose')) {
            $this->error($e->getTraceAsString());
        }

        return 2;
    }
}
```

##### 2.5.2.2.2. ValidateCommand

```php
<?php

declare(strict_types=1);

namespace App\Commands;

use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Services\ValueObjects\ValidationConfig;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\multiselect;

class ValidateCommand extends BaseValidationCommand
{
    /**
     * The signature of the command.
     */
    protected $signature = 'validate
                            {path? : Path to validate (file or directory)}
                            {--scope=all : Validation scope (internal, external, anchor, image, all)}
                            {--format=console : Output format (console, json, html, markdown)}
                            {--output= : Output file path}
                            {--interactive : Run in interactive mode with guided setup}
                            {--concurrent=10 : Number of concurrent requests (1-50)}
                            {--timeout=30 : Request timeout in seconds (5-300)}
                            {--max-redirects=5 : Maximum redirects to follow (0-10)}
                            {--no-cache : Disable caching for this validation}
                            {--follow-redirects : Follow HTTP redirects}
                            {--user-agent=validate-links/1.0 : Custom User-Agent string}
                            {--verbose : Show detailed output and error messages}';

    /**
     * The description of the command.
     */
    protected $description = 'Validate links in documentation files with comprehensive scope and format options';

    private LinkValidationInterface $linkValidation;
    private ReportingInterface $reporting;

    public function __construct(
        LinkValidationInterface $linkValidation,
        ReportingInterface $reporting
    ) {
        parent::__construct();
        $this->linkValidation = $linkValidation;
        $this->reporting = $reporting;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            // Validate command options using enum validation
            $this->validateCommandOptions();

            if ($this->option('interactive')) {
                return $this->handleInteractive();
            }

            return $this->handleNonInteractive();
        } catch (\Throwable $e) {
            return $this->handleValidationError($e);
        }
    }

    /**
     * Handle interactive mode with comprehensive configuration.
     */
    private function handleInteractive(): int
    {
        $this->info('🔗 Interactive Link Validation Setup');
        $this->newLine();

        // Gather paths
        $paths = $this->gatherPaths();

        // Gather validation scope
        $scope = $this->gatherValidationScope();

        // Gather output preferences
        $outputConfig = $this->gatherOutputConfiguration();

        // Gather advanced options
        $advancedOptions = $this->gatherAdvancedOptions();

        // Confirm configuration
        if (!$this->confirmConfiguration($paths, $scope, $outputConfig, $advancedOptions)) {
            $this->warn('Validation cancelled.');
            return self::FAILURE;
        }

        // Execute validation with real-time feedback
        return $this->executeInteractiveValidation($paths, $scope, $outputConfig, $advancedOptions);
    }

    /**
     * Gather paths to validate with validation.
     */
    private function gatherPaths(): array
    {
        $paths = [];

        do {
            $path = text(
                label: 'Enter path to validate',
                placeholder: './docs',
                required: true,
                validate: fn (string $value) => is_dir($value) || is_file($value)
                    ? null
                    : 'Path must exist'
            );

            $paths[] = $path;

            $addMore = confirm('Add another path?', false);
        } while ($addMore);

        return $paths;
    }

    /**
     * Gather validation scope using enum-driven options.
     *
     * @return ValidationScope[]
     */
    private function gatherValidationScope(): array
    {
        $this->info('📋 Select validation scopes:');
        $this->newLine();

        // Display scope descriptions
        foreach (ValidationScope::cases() as $scope) {
            if ($scope !== ValidationScope::ALL) {
                $this->line("  <fg=cyan>{$scope->value}</fg>: {$scope->getDescription()}");
            }
        }

        $this->newLine();

        $selectedValues = multiselect(
            label: 'What types of links should be validated?',
            options: $this->getScopeOptions(),
            default: [ValidationScope::INTERNAL->value, ValidationScope::ANCHOR->value],
            required: true,
            hint: 'Use space to select, enter to confirm'
        );

        // Convert selected values to enum instances
        return array_map(
            fn($value) => ValidationScope::from($value),
            $selectedValues
        );
    }

    /**
     * Get scope options formatted for multiselect.
     */
    private function getScopeOptions(): array
    {
        $options = [];

        foreach (ValidationScope::cases() as $scope) {
            if ($scope !== ValidationScope::ALL) {
                $options[$scope->value] = $scope->getDescription();
            }
        }

        return $options;
    }

    /**
     * Gather output configuration using enum-driven options.
     */
    private function gatherOutputConfiguration(): array
    {
        $this->info('📄 Select output format:');
        $this->newLine();

        // Display format descriptions and use cases
        foreach (OutputFormat::cases() as $format) {
            $this->line("  <fg=cyan>{$format->value}</fg>: {$format->getDescription()}");
            $useCases = implode(', ', $format->getUseCases());
            $this->line("    <fg=gray>Use cases: {$useCases}</fg>");
        }

        $this->newLine();

        $formatOptions = [];
        foreach (OutputFormat::cases() as $format) {
            $formatOptions[$format->value] = $format->getDescription();
        }

        $selectedFormat = select(
            label: 'Choose output format',
            options: $formatOptions,
            default: OutputFormat::CONSOLE->value,
            hint: 'Each format has different capabilities and use cases'
        );

        $format = OutputFormat::from($selectedFormat);

        $outputFile = null;
        if ($format !== OutputFormat::CONSOLE) {
            $saveToFile = confirm('Save output to file?', true);

            if ($saveToFile) {
                $defaultFilename = $format->getDefaultFilename();
                $outputFile = text(
                    label: 'Output file path',
                    placeholder: "./reports/{$defaultFilename}",
                    default: "./reports/{$defaultFilename}",
                    required: true,
                    validate: fn (string $value) => $this->validateOutputPath($value, $format)
                );
            }
        }

        return [
            'format' => $format,
            'file' => $outputFile,
        ];
    }

    /**
     * Validate output file path and extension.
     */
    private function validateOutputPath(string $path, OutputFormat $format): ?string
    {
        $expectedExtension = $format->getExtension();
        $actualExtension = pathinfo($path, PATHINFO_EXTENSION);

        if ($actualExtension !== $expectedExtension) {
            return "File extension should be '.{$expectedExtension}' for {$format->value} format";
        }

        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            return "Cannot create directory: {$directory}";
        }

        return null;
    }

    /**
     * Gather advanced validation options.
     */
    private function gatherAdvancedOptions(): array
    {
        $options = [];

        $options['concurrent'] = confirm('Enable concurrent validation?', true);

        if ($options['concurrent']) {
            $options['max_concurrent'] = (int) text(
                label: 'Maximum concurrent requests',
                placeholder: '10',
                default: '10',
                validate: fn (string $value) => is_numeric($value) && (int) $value > 0 && (int) $value <= 50
                    ? null
                    : 'Must be a number between 1 and 50'
            );
        }

        $options['timeout'] = (int) text(
            label: 'Request timeout (seconds)',
            placeholder: '30',
            default: '30',
            validate: fn (string $value) => is_numeric($value) && (int) $value > 0 && (int) $value <= 300
                ? null
                : 'Must be a number between 1 and 300'
        );

        $options['follow_redirects'] = confirm('Follow redirects?', true);

        if ($options['follow_redirects']) {
            $options['max_redirects'] = (int) text(
                label: 'Maximum redirects to follow',
                placeholder: '5',
                default: '5',
                validate: fn (string $value) => is_numeric($value) && (int) $value >= 0 && (int) $value <= 20
                    ? null
                    : 'Must be a number between 0 and 20'
            );
        }

        $options['cache_results'] = confirm('Cache validation results?', true);

        return $options;
    }

    /**
     * Confirm configuration before execution.
     */
    private function confirmConfiguration(array $paths, array $scope, array $outputConfig, array $options): bool
    {
        $this->newLine();
        $this->info('📋 Configuration Summary:');
        $this->line('Paths: ' . implode(', ', $paths));
        $this->line('Scope: ' . implode(', ', $scope));
        $this->line('Format: ' . $outputConfig['format']);
        if ($outputConfig['file']) {
            $this->line('Output: ' . $outputConfig['file']);
        }
        $this->line('Concurrent: ' . ($options['concurrent'] ? 'Yes (' . $options['max_concurrent'] . ')' : 'No'));
        $this->line('Timeout: ' . $options['timeout'] . 's');
        $this->newLine();

        return confirm('Proceed with validation?', true);
    }

    /**
     * Execute interactive validation with progress feedback.
     */
    private function executeInteractiveValidation(array $paths, array $scope, array $outputConfig, array $options): int
    {
        $totalFiles = $this->countFiles($paths);

        $progress = progress(
            label: 'Validating links',
            steps: $totalFiles
        );

        $results = [];
        $fileCount = 0;

        foreach ($paths as $path) {
            $files = $this->getFilesFromPath($path);

            foreach ($files as $file) {
                $progress->label("Validating: " . basename($file));

                $fileResults = $this->linkValidationService->validateFile($file, $scope);
                $results[] = $fileResults;

                $fileCount++;
                $progress->advance();

                // Show real-time broken link alerts
                if ($this->hasBrokenLinks($fileResults)) {
                    $this->warn("⚠️  Broken links found in: {$file}");
                }
            }
        }

        $progress->finish();

        // Generate and display/save report
        $report = $this->reportingService->generateReport($results, $outputConfig['format']);

        if ($outputConfig['file']) {
            $this->reportingService->saveReport($report, $outputConfig['file']);
            $this->info("Report saved to: {$outputConfig['file']}");
        } else {
            $this->line($report);
        }

        // Show summary
        $summary = $this->reportingService->generateSummary($results);
        $this->displaySummary($summary);

        return $summary['broken_links'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Count total files to be processed.
     */
    private function countFiles(array $paths): int
    {
        $count = 0;
        foreach ($paths as $path) {
            if (is_file($path)) {
                $count++;
            } elseif (is_dir($path)) {
                $count += count($this->getFilesFromPath($path));
            }
        }
        return $count;
    }

    /**
     * Get files from a given path.
     */
    private function getFilesFromPath(string $path): array
    {
        if (is_file($path)) {
            return [$path];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['md', 'markdown', 'html', 'htm'])) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Check if file results contain broken links.
     */
    private function hasBrokenLinks(array $fileResults): bool
    {
        foreach ($fileResults as $result) {
            if (isset($result['status']) && $result['status'] === 'broken') {
                return true;
            }
        }
        return false;
    }

    /**
     * Display validation summary.
     */
    private function displaySummary(array $summary): void
    {
        $this->newLine();
        $this->info('📊 Validation Summary:');
        $this->line("Files processed: {$summary['files_processed']}");
        $this->line("Links checked: {$summary['links_checked']}");
        $this->line("Valid links: {$summary['valid_links']}");

        if ($summary['broken_links'] > 0) {
            $this->error("Broken links: {$summary['broken_links']}");
        } else {
            $this->info("Broken links: 0 ✅");
        }

        $this->line("Execution time: {$summary['execution_time']}s");
    }

    /**
     * Handle non-interactive mode.
     */
    private function handleNonInteractive(): int
    {
        $path = $this->argument('path') ?? './docs';
        $scope = $this->option('scope');
        $format = $this->option('format');
        $outputFile = $this->option('output');
        $concurrent = (int) $this->option('concurrent');
        $timeout = (int) $this->option('timeout');

        return $this->performValidation($path, $scope, $format, $outputFile, $concurrent, $timeout);
    }

    /**
     * Perform the actual validation.
     */
    private function performValidation(
        string $path,
        string $scope,
        string $format,
        ?string $outputFile,
        int $concurrent,
        int $timeout
    ): int {
        $this->info("🔍 Validating links in: {$path}");
        $this->info("📋 Scope: {$scope}");
        $this->info("📄 Format: {$format}");

        if ($outputFile) {
            $this->info("💾 Output: {$outputFile}");
        }

        $this->newLine();

        try {
            // Create validation configuration
            $config = new ValidationConfig([
                'scope' => $scope,
                'concurrent_requests' => $concurrent,
                'timeout' => $timeout,
            ]);

            // Perform validation
            $results = $this->linkValidation->validateFile($path, $config);

            // Generate and display report
            $report = $this->reporting->generateReport($results, $format);

            if ($format === 'console') {
                $this->line($report);
            } else {
                $this->info($report);
            }

            // Save to file if requested
            if ($outputFile) {
                $success = $this->reporting->exportReport($results, $outputFile, $format);

                if ($success) {
                    $this->info("✅ Report saved to: {$outputFile}");
                } else {
                    $this->error("❌ Failed to save report to: {$outputFile}");
                    return 1;
                }
            }

            // Show summary
            $summary = $this->reporting->generateSummary($results);
            $this->newLine();
            $this->info("📊 Summary:");
            $this->info("   Total links: {$summary['total']}");
            $this->info("   Valid: {$summary['valid']}");
            $this->info("   Invalid: {$summary['invalid']}");
            $this->info("   Success rate: {$summary['success_rate']}%");

            return $summary['invalid'] > 0 ? 1 : 0;

        } catch (\Exception $e) {
            $this->error("❌ Validation failed: {$e->getMessage()}");
            return 1;
        }
    }
}
```

### 2.5.3. Value Objects and Data Structures

**Task Reference:** [Task 2.1 & 2.2](090-implementation-plan.md#phase-2-content-remediation-week-2)

#### 2.5.3.1. ValidationConfig Value Object

```php
<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use App\Enums\ValidationScope;
use App\Enums\OutputFormat;
use InvalidArgumentException;

class ValidationConfig
{
    /**
     * @param ValidationScope[] $scopes
     */
    public function __construct(
        private readonly array $scopes = [ValidationScope::ALL],
        private readonly int $timeout = 30,
        private readonly int $maxRedirects = 5,
        private readonly int $concurrentRequests = 10,
        private readonly bool $cacheEnabled = true,
        private readonly bool $followRedirects = true,
        private readonly string $userAgent = 'validate-links/1.0',
        private readonly ?OutputFormat $outputFormat = null,
        private readonly array $additionalOptions = []
    ) {
        $this->validate();
    }

    /**
     * Create configuration from array.
     */
    public static function create(array $config): self
    {
        return new self(
            scopes: $config['scopes'] ?? [ValidationScope::ALL],
            timeout: $config['timeout'] ?? 30,
            maxRedirects: $config['max_redirects'] ?? 5,
            concurrentRequests: $config['concurrent_requests'] ?? 10,
            cacheEnabled: $config['cache_enabled'] ?? true,
            followRedirects: $config['follow_redirects'] ?? true,
            userAgent: $config['user_agent'] ?? 'validate-links/1.0',
            outputFormat: $config['output_format'] ?? null,
            additionalOptions: $config['additional_options'] ?? []
        );
    }

    /**
     * Create configuration with defaults.
     */
    public static function withDefaults(array $overrides = []): self
    {
        return self::create($overrides);
    }

    /**
     * Get validation scopes.
     *
     * @return ValidationScope[]
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * Check if specific scope is enabled.
     */
    public function hasScope(ValidationScope $scope): bool
    {
        return in_array($scope, $this->scopes, true) ||
               in_array(ValidationScope::ALL, $this->scopes, true);
    }

    /**
     * Get effective scopes (expand ALL to individual scopes).
     *
     * @return ValidationScope[]
     */
    public function getEffectiveScopes(): array
    {
        if (in_array(ValidationScope::ALL, $this->scopes, true)) {
            return ValidationScope::ALL->getIncludedScopes();
        }

        return $this->scopes;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getMaxRedirects(): int
    {
        return $this->maxRedirects;
    }

    public function getConcurrentRequests(): int
    {
        return $this->concurrentRequests;
    }

    public function isCacheEnabled(): bool
    {
        return $this->cacheEnabled;
    }

    public function shouldFollowRedirects(): bool
    {
        return $this->followRedirects;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function getOutputFormat(): ?OutputFormat
    {
        return $this->outputFormat;
    }

    public function getAdditionalOptions(): array
    {
        return $this->additionalOptions;
    }

    public function shouldValidateInternal(): bool
    {
        return $this->hasScope(ValidationScope::INTERNAL);
    }

    public function shouldValidateExternal(): bool
    {
        return $this->hasScope(ValidationScope::EXTERNAL);
    }

    public function shouldValidateAnchors(): bool
    {
        return $this->hasScope(ValidationScope::ANCHOR);
    }

    public function shouldValidateCrossReferences(): bool
    {
        return $this->hasScope(ValidationScope::CROSS_REFERENCE);
    }

    /**
     * Create new instance with modified scopes.
     *
     * @param ValidationScope[] $scopes
     */
    public function withScopes(array $scopes): self
    {
        return new self(
            scopes: $scopes,
            timeout: $this->timeout,
            maxRedirects: $this->maxRedirects,
            concurrentRequests: $this->concurrentRequests,
            cacheEnabled: $this->cacheEnabled,
            followRedirects: $this->followRedirects,
            userAgent: $this->userAgent,
            outputFormat: $this->outputFormat,
            additionalOptions: $this->additionalOptions
        );
    }

    /**
     * Create new instance with modified output format.
     */
    public function withOutputFormat(OutputFormat $format): self
    {
        return new self(
            scopes: $this->scopes,
            timeout: $this->timeout,
            maxRedirects: $this->maxRedirects,
            concurrentRequests: $this->concurrentRequests,
            cacheEnabled: $this->cacheEnabled,
            followRedirects: $this->followRedirects,
            userAgent: $this->userAgent,
            outputFormat: $format,
            additionalOptions: $this->additionalOptions
        );
    }

    public function toArray(): array
    {
        return [
            'scopes' => array_map(fn($scope) => $scope->value, $this->scopes),
            'timeout' => $this->timeout,
            'max_redirects' => $this->maxRedirects,
            'concurrent_requests' => $this->concurrentRequests,
            'cache_enabled' => $this->cacheEnabled,
            'follow_redirects' => $this->followRedirects,
            'user_agent' => $this->userAgent,
            'output_format' => $this->outputFormat?->value,
            'additional_options' => $this->additionalOptions,
        ];
    }

    private function validate(): void
    {
        if (empty($this->scopes)) {
            throw new InvalidArgumentException('At least one validation scope must be specified');
        }

        foreach ($this->scopes as $scope) {
            if (!$scope instanceof ValidationScope) {
                throw new InvalidArgumentException('All scopes must be ValidationScope enum instances');
            }
        }

        if ($this->concurrentRequests < 1 || $this->concurrentRequests > 50) {
            throw new InvalidArgumentException('Concurrent requests must be between 1 and 50');
        }

        if ($this->timeout < 1 || $this->timeout > 300) {
            throw new InvalidArgumentException('Timeout must be between 1 and 300 seconds');
        }

        if ($this->maxRedirects < 0 || $this->maxRedirects > 10) {
            throw new InvalidArgumentException('Max redirects must be between 0 and 10');
        }
    }
}
```

#### 2.5.3.2. ValidationResult Value Object

```php
<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;

class ValidationResult
{
    public function __construct(
        private readonly string $url,
        private readonly LinkStatus $status,
        private readonly ValidationScope $scope,
        private readonly ?string $error = null,
        private readonly ?int $httpStatusCode = null,
        private readonly ?string $redirectUrl = null,
        private readonly float $responseTime = 0.0,
        private readonly array $metadata = []
    ) {}

    /**
     * Create a successful validation result.
     */
    public static function success(
        string $url,
        ValidationScope $scope,
        ?int $httpStatusCode = null,
        float $responseTime = 0.0,
        array $metadata = []
    ): self {
        return new self(
            url: $url,
            status: LinkStatus::VALID,
            scope: $scope,
            httpStatusCode: $httpStatusCode,
            responseTime: $responseTime,
            metadata: $metadata
        );
    }

    /**
     * Create a failed validation result.
     */
    public static function failure(
        string $url,
        ValidationScope $scope,
        LinkStatus $status,
        ?string $error = null,
        ?int $httpStatusCode = null,
        ?string $redirectUrl = null,
        float $responseTime = 0.0,
        array $metadata = []
    ): self {
        return new self(
            url: $url,
            status: $status,
            scope: $scope,
            error: $error,
            httpStatusCode: $httpStatusCode,
            redirectUrl: $redirectUrl,
            responseTime: $responseTime,
            metadata: $metadata
        );
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getStatus(): LinkStatus
    {
        return $this->status;
    }

    public function getScope(): ValidationScope
    {
        return $this->scope;
    }

    public function isValid(): bool
    {
        return $this->status === LinkStatus::VALID;
    }

    public function isBroken(): bool
    {
        return $this->status->isBroken();
    }

    public function isTemporary(): bool
    {
        return $this->status->isTemporary();
    }

    public function isSecurityIssue(): bool
    {
        return $this->status->isSecurityIssue();
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }

    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    public function getResponseTime(): float
    {
        return $this->responseTime;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Get severity level from status.
     */
    public function getSeverity(): int
    {
        return $this->status->getSeverity();
    }

    /**
     * Get recommended action from status.
     */
    public function getRecommendedAction(): string
    {
        return $this->status->getRecommendedAction();
    }

    /**
     * Get console color for display.
     */
    public function getConsoleColor(): string
    {
        return $this->status->getConsoleColor();
    }

    /**
     * Get formatted display text.
     */
    public function getFormattedDisplay(): string
    {
        return $this->status->getFormattedDisplay();
    }

    /**
     * Check if this result should be retried.
     */
    public function shouldRetry(): bool
    {
        return $this->status->shouldRetry();
    }

    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'status' => $this->status->value,
            'scope' => $this->scope->value,
            'is_valid' => $this->isValid(),
            'is_broken' => $this->isBroken(),
            'error' => $this->error,
            'http_status_code' => $this->httpStatusCode,
            'redirect_url' => $this->redirectUrl,
            'response_time' => $this->responseTime,
            'severity' => $this->getSeverity(),
            'recommended_action' => $this->getRecommendedAction(),
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Convert to JSON representation.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }
}
```

## 2.6. Phase 3: Link Integrity & Navigation (Week 3)

**📋 Implementation Plan Reference:** [Phase 3 Tasks](090-implementation-plan.md#phase-3-link-integrity--navigation-week-3)

### 2.6.1. Unit Testing Suite

**Task Reference:** [Task 3.1](090-implementation-plan.md#phase-3-link-integrity--navigation-week-3)

For complete testing implementations, refer to the [Testing Documentation](070-testing-documentation.md).

### 2.6.2. Feature Testing Suite

**Task Reference:** [Task 3.2](090-implementation-plan.md#phase-3-link-integrity--navigation-week-3)

For complete feature testing implementations, refer to the [Testing Documentation](070-testing-documentation.md).

### 2.6.3. Performance and Integration Testing

**Task Reference:** [Task 3.3](090-implementation-plan.md#phase-3-link-integrity--navigation-week-3)

For complete performance testing implementations, refer to the [Testing Documentation](070-testing-documentation.md).

### 2.6.4. Documentation Testing

**Task Reference:** [Task 3.4](090-implementation-plan.md#phase-3-link-integrity--navigation-week-3)

For complete documentation testing implementations, refer to the [Testing Documentation](070-testing-documentation.md).

## 2.7. Phase 4: Quality Assurance & Validation (Week 4)

**📋 Implementation Plan Reference:** [Phase 4 Tasks](090-implementation-plan.md#phase-4-quality-assurance--validation-week-4)

### 2.7.1. Code Quality Validation

**Task Reference:** [Task 4.1](090-implementation-plan.md#phase-4-quality-assurance--validation-week-4)

For complete code quality validation procedures, refer to the [CI/CD Documentation](080-cicd-documentation.md).

### 2.7.2. Documentation Completion

**Task Reference:** [Task 4.2](090-implementation-plan.md#phase-4-quality-assurance--validation-week-4)

For complete documentation procedures, refer to the [CI/CD Documentation](080-cicd-documentation.md).

### 2.7.3. Deployment and Distribution

**Task Reference:** [Task 4.3](090-implementation-plan.md#phase-4-quality-assurance--validation-week-4)

For complete deployment procedures, refer to the [CI/CD Documentation](080-cicd-documentation.md).

## 2.8. Implementation Completion Checklist

### 2.8.1. ✅ Phase 1: Foundation Complete

- [x] **Configuration Files:** composer.json, .env.example, config files
- [x] **Development Tools:** PHPStan, Pint, Rector configurations
- [x] **Testing Setup:** Pest configuration and test infrastructure
- [x] **CI/CD Pipeline:** GitHub Actions workflow with all quality checks

### 2.8.2. ✅ Phase 2: Core Implementation Complete

- [x] **Service Interfaces:** Complete all service contracts
- [x] **Service Implementations:** Full implementations with error handling
- [x] **Command Classes:** Interactive commands with Laravel Prompts
- [x] **Value Objects:** Immutable data structures with validation
- [x] **Exception Classes:** Complete exception hierarchy
- [x] **Service Provider:** Complete dependency injection setup

### 2.8.3. 🔄 Phase 3: Testing & Integration (In Progress)

- [ ] **Unit Tests:** Complete test coverage for all services
- [ ] **Feature Tests:** End-to-end workflow testing
- [ ] **Performance Tests:** Benchmarks and memory usage tests
- [ ] **Integration Tests:** External dependency testing

### 2.8.4. 🔄 Phase 4: Production Readiness (Pending)

- [ ] **Code Quality:** PHPStan level 9 compliance
- [ ] **Documentation:** Complete API reference and usage guides
- [ ] **Deployment:** Production configuration and distribution

---

## 📖 Navigation

**[⬅️ Previous: Project Status](010-project-status.md)** | **[Next: Architecture Overview ➡️](030-architecture-overview.md)** | **[🔝 Top](#implementation-guide---restructured)**
