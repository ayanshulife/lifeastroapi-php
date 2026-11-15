<?php

declare(strict_types=1);

namespace LifeAstroApi;

/** Your plan's per-minute rate limit, from the `X-RateLimit-*` headers of a response. */
final class RateLimit
{
    /**
     * @param int $limit `X-RateLimit-Limit`: requests per minute for your plan.
     * @param int $remaining `X-RateLimit-Remaining`: requests left in the current window.
     * @param \DateTimeImmutable $resetAt `X-RateLimit-Reset`: when the window resets (UTC).
     */
    public function __construct(
        public readonly int $limit,
        public readonly int $remaining,
        public readonly \DateTimeImmutable $resetAt,
    ) {
    }
}
