<?php

declare(strict_types=1);

namespace LifeAstroApi\Pdf\Templates;

use LifeAstroApi\Pdf\Branding;
use LifeAstroApi\Pdf\Js;
use LifeAstroApi\Pdf\Locales;
use LifeAstroApi\Pdf\Shared;

/**
 * Brihad Kundli: renderers for the deep classical sections and the corpus-backed narrative
 * chapters, the North-Indian chart glue, and the Brihad PDF's running header / footer and table of
 * contents. A port of the Node.js SDK's src/pdf/templates/kundliBrihadSections.ts (byte-identical
 * output, golden-tested).
 *
 * The deep sections read the report's snake_case keys; the narrative chapters read
 * `narrative_chapters`.
 *
 * @internal Used by {@see KundliBrihad} and the Brihad PDF wrapper.
 */
final class BrihadSections
{
    private const SIGN_INDEX = [
        'Aries' => 0,
        'Taurus' => 1,
        'Gemini' => 2,
        'Cancer' => 3,
        'Leo' => 4,
        'Virgo' => 5,
        'Libra' => 6,
        'Scorpio' => 7,
        'Sagittarius' => 8,
        'Capricorn' => 9,
        'Aquarius' => 10,
        'Pisces' => 11,
        'मेष' => 0,
        'वृषभ' => 1,
        'मिथुन' => 2,
        'कर्क' => 3,
        'सिंह' => 4,
        'कन्या' => 5,
        'तुला' => 6,
        'वृश्चिक' => 7,
        'धनु' => 8,
        'मकर' => 9,
        'कुम्भ' => 10,
        'कुंभ' => 10,
        'मीन' => 11,
        'तूळ' => 6,
        'മേടം' => 0,
        'ഇടവം' => 1,
        'മിഥുനം' => 2,
        'കർക്കടകം' => 3,
        'ചിങ്ങം' => 4,
        'കന്നി' => 5,
        'തുലാം' => 6,
        'വൃശ്ചികം' => 7,
        'ധനു' => 8,
        'മകരം' => 9,
        'കുംഭം' => 10,
        'മീനം' => 11,
        'మేషం' => 0,
        'వృషభం' => 1,
        'మిథునం' => 2,
        'కర్కాటకం' => 3,
        'సింహం' => 4,
        'కన్య' => 5,
        'తుల' => 6,
        'వృశ్చికం' => 7,
        'ధనుస్సు' => 8,
        'మకరం' => 9,
        'కుంభం' => 10,
        'మీనం' => 11,
        'மேஷம்' => 0,
        'ரிஷபம்' => 1,
        'மிதுனம்' => 2,
        'கடகம்' => 3,
        'சிம்மம்' => 4,
        'கன்னி' => 5,
        'துலாம்' => 6,
        'விருச்சிகம்' => 7,
        'தனுசு' => 8,
        'மகரம்' => 9,
        'கும்பம்' => 10,
        'மீனம்' => 11,
        'ಮೇಷ' => 0,
        'ವೃಷಭ' => 1,
        'ಮಿಥುನ' => 2,
        'ಕರ್ಕಾಟಕ' => 3,
        'ಸಿಂಹ' => 4,
        'ಕನ್ಯಾ' => 5,
        'ತುಲಾ' => 6,
        'ವೃಶ್ಚಿಕ' => 7,
        'ಧನು' => 8,
        'ಮಕರ' => 9,
        'ಕುಂಭ' => 10,
        'ಮೀನ' => 11,
        'મેષ' => 0,
        'વૃષભ' => 1,
        'મિથુન' => 2,
        'કર્ક' => 3,
        'સિંહ' => 4,
        'કન્યા' => 5,
        'તુલા' => 6,
        'વૃશ્ચિક' => 7,
        'ધનુ' => 8,
        'મકર' => 9,
        'કુંભ' => 10,
        'મીન' => 11,
        'মেষ' => 0,
        'বৃষ' => 1,
        'মিথুন' => 2,
        'কর্কট' => 3,
        'সিংহ' => 4,
        'কন্যা' => 5,
        'তুলা' => 6,
        'বৃশ্চিক' => 7,
        'ধনু' => 8,
        'মকর' => 9,
        'কুম্ভ' => 10,
        'মীন' => 11,
    ];

    private const PLANET_NAME_TO_EN = [
        'सूर्य' => 'Sun',
        'चंद्र' => 'Moon',
        'चन्द्र' => 'Moon',
        'मंगल' => 'Mars',
        'बुध' => 'Mercury',
        'गुरु' => 'Jupiter',
        'बृहस्पति' => 'Jupiter',
        'शुक्र' => 'Venus',
        'शनि' => 'Saturn',
        'राहु' => 'Rahu',
        'केतु' => 'Ketu',
    ];

    private const TOC_PALETTE = [
        '#c25e10',
        '#9e2b25',
        '#b8860b',
        '#8a3324',
        '#9c6f1e',
        '#b5471f',
        '#a8451a',
        '#8a5a00',
        '#c0631e',
    ];

    /** Page margins that reserve room for the running header + footer. */
    public const BRIHAD_PDF_MARGIN = [
        'top' => '26mm',
        'bottom' => '16mm',
        'left' => '0mm',
        'right' => '0mm',
    ];

    /** Finds a section marker token (⟦T<idx>⟧) in extracted PDF text (use preg_match_all). */
    public const TOC_MARKER_RE = '/⟦T([0-9]+)⟧/u';

    /** The non-ASCII characters of JS `\s`, UTF-8 encoded. */
    private const JS_SPACES = [
        "\u{A0}", "\u{1680}", "\u{2000}", "\u{2001}", "\u{2002}", "\u{2003}", "\u{2004}", "\u{2005}", "\u{2006}",
        "\u{2007}", "\u{2008}", "\u{2009}", "\u{200A}", "\u{2028}", "\u{2029}", "\u{202F}", "\u{205F}", "\u{3000}",
        "\u{FEFF}",
    ];

    private const SECTION_OPEN = '<section class="section';
    private const PART_BANNER_OPEN = '<div class="part-banner"><h2 class="part-h2">';

    private function __construct()
    {
    }

