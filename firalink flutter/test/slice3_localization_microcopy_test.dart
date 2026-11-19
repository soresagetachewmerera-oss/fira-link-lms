import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:eduex_app/l10n/app_localizations.dart';
import 'package:eduex_app/l10n/lang_en.dart';
import 'package:eduex_app/l10n/lang_am.dart';
import 'package:eduex_app/widgets/fira_error_state.dart';

void main() {
  group('Slice 3: Localization Hardening & Microcopy Tests', () {
    test('UI-004: All new Export Docs Studio and Document Editor keys are present in both EN and AM dictionaries', () {
      final keys = [
        'exportDocsStudio',
        'allTemplates',
        'commercialFinancial',
        'shippingFreight',
        'coffeeCommodity',
        'bankingPayment',
        'declarationsCompliance',
        'tradeOperations',
        'tradeCalculator',
        'templates',
        'shipments',
        'myDocuments',
        'newShipment',
        'finalizeDocument',
        'finalizeAndLock',
        'downloadPdf',
        'downloadExcel',
        'exportDocsStudioPro',
        'firaAiTradeAdvisor',
        'consultFiraAi',
        'monthlySubscription',
        'annualSubscription',
        'partiesAndContact',
        'commodityAndCoffee',
        'logisticsAndShipping',
        'weightsAndCalculations',
        'paymentAndBanking',
        'declarationsAndRemarks',
      ];

      for (final key in keys) {
        expect(kLangEn.containsKey(key), isTrue, reason: 'Missing EN key: $key');
        expect(kLangAm.containsKey(key), isTrue, reason: 'Missing AM key: $key');
        expect(kLangEn[key], isNotEmpty, reason: 'Empty EN value for: $key');
        expect(kLangAm[key], isNotEmpty, reason: 'Empty AM value for: $key');
      }
    });

    test('UI-004: AppLocalizations getters provide valid localized strings for EN and AM', () {
      final locEn = AppLocalizations(const Locale('en'));
      final locAm = AppLocalizations(const Locale('am'));

      expect(locEn.exportDocsStudio, equals('Export Docs Studio'));
      expect(locAm.exportDocsStudio, equals('የኤክስፖርት ሰነዶች ስቱዲዮ'));

      expect(locEn.allTemplates, equals('All Templates (30)'));
      expect(locAm.allTemplates, equals('ሁሉም አብነቶች (30)'));

      expect(locEn.finalizeDocument, equals('Finalize Document'));
      expect(locAm.finalizeDocument, equals('ሰነዱን አጽድቅ'));

      expect(locEn.tradeCalculator, equals('Trade Calculator'));
      expect(locAm.tradeCalculator, equals('የንግድ ማስያ'));
    });

    testWidgets('UI-014: FiraErrorText sanitizes exceptions without leaking raw stack trace', (tester) async {
      final exception = Exception('SQLSTATE[HY000]: General error: database disk image is malformed');
      
      await tester.pumpWidget(
        MaterialApp(
          localizationsDelegates: const [
            AppLocalizations.delegate,
            DefaultMaterialLocalizations.delegate,
            DefaultWidgetsLocalizations.delegate,
          ],
          supportedLocales: AppLocalizations.supportedLocales,
          home: Builder(
            builder: (context) {
              final msg = FiraErrorText.message(context, exception, isAction: true);
              return Scaffold(
                body: Text(msg),
              );
            },
          ),
        ),
      );
      await tester.pumpAndSettle();

      // Verify that internal SQL/stack words do NOT appear in user-facing message
      expect(find.textContaining('SQLSTATE'), findsNothing);
      expect(find.textContaining('HY000'), findsNothing);
      expect(find.textContaining('malformed'), findsNothing);
      expect(find.byType(Text), findsWidgets);
    });

    test('UI-011: Emoji flags and globes removed from CoffeeCandlestickChart', () {
      final file = File('lib/screens/coffee_toolkit/widgets/coffee_candlestick_chart.dart');
      expect(file.existsSync(), isTrue);
      final content = file.readAsStringSync();
      expect(content.contains('🇪🇹'), isFalse, reason: 'Ethiopian flag emoji should be replaced with vector icon');
      expect(content.contains('🌎'), isFalse, reason: 'Globe emoji should be replaced with vector icon');
      expect(content.contains('🌍'), isFalse, reason: 'Globe emoji should be replaced with vector icon');
    });

    test('UI-018: Unicode star "★" replaced with Icon widget for SCA cupping scores', () {
      final listingDetail = File('lib/screens/marketplace/coffee_listing_detail_screen.dart').readAsStringSync();
      expect(listingDetail.contains('★'), isFalse, reason: 'Unicode star character found in coffee_listing_detail_screen.dart');

      final marketplaceHome = File('lib/screens/marketplace/marketplace_home_screen.dart').readAsStringSync();
      expect(marketplaceHome.contains('★'), isFalse, reason: 'Unicode star character found in marketplace_home_screen.dart');
    });
  });
}
