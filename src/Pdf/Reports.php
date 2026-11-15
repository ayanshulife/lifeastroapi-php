<?php

declare(strict_types=1);

namespace LifeAstroApi\Pdf;

use LifeAstroApi\Pdf\Engine\ChromePdfEngine;
use LifeAstroApi\Pdf\Engine\PdfEngine;
use LifeAstroApi\Pdf\Engine\PdfException;
use LifeAstroApi\Pdf\Engine\PdfMargin;
use LifeAstroApi\Pdf\Engine\PdfOptions;
use LifeAstroApi\Pdf\Engine\PdfOutline;
use LifeAstroApi\Pdf\Templates\BrihadSections;

/**
 * Report JSON (from `$client->reports->...`) to branded HTML and PDF, made on your own server:
 * nothing is sent to LifeAstroAPI for this.
 *
 * - `render*Html($data, $branding)`: a complete, self-contained HTML document. Pure PHP, no
 *   browser needed. A port of the Node.js SDK's `lifeastroapi/pdf-html` with byte-identical output.
 * - `*Pdf($data, $branding, $options, $engine)`: PDF bytes, printed by a {@see PdfEngine}: a
 *   headless Chrome on your server ({@see ChromePdfEngine}, the default) or a Gotenberg service.
 *
 * ```php
 * use LifeAstroApi\Pdf\Reports;
 *
 * $report = $client->reports->kundliLite(...$arjun, lang: 'hi');
 * $html = Reports::renderKundliLiteHtml($report, ['companyName' => 'Acme Astro']);
 * $pdf = Reports::kundliLitePdf($report, ['companyName' => 'Acme Astro']);
 * file_put_contents('kundli.pdf', $pdf);
 * ```
 */
final class Reports
{
    /** Every HTML renderer by its Node.js SDK name. */
    public const RENDERERS = [
        'renderKundliLiteHtml',
        'renderKundliDetailedHtml',
        'renderKundliBrihadHtml',
        'renderMatchMakingHtml',
        'renderDashaAnalysisHtml',
        'renderNumerologyHtml',
        'renderSadeSatiHtml',
        'renderVarshaphalHtml',
        'renderHoroscopeHtml',
        'renderMangalDoshaHtml',
    ];

    private function __construct()
    {
    }

    /**
     * Vedic birth chart (lite) report JSON (`$client->reports->kundliLite(...)`) as a complete, branded HTML
     * document.
     *
     * @param array<mixed> $data The report JSON, as the SDK returns it.
     * @param Branding|array<string, mixed>|null $branding Overrides for the report's own branding.
     */
    public static function renderKundliLiteHtml(array $data, Branding|array|null $branding = null): string
    {
        return Templates\KundliLite::render($data, Branding::of($branding));
    }

    /**
     * Detailed kundli report JSON (`$client->reports->kundliDetailed(...)`) as a complete, branded HTML
     * document.
     *
     * @param array<mixed> $data The report JSON, as the SDK returns it.
     * @param Branding|array<string, mixed>|null $branding Overrides for the report's own branding.
     */
    public static function renderKundliDetailedHtml(array $data, Branding|array|null $branding = null): string
    {
        return Templates\KundliDetailed::render($data, Branding::of($branding));
    }

    /**
     * Brihad (comprehensive) kundli report JSON (`$client->reports->kundliBrihad(...)`) as a complete,
     * branded HTML document. For a PDF with the running header, table of contents and bookmarks, use
     * kundliBrihadPdf().
     *
     * @param array<mixed> $data The report JSON, as the SDK returns it.
     * @param Branding|array<string, mixed>|null $branding Overrides for the report's own branding.
     */
    public static function renderKundliBrihadHtml(array $data, Branding|array|null $branding = null): string
    {
        return Templates\KundliBrihad::render($data, Branding::of($branding));
    }