    /**
     * "Graha Sthiti" planet-cards grid: each graha as a card with its glyph, name, sign and
     * nakshatra. `$planets` is the report's `Planets` (name + placement.*).
     */
    public static function renderGrahaSthiti(mixed $planets, string $title): string
    {
        if (!Js::nonEmptyList($planets)) {
            return '';
        }
        $cards = [];
        foreach (Js::list($planets) as $raw) {
            $p = Js::map($raw);
            $glyph = PlanetGlyphs::planetGlyph($p['name_en'] ?? $p['name'] ?? null);
            if ($glyph === '') {
                continue;
            }
            $pl = Js::map($p['placement'] ?? null);
            $retro = Js::truthy($p['retro'] ?? null) ? ' <span style="color:#c0392b;">(R)</span>' : '';
            $cards[] = '<div class="graha-card">
  <div class="graha-glyph">' . $glyph . '</div>
  <div class="graha-info">
    <div class="graha-name">' . Shared::esc($p['name'] ?? null) . $retro . '</div>
    <div class="graha-pos">' . Shared::esc($pl['sign'] ?? null) . '</div>
    <div class="graha-nak">' . Shared::esc($pl['nakshatra'] ?? null) . '</div>
  </div>
</div>';
        }
        if ($cards === []) {
            return '';
        }

        return '<section class="section graha-sthiti-section">
<h2>' . Shared::esc($title) . '</h2>
<div class="graha-grid">' . implode("\n", $cards) . '</div>
</section>';
    }

    /**
     * Chart planets from `{name, house, retro}` rows, labelled for the locale.
     *
     * @param list<mixed> $rows
     *
     * @return list<array{label: string, house: mixed, retro: mixed, color: string|null}>
     */
    private static function chartPlanets(array $rows, string $locale): array
    {
        $labels = NorthIndianChart::planetLabelsFor($locale);
        $out = [];
        foreach ($rows as $raw) {
            $r = Js::map($raw);
            $name = Js::str($r['name'] ?? '');
            // Normalise to the English key whether the JSON gave EN, Devanagari or te/ta/kn/ml.
            $key = isset(NorthIndianChart::PLANET_LABELS_EN[$name])
                ? $name
                : (self::PLANET_NAME_TO_EN[$name] ?? Js::str(Js::or(Locales::southPlanetToEn($name), $name)));
            $lbl = $labels[$key] ?? null;
            if ($lbl === null || $lbl === '' || !Js::truthy($r['house'] ?? null)) {
                continue;
            }
            $out[] = [
                'label' => $lbl,
                'house' => $r['house'],
                'retro' => $r['retro'] ?? null,
                'color' => NorthIndianChart::PLANET_COLORS[$key] ?? null,
            ];
        }

        return $out;
    }

    /**
     * A North-Indian diamond chart from a lagna sign NAME + planet rows (`{name, house, retro}`).
     * '' if the lagna sign is unknown.
     *
     * @param list<mixed> $rows
     */
    public static function renderNorthChart(
        mixed $lagnaSign,
        array $rows,
        string $title,
        string $locale,
        string $accent,
        int|float $size = 210,
    ): string {
        $idx = self::SIGN_INDEX[$lagnaSign === null ? '' : Js::str($lagnaSign)] ?? null;
        if ($idx === null) {
            return '';
        }

        return NorthIndianChart::northIndianChartSvg(
            lagnaSignIndex: $idx,
            planets: self::chartPlanets($rows, $locale),
            title: $title,
            size: $size,
            accent: $accent,
        );
    }

    // ── Table of contents — 2-pass page resolution ──────────────────────────

    /**
     * Tag each top-level `<section class="section...">` with a `data-toc-title` read from its
     * first `<h2>` (or its Part banner) and an invisible ⟦T<idx>⟧ page-locator marker. Returns
     * the tagged HTML and the ordered list of titles found.
     *
     * @return array{html: string, titles: list<string>}
     */
    public static function tagBrihadSectionsForToc(string $html): array
    {
        $titles = [];
        // One replace after the other, as in JS ("&amp;lt;" ends up as "<").
        $unescape = static fn (string $s): string => Js::trim(str_replace(
            ['&amp;', '&lt;', '&gt;', '&quot;', '&#39;'],
            ['&', '<', '>', '"', "'"],
            (string) preg_replace('/<[^>]+>/u', '', $s),
        ));
        $marker = static fn (int $idx): string => '<span style="color:#fff;font-size:1px;">⟦T' . $idx . '⟧</span>';

        // Each top-level section, optionally opened by a Part banner: the Part banner (level 1) and
        // the section heading (level 2) both become TOC entries, and both markers go at the very
        // top of the section so they resolve to its opening page.
        $tag = static function (string $cls, ?string $partInner, string $inner) use (&$titles, $unescape, $marker): string {
            $out = '<section class="section' . $cls . '"';
            $firstMarker = '';
            $dataAttr = '';

            if ($partInner !== null) {
                // Part banner: a level-1 TOC entry.
                $partTitle = $unescape($partInner);
                $pIdx = \count($titles);
                $titles[] = $partTitle;
                $firstMarker .= $marker($pIdx);
                $dataAttr = ' data-toc-title="' . str_replace('"', '&quot;', $partTitle) . '"';
            }

            // The section heading: a level-2 TOC entry.
            $title = $unescape($inner);
            $sIdx = \count($titles);
            $titles[] = $title;
            $firstMarker .= $marker($sIdx);
            if ($dataAttr === '') {
                $dataAttr = ' data-toc-title="' . str_replace('"', '&quot;', $title) . '"';
            }

            $out .= $dataAttr . '>' . $firstMarker;
            if ($partInner !== null) {
                $out .= '<div class="part-banner"><h2 class="part-h2">' . $partInner . '</h2></div>';
            }

            return $out . '<h2>' . $inner . '</h2>';
        };

        // JS `html.replace(/<section class="section([^"]*)">\s*(?:<div class="part-banner"><h2
        // class="part-h2">([\s\S]*?)<\/h2><\/div>\s*)?<h2>([\s\S]*?)<\/h2>/g, ...)`, matched by hand: a
        // PCRE lazy scan over a long section runs into pcre.backtrack_limit.
        $tagged = '';
        $copied = 0;
        $from = 0;
        while (($p = strpos($html, self::SECTION_OPEN, $from)) !== false) {
            $m = self::matchTocSection($html, $p);
            if ($m === null) {
                $from = $p + 1;
                continue;
            }
            $tagged .= substr($html, $copied, $p - $copied) . $tag($m['cls'], $m['part'], $m['inner']);
            $copied = $from = $m['end'];
        }
        $tagged .= substr($html, $copied);

        /** @var list<string> $titles */
        return ['html' => $tagged, 'titles' => $titles];
    }

