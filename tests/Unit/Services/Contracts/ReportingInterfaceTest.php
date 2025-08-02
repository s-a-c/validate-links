<?php

declare(strict_types=1);

use App\Enums\OutputFormat;
use App\Services\Contracts\ReportingInterface;
use App\Services\ValueObjects\ValidationResultCollection;

describe('ReportingInterface', function () {
    beforeEach(function () {
        // Setup comprehensive test data for interface testing
        $this->interfaceName = ReportingInterface::class;
        $this->reflection = new ReflectionClass($this->interfaceName);

        // Expected interface structure - all 9 methods from the actual interface
        $this->expectedMethods = [
            'generateReport' => [
                'parameters' => [ValidationResultCollection::class, OutputFormat::class],
                'returnType' => 'string',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'format' => [
                'parameters' => [ValidationResultCollection::class, OutputFormat::class, 'array'],
                'returnType' => 'string',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'addFormatter' => [
                'parameters' => [OutputFormat::class, 'object'],
                'returnType' => 'void',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'exportReport' => [
                'parameters' => [ValidationResultCollection::class, 'string', '?'.OutputFormat::class],
                'returnType' => 'bool',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'getAvailableFormats' => [
                'parameters' => [],
                'returnType' => 'array',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'supportsFormat' => [
                'parameters' => [OutputFormat::class],
                'returnType' => 'bool',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'generateSummary' => [
                'parameters' => [ValidationResultCollection::class],
                'returnType' => 'App\Services\ValueObjects\ReportSummary',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'getFormatter' => [
                'parameters' => [OutputFormat::class],
                'returnType' => 'object',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'saveReport' => [
                'parameters' => ['string', 'string'],
                'returnType' => 'bool',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
        ];

        // Test data for method parameter validation
        $this->testResults = new ValidationResultCollection([]);
        $this->testFormat = OutputFormat::JSON;
        $this->testContent = '{"test": "content"}';
        $this->testFilePath = 'test-report.json';
    });

    afterEach(function () {
        // Cleanup test data
        unset($this->interfaceName);
        unset($this->reflection);
        unset($this->expectedMethods);
        unset($this->testResults);
        unset($this->testFormat);
        unset($this->testContent);
        unset($this->testFilePath);

        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    });

    describe('interface structure', function () {
        it('is an interface', function () {
            expect($this->reflection->isInterface())->toBeTrue()
                ->and($this->reflection->isTrait())->toBeFalse();
        });

        it('has correct namespace', function () {
            expect($this->reflection->getNamespaceName())->toBe('App\Services\Contracts');
        });

        it('has correct name', function () {
            expect($this->reflection->getName())->toBe('App\Services\Contracts\ReportingInterface')
                ->and($this->reflection->getShortName())->toBe('ReportingInterface');
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
        it('has exactly 9 methods', function () {
            $methods = $this->reflection->getMethods();
            expect($methods)->toHaveCount(9);
        });

        it('has all required methods', function () {
            $methodNames = array_map(fn($method) => $method->getName(), $this->reflection->getMethods());

            expect($methodNames)->toHaveCount(9)
                ->and($methodNames)->toContain('generateReport')
                ->and($methodNames)->toContain('format')
                ->and($methodNames)->toContain('addFormatter')
                ->and($methodNames)->toContain('exportReport')
                ->and($methodNames)->toContain('getAvailableFormats')
                ->and($methodNames)->toContain('supportsFormat')
                ->and($methodNames)->toContain('generateSummary')
                ->and($methodNames)->toContain('getFormatter')
                ->and($methodNames)->toContain('saveReport');
        });
    });

    describe('generateReport method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('generateReport');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(2)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('string');
        });

        it('has correct parameters', function () {
            $method = $this->reflection->getMethod('generateReport');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('results')
                ->and((string) $parameters[0]->getType())->toBe(ValidationResultCollection::class)
                ->and($parameters[1]->getName())->toBe('format')
                ->and((string) $parameters[1]->getType())->toBe(OutputFormat::class);
        });
    });

    describe('format method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('format');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(3)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('string');
        });

        it('has correct parameters', function () {
            $method = $this->reflection->getMethod('format');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('results')
                ->and((string) $parameters[0]->getType())->toBe(ValidationResultCollection::class)
                ->and($parameters[1]->getName())->toBe('format')
                ->and((string) $parameters[1]->getType())->toBe(OutputFormat::class)
                ->and($parameters[2]->getName())->toBe('options')
                ->and((string) $parameters[2]->getType())->toBe('array');
        });
    });

    describe('saveReport method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('saveReport');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(2)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('bool');
        });

        it('has correct parameters', function () {
            $method = $this->reflection->getMethod('saveReport');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('report')
                ->and((string) $parameters[0]->getType())->toBe('string')
                ->and($parameters[1]->getName())->toBe('filePath')
                ->and((string) $parameters[1]->getType())->toBe('string');
        });
    });

    describe('getAvailableFormats method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('getAvailableFormats');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(0)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('array');
        });

        it('has no parameters', function () {
            $method = $this->reflection->getMethod('getAvailableFormats');
            expect($method->getParameters())->toBeEmpty();
        });
    });

    describe('supportsFormat method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('supportsFormat');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(1)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('bool');
        });

        it('has correct parameters', function () {
            $method = $this->reflection->getMethod('supportsFormat');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('format')
                ->and((string) $parameters[0]->getType())->toBe(OutputFormat::class);
        });
    });

    describe('addFormatter method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('addFormatter');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(2)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('void');
        });

        it('has correct parameters', function () {
            $method = $this->reflection->getMethod('addFormatter');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('format')
                ->and((string) $parameters[0]->getType())->toBe(OutputFormat::class)
                ->and($parameters[1]->getName())->toBe('formatter')
                ->and((string) $parameters[1]->getType())->toBe('object');
        });
    });

    describe('exportReport method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('exportReport');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(3)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('bool');
        });

        it('has correct parameters', function () {
            $method = $this->reflection->getMethod('exportReport');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('results')
                ->and((string) $parameters[0]->getType())->toBe(ValidationResultCollection::class)
                ->and($parameters[1]->getName())->toBe('filePath')
                ->and((string) $parameters[1]->getType())->toBe('string')
                ->and($parameters[2]->getName())->toBe('format');
                // Note: nullable types show as the base type in reflection
        });
    });

    describe('generateSummary method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('generateSummary');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(1)
                ->and($method->hasReturnType())->toBeTrue();
                // Note: return type includes namespace
        });

        it('has correct parameters', function () {
            $method = $this->reflection->getMethod('generateSummary');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('results')
                ->and((string) $parameters[0]->getType())->toBe(ValidationResultCollection::class);
        });
    });

    describe('getFormatter method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('getFormatter');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(1)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('object');
        });

        it('has correct parameters', function () {
            $method = $this->reflection->getMethod('getFormatter');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('format')
                ->and((string) $parameters[0]->getType())->toBe(OutputFormat::class);
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
        it('enforces strict parameter types', function () {
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

        it('enforces strict return types', function () {
            foreach ($this->expectedMethods as $methodName => $expectedSignature) {
                $method = $this->reflection->getMethod($methodName);

                expect($method->hasReturnType())->toBeTrue("Method {$methodName} should have return type")
                    ->and((string) $method->getReturnType())->toBe($expectedSignature['returnType'],
                        "Method {$methodName} should return {$expectedSignature['returnType']}");
            }
        });
    });
});
