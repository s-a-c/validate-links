<?php

declare(strict_types=1);

use App\Exceptions\ConfigurationException;
use App\Exceptions\SecurityException;
use App\Exceptions\ValidateLinksException;
use App\Exceptions\ValidationException;
use Tests\Support\ErrorMessageTestHelper;

describe('Contextual Information Validation', function () {
    describe('context data completeness', function () {
        it('validates ValidationException context contains required fields', function () {
            $url = 'https://example.com/test';
            $statusCode = 404;
            $responseTime = 1500;
            
            $exception = ValidationException::linkValidationFailed($url, $statusCode, $responseTime);
            $context = $exception->getContext();
            
            expect($context)->toHaveKey('url', $url)
                ->and($context)->toHaveKey('status_code', $statusCode)
                ->and($context)->toHaveKey('response_time', $responseTime)
                ->and($context)->toHaveKey('validation_type', 'link_validation');
        });

        it('validates ConfigurationException context contains required fields', function () {
            $filePath = '/config/test.json';
            $exception = ConfigurationException::missingFile($filePath);
            $context = $exception->getContext();
            
            expect($context)->toHaveKey('file_path', $filePath)
                ->and($context)->toHaveKey('error_type', 'missing_file');
        });

        it('validates SecurityException context contains required fields', function () {
            $domain = 'malicious.com';
            $exception = SecurityException::blacklistedDomain($domain);
            $context = $exception->getContext();
            
            expect($context)->toHaveKey('domain', $domain)
                ->and($context)->toHaveKey('threat_type', 'blacklisted_domain')
                ->and($context)->toHaveKey('risk_level', 'high');
        });

        it('validates context data types are correct', function () {
            $exception = ValidationException::linkValidationFailed('https://test.com', 404, 1500);
            $context = $exception->getContext();
            
            expect($context['url'])->toBeString()
                ->and($context['status_code'])->toBeInt()
                ->and($context['response_time'])->toBeInt()
                ->and($context['validation_type'])->toBeString();
        });
    });

    describe('context data accuracy', function () {
        it('validates context data matches exception parameters', function () {
            $testCases = [
                [
                    'url' => 'https://example.com/api/test',
                    'status_code' => 500,
                    'response_time' => 3000,
                ],
                [
                    'url' => 'https://test.org/path?param=value',
                    'status_code' => 403,
                    'response_time' => 250,
                ],
                [
                    'url' => 'https://localhost:8080/endpoint',
                    'status_code' => 200,
                    'response_time' => 50,
                ],
            ];

            foreach ($testCases as $testCase) {
                $exception = ValidationException::linkValidationFailed(
                    $testCase['url'],
                    $testCase['status_code'],
                    $testCase['response_time']
                );
                
                $expectedContext = [
                    'url' => $testCase['url'],
                    'status_code' => $testCase['status_code'],
                    'response_time' => $testCase['response_time'],
                    'validation_type' => 'link_validation',
                ];
                
                $results = ErrorMessageTestHelper::validateContextualInformation($exception, $expectedContext);
                
                expect($results['valid'])->toBeTrue()
                    ->and($results['context_complete'])->toBeTrue()
                    ->and($results['context_accurate'])->toBeTrue();
            }
        });

        it('validates file processing context accuracy', function () {
            $testCases = [
                ['file_path' => '/docs/readme.md', 'error' => 'Markdown parsing failed'],
                ['file_path' => '/config/settings.json', 'error' => 'Invalid JSON syntax'],
                ['file_path' => '/data/large-file.txt', 'error' => 'File too large'],
            ];

            foreach ($testCases as $testCase) {
                $exception = ValidationException::fileProcessingFailed(
                    $testCase['file_path'],
                    $testCase['error']
                );
                
                $expectedContext = [
                    'file_path' => $testCase['file_path'],
                    'error' => $testCase['error'],
                    'validation_type' => 'file_processing',
                ];
                
                $results = ErrorMessageTestHelper::validateContextualInformation($exception, $expectedContext);
                
                expect($results['valid'])->toBeTrue()
                    ->and($results['context_accurate'])->toBeTrue();
            }
        });

        it('validates timeout context accuracy', function () {
            $testCases = [
                ['url' => 'https://slow-server.com', 'timeout' => 30],
                ['url' => 'https://api.timeout-test.com', 'timeout' => 60],
                ['url' => 'https://heavy-processing.com', 'timeout' => 120],
            ];

            foreach ($testCases as $testCase) {
                $exception = ValidationException::validationTimeout(
                    $testCase['url'],
                    $testCase['timeout']
                );
                
                $expectedContext = [
                    'url' => $testCase['url'],
                    'timeout_seconds' => $testCase['timeout'],
                    'validation_type' => 'timeout',
                ];
                
                $results = ErrorMessageTestHelper::validateContextualInformation($exception, $expectedContext);
                
                expect($results['valid'])->toBeTrue()
                    ->and($results['context_accurate'])->toBeTrue();
            }
        });
    });

    describe('error metadata validation', function () {
        it('validates error codes are consistent with exception types', function () {
            $testCases = [
                ['exception' => ValidationException::linkValidationFailed('https://test.com', 404, 100), 'expected_code' => 'VALIDATION_ERROR'],
                ['exception' => ConfigurationException::missingFile('/config/test.json'), 'expected_code' => 'CONFIGURATION_ERROR'],
                ['exception' => SecurityException::blacklistedDomain('evil.com'), 'expected_code' => 'SECURITY_VIOLATION'],
            ];

            foreach ($testCases as $testCase) {
                expect($testCase['exception']->getErrorCode())->toBe($testCase['expected_code']);
            }
        });

        it('validates severity levels are appropriate for error types', function () {
            $testCases = [
                ['exception' => ValidationException::linkValidationFailed('https://test.com', 404, 100), 'expected_severity' => 'medium'],
                ['exception' => ConfigurationException::missingFile('/config/test.json'), 'expected_severity' => 'high'],
                ['exception' => SecurityException::blacklistedDomain('evil.com'), 'expected_severity' => 'critical'],
            ];

            foreach ($testCases as $testCase) {
                $results = ErrorMessageTestHelper::validateContextualInformation($testCase['exception']);
                
                expect($testCase['exception']->getSeverity())->toBe($testCase['expected_severity'])
                    ->and($results['severity_appropriate'])->toBeTrue();
            }
        });

        it('validates error classification consistency', function () {
            $validationErrors = [
                ValidationException::linkValidationFailed('https://test.com', 404, 100),
                ValidationException::fileProcessingFailed('/test/file.md', 'Error'),
                ValidationException::validationTimeout('https://test.com', 30),
            ];

            foreach ($validationErrors as $exception) {
                expect($exception->getErrorCode())->toBe('VALIDATION_ERROR')
                    ->and($exception->getSeverity())->toBe('medium');
            }

            $configurationErrors = [
                ConfigurationException::missingFile('/config/test.json'),
                ConfigurationException::invalidFormat('/config/test.json', 'JSON', 'invalid'),
                ConfigurationException::missingRequiredKey('api_key', '/config/test.json'),
                ConfigurationException::invalidValue('timeout', 'invalid', 'integer'),
            ];

            foreach ($configurationErrors as $exception) {
                expect($exception->getErrorCode())->toBe('CONFIGURATION_ERROR')
                    ->and($exception->getSeverity())->toBe('high');
            }

            $securityErrors = [
                SecurityException::threatDetected('https://malicious.com', 'malware', ['risk' => 'high']),
                SecurityException::blacklistedDomain('evil.com'),
                SecurityException::sslViolation('https://insecure.com', 'Certificate expired'),
            ];

            foreach ($securityErrors as $exception) {
                expect($exception->getErrorCode())->toBe('SECURITY_VIOLATION')
                    ->and($exception->getSeverity())->toBe('critical');
            }
        });
    });

    describe('correlation and tracing information', function () {
        it('validates exception serialization includes context', function () {
            $exception = ValidationException::linkValidationFailed('https://test.com', 404, 100);
            $serialized = $exception->__serialize();
            
            expect($serialized)->toHaveKey('context')
                ->and($serialized['context'])->toBeArray()
                ->and($serialized['context'])->toHaveKey('url')
                ->and($serialized['context'])->toHaveKey('status_code')
                ->and($serialized['context'])->toHaveKey('response_time')
                ->and($serialized['context'])->toHaveKey('validation_type');
        });

        it('validates exception array conversion includes all context', function () {
            $exception = ValidationException::linkValidationFailed('https://test.com', 404, 100);
            $array = $exception->toArray();
            
            expect($array)->toHaveKey('error_code', 'VALIDATION_ERROR')
                ->and($array)->toHaveKey('message')
                ->and($array)->toHaveKey('severity', 'medium')
                ->and($array)->toHaveKey('file')
                ->and($array)->toHaveKey('line')
                ->and($array)->toHaveKey('context')
                ->and($array['context'])->toBeArray();
        });

        it('validates context preservation across exception operations', function () {
            $originalException = ValidationException::linkValidationFailed('https://test.com', 404, 100);
            $originalContext = $originalException->getContext();
            
            // Test serialization and unserialization
            $serialized = $originalException->__serialize();
            $newException = new ValidationException();
            $newException->__unserialize($serialized);
            
            $newContext = $newException->getContext();
            
            expect($newContext)->toBe($originalContext);
        });
    });

    describe('context validation edge cases', function () {
        it('handles empty and null values in context', function () {
            $testCases = [
                ['url' => '', 'status_code' => 404, 'response_time' => 100],
                ['url' => 'https://test.com', 'status_code' => 0, 'response_time' => 100],
                ['url' => 'https://test.com', 'status_code' => 404, 'response_time' => 0],
            ];

            foreach ($testCases as $testCase) {
                $exception = ValidationException::linkValidationFailed(
                    $testCase['url'],
                    $testCase['status_code'],
                    $testCase['response_time']
                );
                
                $context = $exception->getContext();
                
                expect($context)->toHaveKey('url')
                    ->and($context)->toHaveKey('status_code')
                    ->and($context)->toHaveKey('response_time')
                    ->and($context['url'])->toBe($testCase['url'])
                    ->and($context['status_code'])->toBe($testCase['status_code'])
                    ->and($context['response_time'])->toBe($testCase['response_time']);
            }
        });

        it('handles special characters in context values', function () {
            $specialCases = [
                ['url' => 'https://example.com/path with spaces', 'error' => 'Error with "quotes"'],
                ['url' => 'https://тест.рф/путь', 'error' => 'Unicode error: 测试错误'],
                ['url' => 'https://example.com/emoji/🚀', 'error' => 'Emoji error: 🚨'],
            ];

            foreach ($specialCases as $case) {
                $exception = ValidationException::fileProcessingFailed($case['url'], $case['error']);
                $context = $exception->getContext();
                
                expect($context['file_path'])->toBe($case['url'])
                    ->and($context['error'])->toBe($case['error'])
                    ->and(mb_check_encoding($context['file_path'], 'UTF-8'))->toBeTrue()
                    ->and(mb_check_encoding($context['error'], 'UTF-8'))->toBeTrue();
            }
        });

        it('handles very large context values', function () {
            $longUrl = 'https://example.com/' . str_repeat('very-long-path/', 100);
            $longError = str_repeat('Very detailed error description. ', 50);
            
            $exception = ValidationException::fileProcessingFailed($longUrl, $longError);
            $context = $exception->getContext();
            
            expect($context['file_path'])->toBe($longUrl)
                ->and($context['error'])->toBe($longError)
                ->and(strlen($context['file_path']))->toBeGreaterThan(1000)
                ->and(strlen($context['error']))->toBeGreaterThan(1000);
        });

        it('handles numeric edge cases in context', function () {
            $edgeCases = [
                ['status_code' => PHP_INT_MAX, 'response_time' => PHP_INT_MAX],
                ['status_code' => PHP_INT_MIN, 'response_time' => PHP_INT_MIN],
                ['status_code' => 0, 'response_time' => 0],
                ['status_code' => -1, 'response_time' => -1],
            ];

            foreach ($edgeCases as $case) {
                $exception = ValidationException::linkValidationFailed(
                    'https://test.com',
                    $case['status_code'],
                    $case['response_time']
                );
                
                $context = $exception->getContext();
                
                expect($context['status_code'])->toBe($case['status_code'])
                    ->and($context['response_time'])->toBe($case['response_time'])
                    ->and($context['status_code'])->toBeInt()
                    ->and($context['response_time'])->toBeInt();
            }
        });
    });

    describe('context validation with custom expectations', function () {
        it('validates context using ErrorMessageTestHelper with expected context', function () {
            $url = 'https://api.example.com/v1/users';
            $statusCode = 401;
            $responseTime = 750;
            
            $exception = ValidationException::linkValidationFailed($url, $statusCode, $responseTime);
            
            $expectedContext = [
                'url' => $url,
                'status_code' => $statusCode,
                'response_time' => $responseTime,
                'validation_type' => 'link_validation',
            ];
            
            $results = ErrorMessageTestHelper::validateContextualInformation($exception, $expectedContext);
            
            expect($results['valid'])->toBeTrue()
                ->and($results['errors'])->toBeEmpty()
                ->and($results['context_complete'])->toBeTrue()
                ->and($results['context_accurate'])->toBeTrue()
                ->and($results['severity_appropriate'])->toBeTrue();
        });

        it('detects context mismatches', function () {
            $exception = ValidationException::linkValidationFailed('https://test.com', 404, 100);
            
            $incorrectContext = [
                'url' => 'https://different.com', // Wrong URL
                'status_code' => 500, // Wrong status code
                'response_time' => 200, // Wrong response time
                'validation_type' => 'link_validation',
            ];
            
            $results = ErrorMessageTestHelper::validateContextualInformation($exception, $incorrectContext);
            
            expect($results['valid'])->toBeFalse()
                ->and($results['errors'])->not->toBeEmpty()
                ->and($results['context_accurate'])->toBeFalse();
            
            // Check specific error messages
            $errorMessages = implode(' ', $results['errors']);
            expect($errorMessages)->toContain('url')
                ->and($errorMessages)->toContain('status_code')
                ->and($errorMessages)->toContain('response_time');
        });

        it('detects missing context fields', function () {
            $exception = ValidationException::linkValidationFailed('https://test.com', 404, 100);
            
            $incompleteContext = [
                'url' => 'https://test.com',
                // Missing status_code, response_time, and validation_type
            ];
            
            $results = ErrorMessageTestHelper::validateContextualInformation($exception, $incompleteContext);
            
            expect($results['valid'])->toBeFalse()
                ->and($results['errors'])->not->toBeEmpty()
                ->and($results['context_complete'])->toBeFalse();
        });
    });

    describe('comprehensive context validation reporting', function () {
        it('generates comprehensive validation report for ValidationException', function () {
            $exception = ValidationException::linkValidationFailed('https://api.test.com', 404, 1500);
            
            $report = ErrorMessageTestHelper::generateValidationReport($exception);
            
            expect($report)->toHaveKey('timestamp')
                ->and($report)->toHaveKey('exception_class', ValidationException::class)
                ->and($report)->toHaveKey('message')
                ->and($report)->toHaveKey('error_code', 'VALIDATION_ERROR')
                ->and($report)->toHaveKey('severity', 'medium')
                ->and($report)->toHaveKey('validation_results')
                ->and($report)->toHaveKey('overall_score')
                ->and($report['overall_score'])->toBeGreaterThan(0);
        });

        it('generates comprehensive validation report for ConfigurationException', function () {
            $exception = ConfigurationException::missingFile('/config/important.json');
            
            $report = ErrorMessageTestHelper::generateValidationReport($exception);
            
            expect($report['exception_class'])->toBe(ConfigurationException::class)
                ->and($report['error_code'])->toBe('CONFIGURATION_ERROR')
                ->and($report['severity'])->toBe('high')
                ->and($report['validation_results'])->toHaveKey('contextual_info');
        });

        it('generates comprehensive validation report for SecurityException', function () {
            $exception = SecurityException::threatDetected('https://malicious.com', 'malware', ['risk' => 'high']);
            
            $report = ErrorMessageTestHelper::generateValidationReport($exception);
            
            expect($report['exception_class'])->toBe(SecurityException::class)
                ->and($report['error_code'])->toBe('SECURITY_VIOLATION')
                ->and($report['severity'])->toBe('critical')
                ->and($report['validation_results'])->toHaveKey('contextual_info');
        });

        it('includes actionable recommendations in validation reports', function () {
            $exception = ValidationException::linkValidationFailed('https://test.com', 404, 100);
            
            $report = ErrorMessageTestHelper::generateValidationReport($exception);
            
            expect($report)->toHaveKey('recommendations')
                ->and($report['recommendations'])->toBeArray();
            
            // Recommendations should be helpful and specific
            foreach ($report['recommendations'] as $recommendation) {
                expect($recommendation)->toBeString()
                    ->and(strlen($recommendation))->toBeGreaterThan(10);
            }
        });
    });
});