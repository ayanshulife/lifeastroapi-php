<?php

declare(strict_types=1);

namespace LifeAstroApi\Exception;

/**
 * Base class of every error the SDK throws.
 *
 * A non-2xx response becomes a subclass for its status (400 {@see BadRequestException},
 * 401 {@see AuthenticationException}, 402 {@see PaymentRequiredException},
 * 403 {@see PermissionException}, 404 {@see NotFoundException}, 429 {@see RateLimitException},
 * 5xx {@see ServerException}). A network failure is a {@see ConnectionException}, and a timeout a
 * {@see TimeoutException}. Each carries the API's error code, the HTTP status and the request id.
 *
 * `getCode()` returns the HTTP status as well (0 when no response arrived).
 */
class LifeAstroException extends \RuntimeException
{
    /**
     * @param string $message Human-readable message from the API (or the SDK).
     * @param string $errorCode Machine-readable code, e.g. `insufficient_credits`.
     * @param int $status HTTP status, 0 for network failures and timeouts.
     * @param string|null $requestId The API's request id; quote it to support.
     * @param mixed $body The decoded response body, when there was one.
     */
    public function __construct(
        string $message,
        private readonly string $errorCode,
        private readonly int $status = 0,
        private readonly ?string $requestId = null,
        private readonly mixed $body = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }

    /** HTTP status of the response (0 for network failures and timeouts). */
    public function getStatus(): int
    {
        return $this->status;
    }

    /** Machine-readable error code from the API, e.g. `insufficient_credits` or `invalid_param`. */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /** The API's request id, when the response had one. Quote it to support. */
    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    /** The decoded response body (array, string or null). */
    public function getBody(): mixed
    {
        return $this->body;
    }

    /**
     * Build the exception for an HTTP error response.
     *
     * @param mixed $body The decoded body; the API sends `{"error": {"code", "message", "request_id"}}`.
     * @param float|null $retryAfter Seconds from the `Retry-After` header, if any.
     *
     * @internal
     */
    public static function fromResponse(int $status, mixed $body, ?float $retryAfter = null): self
    {
        $error = is_array($body) && isset($body['error']) && is_array($body['error']) ? $body['error'] : [];
        $code = isset($error['code']) && is_string($error['code']) ? $error['code'] : self::defaultCode($status);
        $message = isset($error['message']) && is_string($error['message']) ? $error['message'] : "HTTP {$status}";
        $requestId = isset($error['request_id']) && is_string($error['request_id']) ? $error['request_id'] : null;

        return match (true) {
            $status === 400 => new BadRequestException($message, $code, $status, $requestId, $body),
            $status === 401 => new AuthenticationException($message, $code, $status, $requestId, $body),
            $status === 402 => new PaymentRequiredException($message, $code, $status, $requestId, $body),
            $status === 403 => new PermissionException($message, $code, $status, $requestId, $body),
            $status === 404 => new NotFoundException($message, $code, $status, $requestId, $body),
            $status === 429 => new RateLimitException($message, $code, $status, $requestId, $body, $retryAfter),
            $status >= 500 => new ServerException($message, $code, $status, $requestId, $body),
            default => new self($message, $code, $status, $requestId, $body),
        };
    }

    private static function defaultCode(int $status): string
    {
        return match (true) {
            $status === 0 => 'connection_error',
            $status >= 500 => 'internal_error',
            $status >= 400 => 'bad_request',
            default => 'error',
        };
    }
}
