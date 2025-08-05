<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\LinkStatus;

/**
 * Comprehensive unit tests for LinkStatus enum.
 *
 * Tests all enum cases, methods, and edge cases with 100% coverage.
 * Validates enum behavior, method return values, and type safety.
 */
describe('LinkStatus Enum', function () {

    describe('Enum Cases', function () {
        it('has all expected enum cases', function () {
            $expectedCases = [
                'VALID',
                'BROKEN',
                'TIMEOUT',
                'FORBIDDEN',
                'NOT_FOUND',
                'INVALID_FORMAT',
                'SECURITY_VIOLATION',
                'REDIRECT_LOOP',
                'SSL_ERROR',
                'DNS_ERROR',
            ];

            $actualCases = array_map(fn ($case) => $case->name, LinkStatus::cases());

            expect($actualCases)->toBe($expectedCases);
        });

        it('has correct string values for all cases', function () {
            expect(LinkStatus::VALID->value)->toBe('valid');
            expect(LinkStatus::BROKEN->value)->toBe('broken');
            expect(LinkStatus::TIMEOUT->value)->toBe('timeout');
            expect(LinkStatus::FORBIDDEN->value)->toBe('forbidden');
            expect(LinkStatus::NOT_FOUND->value)->toBe('not_found');
            expect(LinkStatus::INVALID_FORMAT->value)->toBe('invalid_format');
            expect(LinkStatus::SECURITY_VIOLATION->value)->toBe('security_violation');
            expect(LinkStatus::REDIRECT_LOOP->value)->toBe('redirect_loop');
            expect(LinkStatus::SSL_ERROR->value)->toBe('ssl_error');
            expect(LinkStatus::DNS_ERROR->value)->toBe('dns_error');
        });
    });

    describe('values() method', function () {
        it('returns all enum values as array', function () {
            $values = LinkStatus::values();

            expect($values)->toBeArray()
                ->and($values)->toHaveCount(10)
                ->and($values)->toContain('valid', 'broken', 'timeout', 'forbidden', 'not_found');
        });

        it('returns values in correct order', function () {
            $expectedValues = [
                'valid', 'broken', 'timeout', 'forbidden', 'not_found',
                'invalid_format', 'security_violation', 'redirect_loop', 'ssl_error', 'dns_error',
            ];

            expect(LinkStatus::values())->toBe($expectedValues);
        });
    });

    describe('isBroken() method', function () {
        it('returns false for VALID status', function () {
            expect(LinkStatus::VALID->isBroken())->toBeFalse();
        });

        it('returns true for all non-valid statuses', function () {
            $brokenStatuses = [
                LinkStatus::BROKEN,
                LinkStatus::TIMEOUT,
                LinkStatus::FORBIDDEN,
                LinkStatus::NOT_FOUND,
                LinkStatus::INVALID_FORMAT,
                LinkStatus::SECURITY_VIOLATION,
                LinkStatus::REDIRECT_LOOP,
                LinkStatus::SSL_ERROR,
                LinkStatus::DNS_ERROR,
            ];

            foreach ($brokenStatuses as $status) {
                expect($status->isBroken())->toBeTrue();
            }
        });
    });

    describe('isTemporary() method', function () {
        it('returns true for temporary issues', function () {
            expect(LinkStatus::TIMEOUT->isTemporary())->toBeTrue();
            expect(LinkStatus::DNS_ERROR->isTemporary())->toBeTrue();
        });

        it('returns false for permanent issues', function () {
            $permanentStatuses = [
                LinkStatus::VALID,
                LinkStatus::BROKEN,
                LinkStatus::FORBIDDEN,
                LinkStatus::NOT_FOUND,
                LinkStatus::INVALID_FORMAT,
                LinkStatus::SECURITY_VIOLATION,
                LinkStatus::REDIRECT_LOOP,
                LinkStatus::SSL_ERROR,
            ];

            foreach ($permanentStatuses as $status) {
                expect($status->isTemporary())->toBeFalse();
            }
        });
    });

    describe('isSecurityIssue() method', function () {
        it('returns true for security-related issues', function () {
            expect(LinkStatus::SECURITY_VIOLATION->isSecurityIssue())->toBeTrue();
            expect(LinkStatus::SSL_ERROR->isSecurityIssue())->toBeTrue();
        });

        it('returns false for non-security issues', function () {
            $nonSecurityStatuses = [
                LinkStatus::VALID,
                LinkStatus::BROKEN,
                LinkStatus::TIMEOUT,
                LinkStatus::FORBIDDEN,
                LinkStatus::NOT_FOUND,
                LinkStatus::INVALID_FORMAT,
                LinkStatus::REDIRECT_LOOP,
                LinkStatus::DNS_ERROR,
            ];

            foreach ($nonSecurityStatuses as $status) {
                expect($status->isSecurityIssue())->toBeFalse();
            }
        });
    });

    describe('getDisplayName() method', function () {
        it('returns correct display names for all statuses', function () {
            expect(LinkStatus::VALID->getDisplayName())->toBe('Valid');
            expect(LinkStatus::BROKEN->getDisplayName())->toBe('Broken');
            expect(LinkStatus::TIMEOUT->getDisplayName())->toBe('Timeout');
            expect(LinkStatus::FORBIDDEN->getDisplayName())->toBe('Forbidden');
            expect(LinkStatus::NOT_FOUND->getDisplayName())->toBe('Not Found');
            expect(LinkStatus::INVALID_FORMAT->getDisplayName())->toBe('Invalid Format');
            expect(LinkStatus::SECURITY_VIOLATION->getDisplayName())->toBe('Security Violation');
            expect(LinkStatus::REDIRECT_LOOP->getDisplayName())->toBe('Redirect Loop');
            expect(LinkStatus::SSL_ERROR->getDisplayName())->toBe('SSL Error');
            expect(LinkStatus::DNS_ERROR->getDisplayName())->toBe('DNS Error');
        });
    });

    describe('getDescription() method', function () {
        it('returns meaningful descriptions for all statuses', function () {
            expect(LinkStatus::VALID->getDescription())->toBe('Link is valid and accessible');
            expect(LinkStatus::BROKEN->getDescription())->toBe('Link is broken or inaccessible');
            expect(LinkStatus::TIMEOUT->getDescription())->toBe('Request timed out');
            expect(LinkStatus::FORBIDDEN->getDescription())->toBe('Access forbidden (403)');
            expect(LinkStatus::NOT_FOUND->getDescription())->toBe('Resource not found (404)');
            expect(LinkStatus::INVALID_FORMAT->getDescription())->toBe('Invalid URL format');
            expect(LinkStatus::SECURITY_VIOLATION->getDescription())->toBe('Security policy violation');
            expect(LinkStatus::REDIRECT_LOOP->getDescription())->toBe('Redirect loop detected');
            expect(LinkStatus::SSL_ERROR->getDescription())->toBe('SSL/TLS certificate error');
            expect(LinkStatus::DNS_ERROR->getDescription())->toBe('DNS resolution failed');
        });
    });

    describe('getSeverity() method', function () {
        it('returns correct severity levels', function () {
            expect(LinkStatus::VALID->getSeverity())->toBe(0);
            expect(LinkStatus::TIMEOUT->getSeverity())->toBe(2);
            expect(LinkStatus::DNS_ERROR->getSeverity())->toBe(2);
            expect(LinkStatus::NOT_FOUND->getSeverity())->toBe(3);
            expect(LinkStatus::INVALID_FORMAT->getSeverity())->toBe(3);
            expect(LinkStatus::FORBIDDEN->getSeverity())->toBe(4);
            expect(LinkStatus::REDIRECT_LOOP->getSeverity())->toBe(4);
            expect(LinkStatus::BROKEN->getSeverity())->toBe(5);
            expect(LinkStatus::SECURITY_VIOLATION->getSeverity())->toBe(5);
            expect(LinkStatus::SSL_ERROR->getSeverity())->toBe(5);
        });

        it('returns severity levels in valid range', function () {
            foreach (LinkStatus::cases() as $status) {
                expect($status->getSeverity())->toBeGreaterThanOrEqual(0)
                    ->and($status->getSeverity())->toBeLessThanOrEqual(5);
            }
        });
    });

    describe('getRecommendedAction() method', function () {
        it('returns actionable recommendations for all statuses', function () {
            expect(LinkStatus::VALID->getRecommendedAction())->toBe('No action required');
            expect(LinkStatus::TIMEOUT->getRecommendedAction())->toBe('Retry validation or increase timeout');
            expect(LinkStatus::FORBIDDEN->getRecommendedAction())->toBe('Check access permissions or authentication');
            expect(LinkStatus::NOT_FOUND->getRecommendedAction())->toBe('Update link URL or remove broken link');
            expect(LinkStatus::INVALID_FORMAT->getRecommendedAction())->toBe('Fix URL syntax and format');
            expect(LinkStatus::SECURITY_VIOLATION->getRecommendedAction())->toBe('Review security policies and URL safety');
            expect(LinkStatus::REDIRECT_LOOP->getRecommendedAction())->toBe('Check redirect configuration');
            expect(LinkStatus::SSL_ERROR->getRecommendedAction())->toBe('Verify SSL certificate validity');
            expect(LinkStatus::DNS_ERROR->getRecommendedAction())->toBe('Check domain name and DNS configuration');
            expect(LinkStatus::BROKEN->getRecommendedAction())->toBe('Investigate and fix underlying issue');
        });
    });

    describe('getConsoleColor() method', function () {
        it('returns valid console colors for all statuses', function () {
            $validColors = ['green', 'yellow', 'magenta', 'cyan', 'red'];

            foreach (LinkStatus::cases() as $status) {
                expect($status->getConsoleColor())->toBeIn($validColors);
            }
        });

        it('returns specific colors for status categories', function () {
            expect(LinkStatus::VALID->getConsoleColor())->toBe('green');
            expect(LinkStatus::TIMEOUT->getConsoleColor())->toBe('yellow');
            expect(LinkStatus::DNS_ERROR->getConsoleColor())->toBe('yellow');
            expect(LinkStatus::NOT_FOUND->getConsoleColor())->toBe('magenta');
            expect(LinkStatus::INVALID_FORMAT->getConsoleColor())->toBe('magenta');
            expect(LinkStatus::FORBIDDEN->getConsoleColor())->toBe('cyan');
            expect(LinkStatus::REDIRECT_LOOP->getConsoleColor())->toBe('cyan');
            expect(LinkStatus::BROKEN->getConsoleColor())->toBe('red');
            expect(LinkStatus::SECURITY_VIOLATION->getConsoleColor())->toBe('red');
            expect(LinkStatus::SSL_ERROR->getConsoleColor())->toBe('red');
        });
    });

    describe('getIcon() method', function () {
        it('returns emoji icons for all statuses', function () {
            expect(LinkStatus::VALID->getIcon())->toBe('✅');
            expect(LinkStatus::TIMEOUT->getIcon())->toBe('⏱️');
            expect(LinkStatus::FORBIDDEN->getIcon())->toBe('🚫');
            expect(LinkStatus::NOT_FOUND->getIcon())->toBe('❌');
            expect(LinkStatus::INVALID_FORMAT->getIcon())->toBe('⚠️');
            expect(LinkStatus::SECURITY_VIOLATION->getIcon())->toBe('🔒');
            expect(LinkStatus::REDIRECT_LOOP->getIcon())->toBe('🔄');
            expect(LinkStatus::SSL_ERROR->getIcon())->toBe('🔐');
            expect(LinkStatus::DNS_ERROR->getIcon())->toBe('🌐');
            expect(LinkStatus::BROKEN->getIcon())->toBe('💥');
        });

        it('returns non-empty strings for all icons', function () {
            foreach (LinkStatus::cases() as $status) {
                expect($status->getIcon())->toBeString()
                    ->and($status->getIcon())->not->toBeEmpty();
            }
        });
    });

    describe('getGroup() method', function () {
        it('returns correct groups for status organization', function () {
            expect(LinkStatus::VALID->getGroup())->toBe('success');
            expect(LinkStatus::TIMEOUT->getGroup())->toBe('temporary');
            expect(LinkStatus::DNS_ERROR->getGroup())->toBe('temporary');
            expect(LinkStatus::FORBIDDEN->getGroup())->toBe('client_error');
            expect(LinkStatus::NOT_FOUND->getGroup())->toBe('client_error');
            expect(LinkStatus::INVALID_FORMAT->getGroup())->toBe('format_error');
            expect(LinkStatus::SECURITY_VIOLATION->getGroup())->toBe('format_error');
            expect(LinkStatus::REDIRECT_LOOP->getGroup())->toBe('protocol_error');
            expect(LinkStatus::SSL_ERROR->getGroup())->toBe('protocol_error');
            expect(LinkStatus::BROKEN->getGroup())->toBe('unknown_error');
        });
    });

    describe('getFormattedDisplay() method', function () {
        it('returns formatted display text with color tags', function () {
            $formatted = LinkStatus::VALID->getFormattedDisplay();

            expect($formatted)->toBeString()
                ->and($formatted)->toContain('<fg=green>')
                ->and($formatted)->toContain('✅')
                ->and($formatted)->toContain('Link is valid and accessible')
                ->and($formatted)->toContain('</fg>');
        });

        it('includes icon and description for all statuses', function () {
            foreach (LinkStatus::cases() as $status) {
                $formatted = $status->getFormattedDisplay();

                expect($formatted)->toContain($status->getIcon())
                    ->and($formatted)->toContain($status->getDescription())
                    ->and($formatted)->toContain('<fg=')
                    ->and($formatted)->toContain('</fg>');
            }
        });
    });

    describe('shouldRetry() method', function () {
        it('returns true for retryable statuses', function () {
            expect(LinkStatus::TIMEOUT->shouldRetry())->toBeTrue();
            expect(LinkStatus::DNS_ERROR->shouldRetry())->toBeTrue();
        });

        it('returns false for non-retryable statuses', function () {
            $nonRetryableStatuses = [
                LinkStatus::VALID,
                LinkStatus::BROKEN,
                LinkStatus::FORBIDDEN,
                LinkStatus::NOT_FOUND,
                LinkStatus::INVALID_FORMAT,
                LinkStatus::SECURITY_VIOLATION,
                LinkStatus::REDIRECT_LOOP,
                LinkStatus::SSL_ERROR,
            ];

            foreach ($nonRetryableStatuses as $status) {
                expect($status->shouldRetry())->toBeFalse();
            }
        });
    });

    describe('getHttpStatusCode() method', function () {
        it('returns correct HTTP status codes where applicable', function () {
            expect(LinkStatus::VALID->getHttpStatusCode())->toBe(200);
            expect(LinkStatus::FORBIDDEN->getHttpStatusCode())->toBe(403);
            expect(LinkStatus::NOT_FOUND->getHttpStatusCode())->toBe(404);
            expect(LinkStatus::TIMEOUT->getHttpStatusCode())->toBe(408);
            expect(LinkStatus::REDIRECT_LOOP->getHttpStatusCode())->toBe(310);
            expect(LinkStatus::SSL_ERROR->getHttpStatusCode())->toBe(526);
        });

        it('returns null for statuses without HTTP equivalents', function () {
            expect(LinkStatus::BROKEN->getHttpStatusCode())->toBeNull();
            expect(LinkStatus::INVALID_FORMAT->getHttpStatusCode())->toBeNull();
            expect(LinkStatus::SECURITY_VIOLATION->getHttpStatusCode())->toBeNull();
            expect(LinkStatus::DNS_ERROR->getHttpStatusCode())->toBeNull();
        });
    });
});
