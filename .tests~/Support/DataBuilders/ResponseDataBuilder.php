<?php

declare(strict_types=1);

namespace Tests\Support\DataBuilders;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;

/**
 * Specialized builder for creating comprehensive HTTP response test data.
 * 
 * Provides deterministic HTTP response generation for various testing scenarios including
 * success responses, error responses, redirects, and edge cases. All data generation
 * is seeded and reproducible across test runs.
 * 
 * @package Tests\Support\DataBuilders
 * @author Roo
 * @since 1.0.0
 */
final class ResponseDataBuilder
{
    /**
     * Deterministic seed for response generation.
     * 
     * @var int
     */
    private static int $seed = 13579;
    
    /**
     * Counter for sequential response generation.
     * 
     * @var int
     */
    private static int $counter = 0;
    
    /**
     * Cache for loaded fixture data.
     * 
     * @var array<string, mixed>
     */
    private static array $fixtureCache = [];
    
    /**
     * Set the seed for deterministic response generation.
     * 
     * @param int $seed The seed value for deterministic generation
     * @return void
     */
    public static function setSeed(int $seed): void
    {
        self::$seed = $seed;
        self::$counter = 0;
    }
    
    /**
     * Reset the response generation state.
     * 
     * @return void
     */
    public static function reset(): void
    {
        self::$seed = 13579;
        self::$counter = 0;
        self::$fixtureCache = [];
    }
    
    /**
     * Load fixture data from JSON files with caching.
     * 
     * @param string $fixturePath Path to fixture file relative to tests/Fixtures/
     * @return array<string, mixed> Fixture data
     */
    private static function loadFixture(string $fixturePath): array
    {
        if (isset(self::$fixtureCache[$fixturePath])) {
            return self::$fixtureCache[$fixturePath];
        }
        
        $fullPath = __DIR__ . '/../../Fixtures/' . $fixturePath;
        
        if (!file_exists($fullPath)) {
            throw new \InvalidArgumentException("Fixture file not found: {$fullPath}");
        }
        
        $content = file_get_contents($fullPath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read fixture file: {$fullPath}");
        }
        
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON in fixture file: {$fullPath}");
        }
        
