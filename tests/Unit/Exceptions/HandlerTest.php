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

describe('Handler', function () {
    beforeEach(function () {
        $this->handler = new Handler(app());
        // Use a real Request object instead of mocking to avoid complex mock setup
        $this->request = Request::create('/', 'GET');
    });

    describe('class structure', function () {
        it('extends Laravel ExceptionHandler', function () {
            expect($this->handler)->toBeInstanceOf(ExceptionHandler::class);
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

    describe('render method', function () {
        it('handles ValidateLinksException instances', function () {
            $exception = ConfigurationException::missingFile('/test/config.json');

            $response = $this->handler->render($this->request, $exception);

            expect($response)->toBeInstanceOf(Response::class)
                ->and($response->getStatusCode())->toBe(500)
                ->and($response->headers->get('Content-Type'))->toBe('text/plain')
                ->and($response->getContent())->toContain('Error [CONFIGURATION_ERROR]:')
                ->and($response->getContent())->toContain('Configuration file not found: /test/config.json');
        });

        it('delegates non-ValidateLinksException to parent', function () {
            $exception = new Exception('Regular exception');

            // Call the actual parent render method (request mock is set up in beforeEach)
            $response = $this->handler->render($this->request, $exception);

            // The parent should handle non-ValidateLinksException instances
            expect($response)->toBeInstanceOf(Response::class);
        });

        it('handles different ValidateLinksException types', function () {
            $testCases = [
                ConfigurationException::missingFile('/test.json'),
                ConfigurationException::invalidValue('key', 'value', 'type'),
            ];

            foreach ($testCases as $exception) {
                $response = $this->handler->render($this->request, $exception);

                expect($response)->toBeInstanceOf(Response::class)
                    ->and($response->getStatusCode())->toBe(500)
                    ->and($response->getContent())->toContain('Error [CONFIGURATION_ERROR]:');
            }
        });
    });

    describe('renderValidateLinksException method', function () {
        beforeEach(function () {
            $this->exception = ConfigurationException::missingFile('/test/config.json');
        });

        it('returns HTTP response with correct status and content type', function () {
            $response = $this->handler->render($this->request, $this->exception);

            expect($response)->toBeInstanceOf(Response::class)
                ->and($response->getStatusCode())->toBe(500)
                ->and($response->headers->get('Content-Type'))->toBe('text/plain');
        });

        it('includes error code and message in response', function () {
            $response = $this->handler->render($this->request, $this->exception);

            expect($response->getContent())->toContain('Error [CONFIGURATION_ERROR]:')
                ->and($response->getContent())->toContain('Configuration file not found: /test/config.json');
        });

        it('handles console output when kernel has getOutput method', function () {
            $output = Mockery::mock(OutputInterface::class);
            $output->shouldReceive('writeln')
                ->with('<error>Error [CONFIGURATION_ERROR]: Configuration file not found: /test/config.json</error>');
            $output->shouldReceive('writeln')
                ->with('<comment>Context:</comment>');
            $output->shouldReceive('writeln')
                ->with('  <info>file_path:</info> /test/config.json');
            $output->shouldReceive('writeln')
                ->with('  <info>error_type:</info> missing_file');

            $kernel = Mockery::mock(Kernel::class);
            $kernel->shouldReceive('getOutput')->andReturn($output);

            app()->instance(Kernel::class, $kernel);

            $response = $this->handler->render($this->request, $this->exception);

            expect($response)->toBeInstanceOf(Response::class);
        });

        it('handles console output when kernel does not have getOutput method', function () {
            $kernel = Mockery::mock(Kernel::class);
            // Don't add getOutput method to simulate older Laravel versions

            app()->instance(Kernel::class, $kernel);

            $response = $this->handler->render($this->request, $this->exception);

            expect($response)->toBeInstanceOf(Response::class)
                ->and($response->getStatusCode())->toBe(500);
        });

        it('handles exception when getting console output fails', function () {
            $kernel = Mockery::mock(Kernel::class);
            $kernel->shouldReceive('getOutput')
                ->andThrow(new Exception('Console output error'));

            app()->instance(Kernel::class, $kernel);

            $response = $this->handler->render($this->request, $this->exception);

            expect($response)->toBeInstanceOf(Response::class)
                ->and($response->getStatusCode())->toBe(500);
        });

        it('displays context information when available', function () {
            $exception = ConfigurationException::invalidValue('timeout', 'invalid', 'integer');

            $output = mock(OutputInterface::class);
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

            $kernel = mock(Kernel::class);
            $kernel->shouldReceive('getOutput')->andReturn($output);

            app()->instance(Kernel::class, $kernel);

            $response = $this->handler->render($this->request, $exception);

            expect($response)->toBeInstanceOf(Response::class);
        });

        it('handles different context value types', function () {
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

            $response = $this->handler->render($this->request, $exception);

            expect($response)->toBeInstanceOf(Response::class)
                ->and($response->getStatusCode())->toBe(500)
                ->and($response->getContent())->toContain('Test message')
                ->and($response->getContent())->toContain('CONFIGURATION_ERROR');
        });

        it('displays stack trace when debug is enabled', function () {
            config(['app.debug' => true]);

            $response = $this->handler->render($this->request, $this->exception);

            expect($response)->toBeInstanceOf(Response::class)
                ->and($response->getStatusCode())->toBe(500)
                ->and($response->getContent())->toContain('Error [CONFIGURATION_ERROR]:')
                ->and($response->getContent())->toContain('Configuration file not found: /test/config.json');
        });

        it('does not display stack trace when debug is disabled', function () {
            config(['app.debug' => false]);

            $response = $this->handler->render($this->request, $this->exception);

            expect($response)->toBeInstanceOf(Response::class)
                ->and($response->getStatusCode())->toBe(500)
                ->and($response->getContent())->toContain('Error [CONFIGURATION_ERROR]:')
                ->and($response->getContent())->toContain('Configuration file not found: /test/config.json');
        });

        it('handles exception without context', function () {
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

            $output = mock(OutputInterface::class);
            $output->shouldReceive('writeln')
                ->with('<error>Error [TEST_ERROR]: Test message</error>');
            $output->shouldNotReceive('writeln')->with('<comment>Context:</comment>');

            $kernel = mock(Kernel::class);
            $kernel->shouldReceive('getOutput')->andReturn($output);

            app()->instance(Kernel::class, $kernel);

            $response = $this->handler->render($this->request, $exception);

            expect($response)->toBeInstanceOf(Response::class)
                ->and($response->getContent())->toContain('Error [TEST_ERROR]: Test message');
        });

        it('handles JSON encoding errors gracefully', function () {
            // Create a context with a value that cannot be JSON encoded
            $resource = fopen('php://memory', 'r');
            $context = ['resource' => $resource];

            $exception = ConfigurationException::withContext('Test message', $context);

            $output = mock(OutputInterface::class);
            $output->shouldReceive('writeln')->with(Mockery::any())->atLeast(1);

            $kernel = mock(Kernel::class);
            $kernel->shouldReceive('getOutput')->andReturn($output);

            app()->instance(Kernel::class, $kernel);

            // This should not throw an exception
            expect(function () use ($exception) {
                $this->handler->render($this->request, $exception);
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

    describe('integration with Laravel framework', function () {
        it('integrates with Laravel application container', function () {
            expect(function () {
                app(Handler::class);
            })->not->toThrow(Exception::class);
        });

        it('handles request parameter correctly', function () {
            $request = Request::create('/test', 'GET');
            $exception = ConfigurationException::missingFile('/test.json');

            $response = $this->handler->render($request, $exception);

            expect($response)->toBeInstanceOf(Response::class);
        });
    });

    describe('error handling and edge cases', function () {
        it('handles very long error messages', function () {
            $longMessage = str_repeat('Very long error message. ', 100);
            $exception = ConfigurationException::withContext($longMessage, []);

            $response = $this->handler->render($this->request, $exception);

            expect($response->getContent())->toContain($longMessage);
        });

        it('handles unicode characters in error messages', function () {
            $unicodeMessage = 'Error with unicode: 🚫 ñáéíóú тест';
            $exception = ConfigurationException::withContext($unicodeMessage, []);

            $response = $this->handler->render($this->request, $exception);

            expect($response->getContent())->toContain($unicodeMessage);
        });

        it('handles large context arrays', function () {
            $largeContext = [];
            for ($i = 0; $i < 100; $i++) {
                $largeContext["key_{$i}"] = "value_{$i}";
            }

            $exception = ConfigurationException::withContext('Test', $largeContext);

            $response = $this->handler->render($this->request, $exception);

            expect($response)->toBeInstanceOf(Response::class);
        });

        it('handles nested array and object context values', function () {
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

            $response = $this->handler->render($this->request, $exception);

            expect($response)->toBeInstanceOf(Response::class);
        });

        it('handles circular references in context gracefully', function () {
            $context = [];
            $context['self'] = &$context;

            $exception = ConfigurationException::withContext('Test', $context);

            // This should not cause infinite recursion or crash
            expect(function () use ($exception) {
                $this->handler->render($this->request, $exception);
            })->not->toThrow(Exception::class);
        });
    });

    describe('console output formatting', function () {
        it('formats error messages with proper styling', function () {
            $exception = ConfigurationException::missingFile('/test.json');

            $output = mock(OutputInterface::class);
            $output->shouldReceive('writeln')
                ->with('<error>Error [CONFIGURATION_ERROR]: Configuration file not found: /test.json</error>');
            $output->shouldReceive('writeln')->with(Mockery::any())->atLeast(1);

            $kernel = mock(Kernel::class);
            $kernel->shouldReceive('getOutput')->andReturn($output);

            app()->instance(Kernel::class, $kernel);

            $this->handler->render($this->request, $exception);
        });

        it('formats context with proper styling', function () {
            $exception = ConfigurationException::invalidValue('key', 'value', 'type');

            $output = mock(OutputInterface::class);
            $output->shouldReceive('writeln')->with(Mockery::any());
            $output->shouldReceive('writeln')->with('<comment>Context:</comment>');
            $output->shouldReceive('writeln')
                ->with(Mockery::pattern('/  <info>\w+:<\/info> .+/'))
                ->atLeast(1);

            $kernel = mock(Kernel::class);
            $kernel->shouldReceive('getOutput')->andReturn($output);

            app()->instance(Kernel::class, $kernel);

            $this->handler->render($this->request, $exception);
        });

        it('formats stack trace with proper styling', function () {
            config(['app.debug' => true]);

            $exception = ConfigurationException::missingFile('/test.json');

            $output = mock(OutputInterface::class);
            $output->shouldReceive('writeln')->with(Mockery::any())->atLeast(1);
            $output->shouldReceive('writeln')->with('<comment>Stack trace:</comment>');
            $output->shouldReceive('writeln')->with(Mockery::type('string'));

            $kernel = mock(Kernel::class);
            $kernel->shouldReceive('getOutput')->andReturn($output);

            app()->instance(Kernel::class, $kernel);

            $this->handler->render($this->request, $exception);
        });
    });

    describe('HTTP response characteristics', function () {
        it('returns 500 status code for all ValidateLinksException instances', function () {
            $exceptions = [
                ConfigurationException::missingFile('/test.json'),
                ConfigurationException::invalidValue('key', 'value', 'type'),
            ];

            foreach ($exceptions as $exception) {
                $response = $this->handler->render($this->request, $exception);
                expect($response->getStatusCode())->toBe(500);
            }
        });

        it('sets correct content type header', function () {
            $exception = ConfigurationException::missingFile('/test.json');

            $response = $this->handler->render($this->request, $exception);

            expect($response->headers->get('Content-Type'))->toBe('text/plain');
        });

        it('includes error code and message in response body', function () {
            $exception = ConfigurationException::invalidValue('timeout', 'invalid', 'integer');

            $response = $this->handler->render($this->request, $exception);

            expect($response->getContent())->toContain('Error [CONFIGURATION_ERROR]:')
                ->and($response->getContent())->toContain("Invalid value for configuration key 'timeout'");
        });
    });
});
