# lifeastroapi/lifeastroapi

Official **PHP SDK** for [LifeAstroAPI](https://lifeastroapi.com): a REST
[astrology API](https://lifeastroapi.com/apis/) for Vedic astrology, panchang, Hindu festivals,
kundli, kundli matching, muhurat, dashas, horoscopes, numerology, tarot and Western charts, with
location-aware calculations and pay-per-call billing. Free tier: 1,500 credits a month, no card.
[Get a key](https://lifeastroapi.com/signup/).

- **Every endpoint**: 322 methods across 23 resource groups (panchang, chart, dasha, milan,
  transit, horoscope, numerology, muhurta, reports, narrative, remedies, a complete Western
  module and more). Same methods as the official Node.js SDK, generated from the same manifest.
- **No third-party runtime packages**: the default transport uses `ext-curl` and keeps the
  connection open between calls. Bring your own PSR-18 client (Guzzle, Symfony) if you prefer.
- **10 languages**: Vedic methods answer in `en`, `hi`, `mr`, `gu`, `bn`, `te`, `ta`, `kn`, `ml`
  and `pa` with `lang:`.
- **Typed**: PHP 8 named arguments, native types, enums for languages, ayanamsas and house
  systems, and a PHPDoc block on every method (description, credit cost, array shapes). Passes
  PHPStan at level max.
- Typed exceptions, automatic retries (429, 5xx and network errors) that honour `Retry-After`,
  timeouts, and an escape hatch for any endpoint.
- PHP 8.1 and later. Works in Laravel, Symfony, WordPress or plain PHP.

```bash
composer require lifeastroapi/lifeastroapi
```

Get an API key at **https://lifeastroapi.com/dashboard/keys**.

## Quick start

```php
use LifeAstroApi\Lang;
use LifeAstroApi\LifeAstro;

$client = new LifeAstro(apiKey: 'dv_live_...');
// or: new LifeAstro()  reads the LIFEASTRO_API_KEY environment variable

// Today's panchang for Ahmedabad, in Gujarati
$day = $client->panchang->basic(lat: 23.0225, lon: 72.5714, tz: 'Asia/Kolkata', lang: Lang::Gu);
echo $day['tithi']['name'], ' ', $day['nakshatra']['name'], ' ', $day['vara']['weekday'];
// for example: પંચમી પૂર્વાષાઢા શનિવાર

// A Vedic birth chart
$chart = $client->chart->planets(date: '1990-01-15', time: '10:30', tz: 'Asia/Kolkata', lat: 19.076, lon: 72.8777);
foreach ($chart['planets'] as $planet) {
    echo $planet['name'], ' ', $planet['house'], ' ', $planet['dms_in_sign'], "\n";
}

// Kundli matching score
$match = $client->milan->ashtakootaTotal(
    boy: ['date' => '1990-01-15', 'time' => '10:30', 'tz' => 'Asia/Kolkata', 'lat' => 19.076, 'lon' => 72.8777],
    girl: ['date' => '1992-03-20', 'time' => '08:15', 'tz' => 'Asia/Kolkata', 'lat' => 28.6139, 'lon' => 77.209],
);
echo $match['total'], ' / ', $match['max'];   // 8.5 / 36
```

More in [examples/](https://github.com/ayanshulife/lifeastroapi-php/tree/main/examples): quick
start, error handling, a PSR-18 client, and a Laravel app.

## Laravel

Put the key in `.env`, register one client, and inject it where you need it:

```dotenv
LIFEASTRO_API_KEY=dv_live_...
```

```php
// config/services.php
'lifeastro' => [
    'key' => env('LIFEASTRO_API_KEY'),
],

// app/Providers/AppServiceProvider.php, in register()
$this->app->singleton(LifeAstro::class, fn () => new LifeAstro(apiKey: config('services.lifeastro.key')));
```

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use LifeAstroApi\Exception\LifeAstroException;
use LifeAstroApi\LifeAstro;

class PanchangController extends Controller
{
    public function __construct(private readonly LifeAstro $astro)
    {
    }

    public function show(Request $request)
    {
        $input = $request->validate(['lat' => 'required|numeric', 'lon' => 'required|numeric', 'lang' => 'nullable|string']);

        try {
            $day = $this->astro->panchang->basic(
                lat: (float) $input['lat'],
                lon: (float) $input['lon'],
                tz: 'Asia/Kolkata',
                lang: $input['lang'] ?? 'en',
            );
        } catch (LifeAstroException $e) {
            report($e);
            return response()->json(['error' => 'Panchang is not available right now.'], 503);
        }

        return response()->json(['tithi' => $day['tithi'], 'nakshatra' => $day['nakshatra']]);
    }
}
```

The full example, with validation and the route, is in
[examples/laravel/](https://github.com/ayanshulife/lifeastroapi-php/tree/main/examples/laravel).

## Authentication

Your API key (`dv_live_...`) is sent as an `Authorization: Bearer ...` header on every request.

```php
new LifeAstro(apiKey: 'dv_live_...');   // 1. explicit
new LifeAstro('dv_live_...');           // 2. first argument
new LifeAstro();                        // 3. reads LIFEASTRO_API_KEY ($_ENV, $_SERVER or getenv())
```

Without a key the constructor throws `LifeAstroException` (error code `missing_api_key`).
`var_dump($client)` shows the key masked.

> **Keep the key on your server.** A `dv_live_...` key carries your account's full billing
> access. Load it from an environment variable or a secrets manager, never from code that ships to
> browsers or apps, and never commit it.

## Configuration

```php
$client = new LifeAstro(
    apiKey: 'dv_live_...',                    // or set LIFEASTRO_API_KEY
    baseUrl: 'https://api.lifeastroapi.com',  // e.g. http://localhost:8080 for local work
    timeout: 30.0,                            // seconds per request (default 30)
    maxRetries: 2,                            // retries on 429 / 5xx / network errors (default 2)
    defaultHeaders: ['X-Request-Source' => 'my-app'],  // extra headers on every request
);
```

Create one client and reuse it: it keeps the HTTP connection open between calls.

## Arguments

Every method takes **named arguments**. The SDK turns them into the exact query parameters the
API expects (`birth.lat`, `boy.date`, `start_date`, ...).

> Pass arguments by name. Their order is not part of the SDK's contract: a later version may add
> an optional argument in the middle of the list.

| Input | Used by | Arguments |
|-------|---------|-----------|
| **Moment** | `panchang->*`, `muhurta->*` (one date), `prashna->*`, `narrative->prashna` | `lat, lon, tz, date: null, time: null` |
| **Instant** | `transit->positions`, `transit->doubleTransit`, `meta->ayanamsas` | `date: null, time: null, tz: null` |
| **Birth** | `chart->*`, `dasha->*`, narratives, reports | `date, time, tz, lat, lon` |
| **Boy / girl** | `milan->*`, `reports->matchMaking` | `boy: [...], girl: [...]` |
| **Two people** | `western->synastry->*`, `western->composite->*` | `personA: [...], personB: [...]` |
| **Rashi** | `horoscope->*` | `rashi: 'aries', date: null, lang: null` |
| **Date range** | `eclipses->*`, `western->ingresses` | `startDate, endDate` |
| **Muhurta search** | `muhurta->bestTime` | `lat, lon, tz, startDate, endDate, purpose: null, topN: null, birthDate: null` |
| **Natal + window** | `transit->sadeSatiWindows`, `transit->windows` | `birth: [...], from: null, tz: null` |
| **Natal + transit** | `transit->tarabala`, `transit->vedha`, `transit->smallPanoti`, `ashtakavarga->kaksha`, `western->transits->toNatal`, `narrative->transitPhal`, `narrative->dashaPhal`, ... | `birth: [...], date, time, tz` |

`boy`, `girl`, `personA`, `personB` and `birth` are arrays with `date` (`YYYY-MM-DD`), `time`
(`HH:MM`), `tz` (IANA name such as `Asia/Kolkata`, or an offset such as `+05:30`), `lat` and
`lon`. Keep a birth in one array and spread it into the flat methods:

```php
$arjun = ['date' => '1990-01-15', 'time' => '10:30', 'tz' => 'Asia/Kolkata', 'lat' => 19.076, 'lon' => 72.8777];

$client->chart->planets(...$arjun);            // date, time, tz, lat, lon unprefixed
$client->milan->mangalDosha(...$arjun);        // sent as birth.date, birth.time, ...
$client->transit->tarabala(birth: $arjun, date: '2027-01-15', time: '06:00', tz: 'Asia/Kolkata');
$client->chart->planets(...$arjun, lang: Lang::Ta);   // the spread comes first, then named arguments
```

**Names.** Method and argument names are the Node SDK's names: `ashtakootaTotal`, `houseSystem`,
`startDate`. Most arguments have the same name as their query parameter. Where they differ, the
method's PHPDoc lists the wire name; for example `narrative->horoscopeDailyByLagna(onDate: ...)`
sends `transit_date`.

**Dates.** The API needs a `date` on every moment endpoint. Where the argument is optional, the
SDK sends today's date in the `tz` you gave (`transit->positions()` with no arguments uses UTC).

**Settings.** Vedic methods accept `ayanamsa` (Lahiri when left out) and chart methods
`houseSystem`, each as an enum case or its string value:

```php
use LifeAstroApi\Ayanamsa;
use LifeAstroApi\HouseSystem;

$client->chart->planets(...$arjun, ayanamsa: Ayanamsa::Krishnamurti, houseSystem: HouseSystem::Placidus);
$client->chart->planets(...$arjun, ayanamsa: 'raman', houseSystem: 'whole-sign');
```

**Data endpoints** (`chart->kundliFacts`, `chart->yogas`, `chart->doshas`, `chart->sensitivity`,
`transit->sadeSatiWindows`, `transit->windows`, `remedies->forGraha`, `milan->overlay`,
`milan->summary`) return ids and numbers, no prose: planet ids `sun` ... `ketu`, `sign_index`
0-based (0 = Aries), `house` 1-based whole-sign from the lagna, every instant as `*_local`
(RFC 3339) plus `*_jd` (Julian Day, UT).

## Languages

Every Vedic method whose response carries names or text takes an optional `lang`. The same fields
come back in that language; ids, dates, numbers and explicit `*_en` / `*_hi` fields do not change.

```php
$day = $client->panchang->basic(lat: 19.076, lon: 72.8777, tz: 'Asia/Kolkata', date: '2027-01-15', lang: Lang::Mr);
$chart = $client->chart->planets(...$arjun, lang: 'ta');   // the plain code works too
```

| `lang` | Enum | Language | Coverage |
|--------|------|----------|----------|
| `en` | `Lang::En` | English (default) | complete |
| `hi` | `Lang::Hi` | Hindi | complete |
| `mr` | `Lang::Mr` | Marathi | complete |
| `gu` | `Lang::Gu` | Gujarati | complete |
| `bn` | `Lang::Bn` | Bengali | complete |
| `te` | `Lang::Te` | Telugu | complete |
| `ta` | `Lang::Ta` | Tamil | complete |
| `kn` | `Lang::Kn` | Kannada | complete |
| `ml` | `Lang::Ml` | Malayalam | complete |
| `pa` | `Lang::Pa` | Punjabi | names and terms; longer prose is still English |

- `Lang::cases()` lists the codes; `$lang->isComplete()` is false only for `pa`.
- **Where it works:** `panchang`, `calendar`, `festivals`, `eclipses`, `planetMoments`, `transit`,
  `milan`, `muhurta`, `chart`, `prashna`, `numerology`, `dasha`, `varshaphal`, `ashtakavarga`,
  `lalkitab`, `horoscope`, `reports`, `narrative` and `remedies`. The SDK sends it as both `lang`
  and `locale`. `festivals->*`, `panchang->monthly` and some `remedies->*` methods also accept the
  older `locale` argument; `lang` wins when both are given.
- **Methods without `lang`:** those that return only times, numbers or ids, such as
  `panchang->rahuKaal`, `panchang->sunriseSunset`, `chart->yogas`, `chart->doshas`,
  `transit->windows` and `milan->overlay`. There is nothing in them to translate.
- **English only:** `western->*`, `tarot->*`, `geo->*` and `meta->*`. The `western->narrative->*`
  methods take `lang` for `en`, `hi` and `mr`.

## Resource groups

`$client-><group>-><method>(...)` returns the response as a decoded JSON array.

| Group | What it covers |
|-------|----------------|
| `panchang` | tithi, nakshatra, yoga, karana, vara, sunrise/sunset, rahu kaal, choghadiya, hora, muhurtas, monthly calendar, composite `basic` / `advanced` |
| `chart` | planets, ascendant, houses, divisional (D1-D60), dignity, aspects, shadbala, ashtakvarga, avakhada, KP, astro details; data bundles `kundliFacts`, `yogas`, `doshas`, birth-time `sensitivity` |
| `dasha` | Vimshottari (full, current, 5-level drill-down), Yogini and Chara dashas |
| `milan` | Ashtakoota (total, full, per koota), mangal / nadi / shani dosha, navamsa and dasha compatibility, `overlay`, `summary` |
| `transit` | positions, sade sati, ashtakavarga, tarabala, vedha, double transit, small panoti, `sadeSatiWindows`, `windows` |
| `horoscope` | daily, weekly, monthly by rashi |
| `numerology` | driver, conductor, soul, personality, destiny, full, advanced, challenges, personal periods |
| `muhurta` | vivah, naamkaran, griha pravesh, vyapar, yatra, best time, chandra / tara bala |
| `varshaphal` | annual chart, lord, muntha, mudda dasha, harsha bala, tajika yogas |
| `prashna` | horary answer, chart, arudha, significators |
| `eclipses` | solar, lunar, all (date range, local contacts and sutak) |
| `calendar` | Hindu month, ritu, samvatsara, adhik maas, solstices |
| `festivals` | Hindu festivals by month, on a date, or by vrat family; `lang`, `monthSystem` (amanta / purnimanta) |
| `planetMoments` | retrograde windows, ingress, combustion window, speed |
| `ashtakavarga` | sarva, bhinna (per planet), kaksha, transit score |
| `geo` | place search, reverse geocoding, time zone, place by id |
| `tarot` | cards, one card, daily card, spread, yes / no |
| `reports` | JSON reports: kundli (lite, detailed, brihad), match making, mangal dosha, sade sati, varshaphal, numerology, horoscopes |
| `narrative` | authored Vedic readings (lagna, planets, yogas, doshas, dasha phal, transit phal, prashna, horoscopes, ...) |
| `remedies` | remedies by rule id, `forGraha`, mantra, pooja, vrat, daan, rudraksha, yantra, Lal Kitab |
| `lalkitab` | Lal Kitab chart and dasha |
| `meta` | free catalogs: `endpoints` (credit cost of every route), `ayanamsas`, `vedicYogas`, `vedicDoshas`, `vedicLocales`, `version` |
| `western` | the full tropical module (below) |

### Western (tropical)

```php
$priya = ['date' => '1992-03-20', 'time' => '08:15', 'tz' => 'Asia/Kolkata', 'lat' => 28.6139, 'lon' => 77.209];

$client->western->natal->chart(...$arjun, houseSystem: HouseSystem::Placidus);
$client->western->synastry->score(personA: $arjun, personB: $priya);
$client->western->transits->toNatal(birth: $arjun, date: '2026-06-16', time: '12:00', tz: 'UTC');
$client->western->returns->solar(...$arjun, returnYear: 2026);
$client->western->profections->annual(birthDate: '1990-01-15', ascSign: 'capricorn');
```

Sub-groups: `natal`, `horoscope`, `synastry`, `composite`, `davison`, `transits`, `progressions`,
`solarArc`, `returns`, `dignities`, `profections`, `zodiacalReleasing`, `primaryDirections`,
`interpretation`, `narrative`, `heliocentric`, `astrocartography`, `eclipses`, `lunar`,
`compatibility`, plus `firdaria`, `ingresses` and `retrogradeWindow` on `western` itself.

Each method's PHPDoc has the endpoint description, its credit cost and its path. The costs are a
snapshot taken when this version was built; `$client->meta->endpoints()` returns the live cost of
every route.

## Error handling

Every non-2xx response throws a typed exception carrying the API's error code, the HTTP status and
the request id:

```php
use LifeAstroApi\Exception\LifeAstroException;
use LifeAstroApi\Exception\PaymentRequiredException;
use LifeAstroApi\Exception\RateLimitException;

try {
    $client->chart->planets(...$arjun);
} catch (PaymentRequiredException $e) {
    // 402: out of credits
} catch (RateLimitException $e) {
    echo 'retry after ', $e->getRetryAfter(), " s\n";
} catch (LifeAstroException $e) {
    echo $e->getErrorCode(), ' ', $e->getStatus(), ' ', $e->getMessage(), ' ', $e->getRequestId();
}
```

| Exception (`LifeAstroApi\Exception\`) | When |
|-----------|------|
| `BadRequestException` | 400: a parameter is missing, malformed or out of range |
| `AuthenticationException` | 401: missing or invalid key |
| `PaymentRequiredException` | 402: out of credits |
| `PermissionException` | 403: key not allowed (plan, IP allow-list, suspended) |
| `NotFoundException` | 404: unknown path or id |
| `RateLimitException` | 429: rate limit or daily quota; `getRetryAfter()` in seconds |
| `ServerException` | 5xx |
| `ConnectionException` | network failure, no response (`getStatus()` is 0) |
| `TimeoutException` | the request timed out (a `ConnectionException`) |

All of them extend `LifeAstroException` (a `RuntimeException`); `getCode()` is the HTTP status
too, and `getBody()` the decoded response body. Requests are retried automatically (`maxRetries`,
default 2) on 408, 425, 429, 500, 502, 503, 504, network errors and timeouts, with exponential
backoff (0.5 s, 1 s, 2 s, ... up to 8 s) or the server's `Retry-After` (up to 30 s).

## Responses

Methods return the decoded JSON body as an array (`json_decode($body, true)`). The API has hundreds
of response shapes and they grow over time, so the SDK does not wrap them in classes. If the API
ever wraps a response in a `{ data, meta }` envelope, the SDK returns `data`.

## Per-call options

```php
use LifeAstroApi\RequestOptions;

$client->chart->planets(...$arjun, requestOptions: new RequestOptions(timeout: 5));
$client->request('/v1/panchang/tithi', [...], new RequestOptions(raw: true));
```

`timeout` overrides the client timeout for one call; `raw` returns the body exactly as received.

## Escape hatch

Call any `/v1/*` endpoint by its path and wire parameter names, for example one that is newer
than your SDK version:

```php
$tithi = $client->request('/v1/panchang/tithi', [
    'lat' => 28.6139, 'lon' => 77.209, 'tz' => 'Asia/Kolkata', 'date' => '2027-01-15',
]);
// $client->get(...) is an alias
```

## Your own HTTP client (PSR-18)

The built-in transport needs only `ext-curl`. To send requests through a client you already use
(for its proxy, logging or middleware), pass any [PSR-18](https://www.php-fig.org/psr/psr-18/)
client as `transport` and a PSR-17 request factory as `requestFactory`:

```php
// Guzzle 7: composer require guzzlehttp/guzzle
$client = new LifeAstro(
    transport: new \GuzzleHttp\Client(['timeout' => 30]),
    requestFactory: new \GuzzleHttp\Psr7\HttpFactory(),
);

// Symfony: composer require symfony/http-client nyholm/psr7
// Psr18Client is its own request factory.
$client = new LifeAstro(transport: new \Symfony\Component\HttpClient\Psr18Client());
```

With a PSR-18 client, its own timeout setting applies (PSR-18 has no per-request timeout);
retries, `Retry-After` and error mapping work the same. You can also implement
`LifeAstroApi\Http\Transport` (one `get()` method) for anything else.

## PDF reports

The `reports->*` methods return the report JSON. Branded PDF rendering of those reports, which
the Node SDK offers in `lifeastroapi/pdf`, is planned for a later version of this package.

## Development

```bash
composer install
composer generate      # regenerate src/Resources from the Node SDK's manifest (needs node)
composer goldens       # record the Node SDK's request for every method (needs node)
composer test          # PHPUnit: mock transport, a local cURL server, Node parity for all methods
composer analyse       # PHPStan, level max, checked against PHP 8.1
composer cs            # PSR-12 (composer cs-fix to fix)
```

`tool/generate.mjs` reads the endpoint manifest of the Node SDK (`../lifeastroapi-node`, through
its `scripts/manifest.mjs`), so the SDKs always have the same methods, names and `lang` rules.
`--costs-url <base url>` refreshes the credit costs (`tool/costs.json`) from
`GET /v1/meta/endpoints`. After regenerating, run `composer goldens` and `composer test`:
`tests/NodeParityTest.php` checks that every method sends the same path and query string as the
Node SDK.

`tool/live-sweep.php` calls every method once against a real server and lists the calls the API
rejects. It spends credits (one call per endpoint) and has no default URL:

```bash
LIFEASTRO_API_KEY=dv_test_... php tool/live-sweep.php --base-url=http://localhost:8080
```

## Links

- Documentation: https://lifeastroapi.com/docs/
- API reference: https://lifeastroapi.com/docs/reference/
- Node.js / TypeScript SDK: `npm install lifeastroapi`

## License

MIT
