<?php

declare(strict_types=1);

namespace .archive\tests.old\Support\Factories;

use App\Services\Contracts\GitHubAnchorInterface;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Services\Contracts\SecurityValidationInterface;
use App\Services\Contracts\StatisticsInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Filesystem\Filesystem;
use Mockery;
use Mockery\MockInterface;

/**
 * Factory for creating properly typed mocks.
 *
 * This replaces the trait-based approach to resolve PHPStan issues
 * and provide better type safety in tests.old.
 */
final class MockFactory
{
    public static function linkValidation(): MockInterface&LinkValidationInterface
    {
        return Mockery::mock(LinkValidationInterface::class);
    }

    public static function reporting(): MockInterface&ReportingInterface
    {
        return Mockery::mock(ReportingInterface::class);
    }

    public static function gitHubAnchor(): MockInterface&GitHubAnchorInterface
    {
        return Mockery::mock(GitHubAnchorInterface::class);
    }

    public static function securityValidation(): MockInterface&SecurityValidationInterface
    {
        return Mockery::mock(SecurityValidationInterface::class);
    }

    public static function statistics(): MockInterface&StatisticsInterface
    {
        return Mockery::mock(StatisticsInterface::class);
    }

    public static function cache(): MockInterface&CacheRepository
    {
        return Mockery::mock(CacheRepository::class);
    }

    public static function config(): MockInterface&ConfigRepository
    {
        return Mockery::mock(ConfigRepository::class);
    }

    public static function filesystem(): MockInterface&Filesystem
    {
        return Mockery::mock(Filesystem::class);
    }

    /**
     * Create LinkValidation mock with common default expectations.
     */
    public static function linkValidationWithDefaults(): MockInterface&LinkValidationInterface
    {
        $mock = self::linkValidation();
        $mock->shouldReceive('validateFiles')
            ->andReturn(['summary' => ['broken_links' => 0]])
            ->byDefault();

        return $mock;
    }

    /**
     * Create Reporting mock with common default expectations.
     */
    public static function reportingWithDefaults(): MockInterface&ReportingInterface
    {
        $mock = self::reporting();
        $mock->shouldReceive('generateReport')
            ->andReturn('')
            ->byDefault();

        return $mock;
    }
}