    /**
     * Match making (kundli milan) report JSON (`$client->reports->matchMaking(...)`) as a complete, branded HTML
     * document.
     *
     * @param array<mixed> $data The report JSON, as the SDK returns it.
     * @param Branding|array<string, mixed>|null $branding Overrides for the report's own branding.
     */
    public static function renderMatchMakingHtml(array $data, Branding|array|null $branding = null): string
    {
        return Templates\MatchMaking::render($data, Branding::of($branding));
    }

    /**
     * Dasha analysis report JSON (`$client->reports->dashaAnalysis(...)`) as a complete, branded HTML
     * document.
     *
     * @param array<mixed> $data The report JSON, as the SDK returns it.
     * @param Branding|array<string, mixed>|null $branding Overrides for the report's own branding.
     */
    public static function renderDashaAnalysisHtml(array $data, Branding|array|null $branding = null): string
    {
        return Templates\DashaAnalysis::render($data, Branding::of($branding));
    }

    /**
     * Numerology report JSON (`$client->reports->numerology(...)`) as a complete, branded HTML
     * document.
     *
     * @param array<mixed> $data The report JSON, as the SDK returns it.
     * @param Branding|array<string, mixed>|null $branding Overrides for the report's own branding.
     */
    public static function renderNumerologyHtml(array $data, Branding|array|null $branding = null): string
    {
        return Templates\Numerology::render($data, Branding::of($branding));
    }

    /**
     * Sade Sati report JSON (`$client->reports->sadeSati(...)`) as a complete, branded HTML
     * document.
     *
     * @param array<mixed> $data The report JSON, as the SDK returns it.
     * @param Branding|array<string, mixed>|null $branding Overrides for the report's own branding.
     */
    public static function renderSadeSatiHtml(array $data, Branding|array|null $branding = null): string
    {
        return Templates\SadeSati::render($data, Branding::of($branding));
    }

    /**
     * Varshaphal (annual chart) report JSON (`$client->reports->varshaphal(...)`) as a complete, branded HTML
     * document.
     *
     * @param array<mixed> $data The report JSON, as the SDK returns it.
     * @param Branding|array<string, mixed>|null $branding Overrides for the report's own branding.
     */
    public static function renderVarshaphalHtml(array $data, Branding|array|null $branding = null): string
    {
        return Templates\Varshaphal::render($data, Branding::of($branding));
    }

    /**
     * Horoscope report JSON (`$client->reports->horoscopeDaily(...)`, `horoscopeWeekly`,
     * `horoscopeMonthly`) as a complete, branded HTML document.
     *
     * @param array<mixed> $data The report JSON, as the SDK returns it.
     * @param Branding|array<string, mixed>|null $branding Overrides for the report's own branding.
     */
    public static function renderHoroscopeHtml(array $data, Branding|array|null $branding = null): string
    {
        return Templates\Horoscope::render($data, Branding::of($branding));
    }

    /**
     * Mangal Dosha (Manglik) report JSON (`$client->reports->mangalDosha(...)`) as a complete, branded HTML
     * document.
     *
     * @param array<mixed> $data The report JSON, as the SDK returns it.
     * @param Branding|array<string, mixed>|null $branding Overrides for the report's own branding.
     */
    public static function renderMangalDoshaHtml(array $data, Branding|array|null $branding = null): string
    {
        return Templates\MangalDosha::render($data, Branding::of($branding));
    }

    /**
     * Print a complete HTML document (from any `render*Html()` method) to PDF bytes.
     *
     * With no `$engine`, a headless Chrome / Chromium / Edge is launched for this one call and
     * closed afterwards. To print many PDFs, launch one {@see ChromePdfEngine} and pass it to every
     * call; without a browser on the server, pass a {@see Engine\GotenbergPdfEngine}.
     *
     * @throws PdfException
     */
    public static function htmlToPdf(string $html, ?PdfOptions $options = null, ?PdfEngine $engine = null): string
    {
        return self::withEngine($engine, static fn (PdfEngine $e): string => $e->htmlToPdf($html, $options));
    }

