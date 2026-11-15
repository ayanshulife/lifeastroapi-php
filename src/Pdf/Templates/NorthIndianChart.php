<?php

declare(strict_types=1);

namespace LifeAstroApi\Pdf\Templates;

use LifeAstroApi\Pdf\Js;
use LifeAstroApi\Pdf\Locales;
use LifeAstroApi\Pdf\Shared;

/**
 * North-Indian (diamond) chart SVG generator. A port of the Node.js SDK's
 * src/pdf/templates/northIndianChart.ts (byte-identical output, golden-tested).
 *
 * The North-Indian kundli is a fixed diamond: an outer square crossed by both diagonals, with a
 * rotated inner square joining the four edge-midpoints. This makes 12 fixed house cells. House 1
 * (lagna) is the top-centre cell; houses run anticlockwise. The sign number printed in each cell
 * is fixed by the lagna (house-1 sign), then +1 per house. Planets are drawn in their house.
 *
 * @internal Used by the Brihad template.
 */
final class NorthIndianChart
{
    private const HOUSE_XY = [
        1 => [
            50,
            25,
        ],
        2 => [
            25,
            8,
        ],
        3 => [
            9,
            25,
        ],
        4 => [
            25,
            50,
        ],
        5 => [
            9,
            75,
        ],
        6 => [
            25,
            92,
        ],
        7 => [
            50,
            75,
        ],
        8 => [
            75,
            92,
        ],
        9 => [
            91,
            75,
        ],
        10 => [
            75,
            50,
        ],
        11 => [
            91,
            25,
        ],
        12 => [
            75,
            8,
        ],
    ];

    public const PLANET_LABELS_EN = [
        'Sun' => 'Su',
        'Moon' => 'Mo',
        'Mars' => 'Ma',
        'Mercury' => 'Me',
        'Jupiter' => 'Ju',
        'Venus' => 'Ve',
        'Saturn' => 'Sa',
        'Rahu' => 'Ra',
        'Ketu' => 'Ke',
        'Uranus' => 'Ur',
        'Neptune' => 'Ne',
        'Pluto' => 'Pl',
    ];

    private const PLANET_LABELS_HI = [
        'Sun' => 'सू',
        'Moon' => 'चं',
        'Mars' => 'मं',
        'Mercury' => 'बु',
        'Jupiter' => 'गु',
        'Venus' => 'शु',
        'Saturn' => 'श',
        'Rahu' => 'रा',
        'Ketu' => 'के',
        'Uranus' => 'यू',
        'Neptune' => 'ने',
        'Pluto' => 'प्लू',
    ];

    private const PLANET_LABELS_TE = [
        'Sun' => 'సూ',
        'Moon' => 'చం',
        'Mars' => 'కు',
        'Mercury' => 'బు',
        'Jupiter' => 'గు',
        'Venus' => 'శు',
        'Saturn' => 'శ',
        'Rahu' => 'రా',
        'Ketu' => 'కే',
        'Uranus' => 'యు',
        'Neptune' => 'నె',
        'Pluto' => 'ప్లు',
    ];

    private const PLANET_LABELS_TA = [
        'Sun' => 'சூ',
        'Moon' => 'சந்',
        'Mars' => 'செ',
        'Mercury' => 'பு',
        'Jupiter' => 'கு',
        'Venus' => 'சு',
        'Saturn' => 'சனி',
        'Rahu' => 'ரா',
        'Ketu' => 'கே',
        'Uranus' => 'யு',
        'Neptune' => 'நெ',
        'Pluto' => 'ப்ளு',
    ];

    private const PLANET_LABELS_KN = [
        'Sun' => 'ಸೂ',
        'Moon' => 'ಚಂ',
        'Mars' => 'ಮಂ',
        'Mercury' => 'ಬು',
        'Jupiter' => 'ಗು',
        'Venus' => 'ಶು',
        'Saturn' => 'ಶ',
        'Rahu' => 'ರಾ',
        'Ketu' => 'ಕೇ',
        'Uranus' => 'ಯು',
        'Neptune' => 'ನೆ',
        'Pluto' => 'ಪ್ಲು',
    ];

    private const PLANET_LABELS_ML = [
        'Sun' => 'സൂ',
        'Moon' => 'ച',
        'Mars' => 'ചൊ',
        'Mercury' => 'ബു',
        'Jupiter' => 'വ്യാ',
        'Venus' => 'ശു',
        'Saturn' => 'ശ',
        'Rahu' => 'രാ',
        'Ketu' => 'കേ',
        'Uranus' => 'യു',
        'Neptune' => 'നെ',
        'Pluto' => 'പ്ലൂ',
    ];

