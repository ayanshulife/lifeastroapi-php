<?php

declare(strict_types=1);

namespace LifeAstroApi\Tests\Pdf;

use LifeAstroApi\Pdf\Branding;
use LifeAstroApi\Pdf\Reports;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Every report renderer gives byte-for-byte the HTML of the Node.js SDK's templates, for the inputs
 * of the Node SDK's own PDF tests and the real report responses in every locale, with and without
 * a branding override. The expected HTML is written by tool/pdf-goldens.mjs (`composer pdf-goldens`).
 *
 * One report: vendor/bin/phpunit --filter 'testRendersLikeNode.*kundliLite' tests/Pdf/PdfGoldenTest.php
 */
final class PdfGoldenTest extends TestCase
{
    private const DIR = __DIR__ . '/../fixtures/pdf';

    /** @return iterable<string, array{string, string, array<mixed>, array<string, mixed>|null}> */
    public static function cases(): iterable
    {
        $cases = json_decode((string) file_get_contents(self::DIR . '/cases.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($cases);
        foreach ($cases as $case) {
            self::assertIsArray($case);
            $id = $case['id'] ?? null;
            $fn = $case['fn'] ?? null;
            $data = $case['data'] ?? null;
            $branding = $case['branding'] ?? null;
            self::assertIsString($id);
            self::assertIsString($fn);
            self::assertIsArray($data);
            /** @var array<string, mixed>|null $branding */
            yield $id => [$id, $fn, $data, \is_array($branding) ? $branding : null];
        }
    }

    public function testEveryNodeRendererIsPorted(): void
    {
        $cases = json_decode((string) file_get_contents(self::DIR . '/cases.json'), true);
        self::assertIsArray($cases);
        $fns = [];
        foreach ($cases as $case) {
            if (\is_array($case) && \is_string($case['fn'] ?? null)) {
                $fns[$case['fn']] = $case['fn'];
            }
        }
        $fns = array_values($fns);
        sort($fns);
        $ours = Reports::RENDERERS;
        sort($ours);
        self::assertSame($fns, $ours);
    }

    /**
     * @param array<mixed> $data
     * @param array<string, mixed>|null $branding
     */
    #[DataProvider('cases')]
    public function testRendersLikeNode(string $id, string $fn, array $data, ?array $branding): void
    {
        $expected = (string) file_get_contents(self::DIR . "/golden/{$id}.html");
        $render = [Reports::class, $fn];
        self::assertTrue(\is_callable($render), "Reports::{$fn} is missing");
        $html = $render($data, $branding === null ? null : Branding::fromArray($branding));
        self::assertIsString($html);
        if ($html !== $expected) {
            self::fail(self::firstDifference($id, $expected, $html));
        }
        $this->addToAssertionCount(1);
    }

    /** A readable message around the first byte that differs. */
    private static function firstDifference(string $id, string $expected, string $actual): string
    {
        $max = min(\strlen($expected), \strlen($actual));
        $at = 0;
        while ($at < $max && $expected[$at] === $actual[$at]) {
            $at++;
        }
        $line = substr_count($expected, "\n", 0, $at) + 1;
        $from = max(0, $at - 150);

        return sprintf(
            "%s differs from Node at byte %d (line %d).\n--- expected (Node) ---\n%s\n--- actual (PHP) ---\n%s\n",
            $id,
            $at,
            $line,
            mb_strcut($expected, $from, 400),
            mb_strcut($actual, $from, 400),
        );
    }
}
