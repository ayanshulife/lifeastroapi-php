<?php

declare(strict_types=1);

namespace LifeAstroApi\Pdf\Templates;

use LifeAstroApi\Pdf\Branding;
use LifeAstroApi\Pdf\Js;
use LifeAstroApi\Pdf\Shared;

/**
 * Match Making (Kundli Milan) report -> HTML. A port of the Node.js SDK's
 * src/pdf/templates/matchMaking.ts (byte-identical output, golden-tested).
 *
 * The verdict, dosha and nadi prose is hard-coded per locale (ported verbatim), including the
 * Computed=false honesty guard so a Mangal Dosha check that never ran is never shown as "no dosha".
 *
 * @internal Use {@see \LifeAstroApi\Pdf\Reports}.
 */
final class MatchMaking
{
    private const LABELS = [
        'en' => [
            'title_match' => 'Kundli Milan — Match Compatibility',
            'born' => 'Born',
            'generated' => 'Generated on',
            'section_ashtakoota' => 'Ashtakoota — 8-Fold Compatibility',
            'section_doshas' => 'Doshas',
            'section_final' => 'Final Verdict',
            'koota' => 'Koota',
            'max' => 'Max',
            'score' => 'Score',
            'details' => 'Details',
            'total' => 'Total',
            'nakshatra' => 'Nakshatra',
            'ashtakoota_verdict' => 'Ashtakoota Verdict',
            'mangal_dosha' => 'Mangal Dosha',
            'nadi_dosha' => 'Nadi Dosha',
        ],
        'hi' => [
            'title_match' => 'कुंडली मिलान — मिलान पात्रता',
            'born' => 'जन्म',
            'generated' => 'तैयार',
            'section_ashtakoota' => 'अष्टकूट — 8 गुण मिलान',
            'section_doshas' => 'दोष',
            'section_final' => 'अंतिम निष्कर्ष',
            'koota' => 'कूट',
            'max' => 'अधिकतम',
            'score' => 'अंक',
            'details' => 'विवरण',
            'total' => 'कुल',
            'nakshatra' => 'नक्षत्र',
            'ashtakoota_verdict' => 'अष्टकूट निष्कर्ष',
            'mangal_dosha' => 'मंगल दोष',
            'nadi_dosha' => 'नाड़ी दोष',
        ],
        'mr' => [
            'title_match' => 'कुंडली जुळवणी — मिलन पात्रता',
            'born' => 'जन्म',
            'generated' => 'तयार',
            'section_ashtakoota' => 'अष्टकूट — 8 गुण जुळवणी',
            'section_doshas' => 'दोष',
            'section_final' => 'अंतिम निष्कर्ष',
            'koota' => 'कूट',
            'max' => 'कमाल',
            'score' => 'गुण',
            'details' => 'तपशील',
            'total' => 'एकूण',
            'nakshatra' => 'नक्षत्र',
            'ashtakoota_verdict' => 'अष्टकूट निष्कर्ष',
            'mangal_dosha' => 'मंगळ दोष',
            'nadi_dosha' => 'नाडी दोष',
        ],
        'te' => [
            'title_match' => 'జాతక పొంతన: వివాహ అనుకూలత',
            'born' => 'జననం',
            'generated' => 'రూపొందించిన తేదీ',
            'section_ashtakoota' => 'అష్టకూట: 8 కూటాల పొంతన',
            'section_doshas' => 'దోషాలు',
            'section_final' => 'తుది నిర్ణయం',
            'koota' => 'కూటం',
            'max' => 'గరిష్ఠం',
            'score' => 'గుణాలు',
            'details' => 'వివరాలు',
            'total' => 'మొత్తం',
            'nakshatra' => 'నక్షత్రం',
            'ashtakoota_verdict' => 'అష్టకూట నిర్ణయం',
            'mangal_dosha' => 'కుజ దోషం',
            'nadi_dosha' => 'నాడీ దోషం',
        ],
        'ta' => [
            'title_match' => 'ஜாதகப் பொருத்தம்: திருமண இணக்கம்',
            'born' => 'பிறப்பு',
            'generated' => 'தயாரித்த நாள்',
            'section_ashtakoota' => 'அஷ்டகூடம்: 8 கூடப் பொருத்தம்',
            'section_doshas' => 'தோஷங்கள்',
            'section_final' => 'இறுதி முடிவு',
            'koota' => 'கூடம்',
            'max' => 'அதிகபட்சம்',
            'score' => 'புள்ளிகள்',
            'details' => 'விவரம்',
            'total' => 'மொத்தம்',
            'nakshatra' => 'நட்சத்திரம்',
            'ashtakoota_verdict' => 'அஷ்டகூட முடிவு',
            'mangal_dosha' => 'செவ்வாய் தோஷம்',
            'nadi_dosha' => 'நாடி தோஷம்',
        ],
        'kn' => [
            'title_match' => 'ಜಾತಕ ಹೊಂದಾಣಿಕೆ: ವಿವಾಹ ಅನುರೂಪತೆ',
            'born' => 'ಜನನ',
            'generated' => 'ತಯಾರಿಸಿದ ದಿನಾಂಕ',
            'section_ashtakoota' => 'ಅಷ್ಟಕೂಟ: 8 ಕೂಟಗಳ ಹೊಂದಾಣಿಕೆ',
            'section_doshas' => 'ದೋಷಗಳು',
            'section_final' => 'ಅಂತಿಮ ನಿರ್ಣಯ',
            'koota' => 'ಕೂಟ',
            'max' => 'ಗರಿಷ್ಠ',
            'score' => 'ಗುಣ',
            'details' => 'ವಿವರ',
            'total' => 'ಒಟ್ಟು',
            'nakshatra' => 'ನಕ್ಷತ್ರ',
            'ashtakoota_verdict' => 'ಅಷ್ಟಕೂಟ ನಿರ್ಣಯ',
            'mangal_dosha' => 'ಮಂಗಳ ದೋಷ',
            'nadi_dosha' => 'ನಾಡಿ ದೋಷ',
        ],
        'ml' => [
            'title_match' => 'ജാതകപ്പൊരുത്തം: വിവാഹയോഗ്യത',
            'born' => 'ജനനം',
            'generated' => 'തയ്യാറാക്കിയ തീയതി',
            'section_ashtakoota' => 'അഷ്ടകൂടം: 8 കൂടങ്ങളുടെ പൊരുത്തം',
            'section_doshas' => 'ദോഷങ്ങൾ',
            'section_final' => 'അന്തിമ നിഗമനം',
            'koota' => 'കൂടം',
            'max' => 'പരമാവധി',
            'score' => 'ഗുണം',
            'details' => 'വിശദാംശം',
            'total' => 'ആകെ',
            'nakshatra' => 'നക്ഷത്രം',
            'ashtakoota_verdict' => 'അഷ്ടകൂട നിഗമനം',
            'mangal_dosha' => 'ചൊവ്വാദോഷം',
            'nadi_dosha' => 'നാഡീദോഷം',
        ],
        'gu' => [
            'title_match' => 'કુંડળી મેળાપ: વિવાહ પાત્રતા',
            'born' => 'જન્મ',
            'generated' => 'તૈયાર',
            'section_ashtakoota' => 'અષ્ટકૂટ: 8 ગુણ મેળાપ',
            'section_doshas' => 'દોષ',
            'section_final' => 'અંતિમ નિષ્કર્ષ',
            'koota' => 'કૂટ',
            'max' => 'મહત્તમ',
            'score' => 'ગુણ',
            'details' => 'વિગત',
            'total' => 'કુલ',
            'nakshatra' => 'નક્ષત્ર',
            'ashtakoota_verdict' => 'અષ્ટકૂટ નિષ્કર્ષ',
            'mangal_dosha' => 'મંગળ દોષ',
            'nadi_dosha' => 'નાડી દોષ',
        ],
        'bn' => [
            'title_match' => 'কুণ্ডলী মিলন: যোটক বিচার',
            'born' => 'জন্ম',
            'generated' => 'তৈরি',
            'section_ashtakoota' => 'অষ্টকূট: 8 গুণের মিলন',
            'section_doshas' => 'দোষ',
            'section_final' => 'চূড়ান্ত সিদ্ধান্ত',
            'koota' => 'কূট',
            'max' => 'সর্বোচ্চ',
            'score' => 'প্রাপ্ত গুণ',
            'details' => 'বিবরণ',
            'total' => 'মোট',
            'nakshatra' => 'নক্ষত্র',
            'ashtakoota_verdict' => 'অষ্টকূটের সিদ্ধান্ত',
            'mangal_dosha' => 'মাঙ্গলিক দোষ',
            'nadi_dosha' => 'নাড়ী দোষ',
        ],
    ];

