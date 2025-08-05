<?php

declare(strict_types=1);

use App\Services\SecurityValidationService;

describe('SecurityValidationService', function () {
    let('svc', fn () => new SecurityValidationService());

    beforeEach(function () {
        // No global state; config() calls in constructor are wrapped with try/catch.
    });

    afterEach(function () {
        // No teardown required
    });

    it('it_should_reject_empty_or_nullbyte_urls', function () {
        $svc = ($this->svc)();

        expect(fn () => $svc->validateUrl(''))->toThrow(InvalidArgumentException::class);
        expect($svc->validateUrl("http://ex\0ample.com"))->toBeFalse();
    });

    it('it_should_allow_http_and_https_and_default_allow_private_in_tests', function () {
        $svc = ($this->svc)();

        expect($svc->validateUrl('http://example.com'))->toBeTrue();
        expect($svc->validateUrl('https://example.com'))->toBeTrue();

        // Localhost/private IPs in tests usually allowed by fallback logic
        expect($svc->validateUrl('http://127.0.0.1'))->toBeTrue();
        expect($svc->validateUrl('http://localhost'))->toBeTrue();
    });

    it('it_should_validate_protocol_and_domain_rules', function () {
        $svc = ($this->svc)();

        // Disallow unsupported scheme
        expect($svc->validateUrl('ftp://example.com'))->toBeFalse();

        // Domain block list is empty by default, so allow typical host
        expect($svc->validateUrl('https://valid.example.com'))->toBeTrue();
    });

    it('it_should_reject_paths_with_nullbytes_and_blocked_prefixes', function () {
        $svc = ($this->svc)();

        expect(fn () => $svc->validatePath(''))->toThrow(InvalidArgumentException::class);
        expect($svc->validatePath("/etc/passwd"))->toBeFalse();
        expect($svc->validatePath("/usr/bin/php"))->toBeFalse();
    });

    it('it_should_allow_relative_or_temp_paths_even_without_base_path', function () {
        $svc = ($this->svc)();

        // Relative path that should resolve within project for tests
        $tmpFile = sys_get_temp_dir() . '/vl-test-' . uniqid() . '.txt';
        file_put_contents($tmpFile, 'x');

        try {
            // Service allow temp paths in test environment when base_path() unavailable
            expect($svc->validatePath($tmpFile))->toBeTrue();
        } finally {
            @unlink($tmpFile);
        }
    });

    it('it_should_report_security_configuration', function () {
        $svc = ($this->svc)();

        $cfg = $svc->getSecurityConfig();
        expect($cfg)->toBeArray()
            ->and($cfg)->toHaveKeys(['blocked_domains', 'allowed_protocols', 'max_file_size', 'blocked_paths']);
    });

    it('it_should_respect_file_size_limits', function () {
        $svc = ($this->svc)();

        $tmpFile = sys_get_temp_dir() . '/vl-test-size-' . uniqid() . '.txt';
        file_put_contents($tmpFile, str_repeat('a', 1024));

        try {
            expect($svc->validateFileSize($tmpFile))->toBeTrue();
        } finally {
            @unlink($tmpFile);
        }

        // Non-existent file
        expect($svc->validateFileSize('/non/existent/file'))->toBeFalse();
    });
});