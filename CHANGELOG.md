# Changelog

## 0.1.0 (2026-11-14)

First release of the official PHP SDK.

- `LifeAstro` client for PHP 8.1 and later. The API key comes from `apiKey:` or the
  `LIFEASTRO_API_KEY` environment variable (also read from `$_ENV` / `$_SERVER`, as filled by
  .env loaders).
- 322 methods in 23 resource groups (`$client->panchang->basic(...)`, `$client->chart->planets(...)`,
  `$client->western->natal->chart(...)`, ...): one method per endpoint, generated from the same
  manifest as the Node.js SDK 0.9.1, with the Node method and argument names.
- PHP 8 named arguments mapped to the API's wire names: unprefixed `date` / `time` / `tz` / `lat` /
  `lon` for a chart, `birth: [...]` for the natal chart of transit-style methods, `boy: [...]` /
  `girl: [...]` for kundli matching and `personA: [...]` / `personB: [...]` for Western pairs.
  Moment methods send today's date in the given `tz` when `date` is left out.
- `lang` on every Vedic method whose response carries names or text, sent as `lang` and `locale`.
  The `Lang` enum lists the 10 codes (`Lang::Gu`); a plain string (`'gu'`) works too. `Ayanamsa`
  and `HouseSystem` enums for the calculation settings.
- Typed exceptions with `getStatus()`, `getErrorCode()`, `getMessage()` and `getRequestId()`:
  `BadRequestException`, `AuthenticationException`, `PaymentRequiredException`,
  `PermissionException`, `NotFoundException`, `RateLimitException` (`getRetryAfter()`),
  `ServerException`, `ConnectionException` and `TimeoutException`, all extending
  `LifeAstroException`.
- Automatic retries with exponential backoff on 408, 425, 429, 500, 502, 503, 504, network errors
  and timeouts, honouring `Retry-After`; per-call `RequestOptions(timeout: ..., raw: ...)`.
- No third-party runtime packages: the default transport uses `ext-curl` and keeps the connection
  open between calls. Any PSR-18 client (Guzzle, Symfony, ...) can be passed as `transport:`.
- `$client->request($path, $query)` / `$client->get(...)` to call any `/v1/*` endpoint directly.
- Responses are the decoded JSON as arrays. Every method has native types and a PHPDoc block with
  the endpoint description, credit cost, path and array shapes; the package passes PHPStan at
  level max.
- Not in this version: branded PDF rendering of `reports.*` (the Node SDK's `lifeastroapi/pdf`).
