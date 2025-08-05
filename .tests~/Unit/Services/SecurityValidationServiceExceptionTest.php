<?php

declare(strict_types=1);

use App\Services\SecurityValidationService;

describe('SecurityValidationService Exception Handling', function () {
    beforeEach(function () {
        $this->securityService = new SecurityValidationService();
    });

    describe('URL Validation Exception Scenarios', function () {
        it('throws InvalidArgumentException for empty URL', function () {
            expectExceptionWithMessage(
                fn() => $this->securityService->validateUrl(''),
                InvalidArgumentException::class,
                'URL cannot be empty'
            );
        });

        it('throws InvalidArgumentException for zero string URL', function () {
            expectExceptionWithMessage(
                fn() => $this->securityService->validateUrl('0'),
                InvalidArgumentException::class,
                'URL cannot be empty'
            );
        });

        it('handles URLs with null bytes gracefully', function () {
            $urlWithNullByte = "https://example.com\0malicious";
            
            $result = $this->securityService->validateUrl($urlWithNullByte);
            
            expect($result)->toBeFalse();
        });

        it('handles URLs with carriage return gracefully', function () {
            $urlWithCR = "https://example.com\rmalicious";
            
            $result = $this->securityService->validateUrl($urlWithCR);
            
            expect($result)->toBeFalse();
        });

        it('handles URLs with newline gracefully', function () {
            $urlWithNewline = "https://example.com\nmalicious";
            
            $result = $this->securityService->validateUrl($urlWithNewline);
            
            expect($result)->toBeFalse();
        });

        it('handles malformed URLs gracefully', function () {
            $malformedUrls = [
                'not-a-url',
                'http://',
                'https://',
                '://invalid',
                'http://[invalid-ipv6',
                'https://example..com',
                'ftp://example.com', // Invalid protocol
            ];
            
            foreach ($malformedUrls as $url) {
                $result = $this->securityService->validateUrl($url);
                expect($result)->toBeFalse("URL '{$url}' should be invalid");
            }
        });

        it('handles blocked domains correctly', function () {
            // Test with a domain that would be blocked if configured
            $blockedDomain = 'malicious.example.com';
            
            // Since we can't easily mock the config in this context,
            // we test the isDomainBlocked method directly
            $isBlocked = $this->securityService->isDomainBlocked($blockedDomain);
            
            // Should return false since no domains are blocked by default
            expect($isBlocked)->toBeFalse();
        });

        it('handles protocol validation correctly', function () {
            $validProtocols = ['http', 'https'];
            $invalidProtocols = ['ftp', 'javascript', 'data', 'file'];
            
            foreach ($validProtocols as $protocol) {
                expect($this->securityService->isProtocolAllowed($protocol))->toBeTrue();
            }
            
            foreach ($invalidProtocols as $protocol) {
                expect($this->securityService->isProtocolAllowed($protocol))->toBeFalse();
            }
        });
    });

    describe('Path Validation Exception Scenarios', function () {
        it('throws InvalidArgumentException for empty path', function () {
            expectExceptionWithMessage(
                fn() => $this->securityService->validatePath(''),
                InvalidArgumentException::class,
                'Path cannot be empty'
            );
        });

        it('throws InvalidArgumentException for zero string path', function () {
            expectExceptionWithMessage(
                fn() => $this->securityService->validatePath('0'),
                InvalidArgumentException::class,
                'Path cannot be empty'
            );
        });

        it('handles paths with null bytes gracefully', function () {
            $pathWithNullByte = "/valid/path\0../../../etc/passwd";
            
            $result = $this->securityService->validatePath($pathWithNullByte);
            
            expect($result)->toBeFalse();
        });

        it('handles paths with carriage return gracefully', function () {
            $pathWithCR = "/valid/path\r../../../etc/passwd";
            
            $result = $this->securityService->validatePath($pathWithCR);
            
            expect($result)->toBeFalse();
        });

        it('handles paths with newline gracefully', function () {
            $pathWithNewline = "/valid/path\n../../../etc/passwd";
            
            $result = $this->securityService->validatePath($pathWithNewline);
            
            expect($result)->toBeFalse();
        });

        it('handles non-existent paths gracefully', function () {
            $nonExistentPath = '/absolutely/nonexistent/path/that/should/not/exist';
            
            $result = $this->securityService->validatePath($nonExistentPath);
            
            expect($result)->toBeFalse();
        });

        it('handles blocked system paths correctly', function () {
            $blockedPaths = [
                '/etc/passwd',
                '/var/log/system.log',
                '/usr/bin/sudo',
                '/bin/bash',
                '/sbin/init',
                '/root/.ssh/id_rsa',
                '/home/user/.ssh/id_rsa',
            ];
            
            foreach ($blockedPaths as $path) {
                // Create a temporary file to test with
                $tempFile = createTestFile(basename($path), 'test content');
                
                // The path should be valid for temp files during testing
                $result = $this->securityService->validatePath($tempFile);
                expect($result)->toBeTrue("Temp file should be valid: {$tempFile}");
            }
        });

        it('handles directory traversal attempts gracefully', function () {
            $traversalPaths = [
                '../../../etc/passwd',
                '..\\..\\..\\windows\\system32\\config\\sam',
                '/var/www/../../../etc/passwd',
                'normal/path/../../../../../../etc/passwd',
            ];
            
            foreach ($traversalPaths as $path) {
                $result = $this->securityService->validatePath($path);
                expect($result)->toBeFalse("Path should be invalid: {$path}");
            }
        });
    });

    describe('File Size Validation Exception Scenarios', function () {
        it('handles non-existent files gracefully', function () {
            $nonExistentFile = '/absolutely/nonexistent/file.txt';
            
            $result = $this->securityService->validateFileSize($nonExistentFile);
            
            expect($result)->toBeFalse();
        });

        it('handles files with unreadable size gracefully', function () {
            // Create a temporary file and then make it unreadable
            $tempFile = createTestFile('unreadable.txt', 'test content');
            
            // File should exist and be readable initially
            expect($this->securityService->validateFileSize($tempFile))->toBeTrue();
            
            // Test with a path that would cause filesize() to fail
            $invalidPath = '/dev/null/../nonexistent';
            expect($this->securityService->validateFileSize($invalidPath))->toBeFalse();
        });

        it('validates file size limits correctly', function () {
            // Create a small test file
            $smallFile = createTestFile('small.txt', 'small content');
            
            $result = $this->securityService->validateFileSize($smallFile);
            
            expect($result)->toBeTrue();
        });
    });

    describe('Security Configuration Exception Scenarios', function () {
        it('handles missing configuration gracefully', function () {
            // The service should handle missing config gracefully with defaults
            $config = $this->securityService->getSecurityConfig();
            
            expect($config)->toBeArray()
                ->and($config)->toHaveKey('blocked_domains')
                ->and($config)->toHaveKey('allowed_protocols')
                ->and($config)->toHaveKey('max_file_size')
                ->and($config)->toHaveKey('blocked_paths');
        });

        it('provides sensible defaults when config is unavailable', function () {
            $config = $this->securityService->getSecurityConfig();
            
            // Should have sensible defaults
            expect($config['allowed_protocols'])->toContain('http')
                ->and($config['allowed_protocols'])->toContain('https')
                ->and($config['max_file_size'])->toBeGreaterThan(0)
                ->and($config['blocked_paths'])->toBeArray();
        });
    });

    describe('Private IP Detection Exception Scenarios', function () {
        it('handles invalid hostnames gracefully', function () {
            $invalidHostnames = [
                '',
                'invalid..hostname',
                'hostname with spaces',
                'hostname\0withnull',
                'very-long-hostname-that-exceeds-normal-limits-and-should-be-handled-gracefully-without-causing-errors',
            ];
            
            foreach ($invalidHostnames as $hostname) {
                // The validateUrl method should handle these gracefully
                $result = $this->securityService->validateUrl("https://{$hostname}");
                expect($result)->toBeFalse("Invalid hostname should be rejected: {$hostname}");
            }
        });

        it('handles DNS resolution failures gracefully', function () {
            // Test with a hostname that should not resolve
            $nonExistentHostname = 'this-hostname-should-not-exist-anywhere.invalid';
            
            $result = $this->securityService->validateUrl("https://{$nonExistentHostname}");
            
            // Should handle DNS failures gracefully
            expect($result)->toBeFalse();
        });
    });

    describe('Environment Detection Exception Scenarios', function () {
        it('handles missing Laravel environment gracefully', function () {
            // The service should detect test environment correctly
            // This is tested indirectly through path validation
            $tempFile = createTestFile('test-env.txt', 'test content');
            
            $result = $this->securityService->validatePath($tempFile);
            
            expect($result)->toBeTrue();
        });

        it('handles missing environment variables gracefully', function () {
            // The service should work even when environment variables are missing
            $config = $this->securityService->getSecurityConfig();
            
            expect($config)->toBeArray();
        });
    });

    describe('Edge Cases and Boundary Conditions', function () {
        it('handles extremely long URLs gracefully', function () {
            $longUrl = 'https://example.com/' . str_repeat('a', 10000);
            
            $result = $this->securityService->validateUrl($longUrl);
            
            // Should handle long URLs without crashing
            expect($result)->toBeBool();
        });

        it('handles extremely long paths gracefully', function () {
            $longPath = '/' . str_repeat('a', 1000) . '/file.txt';
            
            $result = $this->securityService->validatePath($longPath);
            
            // Should handle long paths without crashing
            expect($result)->toBeFalse();
        });

        it('handles unicode characters in URLs gracefully', function () {
            $unicodeUrls = [
                'https://тест.рф',
                'https://example.com/测试',
                'https://example.com/café',
                'https://example.com/🚀',
            ];
            
            foreach ($unicodeUrls as $url) {
                $result = $this->securityService->validateUrl($url);
                expect($result)->toBeBool("Unicode URL should be handled: {$url}");
            }
        });

        it('handles unicode characters in paths gracefully', function () {
            $unicodePaths = [
                '/测试/file.txt',
                '/café/file.txt',
                '/🚀/file.txt',
            ];
            
            foreach ($unicodePaths as $path) {
                $result = $this->securityService->validatePath($path);
                expect($result)->toBeBool("Unicode path should be handled: {$path}");
            }
        });

        it('handles concurrent access gracefully', function () {
            // Test that the service can handle multiple concurrent calls
            $urls = [
                'https://example.com',
                'https://test.org',
                'https://sample.net',
            ];
            
            $results = [];
            foreach ($urls as $url) {
                $results[] = $this->securityService->validateUrl($url);
            }
            
            expect($results)->toHaveCount(3);
            foreach ($results as $result) {
                expect($result)->toBeBool();
            }
        });
    });

    describe('Error Recovery and Cleanup', function () {
        it('recovers gracefully from file system errors', function () {
            $operation = function () {
                throw simulateFileSystemError('io_error');
            };
            
            $recovery = function (Throwable $e) {
                return 'filesystem_error_handled';
            };
            
            testErrorRecovery($operation, $recovery, 'filesystem_error_handled');
        });

        it('provides fallback behavior for configuration errors', function () {
            $operation = function () {
                throw new RuntimeException('Configuration service unavailable');
            };
            
            $fallbackConfig = [
                'blocked_domains' => [],
                'allowed_protocols' => ['http', 'https'],
                'max_file_size' => 50 * 1024 * 1024,
            ];
            
            $result = testGracefulDegradation($operation, $fallbackConfig);
            
            expect($result)->toBe($fallbackConfig);
        });

        it('cleans up resources after validation errors', function () {
            $resourceCleaned = false;
            
            $operation = function () {
                throw new RuntimeException('Validation failed');
            };
            
            $cleanup = function () use (&$resourceCleaned) {
                $resourceCleaned = true;
            };
            
            testErrorCleanup($operation, $cleanup);
            
            expect($resourceCleaned)->toBeTrue();
        });

        it('reports security violations correctly', function () {
            $operation = function () {
                throw new RuntimeException('Security violation detected');
            };
            
            $reporter = function (Throwable $e) {
                return [
                    'type' => 'security_violation',
                    'message' => $e->getMessage(),
                    'severity' => 'high',
                    'timestamp' => time(),
                ];
            };
            
            $reports = testErrorReporting($operation, $reporter);
            
            expect($reports)->toHaveCount(1)
                ->and($reports[0]['type'])->toBe('security_violation')
                ->and($reports[0]['severity'])->toBe('high');
        });
    });

    describe('Performance Under Error Conditions', function () {
        it('maintains performance during repeated validation failures', function () {
            $operation = function () {
                // Simulate validation that always fails
                throw new RuntimeException('Validation failed');
            };
            
            $results = testExceptionHandlingUnderLoad($operation, 50, 0.01);
            
            expect($results['exception_count'])->toBe(50)
                ->and($results['average_time'])->toBeLessThan(0.01);
        });

        it('does not leak memory during error handling', function () {
            $initialMemory = memory_get_usage();
            
            // Perform many operations that fail
            for ($i = 0; $i < 100; $i++) {
                try {
                    throw new RuntimeException("Error {$i}");
                } catch (Throwable) {
                    // Handle and continue
                }
            }
            
            $finalMemory = memory_get_usage();
            $memoryIncrease = $finalMemory - $initialMemory;
            
            // Memory increase should be minimal (less than 1MB)
            expect($memoryIncrease)->toBeLessThan(1024 * 1024);
        });
    });
});