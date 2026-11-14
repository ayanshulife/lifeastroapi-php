<?php

declare(strict_types=1);

namespace LifeAstroApi\Tests;

use LifeAstroApi\Http\Query;
use LifeAstroApi\Lang;
use PHPUnit\Framework\Attributes\DataProvider;

/** Query encoding helpers, checked against what JavaScript produces. */
final class QueryTest extends TestCase
{
    /** @return iterable<string, array{float, string}> */
    public static function floats(): iterable
    {
        // Expected values are JavaScript's String(n).
        yield 'decimal' => [19.076, '19.076'];
        yield 'whole' => [5.0, '5'];
        yield 'negative' => [-72.8777, '-72.8777'];
        yield 'zero' => [0.0, '0'];
        yield 'negative zero' => [-0.0, '0'];
        yield 'binary noise' => [0.1 + 0.2, '0.30000000000000004'];
        yield 'small' => [0.000001, '0.000001'];
        yield 'smaller' => [0.0000001, '1e-7'];
        yield 'small with digits' => [0.00001234, '0.00001234'];
        yield 'large' => [123456789012345680000.0, '123456789012345680000'];
        yield 'huge' => [1.5e21, '1.5e+21'];
        yield 'huge whole' => [1e21, '1e+21'];
        yield 'tiny with digits' => [1.25e-10, '1.25e-10'];
    }

    #[DataProvider('floats')]
    public function testFormatsFloatsLikeJavaScript(float $value, string $expected): void
    {
        self::assertSame($expected, Query::formatFloat($value));
    }

    public function testToWire(): void
    {
        self::assertSame('true', Query::toWire(true));
        self::assertSame('false', Query::toWire(false));
        self::assertSame('42', Query::toWire(42));
        self::assertSame('gu', Query::toWire(Lang::Gu));
        self::assertSame('x', Query::toWire('x'));
        $this->expectException(\InvalidArgumentException::class);
        Query::toWire(['a']);
    }

    public function testEncodeSortsSkipsEmptyAndEncodesLikeUrlSearchParams(): void
    {
        self::assertSame('', Query::encode([]));
        self::assertSame('', Query::encode(['a' => null, 'b' => '']));
        self::assertSame(
            '?a=0&b=false&birth.lat=19.076&q=New+Delhi%2C+India&tz=%2B05%3A30&x=*-._%7E',
            Query::encode([
                'tz' => '+05:30',
                'x' => '*-._~',
                'q' => 'New Delhi, India',
                'birth.lat' => 19.076,
                'b' => false,
                'a' => 0,
            ]),
        );
        self::assertSame(
            '?name=%E0%A4%85%E0%A4%B0%E0%A5%8D%E0%A4%9C%E0%A5%81%E0%A4%A8',
            Query::encode(['name' => 'अर्जुन']),
        );
    }

    public function testPathEncodesLikeEncodeUriComponent(): void
    {
        self::assertSame("/v1/x/a%20b%2Fc!'()*", Query::path('/v1/x/{id}', ['id' => "a b/c!'()*"]));
        self::assertSame('/v1/numerology/5', Query::path('/v1/numerology/{n}', ['n' => 5]));
    }

    public function testTodayInResolvesTheZoneAndFallsBackToUtc(): void
    {
        $now = new \DateTimeImmutable('2026-11-14T20:00:00Z');
        self::assertSame('2026-11-15', Query::todayIn('Asia/Kolkata', $now));
        self::assertSame('2026-11-15', Query::todayIn('+05:30', $now));
        self::assertSame('2026-11-14', Query::todayIn('America/New_York', $now));
        self::assertSame('2026-11-14', Query::todayIn('Not/AZone', $now));
        self::assertSame('2026-11-14', Query::todayIn('5.5', $now));
        self::assertSame('2026-11-14', Query::todayIn(null, $now));
    }
}
