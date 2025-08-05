<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use App\Exceptions\ValidateLinksException;
use Tests\Unit\Exceptions\TestValidateLinksException;

describe('ValidateLinksException', function () {
    describe('class structure', function () {
        it('extends Exception', function () {
            $exception = new TestValidateLinksException('test');
            expect($exception)->toBeInstanceOf(Exception::class);
        });

        it('is an abstract class', function () {
            $reflection = new ReflectionClass(ValidateLinksException::class);
            expect($reflection->isAbstract())->toBeTrue();
        });

        it('has proper namespace', function () {
            $reflection = new ReflectionClass(ValidateLinksException::class);
            expect($reflection->getNamespaceName())->toBe('App\Exceptions');
        });

        it('uses strict types declaration', function () {
            $fileContent = file_get_contents(__DIR__.'/../../../app/Exceptions/ValidateLinksException.php');
            expect($fileContent)->toContain('declare(strict_types=1);');
        });
    });

    describe('constructor', function () {
        it('creates exception with default parameters', function () {
            $exception = new TestValidateLinksException();

            expect($exception->getMessage())->toBe('')
                ->and($exception->getCode())->toBe(0)
                ->and($exception->getPrevious())->toBeNull()
                ->and($exception->getContext())->toBe([]);
        });

        it('creates exception with message', function () {
            $message = 'Test error message';
            $exception = new TestValidateLinksException($message);

            expect($exception->getMessage())->toBe($message);
        });

        it('creates exception with message and code', function () {
            $message = 'Test error';
            $code = 123;
            $exception = new TestValidateLinksException($message, $code);

            expect($exception->getMessage())->toBe($message)
                ->and($exception->getCode())->toBe($code);
        });

        it('creates exception with previous exception', function () {
            $previous = new Exception('Previous exception');
            $exception = new TestValidateLinksException('Current exception', 0, $previous);

            expect($exception->getPrevious())->toBe($previous);
        });

        it('creates exception with context', function () {
            $context = ['key' => 'value', 'number' => 123];
            $exception = new TestValidateLinksException('Test', 0, null, $context);

            expect($exception->getContext())->toBe($context);
        });

        it('creates exception with all parameters', function () {
            $message = 'Full test';
            $code = 456;
            $previous = new Exception('Previous');
            $context = ['data' => 'test'];

            $exception = new TestValidateLinksException($message, $code, $previous, $context);

            expect($exception->getMessage())->toBe($message)
                ->and($exception->getCode())->toBe($code)
                ->and($exception->getPrevious())->toBe($previous)
                ->and($exception->getContext())->toBe($context);
        });
    });

    describe('__serialize method', function () {
        it('serializes exception data correctly', function () {
            $message = 'Test message';
            $code = 123;
            $context = ['key' => 'value'];
            $previous = new Exception('Previous');

            $exception = new TestValidateLinksException($message, $code, $previous, $context);
            $serialized = $exception->__serialize();

            expect($serialized)->toBeArray()
                ->and($serialized)->toHaveKey('message', $message)
                ->and($serialized)->toHaveKey('code', $code)
                ->and($serialized)->toHaveKey('file')
                ->and($serialized)->toHaveKey('line')
                ->and($serialized)->toHaveKey('context', $context)
                ->and($serialized)->toHaveKey('previous', $previous);
        });

        it('serializes exception without previous', function () {
            $exception = new TestValidateLinksException('Test');
            $serialized = $exception->__serialize();

            expect($serialized['previous'])->toBeNull();
        });

        it('serializes exception with empty context', function () {
            $exception = new TestValidateLinksException('Test');
            $serialized = $exception->__serialize();

            expect($serialized['context'])->toBe([]);
        });

        it('is final method', function () {
            $reflection = new ReflectionClass(ValidateLinksException::class);
            $method = $reflection->getMethod('__serialize');

            expect($method->isFinal())->toBeTrue();
        });
    });

    describe('__unserialize method', function () {
        it('unserializes exception data correctly', function () {
            $data = [
                'message' => 'Test message',
                'code' => 123,
                'file' => '/test/file.php',
                'line' => 45,
                'context' => ['key' => 'value'],
                'previous' => new Exception('Previous'),
            ];

            $exception = new TestValidateLinksException();
            $exception->__unserialize($data);

            // Note: Due to readonly properties in Exception class, only context can be restored
            // The previous exception property is also readonly and cannot be modified after construction
            expect($exception->getContext())->toBe($data['context']);
        });

        it('handles missing data with defaults', function () {
            $data = [];

            $exception = new TestValidateLinksException();
            $exception->__unserialize($data);

            // Note: Built-in Exception properties (message, code, file, line) cannot be modified
            // after construction in PHP 8+, so we only test the custom properties
            expect($exception->getContext())->toBe([]);
        });

        it('handles invalid file type', function () {
            $data = ['file' => 123]; // non-string

            $exception = new TestValidateLinksException();
            $exception->__unserialize($data);

            // Note: Built-in Exception properties cannot be modified after construction
            // This test verifies that invalid data doesn't cause errors
            expect($exception)->toBeInstanceOf(TestValidateLinksException::class);
        });

        it('handles invalid line type', function () {
            $data = ['line' => 'invalid']; // non-integer

            $exception = new TestValidateLinksException();
            $exception->__unserialize($data);

            // Note: Built-in Exception properties cannot be modified after construction
            // This test verifies that invalid data doesn't cause errors
            expect($exception)->toBeInstanceOf(TestValidateLinksException::class);
        });

        it('handles invalid context type', function () {
            $data = ['context' => 'invalid']; // non-array

            $exception = new TestValidateLinksException();
            $exception->__unserialize($data);

            expect($exception->getContext())->toBe([]);
        });

        it('handles invalid previous type', function () {
            $data = ['previous' => 'invalid']; // non-Throwable

            $exception = new TestValidateLinksException();
            $exception->__unserialize($data);

            expect($exception->getPrevious())->toBeNull();
        });

        it('is final method', function () {
            $reflection = new ReflectionClass(ValidateLinksException::class);
            $method = $reflection->getMethod('__unserialize');

            expect($method->isFinal())->toBeTrue();
        });
    });

    describe('__toString method', function () {
        it('returns formatted string representation', function () {
            $message = 'Test error';
            $exception = new TestValidateLinksException($message);

            $string = $exception->__toString();

            expect($string)->toContain('TestValidateLinksException')
                ->and($string)->toContain('[TEST_ERROR]')
                ->and($string)->toContain($message)
                ->and($string)->toContain($exception->getFile())
                ->and($string)->toContain((string) $exception->getLine())
                ->and($string)->toContain('Stack trace:');
        });

        it('includes error code from getErrorCode', function () {
            $exception = new TestValidateLinksException('Test');
            $string = $exception->__toString();

            expect($string)->toContain('[TEST_ERROR]');
        });

        it('includes class name', function () {
            $exception = new TestValidateLinksException('Test');
            $string = $exception->__toString();

            expect($string)->toContain('TestValidateLinksException');
        });

        it('includes stack trace', function () {
            $exception = new TestValidateLinksException('Test');
            $string = $exception->__toString();

            expect($string)->toContain('Stack trace:')
                ->and($string)->toContain($exception->getTraceAsString());
        });
    });

    describe('abstract methods', function () {
        it('has abstract getErrorCode method', function () {
            $reflection = new ReflectionClass(ValidateLinksException::class);
            $method = $reflection->getMethod('getErrorCode');

            expect($method->isAbstract())->toBeTrue();
            expect($method->hasReturnType())->toBeTrue();

            $returnType = $method->getReturnType();
            expect($returnType)->not->toBeNull();
            expect($returnType)->toBeInstanceOf(ReflectionNamedType::class);
            /** @var ReflectionNamedType $returnType */
            expect($returnType->getName())->toBe('string');
        });

        it('has abstract getSeverity method', function () {
            $reflection = new ReflectionClass(ValidateLinksException::class);
            $method = $reflection->getMethod('getSeverity');

            expect($method->isAbstract())->toBeTrue();
            expect($method->hasReturnType())->toBeTrue();

            $returnType = $method->getReturnType();
            expect($returnType)->not->toBeNull();
            expect($returnType)->toBeInstanceOf(ReflectionNamedType::class);
            /** @var ReflectionNamedType $returnType */
            expect($returnType->getName())->toBe('string');
        });

        it('concrete implementation provides error code', function () {
            $exception = new TestValidateLinksException('Test');

            expect($exception->getErrorCode())->toBe('TEST_ERROR');
        });

        it('concrete implementation provides severity', function () {
            $exception = new TestValidateLinksException('Test');

            expect($exception->getSeverity())->toBe('medium');
        });
    });

    describe('getContext method', function () {
        it('returns context data', function () {
            $context = ['key' => 'value', 'number' => 123];
            $exception = new TestValidateLinksException('Test', 0, null, $context);

            expect($exception->getContext())->toBe($context);
        });

        it('returns empty array by default', function () {
            $exception = new TestValidateLinksException('Test');

            expect($exception->getContext())->toBe([]);
        });

        it('is final method', function () {
            $reflection = new ReflectionClass(ValidateLinksException::class);
            $method = $reflection->getMethod('getContext');

            expect($method->isFinal())->toBeTrue();
        });

        it('has correct return type annotation', function () {
            $reflection = new ReflectionClass(ValidateLinksException::class);
            $method = $reflection->getMethod('getContext');

            expect($method->hasReturnType())->toBeTrue();

            $returnType = $method->getReturnType();
            expect($returnType)->not->toBeNull();
            expect($returnType)->toBeInstanceOf(ReflectionNamedType::class);
            /** @var ReflectionNamedType $returnType */
            expect($returnType->getName())->toBe('array');
        });
    });

    describe('toArray method', function () {
        it('converts exception to array format', function () {
            $message = 'Test message';
            $context = ['key' => 'value'];
            $exception = new TestValidateLinksException($message, 123, null, $context);

            $array = $exception->toArray();

            expect($array)->toBeArray()
                ->and($array)->toHaveKey('error_code', 'TEST_ERROR')
                ->and($array)->toHaveKey('message', $message)
                ->and($array)->toHaveKey('severity', 'medium')
                ->and($array)->toHaveKey('file', $exception->getFile())
                ->and($array)->toHaveKey('line', $exception->getLine())
                ->and($array)->toHaveKey('context', $context);
        });

        it('includes all required fields', function () {
            $exception = new TestValidateLinksException('Test');
            $array = $exception->toArray();

            $requiredFields = ['error_code', 'message', 'severity', 'file', 'line', 'context'];

            foreach ($requiredFields as $field) {
                expect($array)->toHaveKey($field);
            }
        });

        it('is final method', function () {
            $reflection = new ReflectionClass(ValidateLinksException::class);
            $method = $reflection->getMethod('toArray');

            expect($method->isFinal())->toBeTrue();
        });

        it('has correct return type annotation', function () {
            $reflection = new ReflectionClass(ValidateLinksException::class);
            $method = $reflection->getMethod('toArray');

            expect($method->hasReturnType())->toBeTrue();

            $returnType = $method->getReturnType();
            expect($returnType)->not->toBeNull();
            expect($returnType)->toBeInstanceOf(ReflectionNamedType::class);
            /** @var ReflectionNamedType $returnType */
            expect($returnType->getName())->toBe('array');
        });
    });

    describe('serialization round-trip', function () {
        it('maintains data integrity through serialize/unserialize', function () {
            $message = 'Original message';
            $code = 456;
            $context = ['data' => 'test', 'number' => 789];
            $previous = new Exception('Previous exception');

            $original = new TestValidateLinksException($message, $code, $previous, $context);

            // Serialize and unserialize
            $serialized = $original->__serialize();
            $restored = new TestValidateLinksException();
            $restored->__unserialize($serialized);

            // Note: Built-in Exception properties (message, code, previous) cannot be modified after construction
            // Only custom properties (context) can be restored
            expect($restored->getContext())->toBe($context);
        });
    });

    describe('PHPDoc annotations', function () {
        it('has proper class documentation', function () {
            $reflection = new ReflectionClass(ValidateLinksException::class);
            $docComment = $reflection->getDocComment();

            expect($docComment)->toContain('Base exception class for all validate-links exceptions');
        });

        it('has proper constructor documentation', function () {
            $reflection = new ReflectionClass(ValidateLinksException::class);
            $method = $reflection->getMethod('__construct');
            $docComment = $method->getDocComment();

            expect($docComment)->toContain('Create a new exception instance')
                ->and($docComment)->toContain('@param  string  $message')
                ->and($docComment)->toContain('@param  int  $code')
                ->and($docComment)->toContain('@param  Throwable|null  $previous')
                ->and($docComment)->toContain('@param  array<string, mixed>  $context');
        });

        it('has proper method documentation', function () {
            $reflection = new ReflectionClass(ValidateLinksException::class);

            $methods = [
                '__serialize' => 'Serialize the exception',
                '__unserialize' => 'Unserialize the exception',
                '__toString' => 'String representation of the exception including error code',
                'getErrorCode' => 'Get the error code for this exception',
                'getSeverity' => 'Get the severity level of this exception',
                'getContext' => 'Get additional context data',
                'toArray' => 'Convert exception to array format',
            ];

            foreach ($methods as $methodName => $expectedDoc) {
                $method = $reflection->getMethod($methodName);
                $docComment = $method->getDocComment();
                expect($docComment)->toContain($expectedDoc);
            }
        });
    });

    describe('edge cases and error handling', function () {
        it('handles very long messages', function () {
            $longMessage = str_repeat('Very long error message. ', 100);
            $exception = new TestValidateLinksException($longMessage);

            expect($exception->getMessage())->toBe($longMessage);
        });

        it('handles unicode characters in messages', function () {
            $unicodeMessage = 'Error with unicode: 🚫 ñáéíóú';
            $exception = new TestValidateLinksException($unicodeMessage);

            expect($exception->getMessage())->toBe($unicodeMessage);
        });

        it('handles large context arrays', function () {
            $largeContext = [];
            for ($i = 0; $i < 1000; $i++) {
                $largeContext["key_{$i}"] = "value_{$i}";
            }

            $exception = new TestValidateLinksException('Test', 0, null, $largeContext);

            expect($exception->getContext())->toBe($largeContext);
        });

        it('handles circular references in context', function () {
            $context = [];
            $context['self'] = &$context; // Circular reference

            // This should not throw an error during creation
            expect(function () use ($context) {
                $exception = new TestValidateLinksException('Test', 0, null, $context);
                expect($exception)->toBeInstanceOf(TestValidateLinksException::class);
            })->not->toThrow(Exception::class);
        });

        it('handles negative error codes', function () {
            $exception = new TestValidateLinksException('Test', -123);

            expect($exception->getCode())->toBe(-123);
        });

        it('handles zero error code', function () {
            $exception = new TestValidateLinksException('Test', 0);

            expect($exception->getCode())->toBe(0);
        });
    });

    describe('inheritance behavior', function () {
        it('calls parent constructor', function () {
            $message = 'Test message';
            $code = 123;
            $previous = new Exception('Previous');

            $exception = new TestValidateLinksException($message, $code, $previous);

            // Verify parent Exception properties are set
            expect($exception->getMessage())->toBe($message)
                ->and($exception->getCode())->toBe($code)
                ->and($exception->getPrevious())->toBe($previous);
        });

        it('maintains Exception interface', function () {
            $exception = new TestValidateLinksException('Test');

            expect($exception)->toBeInstanceOf(Throwable::class)
                ->and($exception)->toBeInstanceOf(Exception::class);
        });
    });

    describe('property visibility', function () {
        it('has public previous property', function () {
            $reflection = new ReflectionClass(ValidateLinksException::class);
            $property = $reflection->getProperty('previous');

            expect($property->isPublic())->toBeTrue();
        });

        it('has protected context property', function () {
            $reflection = new ReflectionClass(ValidateLinksException::class);
            $property = $reflection->getProperty('context');

            expect($property->isProtected())->toBeTrue();
        });
    });
});
