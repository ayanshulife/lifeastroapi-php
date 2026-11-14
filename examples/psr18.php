<?php

/**
 * Your own PSR-18 HTTP client as the transport, here Guzzle 7:
 *
 *     composer require guzzlehttp/guzzle
 *     LIFEASTRO_API_KEY=dv_live_... php examples/psr18.php
 *
 * Any PSR-18 client works the same way. Symfony's Psr18Client is also a request factory, so it
 * needs no `requestFactory`:
 *
 *     new LifeAstro(transport: new \Symfony\Component\HttpClient\Psr18Client());
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use LifeAstroApi\Lang;
use LifeAstroApi\LifeAstro;

// With a PSR-18 client, the timeout and proxy settings are the client's own.
$guzzle = new Client(['timeout' => 30, 'connect_timeout' => 10]);

$client = new LifeAstro(
    transport: $guzzle,
    requestFactory: new HttpFactory(),
);

$festivals = $client->festivals->month(year: 2027, month: 10, lat: 22.5726, lon: 88.3639, lang: Lang::Bn);
echo json_encode($festivals, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
