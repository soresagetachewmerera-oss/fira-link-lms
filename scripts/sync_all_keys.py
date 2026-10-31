import json
import re
import sys

sys.stdout.reconfigure(encoding='utf-8')

EN_ARB = 'Eduex Flutter/lib/l10n/app_en.arb'
AM_ARB = 'Eduex Flutter/lib/l10n/app_am.arb'

NEW_KEYS = {
    'deleteAccount': ('Delete Account', 'መለያ ሰርዝ'),
    'deleteAccountConfirm': ('Are you sure you want to delete your account? This action cannot be undone.', 'እርግጠኛ ነዎት መለያዎን መሰረዝ ይፈልጋሉ? ይህ እርምጃ ሊመለስ አይችልም።'),
    'delete': ('Delete', 'ሰርዝ'),
    'cancelReservation': ('Cancel Reservation?', 'ቦታ ማስያዝ ይሰረዝ?'),
    'confirm': ('Confirm', 'አረጋግጥ'),
    'editProfileSubtitle': ('Update your personal information', 'የግል መረጃዎን ያዘምኑ'),
    'changePasswordSubtitle': ('Update your account password', 'የመለያ የይለፍ ቃልዎን ያዘምኑ'),
    'noDataAvailable': ('No data available', 'ምንም መረጃ የለም'),
    'rateApp': ('Rate App', 'መተግበሪያውን ደረጃ ይስጡ'),
    'rateAppSubtitle': ('Rate us on the app store', 'በመተግበሪያ መደብር ውስጥ ደረጃ ይስጡን'),
    'shareApp': ('Share App', 'መተግበሪያውን ያጋሩ'),
    'shareAppSubtitle': ('Share with friends and family', 'ለጓደኞች እና ለቤተሰብ ያጋሩ'),
    'fieldRequired': ('This field is required', 'ይህ ቦታ መሞላት አለበት'),
    'phoneNumber': ('Phone Number', 'ስልክ ቁጥር'),
    'notes': ('Notes / Requests', 'ማስታወሻዎች / ልዩ ጥያቄዎች'),
    'optional': ('Optional', 'አማራጭ'),
    'selectLanguage': ('Select Language', 'ቋንቋ ይምረጡ'),
    'marketIntelligence': ('Market Intelligence', 'የገበያ መረጃ'),
    'offerGenerator': ('Offer Generator', 'የኮንትራት አቅርቦት ማመንጫ'),
    'exportReadiness': ('Export Readiness', 'የኤክስፖርት ዝግጁነት'),
    'replies': ('Replies', 'ምላሾች'),
    'pinned': ('Pinned', 'የተሰካ'),
    'previous': ('Previous', 'የቀደመ'),
    'toolkit': ('Toolkit', 'መሳሪያዎች'),
    'close': ('Close', 'ዝጋ'),
    'next': ('Next', 'ቀጣይ'),
    'profitCalculator': ('Profit Calculator', 'የትርፍ ማስያ'),
    'checkout': ('Checkout', 'ክፍያ ማጠናቀቂያ'),
    'remove': ('Remove', 'አስወግድ'),
    'courseSummary': ('Course Summary', 'የኮርሱ ማጠቃለያ'),
    'show': ('Show', 'አሳይ'),
    'apply': ('Apply', 'ተግብር'),
    'discussion': ('Discussion', 'ውይይት'),
    'coursePrice': ('Course Price', 'የኮርስ ዋጋ'),
    'priceBreakdown': ('Price Breakdown', 'የዋጋ ዝርዝር'),
    'calculationSaved': ('Calculation Saved', 'ስሌቱ ተቀምጧል'),
    'hide': ('Hide', 'ደብቅ'),
    'retry': ('Retry', 'እንደገና ሞክር'),
    'tryAgain': ('Try Again', 'እንደገና ይሞክሩ'),
    'browseCourses': ('Browse Courses', 'ኮርሶችን ያስሱ'),
    'overview': ('Overview', 'አጠቃላይ እይታ'),
    'whatIsIncluded': ("What's Included", 'የተካተቱ ነገሮች'),
    'expeditionDetails': ('Expedition Details', 'የጉዞው ዝርዝሮች'),
    'expeditionItinerary': ('Expedition Itinerary', 'የጉዞ መርሃ ግብር'),
    'pricePerPerson': ('Price per person', 'ዋጋ በአንድ ሰው'),
    'boardingPass': ('Boarding Pass', 'የመግቢያ ፓስፖርት'),
    'registered': ('Registered', 'የተመዘገቡ'),
    'totalAmount': ('TOTAL AMOUNT', 'አጠቃላይ መጠን'),
    'backToTours': ('Back to Tours', 'ወደ ጉዞዎች ተመለስ'),
    'myBookings': ('My Bookings', 'የእኔ ቦታ ማስያዞች'),
    'noTourBookings': ('No Tour Bookings', 'ምንም የጉዞ ቦታ ማስያዝ የለም'),
    'noTourBookingsSubtitle': ('You have not booked any expeditions yet.', 'እስካሁን ምንም ዓይነት ጉዞ አላስያዙም።'),
    'downloadReceipt': ('Download Receipt', 'ደረሰኝ አውርድ'),
    'downloadingReceipt': ('Downloading receipt...', 'ደረሰኝ በማውረድ ላይ...'),
    'receiptDownloadedSuccessfully': ('Receipt downloaded successfully', 'ደረሰኙ በተሳካ ሁኔታ ወርዷል'),
    'failedToDownloadReceipt': ('Failed to download receipt', 'ደረሰኙን ማውረድ አልተቻለም'),
    'sendMessage': ('Send Message', 'መልእክት ላክ'),
    'inbox': ('Inbox', 'ገቢ መልእክቶች'),
    'sentRequests': ('Sent Requests', 'የተላኩ ጥያቄዎች'),
    'camera': ('Camera', 'ካሜራ'),
    'gallery': ('Gallery', 'ፎቶ ማዕከል'),
    'file': ('File', 'ፋይል'),
    'resetAllFilters': ('Reset All Filters', 'ሁሉንም ማጣሪያዎች አጽዳ'),
    'deleteReply': ('Delete Reply', 'ምላሽን ሰርዝ'),
    'deleteReplyConfirm': ('Are you sure you want to delete this reply?', 'እርግጠኛ ነዎት ይህንን ምላሽ መሰረዝ ይፈልጋሉ?'),
    'postThread': ('Post Thread', 'ውይይት ጀምር'),
    'announce': ('Announce', 'ማስታወቂያ'),
    'bundleCheckout': ('Bundle Checkout', 'የጥቅል ክፍያ'),
    'proceedAnyway': ('Proceed Anyway', 'ቢሆንም ይቀጥሉ'),
    'markComplete': ('Mark Complete', 'እንደተጠናቀቀ ምልክት አድርግ'),
    'lessonCompleted': ('Lesson Completed!', 'ትምህርቱ ተጠናቋል!'),
    'inProgress': ('In Progress', 'በሂደት ላይ'),
    'resume': ('Resume', 'ቀጥል'),
    'lesson': ('Lesson', 'ትምህርት'),
}

