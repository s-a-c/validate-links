<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Services\ValueObjects\ValidationResult;
use Tests\Support\Builders\ValidationResultBuilder as VRB;

describe('ValidationResult', function () {
    let('ok', fn () => VRB::new()
        ->url('https://example.com')
        ->status(LinkStatus::VALID)
        ->scope(ValidationScope::EXTERNAL)
        ->time(0.005)
        ->meta(['k' => 'v'])
        ->build()
    );

    let('broken', fn () => VRB::new()
        ->url('https://bad.example/404')
        ->status(LinkStatus::NOT_FOUND)
        ->scope(ValidationScope::EXTERNAL)
        ->error('Not Found')
        ->http(404)
        ->time(0.010)
        ->build()
    );

    beforeEach(function () {
        // no global state
    });

    afterEach(function () {
        // no cleanup required
    });

    it('it_should_render_string_representation_safely', function () {
        $vr = ($this->ok)();
        expect((string) $vr)->toContain('ValidationResult[')
            ->and($vr->getStatus())->toBe(LinkStatus::VALID)
            ->and($vr->getScope())->toBe(ValidationScope::EXTERNAL);
    });

    it('it_should_create_success_and_failure_helpers', function () {
        $s = ValidationResult::success('a', ValidationScope::INTERNAL, 200, 0.001, ['x' => 1], 'file.md');
        $f = ValidationResult::failure('b', ValidationScope::EXTERNAL, LinkStatus::FORBIDDEN, 'forbidden', 403, null, 0.002, [], null);

        expect($s->isValid())->toBeTrue()
            ->and($s->getHttpStatusCode())->toBe(200)
            ->and($s->getResponseTime())->toBeFloat()
            ->and($s->getFilePath())->toBe('file.md');

        expect($f->isValid())->toBeFalse()
            ->and($f->isBroken())->toBeTrue()
            ->and($f->isSecurityIssue())->toBeFalse()
            ->and($f->getError())->toBe('forbidden')
            ->and($f->getHttpStatusCode())->toBe(403);
    });

    it('it_should_construct_from_array_with_defaults', function () {
        $vr = ValidationResult::fromArray([
            'url' => 'x',
            'status' => 'broken',
            'scope' => 'external',
            'error' => null,
            'http_status_code' => 0,
            'response_time' => 0.0,
            'metadata' => ['a' => 1],
        ]);

        expect($vr->getUrl())->toBe('x')
            ->and($vr->isBroken())->toBeTrue()
            ->and($vr->getScope())->toBe(ValidationScope::EXTERNAL);
    });

    it('it_should_aggregate_multiple_results_into_summary_like_object', function () {
        $r1 = ($this->ok)();
        $r2 = ($this->broken)();
        $agg = ValidationResult::aggregate([$r1, $r2], [
            'total_links' => 2,
            'broken_links' => 1,
            'success_rate' => 50.0,
        ]);

        expect($agg->isSuccessful())->toBeFalse()
            ->and($agg->getBrokenCount())->toBe(1)
            ->and($agg->getTotalLinks())->toBe(2)
            ->and($agg->getSuccessRate())->toBe(50.0)
            ->and($agg->hasBrokenLinks())->toBeTrue();

        // Formatter-facing public props
        expect($agg->broken)->toBeArray()
            ->and($agg->files)->toBeArray()
            ->and($agg->links)->toBeArray()
            ->and($agg->duration)->toBeFloat();
    });

    it('it_should_convert_to_array_and_json', function () {
        $vr = ($this->ok)();
        $arr = $vr->toArray();

        expect($arr)->toHaveKeys([
            'url', 'status', 'scope', 'is_valid', 'is_broken', 'http_status_code',
            'redirect_url', 'response_time', 'severity', 'recommended_action', 'metadata', 'file_path',
        ]);

        $json = $vr->toJson();
        expect($json)->toBeString()
            ->and($json)->toContain('"url"')
            ->and($json)->toContain('"status"');
    });

    it('it_should_expose_derived_behaviors_from_linkstatus', function () {
        $vr = ($this->broken)();

        expect($vr->isTemporary())->toBeFalse()
            ->and($vr->shouldRetry())->toBe(LinkStatus::NOT_FOUND->shouldRetry())
            ->and($vr->getFormattedDisplay())->toBeString()
            ->and($vr->getConsoleColor())->toBeString()
            ->and($vr->getRecommendedAction())->toBeString();
    });
});