    /**
     * The TOC section pattern matched at `$p` (where `<section class="section` starts), as the JS
     * regex would match there; null when it does not.
     *
     * @return array{end: int, cls: string, part: string|null, inner: string}|null
     */
    private static function matchTocSection(string $html, int $p): ?array
    {
        $clsStart = $p + \strlen(self::SECTION_OPEN);
        $q = strpos($html, '"', $clsStart);
        if ($q === false || !self::at($html, '">', $q)) {
            return null;
        }
        $cls = substr($html, $clsStart, $q - $clsStart);
        $i = self::skipJsSpace($html, $q + 2);
        if (self::at($html, self::PART_BANNER_OPEN, $i)) {
            // The optional Part banner: its lazy body grows to each `</h2></div>` in turn until
            // whitespace + `<h2>` follows. (Without the banner, `<h2>` cannot match at `<div`.)
            $j = $i + \strlen(self::PART_BANNER_OPEN);
            $k = $j;
            while (($c = strpos($html, '</h2></div>', $k)) !== false) {
                $t = self::skipJsSpace($html, $c + 11);
                if (self::at($html, '<h2>', $t)) {
                    $e = strpos($html, '</h2>', $t + 4);

                    return $e === false ? null : [
                        'end' => $e + 5,
                        'cls' => $cls,
                        'part' => substr($html, $j, $c - $j),
                        'inner' => substr($html, $t + 4, $e - $t - 4),
                    ];
                }
                $k = $c + 1;
            }

            return null;
        }
        if (!self::at($html, '<h2>', $i)) {
            return null;
        }
        $e = strpos($html, '</h2>', $i + 4);

        return $e === false ? null : [
            'end' => $e + 5,
            'cls' => $cls,
            'part' => null,
            'inner' => substr($html, $i + 4, $e - $i - 4),
        ];
    }

    /** Whether `$needle` occurs in `$s` at byte offset `$i`. */
    private static function at(string $s, string $needle, int $i): bool
    {
        return substr($s, $i, \strlen($needle)) === $needle;
    }

    /**
     * The byte offset after the run of JS `\s` whitespace that starts at `$i` (without a regex,
     * so a long string never hits a PCRE limit).
     *
     * @internal Also used by {@see KundliBrihad}.
     */
    public static function skipJsSpace(string $s, int $i): int
    {
        $n = \strlen($s);
        while ($i < $n) {
            $c = $s[$i];
            if ($c === ' ' || $c === "\n" || $c === "\t" || $c === "\r" || $c === "\x0B" || $c === "\f") {
                $i++;
                continue;
            }
            if (\ord($c) < 0xC2) {
                break;
            }
            foreach (self::JS_SPACES as $space) {
                if (self::at($s, $space, $i)) {
                    $i += \strlen($space);
                    continue 2;
                }
            }
            break;
        }

        return $i;
    }

    /**
     * JS `s.trim().length === 0`, without a regex (a PCRE trim of a long string can fail).
     *
     * @internal Also used by {@see KundliBrihad}.
     */
    public static function isJsBlank(string $s): bool
    {
        return self::skipJsSpace($s, 0) === \strlen($s);
    }

    /**
     * The table-of-contents section from measured entries. Part rows ("Part N: …" in any
     * locale) are bold level-1 rows; the rest are indented level-2 rows. Page numbers use a
     * rotating colour palette.
     *
     * @param list<array{title: string, page: int}> $entries
     */
    public static function buildBrihadToc(array $entries, string $heading): string
    {
        if ($entries === []) {
            return '';
        }
        $rows = [];
        foreach ($entries as $i => $e) {
            $col = self::TOC_PALETTE[$i % \count(self::TOC_PALETTE)];
            $isPart = preg_match(Locales::PART_PREFIX_RE, Js::trim($e['title'])) === 1;
            $cls = $isPart ? 'toc-row toc-part' : 'toc-row toc-sub';
            $rows[] = '<div class="' . $cls . '">
  <span class="toc-title">' . Shared::esc($e['title']) . '</span>
  <span class="toc-dots"></span>
  <span class="toc-page" style="color:' . $col . ';">' . $e['page'] . '</span>
</div>';
        }

        return '<section class="section toc-section">
<div class="toc-banner">' . Shared::esc($heading) . '</div>
' . implode("\n", $rows) . '
</section>';
    }

    /**
     * Running header (a Chromium page-margin template): page number (left) + company wordmark
     * (right).
     *
     * As in the Node.js SDK, the branding is read as the report's raw PascalCase `Branding`
     * (`$data['Branding']`, or such an array with the caller's overrides merged in). A
     * {@see Branding} object carries no PascalCase fields, so it gives the defaults, exactly as a
     * camelCase branding does in Node.
     *
     * @param Branding|array<mixed>|null $branding
     */
    public static function brihadHeaderTemplate(Branding|array|null $branding = null): string
    {
        $b = self::rawBranding($branding);
        $company = Js::str(Js::or(Shared::esc($b['companyName']), 'LifeAstro'));

        return '<div style="width:100%;font-family:' . Locales::INDIC_FONT_STACK_ATTR
            . ';-webkit-print-color-adjust:exact;padding:8mm 14mm 0;">
  <div style="display:flex;align-items:center;justify-content:space-between;border-bottom:0.35mm solid #e3d9c0;padding-bottom:2mm;">
    <div style="color:#9e2b25;font-size:12px;font-weight:bold;letter-spacing:.5px;">
      <span style="color:#c25e10;">✦</span>&nbsp;<span class="pageNumber"></span>
    </div>
    <div style="color:#9e2b25;font-weight:bold;font-size:13px;letter-spacing:.4px;">
      <span style="color:#c25e10;">✦</span> ' . $company . '
    </div>
  </div>
</div>';
    }

    /**
     * Running footer: star flourishes around the footer text. The branding is read like
     * {@see self::brihadHeaderTemplate()}'s.
     *
     * @param Branding|array<mixed>|null $branding
     */
    public static function brihadFooterTemplate(Branding|array|null $branding = null): string
    {
        $b = self::rawBranding($branding);
        $txt = Js::str(Js::or(Shared::esc($b['footerText']), 'www.lifeastroapi.com'));
        $star = '<span style="color:#c25e10;font-size:10px;">✦</span><span style="color:#9e2b25;font-size:7px;">✦</span>';

        return '<div style="width:100%;font-family:' . Locales::INDIC_FONT_STACK_ATTR . ';-webkit-print-color-adjust:exact;
              text-align:center;font-size:9px;color:#9e2b25;padding-bottom:5mm;">
  ' . $star . '<span style="margin:0 5mm;letter-spacing:.3px;">' . $txt . '</span>' . $star . '
</div>';
    }

    /**
     * Node's `resolveBranding(branding as RawBranding)`: only PascalCase keys are read.
     *
     * @param Branding|array<mixed>|null $branding
     *
     * @return array{companyName: string, logoUrl: string, primaryColor: string, secondaryColor: string,
     *     footerText: string, watermarkText: string}
     */
    private static function rawBranding(Branding|array|null $branding): array
    {
        return Shared::resolveBranding(\is_array($branding) ? $branding : null, null);
    }

