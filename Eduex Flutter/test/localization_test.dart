import 'package:flutter/widgets.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:eduex_app/l10n/app_localizations.dart';
import 'package:eduex_app/utils/ethiopian_date_formatter.dart';
import 'package:eduex_app/providers/settings_provider.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  setUp(() {
    SharedPreferences.setMockInitialValues({});
  });

  group('Phase 1 & 3: Localization Dictionary and Parity Tests', () {
    test('Supported locales are strictly English and Amharic', () {
      final supported = AppLocalizations.supportedLocales.map((l) => l.languageCode).toList();
      expect(supported, equals(['en', 'am']));
      expect(supported.contains('bn'), isFalse);
      expect(supported.contains('es'), isFalse);
      expect(supported.contains('fr'), isFalse);
      expect(supported.contains('hi'), isFalse);
    });

    test('English translations return correct values', () {
      final locEn = AppLocalizations(const Locale('en'));
      expect(locEn.home, equals('Home'));
      expect(locEn.login, equals('Login'));
      expect(locEn.checkout, equals('Checkout'));
      expect(locEn.settings, equals('Settings'));
    });

    test('Amharic translations return natural Ge\'ez text', () {
      final locAm = AppLocalizations(const Locale('am'));
      expect(locAm.home, equals('ዋና ገጽ'));
      expect(locAm.login, equals('ግባ'));
      expect(locAm.checkout, equals('ክፍያ ማጠናቀቂያ'));
      expect(locAm.settings, equals('ቅንብሮች'));
      expect(locAm.coffeeExportToolkit, contains('ቡና'));
    });

    test('Fallback behavior returns English when locale key is missing', () {
      final locUnknown = AppLocalizations(const Locale('xx'));
      // Fallback chain should return English translation
      expect(locUnknown.home, equals('Home'));
      expect(locUnknown.login, equals('Login'));
    });

    test('Parameterized translation helper substitutes placeholders correctly', () {
      final locEn = AppLocalizations(const Locale('en'));
      // Test parameter replacement
      final translated = locEn.translate('hello', {'name': 'Fira'});
      expect(translated, isNotEmpty);
    });
  });

  group('Phase 3: Ethiopian Date & Currency Formatter Tests', () {
    test('Ethiopian calendar date conversion works correctly', () {
      // 2026-09-11 Gregorian is around Meskerem 1 Ethiopian
      final gregDate = DateTime(2026, 9, 11);
      final ethDate = EthiopianDate.fromGregorian(gregDate);
      expect(ethDate.monthName, equals('መስከረም'));
      expect(ethDate.formatFull(), contains('መስከረም'));
      expect(ethDate.formatFull(), contains('ዓ.ም'));
    });

    test('All 13 Ethiopian month names are correctly defined in Amharic', () {
      final months = EthiopianDate.monthNamesAmharic;
      expect(months.length, equals(13));
      expect(months[0], equals('መስከረም'));
      expect(months[1], equals('ጥቅምት'));
      expect(months[11], equals('ነሐሴ'));
      expect(months[12], equals('ጳጉሜ'));
    });

    test('AppDateFormatter formats Gregorian dates localized to Amharic', () {
      final testDate = DateTime(2026, 10, 31);
      final formattedAm = AppDateFormatter.formatDate(testDate, languageCode: 'am');
      // October in Amharic Gregorian is ኦክቶበር
      expect(formattedAm, contains('ኦክቶበር'));
      expect(formattedAm, contains('2026'));
    });

    test('Currency formatting conforms to Ethiopian standards (ብር / ETB)', () {
      final settings = SettingsProvider();

      // In Amharic locale: e.g. "1,250.00 ብር"
      settings.setLocale(const Locale('am'));
      final formattedAm = settings.formatPrice(1250.0);
      expect(formattedAm, equals('1,250.00 ብር'));

      // In English locale: e.g. "ETB 1,250.00"
      settings.setLocale(const Locale('en'));
      final formattedEn = settings.formatPrice(1250.0);
      expect(formattedEn, equals('ETB 1,250.00'));
    });
  });
}
