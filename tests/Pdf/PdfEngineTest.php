<?php

declare(strict_types=1);

namespace LifeAstroApi\Tests\Pdf;

use LifeAstroApi\Pdf\Engine\ChromePdfEngine;
use LifeAstroApi\Pdf\Engine\PaperFormat;
use LifeAstroApi\Pdf\Engine\PdfEngine;
use LifeAstroApi\Pdf\Engine\PdfMargin;
use LifeAstroApi\Pdf\Engine\PdfOptions;
use LifeAstroApi\Pdf\Engine\PdfOutline;
use LifeAstroApi\Pdf\Reports;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * HTML -> PDF with a real headless Chrome. Skipped when no Chrome, Chromium or Edge is installed
 * (set CHROME_PATH to point at one). The Brihad two-pass print is in PdfBrihadPrintTest.
 */
#[Group('chrome')]
final class PdfEngineTest extends TestCase
{
    private static ?ChromePdfEngine $engine = null;

    public static function setUpBeforeClass(): void
    {
        if (ChromePdfEngine::findChromeExecutable() !== null) {
            self::$engine = ChromePdfEngine::launch();
        }
    }

    public static function tearDownAfterClass(): void
    {
        self::$engine?->close();
        self::$engine = null;
    }

    private static function engine(): PdfEngine
    {
        if (self::$engine === null) {
            self::markTestSkipped('No Chrome, Chromium or Edge found.');
        }

        return self::$engine;
    }

    /** @return array<mixed> */
    public static function example(string $fn, string $locale): array
    {
        $cases = json_decode((string) file_get_contents(__DIR__ . '/../fixtures/pdf/cases.json'), true);
        self::assertIsArray($cases);
        $found = null;
        foreach ($cases as $case) {
            if (
                \is_array($case) && ($case['fn'] ?? null) === $fn && ($case['branding'] ?? null) === null
                && \is_array($case['data'] ?? null) && ($case['data']['Locale'] ?? null) === $locale
            ) {
                $found = $case['data'];
            }
        }
        self::assertIsArray($found, "no {$fn} case in {$locale}");

        return $found;
    }

    public function testAReportPrintsToAValidPdf(): void
    {
        $pdf = Reports::kundliLitePdf(
            self::example('renderKundliLiteHtml', 'hi'),
            ['companyName' => 'Acme Astro'],
            engine: self::engine(),
        );
        self::assertStringStartsWith('%PDF-', $pdf);
        self::assertGreaterThanOrEqual(2, PdfOutline::pageCount($pdf));
        self::assertQpdfOk($pdf);
    }

    public function testHonoursPaperSizeMarginsAndLandscape(): void
    {
        $pdf = self::engine()->htmlToPdf(
            '<!doctype html><html><body><p>x</p></body></html>',
            new PdfOptions(format: PaperFormat::Letter, landscape: true, margin: new PdfMargin('10mm', '1cm')),
        );
        // Letter landscape: 792 x 612 points.
        self::assertStringContainsString('/MediaBox [0 0 792 612]', $pdf);
    }

    public function testLinkAnnotationsGiveThePageOfEachMarker(): void
    {
        $html = '<!doctype html><html><body>';
        foreach (['पहला', 'Second', 'తెలుగు'] as $i => $title) {
            $html .= '<section style="page-break-before:always"><h1>' . $title . '</h1>'
                . '<a href="https://toc.lifeastroapi.invalid/T' . $i . '">⟦T' . $i . '⟧</a></section>';
        }
        $pdf = self::engine()->htmlToPdf($html . '</body></html>');
        self::assertSame(
            [
                'https://toc.lifeastroapi.invalid/T0' => 1,
                'https://toc.lifeastroapi.invalid/T1' => 2,
                'https://toc.lifeastroapi.invalid/T2' => 3,
            ],
            PdfOutline::linkTargetPages($pdf),
        );

        $outlined = PdfOutline::addOutline($pdf, [
            ['title' => 'भाग 1: जन्म', 'page' => 1, 'level' => 1],
            ['title' => 'Planets (D1)', 'page' => 2, 'level' => 2],
            ['title' => 'తెలుగు', 'page' => 9, 'level' => 1],
        ]);
        self::assertSame(3, PdfOutline::pageCount($outlined));
        self::assertStringContainsString('/PageMode /UseOutlines', $outlined);
        self::assertQpdfOk($outlined);
    }

    public function testBrihadPrintsWithATableOfContentsAndBookmarks(): void
    {
        $data = self::example('renderKundliBrihadHtml', 'hi');
        $options = new PdfOptions(timeout: 120);
        $pdf = Reports::kundliBrihadPdf($data, ['companyName' => 'Acme Astro'], $options, self::engine());
        self::assertStringStartsWith('%PDF-', $pdf);
        self::assertStringContainsString('/Type /Outlines', $pdf);
        self::assertGreaterThan(10, PdfOutline::pageCount($pdf));
        // The markers used to find the pages are links only in the first print, not in the PDF.
        self::assertSame([], PdfOutline::linkTargetPages($pdf));
        self::assertQpdfOk($pdf);
    }

    public function testEvaluateRunsJavaScriptInThePage(): void
    {
        $engine = self::engine();
        self::assertInstanceOf(ChromePdfEngine::class, $engine);
        $js = 'document.getElementById("x").textContent + " " + (1 + 2)';
        self::assertSame('ok 3', $engine->evaluate('<p id="x">ok</p>', $js));
    }

    private static function assertQpdfOk(string $pdf): void
    {
        $qpdf = trim((string) shell_exec('command -v qpdf 2>/dev/null'));
        if ($qpdf === '') {
            return;
        }
        $file = tempnam(sys_get_temp_dir(), 'lifeastro-test-');
        self::assertIsString($file);
        file_put_contents($file, $pdf);
        exec(escapeshellarg($qpdf) . ' --check ' . escapeshellarg($file) . ' 2>&1', $out, $code);
        unlink($file);
        self::assertSame(0, $code, implode("\n", $out));
    }
}