    // ── Small helpers ───────────────────────────────────────────────────────

    /** @param \Closure(string): string $label */
    private static function yesNo(\Closure $label, mixed $v): string
    {
        return Shared::esc($label(Js::truthy($v) ? 'lk_present' : 'lk_absent'));
    }

    /** JS `(list ?? []).join(sep)` for a JSON array (not escaped). */
    private static function join(mixed $list, string $sep): string
    {
        return implode($sep, array_map(
            static fn (mixed $e): string => $e === null ? '' : Js::str($e),
            Js::list($list),
        ));
    }

    /** JS `(n ?? 0).toFixed(2)`. */
    private static function fixed2(mixed $n): string
    {
        return Js::toFixed(Js::numOrNull($n) ?? 0, 2);
    }

    /**
     * @param list<string> $headers
     * @param list<string> $rows
     */
    private static function table(array $headers, array $rows): string
    {
        return '<table>
<thead><tr>' . implode('', array_map(static fn (string $h): string => '<th>' . Shared::esc($h) . '</th>', $headers))
            . '</tr></thead>
<tbody>
' . implode("\n", $rows) . '
</tbody>
</table>';
    }

    /**
     * `$fn` over the rows of a JSON array (each row read as an object), joined with newlines.
     *
     * @param \Closure(array<string, mixed>): string $fn
     */
    private static function rows(mixed $list, \Closure $fn): string
    {
        return implode("\n", array_map(static fn (mixed $r): string => $fn(Js::map($r)), Js::list($list)));
    }

    /** Render one narrative chapter (heading + body + bullets per section). */
    public static function renderChapter(mixed $ch, string $locale = 'en'): string
    {
        $c = Js::map($ch);
        $secs = Js::list($c['sections'] ?? null);
        if ($secs === []) {
            return '';
        }
        $blocks = self::rows($secs, static function (array $s) use ($locale): string {
            $heading = Js::truthy($s['Heading'] ?? null) ? '<h3>' . Shared::esc($s['Heading']) . '</h3>' : '';
            $body = Js::truthy($s['Body'] ?? null) ? '<p>' . Shared::escBody($s['Body'], $locale) . '</p>' : '';
            $bullets = Js::nonEmptyList($s['Bullets'] ?? null)
                ? '<ul>' . implode('', array_map(
                    static fn (mixed $x): string => '<li>' . Shared::escBody($x, $locale) . '</li>',
                    Js::list($s['Bullets']),
                )) . '</ul>'
                : '';

            return $heading . $body . $bullets;
        });

        return '<section class="section narrative-chapter">
<h2>' . Shared::esc(Js::or(Js::or($c['title'] ?? null, $c['key'] ?? null), '')) . '</h2>
' . $blocks . '
</section>';
    }

    // ── Section renderers ───────────────────────────────────────────────────

    /**
     * `<tr>` rows of label/value pairs whose value is truthy.
     *
     * @param \Closure(string): string $label
     * @param array<string, mixed> $m
     * @param array<string, string> $keys label key => field
     */
    private static function kvRows(\Closure $label, array $m, array $keys): string
    {
        $out = [];
        foreach ($keys as $k => $f) {
            if (Js::truthy($m[$f] ?? null)) {
                $out[] = '<tr><td><strong>' . Shared::esc($label($k)) . '</strong></td><td>' . Shared::esc($m[$f])
                    . '</td></tr>';
            }
        }

        return implode("\n", $out);
    }

    /** @param \Closure(string): string $label */
    private static function renderAvakahada(\Closure $label, mixed $a): string
    {
        if (!Js::truthy($a)) {
            return '';
        }
        $body = self::kvRows($label, Js::map($a), [
            'avk_paya' => 'paya',
            'avk_varna' => 'varna',
            'avk_vashya' => 'vashya',
            'avk_yoni' => 'yoni',
            'avk_gana' => 'gana',
            'avk_nadi' => 'nadi',
            'avk_tatva' => 'tatva',
            'avk_nak_lord' => 'nakshatra_lord',
            'avk_rashi_lord' => 'rashi_lord',
            'avk_lagna_lord' => 'lagna_lord',
        ]);
        if ($body === '') {
            return '';
        }

        return '<section class="section">
<h2>' . Shared::esc($label('section_avakahada')) . '</h2>
<table><tbody>' . $body . '</tbody></table>
</section>';
    }

    /** @param \Closure(string): string $label */
    private static function renderPanchang(\Closure $label, mixed $p): string
    {
        if (!Js::truthy($p)) {
            return '';
        }
        $body = self::kvRows($label, Js::map($p), [
            'panchang_tithi' => 'tithi',
            'panchang_vara' => 'vara',
            'panchang_nakshatra' => 'nakshatra',
            'panchang_yoga' => 'yoga',
            'panchang_karana' => 'karana',
        ]);
        if ($body === '') {
            return '';
        }

        return '<section class="section">
<h2>' . Shared::esc($label('section_panchang')) . '</h2>
<p>' . Shared::esc($label('panchang_intro')) . '</p>
<table><tbody>' . $body . '</tbody></table>
</section>';
    }

    /** @param \Closure(string): string $label */
    private static function renderGems(\Closure $label, mixed $g): string
    {
        $stones = Js::list(Js::map($g)['stones'] ?? null);
        if ($stones === []) {
            return '';
        }
        $rows = self::rows($stones, static fn (array $s): string => '<tr><td><strong>'
            . Shared::esc($s['kind'] ?? null) . '</strong></td><td>' . Shared::esc($s['stone'] ?? null) . '</td><td>'
            . Shared::esc($s['planet'] ?? null) . '</td><td>' . Shared::esc($s['reason'] ?? null) . '</td></tr>');

        return '<section class="section">
<h2>' . Shared::esc($label('section_gems')) . '</h2>
<p>' . Shared::esc($label('gems_intro')) . '</p>
<table><thead><tr>
<th>' . Shared::esc($label('gems_kind')) . '</th><th>' . Shared::esc($label('gems_stone')) . '</th>
<th>' . Shared::esc($label('gems_planet')) . '</th><th>' . Shared::esc($label('gems_reason')) . '</th>
</tr></thead><tbody>' . $rows . '</tbody></table>
<p class="gems-note">' . Shared::esc($label('gems_note')) . '</p>
</section>';
    }

