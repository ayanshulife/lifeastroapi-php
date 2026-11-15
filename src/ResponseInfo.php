<?php

declare(strict_types=1);

namespace LifeAstroApi;

use LifeAstroApi\Http\TransportResponse;

/**
 * What the API reported about one call besides its body: the request id, your credit balance after
 * the call, your plan's per-minute rate limit and whether the answer came from the API's cache.
 *
 * Passed to `onResponse` (client argument or {@see RequestOptions}) for every final HTTP response,
 * success or error; not for network failures or for attempts that were retried. A property is null
 * when the API did not send its header.
 */
final class ResponseInfo
{
    /**
     * @param int $status HTTP status of the response.
     * @param string $path Request path, e.g. `/v1/panchang/basic`.
     * @param string|null $requestId `X-Request-ID`. Quote it to support.
     * @param int|null $creditsRemaining `X-Credits-Remaining`: the balance after this call (unchanged
     *     after a failed call). Null on free-tier accounts, whose allowance is not a balance, and on
     *     calls that cost nothing.
     * @param RateLimit|null $rateLimit `X-RateLimit-*`: set only when all three headers are present
     *     and numeric.
     * @param string|null $cache `X-Cache`, e.g. `HIT` when the API answered from its cache (billed
     *     the same).
     * @param string|null $language `Content-Language`: the language the answer is in.
     */
    public function __construct(
        public readonly int $status,
        public readonly string $path,
        public readonly ?string $requestId = null,
        public readonly ?int $creditsRemaining = null,
        public readonly ?RateLimit $rateLimit = null,
        public readonly ?string $cache = null,
        public readonly ?string $language = null,
    ) {
    }

    /**
     * Read the headers of a response. Header names are case-insensitive; a header that is missing
     * or not a number leaves its property null.
     *
     * @internal Used by the request pipeline.
     */
    public static function fromResponse(TransportResponse $response, string $path): self
    {
        $limit = self::int($response->header('x-ratelimit-limit'));
        $remaining = self::int($response->header('x-ratelimit-remaining'));
        $reset = self::int($response->header('x-ratelimit-reset'));
        $rateLimit = null;
        if ($limit !== null && $remaining !== null && $reset !== null) {
            $resetAt = \DateTimeImmutable::createFromFormat('U', (string) $reset, new \DateTimeZone('UTC'));
            if ($resetAt !== false) {
                $rateLimit = new RateLimit($limit, $remaining, $resetAt->setTimezone(new \DateTimeZone('UTC')));
            }
        }

        return new self(
            status: $response->status,
            path: $path,
            requestId: self::text($response->header('x-request-id')),
            creditsRemaining: self::int($response->header('x-credits-remaining')),
            rateLimit: $rateLimit,
            cache: self::text($response->header('x-cache')),
            language: self::text($response->header('content-language')),
        );
    }

    private static function int(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }
        $number = filter_var(trim($value), FILTER_VALIDATE_INT);

        return $number === false ? null : $number;
    }

    private static function text(?string $value): ?string
    {
        return $value === null || $value === '' ? null : $value;
    }
}
