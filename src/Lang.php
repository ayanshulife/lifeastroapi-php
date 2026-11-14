<?php

declare(strict_types=1);

namespace LifeAstroApi;

/**
 * Response language, passed as `lang` on Vedic methods (panchang, calendar, festivals, eclipses,
 * planet moments, transit, milan, muhurta, chart, prashna, numerology, dasha, varshaphal,
 * ashtakavarga, Lal Kitab, horoscope, reports, narrative, remedies).
 *
 * - Complete: `en`, `hi`, `mr`, `gu`, `bn`, `te`, `ta`, `kn`, `ml`. Names, terms and prose all
 *   come back in the language.
 * - Partial: `pa`. Names and terms come back in the language; longer prose is still English.
 *
 * Ids, dates, numbers and explicit `*_en` / `*_hi` fields stay the same in every language.
 * Western, tarot, geo and meta methods answer in English.
 *
 * Every method that takes `lang` also accepts the plain code: `Lang::Gu` and `'gu'` are the same.
 */
enum Lang: string
{
    case En = 'en';
    case Hi = 'hi';
    case Mr = 'mr';
    case Gu = 'gu';
    case Bn = 'bn';
    case Te = 'te';
    case Ta = 'ta';
    case Kn = 'kn';
    case Ml = 'ml';
    case Pa = 'pa';

    /**
     * Whether prose (readings, descriptions) comes back in this language too, not only names
     * and terms. True for every language except Punjabi (`pa`).
     */
    public function isComplete(): bool
    {
        return $this !== self::Pa;
    }
}