    private const PLANET_LABELS_GU = [
        'Sun' => 'સૂ',
        'Moon' => 'ચં',
        'Mars' => 'મં',
        'Mercury' => 'બુ',
        'Jupiter' => 'ગુ',
        'Venus' => 'શુ',
        'Saturn' => 'શ',
        'Rahu' => 'રા',
        'Ketu' => 'કે',
        'Uranus' => 'યુ',
        'Neptune' => 'ને',
        'Pluto' => 'પ્લૂ',
    ];

    private const PLANET_LABELS_BN = [
        'Sun' => 'সূ',
        'Moon' => 'চ',
        'Mars' => 'ম',
        'Mercury' => 'বু',
        'Jupiter' => 'বৃ',
        'Venus' => 'শু',
        'Saturn' => 'শ',
        'Rahu' => 'রা',
        'Ketu' => 'কে',
        'Uranus' => 'ইউ',
        'Neptune' => 'নে',
        'Pluto' => 'প্লু',
    ];

    public const PLANET_COLORS = [
        'Sun' => '#e05e18',
        'Moon' => '#3a7bd5',
        'Mars' => '#c0392b',
        'Mercury' => '#27928a',
        'Jupiter' => '#b8860b',
        'Venus' => '#c026a0',
        'Saturn' => '#2c3e50',
        'Rahu' => '#6d4c41',
        'Ketu' => '#00897b',
    ];

    private function __construct()
    {
    }

    /**
     * Render a North-Indian diamond chart as an inline SVG string.
     *
     * @param int $lagnaSignIndex 0-based sign index of house 1 (the lagna sign). Aries = 0.
     * @param list<array{label: string, house: mixed, retro?: mixed, color?: string|null}> $planets
     *     One planet to place: short label, house (1..12, as in the JSON), optional colour.
     * @param string|null $title Chart title shown above (e.g. "Lagna Kundali").
     * @param int|float|null $size px size of the square. Default 200.
     * @param string|null $accent Accent colour for the diamond strokes + sign numbers.
     */
    public static function northIndianChartSvg(
        int $lagnaSignIndex,
        array $planets,
        ?string $title = null,
        int|float|null $size = null,
        ?string $accent = null,
    ): string {
        $size ??= 200;
        $accent ??= '#e0701c';
        $signColor = '#5a5a5a';
        $lagna = (($lagnaSignIndex % 12) + 12) % 12;

        // Group planet labels by house (a JS Map: first-seen order, keys compared by value and type).
        /** @var array<string, array{house: mixed, planets: list<array{label: string, house: mixed, retro?: mixed, color?: string|null}>}> $byHouse */
        $byHouse = [];
        foreach ($planets as $i => $p) {
            $n = self::jsNumber($p['house']);
            if ($n < 1 || $n > 12) {
                continue;
            }
            $key = self::mapKey($p['house'], $i);
            $byHouse[$key] ??= ['house' => $p['house'], 'planets' => []];
            $byHouse[$key]['planets'][] = $p;
        }

        // Frame: outer square + two diagonals + inner diamond (edge midpoints).
        $frame = '
    <rect x="1" y="1" width="98" height="98" fill="none" stroke="' . $accent . '" stroke-width="0.8"/>
    <line x1="1" y1="1" x2="99" y2="99" stroke="' . $accent . '" stroke-width="0.5"/>
    <line x1="99" y1="1" x2="1" y2="99" stroke="' . $accent . '" stroke-width="0.5"/>
    <polygon points="50,1 99,50 50,99 1,50" fill="none" stroke="' . $accent . '" stroke-width="0.5"/>';

        // Sign (rashi) number per house, just above the cell centre so planets sit below it. For
        // the two top corner-triangle houses (centre close to the top edge) it is nudged down.
        $signs = '';
        for ($h = 1; $h <= 12; $h++) {
            $sign = (($lagna + $h - 1) % 12) + 1;
            [$cx, $cy] = self::HOUSE_XY[$h];
            $ny = $cy < 12 ? $cy + 2.5 : $cy - 6;
            $signs .= '<text x="' . $cx . '" y="' . Js::str($ny) . '" font-size="4.4" fill="' . $signColor
                . '" font-weight="600" text-anchor="middle" dominant-baseline="middle">' . $sign . '</text>';
        }

        // Planets per house (stacked, wrapping 3 per line).
        $planetsSvg = '';
        foreach ($byHouse as $group) {
            $h = Js::str($group['house']);
            $pos = preg_match('/^(?:[1-9]|1[0-2])$/D', $h) === 1 ? self::HOUSE_XY[(int) $h] : null;
            if ($pos === null) {
                continue;
            }
            [$cx, $cy] = $pos;
            $arr = $group['planets'];
            $labels = array_map(static fn (array $p): string => $p['label'], $arr);
            $perRow = 3;
            $rows = array_chunk($labels, $perRow);
            $lineH = 4.6;
            // Planets sit below the sign number; in the top-edge corner cells they start lower.
            $topEdge = $cy < 12;
            $anchor = $topEdge ? $cy + 9 : $cy + 1.5;
            $startY = $anchor - ((\count($rows) - 1) * $lineH) / 2;
            foreach ($rows as $ri => $row) {
                $y = $startY + $ri * $lineH;
                $cells = [];
                foreach ($row as $ci => $lbl) {
                    $col = $arr[$ri * $perRow + $ci]['color'] ?? '#1a2a6c';
                    $cells[] = '<tspan fill="' . $col . '">' . Shared::esc($lbl) . '</tspan>';
                }
                $planetsSvg .= '<text x="' . $cx . '" y="' . Js::str($y)
                    . '" font-size="4.6" font-weight="bold" text-anchor="middle" dominant-baseline="middle">'
                    . implode('<tspan> </tspan>', $cells) . '</text>';
            }
        }

        $titleHtml = Js::truthy($title)
            ? '<div style="font-size:10pt;font-weight:700;color:' . $accent . ';margin-bottom:1.5mm;">'
                . Shared::esc($title) . '</div>'
            : '';

        return '<div class="north-chart" style="display:inline-block;text-align:center;margin:2mm 3mm;">
' . $titleHtml . '
<svg viewBox="0 0 100 100" width="' . Js::str($size) . '" height="' . Js::str($size) . '" style="font-family:'
            . Locales::INDIC_FONT_STACK_ATTR . ';">
' . $frame . '
' . $signs . '
' . $planetsSvg . '
</svg>
</div>';
    }