    /** @param \Closure(string): string $label */
    private static function renderGhata(\Closure $label, mixed $g): string
    {
        if (!Js::truthy($g)) {
            return '';
        }
        $m = Js::map($g);
        $keys = [
            'ghata_weekday' => 'weekday',
            'ghata_tithi' => 'tithi_text',
            'ghata_month' => 'month',
            'ghata_nakshatra' => 'nakshatra',
            'ghata_yoga' => 'yoga',
            'ghata_karana' => 'karana',
            'ghata_prahar' => 'prahar',
            'ghata_rashi' => 'ghata_rashi',
        ];
        // JS `v !== undefined && v !== ""`: a present null still gets a row.
        $out = [];
        foreach ($keys as $k => $f) {
            if (\array_key_exists($f, $m) && $m[$f] !== '') {
                $out[] = '<tr><td><strong>' . Shared::esc($label($k)) . '</strong></td><td>' . Shared::esc($m[$f])
                    . '</td></tr>';
            }
        }
        if ($out === []) {
            return '';
        }

        return '<section class="section">
<h2>' . Shared::esc($label('section_ghata')) . '</h2>
<p>' . Shared::esc($label('ghata_intro')) . '</p>
<table><tbody>' . implode("\n", $out) . '</tbody></table>
</section>';
    }

    /** @param \Closure(string): string $label */
    private static function renderChalit(\Closure $label, mixed $c): string
    {
        $m = Js::map($c);
        if (!Js::truthy($c) || !Js::nonEmptyList($m['houses'] ?? null)) {
            return '';
        }
        $hrows = self::rows($m['houses'], static fn (array $h): string => '<tr><td>' . Shared::esc($h['house'] ?? null)
            . '</td><td>' . Shared::esc($h['begin_sign'] ?? null) . ' ' . Shared::esc($h['begin_dms'] ?? null)
            . '</td><td>' . Shared::esc($h['madhya_sign'] ?? null) . ' ' . Shared::esc($h['madhya_dms'] ?? null)
            . '</td></tr>');
        $prows = self::rows($m['planets'] ?? null, static fn (array $p): string => '<tr><td>'
            . Shared::esc($p['name'] ?? null) . '</td><td>' . Shared::esc($p['rashi_house'] ?? null) . '</td><td>'
            . Shared::esc($p['chalit_house'] ?? null) . '</td><td>' . (Js::truthy($p['shifted'] ?? null) ? '✓' : '')
            . '</td></tr>');

        $planetsTable = self::table(
            [$label('planet'), $label('chalit_rashi_house'), $label('chalit_bhava_house'), $label('chalit_shifted')],
            [$prows],
        );

        return '<section class="section">
<h2>' . Shared::esc($label('section_chalit')) . '</h2>
<p>' . Shared::esc($label('chalit_intro')) . '</p>
' . self::table([$label('chalit_house'), $label('chalit_begin'), $label('chalit_madhya')], [$hrows]) . '
<h3>' . Shared::esc($label('chalit_planets')) . '</h3>
' . $planetsTable . '
</section>';
    }

    /** @param \Closure(string): string $label */
    private static function renderKP(\Closure $label, mixed $k): string
    {
        $m = Js::map($k);
        if (!Js::truthy($k) || !Js::nonEmptyList($m['cusps'] ?? null)) {
            return '';
        }
        $cuspRows = self::rows($m['cusps'], static fn (array $c): string => '<tr><td>' . Shared::esc($c['house'] ?? null)
            . '</td><td>' . Shared::esc($c['sign'] ?? null) . ' ' . Shared::esc($c['dms'] ?? null) . '</td><td>'
            . Shared::esc($c['star_lord'] ?? null) . '</td><td>' . Shared::esc($c['sub_lord'] ?? null) . '</td><td>'
            . Shared::esc($c['sub_sub_lord'] ?? null) . '</td></tr>');
        $sigRows = self::rows($m['planet_significators'] ?? null, static fn (array $s): string => '<tr><td>'
            . Shared::esc($s['key'] ?? null) . '</td><td>' . self::join($s['values'] ?? null, ', ') . '</td></tr>');
        $rp = $m['ruling_planets'] ?? null;
        $rp = \is_array($rp) ? $rp : [];
        $rpRows = implode("\n", array_map(
            static fn (string $key): string => '<tr><td><strong>' . Shared::esc(Js::or($label('kp_rp_' . $key), $key))
                . '</strong></td><td>' . Shared::esc($rp[$key] ?? null) . '</td></tr>',
            Js::keys($rp),
        ));
        $rpHtml = $rpRows !== ''
            ? '<h3>' . Shared::esc($label('kp_ruling_planets')) . '</h3>'
                . self::table([$label('kp_rp_role'), $label('planet')], [$rpRows])
            : '';

        $cuspTable = self::table(
            [$label('house'), $label('sign'), $label('kp_star_lord'), $label('kp_sub_lord'), $label('kp_subsub_lord')],
            [$cuspRows],
        );

        return '<section class="section">
<h2>' . Shared::esc($label('section_kp')) . '</h2>
<p>' . Shared::esc($label('kp_intro')) . '</p>
' . $cuspTable . '
<h3>' . Shared::esc($label('kp_significators')) . '</h3>
' . self::table([$label('planet'), $label('kp_signifies_houses')], [$sigRows]) . '
' . $rpHtml . '
</section>';
    }

    /** @param \Closure(string): string $label */
    private static function renderJaimini(\Closure $label, mixed $j): string
    {
        if (!Js::truthy($j)) {
            return '';
        }
        $m = Js::map($j);
        $ckRows = self::rows($m['charakarakas'] ?? null, static fn (array $c): string => '<tr><td>'
            . Shared::esc($c['karaka'] ?? null) . '</td><td>' . Shared::esc($c['planet'] ?? null) . '</td></tr>');
        $arRows = self::rows($m['arudha_padas'] ?? null, static fn (array $a): string => '<tr><td>'
            . Shared::esc($a['label'] ?? null) . ' (' . Shared::esc($a['name'] ?? null) . ')</td><td>'
            . Shared::esc($a['sign'] ?? null) . '</td><td>' . Shared::esc($a['house'] ?? null) . '</td></tr>');
        if ($ckRows === '' && $arRows === '') {
            return '';
        }
        $ckHtml = $ckRows !== ''
            ? '<h3>' . Shared::esc($label('jaimini_charakarakas')) . '</h3>'
                . self::table([$label('jaimini_karaka'), $label('planet')], [$ckRows])
            : '';
        $arHtml = $arRows !== ''
            ? '<h3>' . Shared::esc($label('jaimini_arudha')) . '</h3>'
                . self::table([$label('jaimini_pada'), $label('sign'), $label('house')], [$arRows])
            : '';

        return '<section class="section">
<h2>' . Shared::esc($label('section_jaimini')) . '</h2>
' . $ckHtml . '
<p><strong>' . Shared::esc($label('jaimini_karakamsa')) . ':</strong> ' . Shared::esc($m['karakamsa_sign'] ?? null)
            . ' &nbsp; <strong>' . Shared::esc($label('jaimini_swamsa')) . ':</strong> '
            . Shared::esc($m['swamsa_sign'] ?? null) . '</p>
' . $arHtml . '
</section>';
    }

