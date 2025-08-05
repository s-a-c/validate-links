<?php

// app/Services/ConcurrentValidationService.php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Services\ValueObjects\ValidationResult;
use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

final readonly class ConcurrentValidationService
{
    private Client $httpClient;

    public function __construct(private int $concurrency = 10)
    {
        $this->httpClient = new Client([
            'timeout' => config('validate-links.defaults.timeout'),
            'headers' => [
                'User-Agent' => config('validate-links.scopes.external.user_agent'),
            ],
        ]);
    }

    /**
     * @param  array<array{url: string, text: string, line: int}>  $links
     * @return array<ValidationResult>
     */
    public function validateExternalLinksConcurrently(array $links): array
    {
        $requests = $this->createRequests($links);
        $results = [];

        $pool = new Pool($this->httpClient, $requests, [
            'concurrency' => $this->concurrency,
            'fulfilled' => function (ResponseInterface $response, $index) use (&$results, $links): void {
                if (isset($links[$index])) {
                    $results[$index] = $this->processSuccessfulResponse($response, $links[$index]);
                }
            },
            'rejected' => function ($reason, $index) use (&$results, $links): void {
                if (isset($links[$index])) {
                    $results[$index] = $this->processFailedResponse($reason, $links[$index]);
                }
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();

        return $results;
    }

    /**
     * @param  array<array{url: string}>  $links
     */
    private function createRequests(array $links): Generator
    {
        foreach ($links as $index => $link) {
            yield $index => new Request('HEAD', $link['url']);
        }
    }

    /**
     * Process successful HTTP response.
     *
     * @param  array{url: string}  $link
     */
    private function processSuccessfulResponse(ResponseInterface $response, array $link): ValidationResult
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode >= 200 && $statusCode < 300) {
            return ValidationResult::success($link['url'], ValidationScope::EXTERNAL);
        }

        return ValidationResult::failure(
            $link['url'],
            ValidationScope::EXTERNAL,
            LinkStatus::BROKEN,
            "HTTP {$statusCode}"
        );
    }

    /**
     * Process failed HTTP response.
     *
     * @param  array{url: string}  $link
     */
    private function processFailedResponse(mixed $reason, array $link): ValidationResult
    {
        $message = is_object($reason) && method_exists($reason, 'getMessage')
            ? $reason->getMessage()
            : 'Request failed';

        $errorMessage = (is_string($message) && mb_trim($message) !== '') ? $message : 'Request failed';

        return ValidationResult::failure(
            $link['url'],
            ValidationScope::EXTERNAL,
            LinkStatus::BROKEN,
            $errorMessage
        );
    }
}
