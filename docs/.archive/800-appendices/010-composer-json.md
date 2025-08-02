# Composer JSON - Enhanced

I'll analyze your current composer.json and enhance it with a comprehensive set of test and QA scripts using all the available tools in your environment. Let me first examine what tools you have available and then create an improved version.I'll analyze your current composer.json and create a comprehensive set of test and QA scripts utilizing all the available tools in your development environment. Let me examine your project structure first to understand the codebase better.Based on the existing composer.json and the tools available in this environment, I'll provide you with a comprehensive set of test and QA scripts that utilize all the available testing and code quality tools. Here's an improved composer.json with a comprehensive scripts section:

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
    "bin": ["validate-links"],
    "scripts": {
        "test": "pest",
        "test:parallel": "pest --parallel",
        "test:coverage": "pest --coverage --coverage-html=coverage-html --coverage-clover=coverage.xml",
        "test:coverage:text": "pest --coverage-text --min=80",
        "test:unit": "pest tests/Unit/",
        "test:feature": "pest tests/Feature/",
        "test:arch": "pest --filter=arch",
        "test:type-coverage": "pest --type-coverage --min=80",
        "test:stress": "pest --stress",
        "test:mutation": "pest --mutate",
        "test:profile": "pest --profile",
        "test:bail": "pest --bail",
        "test:phpunit": "phpunit",
        "test:peck": "peck",
        
        "analyse": "phpstan analyse",
        "analyse:app": "phpstan analyse app/ --level=9",
        "analyse:tests": "phpstan analyse tests/ --level=8",
        "analyse:baseline": "phpstan analyse --generate-baseline",
        "analyse:clear-cache": "phpstan clear-result-cache",
        "analyse:memory-limit": "phpstan analyse --memory-limit=2G",
        
        "psalm": "psalm",
        "psalm:stats": "psalm --stats",
        "psalm:info": "psalm --show-info=true",
        "psalm:baseline": "psalm --set-baseline=psalm-baseline.xml",
        "psalm:clear-cache": "psalm --clear-cache",
        "psalm:html": "psalm --report=reports/psalm.html --report-show-info=false",
        "psalm:json": "psalm --report=reports/psalm.json --report-show-info=false",
        "psalm:security": "psalm --taint-analysis",
        
        "cs-fix": "pint",
        "cs-fix:dirty": "pint --dirty",
        "cs-fix:diff": "pint --diff",
        "cs-fix:verbose": "pint -v",
        "cs-check": "pint --test",
        "cs-check:verbose": "pint --test -v",
        "cs-fixer": "php-cs-fixer fix",
        "cs-fixer:dry": "php-cs-fixer fix --dry-run --diff",
        "cs-fixer:check": "php-cs-fixer fix --dry-run --diff --stop-on-violation",
        
        "rector": "rector --dry-run",
        "rector:fix": "rector",
        "rector:diff": "rector --dry-run --diff",
        "rector:clear-cache": "rector --clear-cache",
        
        "insights": "phpinsights",
        "insights:fix": "phpinsights --fix",
        "insights:verbose": "phpinsights -v",
        "insights:summary": "phpinsights --summary",
        "insights:json": "phpinsights --format=json > reports/phpinsights.json",
        
        "security": "composer audit --format=plain",
        "security:json": "composer audit --format=json",
        
        "normalize": "composer normalize --dry-run --diff",
        "normalize:fix": "composer normalize",
        
        "outdated": "composer outdated --direct --strict",
        "outdated:all": "composer outdated",
        
        "validate": "composer validate --strict",
        "validate:deep": "composer validate --strict --check-lock",
        
        "build": "box compile",
        "build:debug": "box compile --debug",
        
        "qa": [
            "@validate:deep",
            "@security",
            "@normalize",
            "@cs-check",
            "@analyse",
            "@psalm",
            "@rector",
            "@insights:summary",
            "@test:coverage:text"
        ],
        "qa:fix": [
            "@normalize:fix",
            "@cs-fix",
            "@rector:fix",
            "@insights:fix"
        ],
        "qa:full": [
            "@validate:deep",
            "@security",
            "@normalize",
            "@cs-check:verbose",
            "@analyse:memory-limit",
            "@psalm:stats",
            "@rector:diff",
            "@insights:verbose",
            "@test:coverage",
            "@test:arch",
            "@test:type-coverage"
        ],
        "qa:ci": [
            "@validate:deep",
            "@security:json",
            "@normalize",
            "@cs-check",
            "@analyse:clear-cache",
            "@analyse",
            "@psalm:clear-cache",
            "@psalm:json",
            "@rector",
            "@insights:json",
            "@test:parallel",
            "@test:coverage"
        ],
        "qa:reports": [
            "@analyse:clear-cache",
            "@analyse",
            "@psalm:clear-cache",
            "@psalm:html",
            "@psalm:json",
            "@insights:json",
            "@test:coverage"
        ],
        
        "migrate:setup": [
            "php artisan config:publish validate-links",
            "php artisan vendor:publish --tag=validate-links-config"
        ],
        
        "clean": [
            "rm -rf coverage-html/",
            "rm -rf reports/",
            "rm -f coverage.xml",
            "rm -f .phpunit.result.cache",
            "@analyse:clear-cache",
            "@psalm:clear-cache",
            "@rector:clear-cache"
        ],
        "setup": [
            "mkdir -p reports",
            "@migrate:setup"
        ],
        
        "pre-commit": [
            "@validate",
            "@cs-fix:dirty",
            "@test:bail"
        ],
        "pre-push": [
            "@qa"
        ],
        
        "install:dev": "composer install --dev --optimize-autoloader",
        "install:prod": "composer install --no-dev --optimize-autoloader --classmap-authoritative"
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

## 1. Key Improvements Made:

### 1.1. **Testing Scripts**

- **Comprehensive Pest integration**: Parallel testing, coverage, architecture tests, type coverage, stress testing, mutation testing
- **Multiple test types**: Unit, feature, architecture, and performance testing
- **PHPUnit fallback**: For compatibility with existing test suites
- **Peck integration**: For additional testing capabilities

### 1.2. **Static Analysis**

- **PHPStan**: Multiple levels, baseline generation, memory management, separate app/tests analysis
- **Psalm**: Security analysis, HTML/JSON reporting, baseline management, statistics
- **Comprehensive reporting**: Both tools configured for CI/CD integration

### 1.3. **Code Quality**

- **Laravel Pint**: Primary code style fixer with various modes
- **PHP-CS-Fixer**: Alternative/supplementary code formatting
- **Rector**: Code modernization and refactoring with safety checks
- **PHP Insights**: Code quality metrics and analysis

### 1.4. **Security & Maintenance**

- **Security auditing**: Composer security checks in multiple formats
- **Dependency management**: Outdated package detection, validation
- **Composer normalization**: JSON structure standardization

### 1.5. **Workflow Scripts**

- **`qa`**: Quick quality assurance check
- **`qa:fix`**: Auto-fix common issues
- **`qa:full`**: Comprehensive quality analysis
- **`qa:ci`**: CI/CD optimized workflow
- **`qa:reports`**: Generate detailed reports

### 1.6. **Development Workflow**

- **Git hooks support**: Pre-commit and pre-push workflows
- **Clean/setup utilities**: Environment preparation and cleanup
- **Build scripts**: Laravel Zero binary compilation

### 1.7. **CI/CD Ready**

- JSON output formats for automated parsing
- Parallel execution support
- Comprehensive coverage reporting
- Cache management for performance

This configuration provides a professional-grade development environment with comprehensive testing, code quality assurance, and automated workflows suitable for both local development and CI/CD pipelines.