    /** @param \Closure(string): string $label */
    private static function renderIshta(\Closure $label, mixed $i, string $locale = 'en'): string
    {
        $m = Js::map($i);
        if (!Js::truthy($i) || !Js::truthy($m['deity'] ?? null)) {
            return '';
        }
        $bodyHtml = Js::truthy($m['body'] ?? null) ? '<p>' . Shared::escBody($m['body'], $locale) . '</p>' : '';

        return '<section class="section">
<h2>' . Shared::esc($label('section_ishta')) . '</h2>
<p>' . Shared::esc($label('ishta_intro')) . '</p>
<p><strong>' . Shared::esc($label('ishta_deity')) . ':</strong> ' . Shared::esc($m['deity']) . '<br>
<strong>' . Shared::esc($label('ishta_12th_sign')) . ':</strong> ' . Shared::esc($m['twelfth_sign'] ?? null) . ' &nbsp;
<strong>' . Shared::esc($label('ishta_graha')) . ':</strong> ' . Shared::esc($m['determining_graha'] ?? null) . '</p>
' . $bodyHtml . '
</section>';
    }

    /** @param \Closure(string): string $label */
    private static function renderMaitri(\Closure $label, mixed $mt): string
    {
        $m = Js::map($mt);
        if (!Js::truthy($mt) || !Js::nonEmptyList($m['order'] ?? null)) {
            return '';
        }
        $order = Js::list($m['order']);
        $tbl = static function (mixed $rows) use ($order): string {
            if (!Js::nonEmptyList($rows)) {
                return '';
            }
            $head = '<tr><th></th>' . implode('', array_map(
                static fn (mixed $o): string => '<th>' . Shared::esc($o) . '</th>',
                $order,
            )) . '</tr>';
            $body = self::rows($rows, static function (array $r) use ($order): string {
                $rel = $r['relations'] ?? null;
                $rel = \is_array($rel) ? $rel : [];

                return '<tr><td><strong>' . Shared::esc($r['planet'] ?? null) . '</strong></td>' . implode('', array_map(
                    static fn (mixed $o): string => '<td>' . Shared::esc($rel[Js::str($o)] ?? null) . '</td>',
                    $order,
                )) . '</tr>';
            });

            return '<table><thead>' . $head . '</thead><tbody>' . $body . '</tbody></table>';
        };

        return '<section class="section">
<h2>' . Shared::esc($label('section_maitri')) . '</h2>
<p>' . Shared::esc($label('maitri_intro')) . '</p>
<h3>' . Shared::esc($label('maitri_naisargik')) . '</h3>' . $tbl($m['naisargik'] ?? null) . '
<h3>' . Shared::esc($label('maitri_tatkalik')) . '</h3>' . $tbl($m['tatkalik'] ?? null) . '
<h3>' . Shared::esc($label('maitri_panchadha')) . '</h3>' . $tbl($m['panchadha'] ?? null) . '
</section>';
    }

    /** @param \Closure(string): string $label */
    private static function renderAvasthas(\Closure $label, mixed $a): string
    {
        if (!Js::nonEmptyList($a)) {
            return '';
        }
        $rows = self::rows($a, static fn (array $x): string => '<tr><td>' . Shared::esc($x['planet'] ?? null)
            . '</td><td>' . Shared::esc($x['baladi'] ?? null) . '</td><td>' . Shared::esc($x['jagradadi'] ?? null)
            . '</td><td>' . Shared::esc($x['deeptadi'] ?? null) . '</td></tr>');

        $table = self::table(
            [$label('planet'), $label('avastha_baladi'), $label('avastha_jagradadi'), $label('avastha_deeptadi')],
            [$rows],
        );

        return '<section class="section">
<h2>' . Shared::esc($label('section_avastha')) . '</h2>
<p>' . Shared::esc($label('avastha_intro')) . '</p>
' . $table . '
</section>';
    }

    /** @param \Closure(string): string $label */
    private static function renderLalKitab(\Closure $label, mixed $lk): string
    {
        if (!Js::truthy($lk)) {
            return '';
        }
        $m = Js::map($lk);
        $planetRows = self::rows($m['planets'] ?? null, static fn (array $p): string => '<tr><td>'
            . Shared::esc($p['name'] ?? null) . '</td><td>' . Shared::esc($p['house'] ?? null) . '</td><td>'
            . Shared::esc($p['sign'] ?? null) . '</td></tr>');
        $rinRows = self::rows($m['rins'] ?? null, static fn (array $r): string => '<tr><td>'
            . Shared::esc($r['name'] ?? null) . '</td><td>' . self::yesNo($label, $r['present'] ?? null) . '</td></tr>');
        if ($planetRows === '' && $rinRows === '') {
            return '';
        }
        $planetsHtml = $planetRows !== ''
            ? '<h3>' . Shared::esc($label('lalkitab_planets')) . '</h3>'
                . self::table([$label('planet'), $label('house'), $label('sign')], [$planetRows])
            : '';
        $rinsHtml = $rinRows !== ''
            ? '<h3>' . Shared::esc($label('lalkitab_rins')) . '</h3>'
                . self::table([$label('lalkitab_rin'), $label('lalkitab_status')], [$rinRows])
            : '';

        return '<section class="section">
<h2>' . Shared::esc($label('section_lalkitab')) . '</h2>
<p>' . Shared::esc($label('lalkitab_intro')) . '</p>
' . $planetsHtml . '
' . $rinsHtml . '
</section>';
    }

    /** @param \Closure(string): string $label */
    private static function renderShadbala(\Closure $label, mixed $s): string
    {
        if (!Js::nonEmptyList($s)) {
            return '';
        }
        $rows = self::rows($s, static fn (array $r): string => '<tr><td>' . Shared::esc($r['planet'] ?? null)
            . '</td><td>' . Shared::esc(self::fixed2($r['total_rupas'] ?? null)) . '</td><td>'
            . Shared::esc($r['rank'] ?? null) . '</td></tr>');

        return '<section class="section">
<h2>' . Shared::esc($label('section_shadbala')) . '</h2>
<p>' . Shared::esc($label('shadbala_intro')) . '</p>
' . self::table([$label('planet'), $label('shadbala_rupas'), $label('shadbala_rank')], [$rows]) . '
</section>';
    }

