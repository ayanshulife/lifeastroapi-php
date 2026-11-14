<?php

declare(strict_types=1);

namespace LifeAstroApi\Http;

use LifeAstroApi\Exception\ConnectionException;
use LifeAstroApi\Exception\TimeoutException;

/**
 * The default transport, on `ext-curl`. It keeps one cURL handle per client, so calls reuse the
 * open connection (keep-alive) instead of a new TLS handshake each time. Responses are
 * decompressed (gzip, deflate, br when cURL has it) and redirects are followed over HTTP(S) only.
 */
final class CurlTransport implements Transport
{
    private ?\CurlHandle $handle = null;

    /**
     * @param float $connectTimeout Seconds allowed for the TCP and TLS connection; the overall
     *     `timeout` of the call still applies. 0 means the overall timeout only.
     */
    public function __construct(private readonly float $connectTimeout = 10.0)
    {
        if (!\extension_loaded('curl')) {
            throw new \LogicException(
                'LifeAstro: the cURL extension (ext-curl) is not loaded. Enable it, or pass a PSR-18 '
                . 'client as `transport`.'
            );
        }
    }

    public function get(string $url, array $headers, float $timeout): TransportResponse
    {
        if ($url === '') {
            throw new \InvalidArgumentException('LifeAstro: empty request URL');
        }
        if ($this->handle === null) {
            $handle = curl_init();
            if ($handle === false) {
                throw new ConnectionException('Network request failed: could not initialise cURL');
            }
            $this->handle = $handle;
        } else {
            $handle = $this->handle;
            curl_reset($handle);
        }

        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = $name . ': ' . $value;
        }

        /** @var array<string, string> $responseHeaders */
        $responseHeaders = [];
        $timeoutMs = $timeout > 0 ? (int) ceil($timeout * 1000) : 0;
        $connectMs = $this->connectTimeout > 0 ? (int) ceil($this->connectTimeout * 1000) : 0;
        if ($timeoutMs > 0 && ($connectMs === 0 || $connectMs > $timeoutMs)) {
            $connectMs = $timeoutMs;
        }

        curl_setopt_array($handle, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => $lines,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT_MS => $timeoutMs,
            CURLOPT_CONNECTTIMEOUT_MS => $connectMs,
            CURLOPT_NOSIGNAL => true,
            CURLOPT_ENCODING => '',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_HEADERFUNCTION => static function (\CurlHandle $ch, string $line) use (&$responseHeaders): int {
                $length = \strlen($line);
                if (str_starts_with($line, 'HTTP/')) {
                    // A new response (after a redirect or a 100 Continue): drop the earlier headers.
                    $responseHeaders = [];

                    return $length;
                }
                $colon = strpos($line, ':');
                if ($colon !== false) {
                    $name = strtolower(trim(substr($line, 0, $colon)));
                    $value = trim(substr($line, $colon + 1));
                    $responseHeaders[$name] = isset($responseHeaders[$name])
                        ? $responseHeaders[$name] . ', ' . $value
                        : $value;
                }

                return $length;
            },
        ]);

        $body = curl_exec($handle);
        if (!\is_string($body)) {
            $errno = curl_errno($handle);
            $error = curl_error($handle);
            if ($errno === CURLE_OPERATION_TIMEDOUT) {
                throw new TimeoutException(
                    sprintf('Request timed out after %s s (%s)', self::seconds($timeout), $error)
                );
            }

            throw new ConnectionException(sprintf('Network request failed: %s (cURL error %d)', $error, $errno));
        }

        return new TransportResponse(curl_getinfo($handle, CURLINFO_RESPONSE_CODE), $responseHeaders, $body);
    }

    private static function seconds(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }
}
