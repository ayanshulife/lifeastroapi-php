<?php

declare(strict_types=1);

namespace LifeAstroApi\Tests;

use LifeAstroApi\Exception\ConnectionException;
use LifeAstroApi\Exception\RateLimitException;
use LifeAstroApi\Exception\TimeoutException;
use LifeAstroApi\LifeAstro;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/** A PSR-18 client as the transport (Guzzle, Symfony and others plug in this way). */
final class Psr18TransportTest extends TestCase
{
    /**
     * @param list<ResponseInterface|\Throwable> $replies
     *
     * @return ClientInterface&object{requests: list<RequestInterface>}
     */
    private static function psrClient(array $replies): ClientInterface
    {
        return new class ($replies) implements ClientInterface {
            /** @var list<RequestInterface> */
            public array $requests = [];

            /** @param list<ResponseInterface|\Throwable> $replies */
            public function __construct(private array $replies)
            {
            }

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->requests[] = $request;
                $next = array_shift($this->replies) ?? new Response(200, [], '{}');
                if ($next instanceof \Throwable) {
                    throw $next;
                }

                return $next;
            }
        };
    }

    private static function networkError(string $message): NetworkExceptionInterface
    {
        return new class ($message) extends \RuntimeException implements NetworkExceptionInterface {
            public function getRequest(): RequestInterface
            {
                return (new Psr17Factory())->createRequest('GET', 'https://api.test');
            }
        };
    }

    public function testSendsTheRequestThroughThePsr18Client(): void
    {
        $psr = self::psrClient([
            new Response(200, ['Content-Type' => 'application/json'], '{"data":{"tithi":"Saptami"}}'),
        ]);
        $client = new LifeAstro(apiKey: 'dv_live_test', transport: $psr, requestFactory: new Psr17Factory());

        $res = $client->panchang->tithi(lat: 28.6139, lon: 77.209, tz: 'Asia/Kolkata', date: '2027-01-15', lang: 'hi');

        self::assertSame(['tithi' => 'Saptami'], $res);
        $request = $psr->requests[0];
        self::assertSame('GET', $request->getMethod());
        self::assertSame(
            'https://api.lifeastroapi.com/v1/panchang/tithi?date=2027-01-15&lang=hi&lat=28.6139&locale=hi&lon=77.209'
            . '&tz=Asia%2FKolkata',
            (string) $request->getUri(),
        );
        self::assertSame('Bearer dv_live_test', $request->getHeaderLine('Authorization'));
        self::assertSame('application/json', $request->getHeaderLine('Accept'));
        self::assertSame('lifeastroapi-php/' . LifeAstro::VERSION, $request->getHeaderLine('User-Agent'));
    }

    public function testMapsErrorsAndRetryAfterFromThePsrResponse(): void
    {
        $body = '{"error":{"code":"rate_limited","message":"Slow down","request_id":"req_9"}}';
        $psr = self::psrClient([
            new Response(429, ['Retry-After' => '0'], $body),
            new Response(429, ['Retry-After' => '4'], $body),
        ]);
        $client = new LifeAstro(
            apiKey: 'dv_live_test',
            maxRetries: 1,
            transport: $psr,
            requestFactory: new Psr17Factory(),
        );
        try {
            $client->meta->version();
            self::fail('Expected an exception');
        } catch (RateLimitException $e) {
            self::assertSame(4.0, $e->getRetryAfter());
            self::assertSame('req_9', $e->getRequestId());
        }
        self::assertCount(2, $psr->requests);
    }

    public function testMapsNetworkExceptionsAndTimeouts(): void
    {
        $psr = self::psrClient([
            self::networkError('Could not resolve host'),
            self::networkError('cURL error 28: Operation timed out after 30001 milliseconds'),
        ]);
        $client = new LifeAstro(
            apiKey: 'dv_live_test',
            maxRetries: 0,
            transport: $psr,
            requestFactory: new Psr17Factory(),
        );
        try {
            $client->meta->version();
            self::fail('Expected an exception');
        } catch (ConnectionException $e) {
            self::assertNotInstanceOf(TimeoutException::class, $e);
            self::assertSame('connection_error', $e->getErrorCode());
            self::assertInstanceOf(NetworkExceptionInterface::class, $e->getPrevious());
        }
        $this->expectException(TimeoutException::class);
        $client->meta->version();
    }

    public function testAClientThatIsAlsoARequestFactoryNeedsNoFactory(): void
    {
        $psr = new class implements ClientInterface, \Psr\Http\Message\RequestFactoryInterface {
            public ?RequestInterface $last = null;

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->last = $request;

                return new Response(200, [], '{"ok":true}');
            }

            public function createRequest(string $method, $uri): RequestInterface
            {
                return (new Psr17Factory())->createRequest($method, $uri);
            }
        };
        $client = new LifeAstro(apiKey: 'dv_live_test', transport: $psr);
        self::assertSame(['ok' => true], $client->meta->version());
        self::assertNotNull($psr->last);
    }

    public function testAPsr18ClientWithoutAFactoryIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('request factory');
        new LifeAstro(apiKey: 'dv_live_test', transport: self::psrClient([]));
    }
}