    /** @param \Closure(string): string $label */
    private static function renderBhavaBala(\Closure $label, mixed $b): string
    {
        if (!Js::nonEmptyList($b)) {
            return '';
        }
        $rows = self::rows($b, static fn (array $r): string => '<tr><td>' . Shared::esc($r['house'] ?? null)
            . '</td><td>' . Shared::esc(self::fixed2($r['bhavadhipati'] ?? null)) . '</td><td>'
            . Shared::esc(self::fixed2($r['bhava_dig_bala'] ?? null)) . '</td><td>'
            . Shared::esc(self::fixed2($r['bhava_drsti'] ?? null)) . '</td><td>'
            . Shared::esc(self::fixed2($r['total'] ?? null)) . '</td></tr>');

        $table = self::table(
            [$label('house'), $label('bb_lord'), $label('bb_dig'), $label('bb_drsti'), $label('bb_total')],
            [$rows],
        );

        return '<section class="section">
<h2>' . Shared::esc($label('section_bhavabala')) . '</h2>
<p>' . Shared::esc($label('bhavabala_intro')) . '</p>
' . $table . '
</section>';
    }

    /** @param \Closure(string): string $label */
    private static function renderAshtakavarga(\Closure $label, mixed $av): string
    {
        $a = Js::map($av);
        if (!Js::truthy($av) || !Js::nonEmptyList($a['sav'] ?? null)) {
            return '';
        }
        $sav = Js::list($a['sav']);
        $savCells = implode('', array_map(static fn (mixed $n): string => '<td>' . Shared::esc($n) . '</td>', $sav));
        $prastarRows = self::rows($a['prastar'] ?? null, static fn (array $p): string => '<tr><td>'
            . Shared::esc($p['planet'] ?? null) . '</td><td>' . Shared::esc($p['total'] ?? null) . '</td></tr>');
        $savHeads = '';
        foreach (array_keys($sav) as $i) {
            $savHeads .= '<th>' . ($i + 1) . '</th>';
        }
        $prastarHtml = $prastarRows !== ''
            ? '<h3>' . Shared::esc($label('ashtakavarga_prastar')) . '</h3>'
                . self::table([$label('planet'), $label('ashtakavarga_planet_total')], [$prastarRows])
            : '';

        return '<section class="section">
<h2>' . Shared::esc($label('section_ashtakavarga')) . '</h2>
<p>' . Shared::esc($label('ashtakavarga_intro')) . '</p>
<table><thead><tr><th>' . Shared::esc($label('ashtakavarga_sav')) . '</th>' . $savHeads . '</tr></thead>
<tbody><tr><td><strong>' . Shared::esc($label('ashtakavarga_bindus')) . '</strong></td>' . $savCells . '</tr>
<tr><td colspan="' . (\count($sav) + 1) . '">' . Shared::esc($label('ashtakavarga_total')) . ': <strong>'
            . Shared::esc($a['sav_total'] ?? null) . '</strong></td></tr></tbody></table>
' . $prastarHtml . '
</section>';
    }

    /** @param \Closure(string): string $label */
    private static function renderVarshaphal(\Closure $label, mixed $years, string $locale = 'en'): string
    {
        if (!Js::nonEmptyList($years)) {
            return '';
        }
        $blocks = [];
        foreach (Js::list($years) as $raw) {
            $y = Js::map($raw);
            $sahamRows = self::rows($y['sahams'] ?? null, static fn (array $s): string => '<tr><td>'
                . Shared::esc($s['name'] ?? null) . '</td><td>' . Shared::esc($s['sign'] ?? null) . ' '
                . Shared::esc($s['dms'] ?? null) . '</td><td>' . Shared::esc($s['lord'] ?? null) . '</td></tr>');
            $pvRows = self::rows($y['panchavargeeya'] ?? null, static fn (array $p): string => '<tr><td>'
                . Shared::esc($p['planet'] ?? null) . '</td><td>' . Shared::esc(self::fixed2($p['vishwa'] ?? null))
                . '</td></tr>');
            $summary = Js::truthy($y['summary'] ?? null) ? '<p>' . Shared::escBody($y['summary'], $locale) . '</p>' : '';
            $sahamHtml = $sahamRows !== ''
                ? '<h4>' . Shared::esc($label('varshaphal_sahams')) . '</h4>'
                    . self::table([$label('varshaphal_saham'), $label('sign'), $label('lord')], [$sahamRows])
                : '';
            $pvHtml = $pvRows !== ''
                ? '<h4>' . Shared::esc($label('varshaphal_panchavargeeya')) . '</h4>'
                    . self::table([$label('planet'), $label('varshaphal_vishwa')], [$pvRows])
                : '';
            $blocks[] = '<h3>' . Shared::esc($label('varshaphal_year')) . ' ' . Shared::esc($y['year'] ?? null) . '</h3>
<p><strong>' . Shared::esc($label('varshaphal_muntha')) . ':</strong> ' . Shared::esc($y['muntha_sign'] ?? null)
                . ' (' . Shared::esc($label('house')) . ' ' . Shared::esc($y['muntha_house'] ?? null) . ') &nbsp;
<strong>' . Shared::esc($label('varshaphal_lord')) . ':</strong> ' . Shared::esc($y['varsha_lord'] ?? null) . '</p>
' . $summary . '
' . $sahamHtml . '
' . $pvHtml;
        }

        return '<section class="section">
<h2>' . Shared::esc($label('section_varshaphal')) . '</h2>
<p>' . Shared::esc($label('varshaphal_intro')) . '</p>
' . implode("\n<hr>\n", $blocks) . '
</section>';
    }

    /**
     * Month-wise Mudda-Dasha timeline: a card per period with a date range, summary, life-area
     * lines and a do / avoid / remedy box.
     *
     * @param \Closure(string): string $label
     */
    private static function renderMuddaTimeline(\Closure $label, mixed $tl, string $locale = 'en'): string
    {
        $t = Js::map($tl);
        if (!Js::truthy($tl) || !Js::nonEmptyList($t['periods'] ?? null)) {
            return '';
        }
        $cards = self::rows($t['periods'], static function (array $p) use ($label, $locale): string {
            $areas = [];
            foreach (Js::list($p['areas'] ?? null) as $rawArea) {
                $a = Js::map($rawArea);
                if (Js::truthy($a['text'] ?? null)) {
                    $areas[] = '<p class="mudda-area"><strong>' . Shared::esc($a['label'] ?? null) . ':</strong> '
                        . Shared::escBody($a['text'], $locale) . '</p>';
                }
            }
            $line = static fn (string $key, string $labelKey): string => Js::truthy($p[$key] ?? null)
                ? '<p><strong>' . Shared::esc($p[$labelKey] ?? null) . ':</strong> ' . Shared::escBody($p[$key], $locale)
                    . '</p>'
                : '';
            $guidance = '<div class="mudda-guidance">
' . $line('do', 'do_label') . '
' . $line('avoid', 'avoid_label') . '
' . $line('remedy', 'remedy_label') . '
</div>';
            $summary = Js::truthy($p['summary'] ?? null)
                ? '<p class="mudda-summary">' . Shared::escBody($p['summary'], $locale) . '</p>'
                : '';

            return '<div class="mudda-period">
<h3 class="mudda-head">' . Shared::esc($p['date_from'] ?? null) . ' ' . Shared::esc(Js::or($label('varshaphal_to'), '–'))
                . ' ' . Shared::esc($p['date_to'] ?? null) . ' &nbsp;·&nbsp; ' . Shared::esc($p['lord'] ?? null) . '</h3>
' . $summary . '
' . implode("\n", $areas) . '
' . $guidance . '
</div>';
        });
        $yr = Js::truthy($t['year'] ?? null) ? ' ' . Shared::esc($t['year']) : '';

        return '<section class="section mudda-section">
<h2>' . Shared::esc(Js::or($t['heading'] ?? null, $label('section_mudda'))) . $yr . '</h2>
' . $cards . '
</section>';
    }

