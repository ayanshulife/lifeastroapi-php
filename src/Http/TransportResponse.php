<?php

declare(strict_types=1);

namespace LifeAstroApi\Http;

/** An HTTP response as a {@see Transport} returns it. */
final class TransportResponse
{
    /** @var array<string, string> Header values by lower-case name. */
    public readonly array $headers;

    /**
     * @param int $status HTTP status code.
     * @param array<string, string> $headers Header values by name (any case); repeated headers
     *     joined with ", ".
     * @param string $body The raw body.
     */
    public function __construct(
        public readonly int $status,
        array $headers = [],
        public readonly string $body = '',
    ) {
        $lower = [];
        foreach ($headers as $name => $value) {
            $lower[strtolower((string) $name)] = $value;
        }
        $this->headers = $lower;
    }

    /** A header value by name (case-insensitive), or null. */
    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }
}
