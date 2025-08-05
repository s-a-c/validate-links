<?php

declare(strict_types=1);

use App\Services\SecurityValidationService;

use Tests\Support\BoundaryConditionValidator;
use Tests\Support\EdgeCaseDataGenerator;
use Tests\Support\ExceptionTestingHelpers;
use Tests\Support\ResourceLimitSimulator;

/**
 * Comprehensive edge case tests for SecurityValidationService.
 * 
 * Tests null inputs, empty collections, malformed data, extreme security scenarios,
 * and boundary conditions to ensure robust security validation under all conditions.
 * 
 * @package Tests\Unit\Services
 * @author Roo
 * @since 1.0.0
 */

describe('SecurityValidationService Edge Cases', function () {
    beforeEach(function () {
        $this->service = new SecurityValidationService();
        
        // Initialize edge case utilities
        EdgeCaseDataGenerator::reset();
        BoundaryConditionValidator::initialize();
        ResourceLimitSimulator::initialize();
    });
    
    afterEach(function () {
        BoundaryConditionValidator::reset();
        ResourceLimitSimulator::cleanup();
    });
    
    describe('Null Input Validation', function () {
        it('validates URL with null inputs', function () {
            $nullInputs = EdgeCaseDataGenerator::generateNullAndEmptyInputs()['null_values'];
            
            $results = BoundaryConditionValidator::validateNullSafety(
                function ($input) {
                    return $this->service->validateUrl($input);
                },
                $nullInputs
            );
            
            foreach ($results as $inputName => $result) {
                if ($inputName === 'null') {
                    // Null should throw InvalidArgumentException
                    expect($result['null_safe'])->toBeFalse();
                    expect($result['exception']['class'])->toBe(InvalidArgumentException::class);
                    expect($result['exception']['message'])->toContain('URL cannot be empty');
                } elseif ($inputName === 'empty_string' || $inputName === 'zero_string') {
                    // Empty string and '0' should throw InvalidArgumentException
                    expect($result['null_safe'])->toBeFalse();
                    expect($result['exception']['class'])->toBe(InvalidArgumentException::class);
                } else {
                    // Other inputs should be handled gracefully
                    expect($result['null_safe'])->toBeTrue();
                    expect($result['result'])->toBeBool();
                }
            }
        });
        
        it('validates path with null inputs', function () {
            $nullInputs = EdgeCaseDataGenerator::generateNullAndEmptyInputs()['null_values'];
            
            $results = BoundaryConditionValidator::validateNullSafety(
                function ($input) {
                    return $this->service->validatePath($input);
                },
                $nullInputs
            );
            
            foreach ($results as $inputName => $result) {
                if ($inputName === 'null') {
                    // Null should throw InvalidArgumentException
                    expect($result['null_safe'])->toBeFalse();
                    expect($result['exception']['class'])->toBe(InvalidArgumentException::class);
                    expect($result['exception']['message'])->toContain('Path cannot be empty');
                } elseif ($inputName === 'empty_string' || $inputName === 'zero_string') {
                    // Empty string and '0' should throw InvalidArgumentException
                    expect($result['null_safe'])->toBeFalse();
                    expect($result['exception']['class'])->toBe(InvalidArgumentException::class);
                } else {
                    // Other inputs should be handled gracefully
                    expect($result['null_safe'])->toBeTrue();
                    expect($result['result'])->toBeBool();
                }
            }
        });
    });
    
    describe('Extreme URL Validation', function () {
        it('handles extreme URL validation scenarios', function () {
            $extremeUrls = EdgeCaseDataGenerator::generateExtremeUrls(['type' => 'all', 'count' => 30]);
            
            foreach ($extremeUrls as $url) {
                $result = $this->service->validateUrl($url);
                
                expect($result)->toBeBool();
                
                // Very long URLs should be rejected for security
                if (strlen($url) > 2048) {
                    expect($result)->toBeFalse("Very long URL should be rejected: " . substr($url, 0, 100) . '...');
                }
            }
        });
        
        it('handles malformed URL validation', function () {
            $malformedData = EdgeCaseDataGenerator::generateMalformedData(['type' => 'urls']);
            
            foreach ($malformedData['urls'] as $malformedUrl) {
                $result = $this->service->validateUrl($malformedUrl);
                
                expect($result)->toBeBool();
                expect($result)->toBeFalse("Malformed URL should be rejected: {$malformedUrl}");
            }
        });
        
        it('handles invalid character validation in URLs', function () {
            $invalidCharData = EdgeCaseDataGenerator::generateInvalidCharacterData();
            
            foreach ($invalidCharData as $invalidUrl) {
                $result = $this->service->validateUrl('https://example.com/' . $invalidUrl);
                
                expect($result)->toBeBool();
                // URLs with control characters should be rejected
                expect($result)->toBeFalse('URL with invalid characters should be rejected');
            }
        });
        
        it('detects control characters in URLs', function () {
            $controlCharUrls = [
                "https://example.com/path\x00null",
                "https://example.com/path\rnewline",
                "https://example.com/path\nnewline",
                "https://example.com/path\ttab",
                "https://example.com/path\x01control",
                "https://example.com/path\x1fcontrol",
            ];
            
            foreach ($controlCharUrls as $url) {
                $result = $this->service->validateUrl($url);
                
                expect($result)->toBeFalse("URL with control characters should be rejected: " . bin2hex($url));
            }
        });
    });
    
    describe('Path Validation', function () {
        it('handles malformed path validation', function () {
            $malformedData = EdgeCaseDataGenerator::generateMalformedData(['type' => 'paths']);
            
            foreach ($malformedData['paths'] as $malformedPath) {
                $result = $this->service->validatePath($malformedPath);
                
                expect($result)->toBeBool();
                expect($result)->toBeFalse("Malformed path should be rejected: {$malformedPath}");
            }
        });
        
        it('detects path traversal attacks', function () {
            $traversalPaths = [
                '../../../etc/passwd',
                '..\\..\\..\\windows\\system32\\config\\sam',
                '/var/log/../../../etc/passwd',
                'normal/path/../../etc/passwd',
                './../../etc/passwd',
                '..%2F..%2F..%2Fetc%2Fpasswd',
                '..%252F..%252F..%252Fetc%252Fpasswd',
            ];
            
            foreach ($traversalPaths as $path) {
                $result = $this->service->validatePath($path);
                
                expect($result)->toBeFalse("Path traversal should be rejected: {$path}");
            }
        });
        
        it('detects blocked path attempts', function () {
            $blockedPaths = [
                '/etc/passwd',
                '/var/log/auth.log',
                '/usr/bin/sudo',
                '/bin/bash',
                '/sbin/init',
                '/root/.ssh/id_rsa',
                '/home/user/.ssh/id_rsa',
            ];
            
            foreach ($blockedPaths as $path) {
                $result = $this->service->validatePath($path);
                
                expect($result)->toBeFalse("Blocked path should be rejected: {$path}");
            }
        });
        
        it('handles temporary path validation in test environment', function () {
            $tempDir = sys_get_temp_dir();
            $tempFile = $tempDir . '/test_security_validation.tmp';
            
            try {
                // Create temporary file
                file_put_contents($tempFile, 'test content');
                
                // Temporary paths should be allowed in test environment
                $result = $this->service->validatePath($tempFile);
                
                expect($result)->toBeTrue('Temporary paths should be allowed in test environment');
                
            } finally {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }
        });
    });
    
    describe('Protocol Validation', function () {
        it('validates protocol edge cases', function () {
            $protocolTests = [
                // Valid protocols
                'http' => true,
                'https' => true,
                'HTTP' => true,
                'HTTPS' => true,
                
                // Invalid protocols
                'ftp' => false,
                'file' => false,
                'javascript' => false,
                'data' => false,
                'vbscript' => false,
                'mailto' => false,
                'tel' => false,
                
                // Edge cases
                '' => false,
                'h ttp' => false,
                'http:' => false,
                'http://' => false,
                'custom-protocol' => false,
            ];
            
            foreach ($protocolTests as $protocol => $expected) {
                $result = $this->service->isProtocolAllowed($protocol);
                
                expect($result)->toBe($expected, "Protocol validation failed for: {$protocol}");
            }
        });
    });
    
    describe('Domain Validation', function () {
        it('handles domain blocking edge cases', function () {
            $domainTests = [
                // Should not be blocked (empty blocked list by default)
                'example.com' => false,
                'test.org' => false,
                'github.com' => false,
                
                // Edge cases
                '' => false,
                '.' => false,
                '.com' => false,
                'example.' => false,
                'EXAMPLE.COM' => false, // Case insensitive
                
                // Special domains
                'localhost' => false,
                '127.0.0.1' => false,
                '[::1]' => false,
            ];
            
            foreach ($domainTests as $domain => $expected) {
                $result = $this->service->isDomainBlocked($domain);
                
                expect($result)->toBe($expected, "Domain blocking failed for: {$domain}");
            }
        });
        
        it('handles Unicode and international domains', function () {
            $unicodeDomains = [
                'https://测试.中国',
                'https://пример.рф',
                'https://テスト.jp',
                'https://مثال.السعودية',
                'https://xn--nxasmq6b.xn--j6w193g', // Punycode
                'https://example.com/测试路径',
                'https://example.com/пример',
            ];
            
            foreach ($unicodeDomains as $domain) {
                $result = $this->service->validateUrl($domain);
                
                expect($result)->toBeBool();
                // Unicode domains should be handled gracefully
            }
        });
    });
    
    describe('IP Address Validation', function () {
        it('validates IP address edge cases', function () {
            $ipTests = [
                // IPv4 addresses
                'https://192.168.1.1' => true,
                'https://127.0.0.1' => true,
                'https://10.0.0.1' => true,
                'https://172.16.0.1' => true,
                'https://8.8.8.8' => true,
                
                // IPv6 addresses
                'https://[::1]' => true,
                'https://[2001:db8::1]' => true,
                'https://[fe80::1]' => true,
                
                // Invalid IP addresses
                'https://256.256.256.256' => false,
                'https://192.168.1' => false,
                'https://[invalid:ipv6]' => false,
            ];
            
            foreach ($ipTests as $url => $shouldBeValid) {
                $result = $this->service->validateUrl($url);
                
                expect($result)->toBeBool();
                
                if (!$shouldBeValid) {
                    expect($result)->toBeFalse("Invalid IP should be rejected: {$url}");
                }
            }
        });
    });
    
    describe('File Size Validation', function () {
        it('handles file size validation edge cases', function () {
            // Test with non-existent file
            $result = $this->service->validateFileSize('/nonexistent/file.txt');
            expect($result)->toBeFalse();
            
            // Create temporary files with different sizes
            $tempDir = sys_get_temp_dir();
            $testFiles = [];
            
            try {
                // Small file
                $smallFile = $tempDir . '/small_test.txt';
                file_put_contents($smallFile, 'small content');
                $testFiles[] = $smallFile;
                
                // Large file (but within limit)
                $largeFile = $tempDir . '/large_test.txt';
                file_put_contents($largeFile, str_repeat('x', 1024 * 1024)); // 1MB
                $testFiles[] = $largeFile;
                
                // Test validation
                expect($this->service->validateFileSize($smallFile))->toBeTrue();
                expect($this->service->validateFileSize($largeFile))->toBeTrue();
                
            } finally {
                // Clean up test files
                foreach ($testFiles as $file) {
                    if (file_exists($file)) {
                        unlink($file);
                    }
                }
            }
        });
    });
    
    describe('Security Configuration', function () {
        it('retrieves security configuration correctly', function () {
            $config = $this->service->getSecurityConfig();
            
            expect($config)->toBeArray();
            expect($config)->toHaveKey('blocked_domains');
            expect($config)->toHaveKey('allowed_protocols');
            expect($config)->toHaveKey('max_file_size');
            expect($config)->toHaveKey('blocked_paths');
            
            expect($config['blocked_domains'])->toBeArray();
            expect($config['allowed_protocols'])->toBeArray();
            expect($config['max_file_size'])->toBeInt();
            expect($config['blocked_paths'])->toBeArray();
            
            // Verify default values
            expect($config['allowed_protocols'])->toContain('http');
            expect($config['allowed_protocols'])->toContain('https');
            expect($config['max_file_size'])->toBeGreaterThan(0);
        });
    });
    
    describe('Concurrent and Performance Testing', function () {
        it('handles concurrent security validation', function () {
            $concurrencyScenario = EdgeCaseDataGenerator::generateConcurrencyScenarios([
                'scenario' => 'basic',
                'threads' => 5
            ]);
            
            $testUrl = 'https://example.com/test';
            
            $results = BoundaryConditionValidator::validateConcurrentAccess(
                function () use ($testUrl) {
                    return $this->service->validateUrl($testUrl);
                },
                $concurrencyScenario
            );
            
            expect($results)->toHaveKey('analysis');
            $analysis = $results['analysis'];
            
            expect($analysis['total_accesses'])->toBeGreaterThan(0);
            expect($analysis['successful_accesses'])->toBeGreaterThanOrEqual(0);
            
            // Security validation should be thread-safe
            expect($analysis['race_conditions_detected'])->toBe(0, 'Security validation should be thread-safe');
        });
        
        it('handles memory pressure during security validation', function () {
            try {
                // Simulate moderate memory pressure
                ResourceLimitSimulator::simulateMemoryPressure([
                    'target_mb' => 30,
                    'gradual' => true
                ]);
                
                $testUrl = 'https://example.com/test';
                $result = $this->service->validateUrl($testUrl);
                
                expect($result)->toBeBool();
                
                // Security validation should still work under memory pressure
                expect($result)->toBeTrue();
                
            } finally {
                ResourceLimitSimulator::releaseMemoryPressure();
            }
        });
    });
    
    describe('String Boundary Validation', function () {
        it('validates string boundary conditions', function () {
            $stringBoundaries = EdgeCaseDataGenerator::generateNullAndEmptyInputs()['boundary_strings'];
            
            // Test URL validation with boundary strings
            $urlResults = BoundaryConditionValidator::validateStringBoundaries(
                function ($input) {
                    return $this->service->validateUrl('https://example.com/' . $input);
                },
                $stringBoundaries
            );
            
            foreach ($urlResults as $testName => $result) {
                expect($result['handles_string'])->toBeTrue("Failed to handle URL string boundary: {$testName}");
                expect($result['result'])->toBeBool();
            }
            
            // Test path validation with boundary strings
            $pathResults = BoundaryConditionValidator::validateStringBoundaries(
                function ($input) {
                    return $this->service->validatePath('/tmp/' . $input);
                },
                $stringBoundaries
            );
            
            foreach ($pathResults as $testName => $result) {
                expect($result['handles_string'])->toBeTrue("Failed to handle path string boundary: {$testName}");
                expect($result['result'])->toBeBool();
            }
        });
    });
    
    describe('Error Recovery and Validation Summary', function () {
        it('handles security validation error recovery', function () {
            // Test recovery from invalid input
            ExceptionTestingHelpers::testErrorRecovery(
                function () {
                    // This should throw an exception
                    return $this->service->validateUrl(null);
                },
                function ($exception) {
                    // Recovery should handle the exception gracefully
                    return false; // Secure default
                },
                false
            );
            
            // Test graceful degradation
            $fallbackResult = ExceptionTestingHelpers::testGracefulDegradation(
                function () {
                    throw new RuntimeException('Simulated security failure');
                },
                false // Secure default
            );
            
            expect($fallbackResult)->toBeFalse();
        });
        
        it('provides comprehensive security validation summary', function () {
            $summary = BoundaryConditionValidator::getValidationSummary();
            
            expect($summary)->toHaveKey('total_tests');
            expect($summary)->toHaveKey('passed_tests');
            expect($summary)->toHaveKey('failed_tests');
            expect($summary)->toHaveKey('success_rate');
            
            // Security validation should have high success rate for proper error handling
            expect($summary['success_rate'])->toBeGreaterThanOrEqual(80, 'Security validation should handle edge cases well');
        });
    });
});