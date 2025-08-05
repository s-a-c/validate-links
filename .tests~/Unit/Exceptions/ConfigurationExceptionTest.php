<?php

declare(strict_types=1);

use App\Exceptions\ConfigurationException;
use App\Exceptions\ValidateLinksException;

describe('ConfigurationException', function () {
    describe('class structure', function () {
        it('extends ValidateLinksException', function () {
            $exception = new ConfigurationException('test');
            expect($exception)->toBeInstanceOf(ValidateLinksException::class);
        });

        it('is a final class', function () {
            $reflection = new ReflectionClass(ConfigurationException::class);
            expect($reflection->isFinal())->toBeTrue();
        });

        it('has proper namespace', function () {
            $reflection = new ReflectionClass(ConfigurationException::class);
            expect($reflection->getNamespaceName())->toBe('App\Exceptions');
        });

        it('uses strict types declaration', function () {
            $fileContent = file_get_contents(__DIR__.'/../../../app/Exceptions/ConfigurationException.php');
            expect($fileContent)->toContain('declare(strict_types=1);');
        });
    });

    describe('withContext static method', function () {
        it('creates exception with custom context', function () {
            $message = 'Test configuration error';
            $context = ['key' => 'value', 'number' => 123];

            $exception = ConfigurationException::withContext($message, $context);

            expect($exception)->toBeInstanceOf(ConfigurationException::class)
                ->and($exception->getMessage())->toBe($message)
                ->and($exception->getCode())->toBe(0)
                ->and($exception->getPrevious())->toBeNull()
                ->and($exception->getContext())->toBe($context);
        });

        it('handles empty context array', function () {
            $message = 'Test error';
            $context = [];

            $exception = ConfigurationException::withContext($message, $context);

            expect($exception->getMessage())->toBe($message)
                ->and($exception->getContext())->toBe($context);
        });

        it('handles complex context data', function () {
            $message = 'Complex error';
            $context = [
                'nested' => ['array' => ['data']],
                'boolean' => true,
                'null' => null,
                'float' => 3.14,
            ];

            $exception = ConfigurationException::withContext($message, $context);

            expect($exception->getContext())->toBe($context);
        });

        it('returns static instance', function () {
            $exception = ConfigurationException::withContext('test', []);

            expect($exception)->toBeInstanceOf(ConfigurationException::class);
        });
    });

    describe('missingFile static method', function () {
        it('creates exception for missing file', function () {
            $filePath = '/path/to/missing/config.json';

            $exception = ConfigurationException::missingFile($filePath);

            expect($exception)->toBeInstanceOf(ConfigurationException::class)
                ->and($exception->getMessage())->toBe("Configuration file not found: {$filePath}")
                ->and($exception->getCode())->toBe(0)
                ->and($exception->getPrevious())->toBeNull();

            $context = $exception->getContext();
            expect($context)->toHaveKey('file_path', $filePath)
                ->and($context)->toHaveKey('error_type', 'missing_file');
        });

        it('handles empty file path', function () {
            $filePath = '';

            $exception = ConfigurationException::missingFile($filePath);

            expect($exception->getMessage())->toBe('Configuration file not found: ')
                ->and($exception->getContext()['file_path'])->toBe('');
        });

        it('handles special characters in file path', function () {
            $filePath = '/path/with spaces/config@#$.json';

            $exception = ConfigurationException::missingFile($filePath);

            expect($exception->getMessage())->toContain($filePath)
                ->and($exception->getContext()['file_path'])->toBe($filePath);
        });

        it('returns static instance', function () {
            $exception = ConfigurationException::missingFile('/test/path');

            expect($exception)->toBeInstanceOf(ConfigurationException::class);
        });
    });

    describe('invalidFormat static method', function () {
        it('creates exception for invalid format', function () {
            $filePath = '/path/to/config.json';
            $expectedFormat = 'JSON';
            $actualContent = 'invalid content';

            $exception = ConfigurationException::invalidFormat($filePath, $expectedFormat, $actualContent);

            expect($exception)->toBeInstanceOf(ConfigurationException::class)
                ->and($exception->getMessage())->toBe("Invalid configuration file format in {$filePath}. Expected {$expectedFormat} format.")
                ->and($exception->getCode())->toBe(0);

            $context = $exception->getContext();
            expect($context)->toHaveKey('file_path', $filePath)
                ->and($context)->toHaveKey('expected_format', $expectedFormat)
                ->and($context)->toHaveKey('actual_content', $actualContent)
                ->and($context)->toHaveKey('error_type', 'invalid_format');
        });

        it('handles empty parameters', function () {
            $exception = ConfigurationException::invalidFormat('', '', '');

            expect($exception->getMessage())->toBe('Invalid configuration file format in . Expected  format.');
        });

        it('handles long actual content', function () {
            $filePath = '/config.json';
            $expectedFormat = 'JSON';
            $actualContent = str_repeat('invalid content ', 100);

            $exception = ConfigurationException::invalidFormat($filePath, $expectedFormat, $actualContent);

            expect($exception->getContext()['actual_content'])->toBe($actualContent);
        });

        it('returns static instance', function () {
            $exception = ConfigurationException::invalidFormat('/test', 'JSON', 'content');

            expect($exception)->toBeInstanceOf(ConfigurationException::class);
        });
    });

    describe('missingRequiredKey static method', function () {
        it('creates exception for missing required key', function () {
            $key = 'required_setting';
            $filePath = '/path/to/config.json';

            $exception = ConfigurationException::missingRequiredKey($key, $filePath);

            expect($exception)->toBeInstanceOf(ConfigurationException::class)
                ->and($exception->getMessage())->toBe("Missing required configuration key '{$key}' in {$filePath}")
                ->and($exception->getCode())->toBe(0);

            $context = $exception->getContext();
            expect($context)->toHaveKey('required_key', $key)
                ->and($context)->toHaveKey('file_path', $filePath)
                ->and($context)->toHaveKey('error_type', 'missing_required_key');
        });

        it('handles empty key and file path', function () {
            $exception = ConfigurationException::missingRequiredKey('', '');

            expect($exception->getMessage())->toBe("Missing required configuration key '' in ");
        });

        it('handles special characters in key', function () {
            $key = 'key.with.dots_and-dashes';
            $filePath = '/config.json';

            $exception = ConfigurationException::missingRequiredKey($key, $filePath);

            expect($exception->getContext()['required_key'])->toBe($key);
        });

        it('returns static instance', function () {
            $exception = ConfigurationException::missingRequiredKey('key', '/path');

            expect($exception)->toBeInstanceOf(ConfigurationException::class);
        });
    });

    describe('invalidValue static method', function () {
        it('creates exception for invalid value with string', function () {
            $key = 'timeout';
            $value = 'invalid';
            $expectedType = 'integer';

            $exception = ConfigurationException::invalidValue($key, $value, $expectedType);

            expect($exception)->toBeInstanceOf(ConfigurationException::class)
                ->and($exception->getMessage())->toBe("Invalid value for configuration key '{$key}'. Expected {$expectedType}.")
                ->and($exception->getCode())->toBe(0);

            $context = $exception->getContext();
            expect($context)->toHaveKey('key', $key)
                ->and($context)->toHaveKey('value', $value)
                ->and($context)->toHaveKey('expected_type', $expectedType)
                ->and($context)->toHaveKey('actual_type', 'string')
                ->and($context)->toHaveKey('error_type', 'invalid_value');
        });

        it('handles different value types', function () {
            $testCases = [
                ['key', 123, 'string', 'integer'],
                ['key', true, 'integer', 'boolean'],
                ['key', [], 'string', 'array'],
                ['key', null, 'string', 'NULL'],
                ['key', 3.14, 'integer', 'double'],
            ];

            foreach ($testCases as [$key, $value, $expectedType, $actualType]) {
                $exception = ConfigurationException::invalidValue($key, $value, $expectedType);

                expect($exception->getContext()['value'])->toBe($value)
                    ->and($exception->getContext()['actual_type'])->toBe($actualType);
            }
        });

        it('handles object values', function () {
            $key = 'config';
            $value = new stdClass();
            $expectedType = 'array';

            $exception = ConfigurationException::invalidValue($key, $value, $expectedType);

            expect($exception->getContext()['actual_type'])->toBe('object');
        });

        it('returns static instance', function () {
            $exception = ConfigurationException::invalidValue('key', 'value', 'type');

            expect($exception)->toBeInstanceOf(ConfigurationException::class);
        });
    });

    describe('invalidFile static method', function () {
        it('throws exception for invalid file', function () {
            $filePath = '/path/to/invalid/config.json';

            expect(function () use ($filePath) {
                ConfigurationException::invalidFile($filePath);
            })->toThrow(ConfigurationException::class, "Configuration error: Invalid configuration file: {$filePath}");
        });

        it('includes correct context when thrown', function () {
            $filePath = '/invalid/config.json';

            expect(function () use ($filePath) {
                ConfigurationException::invalidFile($filePath);
            })->toThrow(ConfigurationException::class);

            // Test context by creating the exception with the same context as invalidFile
            $exception = ConfigurationException::withContext(
                "Configuration error: Invalid configuration file: {$filePath}",
                ['file_path' => $filePath, 'error_type' => 'invalid_file']
            );

            $context = $exception->getContext();
            expect($context)->toHaveKey('file_path', $filePath)
                ->and($context)->toHaveKey('error_type', 'invalid_file');
        });

        it('has never return type', function () {
            $reflection = new ReflectionClass(ConfigurationException::class);
            $method = $reflection->getMethod('invalidFile');

            expect($method->hasReturnType())->toBeTrue();

            $returnType = $method->getReturnType();
            expect($returnType)->not->toBeNull();
            expect($returnType)->toBeInstanceOf(ReflectionNamedType::class);
            /** @var ReflectionNamedType $returnType */
            expect($returnType->getName())->toBe('never');
        });

        it('handles empty file path', function () {
            expect(function () {
                ConfigurationException::invalidFile('');
            })->toThrow(ConfigurationException::class, 'Configuration error: Invalid configuration file: ');
        });
    });

    describe('invalidKey static method', function () {
        it('throws exception for invalid key', function () {
            $key = 'invalid_key';

            expect(function () use ($key) {
                ConfigurationException::invalidKey($key);
            })->toThrow(ConfigurationException::class, "Configuration error: Invalid configuration key: {$key}");
        });

        it('includes correct context when thrown', function () {
            $key = 'bad_key';

            expect(function () use ($key) {
                ConfigurationException::invalidKey($key);
            })->toThrow(ConfigurationException::class);

            // Test context by creating the exception with the same context as invalidKey
            $exception = ConfigurationException::withContext(
                "Configuration error: Invalid configuration key: {$key}",
                ['key' => $key, 'error_type' => 'invalid_key']
            );

            $context = $exception->getContext();
            expect($context)->toHaveKey('key', $key)
                ->and($context)->toHaveKey('error_type', 'invalid_key');
        });

        it('has never return type', function () {
            $reflection = new ReflectionClass(ConfigurationException::class);
            $method = $reflection->getMethod('invalidKey');

            expect($method->hasReturnType())->toBeTrue();

            $returnType = $method->getReturnType();
            expect($returnType)->not->toBeNull();
            expect($returnType)->toBeInstanceOf(ReflectionNamedType::class);
            /** @var ReflectionNamedType $returnType */
            expect($returnType->getName())->toBe('never');
        });

        it('handles empty key', function () {
            expect(function () {
                ConfigurationException::invalidKey('');
            })->toThrow(ConfigurationException::class, 'Configuration error: Invalid configuration key: ');
        });
    });

    describe('getErrorCode method', function () {
        it('returns correct error code', function () {
            $exception = new ConfigurationException('test');

            expect($exception->getErrorCode())->toBe('CONFIGURATION_ERROR');
        });

        it('returns same error code for all instances', function () {
            $exception1 = ConfigurationException::missingFile('/path1');
            $exception2 = ConfigurationException::invalidValue('key', 'value', 'type');

            expect($exception1->getErrorCode())->toBe('CONFIGURATION_ERROR')
                ->and($exception2->getErrorCode())->toBe('CONFIGURATION_ERROR');
        });
    });

    describe('getSeverity method', function () {
        it('returns correct severity', function () {
            $exception = new ConfigurationException('test');

            expect($exception->getSeverity())->toBe('high');
        });

        it('returns same severity for all instances', function () {
            $exception1 = ConfigurationException::missingFile('/path1');
            $exception2 = ConfigurationException::invalidValue('key', 'value', 'type');

            expect($exception1->getSeverity())->toBe('high')
                ->and($exception2->getSeverity())->toBe('high');
        });
    });

    describe('inheritance and interface compliance', function () {
        it('inherits from ValidateLinksException', function () {
            $exception = new ConfigurationException('test');

            expect($exception)->toBeInstanceOf(ValidateLinksException::class)
                ->and($exception)->toBeInstanceOf(Exception::class);
        });

        it('implements standard exception interface', function () {
            $exception = new ConfigurationException('test message', 123);

            expect($exception->getMessage())->toBe('test message')
                ->and($exception->getCode())->toBe(123);
        });
    });

    describe('PHPDoc annotations', function () {
        it('has proper class documentation', function () {
            $reflection = new ReflectionClass(ConfigurationException::class);
            $docComment = $reflection->getDocComment();

            expect($docComment)->toContain('Exception thrown when configuration errors occur');
        });

        it('has proper method documentation', function () {
            $reflection = new ReflectionClass(ConfigurationException::class);

            $methods = [
                'withContext' => 'Create a configuration exception with custom context',
                'missingFile' => 'Create a configuration exception for a missing file',
                'invalidFormat' => 'Create a configuration exception for invalid file format',
                'missingRequiredKey' => 'Create a configuration exception for a missing required key',
                'invalidValue' => 'Create a configuration exception for an invalid value',
                'invalidFile' => 'Create a configuration exception for an invalid file',
                'invalidKey' => 'Create a configuration exception for an invalid key',
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
            $exception = ConfigurationException::withContext($longMessage, []);

            expect($exception->getMessage())->toBe($longMessage);
        });

        it('handles unicode characters in messages', function () {
            $unicodeMessage = 'Configuration error with unicode: 🚫 ñáéíóú';
            $exception = ConfigurationException::withContext($unicodeMessage, []);

            expect($exception->getMessage())->toBe($unicodeMessage);
        });

        it('handles circular references in context', function () {
            $context = [];
            $context['self'] = &$context; // Circular reference

            // This should not throw an error during creation
            expect(function () use ($context) {
                ConfigurationException::withContext('test', $context);
            })->not->toThrow(Exception::class);
        });

        it('preserves exception chain', function () {
            $previous = new Exception('Previous exception');
            $exception = new ConfigurationException('Current exception', 0, $previous);

            expect($exception->getPrevious())->toBe($previous);
        });
    });

    describe('static factory method consistency', function () {
        it('all static methods return ConfigurationException instances', function () {
            $methods = [
                fn () => ConfigurationException::withContext('test', []),
                fn () => ConfigurationException::missingFile('/path'),
                fn () => ConfigurationException::invalidFormat('/path', 'JSON', 'content'),
                fn () => ConfigurationException::missingRequiredKey('key', '/path'),
                fn () => ConfigurationException::invalidValue('key', 'value', 'type'),
            ];

            foreach ($methods as $method) {
                $exception = $method();
                expect($exception)->toBeInstanceOf(ConfigurationException::class);
            }
        });

        it('all static methods set code to 0 by default', function () {
            $methods = [
                fn () => ConfigurationException::withContext('test', []),
                fn () => ConfigurationException::missingFile('/path'),
                fn () => ConfigurationException::invalidFormat('/path', 'JSON', 'content'),
                fn () => ConfigurationException::missingRequiredKey('key', '/path'),
                fn () => ConfigurationException::invalidValue('key', 'value', 'type'),
            ];

            foreach ($methods as $method) {
                $exception = $method();
                expect($exception->getCode())->toBe(0);
            }
        });

        it('all static methods set previous to null by default', function () {
            $methods = [
                fn () => ConfigurationException::withContext('test', []),
                fn () => ConfigurationException::missingFile('/path'),
                fn () => ConfigurationException::invalidFormat('/path', 'JSON', 'content'),
                fn () => ConfigurationException::missingRequiredKey('key', '/path'),
                fn () => ConfigurationException::invalidValue('key', 'value', 'type'),
            ];

            foreach ($methods as $method) {
                $exception = $method();
                expect($exception->getPrevious())->toBeNull();
            }
        });
    });
});
