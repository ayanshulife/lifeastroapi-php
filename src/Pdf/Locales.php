<?php

declare(strict_types=1);

namespace LifeAstroApi\Pdf;

/**
 * Locale tables shared by the PDF templates. A port of the Node.js SDK's src/pdf/locales.ts (the
 * tables are copied from it with tool/ts-tables.mjs).
 *
 * @internal
 */
final class Locales
{
    /** Locales written in Devanagari (the API's `*HI` fields cover these). */
    public const DEVANAGARI_LOCALES = ['hi', 'mr'];

    /** Own-script (non-Devanagari) locales with their own label tables. */
    public const SOUTH_LOCALES = ['te', 'ta', 'kn', 'ml', 'gu', 'bn'];

    /** Every Indic locale the PDF templates carry labels for. */
    public const INDIC_LOCALES = ['hi', 'mr', 'te', 'ta', 'kn', 'ml', 'gu', 'bn'];

    /** Font stack with every Indic script the API can emit. */
    public const INDIC_FONT_STACK = "'Noto Sans', 'Noto Sans Devanagari', 'Noto Sans Telugu', 'Noto Sans Tamil', "
        . "'Noto Sans Kannada', 'Noto Sans Bengali', 'Noto Sans Gujarati', 'Noto Sans Gurmukhi', "
        . "'Noto Sans Malayalam', sans-serif";

    /** Same stack without quotes, safe inside an HTML `style="..."` attribute. */
    public const INDIC_FONT_STACK_ATTR = 'Noto Sans, Noto Sans Devanagari, Noto Sans Telugu, Noto Sans Tamil, '
        . 'Noto Sans Kannada, Noto Sans Bengali, Noto Sans Gujarati, Noto Sans Gurmukhi, '
        . 'Noto Sans Malayalam, sans-serif';

    /**
     * A Brihad Part heading ("Part 1: ...", "भाग 1: ...", ...): the prefix followed by whitespace or
     * a digit (JS `\b` never matches after an Indic letter).
     */
    public const PART_PREFIX_RE = '/^(part|भाग|భాగం|பகுதி|ಭಾಗ|ഭാഗം|ભાગ|ভাগ)[\s0-9]/ui';

    public const PLANET_NAMES = [
        'te' => [
            'Sun' => 'సూర్యుడు',
            'Moon' => 'చంద్రుడు',
            'Mars' => 'కుజుడు',
            'Mercury' => 'బుధుడు',
            'Jupiter' => 'గురువు',
            'Venus' => 'శుక్రుడు',
            'Saturn' => 'శని',
            'Rahu' => 'రాహువు',
            'Ketu' => 'కేతువు',
            'Lagna' => 'లగ్నం',
        ],
        'ta' => [
            'Sun' => 'சூரியன்',
            'Moon' => 'சந்திரன்',
            'Mars' => 'செவ்வாய்',
            'Mercury' => 'புதன்',
            'Jupiter' => 'குரு',
            'Venus' => 'சுக்கிரன்',
            'Saturn' => 'சனி',
            'Rahu' => 'ராகு',
            'Ketu' => 'கேது',
            'Lagna' => 'லக்னம்',
        ],
        'kn' => [
            'Sun' => 'ಸೂರ್ಯ',
            'Moon' => 'ಚಂದ್ರ',
            'Mars' => 'ಮಂಗಳ',
            'Mercury' => 'ಬುಧ',
            'Jupiter' => 'ಗುರು',
            'Venus' => 'ಶುಕ್ರ',
            'Saturn' => 'ಶನಿ',
            'Rahu' => 'ರಾಹು',
            'Ketu' => 'ಕೇತು',
            'Lagna' => 'ಲಗ್ನ',
        ],
        'ml' => [
            'Sun' => 'സൂര്യൻ',
            'Moon' => 'ചന്ദ്രൻ',
            'Mars' => 'ചൊവ്വ',
            'Mercury' => 'ബുധൻ',
            'Jupiter' => 'വ്യാഴം',
            'Venus' => 'ശുക്രൻ',
            'Saturn' => 'ശനി',
            'Rahu' => 'രാഹു',
            'Ketu' => 'കേതു',
            'Lagna' => 'ലഗ്നം',
        ],
        'gu' => [
            'Sun' => 'સૂર્ય',
            'Moon' => 'ચંદ્ર',
            'Mars' => 'મંગળ',
            'Mercury' => 'બુધ',
            'Jupiter' => 'ગુરુ',
            'Venus' => 'શુક્ર',
            'Saturn' => 'શનિ',
            'Rahu' => 'રાહુ',
            'Ketu' => 'કેતુ',
            'Lagna' => 'લગ્ન',
        ],
        'bn' => [
            'Sun' => 'সূর্য',
            'Moon' => 'চন্দ্র',
            'Mars' => 'মঙ্গল',
            'Mercury' => 'বুধ',
            'Jupiter' => 'বৃহস্পতি',
            'Venus' => 'শুক্র',
            'Saturn' => 'শনি',
            'Rahu' => 'রাহু',
            'Ketu' => 'কেতু',
            'Lagna' => 'লগ্ন',
        ],
    ];

