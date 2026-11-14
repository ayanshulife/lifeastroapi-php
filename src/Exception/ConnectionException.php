<?php

declare(strict_types=1);

namespace LifeAstroApi\Exception;

/**
 * No HTTP response arrived: DNS failure, refused connection, TLS error and the like
 * (`getStatus()` is 0). Retried automatically before it is thrown.
 */
class ConnectionException extends LifeAstroException
{
    public function __construct(
        string $message,
        string $errorCode = 'connection_error',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $errorCode, 0, null, null, $previous);
    }
}
