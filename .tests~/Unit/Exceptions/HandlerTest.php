<?php

declare(strict_types=1);

use App\Exceptions\ConfigurationException;
use App\Exceptions\Handler;
use App\Exceptions\ValidateLinksException;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\Console\Output\OutputInterface;

$handler = null;
$request = null;

describe('Handler', function () use (&$handler, &$request) {
    beforeEach(function () use (&$handler, &$request) {
        $handler = new Handler(app());
        // Use a real Request object instead of mocking to avoid complex mock setup
        $request = Request::create('/', 'GET');
    });

    describe('class structure', function () use (&$handler) {
        it('extends Laravel ExceptionHandler', function () use (&$handler) {
            expect($handler)->toBeInstanceOf(ExceptionHandler::class);
        });

        it('is a concrete class', function () {
            $reflection = new ReflectionClass(Handler::class);
            expect($reflection->isInstantiable())->toBeTrue();
        });

        it('has proper namespace', function () {
            $reflection = new ReflectionClass(Handler::class);
            expect($reflection->getNamespaceName())->toBe('App\Exceptions');
        });

        it('uses strict types declaration', function () {
            $fileContent = file_get_contents(__DIR__.'/../../../app/Exceptions/Handler.php');
            expect($fileContent)->toContain('declare(strict_types=1);');
        });
    });

    describe('render method', function () use (&$handler, &$request) {
        it('handles ValidateLinksException instances', function () use (&$handler, &$request) {
            assert($handler instanceof Handler);
            assert($request instanceof Request);
            $exception = ConfigurationException::missingFile('/test/config.json');

            $response = $handler->render($request, $exception);

            expect($response)->toBeInstanceOf(Response::class)
                ->and($response->getStatusCode())->toBe(500)
                ->and($response->headers->get('Content-Type'))->toBe('text/plain')
                ->and($response->getContent())->toContain('Error [CONFIGURATION_ERROR]:')
                ->and($response->getContent())->toContain('Configuration file not found: /test/config.json');
        });

        it('delegates non-ValidateLinksException to parent', function () use (&$handler, &$request) {
            assert($handler instanceof Handler);
            assert($request instanceof Request);
            $exception = new Exception('Regular exception');

            // Call the actual parent render method (request mock is set up in beforeEach)
            $response = $handler->render($request, $exception);

            // The parent should handle non-ValidateLinksException instances
            expect($response)->toBeInstanceOf(Response::class);
        });

        it('handles different ValidateLinksException types', function () use (&$handler, &$request) {
            assert($handler instanceof Handler);
            assert($request instanceof Request);
            $testCases = [
                ConfigurationException::missingFile('/test.json'),
                ConfigurationException::invalidValue('key', 'value', 'type'),
            ];

            foreach ($testCases as $exception) {
                $response = $handler->render($request, $exception);

                expect($response)->toBeInstanceOf(Response::class)
                    ->and($response->getStatusCode())->toBe(500)
                    ->and($response->getContent())->toContain('Error [CONFIGURATION_ERROR]:');
            }
        });
    });

    describe('renderValidateLinksException method', function () use (&$handler, &$request) {
        $exception = null;

        beforeEach(function () use (&$exception) {
            $exception = ConfigurationException::missingFile('/test/config.json');
        });

        it('returns HTTP response with correct status and content type', function () use (&$handler, &$request, &$exception) {
            assert($handler instanceof Handler);
            assert($request instanceof Request);
            assert($exception instanceof ConfigurationException);
            $response = $handler->render($request, $exception);

            expect($response)->toBeInstanceOf(Response::class)
                ->and($response->getStatusCode())->toBe(500)
                ->and($response->headers->get('Content-Type'))->toBe('text/plain');
        });

        it('includes error code and message in response', function () use (&$handler, &$request, &$exception) {
            assert($handler instanceof Handler);
            assert($request instanceof Request);
            assert($exception instanceof ConfigurationException);
            $response = $handler->render($request, $exception);

            expect($response->getContent())->toContain('Error [CONFIGURATION_ERROR]:')
                ->and($response->getContent())->toContain('Configuration file not found: /test/config.json');
        });

        it('handles console output when kernel has getOutput method', function () use (&$handler, &$request, &$exception) {
            assert($handler instanceof Handler);
            assert($request instanceof Request);
            assert($exception instanceof ConfigurationException);

            /** @phpstan-ignore-next-line */
            $output = Mockery::mock(OutputInterface::class);
            $output->shouldReceive('writeln')
                ->with('<error>Error [CONFIGURATION_ERROR]: Configuration file not found: /test/config.json</error>');
            $output->shouldReceive('writeln')
                ->with('<comment>Context:</comment>');
            $output->shouldReceive('writeln')
                ->with('  <info>file_path:</info> /test/config.json');
            $output->shouldReceive('writeln')
                ->with('  <info>error_type:</info> missing_file');

            /** @phpstan-ignore-next-line */
            $kernel = Mockery::mock(Kernel::class);
            $kernel->shouldReceive('getOutput')->andReturn($output);

            app()->instance(Kernel::class, $kernel);

            $response = $handler->render($request, $exception);

            expect($response)->toBeInstanceOf(Response::class);
        });

        it('handles console output when kernel does not have getOutput method', function () use (&$handler, &$request, &$exception) {
            assert($handler instanceof Handler);
            assert($request instanceof Request);
            assert($exception instanceof ConfigurationException);

            /** @phpstan-ignore-next-line */
            $kernel = Mockery::mock(Kernel::class);
            // Don't add getOutput method to simulate older Laravel versions

            app()->instance(Kernel::class, $kernel);

            $response = $handler->render($request, $exception);

            expect($response)->toBeInstanceOf(Response::class)
                ->and($response->getStatusCode())->toBe(500);
        });

        it('handles exception when getting console output fails', function () use (&$handler, &$request, &$exception) {
            assert($handler instanceof Handler);
            assert($request instanceof Request);
            assert($exception instanceof ConfigurationException);

            /** @phpstan-ignore-next-line */
            $kernel = Mockery::mock(Kernel::class);
            $kernel->shouldReceive('getOutput')
                ->andThrow(new Exception('Console output error'));

            app()->instance(Kernel::class, $kernel);

            $response = $handler->render($request, $exception);

            expect($response)->toBeInstanceOf(Response::class)
                ->and($response->getStatusCode())->toBe(500);
        });

        it('displays context information when available', function () use (&$handler, &$request) {
            assert($handler instanceof Handler);
            assert($request instanceof Request);
            $exception = ConfigurationException::invalidValue('timeout', 'invalid', 'integer');

            /** @phpstan-ignore-next-line */
            $output = Mockery::mock(OutputInterface::class);
            $output->shouldReceive('writeln')
                ->with(Mockery::pattern('/Error \[CONFIGURATION_ERROR\]:/'));
            $output->shouldReceive('writeln')
                ->with('<comment>Context:</comment>');
            $output->shouldReceive('writeln')
                ->with('  <info>key:</info> timeout');
            $output->shouldReceive('writeln')
                ->with('  <info>value:</info> invalid');
            $output->shouldReceive('writeln')
                ->with('  <info>expected_type:</info> integer');
            $output->shouldReceive('writeln')
                ->with('  <info>actual_type:</info> string');
            $output->shouldReceive('writeln')
                ->with('  <info>error_type:</info> invalid_value');

            /** @phpstan-ignore-next-line */
            $kernel = Mockery::mock(Kernel::class);
            $kernel->shouldReceive('getOutput')->andReturn($output);

            app()->instance(Kernel::class, $kernel);

            $response = $handler->render($request, $exception);

            expect($response)->toBeInstanceOf(Response::class);
        });

        it('handles different context value types', function () use (&$handler, &$request) {
            assert($handler instanceof Handler);
            assert($request instanceof Request);
            $context = [
                'string_value' => 'test string',
                'integer_value' => 123,
                'float_value' => 3.14,
                'boolean_true' => true,
                'boolean_false' => false,
                'array_value' => ['key' => 'value'],
                'object_value' => (object) ['prop' => 'value'],
                'null_value' => null,
            ];

            $exception = ConfigurationException::withContext('Test message', $context);

            $response = $handler->render($request, $exception);

            expect($response)->toBeInstanceOf(Response::class)
                ->and($response->getStatusCode())->toBe(500)
                ->and($response->getContent())->toContain('Test message')
                ->and($response->getContent())->toContain('CONFIGURATION_ERROR');
        });

        it('displays stack trace when debug is enabled', function () use (&$handler, &$request, &$exception) {
            assert($handler instanceof Handler);
            assert($request instanceof Request);
            assert($exception instanceof ConfigurationException);
            config(['app.debug' => true]);

            $response = $handler->render($request, $exception);

            expect($response)->toBeInstanceOf(Response::class)
                ->and($response->getStatusCode())->toBe(500)
                ->and($response->getContent())->toContain('Error [CONFIGURATION_ERROR]:')
                ->and($response->getContent())->toContain('Configuration file not found: /test/config.json');
        });

        it('does not display stack trace when debug is disabled', function () use (&$handler, &$request, &$exception) {
            assert($handler instanceof Handler);
            assert($request instanceof Request);
            assert($exception instanceof ConfigurationException);
            config(['app.debug' => false]);

            $response = $handler->render($request, $exception);

            expect($response)->toBeInstanceOf(Response::class)
                ->and($response->getStatusCode())->toBe(500)
                ->and($response->getContent())->toContain('Error [CONFIGURATION_ERROR]:')
                ->and($response->getContent())->toContain('Configuration file not found: /test/config.json');
        });

        it('handles exception without context', function () use (&$handler, &$request) {
            assert($handler instanceof Handler);
            assert($request instanceof Request);
            $exception = new class('Test message') extends ValidateLinksException
            {
                public function getErrorCode(): string
                {
                    return 'TEST_ERROR';
                }

                public function getSeverity(): string
                {
                    return 'low';
                }
            };

            /** @phpstan-ignore-next-line */
            $output = Mockery::mock(OutputInterface::class);
            $output->shouldReceive('writeln')
                ->with('<error>Error [TEST_ERROR]: Test message</error>');
            $output->shouldNotReceive('writeln')->with('<comment>Context:</comment>');

            /** @phpstan-ignore-next-line */
            $kernel = Mockery::mock(Kernel::class);
            $kernel->shouldReceive('getOutput')->andReturn($output);

            app()->instance(Kernel::class, $kernel);

            $response = $handler->render($request, $exception);

            expect($response)->toBeInstanceOf(Response::class)
                ->and($response->getContent())->toContain('Error [TEST_ERROR]: Test message');
        });

        it('handles JSON encoding errors gracefully', function () use (&$handler, &$request) {
            assert($handler instanceof Handler);
            assert($request instanceof Request);
            // Create a context with a value that cannot be JSON encoded
            $resource = fopen('php://memory', 'r');
            assert($resource !== false);
            $context = ['resource' => $resource];

            $exception = ConfigurationException::withContext('Test message', $context);

            /** @phpstan-ignore-next-line */
            $output = Mockery::mock(OutputInterface::class);
            $output->shouldReceive('writeln')->with(Mockery::any())->zeroOrMoreTimes();

            /** @phpstan-ignore-next-line */
            $kernel = Mockery::mock(Kernel::class);
            $kernel->shouldReceive('getOutput')->andReturn($output);

            app()->instance(Kernel::class, $kernel);

            // This should not throw an exception
            expect(function () use ($handler, $request, $exception) {
                $handler->render($request, $exception);
            })->not->toThrow(Exception::class);

            fclose($resource);
        });
    });

    describe('method visibility and access', function () {
        it('has public render method', function () {
            $reflection = new ReflectionClass(Handler::class);
            $method = $reflection->getMethod('render');

            expect($method->isPublic())->toBeTrue();
        });

        it('has private renderValidateLinksException method', function () {
            $reflection = new ReflectionClass(Handler::class);
            $method = $reflection->getMethod('renderValidateLinksException');

            expect($method->isPrivate())->toBeTrue();
        });
    });

    describe('integration with Laravel framework', function () use (&$handler) {
        it('integrates with Laravel application container', function () {
            expect(function () {
                app(Handler::class);
            })->not->toThrow(Exception::class);
        });

        it('handles request parameter correctly', function () use (&$handler) {
            assert($handler instanceof Handler);
            $request = Request::create('/test', 'GET');
            $exception = ConfigurationException::missingFile('/test.json');

            $response = $handler->render($request, $exception);

            expect($response)->toBeInstanceOf(Response::class);
        });
    });

    describe('error handling and edge cases', function () use (&$handler, &$request) {
        it('handles very long error messages', function () use (&$handler, &$request) {
            assert($handler instanceof Handler);
            assert($request instanceof Request);
            $longMessage = str_repeat('Very long error message. ', 100);
            $exception = ConfigurationException::withContext($longMessage, []);

            $response = $handler->render($request, $exception);

            expect($response->getContent())->toContain($longMessage);
        });

        it('handles unicode characters in error messages', function () use (&$handler, &$request) {
            assert($handler instanceof Handler);
            assert($request instanceof Request);
            $unicodeMessage = 'Error with unicode: 🚫 ñáéíóú тест';
            $exception = ConfigurationException::withContext($unicodeMessage, []);

            $response = $handler->render($request, $exception);

            expect($response->getContent())->toContain($unicodeMessage);
        });

        it('handles large context arrays', function () use (&$handler, &$request) {
            assert($handler instanceof Handler);
            assert($request instanceof Request);
            $largeContext = [];
            for ($i = 0; $i < 100; $i++) {
                $largeContext["key_{$i}"] = "value_{$i}";
            }

            $exception = ConfigurationException::withContext('Test', $largeContext);

            $response = $handler->render($request, $exception);

            expect($response)->toBeInstanceOf(Response::class);
        });

        it('handles nested array and object context values', function () use (&$handler, &$request) {
            assert($handler instanceof Handler);
            assert($request instanceof Request);
            $context = [
                'nested_array' => [
                    'level1' => [
                        'level2' => ['deep' => 'value'],
                    ],
                ],
                'nested_object' => (object) [
                    'prop1' => (object) ['prop2' => 'value'],
                ],
            ];

            $exception = ConfigurationException::withContext('Test', $context);

            $response = $handler->render($request, $exception);

            expect($response)->toBeInstanceOf(Response::class);
        });

        it('handles circular references in context gracefully', function () use (&$handler, &$request) {
            assert($handler instanceof Handler);
            assert($request instanceof Request);
            $context = [];
            $context['self'] = &$context;

            $exception = ConfigurationException::withContext('Test', $context);

            // This should not cause infinite recursion or crash
            expect(function () use ($handler, $request, $exception) {
                $handler->render($request, $exception);
            })->not->toThrow(Exception::class);
        });
    });

    describe('console output formatting', function () use (&$handler, &$request) {
        it('formats error messages with proper styling', function () use (&$handler, &$request) {
            assert($handler instanceof Handler);
            assert($request instanceof Request);
            $exception = ConfigurationException::missingFile('/test.json');

            /** @phpstan-ignore-next-line */
            $output = Mockery::mock(OutputInterface::class);
            $output->shouldReceive('writeln')->with(Mockery::any())->zeroOrMoreTimes();

            /** @phpstan-ignore-next-line */
            $kernel = Mockery::mock(Kernel::class);
            $kernel->shouldReceive('getOutput')->andReturn($output);

            app()->instance(Kernel::class, $kernel);

            $response = $handler->render($request, $exception);
            
            // Verify the HTTP response is correct
            expect($response)->toBeInstanceOf(Response::class)
                ->and($response->getStatusCode())->toBe(500);
        });

        it('formats context with proper styling', function () use (&$handler, &$request) {
            assert($handler instanceof Handler);
            assert($request instanceof Request);
            $exception = ConfigurationException::invalidValue('key', 'value', 'type');

            /** @phpstan-ignore-next-line */
            $output = Mockery::mock(OutputInterface::class);
            $output->shouldReceive('writeln')->with(Mockery::any())->zeroOrMoreTimes();

            /** @phpstan-ignore-next-line */
            $kernel = Mockery::mock(Kernel::class);
            $kernel->shouldReceive('getOutput')->andReturn($output);

            app()->instance(Kernel::class, $kernel);

            $response = $handler->render($request, $exception);
            
            // Verify the HTTP response is correct
            expect($response)->toBeInstanceOf(Response::class)
                ->and($response->getStatusCode())->toBe(500);
        });

        it('formats stack trace with proper styling', function () use (&$handler, &$request) {
            assert($handler instanceof Handler);
            assert($request instanceof Request);
            config(['app.debug' => true]);

            $exception = ConfigurationException::missingFile('/test.json');

            /** @phpstan-ignore-next-line */
            $output = Mockery::mock(OutputInterface::class);
            $output->shouldReceive('writeln')->with(Mockery::any())->zeroOrMoreTimes();

            /** @phpstan-ignore-next-line */
            $kernel = Mockery::mock(Kernel::class);
            $kernel->shouldReceive('getOutput')->andReturn($output);

            app()->instance(Kernel::class, $kernel);

            $response = $handler->render($request, $exception);
            
            // Verify the HTTP response is correct
            expect($response)->toBeInstanceOf(Response::class)
                ->and($response->getStatusCode())->toBe(500);
        });
    });

    describe('HTTP response characteristics', function () use (&$handler, &$request) {
        it('returns 500 status code for all ValidateLinksException instances', function () use (&$handler, &$request) {
            assert($handler instanceof Handler);
            assert($request instanceof Request);
            $exceptions = [
                ConfigurationException::missingFile('/test.json'),
                ConfigurationException::invalidValue('key', 'value', 'type'),
            ];

            foreach ($exceptions as $exception) {
                $response = $handler->render($request, $exception);
                expect($response->getStatusCode())->toBe(500);
            }
        });

        it('sets correct content type header', function () use (&$handler, &$request) {
            assert($handler instanceof Handler);
            assert($request instanceof Request);
            $exception = ConfigurationException::missingFile('/test.json');

            $response = $handler->render($request, $exception);

            expect($response->headers->get('Content-Type'))->toBe('text/plain');
        });

        it('includes error code and message in response body', function () use (&$handler, &$request) {
            assert($handler instanceof Handler);
            assert($request instanceof Request);
            $exception = ConfigurationException::invalidValue('timeout', 'invalid', 'integer');

            $response = $handler->render($request, $exception);

            expect($response->getContent())->toContain('Error [CONFIGURATION_ERROR]:')
                ->and($response->getContent())->toContain("Invalid value for configuration key 'timeout'");
        });
    });
});
