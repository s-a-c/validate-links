<?php

declare(strict_types=1);

namespace Tests\Support;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;

/**
 * Specialized factory for HTTP client mocking with Guzzle MockHandler.
 * 
 * Provides pre-configured HTTP response scenarios, realistic error simulation,
 * and proper request/response patterns for external dependency testing.
 */
final class HttpMockFactory
{
    /** @var array<string, Response> */
    private static array $standardResponses = [];
    
    /** @var MockHandler|null */
    private static ?MockHandler $currentHandler = null;
    
    /** @var Client|null */
    private static ?Client $currentClient = null;

    /**
     * Initialize standard HTTP responses for common scenarios.
     */
    private static function initializeStandardResponses(): void
    {
        if (!empty(self::$standardResponses)) {
            return;
        }
        
        self::$standardResponses = [
            // Success responses
            'success_200' => new Response(200, [
                'Content-Type' => 'text/html; charset=utf-8',
                'Server' => 'nginx/1.18.0',
                'Cache-Control' => 'public, max-age=3600',
            ], '<html><head><title>Success</title></head><body>OK</body></html>'),
            
            'success_200_json' => new Response(200, [
                'Content-Type' => 'application/json',
                'Server' => 'nginx/1.18.0',
            ], json_encode(['status' => 'ok', 'message' => 'Success'])),
            
            // Client error responses
            'not_found_404' => new Response(404, [
                'Content-Type' => 'text/html',
                'Server' => 'nginx/1.18.0',
            ], '<html><head><title>404 Not Found</title></head><body>Not Found</body></html>'),
            
            'forbidden_403' => new Response(403, [
                'Content-Type' => 'text/html',
                'Server' => 'nginx/1.18.0',
            ], '<html><head><title>403 Forbidden</title></head><body>Forbidden</body></html>'),
            
            'unauthorized_401' => new Response(401, [
                'Content-Type' => 'text/html',
                'WWW-Authenticate' => 'Basic realm="Restricted"',
            ], '<html><head><title>401 Unauthorized</title></head><body>Unauthorized</body></html>'),
            
            // Server error responses
            'server_error_500' => new Response(500, [
                'Content-Type' => 'text/html',
                'Server' => 'nginx/1.18.0',
            ], '<html><head><title>500 Internal Server Error</title></head><body>Internal Server Error</body></html>'),
            
            'service_unavailable_503' => new Response(503, [
                'Content-Type' => 'text/html',
                'Retry-After' => '120',
            ], '<html><head><title>503 Service Unavailable</title></head><body>Service Unavailable</body></html>'),
            
            'bad_gateway_502' => new Response(502, [
                'Content-Type' => 'text/html',
                'Server' => 'nginx/1.18.0',
            ], '<html><head><title>502 Bad Gateway</title></head><body>Bad Gateway</body></html>'),
            
            // Redirect responses
            'redirect_301' => new Response(301, [
                'Location' => 'https://mock-server.test/final',
                'Cache-Control' => 'public, max-age=31536000',
            ], 'Moved Permanently'),
            
            'redirect_302' => new Response(302, [
                'Location' => 'https://mock-server.test/final',
                'Cache-Control' => 'no-cache',
            ], 'Found'),
            
            'redirect_307' => new Response(307, [
                'Location' => 'https://mock-server.test/final',
            ], 'Temporary Redirect'),
            
            // Final destination for redirects
            'final_200' => new Response(200, [
                'Content-Type' => 'text/html',
                'Server' => 'nginx/1.18.0',
            ], '<html><head><title>Final Destination</title></head><body>Final Destination</body></html>'),
            
            // Special responses
            'slow_response' => new Response(200, [
                'Content-Type' => 'text/html',
                'X-Response-Time' => '2000ms',
            ], '<html><head><title>Slow Response</title></head><body>This was slow</body></html>'),
            
            'large_response' => new Response(200, [
                'Content-Type' => 'text/html',
                'Content-Length' => (string)(10 * 1024 * 1024), // 10MB
            ], str_repeat('<p>Large content block</p>', 100000)),
        ];
    }

    /**
     * Create a mock HTTP client with predefined responses.
     */
    public static function createMockClient(array $responses): Client
    {
        self::initializeStandardResponses();
        
        // Convert string keys to actual Response objects
        $mockResponses = [];
        foreach ($responses as $response) {
            if (is_string($response) && isset(self::$standardResponses[$response])) {
                $mockResponses[] = self::$standardResponses[$response];
            } elseif ($response instanceof Response || $response instanceof \Exception) {
                $mockResponses[] = $response;
            } else {
                throw new \InvalidArgumentException('Invalid response type: ' . gettype($response));
            }
        }
        
        self::$currentHandler = new MockHandler($mockResponses);
        $handlerStack = HandlerStack::create(self::$currentHandler);
        
        self::$currentClient = new Client([
            'handler' => $handlerStack,
            'timeout' => 30,
            'connect_timeout' => 10,
            'http_errors' => false, // Don't throw exceptions for HTTP error status codes
        ]);
        
        return self::$currentClient;
    }

