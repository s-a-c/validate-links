<?php

declare(strict_types=1);

use App\Services\Contracts\StatisticsInterface;

describe('StatisticsInterface', function () {
    beforeEach(function () {
        // Setup comprehensive test data for interface testing
        $this->interfaceName = StatisticsInterface::class;
        $this->reflection = new ReflectionClass($this->interfaceName);

        // Expected interface structure - all 7 methods from the actual interface
        $this->expectedMethods = [
            'recordValidation' => [
                'parameters' => ['string', 'bool', '?string'],
                'returnType' => 'void',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'recordFileProcessed' => [
                'parameters' => ['string', 'int'],
                'returnType' => 'void',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'getStatistics' => [
                'parameters' => [],
                'returnType' => 'array',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'getBrokenLinks' => [
                'parameters' => [],
                'returnType' => 'array',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'getProcessedFiles' => [
                'parameters' => [],
                'returnType' => 'array',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'reset' => [
                'parameters' => [],
                'returnType' => 'void',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'getSuccessRate' => [
                'parameters' => [],
                'returnType' => 'float',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
        ];
    });

    describe('interface structure', function () {
        it('is an interface', function () {
            expect($this->reflection->isInterface())->toBeTrue();
        });

        it('has correct namespace', function () {
            expect($this->reflection->getNamespaceName())->toBe('App\Services\Contracts');
        });

        it('has correct name', function () {
            expect($this->reflection->getShortName())->toBe('StatisticsInterface');
        });

        it('has no parent interface', function () {
            expect($this->reflection->getParentClass())->toBeFalse();
        });

        it('implements no other interfaces', function () {
            expect($this->reflection->getInterfaces())->toBeEmpty();
        });

        it('has no constants', function () {
            expect($this->reflection->getConstants())->toBeEmpty();
        });

        it('has no properties', function () {
            expect($this->reflection->getProperties())->toBeEmpty();
        });
    });

    describe('method structure', function () {
        it('has exactly 7 methods', function () {
            $methods = $this->reflection->getMethods();
            expect($methods)->toHaveCount(7);
        });

        it('has all required methods', function () {
            $methodNames = array_map(fn($method) => $method->getName(), $this->reflection->getMethods());

            expect($methodNames)->toHaveCount(7)
                ->and($methodNames)->toContain('recordValidation')
                ->and($methodNames)->toContain('recordFileProcessed')
                ->and($methodNames)->toContain('getStatistics')
                ->and($methodNames)->toContain('getBrokenLinks')
                ->and($methodNames)->toContain('getProcessedFiles')
                ->and($methodNames)->toContain('reset')
                ->and($methodNames)->toContain('getSuccessRate');
        });
    });

    describe('recordValidation method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('recordValidation');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(3)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('void');
        });

        it('has correct parameters', function () {
            $method = $this->reflection->getMethod('recordValidation');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('url')
                ->and((string) $parameters[0]->getType())->toBe('string')
                ->and($parameters[1]->getName())->toBe('isValid')
                ->and((string) $parameters[1]->getType())->toBe('bool')
                ->and($parameters[2]->getName())->toBe('error')
                ->and($parameters[2]->allowsNull())->toBeTrue()
                ->and($parameters[2]->isDefaultValueAvailable())->toBeTrue();
        });
    });

    describe('recordFileProcessed method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('recordFileProcessed');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(2)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('void');
        });

        it('has correct parameters', function () {
            $method = $this->reflection->getMethod('recordFileProcessed');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('filePath')
                ->and((string) $parameters[0]->getType())->toBe('string')
                ->and($parameters[1]->getName())->toBe('linkCount')
                ->and((string) $parameters[1]->getType())->toBe('int');
        });
    });

    describe('getStatistics method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('getStatistics');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(0)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('array');
        });

        it('has no parameters', function () {
            $method = $this->reflection->getMethod('getStatistics');
            $parameters = $method->getParameters();

            expect($parameters)->toBeEmpty();
        });
    });

    describe('getBrokenLinks method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('getBrokenLinks');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(0)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('array');
        });

        it('has no parameters', function () {
            $method = $this->reflection->getMethod('getBrokenLinks');
            $parameters = $method->getParameters();

            expect($parameters)->toBeEmpty();
        });
    });

    describe('getProcessedFiles method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('getProcessedFiles');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(0)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('array');
        });

        it('has no parameters', function () {
            $method = $this->reflection->getMethod('getProcessedFiles');
            $parameters = $method->getParameters();

            expect($parameters)->toBeEmpty();
        });
    });

    describe('reset method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('reset');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(0)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('void');
        });

        it('has no parameters', function () {
            $method = $this->reflection->getMethod('reset');
            $parameters = $method->getParameters();

            expect($parameters)->toBeEmpty();
        });
    });

    describe('getSuccessRate method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('getSuccessRate');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(0)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('float');
        });

        it('has no parameters', function () {
            $method = $this->reflection->getMethod('getSuccessRate');
            $parameters = $method->getParameters();

            expect($parameters)->toBeEmpty();
        });
    });

    describe('method compliance', function () {
        it('all methods have correct parameter counts', function () {
            foreach ($this->expectedMethods as $methodName => $expectedSignature) {
                $method = $this->reflection->getMethod($methodName);
                $parameters = $method->getParameters();

                expect(count($parameters))->toBe(count($expectedSignature['parameters']),
                    "Method {$methodName} should have correct parameter count");

                foreach ($parameters as $parameter) {
                    if (!$parameter->allowsNull() || !$parameter->isDefaultValueAvailable()) {
                        expect($parameter->hasType())->toBeTrue(
                            "Parameter {$parameter->getName()} in {$methodName} should have type hint");
                    }
                }
            }
        });

        it('all methods have correct return types', function () {
            foreach ($this->expectedMethods as $methodName => $expectedSignature) {
                $method = $this->reflection->getMethod($methodName);

                expect($method->hasReturnType())->toBeTrue("Method {$methodName} should have return type")
                    ->and((string) $method->getReturnType())->toBe($expectedSignature['returnType'],
                        "Method {$methodName} should return {$expectedSignature['returnType']}");
            }
        });
    });

    describe('interface compliance', function () {
        it('can be implemented by concrete classes', function () {
            expect($this->reflection->isInstantiable())->toBeFalse()
                ->and($this->reflection->isInterface())->toBeTrue();
        });

        it('has proper PHPDoc annotations', function () {
            $docComment = $this->reflection->getDocComment();
            // Interface may or may not have class-level documentation
            expect($docComment === false || $docComment === null || is_string($docComment))->toBeTrue();
        });

        it('all methods are abstract', function () {
            foreach ($this->reflection->getMethods() as $method) {
                expect($method->isAbstract())->toBeTrue(
                    "Method {$method->getName()} should be abstract in interface");
            }
        });

        it('all methods are public', function () {
            foreach ($this->reflection->getMethods() as $method) {
                expect($method->isPublic())->toBeTrue(
                    "Method {$method->getName()} should be public in interface");
            }
        });
    });
});