with open(EN_ARB, 'r', encoding='utf-8') as f:
    en_data = json.load(f)

with open(AM_ARB, 'r', encoding='utf-8') as f:
    am_data = json.load(f)

for k, (en_val, am_val) in NEW_KEYS.items():
    en_data[k] = en_val
    am_data[k] = am_val

# Make sure all keys are in sync
all_keys = sorted(set(list(en_data.keys()) + list(am_data.keys())))
for k in all_keys:
    if k.startswith('@'): continue
    if k not in en_data:
        en_data[k] = k
    if k not in am_data:
        am_data[k] = en_data[k]

with open(EN_ARB, 'w', encoding='utf-8') as f:
    json.dump(en_data, f, ensure_ascii=False, indent=2)

with open(AM_ARB, 'w', encoding='utf-8') as f:
    json.dump(am_data, f, ensure_ascii=False, indent=2)

print(f"ARB files synchronized. Total keys: {len(all_keys)}")

# 1. Generate lang_en.dart
lang_en_code = """// English localization dictionary
const Map<String, String> kLangEn = {
"""
for k in sorted(en_data.keys()):
    if k.startswith('@'): continue
    lang_en_code += f"  '{k}': {json.dumps(en_data[k], ensure_ascii=False)},\n"
lang_en_code += """};

const Map<String, String> langEn = kLangEn;
"""

