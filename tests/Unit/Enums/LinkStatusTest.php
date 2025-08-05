<?php

declare(strict_types=1);

use App\Enums\LinkStatus;

describe('LinkStatus enum', function () {
    let('valid', fn () => LinkStatus::VALID);
    let('broken', fn () => LinkStatus::BROKEN);
    let('timeout', fn () => LinkStatus::TIMEOUT);
    let('forbidden', fn () => LinkStatus::FORBIDDEN);
    let('notFound', fn () => LinkStatus::NOT_FOUND);
    let('invalid', fn () => LinkStatus::INVALID_FORMAT);
    let('security', fn () => LinkStatus::SECURITY_VIOLATION);
    let('dns', fn () => LinkStatus::DNS_ERROR);

    beforeEach(function () {
        // No global state to set; ensure deterministic assertions only
    });

    afterEach(function () {
        // No teardown necessary
    });

    it('it_should_expose_all_values_when_requested', function () {
        expect(LinkStatus::values())
            ->toContain('valid', 'broken', 'timeout', 'forbidden', 'not_found', 'invalid_format', 'security_violation', 'redirect_loop', 'ssl_error', 'dns_error');
    });

    it('it_should_identify_broken_statuses', function () {
        expect(($this->valid)())->toBeInstanceOf(LinkStatus::class)
            ->and(($this->valid)())->toEqual(LinkStatus::VALID)
            ->and(($this->valid)()->isBroken())->toBeFalse();

        $nonValid = [
            ($this->broken)(),
            ($this->timeout)(),
            ($this->forbidden)(),
            ($this->notFound)(),
            ($this->invalid)(),
            ($this->security)(),
            ($this->dns)(),
        ];

        foreach ($nonValid as $status) {
            expect($status->isBroken())->toBeTrue();
        }
    });

    it('it_should_compute_severity_levels_by_status', function () {
        expect(($this->valid)()->getSeverity())->toBe(0)
            ->and(($this->timeout)()->getSeverity())->toBeGreaterThanOrEqual(1)
            ->and(($this->broken)()->getSeverity())->toBeGreaterThanOrEqual(($this->forbidden)()->getSeverity());
    });

    it('it_should_provide_console_colors_and_icons', function () {
        foreach (LinkStatus::cases() as $status) {
            expect($status->getConsoleColor())->toBeString()
                ->and($status->getIcon())->toBeString()
                ->and($status->getFormattedDisplay())->toBeString();
        }
    });

    it('it_should_map_some_statuses_to_http_codes', function () {
        expect(($this->valid)()->getHttpStatusCode())->toBe(200)
            ->and(($this->forbidden)()->getHttpStatusCode())->toBe(403)
            ->and(($this->notFound)()->getHttpStatusCode())->toBe(404)
            ->and(($this->timeout)()->getHttpStatusCode())->toBe(408)
            ->and(($this->security)()->getHttpStatusCode())->toBe(526)
            ->and(LinkStatus::BROKEN->getHttpStatusCode())->toBeNull();
    });

    it('it_should_indicate_retryability_for_temporary_statuses', function () {
        expect(($this->timeout)()->shouldRetry())->toBeTrue()
            ->and(($this->dns)()->shouldRetry())->toBeTrue()
            ->and(($this->broken)()->shouldRetry())->toBeFalse();
    });

    it('it_should_group_statuses_for_reporting', function () {
        expect(($this->valid)()->getGroup())->toBe('success')
            ->and(($this->timeout)()->getGroup())->toBe('temporary')
            ->and(($this->forbidden)()->getGroup())->toBe('client_error')
            ->and(($this->invalid)()->getGroup())->toBe('format_error');
    });
});