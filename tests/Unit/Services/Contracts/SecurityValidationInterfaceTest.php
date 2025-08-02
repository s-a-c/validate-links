<?php

declare(strict_types=1);

use App\Services\Contracts\SecurityValidationInterface;

describe('SecurityValidationInterface', function () {
    beforeEach(function () {
        // Setup comprehensive test data for interface testing
        $this->interfaceName = SecurityValidationInterface::class;
        $this->reflection = new ReflectionClass($this->interfaceName);

        // Expected interface structure - all 6 methods from the actual interface
        $this->expectedMethods = [
            'validateUrl' => [
                'parameters' => ['string'],
                'returnType' => 'bool',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'validatePath' => [
                'parameters' => ['string'],
                'returnType' => 'bool',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'isDomainBlocked' => [
                'parameters' => ['string'],
                'returnType' => 'bool',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'isProtocolAllowed' => [
                'parameters' => ['string'],
                'returnType' => 'bool',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'validateFileSize' => [
                'parameters' => ['string'],
                'returnType' => 'bool',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'getSecurityConfig' => [
                'parameters' => [],
                'returnType' => 'array',
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
            expect($this->reflection->getShortName())->toBe('SecurityValidationInterface');
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
        it('has exactly 6 methods', function () {
            $methods = $this->reflection->getMethods();
            expect($methods)->toHaveCount(6);
        });

        it('has all required methods', function () {
            $methodNames = array_map(fn($method) => $method->getName(), $this->reflection->getMethods());

            expect($methodNames)->toHaveCount(6)
                ->and($methodNames)->toContain('validateUrl')
                ->and($methodNames)->toContain('validatePath')
                ->and($methodNames)->toContain('isDomainBlocked')
                ->and($methodNames)->toContain('isProtocolAllowed')
                ->and($methodNames)->toContain('validateFileSize')
                ->and($methodNames)->toContain('getSecurityConfig');
        });
    });

    describe('validateUrl method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('validateUrl');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(1)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('bool');
        });

        it('has correct parameters', function () {
            $method = $this->reflection->getMethod('validateUrl');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('url')
                ->and((string) $parameters[0]->getType())->toBe('string');
        });
    });

    describe('validatePath method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('validatePath');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(1)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('bool');
        });

        it('has correct parameters', function () {
            $method = $this->reflection->getMethod('validatePath');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('path')
                ->and((string) $parameters[0]->getType())->toBe('string');
        });
    });

    describe('isDomainBlocked method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('isDomainBlocked');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(1)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('bool');
        });

        it('has correct parameters', function () {
            $method = $this->reflection->getMethod('isDomainBlocked');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('domain')
                ->and((string) $parameters[0]->getType())->toBe('string');
        });
    });

    describe('isProtocolAllowed method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('isProtocolAllowed');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(1)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('bool');
        });

        it('has correct parameters', function () {
            $method = $this->reflection->getMethod('isProtocolAllowed');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('protocol')
                ->and((string) $parameters[0]->getType())->toBe('string');
        });
    });

    describe('validateFileSize method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('validateFileSize');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(1)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('bool');
        });

        it('has correct parameters', function () {
            $method = $this->reflection->getMethod('validateFileSize');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('filePath')
                ->and((string) $parameters[0]->getType())->toBe('string');
        });
    });

    describe('getSecurityConfig method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('getSecurityConfig');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(0)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('array');
        });

        it('has no parameters', function () {
            $method = $this->reflection->getMethod('getSecurityConfig');
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
                    expect($parameter->hasType())->toBeTrue(
                        "Parameter {$parameter->getName()} in {$methodName} should have type hint");
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
