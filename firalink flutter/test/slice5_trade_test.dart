import 'package:eduex_app/l10n/app_localizations.dart';
import 'package:eduex_app/l10n/lang_am.dart';
import 'package:eduex_app/l10n/lang_en.dart';
import 'package:eduex_app/models/lot_model.dart';
import 'package:eduex_app/models/trade_model.dart';
import 'package:eduex_app/screens/marketplace/compare_quotes_screen.dart';
import 'package:eduex_app/screens/marketplace/create_rfq_screen.dart';
import 'package:eduex_app/screens/marketplace/exporters_screen.dart';
import 'package:eduex_app/screens/marketplace/marketplace_rfqs_screen.dart';
import 'package:eduex_app/screens/marketplace/quote_request_screen.dart';
import 'package:eduex_app/screens/marketplace/widgets/market_filters_sheet.dart';
import 'package:eduex_app/services/api_exception.dart';
import 'package:eduex_app/services/lot_service.dart';
import 'package:eduex_app/services/trade_service.dart';
import 'package:eduex_app/theme/app_theme.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

Map<String, dynamic> _rfqJson({String status = 'open', double? target}) => {
      'id': 5,
      'title': 'Washed G1 for Hamburg',
      'volume_value': 19.2,
      'volume_unit': 'mt',
      'destination': 'Hamburg',
      'incoterm': 'FOB',
      'status': status,
      'quotes_count': 2,
      'target_price_usd_per_lb': target,
      'needed_by': '2027-01-15',
      'sample_first': true,
      'buyer_business': {'id': 9, 'name': 'Hamburg Roasters GmbH', 'business_role': 'buyer', 'verification_tier': 1},
    };

Map<String, dynamic> _quoteJson(int id, {String status = 'submitted', bool canAccept = true, bool below = false, String? blocker, int tier = 1, String level = 'documented'}) => {
      'id': id,
      'rfq_id': 5,
      'status': status,
      'price_usd_per_lb': below ? 4.1 : 4.6,
      'volume_bags': 100,
      'total_value_usd': 60848.0,
      'dispatch_days': 10,
      'can_accept': canAccept,
      'below_minimum': below,
      'blocker': blocker,
      'seller_business': {'id': id + 100, 'name': 'Seller $id PLC', 'business_role': 'exporter', 'verification_tier': tier},
      'lot': {'id': id + 200, 'title': 'Lot $id', 'lot_code': 'L-AAA23$id', 'evidence_level': level},
      'reference_price': below ? {'minimum_usd_per_lb': 4.25, 'source': 'Ethiopian Coffee & Tea Authority (ECTA)', 'effective_date': '2026-11-11'} : null,
    };

class _FakeTrade extends TradeService {
  _FakeTrade({this.openError, this.quotesList = const [], this.myQuote});

  final ApiException? openError;
  final List<Map<String, dynamic>> quotesList;
  final Map<String, dynamic>? myQuote;
  final List<String> calls = [];
  Map<String, dynamic>? posted;
  Map<String, dynamic>? sentQuote;

  @override
  Future<List<Rfq>> openRequests({String? origin}) async {
    if (openError != null) throw openError!;
    return [Rfq.fromJson(_rfqJson())];
  }

  @override
  Future<List<Rfq>> myRequests() async => [Rfq.fromJson(_rfqJson())];

  @override
  Future<List<TradeQuote>> myQuotes() async => [];

  @override
  Future<Map<String, dynamic>?> prefillFromLot(int listingId) async =>
      {'title': 'Sidama natural G2', 'origin_region': 'Sidama', 'processing_method': 'natural', 'grade': 'grade_2', 'incoterm': 'FOB'};

  @override
  Future<Rfq> postRequest(Map<String, dynamic> fields) async {
    posted = fields;
    if ((fields['notes'] ?? '').toString().contains('+251')) {
      throw const ApiException(statusCode: 422, code: 'CONTACT_DETAILS');
    }
    return Rfq.fromJson(_rfqJson());
  }

  @override
  Future<({Rfq rfq, bool isOwn, bool canQuote, TradeQuote? myQuote})> request(int id) async => (
        rfq: Rfq.fromJson(_rfqJson(target: 4.5)),
        isOwn: false,
        canQuote: true,
        myQuote: myQuote == null ? null : TradeQuote.fromJson(myQuote!),
      );

