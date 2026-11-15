<?php

declare(strict_types=1);

namespace LifeAstroApi;

use LifeAstroApi\Exception\LifeAstroException;
use LifeAstroApi\Http\CurlTransport;
use LifeAstroApi\Http\HttpClient;
use LifeAstroApi\Http\Psr18Transport;
use LifeAstroApi\Http\Transport;
use LifeAstroApi\Resources\Resources;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * The LifeAstroAPI client.
 *
 * ```php
 * use LifeAstroApi\LifeAstro;
 * use LifeAstroApi\Lang;
 *
 * $client = new LifeAstro(apiKey: 'dv_live_...');   // or new LifeAstro() with LIFEASTRO_API_KEY set
 *
 * $day = $client->panchang->basic(lat: 23.0225, lon: 72.5714, tz: 'Asia/Kolkata', lang: Lang::Gu);
 *
 * $planets = $client->chart->planets(
 *     date: '1990-01-15', time: '10:30', tz: 'Asia/Kolkata', lat: 19.076, lon: 72.8777,
 * );
 * ```
 *
 * Every endpoint is a method on a resource group: `$client->panchang`, `$client->chart`,
 * `$client->milan`, `$client->western->natal` and so on. Methods return the decoded JSON as an
 * array and throw a {@see LifeAstroException} subclass on errors.
 */
final class LifeAstro extends Resources
{
    /** SDK version, sent in the `User-Agent` header. */
    public const VERSION = '0.3.0';

    public const DEFAULT_BASE_URL = 'https://api.lifeastroapi.com';

    /** Environment variable read when no `apiKey` is passed. */
    public const API_KEY_ENV = 'LIFEASTRO_API_KEY';

    private readonly HttpClient $http;

    /**
     * @param string|null $apiKey Your API key (`dv_live_...`). Falls back to the
     *     `LIFEASTRO_API_KEY` environment variable. Get one at https://lifeastroapi.com/dashboard/keys
     * @param string|null $baseUrl API base URL; default `https://api.lifeastroapi.com`
     *     (for example `http://localhost:8080` for local work).
     * @param float $timeout Seconds per request (default 30); 0 means no limit. Applies to the
     *     built-in cURL transport; a PSR-18 client uses its own timeout setting.
     * @param int $maxRetries Automatic retries on 408, 425, 429, 500, 502, 503, 504, network errors
     *     and timeouts (default 2).
     * @param Transport|ClientInterface|null $transport How requests are sent: null for the built-in
     *     cURL transport, any PSR-18 client (Guzzle, Symfony, ...), or your own {@see Transport}.
     * @param RequestFactoryInterface|null $requestFactory PSR-17 request factory for a PSR-18
     *     `transport`. Optional when the client is a factory itself (Symfony's `Psr18Client`).
     * @param array<string, string> $defaultHeaders Extra headers on every request.
     * @param (callable(ResponseInfo): mixed)|null $onResponse Called with the {@see ResponseInfo} of
     *     every call: request id, credits left, rate limit, cache and language. Use it to log usage
     *     or warn when credits run low. Exceptions thrown inside it are ignored.
     *
     * @throws LifeAstroException With code `missing_api_key` when there is no key.
     * @throws \InvalidArgumentException When a PSR-18 client comes without a request factory.
     */
    public function __construct(
        #[\SensitiveParameter] ?string $apiKey = null,
        ?string $baseUrl = null,
        float $timeout = 30.0,
        int $maxRetries = 2,
        Transport|ClientInterface|null $transport = null,
        ?RequestFactoryInterface $requestFactory = null,
        array $defaultHeaders = [],
        ?callable $onResponse = null,
    ) {
        $apiKey ??= self::keyFromEnvironment();
        if ($apiKey === null || trim($apiKey) === '') {
            throw new LifeAstroException(
                'LifeAstro: an API key is required. Pass apiKey: or set LIFEASTRO_API_KEY. '
                . 'Get one at https://lifeastroapi.com/dashboard/keys',
                'missing_api_key',
            );
        }

        $this->http = new HttpClient(
            $apiKey,
            rtrim($baseUrl ?? self::DEFAULT_BASE_URL, '/'),
            $timeout,
            max(0, $maxRetries),
            self::resolveTransport($transport, $requestFactory),
            'lifeastroapi-php/' . self::VERSION,
            $defaultHeaders,
            $onResponse === null ? null : \Closure::fromCallable($onResponse),
        );
        parent::__construct($this->http);
    }

    /**
     * Call any `/v1/*` endpoint by its path and wire parameter names, for example one that is
     * newer than this SDK version. Returns the decoded body (`data` of an envelope, if any).
     *
     * ```php
     * $tithi = $client->request('/v1/panchang/tithi', [
     *     'lat' => 28.6139, 'lon' => 77.209, 'tz' => 'Asia/Kolkata', 'date' => '2027-01-15',
     * ]);
     * ```
     *
     * @param array<string, scalar|\BackedEnum|\Stringable|null> $query Wire names => values; null
     *     and '' are not sent.
     *
     * @throws LifeAstroException
     */
    public function request(string $path, array $query = [], ?RequestOptions $requestOptions = null): mixed
    {
        return $this->http->request($path, $query, $requestOptions);
    }

    /**
     * Alias of {@see request()}.
     *
     * @param array<string, scalar|\BackedEnum|\Stringable|null> $query
     *
     * @throws LifeAstroException
     */
    public function get(string $path, array $query = [], ?RequestOptions $requestOptions = null): mixed
    {
        return $this->http->request($path, $query, $requestOptions);
    }

    /**
     * Never print the key: `var_dump($client)` shows it masked.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return ['http' => $this->http];
    }

    private static function keyFromEnvironment(): ?string
    {
        foreach ([$_ENV[self::API_KEY_ENV] ?? null, $_SERVER[self::API_KEY_ENV] ?? null] as $value) {
            if (\is_string($value) && $value !== '') {
                return $value;
            }
        }
        $value = getenv(self::API_KEY_ENV);

        return \is_string($value) && $value !== '' ? $value : null;
    }

    private static function resolveTransport(
        Transport|ClientInterface|null $transport,
        ?RequestFactoryInterface $requestFactory,
    ): Transport {
        if ($transport === null) {
            return new CurlTransport();
        }
        if ($transport instanceof Transport) {
            return $transport;
        }
        $requestFactory ??= $transport instanceof RequestFactoryInterface ? $transport : null;
        if ($requestFactory === null) {
            throw new \InvalidArgumentException(
                'LifeAstro: a PSR-18 transport needs a PSR-17 request factory. Pass requestFactory:, '
                . 'for example new \GuzzleHttp\Psr7\HttpFactory() or new \Nyholm\Psr7\Factory\Psr17Factory().'
            );
        }

        return new Psr18Transport($transport, $requestFactory);
    }
}