    private const VERDICT_LABELS = [
        'en' => [
            'poor' => 'Poor',
            'fair' => 'Fair',
            'good' => 'Good',
            'excellent' => 'Excellent',
        ],
        'hi' => [
            'poor' => 'अल्प',
            'fair' => 'सामान्य',
            'good' => 'अच्छा',
            'excellent' => 'उत्तम',
        ],
        'mr' => [
            'poor' => 'अल्प',
            'fair' => 'सामान्य',
            'good' => 'चांगला',
            'excellent' => 'उत्तम',
        ],
        'te' => [
            'poor' => 'తక్కువ',
            'fair' => 'సాధారణం',
            'good' => 'మంచిది',
            'excellent' => 'ఉత్తమం',
        ],
        'ta' => [
            'poor' => 'குறைவு',
            'fair' => 'சுமார்',
            'good' => 'நல்லது',
            'excellent' => 'உத்தமம்',
        ],
        'kn' => [
            'poor' => 'ಕಡಿಮೆ',
            'fair' => 'ಸಾಧಾರಣ',
            'good' => 'ಉತ್ತಮ',
            'excellent' => 'ಅತ್ಯುತ್ತಮ',
        ],
        'ml' => [
            'poor' => 'കുറവ്',
            'fair' => 'സാധാരണം',
            'good' => 'നല്ലത്',
            'excellent' => 'ഉത്തമം',
        ],
        'gu' => [
            'poor' => 'અલ્પ',
            'fair' => 'સામાન્ય',
            'good' => 'સારું',
            'excellent' => 'ઉત્તમ',
        ],
        'bn' => [
            'poor' => 'অল্প',
            'fair' => 'সাধারণ',
            'good' => 'ভালো',
            'excellent' => 'উত্তম',
        ],
    ];

