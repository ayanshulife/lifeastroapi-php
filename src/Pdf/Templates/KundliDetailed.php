<?php

declare(strict_types=1);

namespace LifeAstroApi\Pdf\Templates;

use LifeAstroApi\Pdf\Branding;
use LifeAstroApi\Pdf\Js;
use LifeAstroApi\Pdf\Shared;

/**
 * Detailed Kundli report -> HTML. A port of the Node.js SDK's src/pdf/templates/kundliDetailed.ts
 * (byte-identical output, golden-tested). The dasha / house / yoga / dosha blocks reuse the Brihad
 * labels and helpers, as in Node.
 *
 * @internal Use {@see \LifeAstroApi\Pdf\Reports::renderKundliDetailedHtml()}.
 */
final class KundliDetailed
{
    private const LABELS = [
        'en' => [
            'title_kundli' => 'Detailed Kundli',
            'born' => 'Born',
            'at' => 'at',
            'generated' => 'Generated on',
            'section_ascendant' => 'Ascendant',
            'section_planets' => 'Planetary Positions',
            'section_panchang' => 'Panchang at Birth',
            'section_interpretation' => 'Interpretation',
            'ascendant' => 'Ascendant',
            'planet' => 'Planet',
            'sign' => 'Sign',
            'degree' => 'Degree',
            'house' => 'House',
            'nakshatra' => 'Nakshatra',
            'pada' => 'Pada',
            'lord' => 'Lord',
            'tithi' => 'Tithi',
            'yoga' => 'Yoga',
            'karana' => 'Karana',
            'sources' => 'Sources',
        ],
        'hi' => [
            'title_kundli' => 'विस्तृत कुंडली',
            'born' => 'जन्म',
            'at' => 'समय',
            'generated' => 'तैयार',
            'section_ascendant' => 'लग्न',
            'section_planets' => 'ग्रह स्थिति',
            'section_panchang' => 'जन्म पंचांग',
            'section_interpretation' => 'व्याख्या',
            'ascendant' => 'लग्न',
            'planet' => 'ग्रह',
            'sign' => 'राशि',
            'degree' => 'अंश',
            'house' => 'भाव',
            'nakshatra' => 'नक्षत्र',
            'pada' => 'पाद',
            'lord' => 'स्वामी',
            'tithi' => 'तिथि',
            'yoga' => 'योग',
            'karana' => 'करण',
            'sources' => 'स्रोत',
        ],
        'mr' => [
            'title_kundli' => 'तपशीलवार कुंडली',
            'born' => 'जन्म',
            'at' => 'वेळ',
            'generated' => 'तयार',
            'section_ascendant' => 'लग्न',
            'section_planets' => 'ग्रह स्थिती',
            'section_panchang' => 'जन्म पंचांग',
            'section_interpretation' => 'व्याख्या',
            'ascendant' => 'लग्न',
            'planet' => 'ग्रह',
            'sign' => 'राशी',
            'degree' => 'अंश',
            'house' => 'स्थान',
            'nakshatra' => 'नक्षत्र',
            'pada' => 'चरण',
            'lord' => 'अधिपती',
            'tithi' => 'तिथी',
            'yoga' => 'योग',
            'karana' => 'करण',
            'sources' => 'स्रोत',
        ],
        'te' => [
            'title_kundli' => 'వివరణాత్మక జాతకం',
            'born' => 'జననం',
            'at' => 'సమయం',
            'generated' => 'రూపొందించిన తేదీ',
            'section_ascendant' => 'లగ్నం',
            'section_planets' => 'గ్రహ స్థితి',
            'section_panchang' => 'జన్మ పంచాంగం',
            'section_interpretation' => 'వివరణ',
            'ascendant' => 'లగ్నం',
            'planet' => 'గ్రహం',
            'sign' => 'రాశి',
            'degree' => 'భాగలు',
            'house' => 'భావం',
            'nakshatra' => 'నక్షత్రం',
            'pada' => 'పాదం',
            'lord' => 'అధిపతి',
            'tithi' => 'తిథి',
            'yoga' => 'యోగం',
            'karana' => 'కరణం',
            'sources' => 'ఆధారాలు',
        ],
        'ta' => [
            'title_kundli' => 'விரிவான ஜாதகம்',
            'born' => 'பிறப்பு',
            'at' => 'நேரம்',
            'generated' => 'தயாரித்த நாள்',
            'section_ascendant' => 'லக்னம்',
            'section_planets' => 'கிரக நிலை',
            'section_panchang' => 'ஜனன பஞ்சாங்கம்',
            'section_interpretation' => 'விளக்கம்',
            'ascendant' => 'லக்னம்',
            'planet' => 'கிரகம்',
            'sign' => 'ராசி',
            'degree' => 'பாகை',
            'house' => 'வீடு',
            'nakshatra' => 'நட்சத்திரம்',
            'pada' => 'பாதம்',
            'lord' => 'அதிபதி',
            'tithi' => 'திதி',
            'yoga' => 'யோகம்',
            'karana' => 'கரணம்',
            'sources' => 'ஆதாரங்கள்',
        ],
        'kn' => [
            'title_kundli' => 'ವಿಸ್ತೃತ ಜಾತಕ',
            'born' => 'ಜನನ',
            'at' => 'ಸಮಯ',
            'generated' => 'ತಯಾರಿಸಿದ ದಿನಾಂಕ',
            'section_ascendant' => 'ಲಗ್ನ',
            'section_planets' => 'ಗ್ರಹ ಸ್ಥಿತಿ',
            'section_panchang' => 'ಜನ್ಮ ಪಂಚಾಂಗ',
            'section_interpretation' => 'ವಿವರಣೆ',
            'ascendant' => 'ಲಗ್ನ',
            'planet' => 'ಗ್ರಹ',
            'sign' => 'ರಾಶಿ',
            'degree' => 'ಅಂಶ',
            'house' => 'ಭಾವ',
            'nakshatra' => 'ನಕ್ಷತ್ರ',
            'pada' => 'ಪಾದ',
            'lord' => 'ಅಧಿಪತಿ',
            'tithi' => 'ತಿಥಿ',
            'yoga' => 'ಯೋಗ',
            'karana' => 'ಕರಣ',
            'sources' => 'ಆಧಾರಗಳು',
        ],
        'ml' => [
            'title_kundli' => 'വിശദ ജാതകം',
            'born' => 'ജനനം',
            'at' => 'സമയം',
            'generated' => 'തയ്യാറാക്കിയ തീയതി',
            'section_ascendant' => 'ലഗ്നം',
            'section_planets' => 'ഗ്രഹസ്ഥിതി',
            'section_panchang' => 'ജന്മ പഞ്ചാംഗം',
            'section_interpretation' => 'വ്യാഖ്യാനം',
            'ascendant' => 'ലഗ്നം',
            'planet' => 'ഗ്രഹം',
            'sign' => 'രാശി',
            'degree' => 'അംശം',
            'house' => 'ഭാവം',
            'nakshatra' => 'നക്ഷത്രം',
            'pada' => 'പാദം',
            'lord' => 'അധിപൻ',
            'tithi' => 'തിഥി',
            'yoga' => 'യോഗം',
            'karana' => 'കരണം',
            'sources' => 'ആധാരങ്ങൾ',
        ],
        'gu' => [
            'title_kundli' => 'વિસ્તૃત કુંડળી',
            'born' => 'જન્મ',
            'at' => 'સમય',
            'generated' => 'તૈયાર',
            'section_ascendant' => 'લગ્ન',
            'section_planets' => 'ગ્રહ સ્થિતિ',
            'section_panchang' => 'જન્મ પંચાંગ',
            'section_interpretation' => 'વ્યાખ્યા',
            'ascendant' => 'લગ્ન',
            'planet' => 'ગ્રહ',
            'sign' => 'રાશિ',
            'degree' => 'અંશ',
            'house' => 'ભાવ',
            'nakshatra' => 'નક્ષત્ર',
            'pada' => 'પાદ',
            'lord' => 'સ્વામી',
            'tithi' => 'તિથિ',
            'yoga' => 'યોગ',
            'karana' => 'કરણ',
            'sources' => 'સ્રોત',
        ],
        'bn' => [
            'title_kundli' => 'বিস্তারিত কুণ্ডলী',
            'born' => 'জন্ম',
            'at' => 'সময়',
            'generated' => 'তৈরি',
            'section_ascendant' => 'লগ্ন',
            'section_planets' => 'গ্রহের অবস্থান',
            'section_panchang' => 'জন্মের পঞ্জিকা',
            'section_interpretation' => 'ব্যাখ্যা',
            'ascendant' => 'লগ্ন',
            'planet' => 'গ্রহ',
            'sign' => 'রাশি',
            'degree' => 'অংশ',
            'house' => 'ভাব',
            'nakshatra' => 'নক্ষত্র',
            'pada' => 'পাদ',
            'lord' => 'অধিপতি',
            'tithi' => 'তিথি',
            'yoga' => 'যোগ',
            'karana' => 'করণ',
            'sources' => 'সূত্র',
        ],
    ];

