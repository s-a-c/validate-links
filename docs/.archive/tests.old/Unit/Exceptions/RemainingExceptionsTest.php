<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use App\Exceptions\Handler;
use App\Exceptions\SecurityException;
use App\Exceptions\ValidateLinksException;
use App\Exceptions\ValidationException;
use Exception;
use ReflectionClass;
use Throwable;

describe('RemainingExceptions', function (): void {
    beforeEach(function (): void {
        // Exception test setup - no longer needed in pure Pest syntax
    });

    afterEach(function (): void {
        // Exception test teardown - no longer needed in pure Pest syntax
    });

    test('security exception extends validate links exception', function (): void {
        $reflection = new ReflectionClass(SecurityException::class);
        expect($reflection->isSubclassOf(ValidateLinksException::class))->toBeTrue();
    });

    test('security exception can be instantiated', function (): void {
        $exception = new SecurityException('Security violation');

        expect($exception)->toBeInstanceOf(SecurityException::class);
        expect($exception)->toBeInstanceOf(ValidateLinksException::class);
        expect($exception)->toBeInstanceOf(Exception::class);
    });

    test('security exception has correct error code', function (): void {
        $exception = new SecurityException('Security violation');

        expect($exception->getErrorCode())->toBe('SECURITY_VIOLATION');
    });

    test('security exception has critical severity', function (): void {
        $exception = new SecurityException('Security violation');

        expect($exception->getSeverity())->toBe('critical');
    });

    test('security exception can be created with threat context', function (): void {
        $context = [
            'url' => 'https://malicious-site.com',
            'threat_type' => 'malware',
            'risk_level' => 'high',
            'detected_patterns' => ['suspicious_redirect', 'known_malware_domain'],
        ];

        $exception = SecurityException::threatDetected('https://malicious-site.com', 'malware', $context);

        expect($exception->getContext())->toBe($context);
        expect($exception->getMessage())->toContain('malicious-site.com');
        expect($exception->getMessage())->toContain('malware');
    });

    test('security exception can be created for blacklisted domain', function (): void {
        $domain = 'blacklisted-domain.com';
        $exception = SecurityException::blacklistedDomain($domain);

        $expectedContext = [
            'domain' => $domain,
            'threat_type' => 'blacklisted_domain',
            'risk_level' => 'high',
        ];

        expect($exception->getContext())->toBe($expectedContext);
        expect($exception->getMessage())->toContain($domain);
    });

    test('security exception can be created for ssl violation', function (): void {
        $url = 'https://invalid-ssl.com';
        $sslError = 'Certificate has expired';

        $exception = SecurityException::sslViolation($url, $sslError);

        $expectedContext = [
            'url' => $url,
            'ssl_error' => $sslError,
            'threat_type' => 'ssl_violation',
            'risk_level' => 'medium',
        ];

        expect($exception->getContext())->toBe($expectedContext);
        expect($exception->getMessage())->toContain($url);
        expect($exception->getMessage())->toContain($sslError);
    });

    test('validation exception extends validate links exception', function (): void {
        $reflection = new ReflectionClass(ValidationException::class);
        expect($reflection->isSubclassOf(ValidateLinksException::class))->toBeTrue();
    });

    test('validation exception can be instantiated', function (): void {
        $exception = new ValidationException('Validation failed');

        expect($exception)->toBeInstanceOf(ValidationException::class);
        expect($exception)->toBeInstanceOf(ValidateLinksException::class);
        expect($exception)->toBeInstanceOf(Exception::class);
    });

    test('validation exception has correct error code', function (): void {
        $exception = new ValidationException('Validation failed');

        expect($exception->getErrorCode())->toBe('VALIDATION_ERROR');
    });

    test('validation exception has medium severity', function (): void {
        $exception = new ValidationException('Validation failed');

        expect($exception->getSeverity())->toBe('medium');
    });

    test('validation exception can be created for link failure', function (): void {
        $url = 'https://broken-link.com';
        $statusCode = 404;
        $responseTime = 1500;

        $exception = ValidationException::linkValidationFailed($url, $statusCode, $responseTime);

        $expectedContext = [
            'url' => $url,
            'status_code' => $statusCode,
            'response_time' => $responseTime,
            'validation_type' => 'link_validation',
        ];

        expect($exception->getContext())->toBe($expectedContext);
        expect($exception->getMessage())->toContain($url);
        expect($exception->getMessage())->toContain((string) $statusCode);
    });

    test('validation exception can be created for file processing failure', function (): void {
        $filePath = '/path/to/document.md';
        $error = 'File is not readable';

        $exception = ValidationException::fileProcessingFailed($filePath, $error);

        $expectedContext = [
            'file_path' => $filePath,
            'error' => $error,
            'validation_type' => 'file_processing',
        ];

        expect($exception->getContext())->toBe($expectedContext);
        expect($exception->getMessage())->toContain($filePath);
        expect($exception->getMessage())->toContain($error);
    });

    test('validation exception can be created for timeout', function (): void {
        $url = 'https://slow-site.com';
        $timeout = 30;

        $exception = ValidationException::validationTimeout($url, $timeout);

        $expectedContext = [
            'url' => $url,
            'timeout_seconds' => $timeout,
            'validation_type' => 'timeout',
        ];

        expect($exception->getContext())->toBe($expectedContext);
        expect($exception->getMessage())->toContain($url);
        expect($exception->getMessage())->toContain((string) $timeout);
    });

    test('handler extends laravel exception handler', function (): void {
        expect(class_exists(Handler::class))->toBeTrue();

        $reflection = new ReflectionClass(Handler::class);
        $parentClass = $reflection->getParentClass();

        expect($parentClass)->not->toBeFalse();
        expect($parentClass->getName())->toBe(\Illuminate\Foundation\Exceptions\Handler::class);
    });

    test('handler can be instantiated', function (): void {
        $container = app();
        $handler = new Handler($container);

        expect($handler)->toBeInstanceOf(Handler::class);
    });

    test('handler has dont report property', function (): void {
        $reflection = new ReflectionClass(Handler::class);

        expect($reflection->hasProperty('dontReport'))->toBeTrue();

        $property = $reflection->getProperty('dontReport');
        expect($property->isProtected())->toBeTrue();
    });

    test('handler has dont flash property', function (): void {
        $reflection = new ReflectionClass(Handler::class);

        expect($reflection->hasProperty('dontFlash'))->toBeTrue();

        $property = $reflection->getProperty('dontFlash');
        expect($property->isProtected())->toBeTrue();
    });

    test('handler register method exists', function (): void {
        $reflection = new ReflectionClass(Handler::class);

        expect($reflection->hasMethod('register'))->toBeTrue();

        $method = $reflection->getMethod('register');
        expect($method->isPublic())->toBeTrue();
        expect($method->getReturnType())->toBeNull();
    });

    test('all exceptions are serializable', function (): void {
        $exceptions = [
            new SecurityException('Security error'),
            new ValidationException('Validation error'),
        ];

        foreach ($exceptions as $exception) {
            // Test serialization
            $serialized = serialize($exception);
            expect($serialized)->toBeString();
            $unserialized = unserialize($serialized);
            expect($unserialized)->toBeInstanceOf($exception::class);
            /** @var ValidateLinksException $unserialized */
            expect($unserialized->getMessage())->toBe($exception->getMessage());
        }
    });

    test('all exceptions are json serializable', function (): void {
        $exceptions = [
            new SecurityException('Security error'),
            new ValidationException('Validation error'),
        ];

        foreach ($exceptions as $exception) {
            // Test JSON serialization
            $json = json_encode($exception->toArray());
            expect($json)->toBeString();
            assert(is_string($json));
            $decoded = json_decode($json, true);
            expect($decoded)->toBeArray();
            /** @var array<string, mixed> $decoded */
            expect($decoded['error_code'])->toBe($exception->getErrorCode());
        }
    });

    test('all exceptions have valid error code format', function (): void {
        $exceptions = [
            new SecurityException('Security error'),
            new ValidationException('Validation error'),
        ];

        foreach ($exceptions as $exception) {
            expect($exception->getErrorCode())->toMatch('/^[A-Z_]+$/');
        }
    });

    test('all exceptions have appropriate severity levels', function (): void {
        $securityException = new SecurityException('Security error');
        $validationException = new ValidationException('Validation error');

        // Security exceptions should be critical
        expect($securityException->getSeverity())->toBe('critical');

        // Validation exceptions should be medium
        expect($validationException->getSeverity())->toBe('medium');
    });

    test('exceptions can be distinguished by type', function (): void {
        $securityException = new SecurityException('Security error');
        $validationException = new ValidationException('Validation error');

        expect($securityException)->toBeInstanceOf(SecurityException::class);
        expect($securityException)->not->toBeInstanceOf(ValidationException::class);

        expect($validationException)->toBeInstanceOf(ValidationException::class);
        expect($validationException)->not->toBeInstanceOf(SecurityException::class);
    });

    test('exceptions preserve inheritance chain', function (): void {
        $exceptions = [
            new SecurityException('Security error'),
            new ValidationException('Validation error'),
        ];

        foreach ($exceptions as $exception) {
            expect($exception)->toBeInstanceOf(ValidateLinksException::class);
            expect($exception)->toBeInstanceOf(Exception::class);
            expect($exception)->toBeInstanceOf(Throwable::class);
        }
    });

    test('exception factory methods work correctly', function (): void {
        // Test SecurityException factory methods
        $threatException = SecurityException::threatDetected('url', 'malware', []);
        $blacklistException = SecurityException::blacklistedDomain('domain.com');
        $sslException = SecurityException::sslViolation('url', 'error');

        expect($threatException)->toBeInstanceOf(SecurityException::class);
        expect($blacklistException)->toBeInstanceOf(SecurityException::class);
        expect($sslException)->toBeInstanceOf(SecurityException::class);

        // Test ValidationException factory methods
        $linkException = ValidationException::linkValidationFailed('url', 404, 1000);
        $fileException = ValidationException::fileProcessingFailed('file', 'error');
        $timeoutException = ValidationException::validationTimeout('url', 30);

        expect($linkException)->toBeInstanceOf(ValidationException::class);
        expect($fileException)->toBeInstanceOf(ValidationException::class);
        expect($timeoutException)->toBeInstanceOf(ValidationException::class);
    });

});
