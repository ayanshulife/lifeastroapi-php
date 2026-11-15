<?php

declare(strict_types=1);

namespace LifeAstroApi\Pdf;

/**
 * Shared building blocks for the report -> HTML templates. A port of the Node.js SDK's
 * src/pdf/shared.ts; the output is byte-identical (tests/Pdf/PdfGoldenTest.php).
 *
 * @internal
 */
final class Shared
{
    private const DEFAULT_PRIMARY = '#1a1a1a';
    private const DEFAULT_SECONDARY = '#666666';
    private const DEFAULT_FOOTER = 'Generated using LifeAstroAPI';

    /** A CSS colour value goes unescaped into a `:root` rule, so only this grammar is allowed. */
    private const SAFE_COLOR = '/^(#([0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})|[a-zA-Z]+'
        . '|(?:rgb|rgba|hsl|hsla)\([0-9.,%\/\s]+\))$/uD';

    private const ESC = ['&' => '&amp;', '<' => '&lt;', '>' => '&gt;', '"' => '&quot;', "'" => '&#39;'];

    /** The shared CSS skeleton of every report. */
    public const BASE_CSS = "
* { box-sizing: border-box; }
/* Form controls and headings do not always inherit font-family in print, so
   the Indic stack is set on them explicitly too. */
h1, h2, h3, h4, th, td, li, dt, dd, input, button { font-family: inherit; }
html, body {
  font-family: " . Locales::INDIC_FONT_STACK . ";
  color: #1a1a1a;
  font-size: 11pt;
  line-height: 1.45;
  margin: 0;
}
@page { size: A4; margin: 18mm 15mm 22mm 15mm; }
/* Print the brand colours (table headers, cards) as designed. Chromium does so
   with printBackground; the iOS and Android print engines drop backgrounds
   unless the page asks for exact colours. */
html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
h2 {
  color: var(--brand-primary);
  border-bottom: 2px solid var(--brand-primary);
  padding-bottom: 2mm;
  font-size: 16pt;
  margin-top: 8mm;
}
h3 { font-size: 12pt; margin: 6mm 0 2mm; }
p { margin: 2mm 0; }
table { width: 100%; border-collapse: collapse; margin: 4mm 0; }
th, td { text-align: left; padding: 2mm 3mm; border-bottom: 1px solid #e5e5e5; vertical-align: top; }
th { background: var(--brand-primary); color: #fff; font-weight: 600; font-size: 10pt; }
.cover { page-break-after: always; text-align: center; padding: 40mm 0 0; }
.cover img.logo { max-height: 24mm; max-width: 60mm; margin-bottom: 8mm; }
.cover h1 { color: var(--brand-primary); font-size: 28pt; margin: 0 0 4mm; }
.cover .company { color: var(--brand-secondary); font-size: 14pt; margin-bottom: 16mm; }
.cover .subject { font-size: 16pt; margin-bottom: 4mm; }
.cover .meta { color: var(--brand-secondary); font-size: 11pt; }
.cover .generated { margin-top: 32mm; color: var(--brand-secondary); font-size: 9pt; }
.narrative-section { margin-top: 6mm; page-break-inside: avoid; }
.narrative-section .body { margin: 2mm 0; white-space: pre-wrap; }
.narrative-section ul { margin: 1mm 0 2mm 6mm; padding: 0; }
.narrative-section .citations { color: var(--brand-secondary); font-size: 9pt; font-style: italic; }
.cards { display: flex; gap: 4mm; margin: 4mm 0; flex-wrap: wrap; }
.card { flex: 1 1 0; min-width: 40mm; border: 1px solid #e5e5e5; border-radius: 2mm; padding: 3mm; }
.card .label { color: var(--brand-secondary); font-size: 9pt; text-transform: uppercase; letter-spacing: .04em; }
.card .value { font-size: 14pt; color: var(--brand-primary); font-weight: 600; margin-top: 1mm; }
/* On screen the footer is an ordinary block after the content. In print it
   is hidden and the same text is drawn in the page's bottom margin instead
   (see footerCss): a position:fixed footer is laid out against the page
   CONTENT box, so it prints on top of the last lines of body text. */
footer {
  text-align: center; color: var(--brand-secondary); font-size: 8pt;
  margin-top: 10mm;
}
@media print { footer { display: none; } }
/* Telugu and Kannada stack vowel signs and conjuncts above and below the
   line and Tamil has tall marks too, so their lines get a little more height
   (Devanagari keeps the 1.45 it was tuned for). Letter-spacing and uppercase are Latin
   typography; spacing splits Indic clusters apart, so drop it.
   :where() keeps the specificity of a plain \"body\" rule, so a report's own
   body line-height (Brihad sets 1.8) still wins. */
:where(html:lang(te), html:lang(ta), html:lang(kn), html:lang(ml)) body { line-height: 1.6; }
:where(html:lang(hi), html:lang(mr), html:lang(te), html:lang(ta), html:lang(kn), html:lang(ml)) * "
        . "{ letter-spacing: normal !important; }
";

    private function __construct()
    {
    }

    /**
     * Merge caller branding over the branding embedded in the report JSON, then apply defaults.
     * Caller fields win one by one.
     *
     * @return array{companyName: string, logoUrl: string, primaryColor: string, secondaryColor: string,
     *     footerText: string, watermarkText: string}
     */
    public static function resolveBranding(mixed $raw, ?Branding $override): array
    {
        $r = Js::map($raw);
        $rawStr = static fn (string $key): ?string => isset($r[$key]) && \is_string($r[$key]) ? $r[$key] : null;

        return [
            'companyName' => self::firstNonEmpty($override?->companyName, $rawStr('CompanyName')),
            'logoUrl' => self::safeUrl(self::firstNonEmpty($override?->logoUrl, $rawStr('LogoURL'))),
            'primaryColor' => self::safeColor(
                self::firstNonEmpty($override?->primaryColor, $rawStr('PrimaryColor')),
                self::DEFAULT_PRIMARY,
            ),
            'secondaryColor' => self::safeColor(
                self::firstNonEmpty($override?->secondaryColor, $rawStr('SecondaryColor')),
                self::DEFAULT_SECONDARY,
            ),
            'footerText' => self::firstNonEmpty($override?->footerText, $rawStr('FooterText'), self::DEFAULT_FOOTER),
            'watermarkText' => self::firstNonEmpty($override?->watermarkText, $rawStr('WatermarkText')),
        ];
    }

    public static function firstNonEmpty(?string ...$values): string
    {
        foreach ($values as $v) {
            if ($v !== null && Js::trim($v) !== '') {
                return $v;
            }
        }

        return '';
    }

    private static function safeColor(string $value, string $fallback): string
    {
        $v = Js::trim($value);

        return preg_match(self::SAFE_COLOR, $v) === 1 ? $v : $fallback;
    }

    /** Only http(s), `data:image/...`, relative and protocol-relative logo URLs are kept. */
    private static function safeUrl(string $value): string
    {
        $v = Js::trim($value);
        if ($v === '') {
            return '';
        }
        if (preg_match('/^([a-z][a-z0-9+.-]*):/i', $v, $m) !== 1) {
            return $v;
        }
        $scheme = strtolower($m[1]);
        if ($scheme === 'http' || $scheme === 'https') {
            return $v;
        }

        return preg_match('/^data:image\//i', $v) === 1 ? $v : '';
    }

    /** Escape a value for a CSS string literal (`content: "..."`). */
    public static function cssString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        $s = (string) preg_replace_callback(
            '/[\\\\"<>]/',
            static fn (array $m): string => '\\' . dechex(\ord($m[0])) . ' ',
            Js::str($value),
        );

        return self::replace('/[\x{0000}-\x{001F}\x{2028}\x{2029}]/u', ' ', $s);
    }

    /**
     * Strip "AI-tell" punctuation (em / en dash, arrows) in favour of a comma, and tidy the
     * artefacts that leaves.
     */
    public static function humanize(string $text): string
    {
        $t = Js::trim($text);
        if ($t === '—' || $t === '–') {
            return $text;
        }
        $s = self::replace('/\s*(?:⟶|⇒|→|->|=>)\s*/u', ', ', $text);
        $s = self::replace('/(\S)\s*[—–]\s*(\S)/u', '$1, $2', $s);
        $s = self::replace('/(\S)\s*[—–]\s*$/uD', '$1', $s);
        $s = self::replace('/^\s*[—–]\s*(\S)/u', '$1', $s);

        $s = self::replace('/\s+,/u', ',', $s);
        $s = self::replace('/,\s*,+/u', ', ', $s);
        $s = self::replace('/,\s*([।.!?])/u', '$1', $s);
        $s = self::replace('/([(\[])\s*,\s*/u', '$1', $s);
        $s = self::replace('/\s*,\s*([)\]])/u', '$1', $s);
        $s = self::replace('/^\s*,\s*/u', '', $s, 1);

        return self::replace('/,\s*$/uD', '', $s, 1);
    }

    /**
     * Make an authored narrative body read as a warm, second-person reading: drop the repeated
     * "three levels" filler paragraph and turn "the native" / "जातक" into "you" / "आप".
     */
    public static function cleanNarrative(string $text, string $locale = 'en'): string
    {
        $s = self::replace(
            '/(?:वैदिक\s*ज्योतिष|यह\s*कुंडली|यह\s*स्थिति|शास्त्र)[^।]*?तीन\s*स्तर(?:ों|ां)[\s\S]*?'
            . '(?:शास्त्र\s*है।|शास्त्र\s*आहे\.?|दिशा\s*देने[^।]*।)/u',
            '',
            $text,
        );
        $s = self::replace('/[^।]*इन\s*तीन\s*स्तर(?:ों|ां)[\s\S]*?देख[^।]*।/u', '', $s);
        $s = self::replace(
            '/(In Vedic astrology[^.]*three levels[\s\S]*?(?:guidance\.|direction to life\.))/u',
            '',
            $s,
        );

        if ($locale === 'hi') {
            foreach (
                [
                    '/जातक\s*की/u' => 'आपकी',
                    '/जातक\s*को/u' => 'आपको',
                    '/जातक\s*के/u' => 'आपके',
                    '/जातक\s*का/u' => 'आपका',
                    '/जातक\s*में/u' => 'आपमें',
                    '/जातक\s*पर/u' => 'आप पर',
                    '/जातक\s*स्वयं/u' => 'आप स्वयं',
                    '/जातक/u' => 'आप',
                ] as $re => $to
            ) {
                $s = self::replace($re, $to, $s);
            }
        } elseif ($locale === 'mr') {
            $s = str_replace(
                ['जातकाच्या', 'जातकाला', 'जातकाचे', 'जातकाचा', 'जातक'],
                ['तुमच्या', 'तुम्हाला', 'तुमचे', 'तुमचा', 'तुम्ही'],
                $s,
            );
        } elseif (\in_array($locale, ['te', 'ta', 'kn', 'ml', 'gu', 'bn'], true)) {
            // Deliberately no rewrite: these locales carry the person in the verb ending, and the
            // corpus already writes them in the second person.
        } else {
            $b = Js::WB;
            foreach (
                [
                    "/{$b}the native's{$b}/u" => 'your',
                    "/{$b}The native's{$b}/u" => 'Your',
                    "/{$b}the native{$b}/u" => 'you',
                    "/{$b}The native{$b}/u" => 'You',
                    "/{$b}native's{$b}/u" => 'your',
                    "/{$b}native{$b}/u" => 'you',
                ] as $re => $to
            ) {
                $s = self::replace($re, $to, $s);
            }
        }

        $s = self::replace('/\n{3,}/u', "\n\n", $s);
        $s = self::replace('/[ \t]+\n/u', "\n", $s);

        return Js::trim($s);
    }

    /**
     * preg_replace that keeps the input when PCRE gives up (backtrack or JIT limits on a very
     * large text) instead of returning null and losing the text.
     */
    private static function replace(string $pattern, string $replacement, string $subject, int $limit = -1): string
    {
        $out = preg_replace($pattern, $replacement, $subject, $limit);

        return $out ?? $subject;
    }

    /** HTML-escape a value (after humanizing). null -> ''. */
    public static function esc(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return strtr(self::humanize(Js::str($value)), self::ESC);
    }

    /** HTML-escape a narrative body: clean the tone first (locale-aware), then esc. */
    public static function escBody(mixed $value, string $locale = 'en'): string
    {
        if ($value === null) {
            return '';
        }

        return self::esc(self::cleanNarrative(Js::str($value), $locale));
    }

    /** Join a list of strings with a separator, escaping each. */
    public static function escList(mixed $items, string $sep = '; '): string
    {
        if (!Js::isList($items)) {
            return '';
        }

        return implode($sep, array_map(static fn (mixed $i): string => self::esc($i), Js::list($items)));
    }

    /** A value, or a dash when it is empty. */
    public static function orDash(mixed $value): string
    {
        $s = self::esc($value);

        return $s === '' ? '—' : $s;
    }

    /** @param array{watermarkText: string} $b */
    private static function watermarkCss(array $b): string
    {
        if ($b['watermarkText'] === '') {
            return '';
        }

        return '
body::before {
  content: "' . self::cssString($b['watermarkText']) . '";
  position: fixed; top: 50%; left: 50%;
  transform: translate(-50%, -50%) rotate(-30deg);
  font-size: 80pt; color: rgba(0,0,0,0.06); font-weight: 700;
  pointer-events: none; z-index: 0;
}';
    }

    /** @param array{footerText: string, secondaryColor: string} $b */
    private static function footerCss(array $b): string
    {
        if ($b['footerText'] === '') {
            return '';
        }

        return '
@page {
  @bottom-center {
    content: "' . self::cssString($b['footerText']) . '";
    font-family: ' . Locales::INDIC_FONT_STACK . ';
    font-size: 8pt;
    color: ' . self::cssColor($b['secondaryColor']) . ';
  }
}';
    }

    private static function cssColor(string $value): string
    {
        $v = Js::trim($value);

        return preg_match('/^(#[0-9a-fA-F]{3,8}|[a-zA-Z]+|rgba?\([0-9.,%\s]+\)|hsla?\([0-9.,%\sdeg]+\))$/uD', $v) === 1
            ? $v
            : '#666666';
    }

    /**
     * The logo + company-name block for a cover page.
     *
     * @param array{logoUrl: string, companyName: string} $b
     */
    public static function coverBrandingHtml(array $b): string
    {
        $html = '';
        if ($b['logoUrl'] !== '') {
            $html .= '<img src="' . self::esc($b['logoUrl']) . '" alt="" class="logo">';
        }
        if ($b['companyName'] !== '') {
            $html .= '<div class="company">' . self::esc($b['companyName']) . '</div>';
        }

        return $html;
    }

    /**
     * The footer line shown after the content on screen (print uses the page-margin footer).
     *
     * @param array{footerText: string} $b
     */
    public static function footerHtml(array $b): string
    {
        return $b['footerText'] !== '' ? '<footer>' . self::esc($b['footerText']) . '</footer>' : '';
    }

    /**
     * Wrap a report body in a complete, self-contained HTML document.
     *
     * @param array{companyName: string, logoUrl: string, primaryColor: string, secondaryColor: string,
     *     footerText: string, watermarkText: string} $branding
     */
    public static function layoutDocument(
        string $title,
        array $branding,
        string $bodyHtml,
        ?string $lang = 'en',
        string $extraCss = '',
        bool $pageFooter = true,
    ): string {
        $langAttr = self::esc($lang ?? 'en');

        return '<!doctype html>
<html lang="' . ($langAttr !== '' ? $langAttr : 'en') . '">
<head>
<meta charset="utf-8">
<title>' . self::esc($title) . '</title>
<style>
:root { --brand-primary: ' . self::esc($branding['primaryColor']) . '; --brand-secondary: '
            . self::esc($branding['secondaryColor']) . '; }
' . self::BASE_CSS . '
' . self::watermarkCss($branding) . '
' . ($pageFooter ? self::footerCss($branding) : '') . '
' . $extraCss . '
</style>
</head>
<body>
' . $bodyHtml . '
' . self::footerHtml($branding) . '
</body>
</html>';
    }

    /** Render a list of authored narrative sections (heading + body + bullets + citations). */
    public static function narrativeSectionsHtml(mixed $sections, string $sourcesLabel = 'Sources'): string
    {
        if (!Js::nonEmptyList($sections)) {
            return '';
        }
        $out = [];
        foreach (Js::list($sections) as $section) {
            $s = Js::map($section);
            $bullets = Js::nonEmptyList($s['Bullets'] ?? null)
                ? '<ul>' . implode('', array_map(
                    static fn (mixed $b): string => '<li>' . self::esc($b) . '</li>',
                    Js::list($s['Bullets']),
                )) . '</ul>'
                : '';
            $cites = Js::nonEmptyList($s['Citations'] ?? null)
                ? '<div class="citations">' . self::esc($sourcesLabel) . ': ' . self::escList($s['Citations'])
                    . '</div>'
                : '';
            $out[] = '<div class="narrative-section"><h3>' . self::esc($s['Heading'] ?? null)
                . '</h3><div class="body">'
                . self::esc($s['Body'] ?? null) . '</div>' . $bullets . $cites . '</div>';
        }

        return implode("\n", $out);
    }

    /**
     * A localized label lookup over a per-locale table, falling back to English.
     *
     * @param array<string, array<string, string>> $labels
     *
     * @return \Closure(string): string
     */
    public static function makeLabeler(array $labels, mixed $locale): \Closure
    {
        $loc = \is_string($locale) && $locale !== '' && isset($labels[$locale]) ? $locale : 'en';
        $table = $labels[$loc] ?? $labels['en'] ?? [];
        $fallback = $labels['en'] ?? [];

        return static fn (string $key): string => $table[$key] ?? $fallback[$key] ?? '';
    }
}
