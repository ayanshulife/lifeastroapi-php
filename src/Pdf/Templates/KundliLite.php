<?php

declare(strict_types=1);

namespace LifeAstroApi\Pdf\Templates;

use LifeAstroApi\Pdf\Branding;
use LifeAstroApi\Pdf\Js;
use LifeAstroApi\Pdf\Shared;

/**
 * Vedic Birth Chart (Lite) report -> HTML. A port of the Node.js SDK's
 * src/pdf/templates/kundliLite.ts (byte-identical output, golden-tested).
 *
 * @internal Use {@see \LifeAstroApi\Pdf\Reports::renderKundliLiteHtml()}.
 */
final class KundliLite
{
    private const LABELS = [
        'en' => [
            'title_lite' => 'Vedic Birth Chart',
            'born' => 'Born',
            'at' => 'at',
            'generated' => 'Generated on',
            'section_ascendant' => 'Ascendant',
            'section_planets' => 'Planetary Positions',
            'ascendant' => 'Ascendant',
            'nakshatra' => 'Nakshatra',
            'pada' => 'Pada',
            'lord' => 'Lord',
            'planet' => 'Planet',
            'sign' => 'Sign',
            'degree' => 'Degree',
            'house' => 'House',
            'upsell' => 'For a detailed reading covering yogas, dashas, and remedies, request the Brihad Kundli or Detailed Kundli report.',
        ],
        'hi' => [
            'title_lite' => 'जन्म कुंडली',
            'born' => 'जन्म',
            'at' => 'समय',
            'generated' => 'तैयार',
            'section_ascendant' => 'लग्न',
            'section_planets' => 'ग्रह स्थिति',
            'ascendant' => 'लग्न',
            'nakshatra' => 'नक्षत्र',
            'pada' => 'पाद',
            'lord' => 'स्वामी',
            'planet' => 'ग्रह',
            'sign' => 'राशि',
            'degree' => 'अंश',
            'house' => 'भाव',
            'upsell' => 'योग, दशा एवं उपायों सहित विस्तृत विश्लेषण के लिए विस्तृत कुंडली अथवा बृहद् कुंडली रिपोर्ट प्राप्त कीजिए।',
        ],
        'mr' => [
            'title_lite' => 'जन्म कुंडली',
            'born' => 'जन्म',
            'at' => 'वेळ',
            'generated' => 'तयार',
            'section_ascendant' => 'लग्न',
            'section_planets' => 'ग्रह स्थिती',
            'ascendant' => 'लग्न',
            'nakshatra' => 'नक्षत्र',
            'pada' => 'चरण',
            'lord' => 'अधिपती',
            'planet' => 'ग्रह',
            'sign' => 'राशी',
            'degree' => 'अंश',
            'house' => 'स्थान',
            'upsell' => 'योग, दशा आणि उपायांसह तपशीलवार विश्लेषणासाठी तपशीलवार कुंडली किंवा बृहद् कुंडली अहवाल मिळवा.',
        ],
        'te' => [
            'title_lite' => 'జన్మ జాతకం',
            'born' => 'జననం',
            'at' => 'సమయం',
            'generated' => 'రూపొందించిన తేదీ',
            'section_ascendant' => 'లగ్నం',
            'section_planets' => 'గ్రహ స్థితి',
            'ascendant' => 'లగ్నం',
            'nakshatra' => 'నక్షత్రం',
            'pada' => 'పాదం',
            'lord' => 'అధిపతి',
            'planet' => 'గ్రహం',
            'sign' => 'రాశి',
            'degree' => 'భాగలు',
            'house' => 'భావం',
            'upsell' => 'యోగాలు, దశలు మరియు పరిహారాలతో కూడిన వివరమైన విశ్లేషణ కోసం వివరణాత్మక జాతకం లేదా బృహత్ జాతకం నివేదికను పొందండి.',
        ],
        'ta' => [
            'title_lite' => 'ஜனன ஜாதகம்',
            'born' => 'பிறப்பு',
            'at' => 'நேரம்',
            'generated' => 'தயாரித்த நாள்',
            'section_ascendant' => 'லக்னம்',
            'section_planets' => 'கிரக நிலை',
            'ascendant' => 'லக்னம்',
            'nakshatra' => 'நட்சத்திரம்',
            'pada' => 'பாதம்',
            'lord' => 'அதிபதி',
            'planet' => 'கிரகம்',
            'sign' => 'ராசி',
            'degree' => 'பாகை',
            'house' => 'வீடு',
            'upsell' => 'யோகங்கள், திசைகள், பரிகாரங்கள் அடங்கிய விரிவான பலனுக்கு விரிவான ஜாதகம் அல்லது பிருஹத் ஜாதக அறிக்கையைப் பெறுங்கள்.',
        ],
        'kn' => [
            'title_lite' => 'ಜನ್ಮ ಜಾತಕ',
            'born' => 'ಜನನ',
            'at' => 'ಸಮಯ',
            'generated' => 'ತಯಾರಿಸಿದ ದಿನಾಂಕ',
            'section_ascendant' => 'ಲಗ್ನ',
            'section_planets' => 'ಗ್ರಹ ಸ್ಥಿತಿ',
            'ascendant' => 'ಲಗ್ನ',
            'nakshatra' => 'ನಕ್ಷತ್ರ',
            'pada' => 'ಪಾದ',
            'lord' => 'ಅಧಿಪತಿ',
            'planet' => 'ಗ್ರಹ',
            'sign' => 'ರಾಶಿ',
            'degree' => 'ಅಂಶ',
            'house' => 'ಭಾವ',
            'upsell' => 'ಯೋಗ, ದಶೆ ಮತ್ತು ಪರಿಹಾರಗಳನ್ನು ಒಳಗೊಂಡ ವಿವರವಾದ ವಿಶ್ಲೇಷಣೆಗಾಗಿ ವಿಸ್ತೃತ ಜಾತಕ ಅಥವಾ ಬೃಹತ್ ಜಾತಕ ವರದಿಯನ್ನು ಪಡೆಯಿರಿ.',
        ],
        'ml' => [
            'title_lite' => 'ജന്മ ജാതകം',
            'born' => 'ജനനം',
            'at' => 'സമയം',
            'generated' => 'തയ്യാറാക്കിയ തീയതി',
            'section_ascendant' => 'ലഗ്നം',
            'section_planets' => 'ഗ്രഹസ്ഥിതി',
            'ascendant' => 'ലഗ്നം',
            'nakshatra' => 'നക്ഷത്രം',
            'pada' => 'പാദം',
            'lord' => 'അധിപൻ',
            'planet' => 'ഗ്രഹം',
            'sign' => 'രാശി',
            'degree' => 'അംശം',
            'house' => 'ഭാവം',
            'upsell' => 'യോഗം, ദശ, പരിഹാരങ്ങൾ എന്നിവ ഉൾപ്പെടുന്ന വിശദമായ വിശകലനത്തിന് വിശദ ജാതകം അല്ലെങ്കിൽ ബൃഹദ് ജാതകം റിപ്പോർട്ട് സ്വന്തമാക്കുക.',
        ],
        'gu' => [
            'title_lite' => 'જન્મ કુંડળી',
            'born' => 'જન્મ',
            'at' => 'સમય',
            'generated' => 'તૈયાર',
            'section_ascendant' => 'લગ્ન',
            'section_planets' => 'ગ્રહ સ્થિતિ',
            'ascendant' => 'લગ્ન',
            'nakshatra' => 'નક્ષત્ર',
            'pada' => 'પાદ',
            'lord' => 'સ્વામી',
            'planet' => 'ગ્રહ',
            'sign' => 'રાશિ',
            'degree' => 'અંશ',
            'house' => 'ભાવ',
            'upsell' => 'યોગ, દશા અને ઉપાયો સહિત વિસ્તૃત વિશ્લેષણ માટે વિસ્તૃત કુંડળી અથવા બૃહદ્ કુંડળી રિપોર્ટ મેળવજો.',
        ],
        'bn' => [
            'title_lite' => 'জন্মকুণ্ডলী',
            'born' => 'জন্ম',
            'at' => 'সময়',
            'generated' => 'তৈরি',
            'section_ascendant' => 'লগ্ন',
            'section_planets' => 'গ্রহের অবস্থান',
            'ascendant' => 'লগ্ন',
            'nakshatra' => 'নক্ষত্র',
            'pada' => 'পাদ',
            'lord' => 'অধিপতি',
            'planet' => 'গ্রহ',
            'sign' => 'রাশি',
            'degree' => 'অংশ',
            'house' => 'ভাব',
            'upsell' => 'যোগ, দশা ও প্রতিকারসহ বিস্তারিত বিশ্লেষণের জন্য বিস্তারিত কুণ্ডলী বা বৃহৎ কুণ্ডলী রিপোর্ট নিন।',
        ],
    ];

