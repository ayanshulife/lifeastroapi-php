<?php

declare(strict_types=1);

namespace LifeAstroApi\Pdf\Templates;

use LifeAstroApi\Pdf\Js;
use LifeAstroApi\Pdf\Locales;

/**
 * Inline SVG planet glyphs for the "Graha Sthiti" planet-cards grid. A port of the Node.js SDK's
 * src/pdf/templates/planetGlyphs.ts (byte-identical output, golden-tested).
 *
 * Self-contained (no external image assets) so the PDF renders anywhere: each glyph is a small
 * stylized disc/icon in the planet's conventional colour.
 *
 * @internal Used by the Brihad template.
 */
final class PlanetGlyphs
{
    public const PLANET_GLYPHS = [
        'Sun' => '<svg viewBox="0 0 48 48" width="34" height="34" xmlns="http://www.w3.org/2000/svg">
    <g stroke="#e8821b" stroke-width="2" stroke-linecap="round">
      <line x1="41.0" y1="24.0" x2="46.0" y2="24.0"/><line x1="38.7" y1="32.5" x2="43.1" y2="35.0"/><line x1="32.5" y1="38.7" x2="35.0" y2="43.1"/><line x1="24.0" y1="41.0" x2="24.0" y2="46.0"/><line x1="15.5" y1="38.7" x2="13.0" y2="43.1"/><line x1="9.3" y1="32.5" x2="4.9" y2="35.0"/><line x1="7.0" y1="24.0" x2="2.0" y2="24.0"/><line x1="9.3" y1="15.5" x2="4.9" y2="13.0"/><line x1="15.5" y1="9.3" x2="13.0" y2="4.9"/><line x1="24.0" y1="7.0" x2="24.0" y2="2.0"/><line x1="32.5" y1="9.3" x2="35.0" y2="4.9"/><line x1="38.7" y1="15.5" x2="43.1" y2="13.0"/>
    </g>
    <circle cx="24" cy="24" r="13" fill="#f6a623"/>
    <circle cx="24" cy="24" r="13" fill="none" stroke="#e8821b" stroke-width="1.5"/></svg>',
        'Moon' => '<svg viewBox="0 0 48 48" width="34" height="34" xmlns="http://www.w3.org/2000/svg">
    <circle cx="24" cy="24" r="15" fill="#d7dbe0"/>
    <circle cx="29" cy="24" r="13" fill="#ffffff"/>
    <circle cx="19" cy="20" r="2" fill="#c2c8d0"/><circle cx="17" cy="27" r="1.4" fill="#c2c8d0"/></svg>',
        'Mars' => '<svg viewBox="0 0 48 48" width="34" height="34" xmlns="http://www.w3.org/2000/svg">
    <circle cx="24" cy="24" r="15" fill="#c0392b"/>
    <ellipse cx="20" cy="20" rx="6" ry="3" fill="#a93226" transform="rotate(-20 20 20)"/>
    <ellipse cx="28" cy="29" rx="5" ry="2.5" fill="#e06352"/></svg>',
        'Mercury' => '<svg viewBox="0 0 48 48" width="34" height="34" xmlns="http://www.w3.org/2000/svg">
    <circle cx="24" cy="24" r="15" fill="#9aa3ab"/>
    <circle cx="19" cy="20" r="3" fill="#828b94"/><circle cx="30" cy="28" r="2" fill="#828b94"/></svg>',
        'Jupiter' => '<svg viewBox="0 0 48 48" width="34" height="34" xmlns="http://www.w3.org/2000/svg">
    <circle cx="24" cy="24" r="15" fill="#d9a441"/>
    <path d="M9 19 H39 M9 24 H39 M11 29 H37" stroke="#b8860b" stroke-width="2.4" fill="none"/>
    <ellipse cx="28" cy="27" rx="3.5" ry="2" fill="#a8521b"/></svg>',
        'Venus' => '<svg viewBox="0 0 48 48" width="34" height="34" xmlns="http://www.w3.org/2000/svg">
    <circle cx="24" cy="24" r="15" fill="#e9d27a"/>
    <ellipse cx="22" cy="22" rx="7" ry="4" fill="#f0dd9a" transform="rotate(-15 22 22)"/></svg>',
        'Saturn' => '<svg viewBox="0 0 48 48" width="34" height="34" xmlns="http://www.w3.org/2000/svg">
    <ellipse cx="24" cy="24" rx="22" ry="7" fill="none" stroke="#b0884a" stroke-width="2.4" transform="rotate(-18 24 24)"/>
    <circle cx="24" cy="24" r="12" fill="#cdb06b"/></svg>',
        'Rahu' => '<svg viewBox="0 0 48 48" width="34" height="34" xmlns="http://www.w3.org/2000/svg">
    <circle cx="24" cy="24" r="16" fill="#6d4c8c"/>
    <path d="M16 30 q-2 -12 8 -12 q10 0 8 12" fill="none" stroke="#fff" stroke-width="2.6" stroke-linecap="round"/>
    <circle cx="24" cy="30" r="2.4" fill="#fff"/></svg>',
        'Ketu' => '<svg viewBox="0 0 48 48" width="34" height="34" xmlns="http://www.w3.org/2000/svg">
    <circle cx="24" cy="24" r="16" fill="#0f8a7e"/>
    <path d="M16 18 q-2 12 8 12 q10 0 8 -12" fill="none" stroke="#fff" stroke-width="2.6" stroke-linecap="round"/>
    <circle cx="24" cy="18" r="2.4" fill="#fff"/></svg>',
        'Uranus' => '<svg viewBox="0 0 48 48" width="34" height="34" xmlns="http://www.w3.org/2000/svg"><circle cx="24" cy="24" r="15" fill="#6fd0d6"/><ellipse cx="24" cy="24" rx="20" ry="5" fill="none" stroke="#3fa9b0" stroke-width="2" transform="rotate(70 24 24)"/></svg>',
        'Neptune' => '<svg viewBox="0 0 48 48" width="34" height="34" xmlns="http://www.w3.org/2000/svg"><circle cx="24" cy="24" r="15" fill="#3f6fd6"/><path d="M12 22 q12 6 24 0" stroke="#2a52b0" stroke-width="2.2" fill="none"/></svg>',
        'Pluto' => '<svg viewBox="0 0 48 48" width="34" height="34" xmlns="http://www.w3.org/2000/svg"><circle cx="24" cy="24" r="13" fill="#9b7b6b"/></svg>',
    ];

    private const HI_TO_EN = [
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
        'अरुण' => 'Uranus',
        'वरुण' => 'Neptune',
        'यम' => 'Pluto',
    ];

    private function __construct()
    {
    }

    /** The glyph SVG for a planet name (English, Devanagari or te/ta/kn/ml/gu/bn), or ''. */
    public static function planetGlyph(mixed $name): string
    {
        if (!Js::truthy($name)) {
            return '';
        }
        $n = Js::str($name);
        $key = isset(self::PLANET_GLYPHS[$n])
            ? $n
            : (self::HI_TO_EN[$n] ?? Js::str(Js::or(Locales::southPlanetToEn($n), $n)));

        return self::PLANET_GLYPHS[$key] ?? '';
    }
}
