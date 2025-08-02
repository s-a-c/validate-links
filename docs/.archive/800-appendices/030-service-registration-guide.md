# 800.3. Laravel Service Registration Guide

**Date:** July 22, 2025
**Project:** Laravel Zero validate-links Implementation
**Framework:** Laravel 12.20+ (Current Stable)
**Compliance:** `.ai/guidelines.md` and `.ai/guidelines/` standards

## 800.3.1. Overview

This guide provides comprehensive documentation for Laravel service registration patterns, explaining the differences between `register()` and `boot()` methods, and the relationship between `AppServiceProvider.php` and the `bootstrap/providers.php` configuration.

## 800.3.2. 🔄 Service Registration Lifecycle

### 800.3.2.1. Phase 1: Provider Discovery

```
bootstrap/providers.php → Application loads provider classes
```

### Phase 2: Registration Phase

```
register() methods called for ALL providers
↓
Services bound to container
↓
NO service resolution allowed
```

### Phase 3: Boot Phase

```
boot() methods called for ALL providers
↓
Services can be resolved safely
↓
Configuration and event registration
```

## 📋 register() vs boot() Methods

### register() Method - Service Binding

**Purpose:** Bind services into the Laravel service container

**Rules:**

- ✅ **DO:** Bind interfaces to implementations
- ✅ **DO:** Register singletons and factory closures
- ✅ **DO:** Register configuration values
- ❌ **DON'T:** Access other services from the container
- ❌ **DON'T:** Perform configuration that requires other services

**Example:**

```php
public function register(): void
{
    // ✅ CORRECT: Simple interface binding
    $this->app->singleton(StatisticsInterface::class, StatisticsService::class);
    
    // ✅ CORRECT: Factory closure for complex dependencies
    $this->app->singleton(ReportingInterface::class, function ($app) {
        return new ReportingService(
            $app->make(StatisticsInterface::class)
        );
    });
    
    // ❌ INCORRECT: Accessing services (may fail!)
    // $config = $this->app->make(ConfigurationService::class);
}
```

### boot() Method - Service Configuration

**Purpose:** Configure services after all providers have registered their bindings

**Rules:**

- ✅ **DO:** Access other services from the container
- ✅ **DO:** Publish configuration files
- ✅ **DO:** Register event listeners
- ✅ **DO:** Configure services with dependencies
- ❌ **DON'T:** Bind new services (use register() instead)

**Example:**

```php
public function boot(): void
{
    // ✅ CORRECT: Access services safely
    $reportingService = $this->app->make(ReportingInterface::class);
    
    // ✅ CORRECT: Publish configuration
    $this->publishes([
        __DIR__.'/../../config/validate-links.php' => config_path('validate-links.php'),
    ], 'validate-links-config');
    
    // ✅ CORRECT: Register event listeners
    Event::listen(ValidationCompleted::class, ValidationCompletedListener::class);
}
```

## 🏗️ AppServiceProvider vs Custom Providers

### AppServiceProvider.php

**Use for:**

- Framework-level services
- Third-party package bindings
- Global application concerns
- Environment-specific configurations

```php
<?php
// app/Providers/AppServiceProvider.php

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Framework-level services
        if ($this->app->environment('local')) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
        }
        
        // Global utilities
        $this->app->singleton('app.version', fn() => config('app.version', '1.0.0'));
    }

    public function boot(): void
    {
        // Global configuration
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
```

### Custom Service Providers

**Use for:**

- Domain-specific services
- Business logic services
- Feature-specific concerns
- Module-specific configurations

```php
<?php
// app/Providers/ValidateLinksServiceProvider.php

class ValidateLinksServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Domain-specific services
        $this->app->singleton(LinkValidationInterface::class, LinkValidationService::class);
        $this->app->singleton(ReportingInterface::class, ReportingService::class);
    }

    public function boot(): void
    {
        // Feature-specific configuration
        $this->publishes([
            __DIR__.'/../../config/validate-links.php' => config_path('validate-links.php'),
        ], 'validate-links-config');
    }
}
```

## 📁 bootstrap/providers.php Configuration

**Laravel 12 Approach:** Providers are registered in the providers array:

```php
<?php
// bootstrap/providers.php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\ValidateLinksServiceProvider::class,
];
```

**Benefits:**

- **Performance:** Providers loaded only when needed
- **Organization:** Clear separation of concerns
- **Maintainability:** Easy to add/remove providers
- **Testing:** Individual providers can be tested in isolation

**Laravel 12 Enhancements:**

- **Improved Service Resolution:** Faster container resolution with better caching
- **Enhanced Debugging:** Better error messages and stack traces for service issues
- **Modern PHP Support:** Full PHP 8.4+ compatibility with latest language features
- **Security Improvements:** Updated security practices and enhanced protection

**vs Laravel 11 and earlier config/app.php:**

```php
// OLD WAY (Laravel 11 and earlier)
'providers' => [
    App\Providers\AppServiceProvider::class,
    App\Providers\ValidateLinksServiceProvider::class,
],
```

## 🎯 Best Practices

### 1. Dependency Order

```php
// ✅ CORRECT: Register dependencies first
$this->app->singleton(StatisticsInterface::class, StatisticsService::class);
$this->app->singleton(ReportingInterface::class, function ($app) {
    return new ReportingService($app->make(StatisticsInterface::class));
});
```

### 2. Singleton vs Bind

```php
// ✅ Singleton for stateful services
$this->app->singleton(StatisticsInterface::class, StatisticsService::class);

// ✅ Bind for stateless services (new instance each time)
$this->app->bind(ValidationRequest::class, ValidationRequest::class);
```

### 3. Interface Binding

```php
// ✅ CORRECT: Bind interfaces to implementations
$this->app->singleton(LinkValidationInterface::class, LinkValidationService::class);

// ❌ INCORRECT: Don't bind concrete classes to themselves
$this->app->singleton(LinkValidationService::class, LinkValidationService::class);
```

### 4. Factory Closures for Complex Dependencies

```php
$this->app->singleton(ReportingInterface::class, function ($app) {
    $service = new ReportingService($app->make(StatisticsInterface::class));
    
    // Configure the service
    $service->addFormatter('console', new ConsoleFormatter());
    $service->addFormatter('json', new JsonFormatter());
    
    return $service;
});
```

## 🧪 Testing Service Registration

```php
<?php
// tests/Unit/Providers/ValidateLinksServiceProviderTest.php

class ValidateLinksServiceProviderTest extends TestCase
{
    public function test_services_are_registered(): void
    {
        $provider = new ValidateLinksServiceProvider($this->app);
        $provider->register();
        
        $this->assertTrue($this->app->bound(LinkValidationInterface::class));
        $this->assertInstanceOf(
            LinkValidationService::class,
            $this->app->make(LinkValidationInterface::class)
        );
    }
    
    public function test_singleton_services_return_same_instance(): void
    {
        $provider = new ValidateLinksServiceProvider($this->app);
        $provider->register();
        
        $instance1 = $this->app->make(LinkValidationInterface::class);
        $instance2 = $this->app->make(LinkValidationInterface::class);
        
        $this->assertSame($instance1, $instance2);
    }
}
```

## 🔗 Related Documentation

- **[Implementation Guide](../020-implementation-guide.md#21-service-implementation-completion)** - Complete service registration implementation
- **[Architecture Overview](../030-architecture-overview.md#333-service-provider-architecture)** - Service provider architecture patterns
- **[Laravel Service Container Documentation](https://laravel.com/docs/12.x/container)** - Official Laravel documentation

---

*This guide ensures consistent service registration patterns across the validate-links application and provides clear guidelines for junior developers.*
