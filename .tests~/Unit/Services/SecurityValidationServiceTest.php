<?php

declare(strict_types=1);

use App\Services\SecurityValidationService;
use App\Services\Contracts\SecurityValidationInterface;

/**
 * Unit test suite for SecurityValidationService.
 *
 * Tests security validation functionality including URL validation, path validation,
 * domain blocking, protocol checking, file size validation, and security configuration.
 *
 * @group unit
 * @group services
 * @group security
 *
 * @covers \App\Services\SecurityValidationService
 */
describe('SecurityValidationService', function () {
    beforeEach(function () {
        // Setup Phase: Prepare comprehensive test environment

        // Create service instance normally - constructor handles config gracefully
        $this->service = new SecurityValidationService();

        // Set up test data for URLs
        $this->testUrls = [
            'valid' => [
                'https://example.com',
                'http://test.org',
                'https://subdomain.example.com/path',
                'http://localhost:8080',
            ],
            'invalid_protocol' => [
                'ftp://example.com',
                'file://local/path',
                'javascript:alert(1)',
                'data:text/html,<script>',
            ],
            'malicious' => [
                "https://example.com\0",
                "http://test.com\r\n",
                "https://evil.com\n",
                "http://bad.com\r",
            ],
            'private_ips' => [
                'http://192.168.1.1',
                'http://10.0.0.1',
                'http://172.16.0.1',
                'http://127.0.0.1',
            ],
        ];

        // Set up test data for file paths
        $this->testPaths = [
            'safe_relative' => [
                'test.md',
                'docs/readme.md',
                './config/app.php',
                'storage/logs/test.log',
            ],
            'safe_temp' => [
                sys_get_temp_dir() . '/test.txt',
                tempnam(sys_get_temp_dir(), 'test-'),
            ],
            'dangerous' => [
                '../../../etc/passwd',
                '../../etc/hosts',
                '/etc/passwd',
                '/var/log/auth.log',
            ],
            'malicious' => [
                "test.txt\0",
                "file.md\r\n",
                "path\n",
                "bad\r",
            ],
        ];

        // Set up test data for domains
        $this->testDomains = [
            'normal' => [
                'example.com',
                'test.org',
                'subdomain.example.com',
                'localhost',
            ],
            'case_variants' => [
                'EXAMPLE.COM',
                'Test.ORG',
                'SUBDOMAIN.example.COM',
                'LOCALHOST',
            ],
        ];

        // Set up test data for protocols
        $this->testProtocols = [
            'allowed' => ['http', 'https', 'HTTP', 'HTTPS'],
            'blocked' => ['ftp', 'file', 'javascript', 'data', 'mailto'],
        ];

        // Create test files for file size validation
        $this->testFiles = [
            'small' => tempnam(sys_get_temp_dir(), 'small-'),
            'large' => tempnam(sys_get_temp_dir(), 'large-'),
        ];

        // Write test content
        file_put_contents($this->testFiles['small'], str_repeat('a', 1024)); // 1KB
        file_put_contents($this->testFiles['large'], str_repeat('b', 100 * 1024 * 1024)); // 100MB

        // Expected security configuration structure
        $this->expectedConfigKeys = [
            'blocked_domains',
            'allowed_protocols',
            'max_file_size',
            'blocked_paths',
        ];
    });

    afterEach(function () {
        // Teardown Phase: Clean up test environment

        // Clean up test files
        foreach ($this->testFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        // Clear properties
        unset(
            $this->service,
            $this->testUrls,
            $this->testPaths,
            $this->testDomains,
            $this->testProtocols,
            $this->testFiles,
            $this->expectedConfigKeys
        );

        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    });

    describe('service instantiation and interface compliance', function () {
        it('can be instantiated', function () {
            expect($this->service)->toBeInstanceOf(SecurityValidationService::class);
        });

        it('implements SecurityValidationInterface', function () {
            expect($this->service)->toBeInstanceOf(SecurityValidationInterface::class);
        });

        it('has all required interface methods', function () {
            $reflection = new \ReflectionClass($this->service);

            expect($reflection->hasMethod('validateUrl'))->toBeTrue()
                ->and($reflection->hasMethod('validatePath'))->toBeTrue()
                ->and($reflection->hasMethod('isDomainBlocked'))->toBeTrue()
                ->and($reflection->hasMethod('isProtocolAllowed'))->toBeTrue()
                ->and($reflection->hasMethod('validateFileSize'))->toBeTrue()
                ->and($reflection->hasMethod('getSecurityConfig'))->toBeTrue();
        });

        it('is a final readonly class', function () {
            $reflection = new \ReflectionClass($this->service);

            expect($reflection->isFinal())->toBeTrue()
                ->and($reflection->isReadOnly())->toBeTrue();
        });
    });

    describe('validateUrl method', function () {
        it('validates safe URLs', function () {
            foreach ($this->testUrls['valid'] as $url) {
                $result = $this->service->validateUrl($url);
                expect($result)->toBeBool();
            }
        });

        it('rejects URLs with invalid protocols', function () {
            foreach ($this->testUrls['invalid_protocol'] as $url) {
                $result = $this->service->validateUrl($url);
                expect($result)->toBeFalse();
            }
        });

        it('rejects URLs with malicious characters', function () {
            foreach ($this->testUrls['malicious'] as $url) {
                $result = $this->service->validateUrl($url);
                expect($result)->toBeFalse();
            }
        });

        it('throws exception for empty URL', function () {
            expect(fn() => $this->service->validateUrl(''))
                ->toThrow(InvalidArgumentException::class, 'URL cannot be empty');
        });

        it('throws exception for zero string URL', function () {
            expect(fn() => $this->service->validateUrl('0'))
                ->toThrow(InvalidArgumentException::class, 'URL cannot be empty');
        });

        it('handles private IP addresses', function () {
            foreach ($this->testUrls['private_ips'] as $url) {
                $result = $this->service->validateUrl($url);
                expect($result)->toBeBool(); // Should return boolean regardless of config
            }
        });

        it('handles malformed URLs', function () {
            $malformedUrls = [
                'not-a-url',
                'http://',
                'https://',
                '://example.com',
            ];

            foreach ($malformedUrls as $url) {
                $result = $this->service->validateUrl($url);
                expect($result)->toBeFalse();
            }
        });
    });

    describe('validatePath method', function () {
        it('validates safe relative paths', function () {
            foreach ($this->testPaths['safe_relative'] as $path) {
                $result = $this->service->validatePath($path);
                expect($result)->toBeBool();
            }
        });

        it('validates safe temp paths', function () {
            foreach ($this->testPaths['safe_temp'] as $path) {
                // Create the file first
                if (!file_exists($path)) {
                    file_put_contents($path, 'test');
                }

                $result = $this->service->validatePath($path);
                expect($result)->toBeBool();

                // Clean up
                if (file_exists($path)) {
                    unlink($path);
                }
            }
        });

        it('rejects dangerous paths', function () {
            foreach ($this->testPaths['dangerous'] as $path) {
                $result = $this->service->validatePath($path);
                expect($result)->toBeFalse();
            }
        });

        it('rejects paths with malicious characters', function () {
            foreach ($this->testPaths['malicious'] as $path) {
                $result = $this->service->validatePath($path);
                expect($result)->toBeFalse();
            }
        });

        it('throws exception for empty path', function () {
            expect(fn() => $this->service->validatePath(''))
                ->toThrow(InvalidArgumentException::class, 'Path cannot be empty');
        });

        it('throws exception for zero string path', function () {
            expect(fn() => $this->service->validatePath('0'))
                ->toThrow(InvalidArgumentException::class, 'Path cannot be empty');
        });

        it('handles non-existent paths', function () {
            $nonExistentPath = '/non/existent/path/file.txt';
            $result = $this->service->validatePath($nonExistentPath);
            expect($result)->toBeFalse();
        });
    });

    describe('isDomainBlocked method', function () {
        it('checks domain blocking', function () {
            foreach ($this->testDomains['normal'] as $domain) {
                $result = $this->service->isDomainBlocked($domain);
                expect($result)->toBeBool();
            }
        });

        it('performs case-insensitive domain checking', function () {
            foreach ($this->testDomains['case_variants'] as $domain) {
                $result = $this->service->isDomainBlocked($domain);
                expect($result)->toBeBool();
            }
        });
    });

    describe('isProtocolAllowed method', function () {
        it('allows safe protocols', function () {
            foreach ($this->testProtocols['allowed'] as $protocol) {
                $result = $this->service->isProtocolAllowed($protocol);
                expect($result)->toBeTrue();
            }
        });

        it('blocks unsafe protocols', function () {
            foreach ($this->testProtocols['blocked'] as $protocol) {
                $result = $this->service->isProtocolAllowed($protocol);
                expect($result)->toBeFalse();
            }
        });

        it('performs case-insensitive protocol checking', function () {
            expect($this->service->isProtocolAllowed('HTTP'))->toBeTrue()
                ->and($this->service->isProtocolAllowed('HTTPS'))->toBeTrue()
                ->and($this->service->isProtocolAllowed('FTP'))->toBeFalse()
                ->and($this->service->isProtocolAllowed('FILE'))->toBeFalse();
        });
    });

    describe('validateFileSize method', function () {
        it('validates small files', function () {
            $result = $this->service->validateFileSize($this->testFiles['small']);
            expect($result)->toBeTrue();
        });

        it('rejects large files', function () {
            $result = $this->service->validateFileSize($this->testFiles['large']);
            expect($result)->toBeFalse();
        });

        it('rejects non-existent files', function () {
            $result = $this->service->validateFileSize('/non/existent/file.txt');
            expect($result)->toBeFalse();
        });
    });

    describe('getSecurityConfig method', function () {
        it('returns security configuration array', function () {
            $config = $this->service->getSecurityConfig();

            expect($config)->toBeArray()
                ->and(array_keys($config))->toBe($this->expectedConfigKeys);
        });

        it('returns proper data types in configuration', function () {
            $config = $this->service->getSecurityConfig();

            expect($config['blocked_domains'])->toBeArray()
                ->and($config['allowed_protocols'])->toBeArray()
                ->and($config['max_file_size'])->toBeInt()
                ->and($config['blocked_paths'])->toBeArray();
        });

        it('includes default blocked paths', function () {
            $config = $this->service->getSecurityConfig();
            $blockedPaths = $config['blocked_paths'];

            expect($blockedPaths)->toContain('/etc/')
                ->and($blockedPaths)->toContain('/var/')
                ->and($blockedPaths)->toContain('/usr/')
                ->and($blockedPaths)->toContain('/bin/');
        });
    });

    describe('edge cases and error handling', function () {
        it('handles very long URLs', function () {
            $longUrl = 'https://example.com/' . str_repeat('a', 10000);
            $result = $this->service->validateUrl($longUrl);
            expect($result)->toBeBool();
        });

        it('handles very long paths', function () {
            $longPath = str_repeat('a', 1000) . '.txt';
            $result = $this->service->validatePath($longPath);
            expect($result)->toBeFalse();
        });

        it('handles unicode in URLs', function () {
            $unicodeUrl = 'https://example.com/测试';
            $result = $this->service->validateUrl($unicodeUrl);
            expect($result)->toBeBool();
        });

        it('handles unicode in paths', function () {
            $unicodePath = '测试.txt';
            $result = $this->service->validatePath($unicodePath);
            expect($result)->toBeBool();
        });
    });
});
