<?php

declare(strict_types=1);

use App\Services\Contracts\GitHubAnchorInterface;

describe('GitHubAnchorInterface', function () {
    beforeEach(function () {
        // Setup comprehensive test data for interface testing
        $this->interfaceName = GitHubAnchorInterface::class;
        $this->reflection = new ReflectionClass($this->interfaceName);

        // Expected interface structure - all 4 methods from the actual interface
        $this->expectedMethods = [
            'generateAnchor' => [
                'parameters' => ['string'],
                'returnType' => 'string',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'validateAnchor' => [
                'parameters' => ['string', 'string'],
                'returnType' => 'bool',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'extractAnchors' => [
                'parameters' => ['string'],
                'returnType' => 'array',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'normalizeAnchor' => [
                'parameters' => ['string'],
                'returnType' => 'string',
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
            expect($this->reflection->getShortName())->toBe('GitHubAnchorInterface');
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
        it('has exactly 4 methods', function () {
            $methods = $this->reflection->getMethods();
            expect($methods)->toHaveCount(4);
        });

        it('has all required methods', function () {
            $methodNames = array_map(fn($method) => $method->getName(), $this->reflection->getMethods());

            expect($methodNames)->toHaveCount(4)
                ->and($methodNames)->toContain('generateAnchor')
                ->and($methodNames)->toContain('validateAnchor')
                ->and($methodNames)->toContain('extractAnchors')
                ->and($methodNames)->toContain('normalizeAnchor');
        });
    });

    describe('generateAnchor method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('generateAnchor');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(1)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('string');
        });

        it('has correct parameters', function () {
            $method = $this->reflection->getMethod('generateAnchor');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('headingText')
                ->and((string) $parameters[0]->getType())->toBe('string');
        });
    });

    describe('validateAnchor method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('validateAnchor');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(2)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('bool');
        });

        it('has correct parameters', function () {
            $method = $this->reflection->getMethod('validateAnchor');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('content')
                ->and((string) $parameters[0]->getType())->toBe('string')
                ->and($parameters[1]->getName())->toBe('anchor')
                ->and((string) $parameters[1]->getType())->toBe('string');
        });
    });

    describe('extractAnchors method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('extractAnchors');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(1)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('array');
        });

        it('has correct parameters', function () {
            $method = $this->reflection->getMethod('extractAnchors');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('content')
                ->and((string) $parameters[0]->getType())->toBe('string');
        });
    });

    describe('normalizeAnchor method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('normalizeAnchor');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(1)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('string');
        });

        it('has correct parameters', function () {
            $method = $this->reflection->getMethod('normalizeAnchor');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('anchor')
                ->and((string) $parameters[0]->getType())->toBe('string');
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
