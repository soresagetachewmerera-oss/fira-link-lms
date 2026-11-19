import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:eduex_app/widgets/fira_bottom_dock.dart';
import 'package:eduex_app/widgets/bottom_nav_bar.dart';
import 'package:eduex_app/models/deal_room_model.dart';
import 'package:eduex_app/screens/deal_room/export_contract_esign_screen.dart';
import 'package:eduex_app/screens/marketplace/create_lot_screen.dart';
import 'package:eduex_app/screens/marketplace/create_rfq_screen.dart';
import 'package:eduex_app/screens/onboarding/business_workspace_setup_screen.dart';
import 'package:eduex_app/l10n/app_localizations.dart';
import 'package:eduex_app/theme/app_theme.dart';

Widget _wrap(Widget child, [Locale locale = const Locale('en')]) {
  return MaterialApp(
    theme: AppTheme.theme,
    locale: locale,
    supportedLocales: AppLocalizations.supportedLocales,
    localizationsDelegates: const [
      AppLocalizations.delegate,
      GlobalMaterialLocalizations.delegate,
      GlobalWidgetsLocalizations.delegate,
      GlobalCupertinoLocalizations.delegate,
    ],
    home: child,
  );
}

Map<String, dynamic> _mockDeal() => {
  'id': 101,
  'deal_reference': 'DR-TEST1234',
  'title': 'Test Coffee Contract',
  'incoterm': 'FOB',
  'target_value_usd': 50000,
  'deposit_percent': 30,
  'deposit_usd': 15000,
  'balance_usd': 35000,
  'status': 'under_contract',
  'paused': false,
  'my_side': 'buyer',
  'buyer': {'id': 2, 'name': 'Hamburg Roasters', 'business_role': 'buyer'},
  'seller': {'id': 1, 'name': 'Oromia Coffee Farmers', 'business_role': 'exporter'},
  'lot': {'id': 10, 'title': 'Yirgacheffe Grade 1', 'lot_code': 'L-YIRGA-01', 'price_per_lb': 6.5},
  'buyer_signed': false,
  'seller_signed': true,
  'step_number': 1,
  'step_count': 6,
  'current_step': 'contract_signed',
  'milestones': [],
  'documents': {},
  'activity': [],
};

void main() {
  group('Slice 1 Fixes: Trust, Privacy & Critical Data Safety', () {
    testWidgets('UI-001: FiraBottomDock defaults to 0 inProgressCoursesBadgeCount and displays NO phantom badge', (tester) async {
      await tester.pumpWidget(_wrap(const Scaffold(
        bottomNavigationBar: FiraBottomDock(currentTab: BottomNavTab.home),
      )));
      await tester.pumpAndSettle();

      // Ensure no badge text '2' exists on the screen
      expect(find.text('2'), findsNothing);
    });

    testWidgets('UI-001: FiraBottomDock renders badge only when explicitly given count > 0', (tester) async {
      await tester.pumpWidget(_wrap(const Scaffold(
        bottomNavigationBar: FiraBottomDock(
          currentTab: BottomNavTab.home,
          inProgressCoursesBadgeCount: 5,
        ),
      )));
      await tester.pumpAndSettle();

      expect(find.text('5'), findsOneWidget);
    });

    testWidgets('UI-010: ExportContractEsignScreen contract terms do NOT contain "escrow"', (tester) async {
      final deal = DealRoomModel.fromJson(_mockDeal());
      await tester.pumpWidget(_wrap(ExportContractEsignScreen(
        dealRoom: deal,
      )));
      await tester.pumpAndSettle();

      // Find all Text widgets and verify "escrow" is nowhere in contract terms
      final textFinder = find.byType(Text);
      bool foundEscrow = false;
      bool foundSettlement = false;

      for (final element in textFinder.evaluate()) {
        final textWidget = element.widget as Text;
        final data = textWidget.data ?? '';
        if (data.toLowerCase().contains('escrow')) {
          foundEscrow = true;
        }
        if (data.toLowerCase().contains('milestone settlement')) {
          foundSettlement = true;
        }
      }

      expect(foundEscrow, isFalse, reason: 'Contract terms should never contain the prohibited term "escrow"');
      expect(foundSettlement, isTrue, reason: 'Contract terms should accurately state "milestone settlement deposit"');
    });

    testWidgets('UI-003: CreateLotScreen contains PopScope to guard against accidental data loss', (tester) async {
      await tester.pumpWidget(_wrap(const CreateLotScreen()));
      await tester.pump();

      expect(find.byWidgetPredicate((w) => w is PopScope), findsOneWidget);
    });

    testWidgets('UI-003: CreateRfqScreen contains PopScope to guard against accidental data loss', (tester) async {
      await tester.pumpWidget(_wrap(const CreateRfqScreen()));
      await tester.pump();

      expect(find.byWidgetPredicate((w) => w is PopScope), findsOneWidget);
    });

    testWidgets('UI-003: BusinessWorkspaceSetupScreen contains PopScope to guard against accidental data loss', (tester) async {
      await tester.pumpWidget(_wrap(const BusinessWorkspaceSetupScreen()));
      await tester.pump();

      expect(find.byWidgetPredicate((w) => w is PopScope), findsOneWidget);
    });
  });
}
