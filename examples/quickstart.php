<?php

/**
 * Quick start: today's panchang in Gujarati, a birth chart and a kundli matching score.
 *
 *     composer install
 *     LIFEASTRO_API_KEY=dv_live_... php examples/quickstart.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use LifeAstroApi\Lang;
use LifeAstroApi\LifeAstro;

$client = new LifeAstro(); // reads LIFEASTRO_API_KEY; or new LifeAstro(apiKey: 'dv_live_...')

// Responses are decoded JSON arrays. This reads a nested value, or '' when it is missing.
/** @param array<mixed> $data */
$field = static function (array $data, string ...$keys): string {
    $value = $data;
    foreach ($keys as $key) {
        if (!is_array($value) || !array_key_exists($key, $value)) {
            return '';
        }
        $value = $value[$key];
    }

    return is_scalar($value) ? (string) $value : '';
};

// Today's panchang for Ahmedabad, in Gujarati.
$day = $client->panchang->basic(lat: 23.0225, lon: 72.5714, tz: 'Asia/Kolkata', lang: Lang::Gu);
echo $field($day, 'tithi', 'name'), ' ', $field($day, 'nakshatra', 'name'), ' ', $field($day, 'vara', 'weekday'), "\n";

// A Vedic birth chart (sample person: Arjun Mehta, born 1990-01-15 10:30 in Mumbai).
$arjun = ['date' => '1990-01-15', 'time' => '10:30', 'tz' => 'Asia/Kolkata', 'lat' => 19.076, 'lon' => 72.8777];

$chart = $client->chart->planets(...$arjun);
$planets = $chart['planets'] ?? [];
foreach (is_array($planets) ? $planets : [] as $planet) {
    if (is_array($planet)) {
        $name = $field($planet, 'name');
        printf("%-8s house %-2s %s\n", $name, $field($planet, 'house'), $field($planet, 'dms_in_sign'));
    }
}

// Kundli matching score.
$match = $client->milan->ashtakootaTotal(
    boy: $arjun,
    girl: ['date' => '1992-03-20', 'time' => '08:15', 'tz' => 'Asia/Kolkata', 'lat' => 28.6139, 'lon' => 77.209],
);
echo 'Guna milan: ', $field($match, 'total'), ' / ', $field($match, 'max'), ' (', $field($match, 'verdict'), ")\n";
