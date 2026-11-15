<?php

declare(strict_types=1);

namespace LifeAstroApi\Tests;

use LifeAstroApi\Exception\ConnectionException;
use LifeAstroApi\Exception\ServerException;
use LifeAstroApi\LifeAstro;
use LifeAstroApi\RateLimit;
use LifeAstroApi\RequestOptions;
use LifeAstroApi\ResponseInfo;
use LifeAstroApi\Tests\Support\MockTransport;

/** The `onResponse` hook (Node: "onResponse (credits, rate limit, request id)"). */
final class ResponseInfoTest extends TestCase
{
    private const HEADERS = [
        'X-Request-ID' => '52a520f261d3db37',
        'X-Credits-Remaining' => '64043',
        'X-RateLimit-Limit' => '250',
        'X-RateLimit-Remaining' => '249',
        'X-RateLimit-Reset' => '1794718800',
        'X-Cache' => 'HIT',
        'Content-Language' => 'gu',
    ];

    /** @param (callable(ResponseInfo): mixed)|null $onResponse */
    private static function hooked(MockTransport $transport, ?callable $onResponse, int $maxRetries = 0): LifeAstro
    {
        return new LifeAstro(
            apiKey: 'dv_live_test',
            baseUrl: 'https://api.test',
            maxRetries: $maxRetries,
            transport: $transport,
            onResponse: $onResponse,
        );
    }

    public function testReportsEveryHeaderOnASuccessClientLevelAndPerCall(): void
    {
        $t = (new MockTransport())->push(MockTransport::json(['ok' => 1], 200, self::HEADERS));
        /** @var list<ResponseInfo> $seen */
        $seen = [];
        /** @var list<ResponseInfo> $perCall */
        $perCall = [];
        /** @var list<string> $order */
        $order = [];
        $client = self::hooked($t, function (ResponseInfo $info) use (&$seen, &$order): void {
            $seen[] = $info;
            $order[] = 'client';
        });
        $callHook = function (ResponseInfo $info) use (&$perCall, &$order): void {
            $perCall[] = $info;
            $order[] = 'call';
        };
        $client->panchang->basic(
            lat: 1,
            lon: 2,
            tz: 'UTC',
            lang: 'gu',
            requestOptions: new RequestOptions(onResponse: $callHook),
        );

        self::assertCount(1, $seen);
        self::assertSame($seen, $perCall);
        self::assertSame(['client', 'call'], $order);
        $expected = new ResponseInfo(
            status: 200,
            path: '/v1/panchang/basic',
            requestId: '52a520f261d3db37',
            creditsRemaining: 64043,
            rateLimit: new RateLimit(250, 249, new \DateTimeImmutable('@1794718800')),
            cache: 'HIT',
            language: 'gu',
        );
        self::assertEquals($expected, $seen[0]);
        self::assertNotNull($seen[0]->rateLimit);
        self::assertSame('UTC', $seen[0]->rateLimit->resetAt->getTimezone()->getName());
        self::assertSame('2026-11-15T05:00:00+00:00', $seen[0]->rateLimit->resetAt->format(DATE_ATOM));
    }

    public function testLeavesOutHeadersTheApiDidNotSend(): void
    {
        // Free calls have no credits header; a non-numeric value or an incomplete rate-limit set
        // is left out too.
        $t = (new MockTransport())->push(MockTransport::json(['ok' => 1], 200, [
            'x-request-id' => 'abc',
            'x-credits-remaining' => 'n/a',
            'x-ratelimit-limit' => '250',
            'x-ratelimit-remaining' => '249',
        ]));
        $info = null;
        self::hooked($t, function (ResponseInfo $i) use (&$info): void {
            $info = $i;
        })->meta->version();

        self::assertEquals(new ResponseInfo(status: 200, path: '/v1/meta/version', requestId: 'abc'), $info);
    }

    public function testReportsTheFinalErrorResponseOnceNotTheRetriedAttempts(): void
    {
        $t = new MockTransport();
        foreach ([1, 2, 3] as $n) {
            $t->push(MockTransport::json(
                ['error' => ['code' => 'billing_unavailable', 'message' => 'x']],
                503,
                ['X-Request-ID' => 'r' . $n, 'Retry-After' => '0'],
            ));
        }
        /** @var list<ResponseInfo> $seen */
        $seen = [];
        $client = self::hooked($t, function (ResponseInfo $i) use (&$seen): void {
            $seen[] = $i;
        }, maxRetries: 2);
        try {
            $client->meta->version();
            self::fail('Expected an exception');
        } catch (ServerException $e) {
            self::assertSame(503, $e->getStatus());
        }

        self::assertSame(3, $t->count());
        $reported = array_map(static fn (ResponseInfo $i): array => [$i->status, $i->requestId], $seen);
        self::assertSame([[503, 'r3']], $reported);
    }

    public function testIsNotCalledForANetworkFailure(): void
    {
        // The 503 was retried and the retry failed without a response: nothing to report.
        $t = (new MockTransport())->push(
            MockTransport::json(['error' => ['code' => 'x']], 503, ['X-Request-ID' => 'r1', 'Retry-After' => '0']),
            new ConnectionException('boom'),
        );
        $calls = 0;
        $client = self::hooked($t, function () use (&$calls): void {
            $calls++;
        }, maxRetries: 1);
        try {
            $client->meta->version();
            self::fail('Expected an exception');
        } catch (ConnectionException) {
        }

        self::assertSame(2, $t->count());
        self::assertSame(0, $calls);
    }

    public function testAThrowingHookNeverBreaksTheCall(): void
    {
        $t = (new MockTransport())->push(MockTransport::json(['v' => 7], 200, self::HEADERS));
        $broken = static function (): void {
            throw new \RuntimeException('logger down');
        };
        $client = self::hooked($t, $broken);

        self::assertSame(['v' => 7], $client->meta->version(new RequestOptions(onResponse: $broken)));
    }

    public function testAcceptsAnyCallable(): void
    {
        $t = (new MockTransport())->push(MockTransport::json(['ok' => 1], 200, self::HEADERS));
        $log = new class {
            /** @var list<ResponseInfo> */
            public array $seen = [];

            public function record(ResponseInfo $info): void
            {
                $this->seen[] = $info;
            }
        };
        self::hooked($t, [$log, 'record'])->meta->version();

        self::assertCount(1, $log->seen);
        self::assertSame('52a520f261d3db37', $log->seen[0]->requestId);
    }
}
