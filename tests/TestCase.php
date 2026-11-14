<?php

declare(strict_types=1);

namespace LifeAstroApi\Tests;

use LifeAstroApi\LifeAstro;
use LifeAstroApi\Tests\Support\MockTransport;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /** Arjun Mehta, the neutral sample person. */
    protected const BIRTH = [
        'date' => '1990-01-15',
        'time' => '10:30',
        'tz' => 'Asia/Kolkata',
        'lat' => 19.076,
        'lon' => 72.8777,
    ];

    protected const GIRL = [
        'date' => '1992-03-20',
        'time' => '08:15',
        'tz' => 'Asia/Kolkata',
        'lat' => 28.6139,
        'lon' => 77.209,
    ];

    private string|false $savedEnvKey = false;

    protected function setUp(): void
    {
        // A key in the developer's environment must not leak into the tests.
        $this->savedEnvKey = getenv(LifeAstro::API_KEY_ENV);
        putenv(LifeAstro::API_KEY_ENV);
        unset($_ENV[LifeAstro::API_KEY_ENV], $_SERVER[LifeAstro::API_KEY_ENV]);
    }

    protected function tearDown(): void
    {
        putenv(LifeAstro::API_KEY_ENV . ($this->savedEnvKey === false ? '' : '=' . $this->savedEnvKey));
    }

    /** A client on a mock transport, without retries unless asked. */
    protected static function client(MockTransport $transport, int $maxRetries = 0, float $timeout = 30.0): LifeAstro
    {
        return new LifeAstro(
            apiKey: 'dv_live_test',
            baseUrl: 'https://api.test',
            timeout: $timeout,
            maxRetries: $maxRetries,
            transport: $transport,
        );
    }
}
