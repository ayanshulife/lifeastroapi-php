<?php

declare(strict_types=1);

namespace LifeAstroApi\Pdf\Templates;

use LifeAstroApi\Pdf\Branding;
use LifeAstroApi\Pdf\Js;
use LifeAstroApi\Pdf\Shared;

/**
 * Varshaphal (annual / solar-return) report -> HTML. A port of the Node.js SDK's
 * src/pdf/templates/varshaphal.ts (byte-identical output, golden-tested).
 *
 * @internal Use {@see \LifeAstroApi\Pdf\Reports::renderVarshaphalHtml()}.
 */
final class Varshaphal
{
    private const LABELS = [
        'en' => [
            'title_varsha' => 'Varshaphal — Annual Forecast',
            'born' => 'Born',
            'at' => 'at',
            'generated' => 'Generated on',
            'section_solar_return' => 'Solar Return Moment',
            'section_planets_sr' => 'Planetary Positions at Solar Return',
            'section_outlook' => 'Year Outlook',
            'solar_return_at' => 'Solar Return at',
            'ascendant' => 'Ascendant',
            'nakshatra' => 'Nakshatra',
            'pada' => 'Pada',
            'muntha' => 'Muntha',
            'varsha_lord' => 'Varsha Lord',
            'house' => 'House',
            'from_natal_lagna' => 'from natal lagna',
            'hora_number' => 'Hora',
            'planet' => 'Planet',
            'sign' => 'Sign',
            'degree' => 'Degree',
        ],
        'hi' => [
            'title_varsha' => 'वर्षफल — वार्षिक भविष्यफल',
            'born' => 'जन्म',
            'at' => 'समय',
            'generated' => 'तैयार',
            'section_solar_return' => 'सूर्य प्रत्यागमन क्षण',
            'section_planets_sr' => 'सूर्य प्रत्यागमन के समय ग्रह स्थिति',
            'section_outlook' => 'वार्षिक दृष्टिकोण',
            'solar_return_at' => 'सूर्य प्रत्यागमन समय',
            'ascendant' => 'लग्न',
            'nakshatra' => 'नक्षत्र',
            'pada' => 'पाद',
            'muntha' => 'मुन्था',
            'varsha_lord' => 'वर्ष स्वामी',
            'house' => 'भाव',
            'from_natal_lagna' => 'जन्म लग्न से',
            'hora_number' => 'होरा',
            'planet' => 'ग्रह',
            'sign' => 'राशि',
            'degree' => 'अंश',
        ],
        'mr' => [
            'title_varsha' => 'वर्षफल — वार्षिक भविष्यफल',
            'born' => 'जन्म',
            'at' => 'वेळ',
            'generated' => 'तयार',
            'section_solar_return' => 'सूर्य प्रत्यागमन क्षण',
            'section_planets_sr' => 'सूर्य प्रत्यागमन वेळेस ग्रह स्थिती',
            'section_outlook' => 'वार्षिक दृष्टीकोन',
            'solar_return_at' => 'सूर्य प्रत्यागमन वेळ',
            'ascendant' => 'लग्न',
            'nakshatra' => 'नक्षत्र',
            'pada' => 'चरण',
            'muntha' => 'मुन्था',
            'varsha_lord' => 'वर्ष अधिपती',
            'house' => 'स्थान',
            'from_natal_lagna' => 'जन्म लग्नापासून',
            'hora_number' => 'होरा',
            'planet' => 'ग्रह',
            'sign' => 'राशी',
            'degree' => 'अंश',
        ],
        'te' => [
            'title_varsha' => 'వర్షఫలం: వార్షిక ఫలితాలు',
            'born' => 'జననం',
            'at' => 'సమయం',
            'generated' => 'రూపొందించిన తేదీ',
            'section_solar_return' => 'సౌర పునరాగమన క్షణం',
            'section_planets_sr' => 'సౌర పునరాగమన సమయంలో గ్రహ స్థితి',
            'section_outlook' => 'సంవత్సర దృక్పథం',
            'solar_return_at' => 'సౌర పునరాగమన సమయం',
            'ascendant' => 'లగ్నం',
            'nakshatra' => 'నక్షత్రం',
            'pada' => 'పాదం',
            'muntha' => 'ముంథా',
            'varsha_lord' => 'వర్షాధిపతి',
            'house' => 'భావం',
            'from_natal_lagna' => 'జన్మ లగ్నం నుండి',
            'hora_number' => 'హోర',
            'planet' => 'గ్రహం',
            'sign' => 'రాశి',
            'degree' => 'భాగలు',
        ],
        'ta' => [
            'title_varsha' => 'வருஷ பலன்: ஆண்டுப் பலன்கள்',
            'born' => 'பிறப்பு',
            'at' => 'நேரம்',
            'generated' => 'தயாரித்த நாள்',
            'section_solar_return' => 'சூரிய மீள்வருகைத் தருணம்',
            'section_planets_sr' => 'சூரிய மீள்வருகையின்போது கிரக நிலை',
            'section_outlook' => 'ஆண்டுப் பார்வை',
            'solar_return_at' => 'சூரிய மீள்வருகை நேரம்',
            'ascendant' => 'லக்னம்',
            'nakshatra' => 'நட்சத்திரம்',
            'pada' => 'பாதம்',
            'muntha' => 'முந்தா',
            'varsha_lord' => 'வருஷ அதிபதி',
            'house' => 'வீடு',
            'from_natal_lagna' => 'ஜனன லக்னத்திலிருந்து',
            'hora_number' => 'ஹோரை',
            'planet' => 'கிரகம்',
            'sign' => 'ராசி',
            'degree' => 'பாகை',
        ],
        'kn' => [
            'title_varsha' => 'ವರ್ಷಫಲ: ವಾರ್ಷಿಕ ಫಲ',
            'born' => 'ಜನನ',
            'at' => 'ಸಮಯ',
            'generated' => 'ತಯಾರಿಸಿದ ದಿನಾಂಕ',
            'section_solar_return' => 'ಸೌರ ಪುನರಾಗಮನ ಕ್ಷಣ',
            'section_planets_sr' => 'ಸೌರ ಪುನರಾಗಮನದ ಸಮಯದ ಗ್ರಹ ಸ್ಥಿತಿ',
            'section_outlook' => 'ವರ್ಷದ ದೃಷ್ಟಿಕೋನ',
            'solar_return_at' => 'ಸೌರ ಪುನರಾಗಮನ ಸಮಯ',
            'ascendant' => 'ಲಗ್ನ',
            'nakshatra' => 'ನಕ್ಷತ್ರ',
            'pada' => 'ಪಾದ',
            'muntha' => 'ಮುಂಥಾ',
            'varsha_lord' => 'ವರ್ಷಾಧಿಪತಿ',
            'house' => 'ಭಾವ',
            'from_natal_lagna' => 'ಜನ್ಮ ಲಗ್ನದಿಂದ',
            'hora_number' => 'ಹೋರಾ',
            'planet' => 'ಗ್ರಹ',
            'sign' => 'ರಾಶಿ',
            'degree' => 'ಅಂಶ',
        ],
        'ml' => [
            'title_varsha' => 'വർഷഫലം: വാർഷിക ഫലങ്ങൾ',
            'born' => 'ജനനം',
            'at' => 'സമയം',
            'generated' => 'തയ്യാറാക്കിയ തീയതി',
            'section_solar_return' => 'സൗര പുനരാഗമന നിമിഷം',
            'section_planets_sr' => 'സൗര പുനരാഗമന സമയത്തെ ഗ്രഹസ്ഥിതി',
            'section_outlook' => 'വർഷത്തെക്കുറിച്ചുള്ള കാഴ്ചപ്പാട്',
            'solar_return_at' => 'സൗര പുനരാഗമന സമയം',
            'ascendant' => 'ലഗ്നം',
            'nakshatra' => 'നക്ഷത്രം',
            'pada' => 'പാദം',
            'muntha' => 'മുന്ഥ',
            'varsha_lord' => 'വർഷാധിപൻ',
            'house' => 'ഭാവം',
            'from_natal_lagna' => 'ജന്മലഗ്നത്തിൽ നിന്ന്',
            'hora_number' => 'ഹോര',
            'planet' => 'ഗ്രഹം',
            'sign' => 'രാശി',
            'degree' => 'ഡിഗ്രി',
        ],
        'gu' => [
            'title_varsha' => 'વર્ષફળ: વાર્ષિક ભવિષ્યફળ',
            'born' => 'જન્મ',
            'at' => 'સમય',
            'generated' => 'તૈયાર',
            'section_solar_return' => 'સૂર્ય પ્રત્યાગમનની ક્ષણ',
            'section_planets_sr' => 'સૂર્ય પ્રત્યાગમન સમયે ગ્રહ સ્થિતિ',
            'section_outlook' => 'વાર્ષિક દૃષ્ટિકોણ',
            'solar_return_at' => 'સૂર્ય પ્રત્યાગમન સમય',
            'ascendant' => 'લગ્ન',
            'nakshatra' => 'નક્ષત્ર',
            'pada' => 'પાદ',
            'muntha' => 'મુંથા',
            'varsha_lord' => 'વર્ષ સ્વામી',
            'house' => 'ભાવ',
            'from_natal_lagna' => 'જન્મ લગ્નથી',
            'hora_number' => 'હોરા',
            'planet' => 'ગ્રહ',
            'sign' => 'રાશિ',
            'degree' => 'અંશ',
        ],
        'bn' => [
            'title_varsha' => 'বর্ষফল: বার্ষিক ভবিষ্যফল',
            'born' => 'জন্ম',
            'at' => 'সময়',
            'generated' => 'তৈরি',
            'section_solar_return' => 'সৌর প্রত্যাবর্তনের মুহূর্ত',
            'section_planets_sr' => 'সৌর প্রত্যাবর্তনের সময় গ্রহের অবস্থান',
            'section_outlook' => 'বার্ষিক পূর্বাভাস',
            'solar_return_at' => 'সৌর প্রত্যাবর্তনের সময়',
            'ascendant' => 'লগ্ন',
            'nakshatra' => 'নক্ষত্র',
            'pada' => 'পাদ',
            'muntha' => 'মুন্থা',
            'varsha_lord' => 'বর্ষের অধিপতি',
            'house' => 'ভাব',
            'from_natal_lagna' => 'জন্মলগ্ন থেকে',
            'hora_number' => 'হোরা',
            'planet' => 'গ্রহ',
            'sign' => 'রাশি',
            'degree' => 'অংশ',
        ],
    ];


