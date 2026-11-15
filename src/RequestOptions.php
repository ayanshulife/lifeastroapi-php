<?php

declare(strict_types=1);

namespace LifeAstroApi;

/**
 * Options for a single call, passed as the last argument of any method:
 *
 * ```php
 * $client->chart->planets(...$arjun, requestOptions: new RequestOptions(timeout: 5));
 * ```
 */
final class RequestOptions
{
    /** @var (\Closure(ResponseInfo): mixed)|null */
    public readonly ?\Closure $onResponse;

    /**
     * @param float|null $timeout Seconds for this call, overriding the client timeout. Applies to
     *     the built-in cURL transport; a PSR-18 client uses its own timeout settings.
     * @param bool $raw Return the decoded body exactly as received, without unwrapping a
     *     `{ data, meta }` envelope if one is present.
     * @param (callable(ResponseInfo): mixed)|null $onResponse Called with this call's
     *     {@see ResponseInfo} (after the client-level `onResponse`). Exceptions thrown inside it
     *     are ignored; its return value is not used.
     */
    public function __construct(
        public readonly ?float $timeout = null,
        public readonly bool $raw = false,
        ?callable $onResponse = null,
    ) {
        $this->onResponse = $onResponse === null ? null : \Closure::fromCallable($onResponse);
    }
}
