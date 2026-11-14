<?php

declare(strict_types=1);

namespace LifeAstroApi\Tests\Support;

use LifeAstroApi\Http\Transport;
use LifeAstroApi\Http\TransportResponse;

/** Records every request and answers from a queue, a responder, or with `{}`. */
final class MockTransport implements Transport
{
    /** @var list<array{url: string, headers: array<string, string>, timeout: float}> */
    public array $requests = [];

    /** @var list<TransportResponse|\Throwable> */
    private array $queue = [];

    /** @param (\Closure(string): TransportResponse)|null $responder */
    public function __construct(private readonly ?\Closure $responder = null)
    {
    }

    /** Queue responses (or exceptions to throw) for the next calls, in order. */
    public function push(TransportResponse|\Throwable ...$items): self
    {
        foreach ($items as $item) {
            $this->queue[] = $item;
        }

        return $this;
    }

    /**
     * A JSON response.
     *
     * @param array<string, string> $headers
     */
    public static function json(mixed $body, int $status = 200, array $headers = []): TransportResponse
    {
        return new TransportResponse(
            $status,
            ['Content-Type' => 'application/json'] + $headers,
            (string) json_encode($body, JSON_UNESCAPED_UNICODE),
        );
    }

    public function get(string $url, array $headers, float $timeout): TransportResponse
    {
        $this->requests[] = ['url' => $url, 'headers' => $headers, 'timeout' => $timeout];
        $next = array_shift($this->queue);
        if ($next instanceof \Throwable) {
            throw $next;
        }
        if ($next !== null) {
            return $next;
        }
        if ($this->responder !== null) {
            return ($this->responder)($url);
        }

        return self::json([]);
    }

    /** @phpstan-impure */
    public function count(): int
    {
        return \count($this->requests);
    }

    /** @phpstan-impure */
    public function lastUrl(): string
    {
        $last = end($this->requests);
        if ($last === false) {
            throw new \LogicException('No request was sent.');
        }

        return $last['url'];
    }

    /**
     * @return array<string, string>
     *
     * @phpstan-impure
     */
    public function lastHeaders(): array
    {
        $last = end($this->requests);
        if ($last === false) {
            throw new \LogicException('No request was sent.');
        }

        return $last['headers'];
    }

    /**
     * Path and query string, without scheme and host.
     *
     * @phpstan-impure
     */
    public function lastPathAndQuery(): string
    {
        $url = $this->lastUrl();
        $path = (string) parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

        return \is_string($query) ? $path . '?' . $query : $path;
    }

    /** @phpstan-impure */
    public function lastPath(): string
    {
        return (string) parse_url($this->lastUrl(), PHP_URL_PATH);
    }

    /**
     * The query of the last request as wire name => value. (parse_str() would turn the dots of
     * `birth.lat` into underscores, so this decodes by hand.)
     *
     * @return array<string, string>
     *
     * @phpstan-impure
     */
    public function lastQuery(): array
    {
        $query = parse_url($this->lastUrl(), PHP_URL_QUERY);
        $out = [];
        if (!\is_string($query) || $query === '') {
            return $out;
        }
        foreach (explode('&', $query) as $pair) {
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');
            $out[urldecode($key)] = urldecode($value);
        }

        return $out;
    }
}
