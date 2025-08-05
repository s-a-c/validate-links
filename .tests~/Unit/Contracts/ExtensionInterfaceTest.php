<?php

declare(strict_types=1);

use App\Contracts\ExtensionInterface;

describe('ExtensionInterface', function () {
    beforeEach(function () {
        // Minimal setup - only create reflection once per test group
        $this->interfaceName = ExtensionInterface::class;
        $this->reflection = new ReflectionClass($this->interfaceName);
    });

    describe('interface structure', function () {
        it('is an interface', function () {
            expect($this->reflection->isInterface())->toBeTrue()
                ->and($this->reflection->isTrait())->toBeFalse();
        });

        it('has correct namespace', function () {
            expect($this->reflection->getNamespaceName())->toBe('App\Contracts');
        });

        it('has correct name', function () {
            expect($this->reflection->getName())->toBe('App\Contracts\ExtensionInterface')
                ->and($this->reflection->getShortName())->toBe('ExtensionInterface');
        });

        it('has no parent interfaces', function () {
            expect($this->reflection->getInterfaces())->toBeEmpty()
                ->and($this->reflection->getParentClass())->toBeFalse();
        });

        it('is not instantiable', function () {
            expect($this->reflection->isInstantiable())->toBeFalse();
        });
    });

    describe('method structure', function () {
        it('has exactly 5 methods', function () {
            $methods = $this->reflection->getMethods();
            expect($methods)->toHaveCount(5);
        });

        it('has all required methods', function () {
            $methodNames = array_map(fn ($method) => $method->getName(), $this->reflection->getMethods());

            expect($methodNames)->toContain('getName')
                ->and($methodNames)->toContain('getVersion')
                ->and($methodNames)->toContain('getDescription')
                ->and($methodNames)->toContain('register')
                ->and($methodNames)->toContain('boot');
        });
    });

    describe('method signatures', function () {
        it('getName has correct signature', function () {
            $method = $this->reflection->getMethod('getName');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(0)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('string');
        });

        it('getVersion has correct signature', function () {
            $method = $this->reflection->getMethod('getVersion');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(0)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('string');
        });

        it('getDescription has correct signature', function () {
            $method = $this->reflection->getMethod('getDescription');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(0)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('string');
        });

        it('register has correct signature', function () {
            $method = $this->reflection->getMethod('register');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(0)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('void');
        });

        it('boot has correct signature', function () {
            $method = $this->reflection->getMethod('boot');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(0)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('void');
        });
    });

    describe('method visibility and modifiers', function () {
        it('all methods are public and abstract', function () {
            foreach ($this->reflection->getMethods() as $method) {
                expect($method->isPublic())->toBeTrue("Method {$method->getName()} should be public")
                    ->and($method->isAbstract())->toBeTrue("Method {$method->getName()} should be abstract")
                    ->and($method->isFinal())->toBeFalse("Method {$method->getName()} should not be final")
                    ->and($method->isStatic())->toBeFalse("Method {$method->getName()} should not be static");
            }
        });
    });

    describe('type safety', function () {
        it('enforces string return types for getter methods', function () {
            $getterMethods = ['getName', 'getVersion', 'getDescription'];

            foreach ($getterMethods as $methodName) {
                $method = $this->reflection->getMethod($methodName);
                expect($method->hasReturnType())->toBeTrue("Method {$methodName} should have return type")
                    ->and((string) $method->getReturnType())->toBe('string', "Method {$methodName} should return string");
            }
        });

        it('enforces void return types for action methods', function () {
            $actionMethods = ['register', 'boot'];

            foreach ($actionMethods as $methodName) {
                $method = $this->reflection->getMethod($methodName);
                expect($method->hasReturnType())->toBeTrue("Method {$methodName} should have return type")
                    ->and((string) $method->getReturnType())->toBe('void', "Method {$methodName} should return void");
            }
        });
    });

    describe('interface compliance', function () {
        it('can be implemented by concrete classes', function () {
            // Create a simple test implementation without anonymous class overhead
            $testImplementation = new class implements ExtensionInterface
            {
                public function getName(): string { return 'TestExtension'; }
                public function getVersion(): string { return '1.0.0'; }
                public function getDescription(): string { return 'Test extension'; }
                public function register(): void { }
                public function boot(): void { }
            };

            expect($testImplementation)->toBeInstanceOf(ExtensionInterface::class);
        });

        it('implementation provides all required methods', function () {
            $testImplementation = new class implements ExtensionInterface
            {
                public function getName(): string { return 'TestExtension'; }
                public function getVersion(): string { return '1.0.0'; }
                public function getDescription(): string { return 'Test extension'; }
                public function register(): void { }
                public function boot(): void { }
            };

            $implementationReflection = new ReflectionClass($testImplementation);
            $requiredMethods = ['getName', 'getVersion', 'getDescription', 'register', 'boot'];

            foreach ($requiredMethods as $methodName) {
                expect($implementationReflection->hasMethod($methodName))->toBeTrue("Method {$methodName} should exist");
                $method = $implementationReflection->getMethod($methodName);
                expect($method->isPublic())->toBeTrue("Method {$methodName} should be public");
            }
        });

        it('implementation methods return correct types', function () {
            $testImplementation = new class implements ExtensionInterface
            {
                public function getName(): string { return 'TestExtension'; }
                public function getVersion(): string { return '1.0.0'; }
                public function getDescription(): string { return 'Test extension'; }
                public function register(): void { }
                public function boot(): void { }
            };

            expect($testImplementation->getName())->toBeString()
                ->and($testImplementation->getVersion())->toBeString()
                ->and($testImplementation->getDescription())->toBeString();

            // Test void methods don't throw exceptions
            expect(fn () => $testImplementation->register())->not->toThrow(Exception::class)
                ->and(fn () => $testImplementation->boot())->not->toThrow(Exception::class);
        });
    });
});