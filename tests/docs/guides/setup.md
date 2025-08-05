# Environment Setup Guide

This guide walks you through setting up the advanced PEST test suite environment for development, testing, and CI/CD integration.

## Prerequisites

### System Requirements

- **PHP**: 8.1 or higher
- **Composer**: 2.0 or higher
- **Memory**: At least 512MB available for PHP
- **Extensions**: Required PHP extensions listed below

### Required PHP Extensions

```bash
# Check required extensions
php -m | grep -E "(mbstring|json|xml|curl|openssl|fileinfo|tokenizer)"

# Install missing extensions (Ubuntu/Debian)
sudo apt-get install php-mbstring php-json php-xml php-curl php-openssl

# Install missing extensions (macOS with Homebrew)
brew install php@8.1
```

### Optional Extensions for Enhanced Features

```bash
# For better performance and parallel testing
php -m | grep -E "(opcache|apcu|redis|memcached)"

# Install optional extensions
sudo apt-get install php-opcache php-apcu php-redis
```

## Installation

### 1. Clone and Install Dependencies

```bash
# Clone the repository
git clone <repository-url>
cd validate-links

# Install dependencies
composer install

# Install development dependencies
composer install --dev
```

### 2. Environment Configuration

```bash
# Copy environment file
cp .env.example .env.testing

# Edit testing environment
nano .env.testing
```

### 3. Configure Testing Environment

```bash
# .env.testing
APP_ENV=testing
APP_DEBUG=false

# Database configuration
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

# Cache configuration
CACHE_DRIVER=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync

# Logging
LOG_CHANNEL=null

# Test-specific settings
INCLUDE_PERFORMANCE_TESTS=false
INCLUDE_SLOW_TESTS=false
INCLUDE_EXTERNAL_TESTS=false
PEST_PERFORMANCE_REPORT=false
PEST_PARALLEL=false
```

## Basic Configuration

### PEST Configuration

The main configuration is in [`pest.config.php`](../../../pest.config.php):

```php
<?php

return [
    // Test suite definitions
    'suites' => [
        'unit' => [
            'paths' => ['tests/Unit'],
            'parallel' => true,
            'memory_limit' => '256M',
        ],
        'feature' => [
            'paths' => ['tests/Feature'],
            'parallel' => true,
            'memory_limit' => '512M',
        ],
        // ... other suites
    ],
    
    // Performance optimization
    'environment' => [
        'APP_ENV' => 'testing',
        'APP_DEBUG' => false,
        'DB_CONNECTION' => 'sqlite',
        'DB_DATABASE' => ':memory:',
        'CACHE_DRIVER' => 'array',
        'LOG_CHANNEL' => 'null',
    ],
    
    // Advanced features
    'lazy_initialization' => [
        'enabled' => true,
        'cache_instances' => true,
        'auto_cleanup' => true,
    ],
    
    'custom_matchers' => [
        'enabled' => true,
        'auto_load' => true,
    ],
    
    'tagging' => [
        'enabled' => true,
        'auto_discovery' => true,
    ],
];
```

### PHPUnit Configuration

The [`phpunit.xml`](../../../phpunit.xml) file configures the underlying PHPUnit:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         processIsolation="false"
         stopOnFailure="false"
         cacheDirectory="storage/framework/cache/phpunit">
    
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    
    <source>
        <include>
            <directory>app</directory>
        </include>
        <exclude>
            <directory>app/Exceptions</directory>
        </exclude>
    </source>
    
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
    </php>
</phpunit>
```

## Development Environment Setup

### 1. IDE Configuration

#### VS Code Setup

Create `.vscode/settings.json`:

```json
{
    "php.validate.executablePath": "/usr/bin/php",
    "php.suggest.basic": false,
    "phpunit.php": "/usr/bin/php",
    "phpunit.phpunit": "./vendor/bin/phpunit",
    "pest-php.phpPath": "/usr/bin/php",
    "pest-php.pestPath": "./vendor/bin/pest",
    "files.associations": {
        "*.php": "php"
    },
    "emmet.includeLanguages": {
        "php": "html"
    }
}
```

Create `.vscode/tasks.json`:

```json
{
    "version": "2.0.0",
    "tasks": [
        {
            "label": "Run PEST Tests",
            "type": "shell",
            "command": "./vendor/bin/pest",
            "group": "test",
            "presentation": {
                "echo": true,
                "reveal": "always",
                "focus": false,
                "panel": "shared"
            }
        },
        {
            "label": "Run PEST with Coverage",
            "type": "shell",
            "command": "./vendor/bin/pest --coverage",
            "group": "test"
        },
        {
            "label": "Run Performance Tests",
            "type": "shell",
            "command": "INCLUDE_PERFORMANCE_TESTS=true ./vendor/bin/pest tests/Performance",
            "group": "test"
        }
    ]
}
```

#### PhpStorm Setup

1. **Configure PHP Interpreter**:
   - File → Settings → Languages & Frameworks → PHP
   - Set PHP language level to 8.1+
   - Configure CLI Interpreter

2. **Configure Test Framework**:
   - File → Settings → Languages & Frameworks → PHP → Test Frameworks
   - Add PHPUnit by Remote Interpreter
   - Set path to `vendor/bin/phpunit`

3. **Configure PEST Plugin**:
   - Install PEST plugin from marketplace
   - Configure PEST executable: `vendor/bin/pest`

### 2. Git Hooks Setup

Create `.git/hooks/pre-commit`:

```bash
#!/bin/bash

