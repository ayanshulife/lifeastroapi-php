<?php

declare(strict_types=1);

namespace LifeAstroApi\Tests\Pdf;

use LifeAstroApi\Pdf\Shared;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The text helpers (humanize, esc, cleanNarrative) give the same output as the Node.js SDK for
 * every string in the golden inputs (tests/fixtures/pdf/text.json, from tool/pdf-goldens.mjs).
 */
final class PdfTextTest extends TestCase
{
    /** @return iterable<string, array{array{s: string, humanize: string, esc: string, clean: array<string, string>}}> */
    public static function rows(): iterable
    {
        $rows = json_decode((string) file_get_contents(__DIR__ . '/../fixtures/pdf/text.json'), true);
        self::assertIsArray($rows);
        foreach ($rows as $i => $row) {
            /** @var array{s: string, humanize: string, esc: string, clean: array<string, string>} $row */
            yield '#' . $i . ' ' . mb_substr($row['s'], 0, 40) => [$row];
        }
    }

    /** @param array{s: string, humanize: string, esc: string, clean: array<string, string>} $row */
    #[DataProvider('rows')]
    public function testTextHelpersMatchNode(array $row): void
    {
        self::assertSame($row['humanize'], Shared::humanize($row['s']), 'humanize');
        self::assertSame($row['esc'], Shared::esc($row['s']), 'esc');
        foreach ($row['clean'] as $locale => $expected) {
            $actual = Shared::cleanNarrative($row['s'], (string) $locale);
            self::assertSame($expected, $actual, "cleanNarrative {$locale}");
        }
    }
}
