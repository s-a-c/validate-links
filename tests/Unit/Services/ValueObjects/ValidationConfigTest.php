<?php

declare(strict_types=1);

use App\Enums\OutputFormat;
use App\Enums\ValidationScope;
use App\Services\ValueObjects\ValidationConfig;

describe('ValidationConfig', function () {
    let('default', fn () => ValidationConfig::withDefaults());

    beforeEach(function () {
        // no-op
    });

    afterEach(function () {
        // no-op
    });

    it('it_should_create_with_defaults_and_expand_all_scope', function () {
        $cfg = ($this->default)();

        expect($cfg->getScopes())->toBeArray()
            ->and($cfg->getEffectiveScopes())->toEqual(ValidationScope::ALL->getIncludedScopes())
            ->and($cfg->getTimeout())->toBe(30)
            ->and($cfg->getMaxRedirects())->toBe(5)
            ->and($cfg->getConcurrentRequests())->toBe(10)
            ->and($cfg->isCacheEnabled())->toBeTrue()
            ->and($cfg->shouldFollowRedirects())->toBeTrue()
            ->and($cfg->getUserAgent())->toBe('validate-links/1.0')
            ->and($cfg->getOutputFormat())->toBeNull();
    });

    it('it_should_validate_and_cast_values_in_create', function () {
        $cfg = ValidationConfig::create([
            'scopes' => [ValidationScope::INTERNAL, ValidationScope::ANCHOR],
            'timeout' => '60',
            'max_redirects' => '7',
            'concurrent_requests' => '4',
            'cache_enabled' => false,
            'follow_redirects' => false,
            'user_agent' => 'ua/2.0',
            'output_format' => OutputFormat::JSON,
            'additional_options' => ['x' => 1],
            'interactive' => true,
            'dry_run' => true,
        ]);

        expect($cfg->getEffectiveScopes())->toEqual([ValidationScope::INTERNAL, ValidationScope::ANCHOR])
            ->and($cfg->getTimeout())->toBe(60)
            ->and($cfg->getMaxRedirects())->toBe(7)
            ->and($cfg->getConcurrentRequests())->toBe(4)
            ->and($cfg->isCacheEnabled())->toBeFalse()
            ->and($cfg->shouldFollowRedirects())->toBeFalse()
            ->and($cfg->getUserAgent())->toBe('ua/2.0')
            ->and($cfg->getOutputFormat())->toBe(OutputFormat::JSON)
            ->and($cfg->getAdditionalOptions())->toHaveKey('x')
            ->and($cfg->isInteractive())->toBeTrue()
            ->and($cfg->isDryRun())->toBeTrue();
    });

    it('it_should_support_immutable_withers', function () {
        $cfg = ($this->default)();

        $withScopes = $cfg->withScopes([ValidationScope::EXTERNAL]);
        $withFormat = $cfg->withOutputFormat(OutputFormat::HTML);

        expect($withScopes)->not->toBe($cfg)
            ->and($withFormat)->not->toBe($cfg)
            ->and($withScopes->getEffectiveScopes())->toEqual([ValidationScope::EXTERNAL])
            ->and($withFormat->getOutputFormat())->toBe(OutputFormat::HTML);

        // original remains unchanged
        expect($cfg->getOutputFormat())->toBeNull();
    });

    it('it_should_serialize_to_array_with_scalar_values', function () {
        $cfg = ValidationConfig::create([
            'scopes' => [ValidationScope::INTERNAL, ValidationScope::EXTERNAL],
            'timeout' => 15,
            'max_redirects' => 2,
            'concurrent_requests' => 3,
            'cache_enabled' => true,
            'follow_redirects' => true,
            'user_agent' => 'ua',
            'output_format' => OutputFormat::MARKDOWN,
            'additional_options' => ['a' => 'b'],
            'interactive' => false,
            'dry_run' => false,
        ]);

        $arr = $cfg->toArray();

        expect($arr)->toHaveKeys([
            'scopes', 'timeout', 'max_redirects', 'concurrent_requests',
            'cache_enabled', 'follow_redirects', 'user_agent', 'output_format',
            'additional_options', 'interactive', 'dry_run',
        ]);

        expect($arr['scopes'])->toBeArray()
            ->and($arr['scopes'])->toContain('internal', 'external')
            ->and($arr['output_format'])->toBe('markdown');
    });

    it('it_should_enforce_validation_constraints', function () {
        // concurrent requests out of bounds
        expect(fn () => ValidationConfig::create(['concurrent_requests' => 0]))->toThrow(InvalidArgumentException::class);
        expect(fn () => ValidationConfig::create(['concurrent_requests' => 999]))->toThrow(InvalidArgumentException::class);

        // timeout out of bounds
        expect(fn () => ValidationConfig::create(['timeout' => 0]))->toThrow(InvalidArgumentException::class);
        expect(fn () => ValidationConfig::create(['timeout' => 9999]))->toThrow(InvalidArgumentException::class);

        // redirects out of bounds
        expect(fn () => ValidationConfig::create(['max_redirects' => -1]))->toThrow(InvalidArgumentException::class);
        expect(fn () => ValidationConfig::create(['max_redirects' => 99]))->toThrow(InvalidArgumentException::class);
    });
});