echo "Running pre-commit checks..."

# Run fast tests
echo "Running fast tests..."
./vendor/bin/pest --group=fast --bail

if [ $? -ne 0 ]; then
    echo "Fast tests failed. Commit aborted."
    exit 1
fi

# Run code style checks
echo "Running code style checks..."
./vendor/bin/php-cs-fixer fix --dry-run --diff

if [ $? -ne 0 ]; then
    echo "Code style issues found. Run 'composer fix-style' to fix them."
    exit 1
fi

echo "Pre-commit checks passed!"
```

Make it executable:

```bash
chmod +x .git/hooks/pre-commit
```

### 3. Composer Scripts

Add useful scripts to `composer.json`:

```json
{
    "scripts": {
        "test": "pest",
        "test:unit": "pest tests/Unit",
        "test:feature": "pest tests/Feature",
        "test:integration": "pest tests/Integration",
        "test:performance": "INCLUDE_PERFORMANCE_TESTS=true pest tests/Performance",
        "test:parallel": "pest --parallel",
        "test:coverage": "pest --coverage",
        "test:fast": "pest --group=fast",
        "test:slow": "INCLUDE_SLOW_TESTS=true pest --group=slow",
        "test:external": "INCLUDE_EXTERNAL_TESTS=true pest --group=external",
        "test:all": "INCLUDE_PERFORMANCE_TESTS=true INCLUDE_SLOW_TESTS=true INCLUDE_EXTERNAL_TESTS=true pest",
        "fix-style": "php-cs-fixer fix",
        "check-style": "php-cs-fixer fix --dry-run --diff",
        "analyze": "phpstan analyse",
        "insights": "phpinsights"
    }
}
```

## Advanced Configuration

### 1. Parallel Testing Setup

```bash
# Enable parallel testing
export PEST_PARALLEL=true

# Set number of processes (optional, auto-detected by default)
export PEST_PROCESSES=8

# Configure worker memory limit
export PEST_WORKER_MEMORY_LIMIT=512M
```

### 2. Performance Testing Setup

```bash
# Enable performance tests
export INCLUDE_PERFORMANCE_TESTS=true

# Enable performance reporting
export PEST_PERFORMANCE_REPORT=true

# Set performance thresholds
export PEST_FAST_THRESHOLD=0.1
export PEST_NORMAL_THRESHOLD=1.0
export PEST_SLOW_THRESHOLD=5.0
```

### 3. External Dependencies Setup

```bash
# Enable external tests
export INCLUDE_EXTERNAL_TESTS=true

# GitHub integration
export GITHUB_TOKEN=your_github_token

# API endpoints
export TEST_API_ENDPOINT=https://api.example.com
export TEST_API_KEY=your_api_key
```

### 4. Database Setup

#### SQLite (Default)

```bash
# Uses in-memory database by default
export DB_CONNECTION=sqlite
export DB_DATABASE=:memory:
```

#### MySQL/PostgreSQL (Optional)

```bash
# MySQL
export DB_CONNECTION=mysql
export DB_HOST=localhost
export DB_PORT=3306
export DB_DATABASE=validate_links_test
export DB_USERNAME=test_user
export DB_PASSWORD=test_password

# PostgreSQL
export DB_CONNECTION=pgsql
export DB_HOST=localhost
export DB_PORT=5432
export DB_DATABASE=validate_links_test
export DB_USERNAME=test_user
export DB_PASSWORD=test_password
```

## Verification

### 1. Basic Test Run

```bash
# Run basic tests
./vendor/bin/pest

# Expected output:
#   PASS  Tests\Unit\...
#   PASS  Tests\Feature\...
#   
#   Tests:  XX passed
#   Time:   X.XXs
```

### 2. Feature Verification

```bash
# Test lazy initialization
./vendor/bin/pest --group=lazy

# Test custom matchers
./vendor/bin/pest --group=matchers

