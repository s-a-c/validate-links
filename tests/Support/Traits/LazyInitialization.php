<?php

declare(strict_types=1);

namespace Tests\Support\Traits;

/**
 * Lazy Initialization Trait for PEST Tests
 * 
 * Implements PEST's let() pattern for deferred object creation,
 * providing memory-efficient test setup with lazy loading.
 */
trait LazyInitialization
{
    /**
     * Lazy-loaded instances cache
     * @var array<string, mixed>
     */
    private array $lazyInstances = [];
    
    /**
     * Lazy initialization definitions
     * @var array<string, callable(): mixed>
     */
    private array $lazyDefinitions = [];
    
    /**
     * Define a lazy-loaded property using PEST's let() pattern
     *
     * @param string $name Property name
     * @param callable(): mixed $factory Factory function
     * @return void
     */
    protected function let(string $name, callable $factory): void
    {
        $this->lazyDefinitions[$name] = $factory;
    }
    
    /**
     * Get lazy-loaded instance (creates on first access)
     * 
     * @param string $name Property name
     * @return mixed
     */
    protected function lazy(string $name): mixed
    {
        if (!isset($this->lazyInstances[$name])) {
            if (!isset($this->lazyDefinitions[$name])) {
                throw new \InvalidArgumentException("Lazy property '{$name}' not defined");
            }
            
            $factory = $this->lazyDefinitions[$name];
            $this->lazyInstances[$name] = $factory();
        }
        
        return $this->lazyInstances[$name];
    }
    
    /**
     * Check if lazy property is defined
     * 
     * @param string $name Property name
     * @return bool
     */
    protected function hasLazy(string $name): bool
    {
        return isset($this->lazyDefinitions[$name]);
    }
    
    /**
     * Clear lazy instances (useful for test isolation)
     * 
     * @param string|null $name Specific property name or null for all
     * @return void
     */
    protected function clearLazy(?string $name = null): void
    {
        if ($name === null) {
            $this->lazyInstances = [];
        } else {
            unset($this->lazyInstances[$name]);
        }
    }
    
    /**
     * Define multiple lazy properties at once
     *
     * @param array<string, callable(): mixed> $definitions Array of name => factory pairs
     * @return void
     */
    protected function letMany(array $definitions): void
    {
        foreach ($definitions as $name => $factory) {
            if (is_string($name) && is_callable($factory)) {
                $this->let($name, $factory);
            }
        }
    }
    
    /**
     * Magic method to access lazy properties directly
     * 
     * @param string $name Property name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        if ($this->hasLazy($name)) {
            return $this->lazy($name);
        }
        
        throw new \InvalidArgumentException("Property '{$name}' not found");
    }
    
    /**
     * Magic method to check if lazy property exists
     * 
     * @param string $name Property name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return $this->hasLazy($name);
    }
    
    /**
     * Clean up lazy instances after test
     * Call this in afterEach() or tearDown()
     * 
     * @return void
     */
    protected function cleanupLazyInstances(): void
    {
        $this->lazyInstances = [];
        $this->lazyDefinitions = [];
    }
}