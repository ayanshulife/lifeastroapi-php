<?php

declare(strict_types=1);

namespace LifeAstroApi\Tests;

use LifeAstroApi\Exception\BadRequestException;
use LifeAstroApi\Exception\ConnectionException;
use LifeAstroApi\Exception\RateLimitException;
use LifeAstroApi\Exception\ServerException;
use LifeAstroApi\Exception\TimeoutException;
use LifeAstroApi\Http\HttpClient;
use LifeAstroApi\LifeAstro;
use LifeAstroApi\Tests\Support\MockTransport;

/**
 * Retries (Node: "retries", "error status mapping"). Retry-After: 0 keeps status retries instant;
 * network-error retries wait the real first backoff (0.5-0.75 s).
 */
final class RetryTest extends TestCase
{
    private static function reply(int $status): \LifeAstroApi\Http\TransportResponse
    {
        return MockTransport::json(['error' => ['code' => 'x', 'message' => 'x']], $status, ['Retry-After' => '0']);
    }

    public function testRetriesA429ThenSucceeds(): void
    {
        $t = (new MockTransport())->push(self::reply(429), MockTransport::json(['ok' => true]));
        self::assertSame(['ok' => true], self::client($t, maxRetries: 2)->meta->version());
        self::assertSame(2, $t->count());
    }

    public function testRetriesEveryRetryableStatus(): void
    {
        foreach ([408, 425, 429, 500, 502, 503, 504] as $status) {
            $t = (new MockTransport())->push(self::reply($status), MockTransport::json(['ok' => $status]));
            self::assertSame(['ok' => $status], self::client($t, maxRetries: 1)->meta->version(), (string) $status);
            self::assertSame(2, $t->count(), (string) $status);
        }
    }

    public function testGivesUpAfterMaxRetries(): void
    {
        $t = (new MockTransport())->push(self::reply(503), self::reply(503), self::reply(503), self::reply(503));
        try {
            self::client($t, maxRetries: 2)->meta->version();
            self::fail('Expected an exception');
        } catch (ServerException $e) {
            self::assertSame(503, $e->getStatus());
        }
        self::assertSame(3, $t->count());
    }

    public function testANegativeMaxRetriesStillMakesTheRequestOnce(): void
    {
        $t = (new MockTransport())->push(self::reply(429));
        $client = new LifeAstro(apiKey: 'dv_live_test', maxRetries: -3, transport: $t);
        $this->expectException(RateLimitException::class);
        try {
            $client->meta->version();
        } finally {
            self::assertSame(1, $t->count());
        }
    }

    public function testDoesNotRetryANonRetryable4xxOr501(): void
    {
        $t = (new MockTransport())->push(self::reply(400));
        try {
            self::client($t, maxRetries: 2)->meta->version();
            self::fail('Expected an exception');
        } catch (BadRequestException) {
            self::assertSame(1, $t->count());
        }

        $t = (new MockTransport())->push(self::reply(501));
        try {
            self::client($t, maxRetries: 2)->meta->version();
            self::fail('Expected an exception');
        } catch (ServerException) {
            self::assertSame(1, $t->count());
        }
    }

    public function testRetriesANetworkErrorThenSucceeds(): void
    {
        $t = (new MockTransport())->push(new ConnectionException('refused'), MockTransport::json(['ok' => 1]));
        $started = microtime(true);
        self::assertSame(['ok' => 1], self::client($t, maxRetries: 1)->meta->version());
        self::assertSame(2, $t->count());
        self::assertGreaterThanOrEqual(0.45, microtime(true) - $started, 'waits the first backoff');
    }

    public function testRetriesATimeoutAndThrowsItWhenRetriesRunOut(): void
    {
        $t = (new MockTransport())->push(new TimeoutException('slow'), new TimeoutException('slow'));
        $this->expectException(TimeoutException::class);
        try {
            self::client($t, maxRetries: 1)->meta->version();
        } finally {
            self::assertSame(2, $t->count());
        }
    }

    public function testBackoffDoublesFromHalfASecondUpToEight(): void
    {
        foreach ([0 => 0.5, 1 => 1.0, 2 => 2.0, 3 => 4.0, 4 => 8.0, 5 => 8.0, 40 => 8.0] as $attempt => $base) {
            $wait = HttpClient::backoffSeconds($attempt);
            self::assertGreaterThanOrEqual($base, $wait, "attempt {$attempt}");
            self::assertLessThan($base + 0.25, $wait, "attempt {$attempt}");
        }
    }

    public function testBackoffHonoursRetryAfterUpTo30Seconds(): void
    {
        self::assertSame(3.0, HttpClient::backoffSeconds(0, 3.0));
        self::assertSame(0.0, HttpClient::backoffSeconds(2, 0.0));
        self::assertSame(30.0, HttpClient::backoffSeconds(0, 120.0));
    }

    public function testParsesRetryAfter(): void
    {
        self::assertSame(5.0, HttpClient::parseRetryAfter('5'));
        self::assertSame(1.5, HttpClient::parseRetryAfter(' 1.5 '));
        self::assertSame(0.0, HttpClient::parseRetryAfter('-4'));
        self::assertNull(HttpClient::parseRetryAfter(null));
        self::assertNull(HttpClient::parseRetryAfter(''));
        self::assertNull(HttpClient::parseRetryAfter('soon'));
        $now = (float) strtotime('2026-10-21 07:28:00 UTC');
        self::assertSame(60.0, HttpClient::parseRetryAfter('Wed, 21 Oct 2026 07:29:00 GMT', $now));
        self::assertSame(0.0, HttpClient::parseRetryAfter('Wed, 21 Oct 2026 07:00:00 GMT', $now));
    }
}
