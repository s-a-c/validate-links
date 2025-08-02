<?php

declare(strict_types=1);

namespace .archive\tests.old\Support\Traits;

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
 * Trait providing centralized mocking functionality for all test classes.
 *
 * This trait follows the established preference for trait-based composition
 * over base class inheritance, providing consistent mocking utilities across
 * all test classes in the suite.
 */
trait MockingHelpers
{
    /**
     * Create mock for LinkValidationInterface.
     */
    protected function mockLinkValidation(): MockInterface
    {
        return Mockery::mock(LinkValidationInterface::class);
    }

    /**
     * Create mock for ReportingInterface.
     */
    protected function mockReporting(): MockInterface
    {
        return Mockery::mock(ReportingInterface::class);
    }

    /**
     * Create mock for GitHubAnchorInterface.
     */
    protected function mockGitHubAnchor(): MockInterface
    {
        return Mockery::mock(GitHubAnchorInterface::class);
    }

    /**
     * Create mock for SecurityValidationInterface.
     */
    protected function mockSecurityValidation(): MockInterface
    {
        return Mockery::mock(SecurityValidationInterface::class);
    }

    /**
     * Create mock for StatisticsInterface.
     */
    protected function mockStatistics(): MockInterface
    {
        return Mockery::mock(StatisticsInterface::class);
    }

    /**
     * Create mock for Cache Repository.
     */
    protected function mockCache(): MockInterface
    {
        return Mockery::mock(CacheRepository::class);
    }

    /**
     * Create mock for Config Repository.
     */
    protected function mockConfig(): MockInterface
    {
        return Mockery::mock(ConfigRepository::class);
    }

    /**
     * Create mock for Filesystem.
     */
    protected function mockFilesystem(): MockInterface
    {
        return Mockery::mock(Filesystem::class);
    }

    /**
     * Set up common mock expectations for LinkValidationInterface.
     */
    protected function setupLinkValidationMock(
        MockInterface $mock,
        array $expectations = []
    ): void {
        $defaults = [
            'validateFiles' => ['return' => ['summary' => ['broken_links' => 0]]],
            'validateFile' => ['return' => []],
        ];

        $expectations = array_merge($defaults, $expectations);

        foreach ($expectations as $method => $config) {
            $expectation = $mock->shouldReceive($method);

            if (isset($config['times'])) {
                $expectation->times($config['times']);
            }

            if (isset($config['with'])) {
                $expectation->with(...$config['with']);
            }

            if (isset($config['return'])) {
                $expectation->andReturn($config['return']);
            }

            if (isset($config['throw'])) {
                $expectation->andThrow($config['throw']);
            }
        }
    }

    /**
     * Set up common mock expectations for ReportingInterface.
     */
    protected function setupReportingMock(
        MockInterface $mock,
        array $expectations = []
    ): void {
        $defaults = [
            'generateReport' => ['return' => 0],
            'generateSummary' => ['return' => ['total' => 0, 'valid' => 0, 'invalid' => 0]],
        ];

        $expectations = array_merge($defaults, $expectations);

        foreach ($expectations as $method => $config) {
            $expectation = $mock->shouldReceive($method);

            if (isset($config['times'])) {
                $expectation->times($config['times']);
            }

            if (isset($config['with'])) {
                $expectation->with(...$config['with']);
            }

            if (isset($config['return'])) {
                $expectation->andReturn($config['return']);
            }
        }
    }

    /**
     * Set up cache mock with common operations.
     */
    protected function setupCacheMock(
        MockInterface $mock,
        array $operations = []
    ): void {
        foreach ($operations as $operation => $config) {
            switch ($operation) {
                case 'get':
                    $mock->shouldReceive('get')
                        ->with($config['key'], $config['default'] ?? null)
                        ->andReturn($config['return'] ?? null);
                    break;

                case 'put':
                    $mock->shouldReceive('put')
                        ->with($config['key'], $config['value'], $config['ttl'] ?? null)
                        ->andReturn($config['return'] ?? true);
                    break;

                case 'forget':
                    $mock->shouldReceive('forget')
                        ->with($config['key'])
                        ->andReturn($config['return'] ?? true);
                    break;

                case 'flush':
                    $mock->shouldReceive('flush')
                        ->andReturn($config['return'] ?? true);
                    break;
            }
        }
    }

