<?php

declare(strict_types=1);

namespace LifeAstroApi\Http;

use LifeAstroApi\Exception\ConnectionException;
use LifeAstroApi\Exception\LifeAstroException;
use LifeAstroApi\RequestOptions;
use LifeAstroApi\ResponseInfo;

/**
 * The request pipeline behind every method: URL and query building, bearer auth, timeout, JSON
 * decoding, `{ data, meta }` envelope unwrapping, error mapping, bounded retries with
 * exponential backoff that honour `Retry-After`, and the `onResponse` hook. A port of
 * `src/http.ts` of the Node.js SDK.
 *
 * @internal Use {@see \LifeAstroApi\LifeAstro}.
 */
final class HttpClient
{
    /** Statuses retried automatically (with backoff, up to `maxRetries` times). */
    public const RETRYABLE_STATUS = [408, 425, 429, 500, 502, 503, 504];

    /**
     * @param array<string, string> $defaultHeaders Extra headers on every request; they win over
     *     the SDK's own headers (case-insensitive).
     * @param (\Closure(ResponseInfo): mixed)|null $onResponse Called with the {@see ResponseInfo} of
     *     every final response, before a per-call hook.
     */
    public function __construct(
        #[\SensitiveParameter] private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly float $timeout,
        private readonly int $maxRetries,
        private readonly Transport $transport,
        private readonly string $userAgent,
        private readonly array $defaultHeaders = [],
        private readonly ?\Closure $onResponse = null,
    ) {
    }

    /**
     * GET `$path` and return the decoded body: the `data` member when the body is a
     * `{ data, meta }` envelope, the whole body otherwise (or with `raw`). A body that is not JSON
     * comes back as the string.
     *
     * @param array<string, mixed> $query Wire parameter names => values; null and '' are skipped.
     *
     * @throws LifeAstroException
     */
    public function request(string $path, array $query = [], ?RequestOptions $options = null): mixed
    {
        return $this->send($path, $query, $options)[1];
    }

    /**
     * Like {@see request()}, for the typed methods: the decoded JSON object or array.
     *
     * @param array<string, mixed> $query
     *
     * @return array<mixed>
     *
     * @throws LifeAstroException Also when the body is not a JSON object or array
     *     (code `invalid_response`).
     */
    public function requestArray(string $path, array $query = [], ?RequestOptions $options = null): array
    {
        [$status, $result] = $this->send($path, $query, $options);
        if ($result === null) {
            return [];
        }
        if (!\is_array($result)) {
            throw new LifeAstroException(
                'Expected a JSON object in the response body, got ' . get_debug_type($result),
                'invalid_response',
                $status,
                null,
                $result,
            );
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array{0: int, 1: mixed} The HTTP status and the decoded (unwrapped) body.
     */
    private function send(string $path, array $query, ?RequestOptions $options): array
    {
        $path = str_starts_with($path, '/') ? $path : '/' . $path;
        $url = $this->baseUrl . $path . Query::encode($query);
        $timeout = $options->timeout ?? $this->timeout;
        $headers = $this->headers();

        for ($attempt = 0;; $attempt++) {
            try {
                $response = $this->transport->get($url, $headers, $timeout);
            } catch (ConnectionException $e) {
                if ($attempt < $this->maxRetries) {
                    self::sleep(self::backoffSeconds($attempt));
                    continue;
                }

                throw $e;
            }

            $body = self::decode($response->body);
            if ($response->status >= 200 && $response->status < 300) {
                $this->emit($response, $path, $options);
                if ($options !== null && $options->raw) {
                    return [$response->status, $body];
                }
                // The API returns bare JSON today. A { data, meta } envelope is unwrapped if a
                // response ever carries one, so adding it on the server is not a breaking change.
                if (\is_array($body) && \array_key_exists('data', $body)) {
                    return [$response->status, $body['data']];
                }

                return [$response->status, $body];
            }

            $retryAfter = self::parseRetryAfter($response->header('retry-after'));
            $error = LifeAstroException::fromResponse($response->status, $body, $retryAfter);
            if (\in_array($response->status, self::RETRYABLE_STATUS, true) && $attempt < $this->maxRetries) {
                self::sleep(self::backoffSeconds($attempt, $retryAfter));
                continue;
            }

            $this->emit($response, $path, $options);
            throw $error;
        }
    }

    /**
     * Call the client-level hook, then the per-call one, with the final response of a call. A hook
     * that throws never breaks the call.
     */
    private function emit(TransportResponse $response, string $path, ?RequestOptions $options): void
    {
        $hooks = array_filter([$this->onResponse, $options?->onResponse]);
        if ($hooks === []) {
            return;
        }
        $info = ResponseInfo::fromResponse($response, $path);
        foreach ($hooks as $hook) {
            try {
                $hook($info);
            } catch (\Throwable) {
                // A logging hook must never break the call.
            }
        }
    }

    /**
     * The wait before retry number `$attempt` (0-based): `Retry-After` capped at 30 s, otherwise
     * 0.5 s, 1 s, 2 s, ... up to 8 s, plus up to 0.25 s of jitter.
     */
    public static function backoffSeconds(int $attempt, ?float $retryAfter = null): float
    {
        if ($retryAfter !== null && is_finite($retryAfter)) {
            return min(max($retryAfter, 0.0), 30.0);
        }
        $base = min(0.5 * (2 ** min($attempt, 16)), 8.0);

        return $base + mt_rand(0, 249) / 1000;
    }

    /**
     * Seconds from a `Retry-After` header: delta-seconds (`120`) or an HTTP date
     * (`Wed, 21 Oct 2026 07:28:00 GMT`). Null when absent or unreadable.
     */
    public static function parseRetryAfter(?string $value, ?float $now = null): ?float
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        $value = trim($value);
        if (is_numeric($value)) {
            return max(0.0, (float) $value);
        }
        $at = strtotime($value);
        if ($at === false) {
            return null;
        }

        return max(0.0, $at - ($now ?? microtime(true)));
    }

    /**
     * Never print the key: `var_dump()` / `print_r()` of a client show it masked.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'apiKey' => self::mask($this->apiKey),
            'baseUrl' => $this->baseUrl,
            'timeout' => $this->timeout,
            'maxRetries' => $this->maxRetries,
            'transport' => $this->transport::class,
            'userAgent' => $this->userAgent,
        ];
    }

    /** `dv_live_abcd...` -> `dv_live_****`. */
    public static function mask(string $apiKey): string
    {
        $cut = strrpos($apiKey, '_');

        return ($cut === false ? '' : substr($apiKey, 0, $cut + 1)) . '****';
    }

    /** @return array<string, string> */
    private function headers(): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept' => 'application/json',
            'User-Agent' => $this->userAgent,
        ];
        foreach ($this->defaultHeaders as $name => $value) {
            foreach (array_keys($headers) as $existing) {
                if (strcasecmp($existing, $name) === 0) {
                    unset($headers[$existing]);
                }
            }
            $headers[$name] = $value;
        }

        return $headers;
    }

    private static function decode(string $text): mixed
    {
        if ($text === '') {
            return null;
        }
        try {
            return json_decode($text, true, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        } catch (\JsonException) {
            return $text;
        }
    }

    private static function sleep(float $seconds): void
    {
        if ($seconds > 0) {
            usleep((int) round($seconds * 1_000_000));
        }
    }
}
