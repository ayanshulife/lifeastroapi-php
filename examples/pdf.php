<?php

/**
 * A branded kundli PDF in Hindi, made on this machine with a headless Chrome (or set
 * GOTENBERG_URL to print through a Gotenberg service instead).
 *
 *     LIFEASTRO_API_KEY=dv_live_... php examples/pdf.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use LifeAstroApi\Lang;
use LifeAstroApi\LifeAstro;
use LifeAstroApi\Pdf\Branding;
use LifeAstroApi\Pdf\Engine\ChromePdfEngine;
use LifeAstroApi\Pdf\Engine\GotenbergPdfEngine;
use LifeAstroApi\Pdf\Reports;

$client = new LifeAstro();

$report = $client->reports->kundliLite(
    date: '1990-01-15',
    time: '10:30',
    tz: 'Asia/Kolkata',
    lat: 19.076,
    lon: 72.8777,
    name: 'Arjun Mehta',
    place: 'Mumbai',
    lang: Lang::Hi,
);

$branding = new Branding(companyName: 'Acme Astro', primaryColor: '#6b21a8', footerText: 'Acme Astro');

$gotenberg = getenv('GOTENBERG_URL');
$engine = is_string($gotenberg) && $gotenberg !== ''
    ? new GotenbergPdfEngine($gotenberg)
    : ChromePdfEngine::launch();

try {
    $pdf = Reports::kundliLitePdf($report, $branding, engine: $engine);
} finally {
    $engine->close();
}

file_put_contents(__DIR__ . '/kundli-lite.pdf', $pdf);
echo 'Wrote examples/kundli-lite.pdf (', strlen($pdf), " bytes)\n";

// The HTML alone, without any engine (for example to show in a browser):
file_put_contents(__DIR__ . '/kundli-lite.html', Reports::renderKundliLiteHtml($report, $branding));
