<?php

declare(strict_types=1);

namespace LifeAstroApi\Tests;

use LifeAstroApi\Exception\AuthenticationException;
use LifeAstroApi\Exception\BadRequestException;
use LifeAstroApi\Exception\ConnectionException;
use LifeAstroApi\Exception\LifeAstroException;
use LifeAstroApi\Exception\NotFoundException;
use LifeAstroApi\Exception\PaymentRequiredException;
use LifeAstroApi\Exception\PermissionException;
use LifeAstroApi\Exception\RateLimitException;
use LifeAstroApi\Exception\ServerException;
use LifeAstroApi\Exception\TimeoutException;
use LifeAstroApi\Http\TransportResponse;
use LifeAstroApi\Tests\Support\MockTransport;
use PHPUnit\Framework\Attributes\DataProvider;

/** Error mapping (Node: "errors", "error status mapping"). */
final class ErrorsTest extends TestCase
{
    /** @param array<string, string> $headers */
    private static function apiError(int $status, string $code, string $message, array $headers = []): TransportResponse
    {
        return MockTransport::json(
            ['error' => ['code' => $code, 'message' => $message, 'request_id' => 'req_123']],
            $status,
            $headers,
        );
    }

    public function testMaps401ToAuthenticationExceptionWithCodeAndRequestId(): void
    {
        $t = (new MockTransport())->push(self::apiError(401, 'invalid_api_key', 'Invalid API key'));
        try {
            self::client($t)->meta->version();
            self::fail('Expected an exception');
        } catch (AuthenticationException $e) {
            self::assertSame(401, $e->getStatus());
            self::assertSame(401, $e->getCode());
            self::assertSame('invalid_api_key', $e->getErrorCode());
            self::assertSame('Invalid API key', $e->getMessage());
            self::assertSame('req_123', $e->getRequestId());
            self::assertIsArray($e->getBody());
        }
    }

    /** @return iterable<string, array{int, class-string<LifeAstroException>}> */
    public static function statuses(): iterable
    {
        yield '400' => [400, BadRequestException::class];
        yield '401' => [401, AuthenticationException::class];
        yield '402' => [402, PaymentRequiredException::class];
        yield '403' => [403, PermissionException::class];
        yield '404' => [404, NotFoundException::class];
        yield '409' => [409, LifeAstroException::class];
        yield '429' => [429, RateLimitException::class];
        yield '500' => [500, ServerException::class];
        yield '502' => [502, ServerException::class];
        yield '503' => [503, ServerException::class];
    }

    /** @param class-string<LifeAstroException> $class */
    #[DataProvider('statuses')]
    public function testMapsStatusToItsException(int $status, string $class): void
    {
        $t = (new MockTransport())->push(self::apiError($status, 'some_code', 'Something'));
        try {
            self::client($t)->meta->version();
            self::fail('Expected an exception');
        } catch (LifeAstroException $e) {
            self::assertSame($class, $e::class);
            self::assertSame($status, $e->getStatus());
            self::assertSame('some_code', $e->getErrorCode());
        }
    }

    public function testMaps429ToRateLimitExceptionWithRetryAfter(): void
    {
        $t = (new MockTransport())->push(self::apiError(429, 'rate_limited', 'Slow down', ['Retry-After' => '7']));
        try {
            self::client($t)->meta->version();
            self::fail('Expected an exception');
        } catch (RateLimitException $e) {
            self::assertSame(7.0, $e->getRetryAfter());
            self::assertSame('rate_limited', $e->getErrorCode());
        }
    }

    public function testParsesTheHttpDateFormOfRetryAfter(): void
    {
        $at = gmdate('D, d M Y H:i:s', time() + 20) . ' GMT';
        $t = (new MockTransport())->push(self::apiError(429, 'rate_limited', 'Slow down', ['retry-after' => $at]));
        try {
            self::client($t)->meta->version();
            self::fail('Expected an exception');
        } catch (RateLimitException $e) {
            $retryAfter = $e->getRetryAfter();
            self::assertNotNull($retryAfter);
            self::assertGreaterThan(15, $retryAfter);
            self::assertLessThanOrEqual(20, $retryAfter);
        }
    }

    public function testDefaultsWhenTheBodyHasNoErrorObject(): void
    {
        $t = (new MockTransport())->push(new TransportResponse(502, [], '<html>Bad Gateway</html>'));
        try {
            self::client($t)->meta->version();
            self::fail('Expected an exception');
        } catch (ServerException $e) {
            self::assertSame('internal_error', $e->getErrorCode());
            self::assertSame('HTTP 502', $e->getMessage());
            self::assertNull($e->getRequestId());
            self::assertSame('<html>Bad Gateway</html>', $e->getBody());
        }

        $t->push(MockTransport::json(['message' => 'nope'], 400));
        try {
            self::client($t)->meta->version();
            self::fail('Expected an exception');
        } catch (BadRequestException $e) {
            self::assertSame('bad_request', $e->getErrorCode());
        }
    }

    public function testSubclassesAreAlsoTheBaseException(): void
    {
        $t = (new MockTransport())->push(
            self::apiError(403, 'forbidden', 'No'),
            self::apiError(404, 'not_found', 'No'),
        );
        $client = self::client($t);
        foreach ([PermissionException::class, NotFoundException::class] as $class) {
            try {
                $client->meta->version();
                self::fail('Expected an exception');
            } catch (LifeAstroException $e) {
                self::assertInstanceOf($class, $e);
                self::assertInstanceOf(\RuntimeException::class, $e);
            }
        }
    }

    public function testNetworkFailuresAreConnectionExceptions(): void
    {
        $t = (new MockTransport())->push(new ConnectionException('Network request failed: refused'));
        try {
            self::client($t)->meta->version();
            self::fail('Expected an exception');
        } catch (ConnectionException $e) {
            self::assertSame(0, $e->getStatus());
            self::assertSame('connection_error', $e->getErrorCode());
        }
    }

    public function testTimeoutsAreTimeoutExceptionsAndConnectionExceptions(): void
    {
        $t = (new MockTransport())->push(new TimeoutException('Request timed out after 30 s'));
        try {
            self::client($t)->meta->version();
            self::fail('Expected an exception');
        } catch (ConnectionException $e) {
            self::assertInstanceOf(TimeoutException::class, $e);
            self::assertSame('timeout', $e->getErrorCode());
            self::assertSame(0, $e->getStatus());
        }
    }
}
