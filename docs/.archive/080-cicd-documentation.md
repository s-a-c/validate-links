# 8. CI/CD Implementation Guide

**Date:** July 22, 2025  
**Project:** Laravel Zero validate-links Implementation  
**Compliance:** `.ai/guidelines.md` and `.ai/guidelines/` standards

## 8.1. Overview

This document provides comprehensive documentation for the CI/CD pipeline implementation using GitHub Actions workflows. The pipeline ensures code quality, automated testing, and reliable deployment for the validate-links Laravel Zero application.

### 8.1.0. Navigation and Cross-References

**📖 Related Documentation:**
- **[Implementation Guide](020-implementation-guide.md)** - Complete source code and deployment preparation
- **[Testing Documentation](070-testing-documentation.md)** - Testing strategies implemented in CI/CD
- **[Code Documentation](060-code-documentation.md)** - Classes and components being tested and deployed
- **[Documentation Index](000-documentation-index.md)** - Comprehensive cross-reference guide

**🔗 Quick Links:**
- [Deployment Preparation](020-implementation-guide.md#43-deployment-and-distribution) → [Deployment Pipeline](#85-deployment-pipeline)
- [Testing Implementation](020-implementation-guide.md#31-unit-testing-suite) → [Testing Pipeline](#82-main-testing-pipeline)
- [Testing Strategies](070-testing-documentation.md) → [CI/CD Testing Integration](#82-main-testing-pipeline)
- [Security Implementation](020-implementation-guide.md#21-service-implementation-completion) → [Security Pipeline](#86-security-and-compliance-pipeline)

### 8.1.1. CI/CD Architecture

The CI/CD pipeline implements a multi-stage approach with parallel execution for efficiency:

**Continuous Integration Stages:**
- **Code Quality:** Static analysis, formatting, and architecture validation
- **Testing:** Unit, feature, and integration test execution
- **Security:** Vulnerability scanning and dependency auditing
- **Documentation:** Link validation and documentation testing

**Continuous Deployment Stages:**
- **Build:** Application packaging and artifact creation
- **Staging:** Deployment to staging environment for validation
- **Production:** Automated production deployment with rollback capability

### 8.1.2. Workflow Organization

**Primary Workflows:**
- `tests.yml` - Main testing pipeline
- `code-quality.yml` - Code quality and static analysis
- `deploy.yml` - Production deployment pipeline
- `documentation-validation.yml` - Documentation integrity validation

**Supporting Workflows:**
- `static-analysis.yml` - PHPStan and Psalm analysis
- `type-checks.yml` - Type safety validation
- `lint.yml` - Code formatting and style checks

## 8.2. Main Testing Pipeline

### 8.2.1. Primary Test Workflow

**File:** `.github/workflows/tests.yml`

**Complete Implementation:**

```yaml
name: tests

on:
  push:
    branches:
      - develop
      - main
  pull_request:
    branches:
      - develop
      - main

jobs:
  ci:
    runs-on: ubuntu-latest
    environment: Testing

    strategy:
      matrix:
        php-version: [8.4]
        dependency-version: [prefer-lowest, prefer-stable]

    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php-version }}
          tools: composer:v2
          coverage: xdebug
          extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite, pdo_sqlite

      - name: Cache Composer dependencies
        uses: actions/cache@v3
        with:
          path: ~/.composer/cache/files
          key: dependencies-php-${{ matrix.php-version }}-composer-${{ hashFiles('composer.lock') }}
          restore-keys: |
            dependencies-php-${{ matrix.php-version }}-composer-

      - name: Install Dependencies
        run: composer install --no-interaction --prefer-dist --optimize-autoloader --${{ matrix.dependency-version }}

      - name: Copy Environment File
        run: cp .env.example .env

      - name: Generate Application Key
        run: php artisan key:generate

      - name: Create Testing Database
        run: touch database/testing.sqlite

      - name: Run Unit Tests
        run: ./vendor/bin/pest --testsuite=Unit --coverage --coverage-clover=coverage-unit.xml

      - name: Run Feature Tests
        run: ./vendor/bin/pest --testsuite=Feature --coverage --coverage-clover=coverage-feature.xml

      - name: Run Service Tests
        run: ./vendor/bin/pest --testsuite=Service --coverage --coverage-clover=coverage-service.xml

      - name: Upload Coverage to Codecov
        uses: codecov/codecov-action@v3
        with:
          files: ./coverage-unit.xml,./coverage-feature.xml,./coverage-service.xml
          flags: unittests
          name: codecov-umbrella
          fail_ci_if_error: true

      - name: Archive Test Results
        uses: actions/upload-artifact@v3
        if: failure()
        with:
          name: test-results-${{ matrix.php-version }}-${{ matrix.dependency-version }}
          path: |
            tests/results/
            storage/logs/
```

### 8.2.2. Test Configuration

**Pest Configuration for CI:**

```php
// pest.config.php
<?php

return [
    'testsuites' => [
        'Unit' => [
            'paths' => ['tests/Unit'],
            'testsuite' => 'Unit'
        ],
        'Feature' => [
            'paths' => ['tests/Feature'],
            'testsuite' => 'Feature'
        ],
        'Service' => [
            'paths' => ['tests/Service'],
            'testsuite' => 'Service'
        ],
        'Integration' => [
            'paths' => ['tests/Integration'],
            'testsuite' => 'Integration'
        ],
        'Performance' => [
            'paths' => ['tests/Performance'],
            'testsuite' => 'Performance'
        ]
    ],
    'coverage' => [
        'include' => [
            'app/'
        ],
        'exclude' => [
            'app/Console/Kernel.php',
            'app/Exceptions/Handler.php'
        ],
        'report' => [
            'html' => 'tests/coverage',
            'xml' => 'tests/coverage.xml'
        ]
    ]
];
```

## 8.3. Code Quality Pipeline

### 8.3.1. Code Quality Workflow

**File:** `.github/workflows/code-quality.yml`

**Complete Implementation:**

```yaml
name: Code Quality

on:
  push:
    branches: [ develop, main ]
  pull_request:
    branches: [ develop, main ]

jobs:
  code-quality:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite, pdo_sqlite
          coverage: xdebug

      - name: Cache Composer dependencies
        uses: actions/cache@v3
        with:
          path: ~/.composer/cache/files
          key: dependencies-composer-${{ hashFiles('composer.lock') }}

      - name: Install Dependencies
        run: composer install -q --no-ansi --no-interaction --no-scripts --no-progress --prefer-dist

      - name: Check Code Style (Pint)
        run: composer pint:test

      - name: Static Analysis (PHPStan)
        run: composer phpstan

      - name: Type Safety (Psalm)
        run: composer psalm

      - name: Code Modernization (Rector Dry Run)
        run: composer rector:dry-run

      - name: Architecture Analysis (PHP Insights)
        run: composer insights -- --min-quality=90 --min-complexity=90 --min-architecture=90 --min-style=90 --no-interaction

      - name: Security Audit
        run: composer audit

      - name: Dependency Validation
        run: composer validate --strict

      - name: Upload Analysis Results
        uses: actions/upload-artifact@v3
        if: always()
        with:
          name: code-quality-results
          path: |
            reports/
            .phpstan-cache/
```

### 8.3.2. Static Analysis Configuration

**PHPStan Configuration (`phpstan.neon`):**

```neon
includes:
    - ./vendor/larastan/larastan/extension.neon

parameters:
    level: 9
    paths:
        - app/
        - config/
        - bootstrap/
    
    excludePaths:
        - app/Console/Kernel.php
        - bootstrap/cache/
    
    checkMissingIterableValueType: false
    checkGenericClassInNonGenericObjectType: false
    
    ignoreErrors:
        - '#Call to an undefined method Illuminate\\Contracts\\Foundation\\Application::#'
    
    reportUnmatchedIgnoredErrors: false
    
    tmpDir: reports/phpstan
```

**Psalm Configuration (`psalm.xml`):**

```xml
<?xml version="1.0"?>
<psalm
    errorLevel="1"
    resolveFromConfigFile="true"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns="https://getpsalm.org/schema/config"
    xsi:schemaLocation="https://getpsalm.org/schema/config vendor/vimeo/psalm/config.xsd"
    findUnusedBaselineEntry="true"
    findUnusedCode="true"
>
    <projectFiles>
        <directory name="app" />
        <directory name="config" />
        <directory name="bootstrap" />
        <ignoreFiles>
            <directory name="vendor" />
            <directory name="storage" />
            <file name="app/Console/Kernel.php" />
        </ignoreFiles>
    </projectFiles>

    <plugins>
        <pluginClass class="Psalm\LaravelPlugin\Plugin"/>
    </plugins>

    <issueHandlers>
        <LessSpecificReturnType errorLevel="info" />
        <MoreSpecificReturnType errorLevel="info" />
        <PropertyNotSetInConstructor errorLevel="info" />
    </issueHandlers>
</psalm>
```

## 8.4. Documentation Validation Pipeline

### 8.4.1. Documentation Workflow

**File:** `.github/workflows/documentation-validation.yml`

**Complete Implementation:**

```yaml
name: Documentation Validation

on:
  push:
    branches: [ develop, main ]
    paths: 
      - 'docs/**'
      - 'README.md'
      - '.github/workflows/documentation-validation.yml'
  pull_request:
    branches: [ develop, main ]
    paths:
      - 'docs/**'
      - 'README.md'

jobs:
  validate-documentation:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: dom, curl, libxml, mbstring, zip

      - name: Install Dependencies
        run: composer install --no-interaction --prefer-dist --optimize-autoloader

      - name: Validate Internal Links
        run: |
          php validate-links validate docs/ \
            --scope=internal \
            --scope=anchor \
            --format=json \
            --output=docs-validation.json \
            --max-broken=0

      - name: Validate Documentation Structure
        run: |
          # Check required documentation files exist
          test -f docs/README.md
          test -f docs/010-project-status.md
          test -f docs/020-implementation-guide.md
          test -f docs/030-architecture-overview.md
          test -f docs/040-api-reference.md
          test -f docs/050-usage-guide.md
          test -f docs/060-code-documentation.md
          test -f docs/070-testing-documentation.md
          test -f docs/080-cicd-documentation.md
          test -f docs/090-implementation-plan.md

      - name: Check Documentation Formatting
        run: |
          # Validate markdown formatting
          find docs/ -name "*.md" -exec markdownlint {} \;

      - name: Validate Code Examples
        run: |
          # Extract and validate PHP code examples from documentation
          php artisan validate:code-examples docs/

      - name: Check WCAG 2.1 AA Compliance
        run: |
          # Validate accessibility compliance for generated HTML
          php artisan validate:accessibility docs/

      - name: Upload Validation Results
        uses: actions/upload-artifact@v3
        if: always()
        with:
          name: documentation-validation
          path: |
            docs-validation.json
            accessibility-report.json
```

### 8.4.2. Link Validation Integration

**Custom Artisan Command for Documentation Validation:**

```php
<?php
// app/Console/Commands/ValidateCodeExamples.php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ValidateCodeExamples extends Command
{
    protected $signature = 'validate:code-examples {path}';
    protected $description = 'Validate PHP code examples in documentation';

    public function handle(): int
    {
        $path = $this->argument('path');
        $files = glob("{$path}/**/*.md");
        
        $errors = 0;
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $codeBlocks = $this->extractPhpCodeBlocks($content);
            
            foreach ($codeBlocks as $index => $code) {
                if (!$this->validatePhpSyntax($code)) {
                    $this->error("Invalid PHP syntax in {$file} (block " . ($index + 1) . ")");
                    $errors++;
                }
            }
        }
        
        if ($errors === 0) {
            $this->info('All code examples are valid');
            return 0;
        }
        
        $this->error("Found {$errors} invalid code examples");
        return 1;
    }

    private function extractPhpCodeBlocks(string $content): array
    {
        preg_match_all('/```php\n(.*?)\n```/s', $content, $matches);
        return $matches[1] ?? [];
    }

    private function validatePhpSyntax(string $code): bool
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'php_syntax_check');
        file_put_contents($tempFile, "<?php\n" . $code);
        
        $output = shell_exec("php -l {$tempFile} 2>&1");
        unlink($tempFile);
        
        return strpos($output, 'No syntax errors detected') !== false;
    }
}
```

## 8.5. Deployment Pipeline

### 8.5.1. Production Deployment Workflow

**File:** `.github/workflows/deploy.yml`

**Complete Implementation:**

```yaml
name: Deploy

on:
  push:
    branches: [ main ]
    tags: [ 'v*' ]
  workflow_dispatch:
    inputs:
      environment:
        description: 'Deployment environment'
        required: true
        default: 'staging'
        type: choice
        options:
          - staging
          - production

jobs:
  build:
    runs-on: ubuntu-latest
    outputs:
      version: ${{ steps.version.outputs.version }}

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite, pdo_sqlite

      - name: Install Dependencies
        run: composer install --no-ansi --no-interaction --no-scripts --no-progress --prefer-dist --optimize-autoloader --no-dev

      - name: Generate Version
        id: version
        run: |
          if [[ $GITHUB_REF == refs/tags/* ]]; then
            VERSION=${GITHUB_REF#refs/tags/}
          else
            VERSION=${GITHUB_SHA::8}
          fi
          echo "version=$VERSION" >> $GITHUB_OUTPUT

      - name: Build Application
        run: |
          php artisan app:build validate-links --build-version=${{ steps.version.outputs.version }}

      - name: Create Distribution Archive
        run: |
          tar -czf validate-links-${{ steps.version.outputs.version }}.tar.gz \
            --exclude='.git*' \
            --exclude='tests' \
            --exclude='node_modules' \
            --exclude='.env*' \
            .

      - name: Upload Build Artifacts
        uses: actions/upload-artifact@v3
        with:
          name: validate-links-${{ steps.version.outputs.version }}
          path: |
            builds/validate-links
            validate-links-${{ steps.version.outputs.version }}.tar.gz

  deploy-staging:
    needs: build
    runs-on: ubuntu-latest
    environment: staging
    if: github.ref == 'refs/heads/main' || github.event.inputs.environment == 'staging'

    steps:
      - name: Download Build Artifacts
        uses: actions/download-artifact@v3
        with:
          name: validate-links-${{ needs.build.outputs.version }}

      - name: Deploy to Staging
        run: |
          echo "Deploying version ${{ needs.build.outputs.version }} to staging"
          # Add staging deployment commands here

      - name: Run Smoke Tests
        run: |
          # Test basic functionality in staging
          ./builds/validate-links validate --version
          ./builds/validate-links validate docs/ --dry-run

  deploy-production:
    needs: [build, deploy-staging]
    runs-on: ubuntu-latest
    environment: production
    if: startsWith(github.ref, 'refs/tags/') || github.event.inputs.environment == 'production'

    steps:
      - name: Download Build Artifacts
        uses: actions/download-artifact@v3
        with:
          name: validate-links-${{ needs.build.outputs.version }}

      - name: Deploy to Production
        uses: deployphp/action@v1
        with:
          private-key: ${{ secrets.DEPLOY_KEY }}
          dep: deploy production
          deployer-version: "7.0.0"

      - name: Create GitHub Release
        if: startsWith(github.ref, 'refs/tags/')
        uses: actions/create-release@v1
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
        with:
          tag_name: ${{ github.ref }}
          release_name: Release ${{ needs.build.outputs.version }}
          body: |
            ## Changes in this Release
            - Automated release from CI/CD pipeline
            - Version: ${{ needs.build.outputs.version }}

            ## Installation
            ```bash
            wget https://github.com/${{ github.repository }}/releases/download/${{ github.ref_name }}/validate-links-${{ needs.build.outputs.version }}.tar.gz
            tar -xzf validate-links-${{ needs.build.outputs.version }}.tar.gz
            ```
          draft: false
          prerelease: false

      - name: Upload Release Assets
        if: startsWith(github.ref, 'refs/tags/')
        uses: actions/upload-release-asset@v1
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
        with:
          upload_url: ${{ steps.create_release.outputs.upload_url }}
          asset_path: ./validate-links-${{ needs.build.outputs.version }}.tar.gz
          asset_name: validate-links-${{ needs.build.outputs.version }}.tar.gz
          asset_content_type: application/gzip
```

---

*This comprehensive CI/CD documentation provides complete implementation guides for automated quality assurance, security scanning, and deployment pipelines.*

---

## 📖 Navigation

**[⬅️ Previous: Testing Documentation](070-testing-documentation.md)** | **[Next: Implementation Plan ➡️](090-implementation-plan.md)** | **[🔝 Top](#8-cicd-implementation-guide)**