    /** @param \Closure(string): string $label */
    private static function renderRajyoga(\Closure $label, mixed $rp): string
    {
        if (!Js::truthy($rp)) {
            return '';
        }
        $r = Js::map($rp);
        $skRows = self::rows($r['swarnim_kaal'] ?? null, static fn (array $p): string => '<tr><td>'
            . Shared::esc($p['lord'] ?? null) . '</td><td>' . Shared::esc($p['start'] ?? null) . '</td><td>'
            . Shared::esc($p['end'] ?? null) . '</td></tr>');
        $skHtml = $skRows !== ''
            ? '<h3>' . Shared::esc($label('rajyoga_swarnim')) . '</h3>'
                . self::table([$label('planet'), $label('rajyoga_from'), $label('rajyoga_to')], [$skRows])
            : '';

        return '<section class="section">
<h2>' . Shared::esc($label('section_rajyoga')) . '</h2>
<div class="rajyoga-power">
<div class="rajyoga-pct">' . Shared::esc($r['percent'] ?? null) . '%</div>
<div class="rajyoga-label">' . Shared::esc($label('rajyoga_strength')) . '</div>
</div>
<p>' . Shared::esc($label('rajyoga_planets')) . ': ' . self::join($r['planets'] ?? null, ', ') . '</p>
' . $skHtml . '
</section>';
    }

    /**
     * All deep classical sections + the corpus-backed narrative chapters, in the older flat
     * report order, without the empty ones.
     *
     * @param \Closure(string): string $label
     * @param array<string, mixed> $d
     *
     * @return list<string>
     */
    public static function renderBrihadDeepSections(\Closure $label, array $d, string $locale = 'en'): array
    {
        $out = [
            self::renderAvakahada($label, $d['avakahada'] ?? null),
            self::renderGhata($label, $d['ghata_chakra'] ?? null),
            self::renderChalit($label, $d['chalit'] ?? null),
            self::renderShadbala($label, $d['shadbala'] ?? null),
            self::renderBhavaBala($label, $d['bhava_bala'] ?? null),
            self::renderAshtakavarga($label, $d['ashtakavarga'] ?? null),
            self::renderMaitri($label, $d['maitri'] ?? null),
            self::renderAvasthas($label, $d['avasthas'] ?? null),
            self::renderKP($label, $d['kp'] ?? null),
            self::renderJaimini($label, $d['jaimini'] ?? null),
            self::renderIshta($label, $d['ishta_devata'] ?? null, $locale),
            self::renderLalKitab($label, $d['lal_kitab'] ?? null),
            self::renderRajyoga($label, $d['rajyoga_power'] ?? null),
            self::renderVarshaphal($label, $d['varshaphal'] ?? null, $locale),
            self::renderMuddaTimeline($label, $d['mudda_timeline'] ?? null, $locale),
        ];
        // Corpus-backed narrative chapters. vistrit_bhavishya opens the document, so it is
        // skipped here.
        foreach (Js::list($d['narrative_chapters'] ?? null) as $ch) {
            if ((Js::map($ch)['key'] ?? null) === 'vistrit_bhavishya') {
                continue;
            }
            $out[] = self::renderChapter($ch, $locale);
        }

        return array_values(array_filter($out, static fn (string $s): bool => $s !== ''));
    }

    /**
     * The Brihad deep sections rendered individually and addressable by key, so the Part layout
     * can place each one. Each value is the section HTML or ''.
     *
     * @param \Closure(string): string $label
     * @param array<string, mixed> $d
     *
     * @return array{avakahada: string, panchang: string, gems: string, ghata: string, chalit: string,
     *     shadbala: string, bhavabala: string, ashtakavarga: string, maitri: string, avasthas: string,
     *     kp: string, jaimini: string, ishta: string, lalkitab: string, rajyoga: string,
     *     varshaphal: string, mudda: string}
     */
    public static function brihadDeepBlocks(\Closure $label, array $d, string $locale = 'en'): array
    {
        return [
            'avakahada' => self::renderAvakahada($label, $d['avakahada'] ?? null),
            'panchang' => self::renderPanchang($label, $d['panchang'] ?? null),
            'gems' => self::renderGems($label, $d['gems'] ?? null),
            'ghata' => self::renderGhata($label, $d['ghata_chakra'] ?? null),
            'chalit' => self::renderChalit($label, $d['chalit'] ?? null),
            'shadbala' => self::renderShadbala($label, $d['shadbala'] ?? null),
            'bhavabala' => self::renderBhavaBala($label, $d['bhava_bala'] ?? null),
            'ashtakavarga' => self::renderAshtakavarga($label, $d['ashtakavarga'] ?? null),
            'maitri' => self::renderMaitri($label, $d['maitri'] ?? null),
            'avasthas' => self::renderAvasthas($label, $d['avasthas'] ?? null),
            'kp' => self::renderKP($label, $d['kp'] ?? null),
            'jaimini' => self::renderJaimini($label, $d['jaimini'] ?? null),
            'ishta' => self::renderIshta($label, $d['ishta_devata'] ?? null, $locale),
            'lalkitab' => self::renderLalKitab($label, $d['lal_kitab'] ?? null),
            'rajyoga' => self::renderRajyoga($label, $d['rajyoga_power'] ?? null),
            'varshaphal' => self::renderVarshaphal($label, $d['varshaphal'] ?? null, $locale),
            'mudda' => self::renderMuddaTimeline($label, $d['mudda_timeline'] ?? null, $locale),
        ];
    }
}