  @override
  Future<TradeQuote> sendQuote(int rfqId, Map<String, dynamic> fields) async {
    sentQuote = fields;
    return TradeQuote.fromJson(_quoteJson(1));
  }

  @override
  Future<({Rfq rfq, bool canDecide, List<TradeQuote> quotes})> quotes(int rfqId, {bool verifiedOnly = true, String? minLevel, String sort = 'price'}) async {
    calls.add('quotes:$verifiedOnly:$minLevel');
    return (rfq: Rfq.fromJson(_rfqJson()), canDecide: true, quotes: quotesList.map(TradeQuote.fromJson).toList());
  }

  @override
  Future<TradeQuote> accept(int quoteId) async {
    calls.add('accept:$quoteId');
    return TradeQuote.fromJson(_quoteJson(quoteId, status: 'accepted'));
  }

  @override
  Future<ExporterProfile> exporter(int id) async => ExporterProfile.fromJson({
        'id': id,
        'name': 'Abyssinia Green PLC',
        'business_role': 'exporter',
        'city': 'Addis Ababa',
        'country': 'Ethiopia',
        'verification_tier': 2,
        'fira_id': 'FL-ABCD-EFGH',
        'member_since': 2026,
        'active_lots': 3,
        'confirmed_deals': 4,
        'usual_reply_hours': null,
        'about': 'Washing stations in Bensa.',
        'lots': [
          {'id': 1, 'slug': 'bensa', 'title': 'Bensa Washed G1', 'origin_region': 'Sidama', 'processing_method': 'washed', 'grade': 'grade_1', 'evidence_level': 'documented'},
        ],
      });
}

class _FakeLots extends LotService {
  _FakeLots(this.lots);

  final List<Map<String, dynamic>> lots;

  @override
  Future<SellerLots> getLots() async => SellerLots.fromJson({
        'business': {'name': 'Seller', 'status': 'verified', 'visible_to_buyers': true},
        'can_manage': true,
        'lots': lots,
      });
}

Map<String, dynamic> _lot(int id, {String status = 'active', bool visible = true}) => {
      'id': id,
      'lot_code': 'L-XYZ23$id',
      'title': 'My lot $id',
      'origin_region': 'Sidama',
      'status': status,
      'evidence_level': 'documented',
      'visible_to_buyers': visible,
      'total_quantity_bags': 150,
    };

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

void _tall(WidgetTester tester) {
  tester.view.physicalSize = const Size(1170, 7000);
  tester.view.devicePixelRatio = 3;
  addTearDown(tester.view.reset);
}