# Test parallel execution
./vendor/bin/pest --parallel --group=parallel

# Test performance monitoring
PEST_PERFORMANCE_REPORT=true ./vendor/bin/pest --group=performance
```

### 3. Coverage Report

```bash
# Generate coverage report
./vendor/bin/pest --coverage

# Generate HTML coverage report
./vendor/bin/pest --coverage-html=reports/coverage
```

## Troubleshooting

### Common Issues

#### 1. Memory Issues

```bash
# Increase PHP memory limit
php -d memory_limit=1G ./vendor/bin/pest

# Or set in php.ini
memory_limit = 1G
```

#### 2. Permission Issues

```bash
# Fix storage permissions
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/

# Fix test file permissions
chmod -R 755 tests/
```

#### 3. Extension Issues

```bash
# Check loaded extensions
php -m

# Install missing extensions
sudo apt-get install php-{extension-name}

# Restart web server if needed
sudo service apache2 restart
# or
sudo service nginx restart
```

#### 4. Parallel Testing Issues

```bash
# Disable parallel testing if issues occur
export PEST_PARALLEL=false

# Reduce number of processes
export PEST_PROCESSES=2

# Check for unsafe tests
./vendor/bin/pest --group=sequential
```

### Debug Mode

```bash
# Enable debug mode
export PEST_DEBUG=true

# Verbose output
./vendor/bin/pest -v

# Very verbose output
./vendor/bin/pest -vv
```

### Performance Debugging

```bash
# Enable performance reporting
export PEST_PERFORMANCE_REPORT=true

# Profile memory usage
php -d memory_limit=1G -d xdebug.mode=profile ./vendor/bin/pest

# Monitor execution time
time ./vendor/bin/pest
```

## CI/CD Integration

### GitHub Actions

Create `.github/workflows/tests.yml`:

```yaml
name: Tests

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
        php-version: [8.1, 8.2, 8.3]
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ matrix.php-version }}
        extensions: mbstring, json, xml, curl, openssl
        coverage: xdebug
    
    - name: Install dependencies
      run: composer install --prefer-dist --no-progress
    
    - name: Run fast tests
      run: ./vendor/bin/pest --group=fast
    
    - name: Run unit tests
      run: ./vendor/bin/pest tests/Unit
    
    - name: Run feature tests
      run: ./vendor/bin/pest tests/Feature
    
    - name: Run integration tests
      run: ./vendor/bin/pest tests/Integration
    
    - name: Run parallel tests
      run: ./vendor/bin/pest --parallel --group=parallel
    
    - name: Generate coverage
      run: ./vendor/bin/pest --coverage-clover=coverage.xml
    
    - name: Upload coverage
      uses: codecov/codecov-action@v3
      with:
        file: ./coverage.xml
```

### GitLab CI

Create `.gitlab-ci.yml`:

```yaml
stages:
  - test
  - coverage

variables:
  COMPOSER_CACHE_DIR: "$CI_PROJECT_DIR/.composer-cache"

cache:
  paths:
    - .composer-cache/
    - vendor/

test:
  stage: test
  image: php:8.1
  
  before_script:
    - apt-get update -qq && apt-get install -y -qq git unzip
    - curl -sS https://getcomposer.org/installer | php
    - php composer.phar install --prefer-dist --no-progress
  
  script:
    - ./vendor/bin/pest --group=fast
    - ./vendor/bin/pest tests/Unit
    - ./vendor/bin/pest tests/Feature
    - ./vendor/bin/pest --parallel --group=parallel
  
  artifacts:
    reports:
      junit: reports/junit.xml

coverage:
  stage: coverage
  image: php:8.1
  
  script:
    - ./vendor/bin/pest --coverage-clover=coverage.xml
  
  artifacts:
    reports:
      coverage_report:
        coverage_format: cobertura
        path: coverage.xml
```

## Next Steps

After completing the setup:

1. **Read the Documentation**:
   - [Basic Usage Examples](../examples/basic-usage.md)
   - [Feature Guides](../features/)
   - [Advanced Patterns](../examples/advanced-patterns.md)

2. **Write Your First Test**:
   - Start with unit tests in `tests/Unit/`
   - Use the enhanced `PestTestCase`
   - Apply appropriate tags

3. **Explore Advanced Features**:
   - Try lazy initialization
   - Use custom matchers
   - Experiment with parallel testing

4. **Contribute**:
   - Follow the [Contributing Guide](contributing.md)
   - Add new tests and features
   - Improve documentation

## Related Documentation

- [Running Tests Guide](running-tests.md)
- [Debugging Guide](debugging.md)
- [Contributing Guide](contributing.md)
- [Configuration Reference](../reference/configuration.md)