    private const OUTLOOK = [
        'en' => [
            'favorable' => 'Muntha in house {house} makes {year} broadly favorable — increased likelihood of goal attainment, stability, or spiritual progress.',
            'challenging' => 'Muntha in house {house} may make {year} challenging. Exercise care around health, expenditure, or debt-related matters.',
            'mixed' => 'Muntha sits in house {house} — {year} likely brings mixed results. Study your chart closely before major decisions.',
        ],
        'hi' => [
            'favorable' => 'मुन्था का {house} वें भाव में होना {year} वर्ष को सामान्यतः अनुकूल बनाता है — लक्ष्य प्राप्ति, स्थायित्व, या आध्यात्मिक प्रगति की संभावनाएं बढ़ती हैं।',
            'challenging' => 'मुन्था का {house} वें भाव में होना {year} वर्ष को चुनौतीपूर्ण बना सकता है। स्वास्थ्य, खर्च, या ऋण-सम्बन्धी मामलों में सावधानी रखिए।',
            'mixed' => 'मुन्था {house} वें भाव में स्थित है — {year} वर्ष में मिश्रित परिणाम संभव। प्रमुख निर्णयों से पूर्व अपनी कुंडली को गहराई से जाँच लीजिए।',
        ],
        'mr' => [
            'favorable' => 'मुन्था {house} व्या स्थानात असल्याने {year} वर्ष सामान्यतः अनुकूल राहील — उद्दिष्टपूर्ती, स्थैर्य किंवा आध्यात्मिक प्रगतीच्या शक्यता वाढतात.',
            'challenging' => 'मुन्था {house} व्या स्थानात असल्याने {year} वर्ष आव्हानात्मक राहू शकते. आरोग्य, खर्च किंवा कर्ज-संबंधी बाबींमध्ये सावधगिरी बाळगा.',
            'mixed' => 'मुन्था {house} व्या स्थानात आहे — {year} वर्षात मिश्र परिणाम संभाव्य. महत्त्वाच्या निर्णयांपूर्वी आपली कुंडली सखोल तपासून घ्या.',
        ],
        'te' => [
            'favorable' => 'ముంథా {house}వ భావంలో ఉండటం వల్ల {year} సంవత్సరం సాధారణంగా అనుకూలంగా ఉంటుంది; లక్ష్య సాధన, స్థిరత్వం లేదా ఆధ్యాత్మిక పురోగతికి అవకాశాలు పెరుగుతాయి.',
            'challenging' => 'ముంథా {house}వ భావంలో ఉండటం వల్ల {year} సంవత్సరం సవాలుగా ఉండవచ్చు. ఆరోగ్యం, ఖర్చులు లేదా అప్పులకు సంబంధించిన విషయాలలో జాగ్రత్తగా ఉండండి.',
            'mixed' => 'ముంథా {house}వ భావంలో ఉంది; {year} సంవత్సరంలో మిశ్రమ ఫలితాలు రావచ్చు. ముఖ్యమైన నిర్ణయాలకు ముందు మీ జాతకాన్ని లోతుగా పరిశీలించుకోండి.',
        ],
        'ta' => [
            'favorable' => 'முந்தா {house}-ஆம் வீட்டில் இருப்பதால் {year} ஆம் ஆண்டு பொதுவாகச் சாதகமாக இருக்கும்; இலக்கை அடைதல், நிலைத்தன்மை அல்லது ஆன்மிக முன்னேற்றத்துக்கான வாய்ப்புகள் கூடும்.',
            'challenging' => 'முந்தா {house}-ஆம் வீட்டில் இருப்பதால் {year} ஆம் ஆண்டு சவாலாக இருக்கலாம். உடல்நலம், செலவு, கடன் தொடர்பான விஷயங்களில் கவனமாக இருங்கள்.',
            'mixed' => 'முந்தா {house}-ஆம் வீட்டில் உள்ளது; {year} ஆம் ஆண்டில் கலவையான பலன்கள் வரலாம். முக்கிய முடிவுகளுக்கு முன் உங்கள் ஜாதகத்தை ஆழமாகப் பார்த்துக்கொள்ளுங்கள்.',
        ],
        'kn' => [
            'favorable' => 'ಮುಂಥಾ {house}ನೇ ಭಾವದಲ್ಲಿರುವುದರಿಂದ {year} ವರ್ಷ ಸಾಮಾನ್ಯವಾಗಿ ಅನುಕೂಲಕರವಾಗಿರುತ್ತದೆ; ಗುರಿ ಸಾಧನೆ, ಸ್ಥಿರತೆ ಅಥವಾ ಆಧ್ಯಾತ್ಮಿಕ ಪ್ರಗತಿಯ ಸಾಧ್ಯತೆಗಳು ಹೆಚ್ಚುತ್ತವೆ.',
            'challenging' => 'ಮುಂಥಾ {house}ನೇ ಭಾವದಲ್ಲಿರುವುದರಿಂದ {year} ವರ್ಷ ಸವಾಲಿನದಾಗಿರಬಹುದು. ಆರೋಗ್ಯ, ಖರ್ಚು ಅಥವಾ ಸಾಲಕ್ಕೆ ಸಂಬಂಧಿಸಿದ ವಿಷಯಗಳಲ್ಲಿ ಎಚ್ಚರಿಕೆಯಿಂದಿರಿ.',
            'mixed' => 'ಮುಂಥಾ {house}ನೇ ಭಾವದಲ್ಲಿದೆ; {year} ವರ್ಷದಲ್ಲಿ ಮಿಶ್ರ ಫಲಿತಾಂಶಗಳು ಸಾಧ್ಯ. ಮುಖ್ಯ ನಿರ್ಧಾರಗಳ ಮೊದಲು ನಿಮ್ಮ ಜಾತಕವನ್ನು ಆಳವಾಗಿ ಪರಿಶೀಲಿಸಿಕೊಳ್ಳಿ.',
        ],
        'ml' => [
            'favorable' => 'മുന്ഥ {house}-ാം ഭാവത്തിലായതിനാൽ {year} വർഷം പൊതുവെ അനുകൂലമായിരിക്കും; ലക്ഷ്യപ്രാപ്തി, സ്ഥിരത, ആത്മീയ പുരോഗതി എന്നിവയ്ക്കുള്ള സാധ്യതകൾ വർധിക്കും.',
            'challenging' => 'മുന്ഥ {house}-ാം ഭാവത്തിലായതിനാൽ {year} വർഷം വെല്ലുവിളി നിറഞ്ഞതാകാം. ആരോഗ്യം, ചെലവ്, കടം എന്നിവയുമായി ബന്ധപ്പെട്ട കാര്യങ്ങളിൽ ശ്രദ്ധ പുലർത്തുക.',
            'mixed' => 'മുന്ഥ {house}-ാം ഭാവത്തിലാണ്; {year} വർഷത്തിൽ സമ്മിശ്ര ഫലങ്ങൾ ഉണ്ടാകാം. പ്രധാന തീരുമാനങ്ങൾക്കു മുമ്പ് നിങ്ങളുടെ ജാതകം ആഴത്തിൽ പരിശോധിക്കുക.',
        ],
        'gu' => [
            'favorable' => 'મુંથા ભાવ {house}માં હોવાથી વર્ષ {year} સામાન્ય રીતે અનુકૂળ રહે છે; લક્ષ્યપ્રાપ્તિ, સ્થિરતા અથવા આધ્યાત્મિક પ્રગતિની સંભાવનાઓ વધે છે.',
            'challenging' => 'મુંથા ભાવ {house}માં હોવાથી વર્ષ {year} પડકારરૂપ બની શકે છે. સ્વાસ્થ્ય, ખર્ચ અથવા ઋણ સંબંધિત બાબતોમાં સાવધાની રાખજો.',
            'mixed' => 'મુંથા ભાવ {house}માં સ્થિત છે; વર્ષ {year}માં મિશ્ર પરિણામ સંભવ છે. મહત્ત્વના નિર્ણયો પહેલાં આપની કુંડળીને ઊંડાણથી તપાસી લેજો.',
        ],
        'bn' => [
            'favorable' => 'মুন্থার অবস্থান ভাব {house}, তাই {year} সাল সাধারণত অনুকূল থাকে; লক্ষ্যপূরণ, স্থিতি বা আধ্যাত্মিক অগ্রগতির সম্ভাবনা বাড়ে।',
            'challenging' => 'মুন্থার অবস্থান ভাব {house}, তাই {year} সাল কিছুটা কঠিন হতে পারে। স্বাস্থ্য, খরচ বা ঋণের ব্যাপারে সাবধান থাকবেন।',
            'mixed' => 'মুন্থার অবস্থান ভাব {house}; {year} সালে মিশ্র ফল দেখা দিতে পারে। বড় সিদ্ধান্তের আগে নিজের কুণ্ডলী ভালো করে দেখে নেবেন।',
        ],
    ];

