<?php

declare(strict_types=1);

namespace LifeAstroApi\Http;

use LifeAstroApi\Exception\ConnectionException;
use LifeAstroApi\Exception\TimeoutException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * Sends requests through any PSR-18 HTTP client (Guzzle 7, Symfony HttpClient's `Psr18Client`,
 * a Laravel app's Guzzle instance, ...), building them with a PSR-17 request factory.
 *
 * You rarely create this yourself: pass the client to the {@see \LifeAstroApi\LifeAstro}
 * constructor as `transport`, and the factory as `requestFactory`.
 *
 * PSR-18 has no per-request timeout, so the client's own timeout setting applies (for Guzzle,
 * `new Client(['timeout' => 30])`); the SDK's `timeout` option does not reach it.
 */
final class Psr18Transport implements Transport
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requestFactory,
    ) {
    }

    public function get(string $url, array $headers, float $timeout): TransportResponse
    {
        $request = $this->requestFactory->createRequest('GET', $url);
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        try {
            $response = $this->client->sendRequest($request);
        } catch (NetworkExceptionInterface $e) {
            if (preg_match('/timed?[ -]?out|timeout/i', $e->getMessage()) === 1) {
                throw new TimeoutException('Request timed out: ' . $e->getMessage(), $e);
            }

            throw new ConnectionException('Network request failed: ' . $e->getMessage(), 'connection_error', $e);
        } catch (ClientExceptionInterface $e) {
            throw new ConnectionException('HTTP client error: ' . $e->getMessage(), 'connection_error', $e);
        }

        $responseHeaders = [];
        foreach ($response->getHeaders() as $name => $values) {
            $responseHeaders[strtolower((string) $name)] = implode(', ', $values);
        }

        return new TransportResponse($response->getStatusCode(), $responseHeaders, (string) $response->getBody());
    }
}
