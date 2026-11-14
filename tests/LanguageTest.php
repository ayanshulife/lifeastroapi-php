<?php

declare(strict_types=1);

namespace LifeAstroApi\Tests;

use LifeAstroApi\Lang;
use LifeAstroApi\LifeAstro;
use LifeAstroApi\Tests\Support\MockTransport;
use LifeAstroApi\Tool\Samples;
use PHPUnit\Framework\Attributes\DataProvider;

/** `lang` handling (Node: "localization"). */
final class LanguageTest extends TestCase
{
    public function testSendsBothLangAndLocale(): void
    {
        $t = new MockTransport();
        self::client($t)->narrative->lagna(...self::BIRTH, lang: Lang::Hi);
        $q = $t->lastQuery();
        self::assertSame('hi', $q['lang']);
        self::assertSame('hi', $q['locale']);
    }

    /** @return iterable<string, array{\Closure(LifeAstro): mixed}> */
    public static function vedicCalls(): iterable
    {
        yield 'panchang.basic' => [static fn (LifeAstro $c) => $c->panchang->basic(
            lat: 23.0225,
            lon: 72.5714,
            tz: 'Asia/Kolkata',
            date: '2027-01-15',
            lang: Lang::Gu,
        )];
        yield 'chart.planets' => [static fn (LifeAstro $c) => $c->chart->planets(...self::BIRTH, lang: Lang::Gu)];
        yield 'milan.ashtakoota' => [static fn (LifeAstro $c) => $c->milan->ashtakoota(
            boy: self::BIRTH,
            girl: self::GIRL,
            lang: Lang::Gu,
        )];
        yield 'festivals.month' => [static fn (LifeAstro $c) => $c->festivals->month(
            year: 2027,
            month: 10,
            lat: 22.5726,
            lon: 88.3639,
            lang: Lang::Gu,
        )];
        yield 'transit.positions' => [static fn (LifeAstro $c) => $c->transit->positions(lang: Lang::Gu)];
        yield 'remedies.forGraha' => [
            static fn (LifeAstro $c) => $c->remedies->forGraha(graha: 'saturn', lang: Lang::Gu),
        ];
    }

    /** @param \Closure(LifeAstro): mixed $call */
    #[DataProvider('vedicCalls')]
    public function testVedicMethodsSendTheLanguage(\Closure $call): void
    {
        $t = new MockTransport();
        $call(self::client($t));
        self::assertSame('gu', $t->lastQuery()['lang']);
        self::assertSame('gu', $t->lastQuery()['locale']);
    }

    public function testAcceptsEveryLangCaseAndThePlainCode(): void
    {
        $t = new MockTransport();
        $client = self::client($t);
        self::assertCount(10, Lang::cases());
        foreach (Lang::cases() as $lang) {
            $client->chart->planets(...self::BIRTH, lang: $lang);
            self::assertSame($lang->value, $t->lastQuery()['lang']);
            $client->chart->planets(...self::BIRTH, lang: $lang->value);
            self::assertSame($lang->value, $t->lastQuery()['lang']);
        }
        self::assertSame(
            ['en', 'hi', 'mr', 'gu', 'bn', 'te', 'ta', 'kn', 'ml', 'pa'],
            array_map(static fn (Lang $l): string => $l->value, Lang::cases()),
        );
        self::assertTrue(Lang::Bn->isComplete());
        self::assertFalse(Lang::Pa->isComplete());
    }

    public function testSendsNoLanguageWhenLangIsOmitted(): void
    {
        $t = new MockTransport();
        self::client($t)->chart->planets(...self::BIRTH);
        self::assertArrayNotHasKey('lang', $t->lastQuery());
        self::assertArrayNotHasKey('locale', $t->lastQuery());
    }

    public function testKeepsTheOlderLocaleArgumentAndLangWinsWhenBothAreGiven(): void
    {
        $t = new MockTransport();
        $client = self::client($t);
        $client->festivals->month(year: 2027, month: 10, locale: 'hi');
        self::assertSame('hi', $t->lastQuery()['locale']);
        self::assertArrayNotHasKey('lang', $t->lastQuery());

        $client->festivals->month(year: 2027, month: 10, locale: 'hi', lang: Lang::Mr);
        self::assertSame('mr', $t->lastQuery()['locale']);
        self::assertSame('mr', $t->lastQuery()['lang']);
    }

    public function testHoroscopeBySignSendsLangOnly(): void
    {
        $t = new MockTransport();
        self::client($t)->horoscope->daily(rashi: 'mesh', lang: Lang::Ta);
        self::assertSame(['lang' => 'ta', 'rashi' => 'mesh'], $t->lastQuery());
    }

    /** @return iterable<string, array{string}> */
    public static function englishOnlyMethods(): iterable
    {
        $english = ['western.natal.chart', 'western.synastry.score', 'tarot.daily', 'geo.search', 'meta.version'];
        foreach ($english as $key) {
            yield $key => [$key];
        }
        // Vedic data endpoints: times, numbers and ids only (manifest.mjs NO_LANG).
        $data = ['panchang.rahuKaal', 'panchang.sunriseSunset', 'chart.yogas', 'chart.doshas', 'transit.windows'];
        foreach ([...$data, 'milan.overlay'] as $key) {
            yield $key => [$key];
        }
    }

    #[DataProvider('englishOnlyMethods')]
    public function testMethodsWithNothingToTranslateTakeNoLang(string $key): void
    {
        [$group, $method] = self::split($key);
        $params = array_map(
            static fn (\ReflectionParameter $p): string => $p->getName(),
            (new \ReflectionMethod($group, $method))->getParameters(),
        );
        self::assertNotContains('lang', $params, $key);
    }

    public function testWesternNarrativeKeepsItsOwnLang(): void
    {
        $t = new MockTransport();
        self::client($t)->western->narrative->decan(sign: 'leo', decan: 2, lang: 'hi');
        self::assertSame('hi', $t->lastQuery()['lang']);
    }

    public function testOnlyTheNoLangListOfVedicMethodsLacksLang(): void
    {
        $english = ['/v1/western/', '/v1/tarot/', '/v1/geo/', '/v1/meta/'];
        $vedic = array_filter(
            Samples::manifest(),
            static fn (array $e): bool => !self::startsWithAny($e['path'], $english),
        );
        $without = array_filter($vedic, static fn (array $e): bool => !Samples::hasParam($e, 'lang'));
        // manifest.mjs NO_LANG: 25 Vedic endpoints that return only times, numbers or ids.
        self::assertCount(25, $without);
        self::assertSame(189, \count($vedic) - \count($without));

        foreach (Samples::manifest() as $entry) {
            $westernNarrative = str_starts_with($entry['path'], '/v1/western/narrative/');
            if (self::startsWithAny($entry['path'], $english) && !$westernNarrative) {
                self::assertFalse(Samples::hasParam($entry, 'lang'), $entry['key']);
            }
        }
    }

    /** @param list<string> $prefixes */
    private static function startsWithAny(string $path, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /** @return array{object, string} */
    private static function split(string $key): array
    {
        $parts = explode('.', $key);
        $method = (string) array_pop($parts);
        $target = self::client(new MockTransport());
        foreach ($parts as $part) {
            $target = $target->{$part};
            self::assertIsObject($target);
        }

        return [$target, $method];
    }
}