    /**
     * Create common HTTP error exceptions for testing.
     */
    public static function createHttpException(string $type, string $url = 'https://mock-server.test'): \Exception
    {
        $request = new Request('GET', $url);
        
        return match ($type) {
            'timeout' => new RequestException(
                'cURL error 28: Operation timed out',
                $request
            ),
            'connection_failed' => new ConnectException(
                'cURL error 7: Failed to connect to host',
                $request
            ),
            'dns_resolution' => new ConnectException(
                'cURL error 6: Could not resolve host',
                $request
            ),
            'ssl_error' => new RequestException(
                'cURL error 60: SSL certificate problem',
                $request
            ),
            'client_error_404' => new ClientException(
                'Client error: 404 Not Found',
                $request,
                self::$standardResponses['not_found_404']
            ),
            'server_error_500' => new ServerException(
                'Server error: 500 Internal Server Error',
                $request,
                self::$standardResponses['server_error_500']
            ),
            default => throw new \InvalidArgumentException("Unknown exception type: {$type}"),
        };
    }

    /**
     * Create a mock client for successful external link validation.
     */
    public static function createSuccessfulValidationClient(): Client
    {
        return self::createMockClient([
            'success_200',
            'success_200',
            'success_200',
        ]);
    }

    /**
     * Create a mock client for mixed validation results.
     */
    public static function createMixedValidationClient(): Client
    {
        return self::createMockClient([
            'success_200',      // First URL succeeds
            'not_found_404',    // Second URL not found
            'server_error_500', // Third URL server error
            'success_200',      // Fourth URL succeeds
        ]);
    }

    /**
     * Create a mock client for redirect testing.
     */
    public static function createRedirectClient(): Client
    {
        return self::createMockClient([
            'redirect_301',
            'redirect_302',
            'final_200',
        ]);
    }

    /**
     * Create a mock client for timeout testing.
     */
    public static function createTimeoutClient(): Client
    {
        $timeoutException = self::createHttpException('timeout');
        
        self::$currentHandler = new MockHandler([$timeoutException]);
        $handlerStack = HandlerStack::create(self::$currentHandler);
        
        self::$currentClient = new Client([
            'handler' => $handlerStack,
            'timeout' => 1, // Very short timeout for testing
        ]);
        
        return self::$currentClient;
    }

    /**
     * Create a mock client for error recovery testing.
     */
    public static function createErrorRecoveryClient(): Client
    {
        return self::createMockClient([
            'service_unavailable_503', // First attempt fails
            'success_200',             // Second attempt succeeds
        ]);
    }

    /**
     * Bind mock client to Laravel service container.
     */
    public static function bindToContainer(Client $client): void
    {
        app()->bind('http.client', fn() => $client);
    }

    /**
     * Get the current mock handler for inspection.
     */
    public static function getCurrentHandler(): ?MockHandler
    {
        return self::$currentHandler;
    }

    /**
     * Get the current mock client.
     */
    public static function getCurrentClient(): ?Client
    {
        return self::$currentClient;
    }

    /**
     * Get remaining requests in the mock handler queue.
     */
    public static function getRemainingRequests(): int
    {
        return self::$currentHandler ? self::$currentHandler->count() : 0;
    }

    /**
     * Reset the HTTP mock factory state.
     */
    public static function reset(): void
    {
        self::$currentHandler = null;
        self::$currentClient = null;
        
        // Restore original HTTP client binding if it exists
        if (app()->bound('http.client.original')) {
            app()->bind('http.client', fn() => app('http.client.original'));
        }
    }

    /**
     * Store original HTTP client before mocking.
     */
    public static function storeOriginalClient(): void
    {
        if (app()->bound('http.client') && !app()->bound('http.client.original')) {
            app()->bind('http.client.original', fn() => app('http.client'));
        }
    }

    /**
     * Get all available standard response types.
     */
    public static function getAvailableResponses(): array
    {
        self::initializeStandardResponses();
        return array_keys(self::$standardResponses);
    }

    /**
     * Create a custom response for specific testing needs.
     */
    public static function createCustomResponse(
        int $statusCode,
        array $headers = [],
        string $body = '',
        string $reason = ''
    ): Response {
        return new Response($statusCode, $headers, $body, '1.1', $reason);
    }
}