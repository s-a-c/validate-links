<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Services\ConcurrentValidationService;
use App\Services\ValueObjects\ValidationResult;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;



describe('ConcurrentValidationService', function () {
    beforeEach(function () {
        // Setup Phase: Prepare test environment with proper isolation

        // Set up reflection for testing private methods
        $this->reflection = new ReflectionClass(ConcurrentValidationService::class);

        // Create a test service instance using reflection to bypass constructor
        // We'll use reflection to create an uninitialized object
        $this->testService = $this->reflection->newInstanceWithoutConstructor();

        // Create unique test data to prevent cross-test contamination
        $testId = uniqid();
        
        // Prepare test data sets with unique identifiers
        $this->testLinks = [
            'empty' => [],
            'single' => [
                ['url' => "https://test-{$testId}.example.com", 'text' => 'Example Link', 'line' => 1],
            ],
            'multiple' => [
                ['url' => "https://test-{$testId}-1.example.com", 'text' => 'Example', 'line' => 1],
                ['url' => "https://test-{$testId}-2.google.com", 'text' => 'Google', 'line' => 2],
                ['url' => "https://test-{$testId}-3.github.com", 'text' => 'GitHub', 'line' => 3],
            ],
            'invalid' => [
                ['url' => "invalid-url-{$testId}", 'text' => 'Invalid URL', 'line' => 1],
            ],
            'mixed' => [
                ['url' => "https://valid-{$testId}.com", 'text' => 'Valid', 'line' => 1],
                ['url' => "invalid-url-{$testId}", 'text' => 'Invalid', 'line' => 2],
            ],
        ];

        // Test responses for different scenarios with proper headers
        $this->testResponses = [
            'success' => new Response(200, ['Content-Type' => 'text/html'], 'OK'),
            'created' => new Response(201, ['Content-Type' => 'application/json'], 'Created'),
            'no_content' => new Response(204, [], ''),
            'redirect' => new Response(301, ['Location' => "https://redirect-{$testId}.com"], 'Moved'),
            'not_found' => new Response(404, ['Content-Type' => 'text/html'], 'Not Found'),
            'server_error' => new Response(500, ['Content-Type' => 'text/html'], 'Internal Server Error'),
        ];

        // Test exceptions for error scenarios with unique identifiers
        $this->testExceptions = [
            'request' => new RequestException('Connection failed', new Request('HEAD', "https://test-{$testId}.example.com")),
            'generic' => new Exception("Generic error {$testId}"),
            'timeout' => new Exception("Request timeout {$testId}"),
        ];
        
        // Initialize synchronization primitives for race condition prevention
        $this->testMutex = new SplObjectStorage();
        $this->testCounter = 0;
    });

    afterEach(function () {
        // Teardown Phase: Clean up test environment with proper synchronization

        try {
            // Clear test data to prevent memory leaks with proper cleanup order
            $this->testMutex = null;
            $this->testCounter = 0;
            $this->testService = null;
            $this->reflection = null;
            $this->testLinks = null;
            $this->testResponses = null;
            $this->testExceptions = null;

            // Force garbage collection if available
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        } catch (Exception $e) {
            // Log cleanup failure but don't fail the test
            error_log("Failed to cleanup test environment: " . $e->getMessage());
        }
    });

    describe('constructor and service instantiation', function () {
        it('requires Laravel configuration for instantiation', function () {
            // This test documents that the service requires Laravel config
            // Actual instantiation tests should be in integration tests
            expect(true)->toBeTrue();
        })->skip('Service requires Laravel config - test in integration tests');
    });

    describe('createRequests private method', function () {
        it('creates HEAD requests for single link', function () {
            $method = $this->reflection->getMethod('createRequests');
            $method->setAccessible(true);

            $generator = $method->invoke($this->testService, $this->testLinks['single']);
            $requests = iterator_to_array($generator);

            expect($requests)->toHaveCount(1)
                ->and($requests[0])->toBeInstanceOf(Request::class)
                ->and($requests[0]->getMethod())->toBe('HEAD')
                ->and((string) $requests[0]->getUri())->toContain('example.com');
        });

        it('creates HEAD requests for multiple links', function () {
            $method = $this->reflection->getMethod('createRequests');
            $method->setAccessible(true);

            $generator = $method->invoke($this->testService, $this->testLinks['multiple']);
            $requests = iterator_to_array($generator);

            expect($requests)->toHaveCount(3)
                ->and($requests[0])->toBeInstanceOf(Request::class)
                ->and($requests[1])->toBeInstanceOf(Request::class)
                ->and($requests[2])->toBeInstanceOf(Request::class)
                ->and($requests[0]->getMethod())->toBe('HEAD')
                ->and($requests[1]->getMethod())->toBe('HEAD')
                ->and($requests[2]->getMethod())->toBe('HEAD')
                ->and((string) $requests[0]->getUri())->toContain('example.com')
                ->and((string) $requests[1]->getUri())->toContain('google.com')
                ->and((string) $requests[2]->getUri())->toContain('github.com');
        });

        it('preserves array indices in request creation', function () {
            $linksWithIndices = [
                2 => ['url' => 'https://first.com', 'text' => 'First', 'line' => 1],
                5 => ['url' => 'https://second.com', 'text' => 'Second', 'line' => 2],
            ];

            $method = $this->reflection->getMethod('createRequests');
            $method->setAccessible(true);

            $generator = $method->invoke($this->testService, $linksWithIndices);
            $requests = iterator_to_array($generator);

            expect($requests)->toHaveKey(2)
                ->and($requests)->toHaveKey(5)
                ->and((string) $requests[2]->getUri())->toBe('https://first.com')
                ->and((string) $requests[5]->getUri())->toBe('https://second.com');
        });

        it('handles empty links array', function () {
            $method = $this->reflection->getMethod('createRequests');
            $method->setAccessible(true);

            $generator = $method->invoke($this->testService, $this->testLinks['empty']);
            $requests = iterator_to_array($generator);

            expect($requests)->toBeEmpty();
        });
    });

    describe('processSuccessfulResponse private method', function () {
        it('processes HTTP 200 response as valid', function () {
            $method = $this->reflection->getMethod('processSuccessfulResponse');
            $method->setAccessible(true);

            $result = $method->invoke($this->testService, $this->testResponses['success'], $this->testLinks['single'][0]);

            expect($result)->toBeInstanceOf(ValidationResult::class)
                ->and($result->getUrl())->toContain('example.com')
                ->and($result->getScope())->toBe(ValidationScope::EXTERNAL)
                ->and($result->getStatus())->toBe(LinkStatus::VALID)
                ->and($result->isSuccessful())->toBeTrue()
                ->and($result->getError())->toBeNull();
        });

        it('processes all 2xx status codes as valid', function () {
            $method = $this->reflection->getMethod('processSuccessfulResponse');
            $method->setAccessible(true);

            $testCases = [
                ['response' => $this->testResponses['success'], 'code' => 200],
                ['response' => $this->testResponses['created'], 'code' => 201],
                ['response' => $this->testResponses['no_content'], 'code' => 204],
                ['response' => new Response(299, [], 'OK'), 'code' => 299],
            ];

            foreach ($testCases as $testCase) {
                $result = $method->invoke($this->testService, $testCase['response'], $this->testLinks['single'][0]);

                expect($result->getStatus())->toBe(LinkStatus::VALID)
                    ->and($result->isSuccessful())->toBeTrue()
                    ->and($result->getError())->toBeNull();
            }
        });

        it('processes non-2xx status codes as broken', function () {
            $method = $this->reflection->getMethod('processSuccessfulResponse');
            $method->setAccessible(true);

            $testCases = [
                ['response' => $this->testResponses['redirect'], 'code' => 301, 'expected' => 'HTTP 301'],
                ['response' => $this->testResponses['not_found'], 'code' => 404, 'expected' => 'HTTP 404'],
                ['response' => $this->testResponses['server_error'], 'code' => 500, 'expected' => 'HTTP 500'],
                ['response' => new Response(100, [], 'Continue'), 'code' => 100, 'expected' => 'HTTP 100'],
            ];

            foreach ($testCases as $testCase) {
                $result = $method->invoke($this->testService, $testCase['response'], $this->testLinks['single'][0]);

                expect($result->getStatus())->toBe(LinkStatus::BROKEN)
                    ->and($result->isSuccessful())->toBeFalse()
                    ->and($result->getError())->toBe($testCase['expected']);
            }
        });
    });

    describe('processFailedResponse private method', function () {
        it('processes RequestException correctly', function () {
            $method = $this->reflection->getMethod('processFailedResponse');
            $method->setAccessible(true);

            $result = $method->invoke($this->testService, $this->testExceptions['request'], $this->testLinks['single'][0]);

            expect($result)->toBeInstanceOf(ValidationResult::class)
                ->and($result->getUrl())->toContain('example.com')
                ->and($result->getScope())->toBe(ValidationScope::EXTERNAL)
                ->and($result->getStatus())->toBe(LinkStatus::BROKEN)
                ->and($result->isSuccessful())->toBeFalse()
                ->and($result->getError())->toBe('Connection failed');
        });

        it('processes generic Exception correctly', function () {
            $method = $this->reflection->getMethod('processFailedResponse');
            $method->setAccessible(true);

            $result = $method->invoke($this->testService, $this->testExceptions['generic'], $this->testLinks['single'][0]);

            expect($result->getStatus())->toBe(LinkStatus::BROKEN)
                ->and($result->isSuccessful())->toBeFalse()
                ->and($result->getError())->toContain('Generic error');
        });

        it('handles non-object reasons gracefully', function () {
            $method = $this->reflection->getMethod('processFailedResponse');
            $method->setAccessible(true);

            $testCases = [
                'string error',
                123,
                null,
                '',
                false,
                [],
            ];

            foreach ($testCases as $reason) {
                $result = $method->invoke($this->testService, $reason, $this->testLinks['single'][0]);

                expect($result->getStatus())->toBe(LinkStatus::BROKEN)
                    ->and($result->isSuccessful())->toBeFalse()
                    ->and($result->getError())->toBe('Request failed');
            }
        });

        it('handles objects without getMessage method', function () {
            $method = $this->reflection->getMethod('processFailedResponse');
            $method->setAccessible(true);

            $objectWithoutGetMessage = new stdClass();
            $result = $method->invoke($this->testService, $objectWithoutGetMessage, $this->testLinks['single'][0]);

            expect($result->getStatus())->toBe(LinkStatus::BROKEN)
                ->and($result->isSuccessful())->toBeFalse()
                ->and($result->getError())->toBe('Request failed');
        });

        it('handles empty error messages from exceptions', function () {
            $method = $this->reflection->getMethod('processFailedResponse');
            $method->setAccessible(true);

            $emptyMessageException = new Exception('');
            $result = $method->invoke($this->testService, $emptyMessageException, $this->testLinks['single'][0]);

            // Empty messages should fall back to 'Request failed' for better UX
            expect($result->getError())->toBe('Request failed');
        });

        it('handles whitespace-only error messages from exceptions', function () {
            $method = $this->reflection->getMethod('processFailedResponse');
            $method->setAccessible(true);

            $whitespaceMessageException = new Exception('   ');
            $result = $method->invoke($this->testService, $whitespaceMessageException, $this->testLinks['single'][0]);

            // Whitespace-only messages should also fall back to 'Request failed'
            expect($result->getError())->toBe('Request failed');
        });
    });

    describe('validateExternalLinksConcurrently public method', function () {
        it('requires service instantiation with Laravel config', function () {
            // This test documents that the main method requires a properly instantiated service
            // Actual functionality tests should be in integration tests
            expect(true)->toBeTrue();
        })->skip('Main method requires Laravel config - test in integration tests');
    });

    describe('service behavior documentation', function () {
        it('documents readonly class characteristics', function () {
            expect($this->reflection->isReadOnly())->toBeTrue()
                ->and($this->reflection->isFinal())->toBeTrue();
        });

        it('documents private method visibility', function () {
            $createRequestsMethod = $this->reflection->getMethod('createRequests');
            $processSuccessfulMethod = $this->reflection->getMethod('processSuccessfulResponse');
            $processFailedMethod = $this->reflection->getMethod('processFailedResponse');

            expect($createRequestsMethod->isPrivate())->toBeTrue()
                ->and($processSuccessfulMethod->isPrivate())->toBeTrue()
                ->and($processFailedMethod->isPrivate())->toBeTrue();
        });

        it('documents constructor parameters', function () {
            $constructor = $this->reflection->getConstructor();
            $parameters = $constructor->getParameters();

            expect($parameters)->toHaveCount(1)
                ->and($parameters[0]->getName())->toBe('concurrency')
                ->and($parameters[0]->isDefaultValueAvailable())->toBeTrue()
                ->and($parameters[0]->getDefaultValue())->toBe(10);
        });
    });
});