with open('Eduex Flutter/lib/l10n/lang_en.dart', 'w', encoding='utf-8') as f:
    f.write(lang_en_code)

# 2. Generate lang_am.dart
lang_am_code = """// Amharic (አማርኛ) localization dictionary
const Map<String, String> kLangAm = {
"""
for k in sorted(am_data.keys()):
    if k.startswith('@'): continue
    lang_am_code += f"  '{k}': {json.dumps(am_data[k], ensure_ascii=False)},\n"
lang_am_code += """};

const Map<String, String> langAm = kLangAm;
"""

with open('Eduex Flutter/lib/l10n/lang_am.dart', 'w', encoding='utf-8') as f:
    f.write(lang_am_code)

# 3. Generate app_localizations.dart
getters_code = ""
for k in sorted(en_data.keys()):
    if k.startswith('@'): continue
    getters_code += f"  String get {k} => _t('{k}');\n"

app_localizations_code = f"""import 'package:flutter/widgets.dart';
import 'lang_en.dart';
import 'lang_am.dart';

class AppLocalizations {{
  AppLocalizations(this.locale);

  final Locale locale;

  static const supportedLocales = <Locale>[
    Locale('en'),
    Locale('am'),
  ];

  // Core bilingual string tables (English & Amharic)
  static const Map<String, Map<String, String>> _values =
      <String, Map<String, String>>{{
        'en': kLangEn,
        'am': kLangAm,
      }};

  static AppLocalizations of(BuildContext context) {{
    return Localizations.of<AppLocalizations>(context, AppLocalizations) ?? AppLocalizations(const Locale('en'));
  }}

  /// Core fallback lookup: returns localized string, falls back to English, then to key
  String _t(String key) {{
    final lang = locale.languageCode;
    return _values[lang]?[key] ?? _values['en']?[key] ?? key;
  }}

  /// Parameterized translation helper with placeholder substitution: e.g. translate('hello', {{'name': 'Abebe'}})
  String translate(String key, [Map<String, dynamic>? params]) {{
    var text = _t(key);
    if (params != null) {{
      params.forEach((paramKey, paramVal) {{
        text = text.replaceAll('{{$paramKey}}', paramVal.toString());
      }});
    }}
    return text;
  }}

  static const LocalizationsDelegate<AppLocalizations> delegate =
      AppLocalizationsDelegate();

  // Strongly-typed getters for all localization keys
{getters_code}
}}

class AppLocalizationsDelegate
    extends LocalizationsDelegate<AppLocalizations> {{
  const AppLocalizationsDelegate();

  @override
  bool isSupported(Locale locale) =>
      AppLocalizations.supportedLocales.any((l) => l.languageCode == locale.languageCode);

  @override
  Future<AppLocalizations> load(Locale locale) async {{
    return AppLocalizations(locale);
  }}

  @override
  bool shouldReload(AppLocalizationsDelegate old) => false;
}}

extension AppLocalizationsX on BuildContext {{
  AppLocalizations get l10n => AppLocalizations.of(this);
}}
"""

with open('Eduex Flutter/lib/l10n/app_localizations.dart', 'w', encoding='utf-8') as f:
    f.write(app_localizations_code)

print("Regenerated app_localizations.dart with all getters.")

# 4. Fix const InputDecoration in tour_booking_screen.dart
tour_screen = 'Eduex Flutter/lib/screens/tours/tour_booking_screen.dart'
with open(tour_screen, 'r', encoding='utf-8') as f:
    tour_content = f.read()

tour_content = tour_content.replace("const InputDecoration(labelText: '${context.l10n.", "InputDecoration(labelText: '${context.l10n.")
tour_content = tour_content.replace("const InputDecoration(\n                  labelText: '${context.l10n.", "InputDecoration(\n                  labelText: '${context.l10n.")

with open(tour_screen, 'w', encoding='utf-8') as f:
    f.write(tour_content)

print("Fixed const InputDecoration in tour_booking_screen.dart.")
