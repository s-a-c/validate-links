<?php

declare(strict_types=1);

use App\Exceptions\ConfigurationException;
use App\Exceptions\SecurityException;
use App\Exceptions\ValidateLinksException;
use App\Exceptions\ValidationException;
use Tests\Support\ErrorMessageTestHelper;

describe('Error Message Validation', function () {
    describe('exact text matching', function () {
        it('validates exact error message text for ValidationException', function () {
            $url = 'https://example.com/broken';
            $statusCode = 404;
            $responseTime = 1500;
            
            $exception = ValidationException::linkValidationFailed($url, $statusCode, $responseTime);
            $expectedMessage = "Link validation failed for {$url} (HTTP {$statusCode}, {$responseTime}ms)";
            
            $isValid = ErrorMessageTestHelper::validateExactText(
                $exception->getMessage(),
                $expectedMessage
            );
            
            expect($isValid)->toBeTrue()
                ->and($exception->getMessage())->toBe($expectedMessage);
        });

        it('validates exact error message text for ConfigurationException', function () {
            $filePath = '/config/missing.json';
            $exception = ConfigurationException::missingFile($filePath);
            $expectedMessage = "Configuration file not found: {$filePath}";
            
            $isValid = ErrorMessageTestHelper::validateExactText(
                $exception->getMessage(),
                $expectedMessage
            );
            
            expect($isValid)->toBeTrue()
                ->and($exception->getMessage())->toBe($expectedMessage);
        });

        it('validates exact error message text for SecurityException', function () {
            $domain = 'malicious.com';
            $exception = SecurityException::blacklistedDomain($domain);
            $expectedMessage = "Access denied: {$domain} is on the security blacklist";
            
            $isValid = ErrorMessageTestHelper::validateExactText(
                $exception->getMessage(),
                $expectedMessage
            );
            
            expect($isValid)->toBeTrue()
                ->and($exception->getMessage())->toBe($expectedMessage);
        });

        it('handles case-insensitive matching when specified', function () {
            $exception = ValidationException::linkValidationFailed('https://test.com', 404, 100);
            $expectedMessage = strtoupper($exception->getMessage());
            
            $isValid = ErrorMessageTestHelper::validateExactText(
                $exception->getMessage(),
                $expectedMessage,
                false // case-insensitive
            );
            
            expect($isValid)->toBeTrue();
        });

        it('fails validation for incorrect exact text', function () {
            $exception = ValidationException::linkValidationFailed('https://test.com', 404, 100);
            $incorrectMessage = 'This is not the correct message';
            
            $isValid = ErrorMessageTestHelper::validateExactText(
                $exception->getMessage(),
                $incorrectMessage
            );
            
            expect($isValid)->toBeFalse();
        });
    });

    describe('pattern-based validation', function () {
        it('validates link validation failure pattern', function () {
            $exception = ValidationException::linkValidationFailed('https://example.com', 500, 2000);
            
            $isValid = ErrorMessageTestHelper::validateStandardPattern(
                $exception->getMessage(),
                'link_validation_failed'
            );
            
            expect($isValid)->toBeTrue();
        });

        it('validates file processing failure pattern', function () {
            $exception = ValidationException::fileProcessingFailed('/path/to/file.md', 'Parse error');
            
            $isValid = ErrorMessageTestHelper::validateStandardPattern(
                $exception->getMessage(),
                'file_processing_failed'
            );
            
            expect($isValid)->toBeTrue();
        });

        it('validates validation timeout pattern', function () {
            $exception = ValidationException::validationTimeout('https://slow.com', 30);
            
            $isValid = ErrorMessageTestHelper::validateStandardPattern(
                $exception->getMessage(),
                'validation_timeout'
            );
            
            expect($isValid)->toBeTrue();
        });

        it('validates configuration error patterns', function () {
            $testCases = [
                [
                    'exception' => ConfigurationException::missingFile('/config/test.json'),
                    'pattern' => 'configuration_missing_file',
                ],
                [
                    'exception' => ConfigurationException::invalidFormat('/config/test.json', 'JSON', 'invalid content'),
                    'pattern' => 'configuration_invalid_format',
                ],
                [
                    'exception' => ConfigurationException::missingRequiredKey('api_key', '/config/test.json'),
                    'pattern' => 'configuration_missing_key',
                ],
                [
                    'exception' => ConfigurationException::invalidValue('timeout', 'invalid', 'integer'),
                    'pattern' => 'configuration_invalid_value',
                ],
            ];

            foreach ($testCases as $testCase) {
                $isValid = ErrorMessageTestHelper::validateStandardPattern(
                    $testCase['exception']->getMessage(),
                    $testCase['pattern']
                );
                
                expect($isValid)->toBeTrue("Pattern {$testCase['pattern']} should match");
            }
        });

        it('validates security error patterns', function () {
            $testCases = [
                [
                    'exception' => SecurityException::threatDetected('https://malicious.com', 'malware', ['risk' => 'high']),
                    'pattern' => 'security_threat_detected',
                ],
                [
                    'exception' => SecurityException::blacklistedDomain('evil.com'),
                    'pattern' => 'security_blacklisted_domain',
                ],
                [
                    'exception' => SecurityException::sslViolation('https://insecure.com', 'Certificate expired'),
                    'pattern' => 'security_ssl_violation',
                ],
            ];

            foreach ($testCases as $testCase) {
                $isValid = ErrorMessageTestHelper::validateStandardPattern(
                    $testCase['exception']->getMessage(),
                    $testCase['pattern']
                );
                
                expect($isValid)->toBeTrue("Pattern {$testCase['pattern']} should match");
            }
        });

        it('validates custom regex patterns', function () {
            $message = 'Error code: ERR-001, Status: FAILED';
            $pattern = '/^Error code: ERR-\d+, Status: (FAILED|SUCCESS)$/';
            
            $isValid = ErrorMessageTestHelper::validatePattern($message, $pattern);
            
            expect($isValid)->toBeTrue();
        });

        it('fails validation for incorrect patterns', function () {
            $exception = ValidationException::linkValidationFailed('https://test.com', 404, 100);
            
            $isValid = ErrorMessageTestHelper::validateStandardPattern(
                $exception->getMessage(),
                'configuration_missing_file' // Wrong pattern
            );
            
            expect($isValid)->toBeFalse();
        });
    });

    describe('message template validation', function () {
        it('validates validation error template compliance', function () {
            $message = 'Link validation failed for https://test.com (HTTP 404, 100ms)';
            $placeholders = [
                'action' => 'Link validation',
                'target' => 'https://test.com',
                'details' => 'HTTP 404, 100ms',
            ];
            
            $results = ErrorMessageTestHelper::validateTemplate(
                $message,
                'validation_error',
                $placeholders
            );
            
            expect($results['valid'])->toBeTrue()
                ->and($results['template_compliance'])->toBeTrue()
                ->and($results['errors'])->toBeEmpty();
        });

        it('validates configuration error template compliance', function () {
            $message = 'Configuration error: Invalid configuration file: /config/test.json';
            $placeholders = [
                'description' => 'Invalid configuration file: /config/test.json',
            ];
            
            $results = ErrorMessageTestHelper::validateTemplate(
                $message,
                'configuration_error',
                $placeholders
            );
            
            expect($results['valid'])->toBeTrue()
                ->and($results['template_compliance'])->toBeTrue()
                ->and($results['errors'])->toBeEmpty();
        });

        it('validates security error template compliance', function () {
            $message = 'Security violation: SSL certificate expired';
            $placeholders = [
                'severity' => 'violation',
                'description' => 'SSL certificate expired',
            ];
            
            $results = ErrorMessageTestHelper::validateTemplate(
                $message,
                'security_error',
                $placeholders
            );
            
            expect($results['valid'])->toBeTrue()
                ->and($results['template_compliance'])->toBeTrue()
                ->and($results['errors'])->toBeEmpty();
        });

        it('detects missing required placeholders', function () {
            $message = 'Validation failed for https://test.com';
            $placeholders = [
                'action' => 'Validation',
                'target' => 'https://test.com',
                // Missing 'details' placeholder
            ];
            
            $results = ErrorMessageTestHelper::validateTemplate(
                $message,
                'validation_error',
                $placeholders
            );
            
            expect($results['valid'])->toBeFalse()
                ->and($results['errors'])->toContain('Missing required placeholder: details');
        });

        it('handles optional placeholders correctly', function () {
            $message = 'Configuration error: Missing required key';
            $placeholders = [
                'description' => 'Missing required key',
                'file_path' => '/config/test.json', // Optional placeholder
            ];
            
            $results = ErrorMessageTestHelper::validateTemplate(
                $message,
                'configuration_error',
                $placeholders
            );
            
            expect($results['valid'])->toBeTrue()
                ->and($results['placeholder_usage']['file_path'])->toBe('optional_present');
        });
    });

    describe('parameter substitution validation', function () {
        it('validates successful parameter substitution', function () {
            $template = 'Error processing {file} at line {line}: {error}';
            $parameters = [
                'file' => 'config.json',
                'line' => '15',
                'error' => 'Invalid syntax',
            ];
            $expectedResult = 'Error processing config.json at line 15: Invalid syntax';
            
            $results = ErrorMessageTestHelper::validateParameterSubstitution(
                $template,
                $parameters,
                $expectedResult
            );
            
            expect($results['valid'])->toBeTrue()
                ->and($results['substituted_message'])->toBe($expectedResult)
                ->and($results['unused_parameters'])->toBeEmpty()
                ->and($results['missing_parameters'])->toBeEmpty();
        });

        it('detects missing parameters', function () {
            $template = 'Error processing {file} at line {line}: {error}';
            $parameters = [
                'file' => 'config.json',
                // Missing 'line' and 'error' parameters
            ];
            $expectedResult = 'Error processing config.json at line {line}: {error}';
            
            $results = ErrorMessageTestHelper::validateParameterSubstitution(
                $template,
                $parameters,
                $expectedResult
            );
            
            expect($results['valid'])->toBeFalse()
                ->and($results['missing_parameters'])->toContain('line')
                ->and($results['missing_parameters'])->toContain('error');
        });

        it('detects unused parameters', function () {
            $template = 'Error processing {file}';
            $parameters = [
                'file' => 'config.json',
                'line' => '15', // Unused parameter
                'extra' => 'value', // Unused parameter
            ];
            $expectedResult = 'Error processing config.json';
            
            $results = ErrorMessageTestHelper::validateParameterSubstitution(
                $template,
                $parameters,
                $expectedResult
            );
            
            expect($results['valid'])->toBeTrue()
                ->and($results['unused_parameters'])->toContain('line')
                ->and($results['unused_parameters'])->toContain('extra');
        });

        it('handles complex parameter substitution', function () {
            $template = 'Validation failed for {url} (HTTP {status_code}, {response_time}ms)';
            $parameters = [
                'url' => 'https://example.com/test',
                'status_code' => '404',
                'response_time' => '1500',
            ];
            $expectedResult = 'Validation failed for https://example.com/test (HTTP 404, 1500ms)';
            
            $results = ErrorMessageTestHelper::validateParameterSubstitution(
                $template,
                $parameters,
                $expectedResult
            );
            
            expect($results['valid'])->toBeTrue()
                ->and($results['substituted_message'])->toBe($expectedResult);
        });
    });

    describe('error message formatting consistency', function () {
        it('validates consistent formatting across ValidationException methods', function () {
            $testCases = [
                ValidationException::linkValidationFailed('https://test.com', 404, 100),
                ValidationException::fileProcessingFailed('/test/file.md', 'Parse error'),
                ValidationException::validationTimeout('https://slow.com', 30),
            ];

            foreach ($testCases as $exception) {
                $message = $exception->getMessage();
                
                // Check basic formatting rules
                expect($message)->not->toBeEmpty()
                    ->and(strlen($message))->toBeGreaterThan(10)
                    ->and(strlen($message))->toBeLessThan(500)
                    ->and($message)->not->toStartWith(' ')
                    ->and($message)->not->toEndWith(' ');
            }
        });

        it('validates consistent formatting across ConfigurationException methods', function () {
            $testCases = [
                ConfigurationException::missingFile('/config/test.json'),
                ConfigurationException::invalidFormat('/config/test.json', 'JSON', 'invalid'),
                ConfigurationException::missingRequiredKey('api_key', '/config/test.json'),
                ConfigurationException::invalidValue('timeout', 'invalid', 'integer'),
            ];

            foreach ($testCases as $exception) {
                $message = $exception->getMessage();
                
                // Check basic formatting rules
                expect($message)->not->toBeEmpty()
                    ->and(strlen($message))->toBeGreaterThan(10)
                    ->and(strlen($message))->toBeLessThan(500)
                    ->and($message)->not->toStartWith(' ')
                    ->and($message)->not->toEndWith(' ');
            }
        });

        it('validates consistent formatting across SecurityException methods', function () {
            $testCases = [
                SecurityException::threatDetected('https://malicious.com', 'malware', ['risk' => 'high']),
                SecurityException::blacklistedDomain('evil.com'),
                SecurityException::sslViolation('https://insecure.com', 'Certificate expired'),
            ];

            foreach ($testCases as $exception) {
                $message = $exception->getMessage();
                
                // Check basic formatting rules
                expect($message)->not->toBeEmpty()
                    ->and(strlen($message))->toBeGreaterThan(10)
                    ->and(strlen($message))->toBeLessThan(500)
                    ->and($message)->not->toStartWith(' ')
                    ->and($message)->not->toEndWith(' ');
            }
        });
    });

    describe('error message categorization', function () {
        it('validates error message categories are properly assigned', function () {
            $testCases = [
                ['exception' => ValidationException::linkValidationFailed('https://test.com', 404, 100), 'expected_code' => 'VALIDATION_ERROR'],
                ['exception' => ConfigurationException::missingFile('/config/test.json'), 'expected_code' => 'CONFIGURATION_ERROR'],
                ['exception' => SecurityException::blacklistedDomain('evil.com'), 'expected_code' => 'SECURITY_VIOLATION'],
            ];

            foreach ($testCases as $testCase) {
                expect($testCase['exception']->getErrorCode())->toBe($testCase['expected_code']);
            }
        });

        it('validates error severity levels are appropriate', function () {
            $testCases = [
                ['exception' => ValidationException::linkValidationFailed('https://test.com', 404, 100), 'expected_severity' => 'medium'],
                ['exception' => ConfigurationException::missingFile('/config/test.json'), 'expected_severity' => 'high'],
                ['exception' => SecurityException::blacklistedDomain('evil.com'), 'expected_severity' => 'critical'],
            ];

            foreach ($testCases as $testCase) {
                expect($testCase['exception']->getSeverity())->toBe($testCase['expected_severity']);
            }
        });
    });

    describe('edge cases and special characters', function () {
        it('handles URLs with special characters', function () {
            $specialUrls = [
                'https://example.com/path with spaces',
                'https://example.com/path?param=value&other=test',
                'https://example.com/path#anchor',
                'https://тест.рф/путь',
                'https://example.com/path/with/unicode/测试',
            ];

            foreach ($specialUrls as $url) {
                $exception = ValidationException::linkValidationFailed($url, 404, 100);
                $message = $exception->getMessage();
                
                expect($message)->toContain($url)
                    ->and(mb_check_encoding($message, 'UTF-8'))->toBeTrue();
            }
        });

        it('handles file paths with special characters', function () {
            $specialPaths = [
                '/path/with spaces/file.md',
                '/path/with/unicode/测试.md',
                '/path/with/émojis/🚀.md',
                '/very/long/path/' . str_repeat('nested/', 20) . 'file.md',
            ];

            foreach ($specialPaths as $path) {
                $exception = ValidationException::fileProcessingFailed($path, 'Test error');
                $message = $exception->getMessage();
                
                expect($message)->toContain($path)
                    ->and(mb_check_encoding($message, 'UTF-8'))->toBeTrue();
            }
        });

        it('handles error messages with special characters', function () {
            $specialErrors = [
                'Error with "quotes" and \'apostrophes\'',
                'Error with unicode: 测试错误',
                'Error with émojis: 🚨 Critical failure',
                'Error with newlines:\nLine 1\nLine 2',
            ];

            foreach ($specialErrors as $error) {
                $exception = ValidationException::fileProcessingFailed('/test/file.md', $error);
                $message = $exception->getMessage();
                
                expect($message)->toContain($error)
                    ->and(mb_check_encoding($message, 'UTF-8'))->toBeTrue();
            }
        });
    });

    describe('message length validation', function () {
        it('validates message length is within acceptable bounds', function () {
            $testCases = [
                ValidationException::linkValidationFailed('https://test.com', 404, 100),
                ConfigurationException::missingFile('/config/test.json'),
                SecurityException::blacklistedDomain('evil.com'),
            ];

            foreach ($testCases as $exception) {
                $message = $exception->getMessage();
                $length = strlen($message);
                
                expect($length)->toBeGreaterThan(10)
                    ->and($length)->toBeLessThan(500);
            }
        });

        it('handles very long URLs gracefully', function () {
            $longUrl = 'https://example.com/' . str_repeat('very-long-path/', 50);
            $exception = ValidationException::linkValidationFailed($longUrl, 404, 100);
            $message = $exception->getMessage();
            
            expect($message)->toContain($longUrl)
                ->and(strlen($message))->toBeLessThan(2000); // Reasonable upper bound
        });

        it('handles very long error descriptions gracefully', function () {
            $longError = str_repeat('Very detailed error description. ', 20);
            $exception = ValidationException::fileProcessingFailed('/test/file.md', $longError);
            $message = $exception->getMessage();
            
            expect($message)->toContain($longError)
                ->and(strlen($message))->toBeLessThan(2000); // Reasonable upper bound
        });
    });
});