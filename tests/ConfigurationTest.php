<?php

declare(strict_types=1);

namespace LifeAstroApi\Tests;

use LifeAstroApi\Exception\LifeAstroException;
use LifeAstroApi\LifeAstro;
use LifeAstroApi\Tests\Support\MockTransport;

/** Constructor options (Node: "configuration"). */
final class ConfigurationTest extends TestCase
{
    public function testThrowsWhenNoApiKeyIsGiven(): void
    {
        try {
            new LifeAstro(transport: new MockTransport());
            self::fail('Expected an exception');
        } catch (LifeAstroException $e) {
            self::assertSame('missing_api_key', $e->getErrorCode());
            self::assertSame(0, $e->getStatus());
            self::assertStringContainsString('LIFEASTRO_API_KEY', $e->getMessage());
        }
    }

    public function testABlankKeyIsNoKey(): void
    {
        $this->expectException(LifeAstroException::class);
        new LifeAstro(apiKey: '  ', transport: new MockTransport());
    }

    public function testReadsTheKeyFromTheEnvironment(): void
    {
        putenv('LIFEASTRO_API_KEY=dv_live_from_env');
        $t = new MockTransport();
        (new LifeAstro(transport: $t))->meta->version();
        self::assertSame('Bearer dv_live_from_env', $t->lastHeaders()['Authorization']);
    }

    public function testReadsTheKeyFromEnvSuperglobal(): void
    {
        // Frameworks that load .env files fill $_ENV / $_SERVER without putenv().
        $_ENV['LIFEASTRO_API_KEY'] = 'dv_live_dotenv';
        $t = new MockTransport();
        (new LifeAstro(transport: $t))->meta->version();
        self::assertSame('Bearer dv_live_dotenv', $t->lastHeaders()['Authorization']);
    }

    public function testAnExplicitKeyWinsOverTheEnvironment(): void
    {
        putenv('LIFEASTRO_API_KEY=dv_live_from_env');
        $t = new MockTransport();
        (new LifeAstro(apiKey: 'dv_live_explicit', transport: $t))->meta->version();
        self::assertSame('Bearer dv_live_explicit', $t->lastHeaders()['Authorization']);
    }

    public function testThePositionalFirstArgumentIsTheKey(): void
    {
        $t = new MockTransport();
        (new LifeAstro('dv_live_positional', transport: $t))->meta->version();
        self::assertSame('Bearer dv_live_positional', $t->lastHeaders()['Authorization']);
    }

    public function testVersionMatchesTheChangelog(): void
    {
        $changelog = (string) file_get_contents(__DIR__ . '/../CHANGELOG.md');
        self::assertMatchesRegularExpression('/^## ' . preg_quote(LifeAstro::VERSION, '/') . ' /m', $changelog);
        preg_match('/^## (\d+\.\d+\.\d+) /m', $changelog, $first);
        self::assertSame(LifeAstro::VERSION, $first[1] ?? null, 'the newest CHANGELOG entry is VERSION');
    }

    public function testComposerJsonHasNoVersionAndTheRightName(): void
    {
        $composer = json_decode((string) file_get_contents(__DIR__ . '/../composer.json'), true);
        self::assertIsArray($composer);
        self::assertSame('lifeastroapi/lifeastroapi', $composer['name'] ?? null);
        self::assertArrayNotHasKey('version', $composer, 'Packagist reads the version from git tags');
    }
}
