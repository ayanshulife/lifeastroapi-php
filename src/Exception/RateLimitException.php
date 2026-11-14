<?php

declare(strict_types=1);

namespace LifeAstroApi\Exception;

/** 429: the per-minute rate limit or the daily quota was exceeded. */
class RateLimitException extends LifeAstroException
{
    /**
     * @param float|null $retryAfter Seconds to wait, from the `Retry-After` header.
     */
    public function __construct(
        string $message,
        string $errorCode,
        int $status = 429,
        ?string $requestId = null,
        mixed $body = null,
        private readonly ?float $retryAfter = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $errorCode, $status, $requestId, $body, $previous);
    }

    /** Seconds to wait before the next call, from the `Retry-After` header (null when absent). */
    public function getRetryAfter(): ?float
    {
        return $this->retryAfter;
    }
}