    public const YOGINI_NAMES = [
        'te' => [
            'Mangala' => 'మంగళా',
            'Pingala' => 'పింగళా',
            'Dhanya' => 'ధాన్యా',
            'Bhramari' => 'భ్రమరి',
            'Bhadrika' => 'భద్రికా',
            'Ulka' => 'ఉల్కా',
            'Siddha' => 'సిద్ధా',
            'Sankata' => 'సంకటా',
        ],
        'ta' => [
            'Mangala' => 'மங்களா',
            'Pingala' => 'பிங்களா',
            'Dhanya' => 'தான்யா',
            'Bhramari' => 'ப்ரமரி',
            'Bhadrika' => 'பத்ரிகா',
            'Ulka' => 'உல்கா',
            'Siddha' => 'சித்தா',
            'Sankata' => 'சங்கடா',
        ],
        'kn' => [
            'Mangala' => 'ಮಂಗಳಾ',
            'Pingala' => 'ಪಿಂಗಳಾ',
            'Dhanya' => 'ಧಾನ್ಯಾ',
            'Bhramari' => 'ಭ್ರಮರಿ',
            'Bhadrika' => 'ಭದ್ರಿಕಾ',
            'Ulka' => 'ಉಲ್ಕಾ',
            'Siddha' => 'ಸಿದ್ಧಾ',
            'Sankata' => 'ಸಂಕಟಾ',
        ],
        'ml' => [
            'Mangala' => 'മംഗള',
            'Pingala' => 'പിംഗള',
            'Dhanya' => 'ധാന്യ',
            'Bhramari' => 'ഭ്രമരി',
            'Bhadrika' => 'ഭദ്രിക',
            'Ulka' => 'ഉൽക',
            'Siddha' => 'സിദ്ധ',
            'Sankata' => 'സങ്കട',
        ],
        'gu' => [
            'Mangala' => 'મંગલા',
            'Pingala' => 'પિંગલા',
            'Dhanya' => 'ધાન્યા',
            'Bhramari' => 'ભ્રમરી',
            'Bhadrika' => 'ભદ્રિકા',
            'Ulka' => 'ઉલ્કા',
            'Siddha' => 'સિદ્ધા',
            'Sankata' => 'સંકટા',
        ],
        'bn' => [
            'Mangala' => 'মঙ্গলা',
            'Pingala' => 'পিঙ্গলা',
            'Dhanya' => 'ধান্যা',
            'Bhramari' => 'ভ্রমরী',
            'Bhadrika' => 'ভদ্রিকা',
            'Ulka' => 'উল্কা',
            'Siddha' => 'সিদ্ধা',
            'Sankata' => 'সঙ্কটা',
        ],
    ];

    public const RASHI_NAMES = [
        'te' => [
            'మేషం',
            'వృషభం',
            'మిథునం',
            'కర్కాటకం',
            'సింహం',
            'కన్య',
            'తుల',
            'వృశ్చికం',
            'ధనుస్సు',
            'మకరం',
            'కుంభం',
            'మీనం',
        ],
        'ta' => [
            'மேஷம்',
            'ரிஷபம்',
            'மிதுனம்',
            'கடகம்',
            'சிம்மம்',
            'கன்னி',
            'துலாம்',
            'விருச்சிகம்',
            'தனுசு',
            'மகரம்',
            'கும்பம்',
            'மீனம்',
        ],
        'kn' => [
            'ಮೇಷ',
            'ವೃಷಭ',
            'ಮಿಥುನ',
            'ಕರ್ಕಾಟಕ',
            'ಸಿಂಹ',
            'ಕನ್ಯಾ',
            'ತುಲಾ',
            'ವೃಶ್ಚಿಕ',
            'ಧನು',
            'ಮಕರ',
            'ಕುಂಭ',
            'ಮೀನ',
        ],
        'ml' => [
            'മേടം',
            'ഇടവം',
            'മിഥുനം',
            'കർക്കടകം',
            'ചിങ്ങം',
            'കന്നി',
            'തുലാം',
            'വൃശ്ചികം',
            'ധനു',
            'മകരം',
            'കുംഭം',
            'മീനം',
        ],
        'gu' => [
            'મેષ',
            'વૃષભ',
            'મિથુન',
            'કર્ક',
            'સિંહ',
            'કન્યા',
            'તુલા',
            'વૃશ્ચિક',
            'ધનુ',
            'મકર',
            'કુંભ',
            'મીન',
        ],
        'bn' => [
            'মেষ',
            'বৃষ',
            'মিথুন',
            'কর্কট',
            'সিংহ',
            'কন্যা',
            'তুলা',
            'বৃশ্চিক',
            'ধনু',
            'মকর',
            'কুম্ভ',
            'মীন',
        ],
    ];

    private const WEEKDAYS_EN = [
        'Sunday',
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
    ];