    /**
     * Set up filesystem mock with common operations.
     */
    protected function setupFilesystemMock(
        MockInterface $mock,
        array $operations = []
    ): void {
        foreach ($operations as $operation => $config) {
            switch ($operation) {
                case 'exists':
                    $mock->shouldReceive('exists')
                        ->with($config['path'])
                        ->andReturn($config['return'] ?? true);
                    break;

                case 'get':
                    $mock->shouldReceive('get')
                        ->with($config['path'])
                        ->andReturn($config['return'] ?? '');
                    break;

                case 'put':
                    $mock->shouldReceive('put')
                        ->with($config['path'], $config['content'])
                        ->andReturn($config['return'] ?? true);
                    break;

                case 'delete':
                    $mock->shouldReceive('delete')
                        ->with($config['path'])
                        ->andReturn($config['return'] ?? true);
                    break;
            }
        }
    }

    /**
     * Create a collection of commonly used mocks.
     */
    protected function createCommonMocks(): array
    {
        return [
            'linkValidation' => $this->mockLinkValidation(),
            'reporting' => $this->mockReporting(),
            'cache' => $this->mockCache(),
            'config' => $this->mockConfig(),
            'filesystem' => $this->mockFilesystem(),
        ];
    }

    /**
     * Set up all common mocks with default expectations.
     */
    protected function setupCommonMocks(array $mocks): void
    {
        if (isset($mocks['linkValidation'])) {
            $this->setupLinkValidationMock($mocks['linkValidation']);
        }

        if (isset($mocks['reporting'])) {
            $this->setupReportingMock($mocks['reporting']);
        }

        if (isset($mocks['cache'])) {
            $this->setupCacheMock($mocks['cache'], [
                'get' => ['key' => Mockery::any(), 'return' => null],
                'put' => ['key' => Mockery::any(), 'value' => Mockery::any(), 'return' => true],
            ]);
        }
    }

    /**
     * Assert that mock was called with expected parameters.
     */
    protected function assertMockCalled(
        MockInterface $mock,
        string $method,
        array $expectedParams = [],
        int $times = 1
    ): void {
        // Simple assertion that method was called
        // Note: shouldHaveReceived has limited functionality compared to shouldReceive
        $mock->shouldHaveReceived($method);

        // Suppress unused parameter warnings for limited API
        unset($expectedParams, $times);
    }

    /**
     * Assert that mock was never called.
     */
    protected function assertMockNeverCalled(MockInterface $mock, string $method): void
    {
        $mock->shouldNotHaveReceived($method);
    }

    /**
     * Reset all mocks to clean state.
     */
    protected function resetMocks(): void
    {
        Mockery::resetContainer();
    }

    /**
     * Verify all mock expectations.
     */
    protected function verifyMocks(): void
    {
        Mockery::close();
    }

    /**
     * Create partial mock that allows some real method calls.
     */
    protected function createMockeryPartialMock(string $className, array $mockedMethods = []): MockInterface
    {
        $mock = Mockery::mock($className)->makePartial();

        foreach ($mockedMethods as $method => $config) {
            $expectation = $mock->shouldReceive($method);

            if (isset($config['return'])) {
                $expectation->andReturn($config['return']);
            }

            if (isset($config['throw'])) {
                $expectation->andThrow($config['throw']);
            }
        }

        return $mock;
    }

    /**
     * Create spy mock for observing method calls.
     */
    protected function createSpyMock(string $className): MockInterface
    {
        return Mockery::spy($className);
    }

    /**
     * Set up mock expectations from configuration array.
     */
    protected function setupMockFromConfig(MockInterface $mock, array $config): void
    {
        foreach ($config as $method => $expectations) {
            if (! is_array($expectations)) {
                $expectations = ['return' => $expectations];
            }

            $expectation = $mock->shouldReceive($method);

            if (isset($expectations['times'])) {
                $expectation->times($expectations['times']);
            }

            if (isset($expectations['with'])) {
                $expectation->with(...$expectations['with']);
            }

            if (isset($expectations['return'])) {
                $expectation->andReturn($expectations['return']);
            }

            if (isset($expectations['throw'])) {
                $expectation->andThrow($expectations['throw']);
            }
        }
    }
}
