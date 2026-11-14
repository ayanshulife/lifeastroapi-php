<?php

declare(strict_types=1);

namespace LifeAstroApi\Http;

use LifeAstroApi\Exception\ConnectionException;
use LifeAstroApi\Exception\TimeoutException;

/**
 * Sends one HTTP GET and returns the response, whatever its status. Retries, error mapping and
 * JSON decoding happen in the client, not here.
 *
 * The SDK ships {@see CurlTransport} (the default) and {@see Psr18Transport} (wraps any PSR-18
 * client). Implement this interface to plug in anything else, for example a recorder in tests.
 */
interface Transport
{
    /**
     * @param string $url Full URL with the query string.
     * @param array<string, string> $headers Request headers, name => value.
     * @param float $timeout Seconds for the whole request; 0 means no limit.
     *
     * @throws TimeoutException When the request timed out.
     * @throws ConnectionException When no response arrived.
     */
    public function get(string $url, array $headers, float $timeout): TransportResponse;
}