    public const WEEKDAY_NAMES = [
        'te' => [
            'ఆదివారం',
            'సోమవారం',
            'మంగళవారం',
            'బుధవారం',
            'గురువారం',
            'శుక్రవారం',
            'శనివారం',
        ],
        'ta' => [
            'ஞாயிற்றுக்கிழமை',
            'திங்கட்கிழமை',
            'செவ்வாய்க்கிழமை',
            'புதன்கிழமை',
            'வியாழக்கிழமை',
            'வெள்ளிக்கிழமை',
            'சனிக்கிழமை',
        ],
        'kn' => [
            'ಭಾನುವಾರ',
            'ಸೋಮವಾರ',
            'ಮಂಗಳವಾರ',
            'ಬುಧವಾರ',
            'ಗುರುವಾರ',
            'ಶುಕ್ರವಾರ',
            'ಶನಿವಾರ',
        ],
        'ml' => [
            'ഞായറാഴ്ച',
            'തിങ്കളാഴ്ച',
            'ചൊവ്വാഴ്ച',
            'ബുധനാഴ്ച',
            'വ്യാഴാഴ്ച',
            'വെള്ളിയാഴ്ച',
            'ശനിയാഴ്ച',
        ],
        'gu' => [
            'રવિવાર',
            'સોમવાર',
            'મંગળવાર',
            'બુધવાર',
            'ગુરુવાર',
            'શુક્રવાર',
            'શનિવાર',
        ],
        'bn' => [
            'রবিবার',
            'সোমবার',
            'মঙ্গলবার',
            'বুধবার',
            'বৃহস্পতিবার',
            'শুক্রবার',
            'শনিবার',
        ],
    ];

    public const TOC_HEADING = [
        'en' => 'Table of Contents',
        'hi' => 'विषय-सूची',
        'mr' => 'विषय-सूची',
        'te' => 'విషయ సూచిక',
        'ta' => 'பொருளடக்கம்',
        'kn' => 'ವಿಷಯ ಸೂಚಿ',
        'ml' => 'ഉള്ളടക്കം',
        'gu' => 'અનુક્રમણિકા',
        'bn' => 'সূচিপত্র',
    ];

    private function __construct()
    {
    }

    public static function isDevanagariLocale(?string $locale): bool
    {
        return $locale !== null && $locale !== '' && \in_array($locale, self::DEVANAGARI_LOCALES, true);
    }

    public static function isSouthLocale(?string $locale): bool
    {
        return $locale !== null && $locale !== '' && \in_array($locale, self::SOUTH_LOCALES, true);
    }

    public static function isIndicLocale(?string $locale): bool
    {
        return $locale !== null && $locale !== '' && \in_array($locale, self::INDIC_LOCALES, true);
    }

    /** Every localized sign name (te/ta/kn/ml/gu/bn) -> 0-based sign index. */
    public static function southSignIndex(string $name): ?int
    {
        foreach (self::RASHI_NAMES as $names) {
            $i = array_search($name, $names, true);
            if ($i !== false) {
                return $i;
            }
        }

        return null;
    }

    /** Map a te/ta/kn/ml/gu/bn planet name back to its English key ('' if unknown). */
    public static function southPlanetToEn(?string $name): string
    {
        if ($name === null || $name === '') {
            return '';
        }
        // Later tables win, as in the JS object built from them.
        $found = '';
        foreach (self::PLANET_NAMES as $table) {
            foreach ($table as $en => $native) {
                if ($native === $name) {
                    $found = (string) $en;
                }
            }
        }

        return $found;
    }

    /** Localize an English planet / Yogini lord key for te/ta/kn/ml/gu/bn; others pass through. */
    public static function localizeLord(?string $locale, ?string $english): string
    {
        $en = $english ?? '';
        if ($locale === null || $locale === '') {
            return $en;
        }

        return self::PLANET_NAMES[$locale][$en] ?? self::YOGINI_NAMES[$locale][$en] ?? $en;
    }

    /** Localize an English weekday name for te/ta/kn/ml/gu/bn; others pass through. */
    public static function localizeWeekday(?string $locale, ?string $english): string
    {
        $en = $english ?? '';
        $table = $locale !== null && $locale !== '' ? (self::WEEKDAY_NAMES[$locale] ?? null) : null;
        if ($table === null) {
            return $en;
        }
        $i = array_search($en, self::WEEKDAYS_EN, true);

        return $i !== false ? $table[$i] : $en;
    }

    /**
     * The lord label a template shows: the API's Devanagari field for hi/mr, the engine's native
     * name for te/ta/kn/ml/gu/bn, else the English key.
     */
    public static function lordLabel(?string $locale, ?string $english, ?string $hindi = null): string
    {
        if (self::isDevanagariLocale($locale) && $hindi !== null && $hindi !== '') {
            return $hindi;
        }
        if (self::isSouthLocale($locale)) {
            return self::localizeLord($locale, $english);
        }

        return $english ?? '';
    }
}
