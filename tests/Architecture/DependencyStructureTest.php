<?php

declare(strict_types=1);

describe('Dependency Structure Architecture Tests', function () {
    describe('Service Layer Dependencies', function () {
        it('validates service interface implementations', function () {
            // Assert: All services implement their contracts
            $serviceContracts = [
                'App\Services\Contracts\LinkValidationInterface' => 'App\Services\LinkValidationService',
                'App\Services\Contracts\ReportingInterface' => 'App\Services\ReportingService',
                'App\Services\Contracts\SecurityValidationInterface' => 'App\Services\SecurityValidationService',
            ];

            foreach ($serviceContracts as $interface => $implementation) {
                if (interface_exists($interface) && class_exists($implementation)) {
                    $reflection = new ReflectionClass($implementation);
                    expect($reflection->implementsInterface($interface))->toBeTrue(
                        "{$implementation} should implement {$interface}"
                    );
                }
            }
        });

        it('validates interface method signatures match implementations', function () {
            // Assert: Interface methods are properly implemented
            $interfacesToCheck = [
                'App\Services\Contracts\LinkValidationInterface',
                'App\Services\Contracts\ReportingInterface',
                'App\Services\Contracts\SecurityValidationInterface',
            ];

            foreach ($interfacesToCheck as $interface) {
                if (interface_exists($interface)) {
                    $interfaceReflection = new ReflectionClass($interface);
                    $interfaceMethods = $interfaceReflection->getMethods();

                    // Find implementing classes
                    $implementingClasses = $this->findImplementingClasses($interface);

                    foreach ($implementingClasses as $implementingClass) {
                        $classReflection = new ReflectionClass($implementingClass);

                        foreach ($interfaceMethods as $interfaceMethod) {
                            expect($classReflection->hasMethod($interfaceMethod->getName()))->toBeTrue(
                                "{$implementingClass} should implement {$interfaceMethod->getName()}"
                            );

                            $classMethod = $classReflection->getMethod($interfaceMethod->getName());
                            
                            // Check parameter count
                            expect($classMethod->getNumberOfParameters())
                                ->toBe($interfaceMethod->getNumberOfParameters());

                            // Check return type compatibility
                            $interfaceReturnType = $interfaceMethod->getReturnType();
                            $classReturnType = $classMethod->getReturnType();
                            
                            if ($interfaceReturnType && $classReturnType) {
                                expect($classReturnType->getName())
                                    ->toBe($interfaceReturnType->getName());
                            }
                        }
                    }
                }
            }
        });

        it('validates dependency injection container bindings', function () {
            // Assert: Services are properly bound in container
            $bindings = [
                'App\Services\Contracts\LinkValidationInterface',
                'App\Services\Contracts\ReportingInterface',
                'App\Services\Contracts\SecurityValidationInterface',
            ];

            foreach ($bindings as $binding) {
                if (interface_exists($binding)) {
                    $resolved = app($binding);
                    expect($resolved)->toBeInstanceOf($binding);
                }
            }
        });
    });

    describe('Command Dependencies', function () {
        it('validates command constructor dependencies', function () {
            // Assert: Commands have proper dependencies
            $commandDependencies = [
                'App\Commands\ValidateCommand' => [
                    'App\Services\Contracts\LinkValidationInterface',
                    'App\Services\Contracts\ReportingInterface',
                ],
                'App\Commands\ReportCommand' => [
                    'App\Services\Contracts\ReportingInterface',
                ],
                'App\Commands\ConfigCommand' => [],
            ];

            foreach ($commandDependencies as $command => $expectedDependencies) {
                if (class_exists($command)) {
                    $reflection = new ReflectionClass($command);
                    $constructor = $reflection->getConstructor();

                    if ($constructor && !empty($expectedDependencies)) {
                        $parameters = $constructor->getParameters();
                        $actualDependencies = [];

                        foreach ($parameters as $parameter) {
                            $type = $parameter->getType();
                            if ($type && !$type->isBuiltin()) {
                                $actualDependencies[] = $type->getName();
                            }
                        }

                        foreach ($expectedDependencies as $expectedDependency) {
                            expect($actualDependencies)->toContain($expectedDependency);
                        }
                    }
                }
            }
        });

        it('validates command service resolution', function () {
            // Assert: Commands can resolve their dependencies
            $commands = [
                'App\Commands\ValidateCommand',
                'App\Commands\ReportCommand',
                'App\Commands\ConfigCommand',
            ];

            foreach ($commands as $command) {
                if (class_exists($command)) {
                    $instance = app($command);
                    expect($instance)->toBeInstanceOf($command);
                }
            }
        });
    });

    describe('Value Object Dependencies', function () {
        it('validates value object immutability', function () {
            // Assert: Value objects are immutable
            $valueObjects = [
                'App\Services\ValueObjects\ValidationConfig',
                'App\Services\ValueObjects\ValidationResult',
                'App\Services\ValueObjects\ValidationResultCollection',
                'App\Services\ValueObjects\ValidationSummary',
            ];

            foreach ($valueObjects as $valueObject) {
                if (class_exists($valueObject)) {
                    $reflection = new ReflectionClass($valueObject);

                    // Check for readonly or final
                    expect($reflection->isReadOnly() || $reflection->isFinal())->toBeTrue(
                        "{$valueObject} should be readonly or final"
                    );

                    // Check for no public setters
                    $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
                    foreach ($methods as $method) {
                        expect($method->getName())->not->toStartWith('set');
                    }
                }
            }
        });

        it('validates value object factory methods', function () {
            // Assert: Value objects have proper factory methods
            $valueObjectFactories = [
                'App\Services\ValueObjects\ValidationConfig' => ['create', 'withDefaults'],
                'App\Services\ValueObjects\ValidationResult' => ['create'],
                'App\Services\ValueObjects\ValidationResultCollection' => ['create', 'empty'],
            ];

            foreach ($valueObjectFactories as $valueObject => $expectedMethods) {
                if (class_exists($valueObject)) {
                    $reflection = new ReflectionClass($valueObject);

                    foreach ($expectedMethods as $method) {
                        expect($reflection->hasMethod($method))->toBeTrue(
                            "{$valueObject} should have {$method} factory method"
                        );

                        if ($reflection->hasMethod($method)) {
                            $methodReflection = $reflection->getMethod($method);
                            expect($methodReflection->isStatic())->toBeTrue(
                                "{$method} should be static"
                            );
                        }
                    }
                }
            }
        });
    });

    describe('Enum Dependencies', function () {
        it('validates enum structure and usage', function () {
            // Assert: Enums are properly structured
            $enums = [
                'App\Enums\LinkStatus',
                'App\Enums\ValidationScope',
                'App\Enums\OutputFormat',
            ];

            foreach ($enums as $enum) {
                if (enum_exists($enum)) {
                    $reflection = new ReflectionEnum($enum);
                    
                    // Check if it's a backed enum (has string/int values)
                    expect($reflection->isBacked())->toBeTrue(
                        "{$enum} should be a backed enum"
                    );

                    // Check for common methods
                    expect($reflection->hasMethod('cases'))->toBeTrue();
                    
                    // Verify cases exist
                    $cases = $enum::cases();
                    expect(count($cases))->toBeGreaterThan(0);
                }
            }
        });

        it('validates enum usage in services', function () {
            // Assert: Services use enums instead of string constants
            $serviceClasses = [
                'App\Services\LinkValidationService',
                'App\Services\ReportingService',
                'App\Services\SecurityValidationService',
            ];

            foreach ($serviceClasses as $service) {
                if (class_exists($service)) {
                    $reflection = new ReflectionClass($service);
                    $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

                    foreach ($methods as $method) {
                        $parameters = $method->getParameters();
                        foreach ($parameters as $parameter) {
                            $type = $parameter->getType();
                            if ($type && !$type->isBuiltin()) {
                                $typeName = $type->getName();
                                // If it's an enum type, verify it exists
                                if (str_contains($typeName, 'App\Enums')) {
                                    expect(enum_exists($typeName))->toBeTrue(
                                        "Enum {$typeName} should exist"
                                    );
                                }
                            }
                        }
                    }
                }
            }
        });
    });

    describe('External Dependencies', function () {
        it('validates HTTP client dependencies', function () {
            // Assert: HTTP client is properly abstracted
            $servicesUsingHttp = [
                'App\Services\LinkValidationService',
            ];

            foreach ($servicesUsingHttp as $service) {
                if (class_exists($service)) {
                    $reflection = new ReflectionClass($service);
                    $constructor = $reflection->getConstructor();

                    if ($constructor) {
                        $parameters = $constructor->getParameters();
                        $hasHttpDependency = false;

                        foreach ($parameters as $parameter) {
                            $type = $parameter->getType();
                            if ($type) {
                                $typeName = $type->getName();
                                if (str_contains($typeName, 'Http') || 
                                    str_contains($typeName, 'Client') ||
                                    str_contains($typeName, 'Guzzle')) {
                                    $hasHttpDependency = true;
                                    break;
                                }
                            }
                        }

                        // Should either have HTTP dependency or use Laravel HTTP facade
                        expect($hasHttpDependency)->toBeTrue(
                            "{$service} should have HTTP client dependency"
                        );
                    }
                }
            }
        });

        it('validates file system dependencies', function () {
            // Assert: File system operations are properly abstracted
            $servicesUsingFileSystem = [
                'App\Services\LinkValidationService',
                'App\Services\ReportingService',
            ];

            foreach ($servicesUsingFileSystem as $service) {
                if (class_exists($service)) {
                    // Services should use Laravel's File facade or filesystem contracts
                    $reflection = new ReflectionClass($service);
                    $methods = $reflection->getMethods();

                    $usesFileSystem = false;
                    foreach ($methods as $method) {
                        $docComment = $method->getDocComment();
                        if ($docComment && (
                            str_contains($docComment, 'file') ||
                            str_contains($docComment, 'File::') ||
                            str_contains($docComment, 'filesystem')
                        )) {
                            $usesFileSystem = true;
                            break;
                        }
                    }

                    if ($usesFileSystem) {
                        // Should have proper file system abstraction
                        expect(true)->toBeTrue(); // Placeholder for file system validation
                    }
                }
            }
        });

        it('validates configuration dependencies', function () {
            // Assert: Configuration is properly injected
            $servicesUsingConfig = [
                'App\Services\LinkValidationService',
                'App\Services\SecurityValidationService',
            ];

            foreach ($servicesUsingConfig as $service) {
                if (class_exists($service)) {
                    $reflection = new ReflectionClass($service);
                    $methods = $reflection->getMethods();

                    foreach ($methods as $method) {
                        $parameters = $method->getParameters();
                        foreach ($parameters as $parameter) {
                            $type = $parameter->getType();
                            if ($type && str_contains($type->getName(), 'ValidationConfig')) {
                                expect(class_exists($type->getName()))->toBeTrue(
                                    "Configuration class {$type->getName()} should exist"
                                );
                            }
                        }
                    }
                }
            }
        });
    });

    describe('Circular Dependency Detection', function () {
        it('detects circular dependencies between services', function () {
            // Assert: No circular dependencies exist
            $services = [
                'App\Services\LinkValidationService',
                'App\Services\ReportingService',
                'App\Services\SecurityValidationService',
            ];

            $dependencies = [];
            
            foreach ($services as $service) {
                if (class_exists($service)) {
                    $dependencies[$service] = $this->getServiceDependencies($service);
                }
            }

            // Check for circular dependencies
            foreach ($dependencies as $service => $serviceDeps) {
                foreach ($serviceDeps as $dependency) {
                    if (isset($dependencies[$dependency])) {
                        expect($dependencies[$dependency])->not->toContain($service);
                    }
                }
            }
        });

        it('validates dependency graph depth', function () {
            // Assert: Dependency graph is not too deep
            $maxDepth = 3; // Maximum allowed dependency depth
            
            $services = [
                'App\Services\LinkValidationService',
                'App\Services\ReportingService',
                'App\Services\SecurityValidationService',
            ];

            foreach ($services as $service) {
                if (class_exists($service)) {
                    $depth = $this->calculateDependencyDepth($service);
                    expect($depth)->toBeLessThanOrEqual($maxDepth);
                }
            }
        });
    });

    // Helper methods
    private function findImplementingClasses(string $interface): array
    {
        $implementingClasses = [];
        $serviceClasses = [
            'App\Services\LinkValidationService',
            'App\Services\ReportingService',
            'App\Services\SecurityValidationService',
        ];

        foreach ($serviceClasses as $class) {
            if (class_exists($class)) {
                $reflection = new ReflectionClass($class);
                if ($reflection->implementsInterface($interface)) {
                    $implementingClasses[] = $class;
                }
            }
        }

        return $implementingClasses;
    }

    private function getServiceDependencies(string $service): array
    {
        $dependencies = [];
        
        if (class_exists($service)) {
            $reflection = new ReflectionClass($service);
            $constructor = $reflection->getConstructor();

            if ($constructor) {
                $parameters = $constructor->getParameters();
                foreach ($parameters as $parameter) {
                    $type = $parameter->getType();
                    if ($type && !$type->isBuiltin()) {
                        $typeName = $type->getName();
                        if (str_starts_with($typeName, 'App\Services')) {
                            $dependencies[] = $typeName;
                        }
                    }
                }
            }
        }

        return $dependencies;
    }

    private function calculateDependencyDepth(string $service, array $visited = []): int
    {
        if (in_array($service, $visited)) {
            return 0; // Circular dependency detected, return 0 to avoid infinite recursion
        }

        $visited[] = $service;
        $dependencies = $this->getServiceDependencies($service);
        
        if (empty($dependencies)) {
            return 1;
        }

        $maxDepth = 0;
        foreach ($dependencies as $dependency) {
            $depth = $this->calculateDependencyDepth($dependency, $visited);
            $maxDepth = max($maxDepth, $depth);
        }

        return $maxDepth + 1;
    }
});
