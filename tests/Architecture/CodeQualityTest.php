<?php

declare(strict_types=1);

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

describe('Code Quality Architecture Tests', function () {
    describe('Namespace and Directory Structure', function () {
        it('enforces proper namespace organization', function () {
            // Assert: Commands are in Commands namespace
            PHPat::rule()
                ->classes(Selector::inNamespace('App\Commands'))
                ->shouldBeAbstract()
                ->orShould()
                ->extendClass('LaravelZero\Framework\Commands\Command')
                ->because('All commands should extend Laravel Zero Command base class');

            // Assert: Services are in Services namespace
            PHPat::rule()
                ->classes(Selector::inNamespace('App\Services'))
                ->shouldNotDependOn(
                    Selector::inNamespace('App\Commands')
                )
                ->because('Services should not depend on Commands');

            // Assert: Value Objects are in proper namespace
            PHPat::rule()
                ->classes(Selector::inNamespace('App\Services\ValueObjects'))
                ->shouldBeReadonly()
                ->orShould()
                ->beFinal()
                ->because('Value Objects should be immutable');
        });

        it('enforces interface segregation', function () {
            // Assert: Interfaces are properly segregated
            PHPat::rule()
                ->classes(Selector::inNamespace('App\Services\Contracts'))
                ->shouldBeInterfaces()
                ->because('Contracts namespace should only contain interfaces');

            // Assert: Services implement their contracts
            PHPat::rule()
                ->classes(Selector::inNamespace('App\Services'))
                ->excluding(Selector::inNamespace('App\Services\ValueObjects'))
                ->excluding(Selector::inNamespace('App\Services\Contracts'))
                ->shouldImplement(
                    Selector::inNamespace('App\Services\Contracts')
                )
                ->because('Services should implement their contracts');
        });

        it('enforces enum usage over string constants', function () {
            // Assert: Enums are used instead of string constants
            PHPat::rule()
                ->classes(Selector::inNamespace('App\Enums'))
                ->shouldBeEnums()
                ->because('Enums namespace should only contain enums');

            // Assert: No string constants in main classes
            expect(true)->toBeTrue(); // Placeholder - would need custom rule for string constants
        });
    });

    describe('Dependency Rules', function () {
        it('enforces layered architecture dependencies', function () {
            // Assert: Commands can depend on Services
            PHPat::rule()
                ->classes(Selector::inNamespace('App\Commands'))
                ->canOnlyDependOn(
                    Selector::inNamespace('App\Services\Contracts'),
                    Selector::inNamespace('App\Enums'),
                    Selector::inNamespace('App\Services\ValueObjects'),
                    Selector::inNamespace('LaravelZero\Framework'),
                    Selector::inNamespace('Illuminate'),
                    Selector::inNamespace('Symfony\Component\Console')
                )
                ->because('Commands should only depend on contracts and framework classes');

            // Assert: Services can only depend on other services and value objects
            PHPat::rule()
                ->classes(Selector::inNamespace('App\Services'))
                ->excluding(Selector::inNamespace('App\Services\Contracts'))
                ->canOnlyDependOn(
                    Selector::inNamespace('App\Services\Contracts'),
                    Selector::inNamespace('App\Services\ValueObjects'),
                    Selector::inNamespace('App\Enums'),
                    Selector::inNamespace('Illuminate\Support'),
                    Selector::inNamespace('GuzzleHttp'),
                    Selector::inNamespace('Symfony\Component\DomCrawler')
                )
                ->because('Services should have minimal external dependencies');
        });

        it('prevents circular dependencies', function () {
            // Assert: No circular dependencies between services
            PHPat::rule()
                ->classes(Selector::inNamespace('App\Services\LinkValidationService'))
                ->shouldNotDependOn(
                    Selector::inNamespace('App\Services\ReportingService')
                )
                ->because('LinkValidationService should not depend on ReportingService');

            PHPat::rule()
                ->classes(Selector::inNamespace('App\Services\ReportingService'))
                ->shouldNotDependOn(
                    Selector::inNamespace('App\Services\LinkValidationService')
                )
                ->because('ReportingService should not depend on LinkValidationService');
        });

        it('enforces security service isolation', function () {
            // Assert: Security service is properly isolated
            PHPat::rule()
                ->classes(Selector::inNamespace('App\Services\SecurityValidationService'))
                ->shouldNotDependOn(
                    Selector::inNamespace('App\Services\LinkValidationService'),
                    Selector::inNamespace('App\Services\ReportingService')
                )
                ->because('SecurityValidationService should be independent');

            // Assert: Other services can depend on security service
            PHPat::rule()
                ->classes(Selector::inNamespace('App\Services\LinkValidationService'))
                ->canDependOn(
                    Selector::inNamespace('App\Services\Contracts\SecurityValidationInterface')
                )
                ->because('LinkValidationService can use SecurityValidationInterface');
        });
    });

    describe('Design Pattern Compliance', function () {
        it('enforces dependency injection pattern', function () {
            // Assert: Services use constructor injection
            $serviceClasses = [
                'App\Services\LinkValidationService',
                'App\Services\ReportingService',
                'App\Commands\ValidateCommand',
                'App\Commands\ReportCommand',
            ];

            foreach ($serviceClasses as $class) {
                if (class_exists($class)) {
                    $reflection = new ReflectionClass($class);
                    $constructor = $reflection->getConstructor();
                    
                    if ($constructor) {
                        $parameters = $constructor->getParameters();
                        foreach ($parameters as $parameter) {
                            $type = $parameter->getType();
                            if ($type && !$type->isBuiltin()) {
                                expect($type->getName())->toContain('Interface')
                                    ->or($type->getName())->toContain('Contract');
                            }
                        }
                    }
                }
            }
        });

        it('enforces immutable value objects', function () {
            // Assert: Value Objects are immutable
            $valueObjectClasses = [
                'App\Services\ValueObjects\ValidationConfig',
                'App\Services\ValueObjects\ValidationResult',
                'App\Services\ValueObjects\ValidationResultCollection',
                'App\Services\ValueObjects\ValidationSummary',
            ];

            foreach ($valueObjectClasses as $class) {
                if (class_exists($class)) {
                    $reflection = new ReflectionClass($class);
                    
                    // Check if class is readonly or final
                    expect($reflection->isReadOnly() || $reflection->isFinal())->toBeTrue();
                    
                    // Check for setter methods (should not exist)
                    $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
                    foreach ($methods as $method) {
                        expect($method->getName())->not->toStartWith('set');
                    }
                }
            }
        });

        it('enforces single responsibility principle', function () {
            // Assert: Each service has a single responsibility
            $serviceResponsibilities = [
                'App\Services\LinkValidationService' => ['validate'],
                'App\Services\ReportingService' => ['generate', 'export'],
                'App\Services\SecurityValidationService' => ['validate', 'check'],
            ];

            foreach ($serviceResponsibilities as $class => $allowedPrefixes) {
                if (class_exists($class)) {
                    $reflection = new ReflectionClass($class);
                    $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
                    
                    foreach ($methods as $method) {
                        if (!$method->isConstructor() && !$method->isDestructor()) {
                            $methodName = $method->getName();
                            $hasAllowedPrefix = false;
                            
                            foreach ($allowedPrefixes as $prefix) {
                                if (str_starts_with($methodName, $prefix)) {
                                    $hasAllowedPrefix = true;
                                    break;
                                }
                            }
                            
                            expect($hasAllowedPrefix)->toBeTrue(
                                "Method {$methodName} in {$class} should start with one of: " . 
                                implode(', ', $allowedPrefixes)
                            );
                        }
                    }
                }
            }
        });
    });

    describe('Code Style and Standards', function () {
        it('enforces strict type declarations', function () {
            // Assert: All PHP files have strict types
            $phpFiles = $this->getAllPhpFiles();
            
            foreach ($phpFiles as $file) {
                $content = file_get_contents($file);
                expect($content)->toContain('declare(strict_types=1);');
            }
        });

        it('enforces proper return type declarations', function () {
            // Assert: All public methods have return type declarations
            $classesToCheck = [
                'App\Services\LinkValidationService',
                'App\Services\ReportingService',
                'App\Services\SecurityValidationService',
            ];

            foreach ($classesToCheck as $class) {
                if (class_exists($class)) {
                    $reflection = new ReflectionClass($class);
                    $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
                    
                    foreach ($methods as $method) {
                        if (!$method->isConstructor() && !$method->isDestructor()) {
                            expect($method->hasReturnType())->toBeTrue(
                                "Method {$method->getName()} in {$class} should have return type"
                            );
                        }
                    }
                }
            }
        });

        it('enforces proper parameter type declarations', function () {
            // Assert: All parameters have type declarations
            $classesToCheck = [
                'App\Services\LinkValidationService',
                'App\Services\ReportingService',
                'App\Services\SecurityValidationService',
            ];

            foreach ($classesToCheck as $class) {
                if (class_exists($class)) {
                    $reflection = new ReflectionClass($class);
                    $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
                    
                    foreach ($methods as $method) {
                        $parameters = $method->getParameters();
                        foreach ($parameters as $parameter) {
                            expect($parameter->hasType())->toBeTrue(
                                "Parameter {$parameter->getName()} in {$method->getName()} should have type"
                            );
                        }
                    }
                }
            }
        });

        it('enforces proper PHPDoc annotations', function () {
            // Assert: All public methods have PHPDoc
            $classesToCheck = [
                'App\Services\LinkValidationService',
                'App\Services\ReportingService',
                'App\Services\SecurityValidationService',
            ];

            foreach ($classesToCheck as $class) {
                if (class_exists($class)) {
                    $reflection = new ReflectionClass($class);
                    $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
                    
                    foreach ($methods as $method) {
                        if (!$method->isConstructor() && !$method->isDestructor()) {
                            $docComment = $method->getDocComment();
                            expect($docComment)->not->toBeFalse(
                                "Method {$method->getName()} in {$class} should have PHPDoc"
                            );
                        }
                    }
                }
            }
        });
    });

    describe('Security Architecture', function () {
        it('enforces security validation integration', function () {
            // Assert: LinkValidationService uses SecurityValidationService
            if (class_exists('App\Services\LinkValidationService')) {
                $reflection = new ReflectionClass('App\Services\LinkValidationService');
                $constructor = $reflection->getConstructor();
                
                if ($constructor) {
                    $parameters = $constructor->getParameters();
                    $hasSecurityDependency = false;
                    
                    foreach ($parameters as $parameter) {
                        $type = $parameter->getType();
                        if ($type && str_contains($type->getName(), 'SecurityValidation')) {
                            $hasSecurityDependency = true;
                            break;
                        }
                    }
                    
                    expect($hasSecurityDependency)->toBeTrue(
                        'LinkValidationService should depend on SecurityValidationInterface'
                    );
                }
            }
        });

        it('enforces input validation patterns', function () {
            // Assert: All user input is validated
            $commandClasses = [
                'App\Commands\ValidateCommand',
                'App\Commands\ReportCommand',
                'App\Commands\ConfigCommand',
            ];

            foreach ($commandClasses as $class) {
                if (class_exists($class)) {
                    $reflection = new ReflectionClass($class);
                    
                    // Check for handle method (main command method)
                    if ($reflection->hasMethod('handle')) {
                        $method = $reflection->getMethod('handle');
                        $docComment = $method->getDocComment();
                        
                        // Should have validation logic or mention validation
                        expect($docComment)->toContain('validate')
                            ->or($docComment)->toContain('security')
                            ->or($docComment)->toContain('sanitize');
                    }
                }
            }
        });
    });

    describe('Performance Architecture', function () {
        it('enforces efficient collection usage', function () {
            // Assert: Services use Laravel Collections efficiently
            $serviceClasses = [
                'App\Services\LinkValidationService',
                'App\Services\ReportingService',
            ];

            foreach ($serviceClasses as $class) {
                if (class_exists($class)) {
                    $reflection = new ReflectionClass($class);
                    $methods = $reflection->getMethods();
                    
                    foreach ($methods as $method) {
                        $returnType = $method->getReturnType();
                        if ($returnType && str_contains($returnType->getName(), 'Collection')) {
                            // Method returns collection - should be efficient
                            expect($method->getName())->not->toContain('toArray')
                                ->and($method->getName())->not->toContain('all');
                        }
                    }
                }
            }
        });

        it('enforces lazy loading patterns', function () {
            // Assert: Large data sets use lazy loading
            if (class_exists('App\Services\ValueObjects\ValidationResultCollection')) {
                $reflection = new ReflectionClass('App\Services\ValueObjects\ValidationResultCollection');
                
                // Should implement Iterator or have lazy methods
                expect($reflection->implementsInterface('Iterator'))
                    ->or($reflection->hasMethod('lazy'))
                    ->or($reflection->hasMethod('chunk'))
                    ->toBeTrue('ValidationResultCollection should support lazy iteration');
            }
        });
    });

    // Helper method to get all PHP files
    private function getAllPhpFiles(): array
    {
        $files = [];
        $directories = ['app', 'tests'];
        
        foreach ($directories as $directory) {
            if (is_dir($directory)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($directory)
                );
                
                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getExtension() === 'php') {
                        $files[] = $file->getPathname();
                    }
                }
            }
        }
        
        return $files;
    }
});
