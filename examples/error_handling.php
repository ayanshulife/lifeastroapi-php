<?php

/**
 * Error handling: every non-2xx response throws a typed exception with the API's error code,
 * the HTTP status and the request id.
 *
 *     LIFEASTRO_API_KEY=dv_live_... php examples/error_handling.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use LifeAstroApi\Exception\BadRequestException;
use LifeAstroApi\Exception\ConnectionException;
use LifeAstroApi\Exception\LifeAstroException;
use LifeAstroApi\Exception\PaymentRequiredException;
use LifeAstroApi\Exception\RateLimitException;
use LifeAstroApi\LifeAstro;
use LifeAstroApi\RequestOptions;

$client = new LifeAstro(maxRetries: 2, timeout: 20);

try {
    // An invalid date: the API answers 400.
    $client->chart->planets(date: '1990-13-45', time: '10:30', tz: 'Asia/Kolkata', lat: 19.076, lon: 72.8777);
} catch (BadRequestException $e) {
    printf("400 %s: %s (request %s)\n", $e->getErrorCode(), $e->getMessage(), $e->getRequestId() ?? '-');
} catch (PaymentRequiredException $e) {
    echo "Out of credits: top up in your LifeAstroAPI dashboard.\n";
} catch (RateLimitException $e) {
    printf("Rate limited; retry after %s s\n", $e->getRetryAfter() ?? '?');
} catch (ConnectionException $e) {
    // Also catches TimeoutException. getStatus() is 0: no response arrived.
    printf("Network problem (%s): %s\n", $e->getErrorCode(), $e->getMessage());
} catch (LifeAstroException $e) {
    printf("HTTP %d %s: %s\n", $e->getStatus(), $e->getErrorCode(), $e->getMessage());
}

// A shorter timeout for one call.
$day = $client->panchang->tithi(
    lat: 28.6139,
    lon: 77.209,
    tz: 'Asia/Kolkata',
    requestOptions: new RequestOptions(timeout: 5),
);
echo json_encode($day, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