    /**
     * The chart-cell label table for a locale (English abbreviations by default).
     *
     * @return array<string, string>
     */
    public static function planetLabelsFor(?string $locale): array
    {
        return match ($locale) {
            'hi', 'mr' => self::PLANET_LABELS_HI,
            'te' => self::PLANET_LABELS_TE,
            'ta' => self::PLANET_LABELS_TA,
            'kn' => self::PLANET_LABELS_KN,
            'ml' => self::PLANET_LABELS_ML,
            'gu' => self::PLANET_LABELS_GU,
            'bn' => self::PLANET_LABELS_BN,
            default => self::PLANET_LABELS_EN,
        };
    }

    /** JS `Number(v)` for a JSON value (NAN when JS gives NaN). */
    public static function jsNumber(mixed $v): int|float
    {
        if (\is_int($v) || \is_float($v)) {
            return $v;
        }
        if (\is_bool($v)) {
            return $v ? 1 : 0;
        }
        if ($v === null) {
            return 0;
        }
        if (\is_array($v)) {
            return array_is_list($v) ? self::jsNumber(Js::str($v)) : NAN;
        }
        if (!\is_string($v)) {
            return NAN;
        }
        $s = Js::trim($v);
        if ($s === '') {
            return 0;
        }
        if (preg_match('/^[+-]?(?:[0-9]+\.?[0-9]*|\.[0-9]+)(?:[eE][+-]?[0-9]+)?$/D', $s) === 1) {
            return (float) $s;
        }
        if (preg_match('/^[+-]?Infinity$/D', $s) === 1) {
            return $s[0] === '-' ? -INF : INF;
        }
        if (preg_match('/^0([xXoObB])([0-9a-fA-F]+)$/D', $s, $m) === 1) {
            $base = match (strtolower($m[1])) {
                'x' => 16,
                'o' => 8,
                default => 2,
            };
            $digits = strtolower($m[2]);
            $valid = $base === 16 ? '0123456789abcdef' : substr('01234567', 0, $base);
            if (strspn($digits, $valid) !== \strlen($digits)) {
                return NAN;
            }

            return (int) base_convert($digits, $base, 10);
        }

        return NAN;
    }

    /** The key a JS Map uses for a value (SameValueZero): numbers and strings never collide. */
    private static function mapKey(mixed $v, int $index): string
    {
        return match (true) {
            \is_int($v), \is_float($v) => 'n:' . Js::str($v),
            \is_string($v) => 's:' . $v,
            \is_bool($v) => $v ? 'true' : 'false',
            $v === null => 'null',
            // Objects and arrays are compared by identity: every planet row has its own.
            default => 'o:' . $index,
        };
    }
}