    /**
     * The report as PDF bytes ({@see renderKundliLiteHtml()} printed by an engine).
     *
     * @param array<mixed> $data The report JSON, as the SDK returns it.
     * @param Branding|array<string, mixed>|null $branding Overrides for the report's own branding.
     * @param PdfEngine|null $engine Default: a headless Chrome launched for this one call.
     *
     * @throws PdfException
     */
    public static function kundliLitePdf(
        array $data,
        Branding|array|null $branding = null,
        ?PdfOptions $options = null,
        ?PdfEngine $engine = null,
    ): string {
        return self::htmlToPdf(self::renderKundliLiteHtml($data, $branding), $options, $engine);
    }

    /**
     * The report as PDF bytes ({@see renderKundliDetailedHtml()} printed by an engine).
     *
     * @param array<mixed> $data The report JSON, as the SDK returns it.
     * @param Branding|array<string, mixed>|null $branding Overrides for the report's own branding.
     * @param PdfEngine|null $engine Default: a headless Chrome launched for this one call.
     *
     * @throws PdfException
     */
    public static function kundliDetailedPdf(
        array $data,
        Branding|array|null $branding = null,
        ?PdfOptions $options = null,
        ?PdfEngine $engine = null,
    ): string {
        return self::htmlToPdf(self::renderKundliDetailedHtml($data, $branding), $options, $engine);
    }

    /**
     * The report as PDF bytes ({@see renderMatchMakingHtml()} printed by an engine).
     *
     * @param array<mixed> $data The report JSON, as the SDK returns it.
     * @param Branding|array<string, mixed>|null $branding Overrides for the report's own branding.
     * @param PdfEngine|null $engine Default: a headless Chrome launched for this one call.
     *
     * @throws PdfException
     */
    public static function matchMakingPdf(
        array $data,
        Branding|array|null $branding = null,
        ?PdfOptions $options = null,
        ?PdfEngine $engine = null,
    ): string {
        return self::htmlToPdf(self::renderMatchMakingHtml($data, $branding), $options, $engine);
    }

    /**
     * The report as PDF bytes ({@see renderDashaAnalysisHtml()} printed by an engine).
     *
     * @param array<mixed> $data The report JSON, as the SDK returns it.
     * @param Branding|array<string, mixed>|null $branding Overrides for the report's own branding.
     * @param PdfEngine|null $engine Default: a headless Chrome launched for this one call.
     *
     * @throws PdfException
     */
    public static function dashaAnalysisPdf(
        array $data,
        Branding|array|null $branding = null,
        ?PdfOptions $options = null,
        ?PdfEngine $engine = null,
    ): string {
        return self::htmlToPdf(self::renderDashaAnalysisHtml($data, $branding), $options, $engine);
    }

    /**
     * The report as PDF bytes ({@see renderNumerologyHtml()} printed by an engine).
     *
     * @param array<mixed> $data The report JSON, as the SDK returns it.
     * @param Branding|array<string, mixed>|null $branding Overrides for the report's own branding.
     * @param PdfEngine|null $engine Default: a headless Chrome launched for this one call.
     *
     * @throws PdfException
     */
    public static function numerologyPdf(
        array $data,
        Branding|array|null $branding = null,
        ?PdfOptions $options = null,
        ?PdfEngine $engine = null,
    ): string {
        return self::htmlToPdf(self::renderNumerologyHtml($data, $branding), $options, $engine);
    }

    /**
     * The report as PDF bytes ({@see renderSadeSatiHtml()} printed by an engine).
     *
     * @param array<mixed> $data The report JSON, as the SDK returns it.
     * @param Branding|array<string, mixed>|null $branding Overrides for the report's own branding.
     * @param PdfEngine|null $engine Default: a headless Chrome launched for this one call.
     *
     * @throws PdfException
     */
    public static function sadeSatiPdf(
        array $data,
        Branding|array|null $branding = null,
        ?PdfOptions $options = null,
        ?PdfEngine $engine = null,
    ): string {
        return self::htmlToPdf(self::renderSadeSatiHtml($data, $branding), $options, $engine);
    }

