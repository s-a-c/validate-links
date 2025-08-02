<?php

declare(strict_types=1);

use App\Services\PluginManager;
use App\Contracts\ExtensionInterface;
use Illuminate\Support\Collection;

/**
 * Unit test suite for PluginManager.
 *
 * Tests plugin management functionality including plugin registration, booting,
 * retrieval, and integration with ExtensionInterface implementations.
 *
 * @group unit
 * @group services
 * @group plugins
 *
 * @covers \App\Services\PluginManager
 */
describe('PluginManager', function () {
    beforeEach(function () {
        // Setup Phase: Prepare comprehensive test environment

        // Create service instance
        $this->manager = new PluginManager();

        // Create test plugin implementations using anonymous classes
        $this->testPlugins = [
            'basic' => new class implements ExtensionInterface {
                public $registered = false;
                public $booted = false;

                public function getName(): string
                {
                    return 'Basic Test Plugin';
                }

                public function getVersion(): string
                {
                    return '1.0.0';
                }

                public function getDescription(): string
                {
                    return 'A basic test plugin for unit testing';
                }

                public function register(): void
                {
                    $this->registered = true;
                }

                public function boot(): void
                {
                    $this->booted = true;
                }
            },
            'advanced' => new class implements ExtensionInterface {
                public $registered = false;
                public $booted = false;
                public $registerCallCount = 0;
                public $bootCallCount = 0;

                public function getName(): string
                {
                    return 'Advanced Test Plugin';
                }

                public function getVersion(): string
                {
                    return '2.1.0';
                }

                public function getDescription(): string
                {
                    return 'An advanced test plugin with call tracking';
                }

                public function register(): void
                {
                    $this->registered = true;
                    $this->registerCallCount++;
                }

                public function boot(): void
                {
                    $this->booted = true;
                    $this->bootCallCount++;
                }
            },
            'minimal' => new class implements ExtensionInterface {
                public $registered = false;
                public $booted = false;

                public function getName(): string
                {
                    return 'Minimal Plugin';
                }

                public function getVersion(): string
                {
                    return '0.1.0';
                }

                public function getDescription(): string
                {
                    return 'Minimal plugin implementation';
                }

                public function register(): void
                {
                    $this->registered = true;
                }

                public function boot(): void
                {
                    $this->booted = true;
                }
            },
        ];

        // Test data for plugin names
        $this->testPluginNames = [
            'existing' => ['Basic Test Plugin', 'Advanced Test Plugin', 'Minimal Plugin'],
            'non_existing' => ['Non Existent Plugin', 'Missing Plugin', 'Unknown Extension'],
        ];

        // Expected plugin collection behavior
        $this->expectedCollectionType = Collection::class;
    });

    afterEach(function () {
        // Teardown Phase: Clean up test environment

        // Clear properties
        unset(
            $this->manager,
            $this->testPlugins,
            $this->testPluginNames,
            $this->expectedCollectionType
        );

        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    });

    describe('service instantiation and structure', function () {
        it('can be instantiated', function () {
            expect($this->manager)->toBeInstanceOf(PluginManager::class);
        });

        it('is a final readonly class', function () {
            $reflection = new \ReflectionClass($this->manager);

            expect($reflection->isFinal())->toBeTrue()
                ->and($reflection->isReadOnly())->toBeTrue();
        });

        it('has all required public methods', function () {
            $reflection = new \ReflectionClass($this->manager);

            expect($reflection->hasMethod('register'))->toBeTrue()
                ->and($reflection->hasMethod('boot'))->toBeTrue()
                ->and($reflection->hasMethod('getPlugin'))->toBeTrue();
        });

        it('initializes with empty plugin collection', function () {
            // Test that no plugins are registered initially
            expect($this->manager->getPlugin('any-plugin'))->toBeNull();
        });
    });

    describe('register method', function () {
        it('registers a plugin successfully', function () {
            $plugin = $this->testPlugins['basic'];

            $this->manager->register($plugin);

            expect($plugin->registered)->toBeTrue()
                ->and($this->manager->getPlugin($plugin->getName()))->toBe($plugin);
        });

        it('calls plugin register method during registration', function () {
            $plugin = $this->testPlugins['advanced'];

            expect($plugin->registerCallCount)->toBe(0);

            $this->manager->register($plugin);

            expect($plugin->registerCallCount)->toBe(1)
                ->and($plugin->registered)->toBeTrue();
        });

        it('registers multiple plugins', function () {
            foreach ($this->testPlugins as $plugin) {
                $this->manager->register($plugin);
            }

            foreach ($this->testPlugins as $plugin) {
                expect($this->manager->getPlugin($plugin->getName()))->toBe($plugin)
                    ->and($plugin->registered)->toBeTrue();
            }
        });

        it('overwrites plugin with same name', function () {
            $firstPlugin = $this->testPlugins['basic'];

            // Register first plugin
            $this->manager->register($firstPlugin);
            expect($this->manager->getPlugin($firstPlugin->getName()))->toBe($firstPlugin);

            // Create a plugin with the same name as the first one
            $duplicateNamePlugin = new class implements ExtensionInterface {
                public $registered = false;

                public function getName(): string
                {
                    return 'Basic Test Plugin'; // Same name as first plugin
                }

                public function getVersion(): string
                {
                    return '3.0.0';
                }

                public function getDescription(): string
                {
                    return 'Duplicate name plugin';
                }

                public function register(): void
                {
                    $this->registered = true;
                }

                public function boot(): void
                {
                    // Implementation
                }
            };

            // Register plugin with duplicate name
            $this->manager->register($duplicateNamePlugin);

            // Should overwrite the first plugin
            expect($this->manager->getPlugin($duplicateNamePlugin->getName()))->toBe($duplicateNamePlugin)
                ->and($this->manager->getPlugin($duplicateNamePlugin->getName()))->not->toBe($firstPlugin);
        });
    });

    describe('boot method', function () {
        it('boots all registered plugins', function () {
            // Register multiple plugins
            foreach ($this->testPlugins as $plugin) {
                $this->manager->register($plugin);
            }

            // Boot all plugins
            $this->manager->boot();

            // Verify all plugins were booted
            foreach ($this->testPlugins as $plugin) {
                expect($plugin->booted)->toBeTrue();
            }
        });

        it('calls boot method on each plugin exactly once', function () {
            $plugin = $this->testPlugins['advanced'];
            $this->manager->register($plugin);

            expect($plugin->bootCallCount)->toBe(0);

            $this->manager->boot();

            expect($plugin->bootCallCount)->toBe(1)
                ->and($plugin->booted)->toBeTrue();
        });

        it('boots plugins in registration order', function () {
            $bootOrder = [];

            // Create plugins that track boot order
            $plugin1 = new class($bootOrder) implements ExtensionInterface {
                private $bootOrder;

                public function __construct(&$bootOrder)
                {
                    $this->bootOrder = &$bootOrder;
                }

                public function getName(): string
                {
                    return 'First Plugin';
                }

                public function getVersion(): string
                {
                    return '1.0.0';
                }

                public function getDescription(): string
                {
                    return 'First plugin';
                }

                public function register(): void
                {
                    // Implementation
                }

                public function boot(): void
                {
                    $this->bootOrder[] = 'First Plugin';
                }
            };

            $plugin2 = new class($bootOrder) implements ExtensionInterface {
                private $bootOrder;

                public function __construct(&$bootOrder)
                {
                    $this->bootOrder = &$bootOrder;
                }

                public function getName(): string
                {
                    return 'Second Plugin';
                }

                public function getVersion(): string
                {
                    return '1.0.0';
                }

                public function getDescription(): string
                {
                    return 'Second plugin';
                }

                public function register(): void
                {
                    // Implementation
                }

                public function boot(): void
                {
                    $this->bootOrder[] = 'Second Plugin';
                }
            };

            // Register in specific order
            $this->manager->register($plugin1);
            $this->manager->register($plugin2);

            // Boot all plugins
            $this->manager->boot();

            // Verify boot order matches registration order
            expect($bootOrder)->toBe(['First Plugin', 'Second Plugin']);
        });

        it('handles empty plugin collection gracefully', function () {
            // Boot with no registered plugins should not throw exception
            expect(fn() => $this->manager->boot())->not->toThrow(Exception::class);
        });
    });

    describe('getPlugin method', function () {
        it('retrieves registered plugin by name', function () {
            $plugin = $this->testPlugins['basic'];
            $this->manager->register($plugin);

            $retrieved = $this->manager->getPlugin($plugin->getName());

            expect($retrieved)->toBe($plugin);
        });

        it('returns null for non-existent plugin', function () {
            foreach ($this->testPluginNames['non_existing'] as $name) {
                expect($this->manager->getPlugin($name))->toBeNull();
            }
        });

        it('retrieves correct plugin when multiple are registered', function () {
            // Register all test plugins
            foreach ($this->testPlugins as $plugin) {
                $this->manager->register($plugin);
            }

            // Verify each can be retrieved correctly
            foreach ($this->testPlugins as $plugin) {
                expect($this->manager->getPlugin($plugin->getName()))->toBe($plugin);
            }
        });

        it('performs exact name matching', function () {
            $plugin = $this->testPlugins['basic'];
            $this->manager->register($plugin);

            // Exact match should work
            expect($this->manager->getPlugin('Basic Test Plugin'))->toBe($plugin);

            // Partial matches should not work
            expect($this->manager->getPlugin('Basic'))->toBeNull()
                ->and($this->manager->getPlugin('Test Plugin'))->toBeNull()
                ->and($this->manager->getPlugin('basic test plugin'))->toBeNull(); // Case sensitive
        });
    });

    describe('integration scenarios', function () {
        it('handles complete plugin lifecycle', function () {
            $plugin = $this->testPlugins['advanced'];

            // Initial state
            expect($plugin->registered)->toBeFalse()
                ->and($plugin->booted)->toBeFalse()
                ->and($plugin->registerCallCount)->toBe(0)
                ->and($plugin->bootCallCount)->toBe(0);

            // Register plugin
            $this->manager->register($plugin);
            expect($plugin->registered)->toBeTrue()
                ->and($plugin->registerCallCount)->toBe(1)
                ->and($plugin->booted)->toBeFalse()
                ->and($plugin->bootCallCount)->toBe(0);

            // Boot plugins
            $this->manager->boot();
            expect($plugin->booted)->toBeTrue()
                ->and($plugin->bootCallCount)->toBe(1);

            // Verify plugin is retrievable
            expect($this->manager->getPlugin($plugin->getName()))->toBe($plugin);
        });

        it('handles multiple plugins with different lifecycles', function () {
            $plugins = array_values($this->testPlugins);

            // Register plugins at different times
            $this->manager->register($plugins[0]);
            $this->manager->register($plugins[1]);

            // Boot all registered plugins
            $this->manager->boot();

            // Register another plugin after booting
            $this->manager->register($plugins[2]);

            // Verify states
            expect($plugins[0]->registered)->toBeTrue()
                ->and($plugins[0]->booted)->toBeTrue()
                ->and($plugins[1]->registered)->toBeTrue()
                ->and($plugins[1]->booted)->toBeTrue()
                ->and($plugins[2]->registered)->toBeTrue();

            // Boot again to boot the newly registered plugin
            $this->manager->boot();
            expect($plugins[2]->booted)->toBeTrue();
        });
    });

    describe('edge cases and error handling', function () {
        it('handles plugins with empty names', function () {
            $emptyNamePlugin = new class implements ExtensionInterface {
                public function getName(): string
                {
                    return '';
                }

                public function getVersion(): string
                {
                    return '1.0.0';
                }

                public function getDescription(): string
                {
                    return 'Plugin with empty name';
                }

                public function register(): void
                {
                    // Implementation
                }

                public function boot(): void
                {
                    // Implementation
                }
            };

            $this->manager->register($emptyNamePlugin);

            expect($this->manager->getPlugin(''))->toBe($emptyNamePlugin);
        });

        it('handles plugins with special characters in names', function () {
            $specialNamePlugin = new class implements ExtensionInterface {
                public function getName(): string
                {
                    return 'Plugin-With_Special.Characters@123!';
                }

                public function getVersion(): string
                {
                    return '1.0.0';
                }

                public function getDescription(): string
                {
                    return 'Plugin with special characters in name';
                }

                public function register(): void
                {
                    // Implementation
                }

                public function boot(): void
                {
                    // Implementation
                }
            };

            $this->manager->register($specialNamePlugin);

            expect($this->manager->getPlugin('Plugin-With_Special.Characters@123!'))->toBe($specialNamePlugin);
        });

        it('handles very long plugin names', function () {
            $longName = str_repeat('VeryLongPluginName', 50);
            $longNamePlugin = new class($longName) implements ExtensionInterface {
                private $name;

                public function __construct(string $name)
                {
                    $this->name = $name;
                }

                public function getName(): string
                {
                    return $this->name;
                }

                public function getVersion(): string
                {
                    return '1.0.0';
                }

                public function getDescription(): string
                {
                    return 'Plugin with very long name';
                }

                public function register(): void
                {
                    // Implementation
                }

                public function boot(): void
                {
                    // Implementation
                }
            };

            $this->manager->register($longNamePlugin);

            expect($this->manager->getPlugin($longName))->toBe($longNamePlugin);
        });
    });
});
