<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\LinkStatus;
use ValueError;

/**
 * Unit test suite for LinkStatus enum.
 *
 * Tests all enum cases, values, and business logic methods to ensure
 * proper functionality of link status classification and validation.
 *
 * @group unit
 * @group enums
 *
 * @covers \App\Enums\LinkStatus
 */
describe('LinkStatus Enum', function (): void {

    test('has all expected cases with correct values', function (): void {
        $expectedCases = [
            'VALID' => 'valid',
            'BROKEN' => 'broken',
            'TIMEOUT' => 'timeout',
            'FORBIDDEN' => 'forbidden',
            'NOT_FOUND' => 'not_found',
            'INVALID_FORMAT' => 'invalid_format',
            'SECURITY_VIOLATION' => 'security_violation',
            'REDIRECT_LOOP' => 'redirect_loop',
            'SSL_ERROR' => 'ssl_error',
            'DNS_ERROR' => 'dns_error',
        ];

        $actualCases = LinkStatus::cases();
        expect($actualCases)->toHaveCount(count($expectedCases));

        foreach ($expectedCases as $name => $value) {
            $case = LinkStatus::tryFrom($value);
            expect($case)->not->toBeNull("Case {$name} with value '{$value}' should exist");
            expect($case->name)->toBe($name);
            expect($case->value)->toBe($value);
        }
    });

    test('values() returns correct array', function (): void {
        $expectedValues = [
            'valid', 'broken', 'timeout', 'forbidden', 'not_found',
            'invalid_format', 'security_violation', 'redirect_loop',
            'ssl_error', 'dns_error',
        ];

        $values = LinkStatus::values();
        expect($values)->toBeArray();
        expect($values)->toHaveCount(count($expectedValues));
        expect($values)->toEqual($expectedValues);
    });

    test('cases() returns all enum cases', function (): void {
        $cases = LinkStatus::cases();

        expect($cases)->toHaveCount(10);
        expect($cases)->each->toBeInstanceOf(LinkStatus::class);
    });

    test('tryFrom() works correctly', function (): void {
        $validValues = [
            'valid', 'broken', 'timeout', 'forbidden', 'not_found',
            'invalid_format', 'security_violation', 'redirect_loop',
            'ssl_error', 'dns_error',
        ];
        $invalidValues = ['invalid', 'unknown', '', 'VALID', 'Valid'];

        foreach ($validValues as $value) {
            $case = LinkStatus::tryFrom($value);
            expect($case)->not->toBeNull("tryFrom('{$value}') should return a case");
            expect($case->value)->toBe($value);
        }

        foreach ($invalidValues as $value) {
            $case = LinkStatus::tryFrom($value);
            expect($case)->toBeNull("tryFrom('{$value}') should return null");
        }
    });

    test('from() works correctly and throws for invalid values', function (): void {
        $validValues = [
            'valid', 'broken', 'timeout', 'forbidden', 'not_found',
            'invalid_format', 'security_violation', 'redirect_loop',
            'ssl_error', 'dns_error',
        ];
        $invalidValues = ['invalid', 'unknown', '', 'VALID', 'Valid'];

        foreach ($validValues as $value) {
            $case = LinkStatus::from($value);
            expect($case)->toBeInstanceOf(LinkStatus::class);
            expect($case->value)->toBe($value);
        }

        foreach ($invalidValues as $value) {
            expect(fn () => LinkStatus::from($value))
                ->toThrow(ValueError::class);
        }
    });

    test('isBroken() returns correct values', function (): void {
        $trueCases = [
            'broken', 'timeout', 'forbidden', 'not_found',
            'invalid_format', 'security_violation', 'redirect_loop',
            'ssl_error', 'dns_error',
        ];
        $falseCases = ['valid'];

        foreach ($trueCases as $caseValue) {
            $case = LinkStatus::from($caseValue);
            expect($case->isBroken())->toBeTrue("Method isBroken() should return true for case {$caseValue}");
        }

        foreach ($falseCases as $caseValue) {
            $case = LinkStatus::from($caseValue);
            expect($case->isBroken())->toBeFalse("Method isBroken() should return false for case {$caseValue}");
        }
    });

    test('isTemporary() returns correct values', function (): void {
        $trueCases = ['timeout', 'dns_error'];
        $falseCases = [
            'valid', 'broken', 'forbidden', 'not_found',
            'invalid_format', 'security_violation', 'redirect_loop', 'ssl_error',
        ];

        foreach ($trueCases as $caseValue) {
            $case = LinkStatus::from($caseValue);
            expect($case->isTemporary())->toBeTrue("Method isTemporary() should return true for case {$caseValue}");
        }

        foreach ($falseCases as $caseValue) {
            $case = LinkStatus::from($caseValue);
            expect($case->isTemporary())->toBeFalse("Method isTemporary() should return false for case {$caseValue}");
        }
    });

    test('isSecurityIssue() returns correct values', function (): void {
        $trueCases = ['security_violation', 'ssl_error'];
        $falseCases = [
            'valid', 'broken', 'timeout', 'forbidden', 'not_found',
            'invalid_format', 'redirect_loop', 'dns_error',
        ];

        foreach ($trueCases as $caseValue) {
            $case = LinkStatus::from($caseValue);
            expect($case->isSecurityIssue())->toBeTrue("Method isSecurityIssue() should return true for case {$caseValue}");
        }

        foreach ($falseCases as $caseValue) {
            $case = LinkStatus::from($caseValue);
            expect($case->isSecurityIssue())->toBeFalse("Method isSecurityIssue() should return false for case {$caseValue}");
        }
    });

    test('shouldRetry() returns correct values', function (): void {
        $trueCases = ['timeout', 'dns_error'];
        $falseCases = [
            'valid', 'broken', 'forbidden', 'not_found',
            'invalid_format', 'security_violation', 'redirect_loop', 'ssl_error',
        ];

        foreach ($trueCases as $caseValue) {
            $case = LinkStatus::from($caseValue);
            expect($case->shouldRetry())->toBeTrue("Method shouldRetry() should return true for case {$caseValue}");
        }

        foreach ($falseCases as $caseValue) {
            $case = LinkStatus::from($caseValue);
            expect($case->shouldRetry())->toBeFalse("Method shouldRetry() should return false for case {$caseValue}");
        }
    });

    test('getDescription() returns non-empty strings', function (): void {
        foreach (LinkStatus::cases() as $case) {
            $description = $case->getDescription();
            expect($description)->toBeString();
            expect($description)->not->toBeEmpty("Description for case {$case->name} should not be empty");
        }
    });

    test('getDescription() returns expected descriptions', function (): void {
        $expectedDescriptions = [
            'valid' => 'Link is valid and accessible',
            'broken' => 'Link is broken or inaccessible',
            'timeout' => 'Request timed out',
            'forbidden' => 'Access forbidden (403)',
            'not_found' => 'Resource not found (404)',
            'invalid_format' => 'Invalid URL format',
            'security_violation' => 'Security policy violation',
            'redirect_loop' => 'Redirect loop detected',
            'ssl_error' => 'SSL/TLS certificate error',
            'dns_error' => 'DNS resolution failed',
        ];

        foreach ($expectedDescriptions as $caseValue => $expectedReturn) {
            $case = LinkStatus::from($caseValue);
            $actualReturn = $case->getDescription();
            expect($actualReturn)->toBe($expectedReturn,
                "Method getDescription() for case {$caseValue} should return '{$expectedReturn}', got '{$actualReturn}'");
        }
    });

    test('getSeverity() returns correct integer values', function (): void {
        $expectedSeverities = [
            'valid' => 0,
            'timeout' => 2,
            'dns_error' => 2,
            'not_found' => 3,
            'invalid_format' => 3,
            'forbidden' => 4,
            'redirect_loop' => 4,
            'broken' => 5,
            'security_violation' => 5,
            'ssl_error' => 5,
        ];

        foreach ($expectedSeverities as $caseValue => $expectedReturn) {
            $case = LinkStatus::from($caseValue);
            $actualReturn = $case->getSeverity();
            expect($actualReturn)->toBe($expectedReturn,
                "Method getSeverity() for case {$caseValue} should return '{$expectedReturn}', got '{$actualReturn}'");
        }
    });

    test('getSeverity() returns values in valid range', function (): void {
        foreach (LinkStatus::cases() as $case) {
            $value = $case->getSeverity();
            expect($value)->toBeInt("Method getSeverity() for case {$case->name} should return an integer");
            expect($value)->toBeGreaterThanOrEqual(0);
            expect($value)->toBeLessThanOrEqual(5);
        }
    });

    test('getRecommendedAction() returns non-empty strings', function (): void {
        foreach (LinkStatus::cases() as $case) {
            $value = $case->getRecommendedAction();
            expect($value)->toBeString("Method getRecommendedAction() for case {$case->name} should return a string");
        }

        foreach (LinkStatus::cases() as $case) {
            $action = $case->getRecommendedAction();
            expect($action)->not->toBeEmpty("Recommended action for case {$case->name} should not be empty");
        }
    });

    test('getRecommendedAction() returns expected actions', function (): void {
        $expectedActions = [
            'valid' => 'No action required',
            'timeout' => 'Retry validation or increase timeout',
            'forbidden' => 'Check access permissions or authentication',
            'not_found' => 'Update link URL or remove broken link',
            'invalid_format' => 'Fix URL syntax and format',
            'security_violation' => 'Review security policies and URL safety',
            'redirect_loop' => 'Check redirect configuration',
            'ssl_error' => 'Verify SSL certificate validity',
            'dns_error' => 'Check domain name and DNS configuration',
            'broken' => 'Investigate and fix underlying issue',
        ];

        foreach ($expectedActions as $caseValue => $expectedReturn) {
            $case = LinkStatus::from($caseValue);
            $actualReturn = $case->getRecommendedAction();
            expect($actualReturn)->toBe($expectedReturn,
                "Method getRecommendedAction() for case {$caseValue} should return '{$expectedReturn}', got '{$actualReturn}'");
        }
    });

    test('getConsoleColor() returns valid color names', function (): void {
        $expectedColors = [
            'valid' => 'green',
            'timeout' => 'yellow',
            'dns_error' => 'yellow',
            'not_found' => 'magenta',
            'invalid_format' => 'magenta',
            'forbidden' => 'cyan',
            'redirect_loop' => 'cyan',
            'broken' => 'red',
            'security_violation' => 'red',
            'ssl_error' => 'red',
        ];

        foreach ($expectedColors as $caseValue => $expectedReturn) {
            $case = LinkStatus::from($caseValue);
            $actualReturn = $case->getConsoleColor();
            expect($actualReturn)->toBe($expectedReturn,
                "Method getConsoleColor() for case {$caseValue} should return '{$expectedReturn}', got '{$actualReturn}'");
        }
    });

    test('getIcon() returns emoji icons', function (): void {
        $expectedIcons = [
            'valid' => '✅',
            'timeout' => '⏱️',
            'forbidden' => '🚫',
            'not_found' => '❌',
            'invalid_format' => '⚠️',
            'security_violation' => '🔒',
            'redirect_loop' => '🔄',
            'ssl_error' => '🔐',
            'dns_error' => '🌐',
            'broken' => '💥',
        ];

        foreach ($expectedIcons as $caseValue => $expectedReturn) {
            $case = LinkStatus::from($caseValue);
            $actualReturn = $case->getIcon();
            expect($actualReturn)->toBe($expectedReturn,
                "Method getIcon() for case {$caseValue} should return '{$expectedReturn}', got '{$actualReturn}'");
        }
    });

    test('getGroup() returns correct groupings', function (): void {
        $expectedGroups = [
            'valid' => 'success',
            'timeout' => 'temporary',
            'dns_error' => 'temporary',
            'forbidden' => 'client_error',
            'not_found' => 'client_error',
            'invalid_format' => 'format_error',
            'security_violation' => 'format_error',
            'redirect_loop' => 'protocol_error',
            'ssl_error' => 'protocol_error',
            'broken' => 'unknown_error',
        ];

        foreach ($expectedGroups as $caseValue => $expectedReturn) {
            $case = LinkStatus::from($caseValue);
            $actualReturn = $case->getGroup();
            expect($actualReturn)->toBe($expectedReturn,
                "Method getGroup() for case {$caseValue} should return '{$expectedReturn}', got '{$actualReturn}'");
        }
    });

    test('getHttpStatusCode() returns correct nullable integers', function (): void {
        $expectedStatusCodes = [
            'valid' => 200,
            'forbidden' => 403,
            'not_found' => 404,
            'timeout' => 408,
            'redirect_loop' => 310,
            'ssl_error' => 526,
            'broken' => null,
            'invalid_format' => null,
            'security_violation' => null,
            'dns_error' => null,
        ];

        foreach ($expectedStatusCodes as $caseValue => $expectedReturn) {
            $case = LinkStatus::from($caseValue);
            $actualReturn = $case->getHttpStatusCode();
            if ($expectedReturn === null) {
                expect($actualReturn)->toBeNull("Method getHttpStatusCode() for case {$caseValue} should return null");
            } else {
                expect($actualReturn)->toBe($expectedReturn,
                    "Method getHttpStatusCode() for case {$caseValue} should return '{$expectedReturn}', got '{$actualReturn}'");
            }
        }
    });

    test('getFormattedDisplay() returns formatted strings', function (): void {
        foreach (LinkStatus::cases() as $case) {
            $display = $case->getFormattedDisplay();

            expect($display)->toBeString();
            expect($display)->not->toBeEmpty();
            expect($display)->toContain('<fg=');
            expect($display)->toContain($case->getIcon());
            expect($display)->toContain($case->getDescription());
            expect($display)->toContain($case->getConsoleColor());
        }
    });

    test('serialization works correctly', function (): void {
        foreach (LinkStatus::cases() as $case) {
            // Test JSON serialization
            $json = json_encode($case);
            expect($json)->toBeString();
            expect($json)->toContain($case->value);

            // Test that we can recreate from value
            $recreated = LinkStatus::from($case->value);
            expect($recreated)->toBe($case);
        }
    });

    test('comparison operations work correctly', function (): void {
        $cases = LinkStatus::cases();

        foreach ($cases as $case1) {
            foreach ($cases as $case2) {
                if ($case1 === $case2) {
                    expect($case1)->toBe($case2);
                    expect($case1->value)->toBe($case2->value);
                } else {
                    expect($case1)->not->toBe($case2);
                }
            }
        }
    });

    test('all methods return consistent types', function (): void {
        // Test string methods
        $stringMethods = ['getDescription', 'getRecommendedAction', 'getConsoleColor', 'getIcon', 'getGroup', 'getFormattedDisplay'];
        foreach ($stringMethods as $method) {
            foreach (LinkStatus::cases() as $case) {
                $value = $case->$method();
                expect($value)->toBeString("Method {$method}() for case {$case->name} should return a string");
            }
        }

        // Test integer methods
        foreach (LinkStatus::cases() as $case) {
            $value = $case->getSeverity();
            expect($value)->toBeInt("Method getSeverity() for case {$case->name} should return an integer");
        }

        // Test boolean methods
        $boolMethods = ['isBroken', 'isTemporary', 'isSecurityIssue', 'shouldRetry'];
        foreach ($boolMethods as $method) {
            foreach (LinkStatus::cases() as $case) {
                $value = $case->$method();
                expect($value)->toBeBool("Method {$method}() for case {$case->name} should return a boolean");
            }
        }
    });

    // Edge case and business logic tests
    test('VALID is the only non-broken status', function (): void {
        expect(LinkStatus::VALID->isBroken())->toBeFalse();

        foreach (LinkStatus::cases() as $case) {
            if ($case !== LinkStatus::VALID) {
                expect($case->isBroken())->toBeTrue("Case {$case->name} should be broken");
            }
        }
    });

    test('only temporary statuses should be retried', function (): void {
        $temporaryStatuses = [LinkStatus::TIMEOUT, LinkStatus::DNS_ERROR];
        $nonTemporaryStatuses = array_filter(
            LinkStatus::cases(),
            fn ($case): bool => ! in_array($case, $temporaryStatuses, true)
        );

        foreach ($temporaryStatuses as $status) {
            expect($status->isTemporary())->toBeTrue();
            expect($status->shouldRetry())->toBeTrue();
        }

        foreach ($nonTemporaryStatuses as $status) {
            expect($status->isTemporary())->toBeFalse();
            expect($status->shouldRetry())->toBeFalse();
        }
    });

    test('security issues have high severity', function (): void {
        $securityStatuses = [LinkStatus::SECURITY_VIOLATION, LinkStatus::SSL_ERROR];

        foreach ($securityStatuses as $status) {
            expect($status->isSecurityIssue())->toBeTrue();
            expect($status->getSeverity())->toBe(5, "Security issue {$status->name} should have maximum severity");
        }
    });

    test('severity levels are logically ordered', function (): void {
        expect(LinkStatus::VALID->getSeverity())->toBe(0); // No issue
        expect(LinkStatus::TIMEOUT->getSeverity())->toBeLessThan(LinkStatus::NOT_FOUND->getSeverity());
        expect(LinkStatus::NOT_FOUND->getSeverity())->toBeLessThan(LinkStatus::FORBIDDEN->getSeverity());
        expect(LinkStatus::FORBIDDEN->getSeverity())->toBeLessThan(LinkStatus::BROKEN->getSeverity());
    });

    test('HTTP status codes are valid when present', function (): void {
        $validHttpCodes = [200, 310, 403, 404, 408, 526];

        foreach (LinkStatus::cases() as $case) {
            $httpCode = $case->getHttpStatusCode();
            if ($httpCode !== null) {
                expect($httpCode)->toBeIn($validHttpCodes, "HTTP status code {$httpCode} for {$case->name} should be valid");
                expect($httpCode)->toBeGreaterThanOrEqual(200);
                expect($httpCode)->toBeLessThanOrEqual(599);
            }
        }
    });

    test('console colors are valid CSS/terminal colors', function (): void {
        $validColors = ['green', 'yellow', 'magenta', 'cyan', 'red'];

        foreach (LinkStatus::cases() as $case) {
            $color = $case->getConsoleColor();
            expect($color)->toBeIn($validColors, "Color {$color} for {$case->name} should be a valid terminal color");
        }
    });

    test('groups categorize statuses logically', function (): void {
        // Success group
        expect(LinkStatus::VALID->getGroup())->toBe('success');

        // Temporary issues
        $temporaryGroup = array_filter(LinkStatus::cases(), fn ($case): bool => $case->getGroup() === 'temporary');
        foreach ($temporaryGroup as $case) {
            expect($case->isTemporary())->toBeTrue("Case {$case->name} in temporary group should be temporary");
        }

        // Client errors should have 4xx-like status codes or be client-side issues
        $clientErrorGroup = array_filter(LinkStatus::cases(), fn ($case): bool => $case->getGroup() === 'client_error');
        foreach ($clientErrorGroup as $case) {
            $httpCode = $case->getHttpStatusCode();
            if ($httpCode !== null) {
                expect($httpCode)->toBeGreaterThanOrEqual(400);
                expect($httpCode)->toBeLessThan(500);
            }
        }
    });
});
