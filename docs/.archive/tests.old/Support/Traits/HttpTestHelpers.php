<?php

declare(strict_types=1);

namespace .archive\tests.old\Support\Traits;

/**
 * Test implementation extracted from tests.old/Traits/HttpTestHelpers.php
 */
trait HttpTestHelpers
{
    protected function mockSuccessfulHttpResponses(array $urls): void
    {
        $responses = [];
        foreach ($urls as $url) {
            $responses[$url] = Http::response('OK', 200, [
                'Content-Type' => 'text/html',
                'Content-Length' => '2',
            ]);
        }
        Http::fake($responses);
    }

    protected function mockBrokenHttpResponses(array $urls, int $statusCode = 404): void
    {
        $responses = [];
        foreach ($urls as $url) {
            $responses[$url] = Http::response('Not Found', $statusCode);
        }
        Http::fake($responses);
    }

    protected function mockTimeoutHttpResponses(array $urls): void
    {
        $responses = [];
        foreach ($urls as $url) {
            $responses[$url] = Http::timeout();
        }
        Http::fake($responses);
    }

    protected function mockRedirectHttpResponses(array $redirects): void
    {
        $responses = [];
        foreach ($redirects as $from => $to) {
            $responses[$from] = Http::response('', 301, ['Location' => $to]);
            $responses[$to] = Http::response('OK', 200);
        }
        Http::fake($responses);
    }

    protected function mockMixedHttpResponses(array $responses): void
    {
        $httpResponses = [];
        foreach ($responses as $url => $config) {
            $httpResponses[$url] = Http::response(
                $config['body'] ?? 'OK',
                $config['status'] ?? 200,
                $config['headers'] ?? []
            );
        }
        Http::fake($httpResponses);
    }
}
