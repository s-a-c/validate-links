<?php

declare(strict_types=1);

use App\Exceptions\ValidateLinksException;
use App\Exceptions\ValidationException;

describe('ValidationException', function () {
    describe('class structure', function () {
        it('extends ValidateLinksException', function () {
            $exception = new ValidationException('test');
            expect($exception)->toBeInstanceOf(ValidateLinksException::class);
        });

        it('is a final class', function () {
            $reflection = new ReflectionClass(ValidationException::class);
            expect($reflection->isFinal())->toBeTrue();
        });

        it('has proper namespace', function () {
            $reflection = new ReflectionClass(ValidationException::class);
            expect($reflection->getNamespaceName())->toBe('App\Exceptions');
        });

        it('uses strict types declaration', function () {
            $fileContent = file_get_contents(__DIR__.'/../../../app/Exceptions/ValidationException.php');
            expect($fileContent)->toContain('declare(strict_types=1);');
        });
    });

    describe('linkValidationFailed static method', function () {
        it('creates exception for link validation failure', function () {
            $url = 'https://example.com/broken-link';
            $statusCode = 404;
            $responseTime = 1500;

            $exception = ValidationException::linkValidationFailed($url, $statusCode, $responseTime);

            expect($exception)->toBeInstanceOf(ValidationException::class)
                ->and($exception->getMessage())->toBe("Link validation failed for {$url} (HTTP {$statusCode}, {$responseTime}ms)")
                ->and($exception->getCode())->toBe(0)
                ->and($exception->getPrevious())->toBeNull();

            $context = $exception->getContext();
            expect($context)->toHaveKey('url', $url)
                ->and($context)->toHaveKey('status_code', $statusCode)
                ->and($context)->toHaveKey('response_time', $responseTime)
                ->and($context)->toHaveKey('validation_type', 'link_validation');
        });

        it('handles different HTTP status codes', function () {
            $testCases = [
                ['https://test1.com', 400, 100],
                ['https://test2.com', 401, 200],
                ['https://test3.com', 403, 300],
                ['https://test4.com', 404, 400],
                ['https://test5.com', 500, 500],
                ['https://test6.com', 502, 600],
                ['https://test7.com', 503, 700],
            ];

            foreach ($testCases as [$url, $statusCode, $responseTime]) {
                $exception = ValidationException::linkValidationFailed($url, $statusCode, $responseTime);

                expect($exception->getContext()['status_code'])->toBe($statusCode)
                    ->and($exception->getMessage())->toContain("HTTP {$statusCode}");
            }
        });

        it('handles zero and negative response times', function () {
            $exception1 = ValidationException::linkValidationFailed('https://test.com', 404, 0);
            $exception2 = ValidationException::linkValidationFailed('https://test.com', 404, -100);

            expect($exception1->getContext()['response_time'])->toBe(0)
                ->and($exception2->getContext()['response_time'])->toBe(-100);
        });

        it('handles very large response times', function () {
            $largeResponseTime = 999999;
            $exception = ValidationException::linkValidationFailed('https://test.com', 404, $largeResponseTime);

            expect($exception->getContext()['response_time'])->toBe($largeResponseTime)
                ->and($exception->getMessage())->toContain("{$largeResponseTime}ms");
        });

        it('handles empty URL', function () {
            $url = '';
            $exception = ValidationException::linkValidationFailed($url, 404, 100);

            expect($exception->getMessage())->toBe('Link validation failed for  (HTTP 404, 100ms)')
                ->and($exception->getContext()['url'])->toBe('');
        });

        it('handles complex URLs', function () {
            $url = 'https://subdomain.example.com:8080/path/to/resource?param=value&other=test#anchor';
            $exception = ValidationException::linkValidationFailed($url, 404, 100);

            expect($exception->getMessage())->toContain($url)
                ->and($exception->getContext()['url'])->toBe($url);
        });

        it('returns static instance', function () {
            $exception = ValidationException::linkValidationFailed('https://test.com', 404, 100);

            expect($exception)->toBeInstanceOf(ValidationException::class);
        });
    });

    describe('fileProcessingFailed static method', function () {
        it('creates exception for file processing failure', function () {
            $filePath = '/path/to/document.md';
            $error = 'File not readable';

            $exception = ValidationException::fileProcessingFailed($filePath, $error);

            expect($exception)->toBeInstanceOf(ValidationException::class)
                ->and($exception->getMessage())->toBe("File processing failed for {$filePath}: {$error}")
                ->and($exception->getCode())->toBe(0)
                ->and($exception->getPrevious())->toBeNull();

            $context = $exception->getContext();
            expect($context)->toHaveKey('file_path', $filePath)
                ->and($context)->toHaveKey('error', $error)
                ->and($context)->toHaveKey('validation_type', 'file_processing');
        });

        it('handles empty file path and error', function () {
            $filePath = '';
            $error = '';

            $exception = ValidationException::fileProcessingFailed($filePath, $error);

            expect($exception->getMessage())->toBe('File processing failed for : ')
                ->and($exception->getContext()['file_path'])->toBe('')
                ->and($exception->getContext()['error'])->toBe('');
        });

        it('handles various file types', function () {
            $testCases = [
                ['/docs/readme.md', 'Markdown parsing error'],
                ['/config/settings.json', 'Invalid JSON format'],
                ['/data/content.html', 'HTML validation failed'],
                ['/assets/style.css', 'CSS syntax error'],
                ['/scripts/app.js', 'JavaScript parsing error'],
            ];

            foreach ($testCases as [$filePath, $error]) {
                $exception = ValidationException::fileProcessingFailed($filePath, $error);

                expect($exception->getContext()['file_path'])->toBe($filePath)
                    ->and($exception->getContext()['error'])->toBe($error);
            }
        });

        it('handles long file paths', function () {
            $longPath = '/very/long/path/'.str_repeat('nested/directory/', 20).'file.md';
            $exception = ValidationException::fileProcessingFailed($longPath, 'Error');

            expect($exception->getMessage())->toContain($longPath)
                ->and($exception->getContext()['file_path'])->toBe($longPath);
        });

        it('handles special characters in file path', function () {
            $filePath = '/path/with spaces/file@#$.md';
            $error = 'Special character handling error';

            $exception = ValidationException::fileProcessingFailed($filePath, $error);

            expect($exception->getMessage())->toContain($filePath)
                ->and($exception->getContext()['file_path'])->toBe($filePath);
        });

        it('handles unicode characters in file path and error', function () {
            $filePath = '/путь/к/файлу.md'; // Cyrillic path
            $error = 'Ошибка обработки файла'; // Cyrillic error

            $exception = ValidationException::fileProcessingFailed($filePath, $error);

            expect($exception->getMessage())->toContain($filePath)
                ->and($exception->getMessage())->toContain($error);
        });

        it('returns static instance', function () {
            $exception = ValidationException::fileProcessingFailed('/test/file.md', 'Error');

            expect($exception)->toBeInstanceOf(ValidationException::class);
        });
    });

    describe('validationTimeout static method', function () {
        it('creates exception for validation timeout', function () {
            $url = 'https://slow-server.com';
            $timeout = 30;

            $exception = ValidationException::validationTimeout($url, $timeout);

            expect($exception)->toBeInstanceOf(ValidationException::class)
                ->and($exception->getMessage())->toBe("Validation timeout for {$url} after {$timeout} seconds")
                ->and($exception->getCode())->toBe(0)
                ->and($exception->getPrevious())->toBeNull();

            $context = $exception->getContext();
            expect($context)->toHaveKey('url', $url)
                ->and($context)->toHaveKey('timeout_seconds', $timeout)
                ->and($context)->toHaveKey('validation_type', 'timeout');
        });

        it('handles different timeout values', function () {
            $testCases = [
                ['https://test1.com', 5],
                ['https://test2.com', 10],
                ['https://test3.com', 30],
                ['https://test4.com', 60],
                ['https://test5.com', 120],
                ['https://test6.com', 300],
            ];

            foreach ($testCases as [$url, $timeout]) {
                $exception = ValidationException::validationTimeout($url, $timeout);

                expect($exception->getContext()['timeout_seconds'])->toBe($timeout)
                    ->and($exception->getMessage())->toContain("after {$timeout} seconds");
            }
        });

        it('handles zero timeout', function () {
            $exception = ValidationException::validationTimeout('https://test.com', 0);

            expect($exception->getContext()['timeout_seconds'])->toBe(0)
                ->and($exception->getMessage())->toContain('after 0 seconds');
        });

        it('handles negative timeout', function () {
            $exception = ValidationException::validationTimeout('https://test.com', -10);

            expect($exception->getContext()['timeout_seconds'])->toBe(-10)
                ->and($exception->getMessage())->toContain('after -10 seconds');
        });

        it('handles very large timeout values', function () {
            $largeTimeout = 999999;
            $exception = ValidationException::validationTimeout('https://test.com', $largeTimeout);

            expect($exception->getContext()['timeout_seconds'])->toBe($largeTimeout)
                ->and($exception->getMessage())->toContain("after {$largeTimeout} seconds");
        });

        it('handles empty URL', function () {
            $url = '';
            $exception = ValidationException::validationTimeout($url, 30);

            expect($exception->getMessage())->toBe('Validation timeout for  after 30 seconds')
                ->and($exception->getContext()['url'])->toBe('');
        });

        it('handles complex URLs', function () {
            $url = 'https://api.example.com/v1/endpoint?timeout=30&retry=3';
            $exception = ValidationException::validationTimeout($url, 30);

            expect($exception->getMessage())->toContain($url)
                ->and($exception->getContext()['url'])->toBe($url);
        });

        it('returns static instance', function () {
            $exception = ValidationException::validationTimeout('https://test.com', 30);

            expect($exception)->toBeInstanceOf(ValidationException::class);
        });
    });

    describe('getErrorCode method', function () {
        it('returns correct error code', function () {
            $exception = new ValidationException('test');

            expect($exception->getErrorCode())->toBe('VALIDATION_ERROR');
        });

        it('returns same error code for all instances', function () {
            $exception1 = ValidationException::linkValidationFailed('https://test.com', 404, 100);
            $exception2 = ValidationException::fileProcessingFailed('/test/file.md', 'Error');
            $exception3 = ValidationException::validationTimeout('https://test.com', 30);

            expect($exception1->getErrorCode())->toBe('VALIDATION_ERROR')
                ->and($exception2->getErrorCode())->toBe('VALIDATION_ERROR')
                ->and($exception3->getErrorCode())->toBe('VALIDATION_ERROR');
        });
    });

    describe('getSeverity method', function () {
        it('returns correct severity', function () {
            $exception = new ValidationException('test');

            expect($exception->getSeverity())->toBe('medium');
        });

        it('returns same severity for all instances', function () {
            $exception1 = ValidationException::linkValidationFailed('https://test.com', 404, 100);
            $exception2 = ValidationException::fileProcessingFailed('/test/file.md', 'Error');
            $exception3 = ValidationException::validationTimeout('https://test.com', 30);

            expect($exception1->getSeverity())->toBe('medium')
                ->and($exception2->getSeverity())->toBe('medium')
                ->and($exception3->getSeverity())->toBe('medium');
        });
    });

    describe('inheritance and interface compliance', function () {
        it('inherits from ValidateLinksException', function () {
            $exception = new ValidationException('test');

            expect($exception)->toBeInstanceOf(ValidateLinksException::class)
                ->and($exception)->toBeInstanceOf(Exception::class);
        });

        it('implements standard exception interface', function () {
            $exception = new ValidationException('test message', 123);

            expect($exception->getMessage())->toBe('test message')
                ->and($exception->getCode())->toBe(123);
        });

        it('supports exception chaining', function () {
            $previous = new Exception('Previous exception');
            $exception = new ValidationException('Current exception', 0, $previous);

            expect($exception->getPrevious())->toBe($previous);
        });
    });

    describe('PHPDoc annotations', function () {
        it('has proper class documentation', function () {
            $reflection = new ReflectionClass(ValidationException::class);
            $docComment = $reflection->getDocComment();

            expect($docComment)->toContain('Exception thrown when link validation fails');
        });

        it('has proper method documentation', function () {
            $reflection = new ReflectionClass(ValidationException::class);

            $methods = [
                'linkValidationFailed' => 'Create a validation exception for link validation failure',
                'fileProcessingFailed' => 'Create a validation exception for file processing failure',
                'validationTimeout' => 'Create a validation exception for validation timeout',
            ];

            foreach ($methods as $methodName => $expectedDoc) {
                $method = $reflection->getMethod($methodName);
                $docComment = $method->getDocComment();
                expect($docComment)->toContain($expectedDoc);
            }
        });

        it('has proper parameter documentation', function () {
            $reflection = new ReflectionClass(ValidationException::class);

            $linkValidationMethod = $reflection->getMethod('linkValidationFailed');
            $docComment = $linkValidationMethod->getDocComment();

            expect($docComment)->toContain('@param  string  $url')
                ->and($docComment)->toContain('@param  int  $statusCode')
                ->and($docComment)->toContain('@param  int  $responseTime');
        });
    });

    describe('edge cases and error handling', function () {
        it('handles very long URLs', function () {
            $longUrl = 'https://example.com/'.str_repeat('very-long-path/', 100);
            $exception = ValidationException::linkValidationFailed($longUrl, 404, 100);

            expect($exception->getMessage())->toContain($longUrl);
        });

        it('handles unicode characters in URLs', function () {
            $unicodeUrl = 'https://тест.рф/путь';
            $exception = ValidationException::linkValidationFailed($unicodeUrl, 404, 100);

            expect($exception->getMessage())->toContain($unicodeUrl);
        });

        it('handles extreme HTTP status codes', function () {
            $testCases = [
                [100, 'Continue'],
                [200, 'OK'],
                [404, 'Not Found'],
                [500, 'Internal Server Error'],
                [999, 'Custom Status'],
            ];

            foreach ($testCases as [$statusCode, $description]) {
                $exception = ValidationException::linkValidationFailed('https://test.com', $statusCode, 100);

                expect($exception->getContext()['status_code'])->toBe($statusCode)
                    ->and($exception->getMessage())->toContain("HTTP {$statusCode}");
            }
        });

        it('handles very long error messages', function () {
            $longError = str_repeat('Very detailed error description. ', 50);
            $exception = ValidationException::fileProcessingFailed('/test/file.md', $longError);

            expect($exception->getMessage())->toContain($longError);
        });

        it('handles special characters in error messages', function () {
            $specialError = 'Error with special chars: <>&"\'';
            $exception = ValidationException::fileProcessingFailed('/test/file.md', $specialError);

            expect($exception->getMessage())->toContain($specialError);
        });
    });

    describe('static factory method consistency', function () {
        it('all static methods return ValidationException instances', function () {
            $methods = [
                fn () => ValidationException::linkValidationFailed('https://test.com', 404, 100),
                fn () => ValidationException::fileProcessingFailed('/test/file.md', 'Error'),
                fn () => ValidationException::validationTimeout('https://test.com', 30),
            ];

            foreach ($methods as $method) {
                $exception = $method();
                expect($exception)->toBeInstanceOf(ValidationException::class);
            }
        });

        it('all static methods set code to 0 by default', function () {
            $methods = [
                fn () => ValidationException::linkValidationFailed('https://test.com', 404, 100),
                fn () => ValidationException::fileProcessingFailed('/test/file.md', 'Error'),
                fn () => ValidationException::validationTimeout('https://test.com', 30),
            ];

            foreach ($methods as $method) {
                $exception = $method();
                expect($exception->getCode())->toBe(0);
            }
        });

        it('all static methods set previous to null by default', function () {
            $methods = [
                fn () => ValidationException::linkValidationFailed('https://test.com', 404, 100),
                fn () => ValidationException::fileProcessingFailed('/test/file.md', 'Error'),
                fn () => ValidationException::validationTimeout('https://test.com', 30),
            ];

            foreach ($methods as $method) {
                $exception = $method();
                expect($exception->getPrevious())->toBeNull();
            }
        });

        it('all static methods include validation_type in context', function () {
            $linkException = ValidationException::linkValidationFailed('https://test.com', 404, 100);
            $fileException = ValidationException::fileProcessingFailed('/test/file.md', 'Error');
            $timeoutException = ValidationException::validationTimeout('https://test.com', 30);

            expect($linkException->getContext()['validation_type'])->toBe('link_validation');
            expect($fileException->getContext()['validation_type'])->toBe('file_processing');
            expect($timeoutException->getContext()['validation_type'])->toBe('timeout');
        });
    });

    describe('validation context validation', function () {
        it('linkValidationFailed includes all required context fields', function () {
            $exception = ValidationException::linkValidationFailed('https://test.com', 404, 100);
            $context = $exception->getContext();

            expect($context)->toHaveCount(4)
                ->and($context)->toHaveKey('url')
                ->and($context)->toHaveKey('status_code')
                ->and($context)->toHaveKey('response_time')
                ->and($context)->toHaveKey('validation_type');
        });

        it('fileProcessingFailed includes all required context fields', function () {
            $exception = ValidationException::fileProcessingFailed('/test/file.md', 'Error');
            $context = $exception->getContext();

            expect($context)->toHaveCount(3)
                ->and($context)->toHaveKey('file_path')
                ->and($context)->toHaveKey('error')
                ->and($context)->toHaveKey('validation_type');
        });

        it('validationTimeout includes all required context fields', function () {
            $exception = ValidationException::validationTimeout('https://test.com', 30);
            $context = $exception->getContext();

            expect($context)->toHaveCount(3)
                ->and($context)->toHaveKey('url')
                ->and($context)->toHaveKey('timeout_seconds')
                ->and($context)->toHaveKey('validation_type');
        });
    });

    describe('message formatting', function () {
        it('formats link validation messages consistently', function () {
            $url = 'https://example.com';
            $statusCode = 404;
            $responseTime = 1500;

            $exception = ValidationException::linkValidationFailed($url, $statusCode, $responseTime);

            expect($exception->getMessage())->toBe("Link validation failed for {$url} (HTTP {$statusCode}, {$responseTime}ms)");
        });

        it('formats file processing messages consistently', function () {
            $filePath = '/path/to/file.md';
            $error = 'File not found';

            $exception = ValidationException::fileProcessingFailed($filePath, $error);

            expect($exception->getMessage())->toBe("File processing failed for {$filePath}: {$error}");
        });

        it('formats timeout messages consistently', function () {
            $url = 'https://example.com';
            $timeout = 30;

            $exception = ValidationException::validationTimeout($url, $timeout);

            expect($exception->getMessage())->toBe("Validation timeout for {$url} after {$timeout} seconds");
        });
    });
});