    private const SOUTH_MATCH = [
        'te' => [
            'verdict' => [
                'excellent' => 'ఉత్తమ పొంతన: అన్ని రంగాలలో సామరస్యానికి అవకాశం ఎక్కువ.',
                'good' => 'మంచి పొంతన: వివాహానికి అనుకూల సూచనలు ఉన్నాయి.',
                'fair' => 'సాధారణ పొంతన: కొన్ని కూటాలలో లోటు ఉంది, అయినా ముందుకు సాగవచ్చు.',
                'poor' => 'తక్కువ పొంతన: చాలా కూటాలలో లోటు ఉంది, జాగ్రత్తగా ఆలోచించడం అవసరం.',
            ],
            'mangalNotComputed' => 'కుజ దోషం పరిశీలించలేదు: జన్మ స్థలం (అక్షాంశం/రేఖాంశం) ఇవ్వలేదు.',
            'mangalNone' => 'కుజ దోషం లేదు.',
            'typePurn' => 'పూర్ణ',
            'typeMild' => 'స్వల్ప',
            'fromLagna' => 'లగ్నం నుండి {0}',
            'fromMoon' => 'చంద్రుని నుండి {0}',
            'mangal' => 'కుజ దోషం ({0}), భావం: {1}.',
            'cancelled' => ' నివృత్తి: ',
            'nadiNone' => 'నాడీ దోషం లేదు. (వరుడు: {0}, వధువు: {1})',
            'nadiExempted' => 'నాడీ దోషం ఉంది, కానీ శాస్త్రీయ మినహాయింపు వల్ల రద్దయింది ({0})',
            'nadiHigh' => 'నాడీ దోషం: ఇద్దరిదీ ఒకే నాడి ({0}). శాస్త్రపరంగా ప్రతికూలం.',
        ],
        'ta' => [
            'verdict' => [
                'excellent' => 'உத்தமப் பொருத்தம்: எல்லாத் துறைகளிலும் இணக்கத்துக்கான வாய்ப்பு அதிகம்.',
                'good' => 'நல்ல பொருத்தம்: திருமணத்துக்குச் சாதகமான அறிகுறிகள் உள்ளன.',
                'fair' => 'சுமாரான பொருத்தம்: சில கூடங்களில் குறை உள்ளது, ஆனாலும் முன்னேறலாம்.',
                'poor' => 'குறைவான பொருத்தம்: பல கூடங்களில் குறை உள்ளது, கவனமாக யோசிப்பது அவசியம்.',
            ],
            'mangalNotComputed' => 'செவ்வாய் தோஷம் சரிபார்க்கப்படவில்லை: பிறந்த இடம் (அட்சரேகை/தீர்க்கரேகை) கொடுக்கப்படவில்லை.',
            'mangalNone' => 'செவ்வாய் தோஷம் இல்லை.',
            'typePurn' => 'முழு',
            'typeMild' => 'லேசான',
            'fromLagna' => 'லக்னத்திலிருந்து {0}',
            'fromMoon' => 'சந்திரனிலிருந்து {0}',
            'mangal' => 'செவ்வாய் தோஷம் ({0}), வீடு: {1}.',
            'cancelled' => ' நிவர்த்தி: ',
            'nadiNone' => 'நாடி தோஷம் இல்லை. (மணமகன்: {0}, மணமகள்: {1})',
            'nadiExempted' => 'நாடி தோஷம் உள்ளது, ஆனால் சாஸ்திர விதிவிலக்கால் நீங்குகிறது ({0})',
            'nadiHigh' => 'நாடி தோஷம்: இருவருக்கும் ஒரே நாடி ({0}). சாஸ்திரப்படி பாதகமானது.',
        ],
        'kn' => [
            'verdict' => [
                'excellent' => 'ಅತ್ಯುತ್ತಮ ಹೊಂದಾಣಿಕೆ: ಎಲ್ಲಾ ಕ್ಷೇತ್ರಗಳಲ್ಲೂ ಸಾಮರಸ್ಯದ ಸಾಧ್ಯತೆ ಹೆಚ್ಚು.',
                'good' => 'ಉತ್ತಮ ಹೊಂದಾಣಿಕೆ: ವಿವಾಹಕ್ಕೆ ಅನುಕೂಲಕರ ಸೂಚನೆಗಳಿವೆ.',
                'fair' => 'ಸಾಧಾರಣ ಹೊಂದಾಣಿಕೆ: ಕೆಲವು ಕೂಟಗಳಲ್ಲಿ ಕೊರತೆ ಇದೆ, ಆದರೂ ಮುಂದುವರಿಯಬಹುದು.',
                'poor' => 'ಕಡಿಮೆ ಹೊಂದಾಣಿಕೆ: ಹಲವು ಕೂಟಗಳಲ್ಲಿ ಕೊರತೆ ಇದೆ, ಎಚ್ಚರಿಕೆಯಿಂದ ಯೋಚಿಸುವುದು ಅಗತ್ಯ.',
            ],
            'mangalNotComputed' => 'ಮಂಗಳ ದೋಷ ಪರಿಶೀಲಿಸಿಲ್ಲ: ಜನ್ಮ ಸ್ಥಳ (ಅಕ್ಷಾಂಶ/ರೇಖಾಂಶ) ನೀಡಿಲ್ಲ.',
            'mangalNone' => 'ಮಂಗಳ ದೋಷ ಇಲ್ಲ.',
            'typePurn' => 'ಪೂರ್ಣ',
            'typeMild' => 'ಅಲ್ಪ',
            'fromLagna' => 'ಲಗ್ನದಿಂದ {0}',
            'fromMoon' => 'ಚಂದ್ರನಿಂದ {0}',
            'mangal' => 'ಮಂಗಳ ದೋಷ ({0}), ಭಾವ: {1}.',
            'cancelled' => ' ನಿವಾರಣೆ: ',
            'nadiNone' => 'ನಾಡಿ ದೋಷ ಇಲ್ಲ. (ವರ: {0}, ವಧು: {1})',
            'nadiExempted' => 'ನಾಡಿ ದೋಷ ಇದೆ, ಆದರೆ ಶಾಸ್ತ್ರೀಯ ವಿನಾಯಿತಿಯಿಂದ ರದ್ದಾಗಿದೆ ({0})',
            'nadiHigh' => 'ನಾಡಿ ದೋಷ: ಇಬ್ಬರದೂ ಒಂದೇ ನಾಡಿ ({0}). ಶಾಸ್ತ್ರದ ಪ್ರಕಾರ ಪ್ರತಿಕೂಲ.',
        ],
        'ml' => [
            'verdict' => [
                'excellent' => 'ഉത്തമ പൊരുത്തം: എല്ലാ മേഖലകളിലും ഐക്യത്തിനുള്ള സാധ്യത കൂടുതലാണ്.',
                'good' => 'നല്ല പൊരുത്തം: വിവാഹത്തിന് അനുകൂലമായ സൂചനകളുണ്ട്.',
                'fair' => 'സാധാരണ പൊരുത്തം: ചില കൂടങ്ങളിൽ കുറവുണ്ട്, എങ്കിലും മുന്നോട്ട് പോകാം.',
                'poor' => 'കുറഞ്ഞ പൊരുത്തം: പല കൂടങ്ങളിലും കുറവുണ്ട്, ശ്രദ്ധാപൂർവം ആലോചിക്കേണ്ടതുണ്ട്.',
            ],
            'mangalNotComputed' => 'ചൊവ്വാദോഷം പരിശോധിച്ചിട്ടില്ല: ജനനസ്ഥലം (അക്ഷാംശം/രേഖാംശം) നൽകിയിട്ടില്ല.',
            'mangalNone' => 'ചൊവ്വാദോഷം ഇല്ല.',
            'typePurn' => 'പൂർണ',
            'typeMild' => 'അൽപ',
            'fromLagna' => 'ലഗ്നത്തിൽ നിന്ന് {0}',
            'fromMoon' => 'ചന്ദ്രനിൽ നിന്ന് {0}',
            'mangal' => 'ചൊവ്വാദോഷം ({0}), ഭാവം: {1}.',
            'cancelled' => ' പരിഹാരം: ',
            'nadiNone' => 'നാഡീദോഷം ഇല്ല. (വരൻ: {0}, വധു: {1})',
            'nadiExempted' => 'നാഡീദോഷം ഉണ്ട്, എന്നാൽ ശാസ്ത്രീയ ഇളവിനാൽ റദ്ദാകുന്നു ({0})',
            'nadiHigh' => 'നാഡീദോഷം: ഇരുവർക്കും ഒരേ നാഡി ({0}). ശാസ്ത്രപ്രകാരം പ്രതികൂലം.',
        ],
        'gu' => [
            'verdict' => [
                'excellent' => 'ઉત્તમ મેળાપ: તમામ ક્ષેત્રોમાં સામંજસ્યની સંભાવના વધુ છે.',
                'good' => 'સારો મેળાપ: વિવાહ માટે અનુકૂળ યોગ છે.',
                'fair' => 'સામાન્ય મેળાપ: કેટલાક કૂટોમાં ઊણપ છે, છતાં આગળ વધી શકાય છે.',
                'poor' => 'અલ્પ મેળાપ: ઘણા કૂટોમાં ઊણપ છે, કાળજીપૂર્વક વિચાર કરવો આવશ્યક છે.',
            ],
            'mangalNotComputed' => 'મંગળ દોષની તપાસ થઈ નથી: જન્મસ્થળ (અક્ષાંશ/રેખાંશ) આપવામાં આવ્યું નથી.',
            'mangalNone' => 'મંગળ દોષ નથી.',
            'typePurn' => 'પૂર્ણ',
            'typeMild' => 'અલ્પ',
            'fromLagna' => 'લગ્નથી {0}',
            'fromMoon' => 'ચંદ્રથી {0}',
            'mangal' => 'મંગળ દોષ ({0}), ભાવ: {1}.',
            'cancelled' => ' નિવારણ: ',
            'nadiNone' => 'નાડી દોષ નથી. (વર: {0}, વધૂ: {1})',
            'nadiExempted' => 'નાડી દોષ છે, પરંતુ શાસ્ત્રીય અપવાદને કારણે રદ થાય છે ({0})',
            'nadiHigh' => 'નાડી દોષ: બંનેની નાડી એક જ છે ({0}). શાસ્ત્રીય રીતે પ્રતિકૂળ.',
        ],
        'bn' => [
            'verdict' => [
                'excellent' => 'উত্তম মিলন: সব ক্ষেত্রে সামঞ্জস্যের সম্ভাবনা বেশি।',
                'good' => 'ভালো মিলন: বিবাহের জন্য অনুকূল যোগ।',
                'fair' => 'সাধারণ মিলন: কয়েকটি কূটে ঘাটতি আছে, তবু এগোনো যায়।',
                'poor' => 'অল্প মিলন: অনেক কূটে ঘাটতি আছে, ভেবেচিন্তে সিদ্ধান্ত নেওয়া জরুরি।',
            ],
            'mangalNotComputed' => 'মাঙ্গলিক দোষের পরীক্ষা হয়নি: জন্মস্থান (অক্ষাংশ/দ্রাঘিমাংশ) দেওয়া হয়নি।',
            'mangalNone' => 'মাঙ্গলিক দোষ নেই।',
            'typePurn' => 'পূর্ণ',
            'typeMild' => 'অল্প',
            'fromLagna' => 'লগ্ন থেকে {0}',
            'fromMoon' => 'চন্দ্র থেকে {0}',
            'mangal' => 'মাঙ্গলিক দোষ ({0}), ভাব: {1}।',
            'cancelled' => ' নিবারণ: ',
            'nadiNone' => 'নাড়ী দোষ নেই। (পাত্র: {0}, পাত্রী: {1})',
            'nadiExempted' => 'নাড়ী দোষ আছে, তবে শাস্ত্রীয় ব্যতিক্রমের কারণে তা খণ্ডিত হয় ({0})',
            'nadiHigh' => 'নাড়ী দোষ: দুজনেরই একই নাড়ী ({0})। শাস্ত্রমতে প্রতিকূল।',
        ],
    ];

