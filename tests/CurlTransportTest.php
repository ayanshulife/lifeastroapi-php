<?php

declare(strict_types=1);

namespace LifeAstroApi\Tests;

use LifeAstroApi\Exception\ConnectionException;
use LifeAstroApi\Exception\NotFoundException;
use LifeAstroApi\Exception\ServerException;
use LifeAstroApi\Exception\TimeoutException;
use LifeAstroApi\Http\CurlTransport;
use LifeAstroApi\LifeAstro;
use LifeAstroApi\RequestOptions;

/**
 * The default cURL transport against a real HTTP server on 127.0.0.1 (PHP's built-in server
 * running tests/fixtures/server.php). No outside network is used.
 */
final class CurlTransportTest extends TestCase
{
    /** @var resource|null */
    private static $server = null;

    private static string $baseUrl = '';

    public static function setUpBeforeClass(): void
    {
        $probe = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        if ($probe === false) {
            self::markTestSkipped("Cannot open a local port: {$error}");
        }
        $name = (string) stream_socket_get_name($probe, false);
        fclose($probe);
        $port = (int) substr($name, (int) strrpos($name, ':') + 1);

        $cmd = [PHP_BINARY, '-S', "127.0.0.1:{$port}", __DIR__ . '/fixtures/server.php'];
        // One process, no PHP_CLI_SERVER_WORKERS: the tests run one request at a time, and a
        // single process stops cleanly on proc_terminate().
        $process = proc_open($cmd, [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
        if (!\is_resource($process)) {
            self::markTestSkipped('Cannot start the PHP built-in server');
        }
        self::$server = $process;
        self::$baseUrl = "http://127.0.0.1:{$port}";

        for ($i = 0; $i < 100; $i++) {
            $socket = @fsockopen('127.0.0.1', $port, $errno, $error, 0.1);
            if ($socket !== false) {
                fclose($socket);

                return;
            }
            usleep(50_000);
        }
        self::markTestSkipped('The PHP built-in server did not start');
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$server !== null) {
            proc_terminate(self::$server);
            for ($i = 0; $i < 40 && proc_get_status(self::$server)['running']; $i++) {
                usleep(50_000);
            }
            if (proc_get_status(self::$server)['running']) {
                proc_terminate(self::$server, 9);
            }
            proc_close(self::$server);
            self::$server = null;
        }
    }

    private static function live(int $maxRetries = 0, float $timeout = 10.0): LifeAstro
    {
        return new LifeAstro(
            apiKey: 'dv_live_test',
            baseUrl: self::$baseUrl,
            timeout: $timeout,
            maxRetries: $maxRetries,
        );
    }

    public function testSendsHeadersAndDecodesAGzippedUtf8Body(): void
    {
        $res = self::live()->request('/v1/echo', ['b' => 'x y', 'a' => 1, 'birth.lat' => 19.076]);
        self::assertIsArray($res);
        self::assertSame('GET', $res['method']);
        self::assertSame('/v1/echo?a=1&b=x+y&birth.lat=19.076', $res['uri']);
        self::assertSame('સપ્તમી', $res['text']);
        $headers = $res['headers'];
        self::assertIsArray($headers);
        self::assertSame('Bearer dv_live_test', $headers['authorization']);
        self::assertSame('application/json', $headers['accept']);
        self::assertSame('lifeastroapi-php/' . LifeAstro::VERSION, $headers['user-agent']);
        self::assertIsString($headers['accept-encoding']);
        self::assertStringContainsString('gzip', $headers['accept-encoding']);
    }

    public function testReusesOneTransportForManyCalls(): void
    {
        $client = self::live();
        for ($i = 0; $i < 3; $i++) {
            $res = $client->request('/v1/echo', ['i' => $i]);
            self::assertIsArray($res);
            self::assertSame("/v1/echo?i={$i}", $res['uri']);
        }
    }

    public function testMapsErrorResponses(): void
    {
        try {
            self::live()->request('/v1/status/404');
            self::fail('Expected an exception');
        } catch (NotFoundException $e) {
            self::assertSame('test_404', $e->getErrorCode());
            self::assertSame('req_local', $e->getRequestId());
        }
    }

    public function testRetriesA503AndReadsRetryAfter(): void
    {
        $started = microtime(true);
        try {
            self::live(maxRetries: 2)->request('/v1/status/503');
            self::fail('Expected an exception');
        } catch (ServerException $e) {
            self::assertSame(503, $e->getStatus());
        }
        self::assertLessThan(3.0, microtime(true) - $started, 'Retry-After: 0 means no wait');
    }

    public function testFollowsRedirects(): void
    {
        $res = self::live()->request('/v1/redirect');
        self::assertIsArray($res);
        self::assertSame('/v1/echo?from=redirect', $res['uri']);
    }

    public function testReturnsAPlainTextBodyAsAString(): void
    {
        self::assertSame('plain text', self::live()->request('/v1/text'));
    }

    public function testTimesOut(): void
    {
        $started = microtime(true);
        try {
            self::live()->request('/v1/slow', [], new RequestOptions(timeout: 0.3));
            self::fail('Expected a timeout');
        } catch (TimeoutException $e) {
            self::assertSame('timeout', $e->getErrorCode());
            self::assertStringContainsString('0.3 s', $e->getMessage());
        }
        self::assertLessThan(1.4, microtime(true) - $started);
    }

    public function testAClosedPortIsAConnectionException(): void
    {
        $probe = stream_socket_server('tcp://127.0.0.1:0');
        self::assertNotFalse($probe);
        $name = (string) stream_socket_get_name($probe, false);
        fclose($probe);

        $client = new LifeAstro(
            apiKey: 'dv_live_test',
            baseUrl: 'http://' . $name,
            maxRetries: 0,
            transport: new CurlTransport(),
        );
        try {
            $client->meta->version();
            self::fail('Expected an exception');
        } catch (ConnectionException $e) {
            self::assertNotInstanceOf(TimeoutException::class, $e);
            self::assertSame(0, $e->getStatus());
            self::assertSame('connection_error', $e->getErrorCode());
        }
    }
}
