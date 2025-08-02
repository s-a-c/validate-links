<?php

declare(strict_types=1);

use App\Exceptions\SecurityException;
use App\Exceptions\ValidateLinksException;

describe('SecurityException', function () {
    describe('class structure', function () {
        it('extends ValidateLinksException', function () {
            $exception = new SecurityException('test');
            expect($exception)->toBeInstanceOf(ValidateLinksException::class);
        });

        it('is a final class', function () {
            $reflection = new ReflectionClass(SecurityException::class);
            expect($reflection->isFinal())->toBeTrue();
        });

        it('has proper namespace', function () {
            $reflection = new ReflectionClass(SecurityException::class);
            expect($reflection->getNamespaceName())->toBe('App\Exceptions');
        });

        it('uses strict types declaration', function () {
            $fileContent = file_get_contents(__DIR__.'/../../../app/Exceptions/SecurityException.php');
            expect($fileContent)->toContain('declare(strict_types=1);');
        });
    });

    describe('threatDetected static method', function () {
        it('creates exception for detected threat', function () {
            $url = 'https://malicious-site.com';
            $threatType = 'malware';
            $context = ['scanner' => 'antivirus', 'confidence' => 0.95];

            $exception = SecurityException::threatDetected($url, $threatType, $context);

            expect($exception)->toBeInstanceOf(SecurityException::class)
                ->and($exception->getMessage())->toBe("Security threat detected at {$url}: {$threatType}")
                ->and($exception->getCode())->toBe(0)
                ->and($exception->getPrevious())->toBeNull()
                ->and($exception->getContext())->toBe($context);
        });

        it('handles empty URL and threat type', function () {
            $url = '';
            $threatType = '';
            $context = [];

            $exception = SecurityException::threatDetected($url, $threatType, $context);

            expect($exception->getMessage())->toBe('Security threat detected at : ')
                ->and($exception->getContext())->toBe($context);
        });

        it('handles complex context data', function () {
            $url = 'https://test.com';
            $threatType = 'phishing';
            $context = [
                'detection_time' => '2023-01-01T12:00:00Z',
                'risk_score' => 8.5,
                'indicators' => ['suspicious_links', 'fake_login_form'],
                'metadata' => ['source' => 'threat_intel', 'verified' => true],
            ];

            $exception = SecurityException::threatDetected($url, $threatType, $context);

            expect($exception->getContext())->toBe($context);
        });

        it('handles special characters in URL and threat type', function () {
            $url = 'https://test.com/path?param=value&other=test';
            $threatType = 'SQL injection attempt';
            $context = ['payload' => "'; DROP TABLE users; --"];

            $exception = SecurityException::threatDetected($url, $threatType, $context);

            expect($exception->getMessage())->toContain($url)
                ->and($exception->getMessage())->toContain($threatType);
        });

        it('returns static instance', function () {
            $exception = SecurityException::threatDetected('https://test.com', 'malware', []);

            expect($exception)->toBeInstanceOf(SecurityException::class);
        });
    });

    describe('blacklistedDomain static method', function () {
        it('creates exception for blacklisted domain', function () {
            $domain = 'malicious-domain.com';

            $exception = SecurityException::blacklistedDomain($domain);

            expect($exception)->toBeInstanceOf(SecurityException::class)
                ->and($exception->getMessage())->toBe("Access denied: {$domain} is on the security blacklist")
                ->and($exception->getCode())->toBe(0)
                ->and($exception->getPrevious())->toBeNull();

            $context = $exception->getContext();
            expect($context)->toHaveKey('domain', $domain)
                ->and($context)->toHaveKey('threat_type', 'blacklisted_domain')
                ->and($context)->toHaveKey('risk_level', 'high');
        });

        it('handles empty domain', function () {
            $domain = '';

            $exception = SecurityException::blacklistedDomain($domain);

            expect($exception->getMessage())->toBe('Access denied:  is on the security blacklist')
                ->and($exception->getContext()['domain'])->toBe('');
        });

        it('handles domain with special characters', function () {
            $domain = 'test-domain.co.uk';

            $exception = SecurityException::blacklistedDomain($domain);

            expect($exception->getMessage())->toContain($domain)
                ->and($exception->getContext()['domain'])->toBe($domain);
        });

        it('handles international domain names', function () {
            $domain = 'тест.рф'; // Cyrillic domain

            $exception = SecurityException::blacklistedDomain($domain);

            expect($exception->getMessage())->toContain($domain)
                ->and($exception->getContext()['domain'])->toBe($domain);
        });

        it('sets correct context structure', function () {
            $domain = 'example.com';

            $exception = SecurityException::blacklistedDomain($domain);
            $context = $exception->getContext();

            expect($context)->toHaveCount(3)
                ->and($context['threat_type'])->toBe('blacklisted_domain')
                ->and($context['risk_level'])->toBe('high');
        });

        it('returns static instance', function () {
            $exception = SecurityException::blacklistedDomain('test.com');

            expect($exception)->toBeInstanceOf(SecurityException::class);
        });
    });

    describe('sslViolation static method', function () {
        it('creates exception for SSL violation', function () {
            $url = 'https://insecure-site.com';
            $sslError = 'Certificate expired';

            $exception = SecurityException::sslViolation($url, $sslError);

            expect($exception)->toBeInstanceOf(SecurityException::class)
                ->and($exception->getMessage())->toBe("SSL security violation for {$url}: {$sslError}")
                ->and($exception->getCode())->toBe(0)
                ->and($exception->getPrevious())->toBeNull();

            $context = $exception->getContext();
            expect($context)->toHaveKey('url', $url)
                ->and($context)->toHaveKey('ssl_error', $sslError)
                ->and($context)->toHaveKey('threat_type', 'ssl_violation')
                ->and($context)->toHaveKey('risk_level', 'medium');
        });

        it('handles empty URL and SSL error', function () {
            $url = '';
            $sslError = '';

            $exception = SecurityException::sslViolation($url, $sslError);

            expect($exception->getMessage())->toBe('SSL security violation for : ');
        });

        it('handles various SSL error types', function () {
            $testCases = [
                ['https://test1.com', 'Certificate expired'],
                ['https://test2.com', 'Self-signed certificate'],
                ['https://test3.com', 'Certificate chain incomplete'],
                ['https://test4.com', 'Hostname mismatch'],
                ['https://test5.com', 'Certificate revoked'],
            ];

            foreach ($testCases as [$url, $sslError]) {
                $exception = SecurityException::sslViolation($url, $sslError);

                expect($exception->getContext()['url'])->toBe($url)
                    ->and($exception->getContext()['ssl_error'])->toBe($sslError);
            }
        });

        it('handles complex URLs', function () {
            $url = 'https://subdomain.example.com:8443/path/to/resource?param=value#anchor';
            $sslError = 'Certificate validation failed';

            $exception = SecurityException::sslViolation($url, $sslError);

            expect($exception->getMessage())->toContain($url)
                ->and($exception->getContext()['url'])->toBe($url);
        });

        it('sets correct context structure', function () {
            $url = 'https://example.com';
            $sslError = 'Test error';

            $exception = SecurityException::sslViolation($url, $sslError);
            $context = $exception->getContext();

            expect($context)->toHaveCount(4)
                ->and($context['threat_type'])->toBe('ssl_violation')
                ->and($context['risk_level'])->toBe('medium');
        });

        it('returns static instance', function () {
            $exception = SecurityException::sslViolation('https://test.com', 'SSL error');

            expect($exception)->toBeInstanceOf(SecurityException::class);
        });
    });

    describe('getErrorCode method', function () {
        it('returns correct error code', function () {
            $exception = new SecurityException('test');

            expect($exception->getErrorCode())->toBe('SECURITY_VIOLATION');
        });

        it('returns same error code for all instances', function () {
            $exception1 = SecurityException::blacklistedDomain('test.com');
            $exception2 = SecurityException::sslViolation('https://test.com', 'error');
            $exception3 = SecurityException::threatDetected('https://test.com', 'malware', []);

            expect($exception1->getErrorCode())->toBe('SECURITY_VIOLATION')
                ->and($exception2->getErrorCode())->toBe('SECURITY_VIOLATION')
                ->and($exception3->getErrorCode())->toBe('SECURITY_VIOLATION');
        });
    });

    describe('getSeverity method', function () {
        it('returns correct severity', function () {
            $exception = new SecurityException('test');

            expect($exception->getSeverity())->toBe('critical');
        });

        it('returns same severity for all instances', function () {
            $exception1 = SecurityException::blacklistedDomain('test.com');
            $exception2 = SecurityException::sslViolation('https://test.com', 'error');
            $exception3 = SecurityException::threatDetected('https://test.com', 'malware', []);

            expect($exception1->getSeverity())->toBe('critical')
                ->and($exception2->getSeverity())->toBe('critical')
                ->and($exception3->getSeverity())->toBe('critical');
        });
    });

    describe('inheritance and interface compliance', function () {
        it('inherits from ValidateLinksException', function () {
            $exception = new SecurityException('test');

            expect($exception)->toBeInstanceOf(ValidateLinksException::class)
                ->and($exception)->toBeInstanceOf(Exception::class);
        });

        it('implements standard exception interface', function () {
            $exception = new SecurityException('test message', 123);

            expect($exception->getMessage())->toBe('test message')
                ->and($exception->getCode())->toBe(123);
        });

        it('supports exception chaining', function () {
            $previous = new Exception('Previous exception');
            $exception = new SecurityException('Current exception', 0, $previous);

            expect($exception->getPrevious())->toBe($previous);
        });
    });

    describe('PHPDoc annotations', function () {
        it('has proper class documentation', function () {
            $reflection = new ReflectionClass(SecurityException::class);
            $docComment = $reflection->getDocComment();

            expect($docComment)->toContain('Exception thrown when security violations are detected');
        });

        it('has proper method documentation', function () {
            $reflection = new ReflectionClass(SecurityException::class);

            $methods = [
                'threatDetected' => 'Create a security exception for a detected threat',
                'blacklistedDomain' => 'Create a security exception for a blacklisted domain',
                'sslViolation' => 'Create a security exception for SSL violations',
            ];

            foreach ($methods as $methodName => $expectedDoc) {
                $method = $reflection->getMethod($methodName);
                $docComment = $method->getDocComment();
                expect($docComment)->toContain($expectedDoc);
            }
        });

        it('has proper parameter documentation', function () {
            $reflection = new ReflectionClass(SecurityException::class);

            $threatDetectedMethod = $reflection->getMethod('threatDetected');
            $docComment = $threatDetectedMethod->getDocComment();

            expect($docComment)->toContain('@param  string  $url')
                ->and($docComment)->toContain('@param  string  $threatType')
                ->and($docComment)->toContain('@param  array<string, mixed>  $context');
        });
    });

    describe('edge cases and error handling', function () {
        it('handles very long URLs', function () {
            $longUrl = 'https://example.com/'.str_repeat('very-long-path/', 100);
            $exception = SecurityException::blacklistedDomain($longUrl);

            expect($exception->getMessage())->toContain($longUrl);
        });

        it('handles unicode characters in messages', function () {
            $unicodeDomain = 'тест.рф';
            $exception = SecurityException::blacklistedDomain($unicodeDomain);

            expect($exception->getMessage())->toContain($unicodeDomain);
        });

        it('handles special characters in threat types', function () {
            $threatType = 'XSS: <script>alert("test")</script>';
            $exception = SecurityException::threatDetected('https://test.com', $threatType, []);

            expect($exception->getMessage())->toContain($threatType);
        });

        it('handles large context arrays', function () {
            $largeContext = [];
            for ($i = 0; $i < 100; $i++) {
                $largeContext["indicator_{$i}"] = "value_{$i}";
            }

            $exception = SecurityException::threatDetected('https://test.com', 'malware', $largeContext);

            expect($exception->getContext())->toBe($largeContext);
        });

        it('handles null values in context gracefully', function () {
            $context = [
                'valid_key' => 'valid_value',
                'null_key' => null,
                'empty_key' => '',
                'zero_key' => 0,
                'false_key' => false,
            ];

            $exception = SecurityException::threatDetected('https://test.com', 'test', $context);

            expect($exception->getContext())->toBe($context);
        });
    });

    describe('static factory method consistency', function () {
        it('all static methods return SecurityException instances', function () {
            $methods = [
                fn () => SecurityException::threatDetected('https://test.com', 'malware', []),
                fn () => SecurityException::blacklistedDomain('malicious.com'),
                fn () => SecurityException::sslViolation('https://test.com', 'SSL error'),
            ];

            foreach ($methods as $method) {
                $exception = $method();
                expect($exception)->toBeInstanceOf(SecurityException::class);
            }
        });

        it('all static methods set code to 0 by default', function () {
            $methods = [
                fn () => SecurityException::threatDetected('https://test.com', 'malware', []),
                fn () => SecurityException::blacklistedDomain('malicious.com'),
                fn () => SecurityException::sslViolation('https://test.com', 'SSL error'),
            ];

            foreach ($methods as $method) {
                $exception = $method();
                expect($exception->getCode())->toBe(0);
            }
        });

        it('all static methods set previous to null by default', function () {
            $methods = [
                fn () => SecurityException::threatDetected('https://test.com', 'malware', []),
                fn () => SecurityException::blacklistedDomain('malicious.com'),
                fn () => SecurityException::sslViolation('https://test.com', 'SSL error'),
            ];

            foreach ($methods as $method) {
                $exception = $method();
                expect($exception->getPrevious())->toBeNull();
            }
        });

        it('all static methods include context data', function () {
            $threatException = SecurityException::threatDetected('https://test.com', 'malware', ['custom' => 'data']);
            $blacklistException = SecurityException::blacklistedDomain('malicious.com');
            $sslException = SecurityException::sslViolation('https://test.com', 'SSL error');

            expect($threatException->getContext())->toHaveKey('custom', 'data');
            expect($blacklistException->getContext())->toHaveKey('domain', 'malicious.com');
            expect($sslException->getContext())->toHaveKey('url', 'https://test.com');
        });
    });

    describe('security context validation', function () {
        it('blacklistedDomain sets correct risk level', function () {
            $exception = SecurityException::blacklistedDomain('test.com');

            expect($exception->getContext()['risk_level'])->toBe('high');
        });

        it('sslViolation sets correct risk level', function () {
            $exception = SecurityException::sslViolation('https://test.com', 'error');

            expect($exception->getContext()['risk_level'])->toBe('medium');
        });

        it('threatDetected preserves custom context', function () {
            $customContext = [
                'risk_level' => 'critical',
                'confidence' => 0.99,
                'source' => 'threat_intelligence',
            ];

            $exception = SecurityException::threatDetected('https://test.com', 'malware', $customContext);

            expect($exception->getContext())->toBe($customContext);
        });

        it('context includes threat type classification', function () {
            $blacklistException = SecurityException::blacklistedDomain('test.com');
            $sslException = SecurityException::sslViolation('https://test.com', 'error');

            expect($blacklistException->getContext()['threat_type'])->toBe('blacklisted_domain');
            expect($sslException->getContext()['threat_type'])->toBe('ssl_violation');
        });
    });

    describe('message formatting', function () {
        it('formats threat detection messages consistently', function () {
            $url = 'https://example.com';
            $threatType = 'malware';

            $exception = SecurityException::threatDetected($url, $threatType, []);

            expect($exception->getMessage())->toBe("Security threat detected at {$url}: {$threatType}");
        });

        it('formats blacklist messages consistently', function () {
            $domain = 'malicious.com';

            $exception = SecurityException::blacklistedDomain($domain);

            expect($exception->getMessage())->toBe("Access denied: {$domain} is on the security blacklist");
        });

        it('formats SSL violation messages consistently', function () {
            $url = 'https://example.com';
            $sslError = 'Certificate expired';

            $exception = SecurityException::sslViolation($url, $sslError);

            expect($exception->getMessage())->toBe("SSL security violation for {$url}: {$sslError}");
        });
    });
});