    /**
     * The report as PDF bytes ({@see renderVarshaphalHtml()} printed by an engine).
     *
     * @param array<mixed> $data The report JSON, as the SDK returns it.
     * @param Branding|array<string, mixed>|null $branding Overrides for the report's own branding.
     * @param PdfEngine|null $engine Default: a headless Chrome launched for this one call.
     *
     * @throws PdfException
     */
    public static function varshaphalPdf(
        array $data,
        Branding|array|null $branding = null,
        ?PdfOptions $options = null,
        ?PdfEngine $engine = null,
    ): string {
        return self::htmlToPdf(self::renderVarshaphalHtml($data, $branding), $options, $engine);
    }

    /**
     * The report as PDF bytes ({@see renderHoroscopeHtml()} printed by an engine).
     *
     * @param array<mixed> $data The report JSON, as the SDK returns it.
     * @param Branding|array<string, mixed>|null $branding Overrides for the report's own branding.
     * @param PdfEngine|null $engine Default: a headless Chrome launched for this one call.
     *
     * @throws PdfException
     */
    public static function horoscopePdf(
        array $data,
        Branding|array|null $branding = null,
        ?PdfOptions $options = null,
        ?PdfEngine $engine = null,
    ): string {
        return self::htmlToPdf(self::renderHoroscopeHtml($data, $branding), $options, $engine);
    }

    /**
     * The report as PDF bytes ({@see renderMangalDoshaHtml()} printed by an engine).
     *
     * @param array<mixed> $data The report JSON, as the SDK returns it.
     * @param Branding|array<string, mixed>|null $branding Overrides for the report's own branding.
     * @param PdfEngine|null $engine Default: a headless Chrome launched for this one call.
     *
     * @throws PdfException
     */
    public static function mangalDoshaPdf(
        array $data,
        Branding|array|null $branding = null,
        ?PdfOptions $options = null,
        ?PdfEngine $engine = null,
    ): string {
        return self::htmlToPdf(self::renderMangalDoshaHtml($data, $branding), $options, $engine);
    }