    private const EXTRA_CSS = '
.cover .year-badge {
  display: inline-block;
  padding: 3mm 8mm;
  margin: 4mm 0 8mm;
  background: var(--brand-primary);
  color: #fff;
  font-size: 22pt;
  font-weight: 700;
  border-radius: 2mm;
}
.indicator-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 5mm; margin: 4mm 0; }
.indicator-card { padding: 5mm; background: #fafafa; border-left: 3px solid var(--brand-primary); }
.indicator-card .label {
  font-size: 9pt; color: var(--brand-secondary);
  text-transform: uppercase; letter-spacing: 0.5pt; margin-bottom: 1mm;
}
.indicator-card .value { font-size: 14pt; font-weight: 600; color: var(--brand-primary); }
.indicator-card .detail { margin-top: 2mm; font-size: 10pt; color: #444; }
';

    private function __construct()
    {
    }

    /** Go `munthaBand`: houses 1/5/9 favorable, 6/8/12 challenging, the rest mixed. */
    private static function munthaBand(int|float $house): string
    {
        if ($house == 1 || $house == 5 || $house == 9) {
            return 'favorable';
        }
        if ($house == 6 || $house == 8 || $house == 12) {
            return 'challenging';
        }

        return 'mixed';
    }

    /** Go `outlookText`: the Year Outlook prose for the locale and band. */
    private static function outlookText(string $locale, int|float $year, string $band, int|float $house): string
    {
        $table = self::OUTLOOK[$locale] ?? self::OUTLOOK['en'];

        return strtr($table[$band] ?? '', ['{house}' => Js::str($house), '{year}' => Js::str($year)]);
    }

    /** @param array<mixed> $data */
    public static function render(array $data, ?Branding $branding = null): string
    {
        $data = Js::map($data);
        $esc = Shared::esc(...);
        $resolved = Shared::resolveBranding($data['Branding'] ?? null, $branding);
        $t = Shared::makeLabeler(self::LABELS, $data['Locale'] ?? null);
        $rawLocale = $data['Locale'] ?? null;
        $loc = \is_string($rawLocale) && $rawLocale !== '' && isset(self::LABELS[$rawLocale]) ? $rawLocale : 'en';

        $subject = Js::map($data['Subject'] ?? null);
        $asc = Js::map($data['Ascendant'] ?? null);
        $muntha = Js::map($data['Muntha'] ?? null);
        $lord = Js::map($data['VarshaLord'] ?? null);
        $planets = Js::list($data['Planets'] ?? null);
        $year = Js::numOrNull($data['TargetYear'] ?? null) ?? 0;

        // Cover page: title + prominent year badge + subject.
        $coverMeta = $esc($t('born')) . ' ' . $esc($subject['BirthDate'] ?? null) . ' ' . $esc($t('at')) . ' '
            . $esc($subject['BirthTime'] ?? null) . '<br>' . $esc($subject['BirthPlace'] ?? null);
        $cover = '<section class="cover">
  ' . Shared::coverBrandingHtml($resolved) . '
  <h1>' . $esc($t('title_varsha')) . '</h1>
  <div class="year-badge">' . $esc($data['TargetYear'] ?? null) . '</div>
  <div class="subject">' . $esc($subject['Name'] ?? null) . '</div>
  <div class="meta">' . $coverMeta . '</div>
  <div class="generated">' . $esc($t('generated')) . ' ' . $esc(Js::formatGenerated($data['GeneratedAt'] ?? null)) . '</div>
</section>';

        // Solar-return moment + ascendant summary.
        $solarReturn = '<h2>' . $esc($t('section_solar_return')) . '</h2>
<p>
  <strong>' . $esc($t('solar_return_at')) . ':</strong> ' . $esc($data['SolarReturnISO'] ?? null) . '<br>
  <strong>' . $esc($t('ascendant')) . ':</strong>
  ' . $esc($asc['sign'] ?? null) . ' (' . $esc($asc['dms_within'] ?? null) . ') —
  ' . $esc($t('nakshatra')) . ': ' . $esc($asc['nakshatra'] ?? null) . ',
  ' . $esc($t('pada')) . ' ' . $esc($asc['pada'] ?? null) . '
</p>';

        // Year indicator cards: muntha placement + varsha lord.
        $indicators = '<div class="indicator-grid">
  <div class="indicator-card">
    <div class="label">' . $esc($t('muntha')) . '</div>
    <div class="value">' . $esc($muntha['sign'] ?? null) . '</div>
    <div class="detail">' . $esc($t('house')) . ' ' . $esc($muntha['house'] ?? null) . ' ' . $esc($t('from_natal_lagna')) . '</div>
  </div>
  <div class="indicator-card">
    <div class="label">' . $esc($t('varsha_lord')) . '</div>
    <div class="value">' . $esc($lord['name'] ?? null) . '</div>
    <div class="detail">' . $esc($t('hora_number')) . ' ' . $esc($lord['hora'] ?? null) . '</div>
  </div>
</div>';

        // Planetary positions at solar return.
        $planetRows = implode('', array_map(
            static function (mixed $planet) use ($esc): string {
                $pl = Js::map($planet);
                $p = Js::map($pl['placement'] ?? null);
                $retro = Js::truthy($pl['retro'] ?? null) ? ' (R)' : '';

                return '<tr><td>' . $esc($pl['name'] ?? null) . $retro . '</td><td>' . $esc($p['sign'] ?? null)
                    . '</td><td>' . $esc($p['dms_within'] ?? null) . '</td><td>' . $esc($pl['house_num'] ?? null)
                    . '</td><td>' . $esc($p['nakshatra'] ?? null) . ' (' . $esc($p['pada'] ?? null) . ')</td></tr>';
            },
            $planets,
        ));
        $planetsTable = '<h2>' . $esc($t('section_planets_sr')) . '</h2>
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

        // Year outlook prose, derived from the muntha house (as in Go).
        $munthaHouse = Js::numOrNull($muntha['house'] ?? null) ?? 0;
        $outlook = '<h2>' . $esc($t('section_outlook')) . '</h2>
<p>' . $esc(self::outlookText($loc, $year, self::munthaBand($munthaHouse), $munthaHouse)) . '</p>';

        $bodyHtml = implode("\n\n", [$cover, $solarReturn, $indicators, $planetsTable, $outlook]);

        return Shared::layoutDocument(
            title: $t('title_varsha') . ' ' . Js::str($data['TargetYear'] ?? null, '') . ' — '
                . Js::str($subject['Name'] ?? null, ''),
            branding: $resolved,
            bodyHtml: $bodyHtml,
            lang: $rawLocale === null ? null : Js::str($rawLocale),
            extraCss: self::EXTRA_CSS,
        );
    }
}
