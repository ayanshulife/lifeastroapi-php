<?php

/**
 * Live API sweep: calls EVERY SDK method once against a real API and lists each one the API
 * rejects. This checks that the manifest's paths and parameter names match what the server
 * accepts; unit tests with a mock transport cannot see that. A port of the Node SDK's
 * scripts/live-sweep.mjs.
 *
 *     LIFEASTRO_API_KEY=dv_test_... php tool/live-sweep.php --base-url=http://127.0.0.1:8080
 *
 * Spends credits: one request per endpoint. There is no default base URL, so a sweep never
 * reaches production by accident; pass it explicitly.
 *
 * Options (environment variable in brackets):
 *     --base-url=URL    API base URL [LIFEASTRO_API_URL]
 *     --skip=LIST       comma-separated path prefixes to leave out [LIFEASTRO_LIVE_SKIP]. Default
 *                       "/v1/reports/" (composed reports cost 500-8000 credits each). Pass
 *                       --skip= to include them.
 *     --only=LIST       comma-separated substrings; only matching paths run [LIFEASTRO_LIVE_ONLY]
 *     --lang=CODE       language sent to every method that takes `lang` [LIFEASTRO_LIVE_LANG]
 *     --dump=DIR        write each response to DIR/<group.method>.json [LIFEASTRO_LIVE_DUMP]
 *     --delay=SECONDS   pause between calls, default 0.45 (per-minute rate limit)
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use LifeAstroApi\Exception\LifeAstroException;
use LifeAstroApi\LifeAstro;
use LifeAstroApi\Tool\Samples;

$opts = getopt('', ['base-url:', 'skip::', 'only:', 'lang:', 'dump:', 'delay:', 'help']);
if ($opts === false || isset($opts['help'])) {
    fwrite(STDOUT, "Usage: php tool/live-sweep.php --base-url=URL [--skip=LIST] [--only=LIST] [--lang=CODE]"
        . " [--dump=DIR] [--delay=SECONDS]\n");
    exit($opts === false ? 2 : 0);
}

$option = static function (string $name, string $env, ?string $default = null) use ($opts): ?string {
    if (\array_key_exists($name, $opts)) {
        $value = $opts[$name];

        return \is_string($value) ? $value : '';
    }
    $fromEnv = getenv($env);

    return \is_string($fromEnv) ? $fromEnv : $default;
};
$split = static fn (?string $value): array => array_values(array_filter(
    explode(',', $value ?? ''),
    static fn (string $s): bool => $s !== '',
));

$apiKey = getenv('LIFEASTRO_API_KEY');
$baseUrl = $option('base-url', 'LIFEASTRO_API_URL');
if (!\is_string($apiKey) || $apiKey === '') {
    fwrite(STDERR, "LIFEASTRO_API_KEY is required.\n");
    exit(2);
}
if ($baseUrl === null || $baseUrl === '') {
    fwrite(STDERR, "--base-url (or LIFEASTRO_API_URL) is required.\n");
    exit(2);
}
$skip = $split($option('skip', 'LIFEASTRO_LIVE_SKIP', '/v1/reports/'));
$only = $split($option('only', 'LIFEASTRO_LIVE_ONLY'));
$lang = $option('lang', 'LIFEASTRO_LIVE_LANG');
$dump = $option('dump', 'LIFEASTRO_LIVE_DUMP');
$delay = (float) ($option('delay', 'LIFEASTRO_LIVE_DELAY', '0.45') ?? '0.45');
if ($dump !== null && $dump !== '' && !is_dir($dump) && !mkdir($dump, 0777, true)) {
    fwrite(STDERR, "Cannot create {$dump}\n");
    exit(2);
}

// A retried failure is still a failure worth seeing: maxRetries 0.
$client = new LifeAstro(apiKey: $apiKey, baseUrl: $baseUrl, timeout: 90, maxRetries: 0);

$failures = [];
$called = 0;
foreach (Samples::entries($skip, $only) as $entry) {
    [$args, $missing] = Samples::arguments($entry);
    if ($missing !== []) {
        $failures[] = "FAIL {$entry['key']}  ({$entry['path']})\n     no sample for " . implode(', ', $missing);
        continue;
    }
    if ($lang !== null && $lang !== '' && Samples::hasParam($entry, 'lang')) {
        $args['lang'] = $lang;
    }
    $called++;
    try {
        $result = Samples::call($client, $entry, $args);
        if ($dump !== null && $dump !== '') {
            $json = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            file_put_contents("{$dump}/{$entry['key']}.json", $json . "\n");
        }
    } catch (LifeAstroException $e) {
        $failures[] = sprintf(
            "FAIL %s  (%s)\n     %d %s %s\n     input=%s",
            $entry['key'],
            $entry['path'],
            $e->getStatus(),
            $e->getErrorCode(),
            $e->getMessage(),
            Samples::describe($args),
        );
    } catch (\Throwable $e) {
        $failures[] = sprintf("FAIL %s  (%s)\n     %s: %s", $entry['key'], $entry['path'], $e::class, $e->getMessage());
    }
    if ($delay > 0) {
        usleep((int) round($delay * 1_000_000));
    }
}

foreach ($failures as $line) {
    fwrite(STDOUT, $line . "\n");
}
fwrite(STDOUT, sprintf("\nlive sweep: %d endpoints called, %d failed\n", $called, \count($failures)));
exit($failures === [] ? 0 : 1);