    /**
     * The Brihad kundli as PDF bytes, with a running header and footer in the page margin, a table
     * of contents with the exact page of every section (read from a first print) and PDF bookmarks.
     * It prints twice and is long (well over 100 pages): run it in a background job.
     *
     * @param array<mixed> $data The report JSON from `$client->reports->kundliBrihad(...)`.
     * @param Branding|array<string, mixed>|null $branding Overrides for the report's own branding.
     * @param PdfEngine|null $engine Default: a headless Chrome launched for this one call.
     *
     * @throws PdfException
     */
    public static function kundliBrihadPdf(
        array $data,
        Branding|array|null $branding = null,
        ?PdfOptions $options = null,
        ?PdfEngine $engine = null,
    ): string {
        $branding = Branding::of($branding);
        $raw = self::rawBranding($data['Branding'] ?? null, $branding);
        $m = BrihadSections::BRIHAD_PDF_MARGIN;
        $pdfOptions = ($options ?? new PdfOptions())->withHeaderFooter(
            BrihadSections::brihadHeaderTemplate($raw),
            BrihadSections::brihadFooterTemplate($raw),
            new PdfMargin($m['top'], $m['bottom'], $m['left'], $m['right']),
        );
        $baseHtml = self::renderKundliBrihadHtml($data, $branding);
        $tagged = BrihadSections::tagBrihadSectionsForToc($baseHtml);

        $print = static function (PdfEngine $e) use ($data, $tagged, $baseHtml, $pdfOptions): string {
            $titles = $tagged['titles'];
            if ($titles === []) {
                return $e->htmlToPdf($baseHtml, $pdfOptions);
            }
            $locale = \is_string($data['Locale'] ?? null) ? $data['Locale'] : 'en';
            $heading = Locales::TOC_HEADING[$locale] ?? Locales::TOC_HEADING['en'];

            // Pass 1: a placeholder TOC with the right number of rows, and every section marker made
            // a link, so the PDF tells which page each section landed on.
            $placeholder = [];
            foreach ($titles as $i => $title) {
                $placeholder[] = ['title' => $title, 'page' => $i + 1];
            }
            $pass1 = $e->htmlToPdf(
                self::linkMarkers(self::insertTocAfterCover(
                    $tagged['html'],
                    BrihadSections::buildBrihadToc($placeholder, $heading),
                )),
                $pdfOptions,
            );

            $entries = [];
            try {
                $pages = PdfOutline::linkTargetPages($pass1);
                foreach ($titles as $i => $title) {
                    $page = $pages[self::MARKER_URL . $i] ?? null;
                    if ($page !== null) {
                        $entries[] = ['title' => $title, 'page' => $page];
                    }
                }
            } catch (PdfException) {
                $entries = [];
            }
            // No page numbers found: print without a TOC rather than with wrong ones.
            if ($entries === []) {
                return $e->htmlToPdf($baseHtml, $pdfOptions);
            }

            // Pass 2: the TOC with the real page numbers, then PDF bookmarks.
            $pdf = $e->htmlToPdf(
                self::insertTocAfterCover($tagged['html'], BrihadSections::buildBrihadToc($entries, $heading)),
                $pdfOptions,
            );
            $outline = [];
            foreach ($entries as $entry) {
                $isPart = preg_match(Locales::PART_PREFIX_RE, Js::trim($entry['title'])) === 1;
                $outline[] = ['title' => $entry['title'], 'page' => $entry['page'], 'level' => $isPart ? 1 : 2];
            }
            try {
                return PdfOutline::addOutline($pdf, $outline);
            } catch (PdfException) {
                return $pdf;
            }
        };

        return self::withEngine($engine, $print);
    }

    private const MARKER_URL = 'https://toc.lifeastroapi.invalid/T';

    private static function linkMarkers(string $html): string
    {
        return (string) preg_replace(
            '/<span style="color:#fff;font-size:1px;">⟦T([0-9]+)⟧<\/span>/u',
            '<span style="color:#fff;font-size:1px;"><a href="' . self::MARKER_URL
                . '$1" style="color:#fff;text-decoration:none;">⟦T$1⟧</a></span>',
            $html,
        );
    }

    private static function insertTocAfterCover(string $html, string $toc): string
    {
        $coverEnd = strpos($html, '</section>');
        if ($coverEnd === false) {
            return $toc . $html;
        }
        $at = $coverEnd + \strlen('</section>');

        return substr($html, 0, $at) . "\n" . $toc . "\n" . substr($html, $at);
    }

    /**
     * The report's PascalCase branding with the caller's fields applied, for the Brihad running
     * header and footer.
     *
     * @return array<string, mixed>
     */
    private static function rawBranding(mixed $raw, ?Branding $override): array
    {
        $out = Js::map($raw);
        foreach (
            [
                'CompanyName' => $override?->companyName,
                'LogoURL' => $override?->logoUrl,
                'PrimaryColor' => $override?->primaryColor,
                'SecondaryColor' => $override?->secondaryColor,
                'FooterText' => $override?->footerText,
                'WatermarkText' => $override?->watermarkText,
            ] as $key => $value
        ) {
            if ($value !== null && trim($value) !== '') {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    /**
     * @param \Closure(PdfEngine): string $body
     *
     * @throws PdfException
     */
    private static function withEngine(?PdfEngine $engine, \Closure $body): string
    {
        if ($engine !== null) {
            return $body($engine);
        }
        $own = ChromePdfEngine::launch();
        try {
            return $body($own);
        } finally {
            $own->close();
        }
    }
}
