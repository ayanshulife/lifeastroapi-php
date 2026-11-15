<?php

declare(strict_types=1);

namespace LifeAstroApi\Tests\Pdf;

use LifeAstroApi\Pdf\Branding;
use LifeAstroApi\Pdf\Templates\BrihadSections;
use PHPUnit\Framework\TestCase;

/**
 * The Brihad PDF helpers (running header / footer, table of contents, section tagging) give the
 * same output as the Node.js SDK (tests/fixtures/pdf/brihad.json, from tool/pdf-goldens.mjs).
 */
final class PdfBrihadTest extends TestCase
{
    private const DIR = __DIR__ . '/../fixtures/pdf';

    /** The camelCase branding tool/pdf-goldens.mjs passes (BRANDING). */
    private const BRANDING = [
        'companyName' => 'Acme Astro',
        'logoUrl' => 'https://cdn.example/acme/logo.png',
        'primaryColor' => '#6b21a8',
        'secondaryColor' => '#9333ea',
        'footerText' => 'Acme Astro · confidential',
        'watermarkText' => 'SAMPLE',
    ];

    /** @return array<string, mixed> */
    private static function golden(): array
    {
        $g = json_decode((string) file_get_contents(self::DIR . '/brihad.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($g);

        /** @var array<string, mixed> $g */
        return $g;
    }

    /**
     * @return list<string>
     */
    private static function strings(mixed $v): array
    {
        self::assertIsArray($v);
        $out = [];
        foreach ($v as $s) {
            self::assertIsString($s);
            $out[] = $s;
        }

        return $out;
    }

    public function testRunningHeader(): void
    {
        $h = self::strings(self::golden()['header'] ?? null);
        self::assertSame($h[0], BrihadSections::brihadHeaderTemplate(null));
        // As in Node, a camelCase branding is read as the raw PascalCase one, so it gives the defaults.
        self::assertSame($h[1], BrihadSections::brihadHeaderTemplate(Branding::fromArray(self::BRANDING)));
        self::assertStringContainsString(
            '</span> Acme &amp; Co',
            BrihadSections::brihadHeaderTemplate(['CompanyName' => 'Acme & Co']),
        );
    }

    public function testRunningFooter(): void
    {
        $f = self::strings(self::golden()['footer'] ?? null);
        self::assertSame($f[0], BrihadSections::brihadFooterTemplate(null));
        self::assertSame($f[1], BrihadSections::brihadFooterTemplate(Branding::fromArray(self::BRANDING)));
        self::assertStringContainsString(
            '>Acme Astro · confidential</span>',
            BrihadSections::brihadFooterTemplate(['FooterText' => 'Acme Astro · confidential']),
        );
    }

    public function testTableOfContents(): void
    {
        $toc = self::golden()['toc'] ?? null;
        self::assertIsArray($toc);
        self::assertNotEmpty($toc);
        foreach ($toc as $t) {
            self::assertIsArray($t);
            self::assertIsString($t['heading'] ?? null);
            self::assertIsString($t['html'] ?? null);
            self::assertIsArray($t['entries'] ?? null);
            $entries = [];
            foreach ($t['entries'] as $e) {
                self::assertIsArray($e);
                self::assertIsString($e['title'] ?? null);
                self::assertIsInt($e['page'] ?? null);
                $entries[] = ['title' => $e['title'], 'page' => $e['page']];
            }
            self::assertSame($t['html'], BrihadSections::buildBrihadToc($entries, $t['heading']), $t['heading']);
        }
        self::assertSame('', BrihadSections::buildBrihadToc([], 'Table of Contents'));
    }

    public function testSectionTagging(): void
    {
        $tagged = self::golden()['tagged'] ?? null;
        self::assertIsArray($tagged);
        self::assertNotEmpty($tagged);
        foreach ($tagged as $t) {
            self::assertIsArray($t);
            $id = $t['id'] ?? null;
            self::assertIsString($id);
            $html = (string) file_get_contents(self::DIR . "/golden/{$id}.html");
            $r = BrihadSections::tagBrihadSectionsForToc($html);
            self::assertSame(self::strings($t['titles'] ?? null), $r['titles'], "{$id} titles");
            self::assertTrue($r['html'] === ($t['html'] ?? null), "{$id} tagged html");
        }
    }

    public function testTocMarkerPattern(): void
    {
        $text = 'Part 1 ⟦T0⟧⟦T1⟧ page text ⟦T12⟧';
        self::assertSame(3, preg_match_all(BrihadSections::TOC_MARKER_RE, $text, $m));
        self::assertSame(['0', '1', '12'], $m[1]);
        self::assertSame(
            ['top' => '26mm', 'bottom' => '16mm', 'left' => '0mm', 'right' => '0mm'],
            BrihadSections::BRIHAD_PDF_MARGIN,
        );
    }
}
