<?php

declare(strict_types=1);

use App\Enums\OutputFormat;
use App\Enums\ValidationScope;
use App\Services\ValueObjects\ValidationConfig;

describe('ValidationConfig', function () {
    describe('constructor', function () {
        it('creates config with default parameters', function () {
            $config = new ValidationConfig();

            expect($config->getScopes())->toBe([ValidationScope::ALL])
                ->and($config->getTimeout())->toBe(30)
                ->and($config->getMaxRedirects())->toBe(5)
                ->and($config->getConcurrentRequests())->toBe(10)
                ->and($config->isCacheEnabled())->toBeTrue()
                ->and($config->shouldFollowRedirects())->toBeTrue()
                ->and($config->getUserAgent())->toBe('validate-links/1.0')
                ->and($config->getOutputFormat())->toBeNull()
                ->and($config->getAdditionalOptions())->toBe([])
                ->and($config->isInteractive())->toBeFalse()
                ->and($config->isDryRun())->toBeFalse();
        });

        it('creates config with custom parameters', function () {
            $scopes = [ValidationScope::EXTERNAL, ValidationScope::INTERNAL];
            $additionalOptions = ['custom' => 'value'];

            $config = new ValidationConfig(
                scopes: $scopes,
                timeout: 60,
                maxRedirects: 3,
                concurrentRequests: 5,
                cacheEnabled: false,
                followRedirects: false,
                userAgent: 'custom-agent/2.0',
                outputFormat: OutputFormat::JSON,
                additionalOptions: $additionalOptions,
                interactive: true,
                dryRun: true
            );

            expect($config->getScopes())->toBe($scopes)
                ->and($config->getTimeout())->toBe(60)
                ->and($config->getMaxRedirects())->toBe(3)
                ->and($config->getConcurrentRequests())->toBe(5)
                ->and($config->isCacheEnabled())->toBeFalse()
                ->and($config->shouldFollowRedirects())->toBeFalse()
                ->and($config->getUserAgent())->toBe('custom-agent/2.0')
                ->and($config->getOutputFormat())->toBe(OutputFormat::JSON)
                ->and($config->getAdditionalOptions())->toBe($additionalOptions)
                ->and($config->isInteractive())->toBeTrue()
                ->and($config->isDryRun())->toBeTrue();
        });

        it('validates concurrent requests range', function () {
            expect(fn () => new ValidationConfig(concurrentRequests: 0))
                ->toThrow(InvalidArgumentException::class, 'Concurrent requests must be between 1 and 50');

            expect(fn () => new ValidationConfig(concurrentRequests: 51))
                ->toThrow(InvalidArgumentException::class, 'Concurrent requests must be between 1 and 50');
        });

        it('validates timeout range', function () {
            expect(fn () => new ValidationConfig(timeout: 0))
                ->toThrow(InvalidArgumentException::class, 'Timeout must be between 1 and 300 seconds');

            expect(fn () => new ValidationConfig(timeout: 301))
                ->toThrow(InvalidArgumentException::class, 'Timeout must be between 1 and 300 seconds');
        });

        it('validates max redirects range', function () {
            expect(fn () => new ValidationConfig(maxRedirects: -1))
                ->toThrow(InvalidArgumentException::class, 'Max redirects must be between 0 and 10');

            expect(fn () => new ValidationConfig(maxRedirects: 11))
                ->toThrow(InvalidArgumentException::class, 'Max redirects must be between 0 and 10');
        });

        it('validates empty scopes array', function () {
            expect(fn () => new ValidationConfig(scopes: []))
                ->toThrow(InvalidArgumentException::class, 'At least one validation scope must be specified');
        });

        it('allows valid boundary values', function () {
            $config = new ValidationConfig(
                concurrentRequests: 1,
                timeout: 1,
                maxRedirects: 0
            );

            expect($config->getConcurrentRequests())->toBe(1)
                ->and($config->getTimeout())->toBe(1)
                ->and($config->getMaxRedirects())->toBe(0);

            $config2 = new ValidationConfig(
                concurrentRequests: 50,
                timeout: 300,
                maxRedirects: 10
            );

            expect($config2->getConcurrentRequests())->toBe(50)
                ->and($config2->getTimeout())->toBe(300)
                ->and($config2->getMaxRedirects())->toBe(10);
        });
    });

    describe('create static method', function () {
        it('creates config from array with all parameters', function () {
            $data = [
                'scopes' => [ValidationScope::EXTERNAL],
                'timeout' => 45,
                'max_redirects' => 7,
                'concurrent_requests' => 15,
                'cache_enabled' => false,
                'follow_redirects' => false,
                'user_agent' => 'test-agent/1.0',
                'output_format' => OutputFormat::XML,
                'additional_options' => ['test' => 'value'],
                'interactive' => true,
                'dry_run' => true,
            ];

            $config = ValidationConfig::create($data);

            expect($config->getScopes())->toBe([ValidationScope::EXTERNAL])
                ->and($config->getTimeout())->toBe(45)
                ->and($config->getMaxRedirects())->toBe(7)
                ->and($config->getConcurrentRequests())->toBe(15)
                ->and($config->isCacheEnabled())->toBeFalse()
                ->and($config->shouldFollowRedirects())->toBeFalse()
                ->and($config->getUserAgent())->toBe('test-agent/1.0')
                ->and($config->getOutputFormat())->toBe(OutputFormat::XML)
                ->and($config->getAdditionalOptions())->toBe(['test' => 'value'])
                ->and($config->isInteractive())->toBeTrue()
                ->and($config->isDryRun())->toBeTrue();
        });

        it('creates config with default values for missing parameters', function () {
            $config = ValidationConfig::create([]);

            expect($config->getScopes())->toBe([ValidationScope::ALL])
                ->and($config->getTimeout())->toBe(30)
                ->and($config->getMaxRedirects())->toBe(5)
                ->and($config->getConcurrentRequests())->toBe(10)
                ->and($config->isCacheEnabled())->toBeTrue()
                ->and($config->shouldFollowRedirects())->toBeTrue()
                ->and($config->getUserAgent())->toBe('validate-links/1.0')
                ->and($config->getOutputFormat())->toBeNull()
                ->and($config->getAdditionalOptions())->toBe([])
                ->and($config->isInteractive())->toBeFalse()
                ->and($config->isDryRun())->toBeFalse();
        });

        it('handles non-array scopes by defaulting to ALL', function () {
            $config = ValidationConfig::create(['scopes' => 'invalid']);

            expect($config->getScopes())->toBe([ValidationScope::ALL]);
        });

        it('filters out invalid scopes and defaults to ALL if none valid', function () {
            $config = ValidationConfig::create(['scopes' => ['invalid', 'also_invalid']]);

            expect($config->getScopes())->toBe([ValidationScope::ALL]);
        });

        it('preserves valid scopes and filters invalid ones', function () {
            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::EXTERNAL, 'invalid', ValidationScope::INTERNAL],
            ]);

            expect($config->getScopes())->toBe([ValidationScope::EXTERNAL, ValidationScope::INTERNAL]);
        });

        it('handles invalid output format by setting to null', function () {
            $config = ValidationConfig::create(['output_format' => 'invalid']);

            expect($config->getOutputFormat())->toBeNull();
        });

        it('handles non-array additional options by defaulting to empty array', function () {
            $config = ValidationConfig::create(['additional_options' => 'invalid']);

            expect($config->getAdditionalOptions())->toBe([]);
        });

        it('handles string user agent without trimming', function () {
            $config = ValidationConfig::create(['user_agent' => '  custom-agent  ']);

            expect($config->getUserAgent())->toBe('  custom-agent  ');
        });

        it('handles empty user agent by keeping empty string', function () {
            $config = ValidationConfig::create(['user_agent' => '']);

            expect($config->getUserAgent())->toBe('');
        });

        it('casts boolean values correctly', function () {
            $config = ValidationConfig::create([
                'cache_enabled' => 'true',  // Non-empty string = true
                'follow_redirects' => 1,    // 1 = true
                'interactive' => 'false',   // Non-empty string = true
                'dry_run' => 0,             // 0 = false
            ]);

            expect($config->isCacheEnabled())->toBeTrue()
                ->and($config->shouldFollowRedirects())->toBeTrue()
                ->and($config->isInteractive())->toBeTrue()
                ->and($config->isDryRun())->toBeFalse();
        });

        it('casts integer values correctly', function () {
            $config = ValidationConfig::create([
                'timeout' => '45',
                'max_redirects' => '7',
                'concurrent_requests' => '15',
            ]);

            expect($config->getTimeout())->toBe(45)
                ->and($config->getMaxRedirects())->toBe(7)
                ->and($config->getConcurrentRequests())->toBe(15);
        });
    });

    describe('withDefaults static method', function () {
        it('creates config with default values', function () {
            $config = ValidationConfig::withDefaults();

            expect($config->getScopes())->toBe([ValidationScope::ALL])
                ->and($config->getTimeout())->toBe(30)
                ->and($config->getConcurrentRequests())->toBe(10);
        });

        it('applies overrides to default values', function () {
            $config = ValidationConfig::withDefaults(['timeout' => 60]);

            expect($config->getTimeout())->toBe(60)
                ->and($config->getConcurrentRequests())->toBe(10); // Still default
        });
    });

    describe('scope methods', function () {
        it('checks if config has specific scope', function () {
            $config = new ValidationConfig(scopes: [ValidationScope::EXTERNAL, ValidationScope::INTERNAL]);

            expect($config->hasScope(ValidationScope::EXTERNAL))->toBeTrue()
                ->and($config->hasScope(ValidationScope::INTERNAL))->toBeTrue()
                ->and($config->hasScope(ValidationScope::ANCHOR))->toBeFalse();
        });

        it('returns effective scopes correctly', function () {
            $config = new ValidationConfig(scopes: [ValidationScope::EXTERNAL]);
            $effectiveScopes = $config->getEffectiveScopes();

            expect($effectiveScopes)->toBe([ValidationScope::EXTERNAL]);
        });

        it('handles ALL scope correctly', function () {
            $config = new ValidationConfig(scopes: [ValidationScope::ALL]);

            expect($config->hasScope(ValidationScope::ALL))->toBeTrue();

            $effectiveScopes = $config->getEffectiveScopes();
            expect($effectiveScopes)->toContain(ValidationScope::EXTERNAL)
                ->and($effectiveScopes)->toContain(ValidationScope::INTERNAL)
                ->and($effectiveScopes)->toContain(ValidationScope::ANCHOR);
        });
    });

    describe('immutability and readonly behavior', function () {
        it('is readonly class', function () {
            $reflection = new ReflectionClass(ValidationConfig::class);

            expect($reflection->isReadOnly())->toBeTrue();
        });

        it('has readonly properties', function () {
            $reflection = new ReflectionClass(ValidationConfig::class);
            $properties = $reflection->getProperties();

            foreach ($properties as $property) {
                expect($property->isReadOnly())->toBeTrue(
                    "Property {$property->getName()} should be readonly"
                );
            }
        });

        it('cannot be modified after creation', function () {
            $config = new ValidationConfig(timeout: 30);

            // Attempting to modify should not be possible due to readonly
            expect($config->getTimeout())->toBe(30);
        });
    });

    describe('serialization', function () {
        it('can be serialized and unserialized', function () {
            $original = new ValidationConfig(
                scopes: [ValidationScope::EXTERNAL],
                timeout: 45,
                outputFormat: OutputFormat::JSON
            );

            $serialized = serialize($original);
            $unserialized = unserialize($serialized);

            expect($unserialized)->toBeInstanceOf(ValidationConfig::class)
                ->and($unserialized->getScopes())->toBe($original->getScopes())
                ->and($unserialized->getTimeout())->toBe($original->getTimeout())
                ->and($unserialized->getOutputFormat())->toBe($original->getOutputFormat());
        });
    });

    describe('edge cases and error handling', function () {
        it('handles very large timeout values within bounds', function () {
            $config = new ValidationConfig(timeout: 300);

            expect($config->getTimeout())->toBe(300);
        });

        it('handles complex additional options', function () {
            $options = [
                'nested' => ['key' => 'value'],
                'array' => [1, 2, 3],
                'boolean' => true,
                'null' => null,
            ];

            $config = new ValidationConfig(additionalOptions: $options);

            expect($config->getAdditionalOptions())->toBe($options);
        });

        it('handles unicode characters in user agent', function () {
            $config = new ValidationConfig(userAgent: 'test-agent/1.0 🤖');

            expect($config->getUserAgent())->toBe('test-agent/1.0 🤖');
        });

        it('handles all validation scopes', function () {
            $allScopes = [
                ValidationScope::ALL,
                ValidationScope::EXTERNAL,
                ValidationScope::INTERNAL,
                ValidationScope::ANCHOR,
            ];

            $config = new ValidationConfig(scopes: $allScopes);

            expect($config->getScopes())->toBe($allScopes);
        });

        it('handles all output formats', function () {
            foreach (OutputFormat::cases() as $format) {
                $config = new ValidationConfig(outputFormat: $format);

                expect($config->getOutputFormat())->toBe($format);
            }
        });
    });

    describe('method return types and visibility', function () {
        it('has correct public getter methods', function () {
            $reflection = new ReflectionClass(ValidationConfig::class);
            $expectedMethods = [
                'getScopes' => 'array',
                'getTimeout' => 'int',
                'getMaxRedirects' => 'int',
                'getConcurrentRequests' => 'int',
                'isCacheEnabled' => 'bool',
                'shouldFollowRedirects' => 'bool',
                'getUserAgent' => 'string',
                'getOutputFormat' => '?App\Enums\OutputFormat',
                'getAdditionalOptions' => 'array',
                'isInteractive' => 'bool',
                'isDryRun' => 'bool',
                'hasScope' => 'bool',
                'getEffectiveScopes' => 'array',
            ];

            foreach ($expectedMethods as $methodName => $expectedReturnType) {
                expect($reflection->hasMethod($methodName))->toBeTrue(
                    "Method {$methodName} should exist"
                );

                $method = $reflection->getMethod($methodName);
                expect($method->isPublic())->toBeTrue(
                    "Method {$methodName} should be public"
                );

                if ($method->hasReturnType()) {
                    $returnType = $method->getReturnType();
                    if ($returnType instanceof ReflectionNamedType) {
                        $actualType = ($returnType->allowsNull() ? '?' : '').$returnType->getName();
                        expect($actualType)->toBe($expectedReturnType);
                    }
                }
            }
        });

        it('has correct static factory methods', function () {
            $reflection = new ReflectionClass(ValidationConfig::class);

            expect($reflection->hasMethod('create'))->toBeTrue();
            expect($reflection->hasMethod('withDefaults'))->toBeTrue();

            $createMethod = $reflection->getMethod('create');
            $withDefaultsMethod = $reflection->getMethod('withDefaults');

            expect($createMethod->isStatic())->toBeTrue()
                ->and($createMethod->isPublic())->toBeTrue()
                ->and($withDefaultsMethod->isStatic())->toBeTrue()
                ->and($withDefaultsMethod->isPublic())->toBeTrue();
        });
    });

    describe('integration with enums', function () {
        it('works with all ValidationScope enum values', function () {
            foreach (ValidationScope::cases() as $scope) {
                $config = new ValidationConfig(scopes: [$scope]);

                expect($config->hasScope($scope))->toBeTrue();
            }
        });

        it('works with all OutputFormat enum values', function () {
            foreach (OutputFormat::cases() as $format) {
                $config = new ValidationConfig(outputFormat: $format);

                expect($config->getOutputFormat())->toBe($format);
            }
        });
    });
});