void main() {
  setUp(() => SharedPreferences.setMockInitialValues({}));

  group('Post a request', () {
    testWidgets('only verified sellers note, required fields, unit and incoterm, sample first', (tester) async {
      _tall(tester);
      final trade = _FakeTrade();
      await tester.pumpWidget(_host(CreateRfqScreen(service: trade)));
      await tester.pumpAndSettle();

      expect(find.text('Only verified sellers can see and answer this request.'), findsOneWidget);
      expect(find.text('Verified sellers only'), findsNothing); // no toggle: always verified sellers
      await tester.tap(find.byKey(const Key('rfq-post')));
      await tester.pumpAndSettle();
      expect(find.text('Required'), findsWidgets);
      expect(trade.posted, isNull);

      await tester.enterText(find.byKey(const Key('rfq-title')), 'Washed G1 for Hamburg');
      await tester.enterText(find.byKey(const Key('rfq-volume')), '19.2');
      await tester.tap(find.text('Bags (60 kg)'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Tonnes').last);
      await tester.pumpAndSettle();
      await tester.enterText(find.byKey(const Key('rfq-destination')), 'Hamburg');
      await tester.tap(find.byKey(const Key('incoterm-CIF')));
      await tester.tap(find.byKey(const Key('rfq-sample-first')));
      await tester.pump();
      await tester.tap(find.byKey(const Key('rfq-post')));
      await tester.pumpAndSettle();

      expect(trade.posted!['volume_value'], 19.2);
      expect(trade.posted!['volume_unit'], 'mt');
      expect(trade.posted!['incoterm'], 'CIF');
      expect(trade.posted!['sample_first'], true);
      expect(trade.posted!.containsKey('verified_only'), isFalse);
    });

    testWidgets('request quote from a lot pre-fills origin, process and grade', (tester) async {
      _tall(tester);
      await tester.pumpWidget(_host(CreateRfqScreen(prefillListingId: 7, service: _FakeTrade())));
      await tester.pumpAndSettle();
      expect(find.text('Sidama natural G2'), findsOneWidget);
      expect(find.text('Natural'), findsOneWidget);
      expect(find.text('Grade 2'), findsOneWidget);
    });
  });

  group('Requests', () {
    testWidgets('open requests say plainly when the business is not a verified seller', (tester) async {
      _tall(tester);
      await tester.pumpWidget(_host(MarketplaceRfqsScreen(service: _FakeTrade(openError: const ApiException(statusCode: 403, code: 'VERIFIED_SELLER_REQUIRED')))));
      await tester.pumpAndSettle();
      expect(find.byKey(const Key('sellers-only')), findsOneWidget);
      expect(find.text('Only verified seller businesses can see open requests.'), findsOneWidget);
    });

    testWidgets('verified sellers see the buyer as a business', (tester) async {
      _tall(tester);
      await tester.pumpWidget(_host(MarketplaceRfqsScreen(service: _FakeTrade())));
      await tester.pumpAndSettle();
      expect(find.text('Washed G1 for Hamburg'), findsOneWidget);
      expect(find.textContaining('Hamburg Roasters GmbH'), findsOneWidget);
      expect(find.textContaining('19.20 Tonnes · deliver to Hamburg · FOB'), findsOneWidget);
    });
  });

  group('Quote this request', () {
    testWidgets('only published, visible lots can be attached and the quote is sent', (tester) async {
      _tall(tester);
      final trade = _FakeTrade(myQuote: {
        ..._quoteJson(1, level: 'documented'),
        'lot': {'id': 11, 'title': 'My lot 11', 'lot_code': 'L-XYZ2311', 'evidence_level': 'documented'},
        'ready_to_ship': '2026-12-01',
        'note': 'GrainPro',
      });
      final lots = _FakeLots([_lot(11), _lot(12, status: 'draft'), _lot(13, visible: false)]);
      await tester.pumpWidget(_host(QuoteRequestScreen(rfqId: 5, trade: trade, lots: lots)));
      await tester.pumpAndSettle();

      expect(find.text('Hamburg Roasters GmbH'), findsOneWidget);
      expect(find.text('Verified · Tier 1'), findsOneWidget);
      expect(find.text('Target price: \$4.50 · Sample first: Yes'), findsOneWidget);
      expect(find.byKey(const Key('quote-lot-11')), findsOneWidget);
      expect(find.byKey(const Key('quote-lot-12')), findsNothing);
      expect(find.byKey(const Key('quote-lot-13')), findsNothing);
      expect(find.text('Your quote: Sent'), findsOneWidget);
      expect(find.text('Notes that ask the buyer to pay or talk elsewhere are not sent.'), findsOneWidget);

      await tester.tap(find.byKey(const Key('quote-send')));
      await tester.pumpAndSettle();
      expect(trade.sentQuote!['listing_id'], 11);
      expect(trade.sentQuote!['price_usd_per_lb'], 4.6);
      expect(trade.sentQuote!['volume_bags'], 100);
      expect(trade.sentQuote!['valid_days'], 14);
      expect(trade.sentQuote!['ready_to_ship'], '2026-12-01');
    });

    testWidgets('no published lot: cannot quote', (tester) async {
      _tall(tester);
      await tester.pumpWidget(_host(QuoteRequestScreen(rfqId: 5, trade: _FakeTrade(), lots: _FakeLots([_lot(12, status: 'draft')]))));
      await tester.pumpAndSettle();
      expect(find.byKey(const Key('quote-no-lots')), findsOneWidget);
      expect(tester.widget<FilledButton>(find.byKey(const Key('quote-send'))).onPressed, isNull);
    });
  });

  group('Compare quotes', () {
    testWidgets('tier, lot level, ECTA flag and accept only where the server allows', (tester) async {
      _tall(tester);
      final trade = _FakeTrade(quotesList: [
        _quoteJson(1, below: true, canAccept: false, blocker: 'BELOW_MINIMUM'),
        _quoteJson(2, level: 'lab_backed'),
        _quoteJson(3, tier: 0, canAccept: false, blocker: 'SELLER_NOT_VERIFIED', level: 'claimed'),
      ]);
      await tester.pumpWidget(_host(CompareQuotesScreen(rfqId: 5, service: trade)));
      await tester.pumpAndSettle();

      expect(find.text('Seller 1 PLC'), findsOneWidget);
      expect(find.text('Verified · Tier 1'), findsNWidgets(2));
      expect(find.text('Not verified at the moment'), findsOneWidget);
      expect(find.text('Lab-backed'), findsOneWidget);
      expect(find.byKey(const Key('below-minimum-1')), findsOneWidget);
      expect(find.textContaining('Ethiopian Coffee & Tea Authority (ECTA)'), findsOneWidget);
      expect(find.text('The seller is not verified at the moment, so it cannot be accepted.'), findsOneWidget);
      expect(find.textContaining('Quotes below the ECTA minimum price are flagged'), findsOneWidget);

      expect(tester.widget<FilledButton>(find.byKey(const Key('accept-1'))).onPressed, isNull);
      expect(tester.widget<FilledButton>(find.byKey(const Key('accept-3'))).onPressed, isNull);
      await tester.tap(find.byKey(const Key('accept-2')));
      await tester.pumpAndSettle();
      await tester.tap(find.byKey(const Key('confirm-accept')));
      await tester.pumpAndSettle();
      expect(trade.calls, contains('accept:2'));
    });

    testWidgets('an accepted quote offers the deal room; the filters reload from the server', (tester) async {
      _tall(tester);
      final trade = _FakeTrade(quotesList: [_quoteJson(4, status: 'accepted', canAccept: false)]);
      await tester.pumpWidget(_host(CompareQuotesScreen(rfqId: 5, service: trade)));
      await tester.pumpAndSettle();
      expect(find.byKey(const Key('deal-room-4')), findsOneWidget);

      await tester.tap(find.byKey(const Key('compare-verified-only')));
      await tester.pumpAndSettle();
      expect(trade.calls.last, 'quotes:false:null');
    });
  });

  group('Filters and exporters', () {
    testWidgets('filters count lots on the server and return the choice', (tester) async {
      _tall(tester);
      MarketFilters? chosen;
      MarketFilters? counted;
      await tester.pumpWidget(_host(Builder(
        builder: (context) => Scaffold(
          body: TextButton(
            onPressed: () async => chosen = await MarketFiltersSheet.show(context, initial: const MarketFilters(), countLots: (f) async {
              counted = f;
              return f.processes.contains('natural') ? 3 : 24;
            }),
            child: const Text('open'),
          ),
        ),
      )));
      await tester.pumpAndSettle();
      await tester.tap(find.text('open'));
      await tester.pumpAndSettle();
      expect(find.text('Show 24 lots'), findsOneWidget);

      await tester.tap(find.byKey(const Key('filter-process-natural')));
      await tester.pumpAndSettle();
      await tester.tap(find.byKey(const Key('filter-level-documented')));
      await tester.pumpAndSettle();
      expect(find.text('Show 3 lots'), findsOneWidget);
      expect(counted!.toQuery()['min_level'], 'documented');

      await tester.tap(find.byKey(const Key('filters-show')));
      await tester.pumpAndSettle();
      expect(chosen!.processes, {'natural'});
      expect(chosen!.toQuery()['processes[]'], ['natural']);
    });

    testWidgets('exporter profile shows public fields; reply time hidden without data', (tester) async {
      _tall(tester);
      await tester.pumpWidget(_host(ExporterProfileScreen(organizationId: 3, service: _FakeTrade())));
      await tester.pumpAndSettle();
      expect(find.text('Verified · Tier 2'), findsOneWidget);
      expect(find.text('Deals on Fira Link'), findsOneWidget);
      expect(find.text('Usual reply'), findsNothing);
      expect(find.byKey(const Key('see-what-we-checked')), findsOneWidget);
      expect(find.text('Written by the seller. Fira Link does not edit it.'), findsOneWidget);
      expect(find.text('Documented'), findsOneWidget);
    });
  });

  test('every slice 5 key exists in English and Amharic', () {
    const prefixes = ['market', 'filters', 'rfq', 'unit', 'trade', 'requests', 'request', 'quote', 'compare', 'blocker', 'exporter', 'gradeG4'];
    final keys = kLangEn.keys.where((k) => prefixes.any(k.startsWith)).toList();
    expect(keys.length, greaterThan(100));
    for (final k in keys) {
      expect(kLangAm[k], isNotNull, reason: 'missing Amharic for $k');
      expect(kLangAm[k], isNotEmpty);
    }
  });
}