    private const EXTRA_CSS = '
.cover .subjects { display: flex; justify-content: space-around; align-items: flex-start; margin-top: 8mm; }
.cover .subject-col { flex: 1; max-width: 40%; }
.cover .subject-col h2 { color: var(--brand-primary); border: none; font-size: 18pt; margin-bottom: 4mm; padding: 0; }
.cover .subject-col .meta { color: var(--brand-secondary); font-size: 10pt; }
.cover .vs { align-self: center; font-size: 24pt; color: var(--brand-secondary); padding: 0 8mm; }
td.score { text-align: right; font-weight: 600; font-variant-numeric: tabular-nums; }
tr.total td { background: #fafafa; font-weight: 700; border-top: 2px solid var(--brand-primary); border-bottom: none; }
.verdict { margin-top: 6mm; padding: 5mm; border: 1px solid var(--brand-primary); border-radius: 3mm; background: #fafafa; }
.verdict.excellent { border-color: #166534; }
.verdict.good { border-color: #65a30d; }
.verdict.fair { border-color: #ca8a04; }
.verdict.poor { border-color: #dc2626; }
.verdict h3 { margin: 0 0 2mm; font-size: 14pt; }
.dosha-block { margin-top: 4mm; padding: 4mm; background: #fafafa; border-left: 3px solid var(--brand-secondary); }
.dosha-block.has-dosha { border-left-color: #dc2626; }
.dosha-block.cancelled { border-left-color: #ca8a04; }
.dosha-block.no-dosha { border-left-color: #166534; }
';

    private function __construct()
    {
    }

    /** @param array<mixed> $data */
    public static function render(array $data, ?Branding $branding = null): string
    {
        $data = Js::map($data);
        $resolved = Shared::resolveBranding($data['Branding'] ?? null, $branding);
        $locale = isset($data['Locale']) ? Js::str($data['Locale']) : null;
        $t = Shared::makeLabeler(self::LABELS, $locale);
        $esc = Shared::esc(...);

        $boy = Js::map($data['Boy'] ?? null);
        $girl = Js::map($data['Girl'] ?? null);
        $ashtakoota = Js::map($data['Ashtakoota'] ?? null);
        $kootas = Js::list($ashtakoota['Kootas'] ?? null);
        $verdict = $ashtakoota['Verdict'] ?? '';
        $boyName = $boy['name'] ?? '';
        $girlName = $girl['name'] ?? '';

        // Cover page: two subjects side by side, "⚭" between.
        /** @param array<string, mixed> $s */
        $subjectMeta = static fn (array $s): string => $esc($t('born')) . ' ' . $esc($s['birth_date'] ?? null) . '<br>'
            . $esc($s['birth_time'] ?? null) . '<br>' . $esc($s['birth_place'] ?? null) . '<br>'
            . $esc($t('nakshatra')) . ': ' . $esc($s['nakshatra'] ?? null);
        $cover = '<section class="cover">
  ' . Shared::coverBrandingHtml($resolved) . '
  <h1>' . $esc($t('title_match')) . '</h1>
  <div class="subjects">
    <div class="subject-col">
      <h2>' . $esc($boyName) . '</h2>
      <div class="meta">' . $subjectMeta($boy) . '</div>
    </div>
    <div class="vs">⚭</div>
    <div class="subject-col">
      <h2>' . $esc($girlName) . '</h2>
      <div class="meta">' . $subjectMeta($girl) . '</div>
    </div>
  </div>
  <div class="generated">' . $esc($t('generated')) . ' ' . $esc(Js::formatGenerated($data['GeneratedAt'] ?? null)) . '</div>
</section>';

        // Ashtakoota score table (koota / max / score / details), then total row.
        $kootaRows = '';
        foreach ($kootas as $raw) {
            $k = Js::map($raw);
            $kootaRows .= '<tr><td>' . $esc($k['name'] ?? null) . '</td><td class="score">' . $esc($k['Max'] ?? null)
                . '</td><td class="score">' . $esc($k['score'] ?? null) . '</td><td>' . $esc($k['Reason'] ?? null) . '</td></tr>';
        }
        $totalRow = '<tr class="total"><td>' . $esc($t('total')) . '</td><td class="score">' . $esc($ashtakoota['Max'] ?? null)
            . '</td><td class="score">' . $esc($ashtakoota['Total'] ?? null) . '</td><td>'
            . $esc(self::verdictLabel($locale, $verdict)) . '</td></tr>';
        $ashtakootaTable = '<h2>' . $esc($t('section_ashtakoota')) . '</h2>
<table>
  <thead>
    <tr>
      <th>' . $esc($t('koota')) . '</th>
      <th>' . $esc($t('max')) . '</th>
      <th>' . $esc($t('score')) . '</th>
      <th>' . $esc($t('details')) . '</th>
    </tr>
  </thead>
  <tbody>' . $kootaRows . $totalRow . '</tbody>
</table>
<div class="verdict ' . $esc($verdict) . '">
  <h3>' . $esc($t('ashtakoota_verdict')) . ': ' . $esc(self::verdictLabel($locale, $verdict)) . '</h3>
  <p>' . $esc(self::verdictDescription($locale, $verdict)) . '</p>
</div>';

        // Dosha analysis: Mangal (boy + girl) + Nadi.
        $mangalBoy = Js::map($data['MangalDoshaBoy'] ?? null);
        $mangalGirl = Js::map($data['MangalDoshaGirl'] ?? null);
        $nadi = Js::map($data['NadiDosha'] ?? null);
        $doshas = '<h2>' . $esc($t('section_doshas')) . '</h2>
<h3>' . $esc($t('mangal_dosha')) . ' — ' . $esc($boyName) . '</h3>
<div class="dosha-block ' . $esc(self::doshaClass($mangalBoy)) . '">' . $esc(self::doshaSummary($locale, $mangalBoy)) . '</div>
<h3>' . $esc($t('mangal_dosha')) . ' — ' . $esc($girlName) . '</h3>
<div class="dosha-block ' . $esc(self::doshaClass($mangalGirl)) . '">' . $esc(self::doshaSummary($locale, $mangalGirl)) . '</div>
<h3>' . $esc($t('nadi_dosha')) . '</h3>
<div class="dosha-block ' . $esc(self::nadiClass($nadi)) . '">' . $esc(self::nadiSummary($locale, $nadi)) . '</div>';

        // Final verdict band.
        $finalVerdict = '<h2>' . $esc($t('section_final')) . '</h2>
<div class="verdict ' . $esc($verdict) . '">
  <p>' . $esc(self::matchVerdict($data)) . '</p>
</div>';

        $bodyHtml = implode("\n\n", [$cover, $ashtakootaTable, $doshas, $finalVerdict]);

        return Shared::layoutDocument(
            lang: $locale,
            title: $t('title_match') . ' — ' . Js::str($boyName) . ' × ' . Js::str($girlName),
            branding: $resolved,
            bodyHtml: $bodyHtml,
            extraCss: self::EXTRA_CSS,
        );
    }

    /** Narrow a request locale to one we have labels for (fail-open to "en"). */
    private static function pickLocale(?string $loc): string
    {
        return $loc !== null && $loc !== '' && isset(self::LABELS[$loc]) ? $loc : 'en';
    }

    private static function verdictLabel(?string $loc, mixed $verdict): mixed
    {
        $table = self::VERDICT_LABELS[self::pickLocale($loc)];

        return $table[Js::str($verdict)] ?? $verdict;
    }

    /** Short prose describing what the Ashtakoota score band means. */
    private static function verdictDescription(?string $loc, mixed $verdict): string
    {
        $l = self::pickLocale($loc);
        $south = self::SOUTH_MATCH[$l] ?? null;
        if ($south !== null) {
            if ($verdict === 'excellent' || $verdict === 'good' || $verdict === 'fair') {
                return $south['verdict'][$verdict];
            }

            return $south['verdict']['poor'];
        }
        switch ($verdict) {
            case 'excellent':
                if ($l === 'hi') {
                    return 'उत्तम मिलान — सभी क्षेत्रों में सामंजस्य की संभावना अधिक है।';
                }
                if ($l === 'mr') {
                    return 'उत्तम जुळवणी — सर्व क्षेत्रांत सामंजस्याची शक्यता अधिक आहे.';
                }

                return 'Excellent match — high compatibility across all areas.';
            case 'good':
                if ($l === 'hi') {
                    return 'अच्छा मिलान — विवाह के लिए अनुकूल योग।';
                }
                if ($l === 'mr') {
                    return 'चांगली जुळवणी — विवाहासाठी अनुकूल योग.';
                }

                return 'Good match — favorable indications for marriage.';
            case 'fair':
                if ($l === 'hi') {
                    return 'सामान्य मिलान — कुछ कूटों में कमी, परंतु आगे बढ़ने योग्य।';
                }
                if ($l === 'mr') {
                    return 'सामान्य जुळवणी — काही कूटांमध्ये कमतरता, परंतु पुढे जाण्यायोग्य.';
                }

                return 'Fair match — some kootas underscore but workable with awareness.';
            default:
                if ($l === 'hi') {
                    return 'अल्प मिलान — कई कूटों में कमी, सावधानीपूर्वक विचार आवश्यक।';
                }
                if ($l === 'mr') {
                    return 'अल्प जुळवणी — अनेक कूटांमध्ये कमतरता, काळजीपूर्वक विचार आवश्यक.';
                }

                return 'Low score — multiple kootas underscore; careful consideration advised.';
        }
    }

    /** @param array<string, mixed> $d */
    private static function doshaClass(array $d): string
    {
        if (!Js::truthy($d['Computed'] ?? null)) {
            return 'not-computed';
        }
        if (Js::truthy($d['HasDosha'] ?? null) && Js::truthy($d['Cancelled'] ?? null)) {
            return 'cancelled';
        }
        if (Js::truthy($d['HasDosha'] ?? null)) {
            return 'has-dosha';
        }

        return 'no-dosha';
    }

    /** JS `Array.isArray(items) ? items.join(sep) : ""`. */
    private static function joinStrings(mixed $items, string $sep): string
    {
        if (!Js::isList($items)) {
            return '';
        }

        return implode($sep, array_map(
            static fn (mixed $e): string => $e === null ? '' : Js::str($e),
            Js::list($items),
        ));
    }

    /** JS `Number(v)`: the coercion behind `h > 0` and `total >= 24`. */
    private static function jsNumber(mixed $v): float
    {
        if ($v === null) {
            return 0.0;
        }
        if (\is_bool($v)) {
            return $v ? 1.0 : 0.0;
        }
        if (\is_int($v) || \is_float($v)) {
            return (float) $v;
        }
        if (\is_array($v)) {
            return array_is_list($v) ? self::jsNumber(Js::str($v)) : NAN;
        }
        if (!\is_string($v)) {
            return NAN;
        }
        $s = Js::trim($v);
        if ($s === '') {
            return 0.0;
        }
        if (preg_match('/^0[xX][0-9a-fA-F]+$/D', $s) === 1) {
            return (float) hexdec(substr($s, 2));
        }
        if (preg_match('/^([+-]?)Infinity$/D', $s, $m) === 1) {
            return $m[1] === '-' ? -INF : INF;
        }
        if (preg_match('/^[+-]?([0-9]+\.?[0-9]*|\.[0-9]+)([eE][+-]?[0-9]+)?$/D', $s) !== 1) {
            return NAN;
        }

        return (float) $s;
    }

    /**
     * Mangal Dosha trigger houses: positive = from the Lagna, negative (its absolute value) = from
     * the Moon.
     *
     * @return array{list<string>, list<string>}
     */
    private static function splitHouses(mixed $housesOccupied): array
    {
        $lagna = [];
        $moon = [];
        foreach (Js::list($housesOccupied) as $h) {
            $n = self::jsNumber($h);
            if ($n > 0) {
                $lagna[] = Js::str($h);
            } elseif ($n < 0) {
                $moon[] = \is_int($h) ? (string) -$h : Js::str(-$n);
            }
        }

        return [$lagna, $moon];
    }

    /**
     * Mangal Dosha summary text, including the Computed=false honesty guard.
     *
     * @param array<string, mixed> $d
     */
    private static function doshaSummary(?string $loc, array $d): string
    {
        $l = self::pickLocale($loc);
        $south = self::SOUTH_MATCH[$l] ?? null;
        $type = $d['Type'] ?? null;
        if ($south !== null) {
            if (!Js::truthy($d['Computed'] ?? null)) {
                return $south['mangalNotComputed'];
            }
            if (!Js::truthy($d['HasDosha'] ?? null)) {
                return $south['mangalNone'];
            }
            $typeText = $type === 'purn' ? $south['typePurn'] : ($type === 'mild' ? $south['typeMild'] : Js::str($type, ''));
            [$lag, $moon] = self::splitHouses($d['HousesOccupied'] ?? null);
            $parts = [];
            if ($lag !== []) {
                $parts[] = strtr($south['fromLagna'], ['{0}' => implode(', ', $lag)]);
            }
            if ($moon !== []) {
                $parts[] = strtr($south['fromMoon'], ['{0}' => implode(', ', $moon)]);
            }
            $out = strtr($south['mangal'], ['{0}' => $typeText, '{1}' => implode('; ', $parts)]);
            if (Js::truthy($d['Cancelled'] ?? null)) {
                $out .= $south['cancelled'] . self::joinStrings($d['CancelReasons'] ?? null, ', ');
            }

            return $out;
        }
        if (!Js::truthy($d['Computed'] ?? null)) {
            if ($l === 'hi') {
                return 'मंगल दोष की जाँच नहीं हुई — जन्म स्थान (अक्षांश/देशांतर) नहीं दिया गया।';
            }
            if ($l === 'mr') {
                return 'मंगळ दोष तपासला नाही — जन्मस्थान (अक्षांश/रेखांश) दिलेले नाही.';
            }

            return 'Mangal Dosha not evaluated — birth latitude/longitude not provided.';
        }
        if (!Js::truthy($d['HasDosha'] ?? null)) {
            if ($l === 'hi') {
                return 'मंगल दोष नहीं है।';
            }
            if ($l === 'mr') {
                return 'मंगळ दोष नाही.';
            }

            return 'No Mangal Dosha present.';
        }
        // Type is a code ("purn"/"mild"): show a readable label, not the raw code.
        $typeLabel = $l === 'hi' || $l === 'mr'
            ? ($type === 'purn' ? 'पूर्ण' : ($type === 'mild' ? 'अल्प' : Js::str($type, '')))
            : ($type === 'purn' ? 'full' : ($type === 'mild' ? 'mild' : Js::str($type, '')));
        [$lagnaH, $moonH] = self::splitHouses($d['HousesOccupied'] ?? null);
        $houseParts = [];
        if ($l === 'hi') {
            if ($lagnaH !== []) {
                $houseParts[] = 'लग्न से ' . implode(', ', $lagnaH);
            }
            if ($moonH !== []) {
                $houseParts[] = 'चंद्र से ' . implode(', ', $moonH);
            }
        } elseif ($l === 'mr') {
            if ($lagnaH !== []) {
                $houseParts[] = 'लग्नापासून ' . implode(', ', $lagnaH);
            }
            if ($moonH !== []) {
                $houseParts[] = 'चंद्रापासून ' . implode(', ', $moonH);
            }
        } else {
            if ($lagnaH !== []) {
                $houseParts[] = implode(', ', $lagnaH) . ' from lagna';
            }
            if ($moonH !== []) {
                $houseParts[] = implode(', ', $moonH) . ' from Moon';
            }
        }
        $houses = implode('; ', $houseParts);
        if ($l === 'hi') {
            $out = 'मंगल दोष (' . $typeLabel . ') — भाव: ' . $houses . '।';
        } elseif ($l === 'mr') {
            $out = 'मंगळ दोष (' . $typeLabel . ') — स्थाने: ' . $houses . '.';
        } else {
            $out = 'Mangal Dosha (' . $typeLabel . ') — house(s): ' . $houses . '.';
        }
        if (Js::truthy($d['Cancelled'] ?? null)) {
            $reasons = self::joinStrings($d['CancelReasons'] ?? null, ', ');
            if ($l === 'hi' || $l === 'mr') {
                $out .= ' रद्द: ' . $reasons;
            } else {
                $out .= ' Cancelled: ' . $reasons;
            }
        }

        return $out;
    }

    /** @param array<string, mixed> $n */
    private static function nadiClass(array $n): string
    {
        return match ($n['Severity'] ?? null) {
            'exempted' => 'cancelled',
            'high' => 'has-dosha',
            default => 'no-dosha',
        };
    }

    /** @param array<string, mixed> $n */
    private static function nadiSummary(?string $loc, array $n): string
    {
        $l = self::pickLocale($loc);
        $boy = Js::str($n['BoyNadi'] ?? '');
        $girl = Js::str($n['GirlNadi'] ?? '');
        $severity = $n['Severity'] ?? null;
        $south = self::SOUTH_MATCH[$l] ?? null;
        if ($south !== null) {
            if ($severity === 'none') {
                return strtr($south['nadiNone'], ['{0}' => $boy, '{1}' => $girl]);
            }
            if ($severity === 'exempted') {
                return strtr($south['nadiExempted'], ['{0}' => self::joinStrings($n['Exceptions'] ?? null, ', ')]);
            }

            return strtr($south['nadiHigh'], ['{0}' => $boy]);
        }
        if ($severity === 'none') {
            if ($l === 'hi') {
                return 'नाड़ी दोष नहीं है। (वर: ' . $boy . ', वधू: ' . $girl . ')';
            }
            if ($l === 'mr') {
                return 'नाडी दोष नाही. (वर: ' . $boy . ', वधू: ' . $girl . ')';
            }

            return 'No Nadi Dosha. (Boy: ' . $boy . ', Girl: ' . $girl . ')';
        }
        if ($severity === 'exempted') {
            $exc = self::joinStrings($n['Exceptions'] ?? null, ', ');
            if ($l === 'hi') {
                return 'नाड़ी दोष है परन्तु शास्त्रीय अपवाद से रद्द (' . $exc . ')';
            }
            if ($l === 'mr') {
                return 'नाडी दोष आहे परंतु शास्त्रीय अपवादाने रद्द (' . $exc . ')';
            }

            return 'Nadi Dosha present but exempted by classical rule (' . $exc . ')';
        }
        // "high"
        if ($l === 'hi') {
            return 'नाड़ी दोष — दोनों की नाड़ी समान (' . $boy . ')। शास्त्रीय रूप से प्रतिकूल।';
        }
        if ($l === 'mr') {
            return 'नाडी दोष — दोघांची नाडी समान (' . $boy . '). शास्त्रानुसार प्रतिकूल.';
        }

        return 'Nadi Dosha — both share ' . $boy . ' nadi. Classically unfavorable.';
    }

    /**
     * Final verdict line: the handler-supplied `FinalVerdict` when present, else derived from the
     * total.
     *
     * @param array<string, mixed> $data
     */
    private static function matchVerdict(array $data): mixed
    {
        if (Js::truthy($data['FinalVerdict'] ?? null)) {
            return $data['FinalVerdict'];
        }
        $total = Js::map($data['Ashtakoota'] ?? null)['Total'] ?? 0;
        if (self::jsNumber($total) >= 24) {
            return 'Marriage indications are favorable. Study both charts for the timing.';
        }

        return 'Score is below conventional threshold (24/36). Consider remedies and a detailed study of both charts.';
    }
}
