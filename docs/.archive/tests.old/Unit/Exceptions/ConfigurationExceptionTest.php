<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use App\Exceptions\ConfigurationException;
use App\Exceptions\ValidateLinksException;
use Exception;
use InvalidArgumentException;
use JsonException;
use ReflectionClass;
use Throwable;

describe('ConfigurationException', function (): void {
    beforeEach(function (): void {
        // Exception test setup - no longer needed in pure Pest syntax
    });

    afterEach(function (): void {
        // Exception test teardown - no longer needed in pure Pest syntax
    });

    test('exception extends validate links exception', function (): void {
        $reflection = new ReflectionClass(ConfigurationException::class);
        expect($reflection->isSubclassOf(ValidateLinksException::class))->toBeTrue();
    });

    test('exception can be instantiated', function (): void {
        $exception = new ConfigurationException('Configuration error');

        expect($exception)->toBeInstanceOf(ConfigurationException::class);
        expect($exception)->toBeInstanceOf(ValidateLinksException::class);
        expect($exception)->toBeInstanceOf(Exception::class);
    });

    test('exception has correct error code', function (): void {
        $exception = new ConfigurationException('Configuration error');

        expect($exception->getErrorCode())->toBe('CONFIGURATION_ERROR');
    });

    test('exception has correct severity', function (): void {
        $exception = new ConfigurationException('Configuration error');

        expect($exception->getSeverity())->toBe('high');
    });

    test('exception can be created with message', function (): void {
        $message = 'Invalid configuration file format';
        $exception = new ConfigurationException($message);

        expect($exception->getMessage())->toBe($message);
    });

    test('exception can be created with code', function (): void {
        $code = 1001;
        $exception = new ConfigurationException('Configuration error', $code);

        expect($exception->getCode())->toBe($code);
    });

    test('exception can be created with previous exception', function (): void {
        $previous = new InvalidArgumentException('Invalid argument');
        $exception = new ConfigurationException('Configuration error', 0, $previous);

        expect($exception->getPrevious())->toBe($previous);
    });

    test('exception can be created with context', function (): void {
        $context = [
            'config_file' => '/path/to/config.json',
            'line_number' => 15,
            'expected_format' => 'json',
            'actual_format' => 'invalid',
        ];

        $exception = ConfigurationException::withContext('Invalid format', $context);

        expect($exception->getContext())->toBe($context);
    });

    test('exception for missing file has correct context', function (): void {
        $filePath = '/path/to/missing/config.json';
        $exception = ConfigurationException::missingFile($filePath);

        $expectedContext = [
            'file_path' => $filePath,
            'error_type' => 'missing_file',
        ];

        expect($exception->getContext())->toBe($expectedContext);
        expect($exception->getMessage())->toContain($filePath);
    });

    test('exception for invalid format has correct context', function (): void {
        $filePath = '/path/to/config.json';
        $expectedFormat = 'json';
        $actualContent = 'invalid json content';

        $exception = ConfigurationException::invalidFormat($filePath, $expectedFormat, $actualContent);

        $expectedContext = [
            'file_path' => $filePath,
            'expected_format' => $expectedFormat,
            'actual_content' => $actualContent,
            'error_type' => 'invalid_format',
        ];

        expect($exception->getContext())->toBe($expectedContext);
        expect($exception->getMessage())->toContain($filePath);
        expect($exception->getMessage())->toContain($expectedFormat);
    });

    test('exception for missing required key has correct context', function (): void {
        $key = 'timeout';
        $filePath = '/path/to/config.json';

        $exception = ConfigurationException::missingRequiredKey($key, $filePath);

        $expectedContext = [
            'required_key' => $key,
            'file_path' => $filePath,
            'error_type' => 'missing_required_key',
        ];

        expect($exception->getContext())->toBe($expectedContext);
        expect($exception->getMessage())->toContain($key);
        expect($exception->getMessage())->toContain($filePath);
    });

    test('exception for invalid value has correct context', function (): void {
        $key = 'timeout';
        $value = 'invalid_timeout';
        $expectedType = 'integer';

        $exception = ConfigurationException::invalidValue($key, $value, $expectedType);

        $expectedContext = [
            'key' => $key,
            'value' => $value,
            'expected_type' => $expectedType,
            'actual_type' => gettype($value),
            'error_type' => 'invalid_value',
        ];

        expect($exception->getContext())->toBe($expectedContext);
        expect($exception->getMessage())->toContain($key);
        expect($exception->getMessage())->toContain($expectedType);
    });

    test('exception to array includes configuration specific data', function (): void {
        $context = [
            'config_file' => '/path/to/config.json',
            'validation_errors' => ['timeout must be positive', 'format is required'],
        ];

        $exception = ConfigurationException::withContext('Multiple validation errors', $context);
        $array = $exception->toArray();

        // Assert exception array structure
        $requiredKeys = ['error_code', 'message', 'severity', 'file', 'line', 'context'];
        foreach ($requiredKeys as $key) {
            expect($array)->toHaveKey($key);
        }
        expect($array['error_code'])->toBe('CONFIGURATION_ERROR');
        expect($array['severity'])->toBe('high');
        expect($array['context'])->toBe($context);
    });

    test('exception is serializable', function (): void {
        $exception = new ConfigurationException('Configuration error');

        // Test serialization
        $serialized = serialize($exception);
        expect($serialized)->toBeString();
        $unserialized = unserialize($serialized);
        expect($unserialized)->toBeInstanceOf(ConfigurationException::class);
        /** @var ConfigurationException $unserialized */
        expect($unserialized->getMessage())->toBe($exception->getMessage());
    });

    test('exception is json serializable', function (): void {
        $exception = new ConfigurationException('Configuration error');

        // Test JSON serialization
        $json = json_encode($exception->toArray());
        expect($json)->toBeString();
        assert(is_string($json));
        $decoded = json_decode($json, true);
        expect($decoded)->toBeArray();
        /** @var array<string, mixed> $decoded */
        expect($decoded['error_code'])->toBe($exception->getErrorCode());
    });

    test('exception can be caught as configuration exception', function (): void {
        // Test that exception is thrown with correct message
        expect(fn () => ConfigurationException::invalidFile('/path/to/file'))
            ->toThrow(ConfigurationException::class, 'Configuration error');
    });

    test('exception can be caught as validate links exception', function (): void {
        // Test that exception is thrown with correct message
        expect(fn () => ConfigurationException::invalidKey('test-key'))
            ->toThrow(ConfigurationException::class, 'Configuration error');
    });

    test('exception factory methods create correct instances', function (): void {
        $missingFileException = ConfigurationException::missingFile('/path/to/file');
        $invalidFormatException = ConfigurationException::invalidFormat('/path/to/file', 'json', 'invalid');
        $missingKeyException = ConfigurationException::missingRequiredKey('key', '/path/to/file');
        $invalidValueException = ConfigurationException::invalidValue('key', 'value', 'integer');
        $contextException = ConfigurationException::withContext('message', ['key' => 'value']);

        expect($missingFileException)->toBeInstanceOf(ConfigurationException::class);
        expect($invalidFormatException)->toBeInstanceOf(ConfigurationException::class);
        expect($missingKeyException)->toBeInstanceOf(ConfigurationException::class);
        expect($invalidValueException)->toBeInstanceOf(ConfigurationException::class);
        expect($contextException)->toBeInstanceOf(ConfigurationException::class);
    });

    test('exception handles complex configuration scenarios', function (): void {
        $complexContext = [
            'config_file' => '/path/to/validate-links.json',
            'section' => 'external_validation',
            'validation_errors' => [
                'timeout' => 'Must be between 1 and 300 seconds',
                'concurrent_requests' => 'Must be between 1 and 50',
                'user_agent' => 'Cannot be empty',
            ],
            'current_values' => [
                'timeout' => -5,
                'concurrent_requests' => 100,
                'user_agent' => '',
            ],
            'line_numbers' => [
                'timeout' => 15,
                'concurrent_requests' => 16,
                'user_agent' => 17,
            ],
        ];

        $exception = ConfigurationException::withContext(
            'Multiple configuration validation errors in external_validation section',
            $complexContext
        );

        expect($exception->getContext())->toBe($complexContext);
        expect($exception->getErrorCode())->toBe('CONFIGURATION_ERROR');
        expect($exception->getSeverity())->toBe('high');
    });

    test('exception preserves original exception chain', function (): void {
        $originalException = new JsonException('Invalid JSON syntax');
        $parseException = new InvalidArgumentException('Failed to parse configuration', 0, $originalException);
        $configException = new ConfigurationException('Configuration file is invalid', 0, $parseException);

        expect($configException->getPrevious())->toBe($parseException);
        $previous = $configException->getPrevious();
        expect($previous)->not->toBeNull();
        /** @var Throwable $previous */
        expect($previous->getPrevious())->toBe($originalException);
    });

    test('exception error code format is valid', function (): void {
        $exception = new ConfigurationException('Configuration error');

        expect($exception->getErrorCode())->toMatch('/^[A-Z_]+$/');
    });

    test('exception severity is appropriate for configuration errors', function (): void {
        $exception = new ConfigurationException('Configuration error');

        // Configuration errors should be high severity as they prevent the application from working
        expect($exception->getSeverity())->toBe('high');
    });

});
