import 'package:eduex_app/l10n/app_localizations.dart';
import 'package:eduex_app/l10n/lang_am.dart';
import 'package:eduex_app/l10n/lang_en.dart';
import 'package:eduex_app/models/deal_room_model.dart';
import 'package:eduex_app/screens/deal_room/deal_room_hub_screen.dart';
import 'package:eduex_app/screens/deal_room/deal_rooms_screen.dart';
import 'package:eduex_app/models/lot_model.dart';
import 'package:eduex_app/screens/marketplace/saved_lots_screen.dart';
import 'package:eduex_app/screens/marketplace/seller_home_screen.dart';
import 'package:eduex_app/services/lot_service.dart';
import 'package:eduex_app/services/deal_room_service.dart';
import 'package:eduex_app/services/trade_service.dart';
import 'package:eduex_app/theme/app_theme.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

Map<String, dynamic> _deal(int id, {String side = 'buyer', bool paused = false}) => {
      'id': id,
      'deal_reference': 'DR-000$id',
      'title': 'Guji G1 for Hamburg',
      'incoterm': 'FOB',
      'target_value_usd': 100000,
      'status': 'under_contract',
      'paused': paused,
      'my_side': side,
      'buyer': {'id': 2, 'name': 'Hamburg Roasters GmbH', 'business_role': 'buyer', 'verification_tier': 1},
      'seller': {'id': 1, 'name': 'Abyssinia Green PLC', 'business_role': 'exporter', 'verification_tier': 1},
      'step_number': 2,
      'step_count': 6,
      'current_step': 'payment_escrow',
      'milestones': <dynamic>[],
    };

class _FakeDeals extends DealRoomService {
  _FakeDeals(this.items);

  final List<Map<String, dynamic>> items;

  @override
  Future<List<DealRoomModel>> getDealRooms() async => items.map(DealRoomModel.fromJson).toList();

  @override
  Future<DealRoomModel> getDealRoom(int id) async => DealRoomModel.fromJson(items.firstWhere((d) => d['id'] == id));
}

class _FakeTrade extends TradeService {
  _FakeTrade(this.lots);

  final List<Map<String, dynamic>> lots;

  @override
  Future<List<Map<String, dynamic>>> savedLots() async => lots;
}

class _FakeLots extends LotService {
  @override
  Future<Map<String, dynamic>> home() async => {
        'business': {'id': 1, 'name': 'Abyssinia Green PLC', 'status': 'in_review', 'visible_to_buyers': false},
        'counts': {'active_lots': 2, 'open_requests': 4, 'samples_to_send': 1},
        'todo': [
          {'kind': 'ship_sample', 'sample_id': 3, 'buyer': 'Hamburg Roasters GmbH', 'lot': 'Guji Natural G1', 'grams': 250, 'due': '2026-11-20'},
          {'kind': 'answer_request', 'rfq_id': 8, 'title': 'Washed G1 for Hamburg', 'volume_value': 19.2, 'volume_unit': 'mt', 'destination': 'Hamburg', 'closes': '2026-11-25'},
          {'kind': 'renew_evidence', 'lot_id': 7, 'lot': 'Guji Natural G1', 'days': 10},
        ],
      };

  @override
  Future<SellerLots> getLots() async => SellerLots.fromJson({
        'business': {'name': 'Abyssinia Green PLC', 'status': 'in_review', 'visible_to_buyers': false},
        'can_manage': true,
        'lots': [
          {'id': 7, 'lot_code': 'L-ABC234', 'title': 'Guji Natural G1', 'origin_region': 'Guji', 'status': 'active', 'evidence_level': 'documented'},
        ],
      });
}

Widget _host(Widget child, {Locale locale = const Locale('en')}) => MaterialApp(
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

void main() {
  setUp(() => SharedPreferences.setMockInitialValues({}));

  testWidgets('deals list: the other business, step line, paused, opens the deal room', (tester) async {
    await tester.pumpWidget(_host(DealRoomsScreen(service: _FakeDeals([_deal(1), _deal(2, side: 'seller', paused: true)]))));
    await tester.pumpAndSettle();

    // Buyer sees the seller business; seller sees the buyer business.
    expect(find.text('Abyssinia Green PLC · DR-0001'), findsOneWidget);
    expect(find.text('Hamburg Roasters GmbH · DR-0002'), findsOneWidget);
    expect(find.text('Step 2 of 6 · Waiting for deposit'), findsOneWidget);
    expect(find.text(kLangEn['dealsPaused']!), findsOneWidget);

    await tester.tap(find.byKey(const Key('deal-1')));
    await tester.pumpAndSettle();
    expect(find.byType(DealRoomHubScreen), findsOneWidget);
  });

  testWidgets('deals list empty state, in Amharic', (tester) async {
    await tester.pumpWidget(_host(DealRoomsScreen(service: _FakeDeals(const [])), locale: const Locale('am')));
    await tester.pumpAndSettle();
    expect(find.text(kLangAm['dealsEmpty']!), findsOneWidget);
  });

  testWidgets('saved lots list and empty state', (tester) async {
    await tester.pumpWidget(_host(SavedLotsScreen(service: _FakeTrade([
      {'id': 7, 'slug': 'guji-g1', 'title': 'Guji Natural G1', 'lot_code': 'L-ABC234', 'origin_region': 'Guji', 'indicative_price_usd_per_lb': '5.20', 'evidence_level': 'documented'},
    ]))));
    await tester.pumpAndSettle();
    expect(find.text('Guji Natural G1'), findsOneWidget);
    expect(find.textContaining('L-ABC234'), findsOneWidget);
    expect(find.textContaining('\$5.20 per lb'), findsOneWidget);

    await tester.pumpWidget(_host(SavedLotsScreen(key: const Key('empty'), service: _FakeTrade(const []))));
    await tester.pumpAndSettle();
    expect(find.text(kLangEn['savedLotsEmpty']!), findsOneWidget);
  });

  testWidgets('seller home: status, counts, samples to ship and requests to answer, hidden banner, my lots', (tester) async {
    tester.view.physicalSize = const Size(1170, 5000);
    tester.view.devicePixelRatio = 3;
    addTearDown(tester.view.reset);
    await tester.pumpWidget(_host(SellerHomeScreen(service: _FakeLots())));
    await tester.pumpAndSettle();

    expect(find.text('Abyssinia Green PLC'), findsWidgets);
    expect(find.text('4'), findsOneWidget);
    expect(find.text(kLangEn['sellerHomeSamplesToSend']!), findsOneWidget);
    expect(find.text('Prepare sample for Hamburg Roasters GmbH'), findsOneWidget);
    expect(find.text('Answer request: Washed G1 for Hamburg'), findsOneWidget);
    expect(find.byKey(const Key('todo-sample-3')), findsOneWidget);
    expect(find.byKey(const Key('todo-request-8')), findsOneWidget);
    expect(find.text(kLangEn['lotsHiddenTitle']!), findsOneWidget);
    expect(find.text(kLangEn['lotsMyLots']!), findsOneWidget);
    expect(tester.takeException(), isNull);
  });
}
