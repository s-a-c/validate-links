<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Services\ValueObjects\ValidationResult;

describe('ValidationResult', function () {
    describe('constructor', function () {
        it('creates result with minimal parameters', function () {
            $result = new ValidationResult(
                url: 'https://example.com',
                status: LinkStatus::VALID,
                scope: ValidationScope::EXTERNAL
            );

            expect($result->getUrl())->toBe('https://example.com')
                ->and($result->getStatus())->toBe(LinkStatus::VALID)
                ->and($result->getScope())->toBe(ValidationScope::EXTERNAL)
                ->and($result->getError())->toBeNull()
                ->and($result->getHttpStatusCode())->toBeNull()
                ->and($result->getRedirectUrl())->toBeNull()
                ->and($result->getResponseTime())->toBe(0.0)
                ->and($result->getMetadata())->toBe([])
                ->and($result->getFilePath())->toBeNull();
        });

        it('creates result with all parameters', function () {
            $metadata = ['custom' => 'data', 'test' => 123];

            $result = new ValidationResult(
                url: 'https://example.com',
                status: LinkStatus::BROKEN,
                scope: ValidationScope::EXTERNAL,
                error: 'Connection failed',
                httpStatusCode: 404,
                redirectUrl: 'https://redirect.com',
                responseTime: 1.5,
                metadata: $metadata,
                filePath: '/path/to/file.md'
            );

            expect($result->getUrl())->toBe('https://example.com')
                ->and($result->getStatus())->toBe(LinkStatus::BROKEN)
                ->and($result->getScope())->toBe(ValidationScope::EXTERNAL)
                ->and($result->getError())->toBe('Connection failed')
                ->and($result->getHttpStatusCode())->toBe(404)
                ->and($result->getRedirectUrl())->toBe('https://redirect.com')
                ->and($result->getResponseTime())->toBe(1.5)
                ->and($result->getMetadata())->toBe($metadata)
                ->and($result->getFilePath())->toBe('/path/to/file.md');
        });

        it('initializes public properties for formatter compatibility', function () {
            $result = new ValidationResult(
                url: 'https://example.com',
                status: LinkStatus::VALID,
                scope: ValidationScope::EXTERNAL
            );

            expect($result->broken)->toBeArray()
                ->and($result->files)->toBeArray()
                ->and($result->links)->toBeArray()
                ->and($result->duration)->toBeFloat();
        });
    });

    describe('success static factory method', function () {
        it('creates successful validation result', function () {
            $result = ValidationResult::success(
                url: 'https://example.com',
                scope: ValidationScope::EXTERNAL,
                httpStatusCode: 200,
                responseTime: 0.5,
                metadata: ['test' => 'data'],
                filePath: '/test.md'
            );

            expect($result->getUrl())->toBe('https://example.com')
                ->and($result->getStatus())->toBe(LinkStatus::VALID)
                ->and($result->getScope())->toBe(ValidationScope::EXTERNAL)
                ->and($result->getHttpStatusCode())->toBe(200)
                ->and($result->getResponseTime())->toBe(0.5)
                ->and($result->getMetadata())->toBe(['test' => 'data'])
                ->and($result->getFilePath())->toBe('/test.md')
                ->and($result->getError())->toBeNull();
        });

        it('creates successful result with minimal parameters', function () {
            $result = ValidationResult::success(
                url: 'https://example.com',
                scope: ValidationScope::INTERNAL
            );

            expect($result->getStatus())->toBe(LinkStatus::VALID)
                ->and($result->getUrl())->toBe('https://example.com')
                ->and($result->getScope())->toBe(ValidationScope::INTERNAL)
                ->and($result->getHttpStatusCode())->toBeNull()
                ->and($result->getResponseTime())->toBe(0.0)
                ->and($result->getMetadata())->toBe([])
                ->and($result->getFilePath())->toBeNull();
        });
    });

    describe('failure static factory method', function () {
        it('creates failed validation result', function () {
            $result = ValidationResult::failure(
                url: 'https://broken.com',
                scope: ValidationScope::EXTERNAL,
                status: LinkStatus::BROKEN,
                error: 'HTTP 404 Not Found',
                httpStatusCode: 404,
                redirectUrl: null,
                responseTime: 2.0,
                metadata: ['error_type' => 'http_error'],
                filePath: '/test.md'
            );

            expect($result->getUrl())->toBe('https://broken.com')
                ->and($result->getStatus())->toBe(LinkStatus::BROKEN)
                ->and($result->getScope())->toBe(ValidationScope::EXTERNAL)
                ->and($result->getError())->toBe('HTTP 404 Not Found')
                ->and($result->getHttpStatusCode())->toBe(404)
                ->and($result->getRedirectUrl())->toBeNull()
                ->and($result->getResponseTime())->toBe(2.0)
                ->and($result->getMetadata())->toBe(['error_type' => 'http_error'])
                ->and($result->getFilePath())->toBe('/test.md');
        });

        it('creates failed result with minimal parameters', function () {
            $result = ValidationResult::failure(
                url: 'https://broken.com',
                scope: ValidationScope::EXTERNAL,
                status: LinkStatus::TIMEOUT
            );

            expect($result->getStatus())->toBe(LinkStatus::TIMEOUT)
                ->and($result->getUrl())->toBe('https://broken.com')
                ->and($result->getScope())->toBe(ValidationScope::EXTERNAL)
                ->and($result->getError())->toBeNull()
                ->and($result->getHttpStatusCode())->toBeNull()
                ->and($result->getResponseTime())->toBe(0.0);
        });
    });

    describe('aggregate static factory method', function () {
        it('creates aggregate result from multiple results', function () {
            $results = [
                ValidationResult::success('https://example.com', ValidationScope::EXTERNAL),
                ValidationResult::failure('https://broken.com', ValidationScope::EXTERNAL, LinkStatus::BROKEN),
            ];

            $aggregate = ValidationResult::aggregate($results, ['message' => 'Aggregate validation']);

            expect($aggregate->getUrl())->toBe('aggregate-2-results')
                ->and($aggregate->getStatus())->toBe(LinkStatus::BROKEN)
                ->and($aggregate->getScope())->toBe(ValidationScope::ALL)
                ->and($aggregate->getError())->toBeNull()
                ->and($aggregate->getMetadata())->toHaveKey('total_results')
                ->and($aggregate->getMetadata()['total_results'])->toBe(2)
                ->and($aggregate->getMetadata()['summary']['message'])->toBe('Aggregate validation');
        });

        it('creates aggregate with broken status when any result is broken', function () {
            $results = [
                ValidationResult::success('https://example.com', ValidationScope::EXTERNAL),
                ValidationResult::failure('https://broken.com', ValidationScope::EXTERNAL, LinkStatus::BROKEN),
            ];

            $aggregate = ValidationResult::aggregate($results);

            expect($aggregate->getStatus())->toBe(LinkStatus::BROKEN);
        });

        it('creates aggregate with valid status when all results are valid', function () {
            $results = [
                ValidationResult::success('https://example1.com', ValidationScope::EXTERNAL),
                ValidationResult::success('https://example2.com', ValidationScope::EXTERNAL),
            ];

            $aggregate = ValidationResult::aggregate($results);

            expect($aggregate->getStatus())->toBe(LinkStatus::VALID);
        });

        it('handles empty results array', function () {
            $aggregate = ValidationResult::aggregate([]);

            expect($aggregate->getStatus())->toBe(LinkStatus::VALID)
                ->and($aggregate->getMetadata()['total_results'])->toBe(0);
        });
    });

    describe('create static factory method', function () {
        it('creates result with basic parameters', function () {
            $result = ValidationResult::create(
                url: 'https://example.com',
                status: LinkStatus::VALID,
                message: 'Link is valid',
                scope: ValidationScope::EXTERNAL,
                httpStatusCode: 200,
                responseTime: 1.0,
                metadata: ['test' => 'data']
            );

            expect($result->getUrl())->toBe('https://example.com')
                ->and($result->getStatus())->toBe(LinkStatus::VALID)
                ->and($result->getError())->toBe('Link is valid')
                ->and($result->getScope())->toBe(ValidationScope::EXTERNAL)
                ->and($result->getHttpStatusCode())->toBe(200)
                ->and($result->getResponseTime())->toBe(1.0)
                ->and($result->getMetadata())->toBe(['test' => 'data']);
        });

        it('creates result with default scope', function () {
            $result = ValidationResult::create(
                url: 'https://example.com',
                status: LinkStatus::VALID
            );

            expect($result->getScope())->toBe(ValidationScope::ALL);
        });

        it('handles empty message by setting error to null', function () {
            $result = ValidationResult::create(
                url: 'https://example.com',
                status: LinkStatus::VALID,
                message: ''
            );

            expect($result->getError())->toBeNull();
        });
    });

    describe('fromArray static factory method', function () {
        it('creates result from array data with all fields', function () {
            $data = [
                'url' => 'https://example.com',
                'status' => LinkStatus::VALID->value,
                'scope' => ValidationScope::EXTERNAL->value,
                'error' => 'Test error',
                'http_status_code' => 200,
                'redirect_url' => 'https://redirect.com',
                'response_time' => 1.5,
                'metadata' => ['test' => 'data'],
            ];

            $result = ValidationResult::fromArray($data);

            expect($result->getUrl())->toBe('https://example.com')
                ->and($result->getStatus())->toBe(LinkStatus::VALID)
                ->and($result->getScope())->toBe(ValidationScope::EXTERNAL)
                ->and($result->getError())->toBe('Test error')
                ->and($result->getHttpStatusCode())->toBe(200)
                ->and($result->getRedirectUrl())->toBe('https://redirect.com')
                ->and($result->getResponseTime())->toBe(1.5)
                ->and($result->getMetadata())->toBe(['test' => 'data']);
        });

        it('creates result from array data with minimal fields', function () {
            $data = [
                'url' => 'https://example.com',
            ];

            $result = ValidationResult::fromArray($data);

            expect($result->getUrl())->toBe('https://example.com')
                ->and($result->getStatus())->toBe(LinkStatus::BROKEN)
                ->and($result->getScope())->toBe(ValidationScope::ALL);
        });

        it('handles invalid enum values by using defaults', function () {
            $data = [
                'url' => 'https://example.com',
                'status' => null, // This will cause it to use BROKEN default
                'scope' => null,  // This will cause it to use ALL default
            ];

            $result = ValidationResult::fromArray($data);

            expect($result->getStatus())->toBe(LinkStatus::BROKEN)
                ->and($result->getScope())->toBe(ValidationScope::ALL);
        });

        it('handles non-array metadata by defaulting to empty array', function () {
            $data = [
                'url' => 'https://example.com',
                'metadata' => 'invalid',
            ];

            $result = ValidationResult::fromArray($data);

            expect($result->getMetadata())->toBe([]);
        });
    });

    describe('status checking methods', function () {
        it('correctly identifies valid results', function () {
            $result = ValidationResult::success('https://example.com', ValidationScope::EXTERNAL);

            expect($result->isValid())->toBeTrue()
                ->and($result->isBroken())->toBeFalse()
                ->and($result->isTemporary())->toBeFalse()
                ->and($result->isSecurityIssue())->toBeFalse();
        });

        it('correctly identifies broken results', function () {
            $result = ValidationResult::failure(
                'https://broken.com',
                ValidationScope::EXTERNAL,
                LinkStatus::BROKEN
            );

            expect($result->isValid())->toBeFalse()
                ->and($result->isBroken())->toBeTrue()
                ->and($result->isTemporary())->toBeFalse()
                ->and($result->isSecurityIssue())->toBeFalse();
        });

        it('correctly identifies timeout results', function () {
            $result = ValidationResult::failure(
                'https://timeout.com',
                ValidationScope::EXTERNAL,
                LinkStatus::TIMEOUT
            );

            expect($result->isValid())->toBeFalse()
                ->and($result->isBroken())->toBeTrue()
                ->and($result->isTemporary())->toBeTrue()
                ->and($result->isSecurityIssue())->toBeFalse();
        });

        it('correctly identifies security issues', function () {
            $result = ValidationResult::failure(
                'https://malicious.com',
                ValidationScope::EXTERNAL,
                LinkStatus::SECURITY_VIOLATION
            );

            expect($result->isValid())->toBeFalse()
                ->and($result->isBroken())->toBeTrue()
                ->and($result->isTemporary())->toBeFalse()
                ->and($result->isSecurityIssue())->toBeTrue();
        });

        it('correctly identifies redirect loop results', function () {
            $result = new ValidationResult(
                url: 'https://example.com',
                status: LinkStatus::REDIRECT_LOOP,
                scope: ValidationScope::EXTERNAL,
                redirectUrl: 'https://redirect.com'
            );

            expect($result->isValid())->toBeFalse()
                ->and($result->isBroken())->toBeTrue()
                ->and($result->isTemporary())->toBeFalse()
                ->and($result->isSecurityIssue())->toBeFalse();
        });
    });

    describe('string representation', function () {
        it('implements Stringable interface', function () {
            $result = ValidationResult::success('https://example.com', ValidationScope::EXTERNAL);

            expect($result)->toBeInstanceOf(Stringable::class);
        });

        it('returns formatted string representation', function () {
            $result = ValidationResult::success('https://example.com', ValidationScope::EXTERNAL);
            $string = (string) $result;

            expect($string)->toBeString()
                ->and($string)->toContain('https://example.com')
                ->and($string)->toContain('valid');
        });

        it('includes error in string representation when present', function () {
            $result = ValidationResult::failure(
                'https://broken.com',
                ValidationScope::EXTERNAL,
                LinkStatus::BROKEN,
                'Connection failed'
            );
            $string = (string) $result;

            expect($string)->toContain('https://broken.com')
                ->and($string)->toContain('broken');
        });
    });

    describe('immutability and readonly behavior', function () {
        it('is readonly class', function () {
            $reflection = new ReflectionClass(ValidationResult::class);

            expect($reflection->isReadOnly())->toBeTrue();
        });

        it('has readonly properties', function () {
            $reflection = new ReflectionClass(ValidationResult::class);
            $properties = $reflection->getProperties(ReflectionProperty::IS_PRIVATE);

            foreach ($properties as $property) {
                expect($property->isReadOnly())->toBeTrue(
                    "Property {$property->getName()} should be readonly"
                );
            }
        });
    });

    describe('serialization', function () {
        it('can be serialized and unserialized', function () {
            $original = ValidationResult::success(
                'https://example.com',
                ValidationScope::EXTERNAL,
                200,
                1.5,
                ['test' => 'data']
            );

            $serialized = serialize($original);
            $unserialized = unserialize($serialized);

            expect($unserialized)->toBeInstanceOf(ValidationResult::class)
                ->and($unserialized->getUrl())->toBe($original->getUrl())
                ->and($unserialized->getStatus())->toBe($original->getStatus())
                ->and($unserialized->getScope())->toBe($original->getScope())
                ->and($unserialized->getMetadata())->toBe($original->getMetadata());
        });
    });

    describe('edge cases and error handling', function () {
        it('handles very long URLs', function () {
            $longUrl = 'https://example.com/'.str_repeat('a', 2000);
            $result = ValidationResult::success($longUrl, ValidationScope::EXTERNAL);

            expect($result->getUrl())->toBe($longUrl);
        });

        it('handles unicode characters in URLs', function () {
            $unicodeUrl = 'https://example.com/测试';
            $result = ValidationResult::success($unicodeUrl, ValidationScope::EXTERNAL);

            expect($result->getUrl())->toBe($unicodeUrl);
        });

        it('handles very long error messages', function () {
            $longError = str_repeat('Error message ', 100);
            $result = ValidationResult::failure(
                'https://example.com',
                ValidationScope::EXTERNAL,
                LinkStatus::BROKEN,
                $longError
            );

            expect($result->getError())->toBe($longError);
        });

        it('handles negative response times', function () {
            $result = new ValidationResult(
                url: 'https://example.com',
                status: LinkStatus::VALID,
                scope: ValidationScope::EXTERNAL,
                responseTime: -1.0
            );

            expect($result->getResponseTime())->toBe(-1.0);
        });

        it('handles very large response times', function () {
            $result = new ValidationResult(
                url: 'https://example.com',
                status: LinkStatus::TIMEOUT,
                scope: ValidationScope::EXTERNAL,
                responseTime: 999999.99
            );

            expect($result->getResponseTime())->toBe(999999.99);
        });

        it('handles complex metadata structures', function () {
            $complexMetadata = [
                'nested' => ['deep' => ['value' => 123]],
                'array' => [1, 2, 3, 'string'],
                'boolean' => true,
                'null' => null,
                'float' => 3.14159,
            ];

            $result = new ValidationResult(
                url: 'https://example.com',
                status: LinkStatus::VALID,
                scope: ValidationScope::EXTERNAL,
                metadata: $complexMetadata
            );

            expect($result->getMetadata())->toBe($complexMetadata);
        });

        it('handles all LinkStatus enum values', function () {
            foreach (LinkStatus::cases() as $status) {
                $result = new ValidationResult(
                    url: 'https://example.com',
                    status: $status,
                    scope: ValidationScope::EXTERNAL
                );

                expect($result->getStatus())->toBe($status);
            }
        });

        it('handles all ValidationScope enum values', function () {
            foreach (ValidationScope::cases() as $scope) {
                $result = new ValidationResult(
                    url: 'https://example.com',
                    status: LinkStatus::VALID,
                    scope: $scope
                );

                expect($result->getScope())->toBe($scope);
            }
        });
    });

    describe('method return types and visibility', function () {
        it('has correct public getter methods', function () {
            $reflection = new ReflectionClass(ValidationResult::class);
            $expectedMethods = [
                'getUrl' => 'string',
                'getStatus' => 'App\Enums\LinkStatus',
                'getScope' => 'App\Enums\ValidationScope',
                'getError' => '?string',
                'getHttpStatusCode' => '?int',
                'getRedirectUrl' => '?string',
                'getResponseTime' => 'float',
                'getMetadata' => 'array',
                'getFilePath' => '?string',
                'isValid' => 'bool',
                'isBroken' => 'bool',
                'isTemporary' => 'bool',
                'isSecurityIssue' => 'bool',
            ];

            foreach ($expectedMethods as $methodName => $expectedReturnType) {
                expect($reflection->hasMethod($methodName))->toBeTrue(
                    "Method {$methodName} should exist"
                );

                $method = $reflection->getMethod($methodName);
                expect($method->isPublic())->toBeTrue(
                    "Method {$methodName} should be public"
                );
            }
        });

        it('has correct static factory methods', function () {
            $reflection = new ReflectionClass(ValidationResult::class);
            $staticMethods = ['success', 'failure', 'aggregate', 'create', 'fromArray'];

            foreach ($staticMethods as $methodName) {
                expect($reflection->hasMethod($methodName))->toBeTrue(
                    "Static method {$methodName} should exist"
                );

                $method = $reflection->getMethod($methodName);
                expect($method->isStatic())->toBeTrue(
                    "Method {$methodName} should be static"
                );
                expect($method->isPublic())->toBeTrue(
                    "Method {$methodName} should be public"
                );
            }
        });
    });
});