        self::$fixtureCache[$fixturePath] = $data;
        return $data;
    }
    
    /**
     * Get the next counter value for sequential data generation.
     * 
     * @return int Next counter value
     */
    private static function nextCounter(): int
    {
        return ++self::$counter;
    }
    
    /**
     * Generate a deterministic pseudo-random number.
     * 
     * @param int $min Minimum value
     * @param int $max Maximum value
     * @return int Deterministic pseudo-random number
     */
    private static function deterministicRandom(int $min = 0, int $max = 100): int
    {
        self::$seed = (self::$seed * 1103515245 + 12345) & 0x7fffffff;
        return $min + (self::$seed % ($max - $min + 1));
    }
    
    /**
     * Create a successful HTTP response.
     * 
     * @param array<string, mixed> $options Response options
     * @return Response Successful HTTP response
     */
    public static function createSuccessResponse(array $options = []): Response
    {
        $counter = self::nextCounter();
        $responses = self::loadFixture('responses/http-responses.json');
        
        $responseType = $options['type'] ?? 'success_responses';
        $specificResponse = $options['response'] ?? null;
        
        if ($specificResponse && isset($responses['response_categories'][$responseType][$specificResponse])) {
            $responseData = $responses['response_categories'][$responseType][$specificResponse];
        } else {
            $successResponses = $responses['response_categories']['success_responses'];
            $responseKeys = array_keys($successResponses);
            $selectedKey = $responseKeys[$counter % count($responseKeys)];
            $responseData = $successResponses[$selectedKey];
        }
        
        return new Response(
            $responseData['status_code'],
            $responseData['headers'],
            $responseData['body']
        );
    }
    
    /**
     * Create a client error HTTP response (4xx).
     * 
     * @param array<string, mixed> $options Response options
     * @return Response Client error HTTP response
     */
    public static function createClientErrorResponse(array $options = []): Response
    {
        $counter = self::nextCounter();
        $responses = self::loadFixture('responses/http-responses.json');
        
        $specificResponse = $options['response'] ?? null;
        $clientErrors = $responses['response_categories']['client_error_responses'];
        
        if ($specificResponse && isset($clientErrors[$specificResponse])) {
            $responseData = $clientErrors[$specificResponse];
        } else {
            $responseKeys = array_keys($clientErrors);
            $selectedKey = $responseKeys[$counter % count($responseKeys)];
            $responseData = $clientErrors[$selectedKey];
        }
        
        return new Response(
            $responseData['status_code'],
            $responseData['headers'],
            $responseData['body']
        );
    }
    
    /**
     * Create a server error HTTP response (5xx).
     * 
     * @param array<string, mixed> $options Response options
     * @return Response Server error HTTP response
     */
    public static function createServerErrorResponse(array $options = []): Response
    {
        $counter = self::nextCounter();
        $responses = self::loadFixture('responses/http-responses.json');
        
        $specificResponse = $options['response'] ?? null;
        $serverErrors = $responses['response_categories']['server_error_responses'];
        
        if ($specificResponse && isset($serverErrors[$specificResponse])) {
            $responseData = $serverErrors[$specificResponse];
        } else {
            $responseKeys = array_keys($serverErrors);
            $selectedKey = $responseKeys[$counter % count($responseKeys)];
            $responseData = $serverErrors[$selectedKey];
        }
        
        return new Response(
            $responseData['status_code'],
            $responseData['headers'],
            $responseData['body']
        );
    }
    
    /**
     * Create a redirect HTTP response (3xx).
     * 
     * @param array<string, mixed> $options Response options
     * @return Response Redirect HTTP response
     */
    public static function createRedirectResponse(array $options = []): Response
    {
        $counter = self::nextCounter();
        $responses = self::loadFixture('responses/http-responses.json');
        
        $specificResponse = $options['response'] ?? null;
        $redirects = $responses['response_categories']['redirect_responses'];
        
        if ($specificResponse && isset($redirects[$specificResponse])) {
            $responseData = $redirects[$specificResponse];
        } else {
            $responseKeys = array_keys($redirects);
            $selectedKey = $responseKeys[$counter % count($responseKeys)];
            $responseData = $redirects[$selectedKey];
        }
        
        return new Response(
            $responseData['status_code'],
            $responseData['headers'],
            $responseData['body']
        );
    }
    
    /**
     * Create a special HTTP response for edge case testing.
     * 
     * @param array<string, mixed> $options Response options
     * @return Response Special HTTP response
     */
    public static function createSpecialResponse(array $options = []): Response
    {
        $counter = self::nextCounter();
        $responses = self::loadFixture('responses/http-responses.json');
        
        $specificResponse = $options['response'] ?? null;
        $specialResponses = $responses['response_categories']['special_responses'];
        
        if ($specificResponse && isset($specialResponses[$specificResponse])) {
            $responseData = $specialResponses[$specificResponse];
        } else {
            $responseKeys = array_keys($specialResponses);
            $selectedKey = $responseKeys[$counter % count($responseKeys)];
            $responseData = $specialResponses[$selectedKey];
        }
        
        return new Response(
            $responseData['status_code'],
            $responseData['headers'],
            $responseData['body']
        );
    }
    
    /**
     * Create an HTTP exception for error testing.
     * 
     * @param array<string, mixed> $options Exception options
     * @return \Exception HTTP exception
     */
    public static function createHttpException(array $options = []): \Exception
    {
        $counter = self::nextCounter();
        $errors = self::loadFixture('responses/error-scenarios.json');
        
        $errorType = $options['type'] ?? 'network_errors';
        $specificError = $options['error'] ?? null;
        
        if ($specificError && isset($errors['error_categories'][$errorType][$specificError])) {
            $errorData = $errors['error_categories'][$errorType][$specificError];
        } else {
            $categoryErrors = $errors['error_categories'][$errorType];
            $errorKeys = array_keys($categoryErrors);
            $selectedKey = $errorKeys[$counter % count($errorKeys)];
            $errorData = $categoryErrors[$selectedKey];
        }
        
        $request = new Request('GET', $options['url'] ?? 'https://example.com');
        
        return match ($errorData['error_type']) {
            'ConnectException' => new ConnectException(
                $errorData['message'],
                $request
            ),
            'ClientException' => new ClientException(
                $errorData['message'],
                $request,
                isset($errorData['http_status_code']) ? 
                    new Response($errorData['http_status_code']) : null
            ),
            'ServerException' => new ServerException(
                $errorData['message'],
                $request,
                isset($errorData['http_status_code']) ? 
                    new Response($errorData['http_status_code']) : null
            ),
            default => new RequestException(
                $errorData['message'],
                $request
            ),
        };
    }
    
    /**
     * Create a collection of HTTP responses for batch testing.
     * 
     * @param int $count Number of responses to generate
     * @param array<string, mixed> $options Generation options
     * @return array<Response> Collection of HTTP responses
     */
    public static function createResponseCollection(int $count = 10, array $options = []): array
    {
        $responses = [];
        $distribution = $options['status_distribution'] ?? [
            'success' => 0.7,
            'client_error' => 0.15,
            'server_error' => 0.1,
            'redirect' => 0.05,
        ];
        
        for ($i = 0; $i < $count; $i++) {
            $responseType = self::selectResponseType($distribution, $i);
            
            $response = match ($responseType) {
                'success' => self::createSuccessResponse($options),
                'client_error' => self::createClientErrorResponse($options),
                'server_error' => self::createServerErrorResponse($options),
                'redirect' => self::createRedirectResponse($options),
                default => self::createSuccessResponse($options),
            };
            
            $responses[] = $response;
        }
        
        return $responses;
    }
    
    /**
     * Create responses for performance testing.
     * 
     * @param array<string, mixed> $options Performance test options
     * @return array<string, Response> Performance test responses
     */
    public static function createPerformanceResponses(array $options = []): array
    {
        return [
            'fast' => new Response(200, ['X-Response-Time' => '50ms'], 'Fast response'),
            'medium' => new Response(200, ['X-Response-Time' => '500ms'], 'Medium response'),
            'slow' => new Response(200, ['X-Response-Time' => '2000ms'], 'Slow response'),
            'large' => new Response(200, [
                'Content-Length' => '1048576',
                'X-Response-Time' => '1000ms'
            ], str_repeat('Large response content. ', 10000)),
            'small' => new Response(200, [
                'Content-Length' => '13',
                'X-Response-Time' => '25ms'
            ], 'Small content'),
        ];
    }
    
    /**
     * Create responses for redirect chain testing.
     * 
     * @param array<string, mixed> $options Redirect options
     * @return array<Response> Redirect chain responses
     */
    public static function createRedirectChain(array $options = []): array
    {
        $chainLength = $options['length'] ?? 3;
        $baseUrl = $options['base_url'] ?? 'https://example.com';
        $responses = [];
        
        for ($i = 0; $i < $chainLength - 1; $i++) {
            $nextUrl = "{$baseUrl}/redirect-" . ($i + 1);
            $responses[] = new Response(301, [
                'Location' => $nextUrl,
                'Cache-Control' => 'no-cache',
            ], "Redirecting to {$nextUrl}");
        }
        
        // Final response
        $responses[] = new Response(200, [
            'Content-Type' => 'text/html',
        ], '<html><body>Final destination</body></html>');
        
        return $responses;
    }
    
    /**
     * Create responses for content type testing.
     * 
     * @param array<string, mixed> $options Content type options
     * @return array<string, Response> Content type responses
     */
    public static function createContentTypeResponses(array $options = []): array
    {
        return [
            'html' => new Response(200, [
                'Content-Type' => 'text/html; charset=utf-8'
            ], '<html><body>HTML content</body></html>'),
            'json' => new Response(200, [
                'Content-Type' => 'application/json'
            ], json_encode(['message' => 'JSON content'])),
            'xml' => new Response(200, [
                'Content-Type' => 'application/xml'
            ], '<?xml version="1.0"?><root><message>XML content</message></root>'),
            'text' => new Response(200, [
                'Content-Type' => 'text/plain'
            ], 'Plain text content'),
            'binary' => new Response(200, [
                'Content-Type' => 'application/octet-stream'
            ], 'Binary content data'),
        ];
    }
    
    /**
     * Select response type based on deterministic distribution.
     * 
     * @param array<string, float> $distribution Response type distribution
     * @param int $index Index for deterministic selection
     * @return string Selected response type
     */
    private static function selectResponseType(array $distribution, int $index): string
    {
        $totalWeight = 100;
        $cumulativeWeights = [];
        $cumulative = 0;
        
        foreach ($distribution as $type => $probability) {
            $cumulative += (int)($probability * $totalWeight);
            $cumulativeWeights[] = ['type' => $type, 'weight' => $cumulative];
        }
        
        $deterministicValue = $index % $totalWeight;
        
        foreach ($cumulativeWeights as $item) {
            if ($deterministicValue < $item['weight']) {
                return $item['type'];
            }
        }
        
        return array_key_first($distribution);
    }
    
    /**
     * Get available response categories from fixtures.
     * 
     * @return array<string> Available response categories
     */
    public static function getAvailableCategories(): array
    {
        $responses = self::loadFixture('responses/http-responses.json');
        return array_keys($responses['response_categories'] ?? []);
    }
    
    /**
     * Get available error categories from fixtures.
     * 
     * @return array<string> Available error categories
     */
    public static function getAvailableErrorCategories(): array
    {
        $errors = self::loadFixture('responses/error-scenarios.json');
        return array_keys($errors['error_categories'] ?? []);
    }
    
    /**
     * Create a response by category and type.
     * 
     * @param string $category Response category
     * @param string $type Response type
     * @param array<string, mixed> $options Additional options
     * @return Response HTTP response
     */
    public static function createByType(string $category, string $type, array $options = []): Response
    {
        $responses = self::loadFixture('responses/http-responses.json');
        
        if (!isset($responses['response_categories'][$category][$type])) {
            throw new \InvalidArgumentException("Unknown response type: {$category}.{$type}");
        }
        
        $responseData = $responses['response_categories'][$category][$type];
        
        return new Response(
            $responseData['status_code'],
            $responseData['headers'],
            $responseData['body']
        );
    }
}