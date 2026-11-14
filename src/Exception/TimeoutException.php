<?php

declare(strict_types=1);

namespace LifeAstroApi\Exception;

/**
 * The request took longer than the timeout (error code `timeout`, `getStatus()` 0).
 * A {@see ConnectionException}, so catching that one catches this too.
 */
class TimeoutException extends ConnectionException
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 'timeout', $previous);
    }
}
