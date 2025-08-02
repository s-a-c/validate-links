<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;
use App\Services\ValueObjects\ValidationStatistics;

describe('LinkValidationInterface', function () {
    beforeEach(function () {
        // Setup comprehensive test data for interface testing
        $this->interfaceName = LinkValidationInterface::class;
        $this->reflection = new ReflectionClass($this->interfaceName);

        // Expected interface structure - all 12 methods from the actual interface
        $this->expectedMethods = [
            'validateLink' => [
                'parameters' => ['string', ValidationConfig::class],
                'returnType' => ValidationResult::class,
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'validateLinks' => [
                'parameters' => ['array', ValidationConfig::class],
                'returnType' => ValidationResultCollection::class,
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'validateFile' => [
                'parameters' => ['string', ValidationConfig::class],
                'returnType' => ValidationResultCollection::class,
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'extractLinks' => [
                'parameters' => ['string'],
                'returnType' => 'array',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'isValidUrl' => [
                'parameters' => ['string'],
                'returnType' => 'bool',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'normalizeUrl' => [
                'parameters' => ['string'],
                'returnType' => 'string',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'getSupportedScopes' => [
                'parameters' => [],
                'returnType' => 'array',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'supportsScope' => [
                'parameters' => [ValidationScope::class],
                'returnType' => 'bool',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'validateByScopes' => [
                'parameters' => ['array', 'array', ValidationConfig::class],
                'returnType' => ValidationResult::class,
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'getStatusStatistics' => [
                'parameters' => [],
                'returnType' => ValidationStatistics::class,
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'setTimeout' => [
                'parameters' => ['int'],
                'returnType' => 'void',
                'visibility' => 'public',
                'isAbstract' => true,
            ],
            'validateFiles' => [
                'parameters' => ['array', ValidationConfig::class],
                'returnType' => ValidationResultCollection::class,
                'visibility' => 'public',
                'isAbstract' => true,
            ],
        ];

        // Test data for method parameter validation
        $this->testConfig = new ValidationConfig(scopes: [ValidationScope::ALL]);
        $this->testUrls = ['https://example.com', 'https://test.com'];
        $this->testFiles = ['test.md', 'example.html'];
        $this->testContent = '# Test\n[Link](https://example.com)';
        $this->testFilePath = 'test.md';

        // Expected return type instances for validation
        $this->expectedValidationResult = ValidationResult::create(
            url: 'https://example.com',
            status: LinkStatus::VALID
        );

        $this->expectedValidationCollection = new ValidationResultCollection([$this->expectedValidationResult]);
        $this->expectedValidationStatistics = new ValidationStatistics(
            total: 0,
            valid: 0,
            broken: 0,
            timeouts: 0,
            securityRisks: 0
        );
    });

    afterEach(function () {
        // Cleanup test data
        unset($this->interfaceName);
        unset($this->reflection);
        unset($this->expectedMethods);
        unset($this->testConfig);
        unset($this->testUrls);
        unset($this->testFiles);
        unset($this->testContent);
        unset($this->testFilePath);
        unset($this->expectedValidationResult);
        unset($this->expectedValidationCollection);
        unset($this->expectedValidationStatistics);

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
            expect($this->reflection->getName())->toBe('App\Services\Contracts\LinkValidationInterface')
                ->and($this->reflection->getShortName())->toBe('LinkValidationInterface');
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
        it('has exactly 12 methods', function () {
            $methods = $this->reflection->getMethods();
            expect($methods)->toHaveCount(12);
        });

        it('has all required methods', function () {
            $methodNames = array_map(fn($method) => $method->getName(), $this->reflection->getMethods());

            expect($methodNames)->toHaveCount(12)
                ->and($methodNames)->toContain('validateLink')
                ->and($methodNames)->toContain('validateLinks')
                ->and($methodNames)->toContain('validateFile')
                ->and($methodNames)->toContain('extractLinks')
                ->and($methodNames)->toContain('isValidUrl')
                ->and($methodNames)->toContain('normalizeUrl')
                ->and($methodNames)->toContain('getSupportedScopes')
                ->and($methodNames)->toContain('supportsScope')
                ->and($methodNames)->toContain('validateByScopes')
                ->and($methodNames)->toContain('getStatusStatistics')
                ->and($methodNames)->toContain('setTimeout')
                ->and($methodNames)->toContain('validateFiles');
        });
    });

    describe('validateLink method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('validateLink');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(2)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe(ValidationResult::class);
        });

        it('has correct parameters', function () {
            $method = $this->reflection->getMethod('validateLink');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('url')
                ->and((string) $parameters[0]->getType())->toBe('string')
                ->and($parameters[1]->getName())->toBe('config')
                ->and((string) $parameters[1]->getType())->toBe(ValidationConfig::class);
        });
    });

    describe('validateLinks method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('validateLinks');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(2)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe(ValidationResultCollection::class);
        });

        it('has correct parameters', function () {
            $method = $this->reflection->getMethod('validateLinks');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('urls')
                ->and((string) $parameters[0]->getType())->toBe('array')
                ->and($parameters[1]->getName())->toBe('config')
                ->and((string) $parameters[1]->getType())->toBe(ValidationConfig::class);
        });
    });

    describe('validateFiles method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('validateFiles');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(2)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe(ValidationResultCollection::class);
        });

        it('has correct parameters', function () {
            $method = $this->reflection->getMethod('validateFiles');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('files')
                ->and((string) $parameters[0]->getType())->toBe('array')
                ->and($parameters[1]->getName())->toBe('config')
                ->and((string) $parameters[1]->getType())->toBe(ValidationConfig::class);
        });
    });

    describe('extractLinks method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('extractLinks');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(1)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('array');
        });

        it('has correct parameters', function () {
            $method = $this->reflection->getMethod('extractLinks');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('content')
                ->and((string) $parameters[0]->getType())->toBe('string');
        });
    });

    describe('isValidUrl method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('isValidUrl');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(1)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('bool');
        });

        it('has correct parameters', function () {
            $method = $this->reflection->getMethod('isValidUrl');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('url')
                ->and((string) $parameters[0]->getType())->toBe('string');
        });
    });

    describe('normalizeUrl method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('normalizeUrl');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(1)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('string');
        });

        it('has correct parameters', function () {
            $method = $this->reflection->getMethod('normalizeUrl');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('url')
                ->and((string) $parameters[0]->getType())->toBe('string');
        });
    });

    describe('getSupportedScopes method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('getSupportedScopes');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(0)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('array');
        });

        it('has no parameters', function () {
            $method = $this->reflection->getMethod('getSupportedScopes');
            expect($method->getParameters())->toBeEmpty();
        });
    });

    describe('supportsScope method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('supportsScope');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(1)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('bool');
        });

        it('has correct parameters', function () {
            $method = $this->reflection->getMethod('supportsScope');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('scope')
                ->and((string) $parameters[0]->getType())->toBe(ValidationScope::class);
        });
    });

    describe('validateByScopes method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('validateByScopes');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(3)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe(ValidationResult::class);
        });

        it('has correct parameters', function () {
            $method = $this->reflection->getMethod('validateByScopes');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('scopes')
                ->and((string) $parameters[0]->getType())->toBe('array')
                ->and($parameters[1]->getName())->toBe('targets')
                ->and((string) $parameters[1]->getType())->toBe('array')
                ->and($parameters[2]->getName())->toBe('config')
                ->and((string) $parameters[2]->getType())->toBe(ValidationConfig::class);
        });
    });

    describe('getStatusStatistics method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('getStatusStatistics');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(0)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe(ValidationStatistics::class);
        });

        it('has no parameters', function () {
            $method = $this->reflection->getMethod('getStatusStatistics');
            $parameters = $method->getParameters();

            expect($parameters)->toBeEmpty();
        });
    });

    describe('setTimeout method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('setTimeout');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(1)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe('void');
        });

        it('has correct parameters', function () {
            $method = $this->reflection->getMethod('setTimeout');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('seconds')
                ->and((string) $parameters[0]->getType())->toBe('int');
        });
    });



    describe('validateFile method', function () {
        it('has correct signature', function () {
            $method = $this->reflection->getMethod('validateFile');

            expect($method->isPublic())->toBeTrue()
                ->and($method->isAbstract())->toBeTrue()
                ->and($method->getNumberOfParameters())->toBe(2)
                ->and($method->hasReturnType())->toBeTrue()
                ->and((string) $method->getReturnType())->toBe(ValidationResultCollection::class);
        });

        it('has correct parameters', function () {
            $method = $this->reflection->getMethod('validateFile');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('filePath')
                ->and((string) $parameters[0]->getType())->toBe('string')
                ->and($parameters[1]->getName())->toBe('config')
                ->and((string) $parameters[1]->getType())->toBe(ValidationConfig::class);
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
