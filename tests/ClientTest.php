<?php

declare(strict_types=1);

namespace LifeAstroApi\Tests;

use LifeAstroApi\Exception\LifeAstroException;
use LifeAstroApi\Http\TransportResponse;
use LifeAstroApi\LifeAstro;
use LifeAstroApi\RequestOptions;
use LifeAstroApi\Tests\Support\MockTransport;

/** Request building and response handling (Node: "transport", "escape hatch", "non-enveloped bodies"). */
final class ClientTest extends TestCase
{
    public function testBuildsAnAuthenticatedGetAndUnwrapsData(): void
    {
        $t = (new MockTransport())->push(
            MockTransport::json(['data' => ['number' => 3], 'meta' => ['cache' => 'miss']]),
        );
        $res = self::client($t)->panchang->tithi(lat: 28.61, lon: 77.2, tz: 'Asia/Kolkata', date: '2027-01-15');

        self::assertSame(['number' => 3], $res);
        self::assertSame('/v1/panchang/tithi', $t->lastPath());
        self::assertSame('28.61', $t->lastQuery()['lat']);
        self::assertSame('Asia/Kolkata', $t->lastQuery()['tz']);
        $headers = $t->lastHeaders();
        self::assertSame('Bearer dv_live_test', $headers['Authorization']);
        self::assertSame('application/json', $headers['Accept']);
        self::assertSame('lifeastroapi-php/' . LifeAstro::VERSION, $headers['User-Agent']);
        self::assertStringStartsWith('https://api.test/v1/panchang/tithi?', $t->lastUrl());
    }

    public function testReturnsTheFullEnvelopeWithRaw(): void
    {
        $t = (new MockTransport())->push(MockTransport::json(['data' => ['x' => 1], 'meta' => ['tier' => 'standard']]));
        $res = self::client($t)->request(
            '/v1/panchang/tithi',
            ['lat' => 1, 'lon' => 2, 'tz' => 'UTC'],
            new RequestOptions(raw: true),
        );
        self::assertSame(['data' => ['x' => 1], 'meta' => ['tier' => 'standard']], $res);
    }

    public function testOmitsEmptyParamsAndSortsTheQueryString(): void
    {
        $t = new MockTransport();
        self::client($t)->panchang->tithi(lat: 28.61, lon: 77.2, tz: 'Asia/Kolkata', date: '', time: null);
        self::assertArrayNotHasKey('date', $t->lastQuery());
        self::assertArrayNotHasKey('time', $t->lastQuery());
        self::assertSame('/v1/panchang/tithi?lat=28.61&lon=77.2&tz=Asia%2FKolkata', $t->lastPathAndQuery());
    }

    public function testBaseUrlTrailingSlashAndPathWithoutLeadingSlash(): void
    {
        $t = new MockTransport();
        $client = new LifeAstro(apiKey: 'dv_live_test', baseUrl: 'http://localhost:8080///', transport: $t);
        $client->request('v1/meta/version');
        self::assertSame('http://localhost:8080/v1/meta/version', $t->lastUrl());
    }

    public function testDefaultBaseUrl(): void
    {
        $t = new MockTransport();
        (new LifeAstro(apiKey: 'dv_live_test', transport: $t))->meta->version();
        self::assertSame('https://api.lifeastroapi.com/v1/meta/version', $t->lastUrl());
    }

    public function testDefaultHeadersAreSentAndCanOverrideTheSdkHeaders(): void
    {
        $t = new MockTransport();
        $client = new LifeAstro(
            apiKey: 'dv_live_test',
            transport: $t,
            defaultHeaders: ['X-Request-Source' => 'my-app', 'user-agent' => 'my-app/1.0'],
        );
        $client->meta->version();
        $headers = $t->lastHeaders();
        self::assertSame('my-app', $headers['X-Request-Source']);
        self::assertSame('my-app/1.0', $headers['user-agent']);
        self::assertArrayNotHasKey('User-Agent', $headers);
        self::assertSame('Bearer dv_live_test', $headers['Authorization']);
    }

    public function testRequestHitsAnArbitraryPathAndUnwrapsData(): void
    {
        $t = (new MockTransport())->push(MockTransport::json(['data' => ['ok' => true]]));
        $res = self::client($t)->request('/v1/some/new-endpoint', ['b' => 2, 'a' => 'x y', 'skip' => null]);
        self::assertSame(['ok' => true], $res);
        self::assertSame('/v1/some/new-endpoint?a=x+y&b=2', $t->lastPathAndQuery());
    }

    public function testGetIsAnAliasOfRequest(): void
    {
        $t = (new MockTransport())->push(MockTransport::json(['value' => 1]));
        self::assertSame(['value' => 1], self::client($t)->get('/v1/meta/version'));
    }

    public function testReturnsABareArrayBodyAsIs(): void
    {
        $t = (new MockTransport())->push(MockTransport::json([1, 2, 3]));
        self::assertSame([1, 2, 3], self::client($t)->meta->version());
    }

    public function testReturnsAnObjectWithoutDataAsIs(): void
    {
        $t = (new MockTransport())->push(MockTransport::json(['tithi' => ['name' => 'Saptami']]));
        self::assertSame(['tithi' => ['name' => 'Saptami']], self::client($t)->meta->version());
    }

    public function testRequestReturnsANonJsonSuccessBodyAsTheString(): void
    {
        $t = (new MockTransport())->push(new TransportResponse(200, ['Content-Type' => 'text/plain'], 'OK'));
        self::assertSame('OK', self::client($t)->request('/healthz'));
    }

    public function testTypedMethodsRejectANonJsonSuccessBody(): void
    {
        $t = (new MockTransport())->push(new TransportResponse(200, [], 'not json'));
        try {
            self::client($t)->meta->version();
            self::fail('Expected an exception');
        } catch (LifeAstroException $e) {
            self::assertSame('invalid_response', $e->getErrorCode());
            self::assertSame(200, $e->getStatus());
            self::assertSame('not json', $e->getBody());
        }
    }

    public function testAnEmptySuccessBodyIsAnEmptyArray(): void
    {
        $t = (new MockTransport())->push(new TransportResponse(204));
        self::assertSame([], self::client($t)->meta->version());
        $t->push(new TransportResponse(204));
        self::assertNull(self::client($t)->request('/v1/meta/version'));
    }

    public function testKeepsUnicodeInResponses(): void
    {
        $t = (new MockTransport())->push(MockTransport::json(['tithi' => ['name' => 'સપ્તમી']]));
        $res = self::client($t)->panchang->tithi(lat: 23.02, lon: 72.57, tz: 'Asia/Kolkata', lang: 'gu');
        self::assertSame(['tithi' => ['name' => 'સપ્તમી']], $res);
    }

    public function testTimeoutReachesTheTransportAndCanBeOverriddenPerCall(): void
    {
        $t = new MockTransport();
        $client = self::client($t, timeout: 12.5);
        $client->meta->version();
        self::assertSame(12.5, $t->requests[0]['timeout']);
        $client->meta->version(requestOptions: new RequestOptions(timeout: 3));
        self::assertSame(3.0, $t->requests[1]['timeout']);
    }

    public function testDebugOutputMasksTheKey(): void
    {
        $client = new LifeAstro(apiKey: 'dv_live_secret123', transport: new MockTransport());
        $dump = print_r($client, true);
        self::assertStringNotContainsString('secret123', $dump);
        self::assertStringContainsString('dv_live_****', $dump);
        ob_start();
        var_dump($client);
        $dumped = (string) ob_get_clean();
        self::assertStringNotContainsString('secret123', $dumped);
    }
}