    private const MIDTIER_CSS = '
.house-block { margin: 4mm 0; padding: 4mm 5mm; background: #fafafa; border-left: 3px solid var(--brand-primary); page-break-inside: avoid; }
.house-block .meta { color: var(--brand-secondary); font-size: 10pt; margin-bottom: 2mm; }
.yoga-block { margin: 3mm 0; padding: 4mm 5mm; background: #fff7ed; border-left: 3px solid #ea580c; page-break-inside: avoid; }
.yoga-block .name { font-size: 13pt; font-weight: 700; color: #ea580c; margin-bottom: 1mm; }
.yoga-block .source, .dosha-block .source { color: var(--brand-secondary); font-size: 9pt; font-style: italic; margin-top: 2mm; }
.dasha-block { margin: 4mm 0; padding: 5mm; background: #f0fdf4; border-left: 4px solid #16a34a; page-break-inside: avoid; }
.dasha-block.upcoming { background: #fafafa; border-left-color: var(--brand-secondary); }
.dasha-block .header-row, .dosha-block .header-row { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 2mm; }
.dasha-block .lord { font-size: 14pt; font-weight: 700; color: var(--brand-primary); }
.dasha-block .dates { color: var(--brand-secondary); font-size: 10pt; }
.dosha-block { margin: 4mm 0; padding: 4mm 5mm; border-left: 4px solid #b91c1c; background: #fef2f2; page-break-inside: avoid; }
.dosha-block.cancelled { border-left-color: #16a34a; background: #f0fdf4; }
.dosha-block.mild { border-left-color: #ca8a04; background: #fefce8; }
.dosha-block .name { font-size: 13pt; font-weight: 700; color: var(--brand-primary); }
.dosha-block .severity { display: inline-block; padding: 1mm 3mm; font-size: 9pt; font-weight: 600; border-radius: 1mm; color: #fff; background: #b91c1c; }
.dosha-block.cancelled .severity { background: #16a34a; }
.dosha-block.mild .severity { background: #ca8a04; }
.empty-list { margin: 3mm 0; color: var(--brand-secondary); font-style: italic; }
';

    private function __construct()
    {
    }

    /** @param array<mixed> $data */
    public static function render(array $data, ?Branding $branding = null): string
    {
        $data = Js::map($data);
        $esc = Shared::esc(...);
        $resolved = Shared::resolveBranding($data['Branding'] ?? null, $branding);
        $t = Shared::makeLabeler(self::LABELS, $data['Locale'] ?? null);

        $subject = Js::map($data['Subject'] ?? null);
        $asc = Js::map($data['Ascendant'] ?? null);
        $panchang = Js::map($data['Panchang'] ?? null);

        $coverMeta = $esc($t('born')) . ' ' . $esc($subject['BirthDate'] ?? null) . ' ' . $esc($t('at')) . ' '
            . $esc($subject['BirthTime'] ?? null) . '<br>' . $esc($subject['BirthPlace'] ?? null);
        $cover = '<section class="cover">
  ' . Shared::coverBrandingHtml($resolved) . '
  <h1>' . $esc($t('title_kundli')) . '</h1>
  <div class="subject">' . $esc($subject['Name'] ?? null) . '</div>
  <div class="meta">' . $coverMeta . '</div>
  <div class="generated">' . $esc($t('generated')) . ' ' . $esc(Js::formatGenerated($data['GeneratedAt'] ?? null)) . '</div>
</section>';

        $ascendant = '<h2>' . $esc($t('section_ascendant')) . '</h2>
<p><strong>' . $esc($t('ascendant')) . ':</strong> ' . $esc($asc['sign'] ?? null) . ' (' . $esc($asc['dms_within'] ?? null)
            . ') — ' . $esc($t('nakshatra')) . ': ' . $esc($asc['nakshatra'] ?? null) . ', ' . $esc($t('pada')) . ' '
            . $esc($asc['pada'] ?? null) . ', ' . $esc($t('lord')) . ' ' . $esc($asc['nak_lord'] ?? null) . '</p>';

        $planetRows = '';
        foreach (Js::list($data['Planets'] ?? null) as $planet) {
            $pl = Js::map($planet);
            $p = Js::map($pl['placement'] ?? null);
            $retro = Js::truthy($pl['retro'] ?? null) ? ' (R)' : '';
            $planetRows .= '<tr><td>' . $esc($pl['name'] ?? null) . $retro . '</td><td>' . $esc($p['sign'] ?? null)
                . '</td><td>' . $esc($p['dms_within'] ?? null) . '</td><td>' . $esc($pl['house_num'] ?? null)
                . '</td><td>' . $esc($p['nakshatra'] ?? null) . ' (' . $esc($p['pada'] ?? null) . ')</td></tr>';
        }
        $planetsTable = '<h2>' . $esc($t('section_planets')) . '</h2>
<table>
  <thead>
    <tr>
      <th>' . $esc($t('planet')) . '</th>
      <th>' . $esc($t('sign')) . '</th>
      <th>' . $esc($t('degree')) . '</th>
      <th>' . $esc($t('house')) . '</th>
      <th>' . $esc($t('nakshatra')) . '</th>
    </tr>
  </thead>
  <tbody>' . $planetRows . '</tbody>
</table>';

        $panchangTable = '<h2>' . $esc($t('section_panchang')) . '</h2>
<table>
  <tr><th>' . $esc($t('tithi')) . '</th><td>' . $esc($panchang['tithi'] ?? null) . '</td></tr>
  <tr><th>' . $esc($t('nakshatra')) . '</th><td>' . $esc($panchang['nakshatra'] ?? null) . '</td></tr>
  <tr><th>' . $esc($t('yoga')) . '</th><td>' . $esc($panchang['yoga'] ?? null) . '</td></tr>
  <tr><th>' . $esc($t('karana')) . '</th><td>' . $esc($panchang['karana'] ?? null) . '</td></tr>
</table>';

        $narratives = '';
        if (Js::nonEmptyList($data['Narratives'] ?? null)) {
            $narratives = '<h2>' . $esc($t('section_interpretation')) . "</h2>\n"
                . Shared::narrativeSectionsHtml($data['Narratives'], $t('sources'));
        }

        // Mid-tier enrichment: dasha + houses + yogas + doshas, with the Brihad labels and helpers.
        $loc = Js::str($data['Locale'] ?? 'en');
        $bl = Shared::makeLabeler(KundliBrihad::LABELS, $data['Locale'] ?? null);
        $houses = Js::list($data['Houses'] ?? null);
        $yogas = Js::list($data['Yogas'] ?? null);
        $upcoming = Js::list($data['UpcomingDashas'] ?? null);

        $housesSection = '';
        if ($houses !== []) {
            $blocks = [];
            foreach ($houses as $house) {
                $h = Js::map($house);
                $num = $h['HouseNum'] ?? 0;
                $lordSign = Js::truthy($h['LordSign'] ?? null)
                    ? ' (' . $esc($bl('in')) . ' ' . $esc($h['LordSign']) . ')'
                    : '';
                $body = Js::truthy($h['Body'] ?? null) ? '<p>' . $esc($h['Body']) . '</p>' : '';
                $blocks[] = '<div class="house-block">
<h3>' . $esc($bl('house')) . ' ' . $esc($num) . ' — ' . $esc(KundliBrihad::houseTopic($loc, $num)) . '</h3>
<div class="meta">' . $esc($bl('lord')) . ': ' . $esc(KundliBrihad::houseLordLabel($loc, $house)) . $lordSign . '</div>
' . $body . '
</div>';
            }
            $housesSection = '<h2>' . $esc($bl('section_houses')) . "</h2>\n" . implode("\n", $blocks);
        }

        $yogasSection = '';
        if ($yogas !== []) {
            $blocks = [];
            foreach ($yogas as $yoga) {
                $y = Js::map($yoga);
                $src = Js::truthy($y['Source'] ?? null)
                    ? '<div class="source">' . $esc($bl('source')) . ': ' . $esc($y['Source']) . '</div>'
                    : '';
                $blocks[] = '<div class="yoga-block"><div class="name">' . $esc(KundliBrihad::yogaNameLabel($loc, $yoga))
                    . '</div><p>' . $esc($y['Effect'] ?? null) . '</p>' . $src . '</div>';
            }
            $yogasSection = '<h2>' . $esc($bl('section_yogas')) . "</h2>\n" . implode("\n", $blocks);
        }

        $dashaBlock = static function (mixed $period, string $class) use ($esc, $bl, $loc): string {
            $d = Js::map($period);

            return '<div class="' . $class . '"><div class="header-row"><span class="lord">'
                . $esc(KundliBrihad::dashaLordLabel($loc, $period)) . '</span><span class="dates">'
                . $esc($d['StartDate'] ?? null) . ' → ' . $esc($d['EndDate'] ?? null) . ' (' . $esc($d['DurationY'] ?? null)
                . ' ' . $esc($bl('years')) . ')</span></div><p>' . $esc($d['Body'] ?? null) . '</p></div>';
        };
        $dashaInner = '';
        $cur = $data['CurrentDasha'] ?? null;
        if (Js::truthy($cur)) {
            $dashaInner .= '<h3>' . $esc($bl('current_dasha')) . "</h3>\n" . $dashaBlock($cur, 'dasha-block');
        }
        if ($upcoming !== []) {
            $dashaInner .= "\n<h3>" . $esc($bl('upcoming_dashas')) . "</h3>\n" . implode("\n", array_map(
                static fn (mixed $d): string => $dashaBlock($d, 'dasha-block upcoming'),
                $upcoming,
            ));
        }
        $dashaSection = $dashaInner !== '' ? '<h2>' . $esc($bl('section_dashas')) . "</h2>\n" . $dashaInner : '';

        $doshasSection = '';
        $doshas = $data['Doshas'] ?? null;
        if (Js::truthy($doshas) && KundliBrihad::anyDoshaPresent($doshas)) {
            $dd = Js::map($doshas);
            $block = static function (mixed $entry, string $nameKey) use ($esc, $bl): string {
                if (!Js::truthy($entry)) {
                    return '';
                }
                $e = Js::map($entry);
                $sev = $e['Severity'] ?? '';
                $src = Js::truthy($e['Source'] ?? null)
                    ? '<div class="source">' . $esc($bl('source')) . ': ' . $esc($e['Source']) . '</div>'
                    : '';

                return '<div class="dosha-block ' . $esc($sev) . '"><div class="header-row"><span class="name">'
                    . $esc(KundliBrihad::doshaNameLabel($bl, $entry, $nameKey)) . '</span><span class="severity">'
                    . $esc(KundliBrihad::severityLabel($bl, $sev)) . '</span></div><p>' . $esc($e['Body'] ?? null) . '</p>'
                    . $src . '</div>';
            };
            $doshasSection = '<h2>' . $esc($bl('section_doshas')) . "</h2>\n" . $block($dd['Mangal'] ?? null, 'dosha_mangal')
                . $block($dd['KaalSarp'] ?? null, 'dosha_kaalsarp') . $block($dd['Pitra'] ?? null, 'dosha_pitra');
        }

        $parts = [$cover, $ascendant, $planetsTable, $housesSection, $yogasSection, $dashaSection, $doshasSection,
            $panchangTable, $narratives];
        $bodyHtml = implode("\n\n", array_filter($parts, static fn (string $p): bool => $p !== ''));

        $lang = $data['Locale'] ?? null;

        return Shared::layoutDocument(
            title: $t('title_kundli') . ' — ' . Js::str($subject['Name'] ?? null, ''),
            branding: $resolved,
            bodyHtml: $bodyHtml,
            lang: $lang === null ? 'en' : Js::str($lang),
            extraCss: self::MIDTIER_CSS,
        );
    }
}