    private const EXTRA_CSS = '
.lagna-summary {
  margin: 4mm 0;
  padding: 5mm;
  background: #fafafa;
  border-left: 3px solid var(--brand-primary);
  font-size: 11pt;
}
.lagna-meta {
  margin: 2mm 0 4mm;
  color: var(--brand-secondary);
  font-size: 10pt;
}
.upsell {
  margin-top: 10mm;
  padding: 4mm;
  background: linear-gradient(to right, rgba(0,0,0,0.02), transparent);
  border-left: 2px solid var(--brand-secondary);
  font-size: 10pt;
  color: var(--brand-secondary);
  font-style: italic;
}
';

    private function __construct()
    {
    }

    /** @param array<mixed> $data */
    public static function render(array $data, ?Branding $branding = null): string
    {
        $data = Js::map($data);
        $b = Shared::resolveBranding($data['Branding'] ?? null, $branding);
        $locale = Js::str(Js::or($data['Locale'] ?? null, 'en'));
        $label = Shared::makeLabeler(self::LABELS, $locale);
        $esc = Shared::esc(...);

        $subject = Js::map($data['Subject'] ?? null);
        $asc = Js::map($data['Ascendant'] ?? null);

        $coverHtml = '
<section class="cover">
  ' . Shared::coverBrandingHtml($b) . '
  <h1>' . $esc($label('title_lite')) . '</h1>
  <div class="subject">' . $esc($subject['Name'] ?? null) . '</div>
  <div class="meta">
    ' . $esc($label('born')) . ' ' . $esc($subject['BirthDate'] ?? null) . ' ' . $esc($label('at')) . ' '
            . $esc($subject['BirthTime'] ?? null) . '<br>
    ' . $esc($subject['BirthPlace'] ?? null) . '
  </div>
  <div class="generated">
    ' . $esc($label('generated')) . ' ' . $esc(Js::formatGenerated($data['GeneratedAt'] ?? null)) . '
  </div>
</section>';

        $lagnaSummaryHtml = Js::truthy($data['LagnaSummary'] ?? null)
            ? '<div class="lagna-summary">' . $esc($data['LagnaSummary']) . '</div>'
            : '';

        $ascendantHtml = '
<h2>' . $esc($label('section_ascendant')) . '</h2>
<p class="lagna-meta">
  <strong>' . $esc($label('ascendant')) . ':</strong>
  ' . $esc($asc['sign'] ?? null) . ' (' . $esc($asc['dms_within'] ?? null) . ')<br>
  <strong>' . $esc($label('nakshatra')) . ':</strong>
  ' . $esc($asc['nakshatra'] ?? null) . ', ' . $esc($label('pada')) . ' ' . $esc($asc['pada'] ?? null) . ',
  ' . $esc($label('lord')) . ' ' . $esc($asc['nak_lord'] ?? null) . '
</p>
' . $lagnaSummaryHtml;

        $rows = [];
        foreach (Js::list($data['Planets'] ?? null) as $planet) {
            $pl = Js::map($planet);
            $p = Js::map($pl['placement'] ?? null);
            $retro = Js::truthy($pl['retro'] ?? null) ? ' (R)' : '';
            $rows[] = '    <tr>
      <td>' . $esc($pl['name'] ?? null) . $retro . '</td>
      <td>' . $esc($p['sign'] ?? null) . '</td>
      <td>' . $esc($p['dms_within'] ?? null) . '</td>
      <td>' . $esc($pl['house_num'] ?? null) . '</td>
      <td>' . $esc($p['nakshatra'] ?? null) . ' (' . $esc($p['pada'] ?? null) . ')</td>
    </tr>';
        }

        $planetsHtml = '
<h2>' . $esc($label('section_planets')) . '</h2>
<table>
  <thead>
    <tr>
      <th>' . $esc($label('planet')) . '</th>
      <th>' . $esc($label('sign')) . '</th>
      <th>' . $esc($label('degree')) . '</th>
      <th>' . $esc($label('house')) . '</th>
      <th>' . $esc($label('nakshatra')) . '</th>
    </tr>
  </thead>
  <tbody>
' . implode("\n", $rows) . '
  </tbody>
</table>';

        $upsellHtml = '<div class="upsell">' . $esc($label('upsell')) . '</div>';

        $bodyHtml = $coverHtml . "\n" . $ascendantHtml . "\n" . $planetsHtml . "\n" . $upsellHtml;

        return Shared::layoutDocument(
            title: $label('title_lite') . ' — ' . Js::str($subject['Name'] ?? null, ''),
            branding: $b,
            bodyHtml: $bodyHtml,
            lang: $locale,
            extraCss: self::EXTRA_CSS,
        );
    }
}
