import 'dart:convert';
import 'package:eduex_app/l10n/app_localizations.dart';
import 'package:eduex_app/screens/marketplace/subscription_management_screen.dart';
import 'package:eduex_app/services/marketplace_service.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class _FakeMarketplaceService extends MarketplaceService {
  _FakeMarketplaceService({
    required this.snapshotResponse,
    this.cancelResponse,
  });

  final http.Response snapshotResponse;
  final http.Response? cancelResponse;
  bool cancelCalled = false;

  @override
  Future<http.Response> getOrganizationEntitlements() async => snapshotResponse;

  @override
  Future<http.Response> cancelSubscription() async {
    cancelCalled = true;
    return cancelResponse ?? http.Response('{"success": true}', 200);
  }
}

Widget _buildTestApp(Widget child) {
  return MaterialApp(
    localizationsDelegates: const [
      AppLocalizations.delegate,
      GlobalMaterialLocalizations.delegate,
      GlobalWidgetsLocalizations.delegate,
      GlobalCupertinoLocalizations.delegate,
    ],
    supportedLocales: const [Locale('en'), Locale('am')],
    home: child,
  );
}

void main() {
  setUp(() => SharedPreferences.setMockInitialValues({}));

  final mockPaidSnapshot = {
    'organization': {
      'id': 1,
      'name': 'Sidama Coffee Union',
      'slug': 'sidama-coffee',
      'status': 'active',
    },
    'plan': {
      'id': 2,
      'key': 'professional',
      'name': 'Professional Export Desk',
      'description': 'Advanced trading features and quotas',
    },
    'subscription': {
      'id': 42,
      'plan_key': 'professional',
      'plan_name': 'Professional Export Desk',
      'status': 'active',
      'billing_period': 'monthly',
      'current_period_end': '2026-12-15T00:00:00.000000Z',
      'grace_until': null,
      'cancel_at_period_end': false,
    },
    'is_owner': true,
    'usage': {
      'active_lots': {'limit': 15, 'used': 6, 'remaining': 9},
      'rfq_responses_per_month': {'limit': 50, 'used': 12, 'remaining': 38},
      'seats': {'limit': 5, 'used': 3, 'remaining': 2},
      'active_deal_rooms': {'limit': 10, 'used': 4, 'remaining': 6},
      'storage_gb': {'limit': 10, 'used': 2, 'remaining': 8},
    },
  };

  final mockGraceSnapshot = {
    'organization': {
      'id': 1,
      'name': 'Yirgacheffe Farmers Co-op',
      'slug': 'yirgacheffe-coop',
      'status': 'active',
    },
    'plan': {
      'id': 3,
      'key': 'enterprise',
      'name': 'Enterprise Global',
    },
    'subscription': {
      'id': 99,
      'plan_key': 'enterprise',
      'plan_name': 'Enterprise Global',
      'status': 'grace',
      'billing_period': 'annual',
      'current_period_end': '2026-11-20T00:00:00.000000Z',
      'grace_until': '2026-12-04T00:00:00.000000Z',
      'cancel_at_period_end': false,
    },
    'is_owner': true,
    'usage': {
      'active_lots': {'limit': -1, 'used': 45, 'remaining': -1},
      'rfq_responses_per_month': {'limit': -1, 'used': 120, 'remaining': -1},
      'seats': {'limit': 20, 'used': 18, 'remaining': 2},
      'active_deal_rooms': {'limit': -1, 'used': 25, 'remaining': -1},
      'storage_gb': {'limit': 50, 'used': 15, 'remaining': 35},
    },
  };

  final mockStarterSnapshot = {
    'organization': {
      'id': 10,
      'name': 'New Trader PLC',
      'slug': 'new-trader',
      'status': 'active',
    },
    'plan': {
      'id': 1,
      'key': 'starter',
      'name': 'Starter (Free)',
    },
    'subscription': {
      'id': null,
      'plan_key': 'starter',
      'plan_name': 'Starter (Free)',
      'status': 'active',
      'billing_period': 'monthly',
      'current_period_end': null,
      'grace_until': null,
      'cancel_at_period_end': false,
    },
    'is_owner': true,
    'usage': {
      'active_lots': {'limit': 3, 'used': 1, 'remaining': 2},
      'rfq_responses_per_month': {'limit': 5, 'used': 2, 'remaining': 3},
      'seats': {'limit': 2, 'used': 1, 'remaining': 1},
      'active_deal_rooms': {'limit': 2, 'used': 0, 'remaining': 2},
      'storage_gb': {'limit': 1, 'used': 0, 'remaining': 1},
    },
  };

  group('SubscriptionManagementScreen Widget Tests', () {
    testWidgets('renders paid active plan with quotas and owner cancel button', (tester) async {
      tester.view.physicalSize = const Size(800, 1400);
      tester.view.devicePixelRatio = 1.0;
      addTearDown(() => tester.view.resetPhysicalSize());

      final service = _FakeMarketplaceService(
        snapshotResponse: http.Response(jsonEncode({'success': true, 'data': mockPaidSnapshot}), 200),
      );

      await tester.pumpWidget(_buildTestApp(SubscriptionManagementScreen(service: service)));
      await tester.pumpAndSettle();

      expect(find.byKey(const Key('subscription-plan-card')), findsOneWidget);
      expect(find.text('Professional Export Desk'), findsOneWidget);
      expect(find.text('ACTIVE'), findsOneWidget);
      expect(find.text('MONTHLY'), findsOneWidget);
      expect(find.text('2026-12-15'), findsOneWidget);

      // Quota meters
      expect(find.byKey(const Key('quota-meter-active-lots')), findsOneWidget);
      expect(find.text('6 / 15'), findsOneWidget);
      expect(find.byKey(const Key('quota-meter-rfq-quotes')), findsOneWidget);
      expect(find.text('12 / 50'), findsOneWidget);
      expect(find.byKey(const Key('quota-meter-team-seats')), findsOneWidget);
      expect(find.text('3 / 5'), findsOneWidget);
      expect(find.byKey(const Key('quota-meter-active-deals')), findsOneWidget);
      expect(find.text('4 / 10'), findsOneWidget);
      expect(find.byKey(const Key('quota-meter-storage-quota')), findsOneWidget);
      expect(find.text('2 / 10 GB'), findsOneWidget);

      // Upgrade button & Cancel renewal button
      expect(find.byKey(const Key('upgrade-plan-button')), findsOneWidget);
      expect(find.byKey(const Key('cancel-renewal-button')), findsOneWidget);

      // Test cancel renewal flow
      await tester.tap(find.byKey(const Key('cancel-renewal-button')));
      await tester.pumpAndSettle();

      // Dialog opens
      expect(find.text('Cancel Renewal'), findsWidgets);
      expect(find.byType(AlertDialog), findsOneWidget);

      // Confirm cancellation
      await tester.tap(find.widgetWithText(ElevatedButton, 'Cancel Renewal'));
      await tester.pumpAndSettle();

      expect(service.cancelCalled, isTrue);
    });

    testWidgets('renders grace period alert when subscription status is grace', (tester) async {
      tester.view.physicalSize = const Size(800, 1400);
      tester.view.devicePixelRatio = 1.0;
      addTearDown(() => tester.view.resetPhysicalSize());

      final service = _FakeMarketplaceService(
        snapshotResponse: http.Response(jsonEncode({'success': true, 'data': mockGraceSnapshot}), 200),
      );

      await tester.pumpWidget(_buildTestApp(SubscriptionManagementScreen(service: service)));
      await tester.pumpAndSettle();

      expect(find.text('Grace'), findsOneWidget);
      expect(find.textContaining('Grace active until: 2026-12-04'), findsOneWidget);
      expect(find.text('45 / Unlimited'), findsOneWidget);
    });

    testWidgets('renders starter baseline notice and hides cancel renewal button', (tester) async {
      tester.view.physicalSize = const Size(800, 1400);
      tester.view.devicePixelRatio = 1.0;
      addTearDown(() => tester.view.resetPhysicalSize());

      final service = _FakeMarketplaceService(
        snapshotResponse: http.Response(jsonEncode({'success': true, 'data': mockStarterSnapshot}), 200),
      );

      await tester.pumpWidget(_buildTestApp(SubscriptionManagementScreen(service: service)));
      await tester.pumpAndSettle();

      expect(find.text('Starter (Free)'), findsOneWidget);
      expect(find.textContaining('Free baseline tier'), findsOneWidget);
      expect(find.byKey(const Key('cancel-renewal-button')), findsNothing);
      expect(find.byKey(const Key('upgrade-plan-button')), findsOneWidget);
    });
  });
}
