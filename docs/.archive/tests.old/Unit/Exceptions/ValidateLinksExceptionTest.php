<?php

declare(strict_types=1);

namespace .archive\tests.old\Unit\Exceptions;

use App\Exceptions\ValidateLinksException;
use Exception;
use ReflectionClass;

describe('ValidateLinksException', function (): void {
    beforeEach(function (): void {
        // Exception test setup - no longer needed in pure Pest syntax
    });

    afterEach(function (): void {
        // Exception test teardown - no longer needed in pure Pest syntax
    });

    test('exception is abstract', function (): void {
        $reflection = new ReflectionClass(ValidateLinksException::class);

        expect($reflection->isAbstract())->toBeTrue();
    });

    test('exception extends standard exception', function (): void {
        $reflection = new ReflectionClass(ValidateLinksException::class);
        expect($reflection->isSubclassOf(Exception::class))->toBeTrue();
    });

    test('exception can be extended', function (): void {
        $concreteException = new ConcreteValidateLinksException('Test message');

        expect($concreteException)->toBeInstanceOf(ValidateLinksException::class);
        expect($concreteException)->toBeInstanceOf(Exception::class);
    });

    test('concrete exception has required methods', function (): void {
        $exception = new ConcreteValidateLinksException('Test message');

        // Test that exception implements required methods by calling them
        expect($exception->getErrorCode())->toBeString();
        expect($exception->getSeverity())->toBeString();
        expect($exception->getContext())->toBeArray();
        expect($exception->toArray())->toBeArray();
    });

    test('concrete exception has correct structure', function (): void {
        $exception = new ConcreteValidateLinksException('Test message', 100);

        // Test exception structure
        expect($exception->getErrorCode())->toBeString();
        expect($exception->getSeverity())->toBeString();
        expect($exception->getContext())->toBeArray();
        expect($exception->toArray())->toBeArray();
    });

    test('exception to array returns correct structure', function (): void {
        $exception = new ConcreteValidateLinksException('Test message', 100);
        $array = $exception->toArray();

        // Assert exception array structure
        $requiredKeys = ['error_code', 'message', 'severity', 'file', 'line', 'context'];
        foreach ($requiredKeys as $key) {
            expect($array)->toHaveKey($key);
        }
    });

    test('exception error code is string', function (): void {
        $exception = new ConcreteValidateLinksException('Test message');

        $errorCode = $exception->getErrorCode();
        expect($errorCode)->toBeString();
        expect($errorCode)->not->toBeEmpty();
    });

    test('exception severity is string', function (): void {
        $exception = new ConcreteValidateLinksException('Test message');

        $severity = $exception->getSeverity();
        expect($severity)->toBeString();
        expect($severity)->not->toBeEmpty();
    });

    test('exception context is array', function (): void {
        $exception = new ConcreteValidateLinksException('Test message');

        $context = $exception->getContext();
        expect($context)->toBeArray();
    });

    test('exception can be created with context', function (): void {
        $context = ['key' => 'value', 'data' => ['nested' => 'value']];
        $exception = new ConcreteValidateLinksException('Test message', 0, null, $context);

        expect($exception->getContext())->toBe($context);
    });

    test('exception can be created with previous exception', function (): void {
        $previous = new Exception('Previous exception');
        $exception = new ConcreteValidateLinksException('Test message', 0, $previous);

        expect($exception->getPrevious())->toBe($previous);
    });

    test('exception is serializable', function (): void {
        $exception = new ConcreteValidateLinksException('Test message', 100);

        // Test serialization
        $serialized = serialize($exception);
        expect($serialized)->toBeString();
        $unserialized = unserialize($serialized);
        expect($unserialized)->toBeInstanceOf(ValidateLinksException::class);
        /** @var ValidateLinksException $unserialized */
        expect($unserialized->getMessage())->toBe($exception->getMessage());
    });

    test('exception is json serializable', function (): void {
        $exception = new ConcreteValidateLinksException('Test message', 100);

        // Test JSON serialization
        $json = json_encode($exception->toArray());
        expect($json)->toBeString();
        assert(is_string($json));
        $decoded = json_decode($json, true);
        expect($decoded)->toBeArray();
        /** @var array<string, mixed> $decoded */
        expect($decoded['error_code'])->toBe($exception->getErrorCode());
    });

    test('exception to array includes all required fields', function (): void {
        $context = ['test_key' => 'test_value'];
        $exception = new ConcreteValidateLinksException('Test message', 100, null, $context);
        $array = $exception->toArray();

        expect($array)->toHaveKey('error_code');
        expect($array)->toHaveKey('message');
        expect($array)->toHaveKey('severity');
        expect($array)->toHaveKey('file');
        expect($array)->toHaveKey('line');
        expect($array)->toHaveKey('context');

        expect($array['error_code'])->toBe('TEST_ERROR');
        expect($array['message'])->toBe('Test message');
        expect($array['severity'])->toBe('medium');
        expect($array['context'])->toBe($context);
    });

    test('exception error code format is valid', function (): void {
        $exception = new ConcreteValidateLinksException('Test message');

        expect($exception->getErrorCode())->toMatch('/^[A-Z_]+$/');
    });

    test('exception handles empty context', function (): void {
        $exception = new ConcreteValidateLinksException('Test message');

        $context = $exception->getContext();
        expect($context)->toBeArray();
        expect($context)->toBeEmpty();
    });

    test('exception handles complex context', function (): void {
        $complexContext = [
            'url' => 'https://example.com',
            'status_code' => 404,
            'headers' => ['Content-Type' => 'text/html'],
            'metadata' => [
                'timestamp' => time(),
                'user_agent' => 'test-agent',
                'nested' => [
                    'deep' => 'value',
                ],
            ],
        ];

        $exception = new ConcreteValidateLinksException('Test message', 0, null, $complexContext);

        expect($exception->getContext())->toBe($complexContext);
    });

    test('exception preserves stack trace', function (): void {
        $exception = new ConcreteValidateLinksException('Test message');

        $trace = $exception->getTrace();
        expect($trace)->toBeArray();
        expect($trace)->not->toBeEmpty();
    });

    test('exception has correct file and line', function (): void {
        $line = __LINE__ + 1;
        $exception = new ConcreteValidateLinksException('Test message');

        expect($exception->getFile())->toBe(__FILE__);
        expect($exception->getLine())->toBe($line);
    });

    test('exception can be caught as base exception', function (): void {
        // Test that exception is thrown with correct message
        expect(fn (): ConcreteValidateLinksException => throw new ConcreteValidateLinksException('Test message'))
            ->toThrow(ConcreteValidateLinksException::class, 'Test message');
    });

    test('exception can be caught as validate links exception', function (): void {
        // Test that exception is thrown with correct message
        expect(fn (): ConcreteValidateLinksException => throw new ConcreteValidateLinksException('Test message'))
            ->toThrow(ValidateLinksException::class, 'Test message');
    });

    test('exception supports different severity levels', function (): void {
        $lowSeverityException = new LowSeverityException('Low severity');
        $highSeverityException = new HighSeverityException('High severity');

        expect($lowSeverityException->getSeverity())->toBe('low');
        expect($highSeverityException->getSeverity())->toBe('critical');
    });

    test('exception supports different error codes', function (): void {
        $exception1 = new ConcreteValidateLinksException('Message 1');
        $exception2 = new AlternativeException('Message 2');

        expect($exception1->getErrorCode())->toBe('TEST_ERROR');
        expect($exception2->getErrorCode())->toBe('ALTERNATIVE_ERROR');
    });

    test('exception string representation includes error code', function (): void {
        $exception = new ConcreteValidateLinksException('Test message');
        $string = (string) $exception;

        expect($string)->toContain('TEST_ERROR');
        expect($string)->toContain('Test message');
    });

});

/**
 * Concrete implementation of ValidateLinksException for testing purposes.
 */
final class ConcreteValidateLinksException extends ValidateLinksException
{
    public function getErrorCode(): string
    {
        return 'TEST_ERROR';
    }

    public function getSeverity(): string
    {
        return 'medium';
    }
}

/**
 * Test helper exception with low severity.
 */
final class LowSeverityException extends ValidateLinksException
{
    public function getErrorCode(): string
    {
        return 'LOW_SEVERITY_ERROR';
    }

    public function getSeverity(): string
    {
        return 'low';
    }
}

/**
 * Test helper exception with high severity.
 */
final class HighSeverityException extends ValidateLinksException
{
    public function getErrorCode(): string
    {
        return 'HIGH_SEVERITY_ERROR';
    }

    public function getSeverity(): string
    {
        return 'critical';
    }
}

/**
 * Alternative test helper exception with different error code.
 */
final class AlternativeException extends ValidateLinksException
{
    public function getErrorCode(): string
    {
        return 'ALTERNATIVE_ERROR';
    }

    public function getSeverity(): string
    {
        return 'medium';
    }
}
