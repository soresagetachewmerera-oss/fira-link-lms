import 'dart:convert';

import 'package:eduex_app/l10n/app_localizations.dart';
import 'package:eduex_app/l10n/lang_am.dart';
import 'package:eduex_app/models/market_series_detail.dart';
import 'package:eduex_app/models/market_watch.dart';
import 'package:eduex_app/providers/market_watch_provider.dart';
import 'package:eduex_app/services/market_data_service.dart' show MarketUnavailableException;
import 'package:eduex_app/services/market_watch_service.dart';
import 'package:eduex_app/theme/app_theme.dart';
import 'package:eduex_app/widgets/market/market_detail_chart.dart';
import 'package:eduex_app/widgets/market/market_sparkline.dart';
import 'package:eduex_app/widgets/market/market_watch_card.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:shared_preferences/shared_preferences.dart';

// ── Fixtures in the server's schema 1 ─────────────────────────────────────

Map<String, dynamic> _row(String key, {double? price = 249.09, String status = 'daily_close', String label = 'Daily close · 2026-11-26', String direction = 'up', bool home = true}) => {
      'key': key,
      'name': '$key full name',
      'short_name': key == 'ICO_COMPOSITE' ? 'ICO composite' : key,
      'kind': 'daily_close',
      'currency': 'USX',
      'unit': 'lb',
      'unit_label': 'US¢/lb',
      'decimals': 2,
      'price': price,
      'prev_close': price == null ? null : 247.25,
      'change': price == null ? null : 1.84,
      'change_pct': price == null ? null : 0.74,
      'direction': price == null ? null : direction,
      'trend': price == null ? null : 'up',
      'status': price == null ? 'unavailable' : status,
      'status_label': price == null ? 'Unavailable' : label,
      'as_of': price == null ? null : '2026-11-26T00:00:00+00:00',
      'source': {'name': 'International Coffee Organization', 'attribution': 'International Coffee Organization (ICO)'},
      'sparkline': price == null ? [] : [240.0, 243.5, 241.2, 247.25, 249.09],
      'spark_ref': price == null ? null : 247.25,
      'on_home': home,
      'a11y_label': price == null ? '$key, unavailable.' : 'ICO composite, 249.09 US cents per pound, up 1.84, 0.74 percent, $label.',
    };

Map<String, dynamic> _watch({List<Map<String, dynamic>>? rows, bool visible = true, List<String>? homeKeys, int schema = 1}) => {
      'success': true,
      'data': {
        'schema': schema,
        'generated_at': '2026-11-27T09:00:00+00:00',
        'status': {'status': 'daily_close', 'label': 'Daily close · 2026-11-26'},
        'sources': ['International Coffee Organization (ICO)', 'National Bank of Ethiopia (NBE)'],
        'disclaimer': 'Market information only, not trading advice.',
        'home': {'visible': visible, 'keys': homeKeys ?? ['ICO_COMPOSITE', 'ETB_USD']},
        'rows': rows ?? [_row('ICO_COMPOSITE'), _row('ETB_USD', price: null), _row('ICO_ROBUSTAS', home: false)],
      },
    };

class _Server {
  _Server(this.body, {this.status = 200, this.etag = '"v1"', this.series});

  Map<String, dynamic>? body;
  Map<String, dynamic>? series;
  int seriesStatus = 200;
  int status;
  String? etag;
  final requests = <http.Request>[];

  MockClient get client => MockClient((request) async {
        requests.add(request);
        if (request.url.path.contains('/market/instruments/')) {
          if (series == null) return http.Response('{}', 503);
          final s = jsonDecode(jsonEncode(series)) as Map<String, dynamic>;
          (s['data'] as Map)['range'] = request.url.queryParameters['range'] ?? '1M';
          final unit = request.url.queryParameters['unit'];
          if (unit != null) (s['data'] as Map)['unit'] = {'key': unit, 'label': unit == 'usd_kg' ? 'USD/kg' : 'US¢/lb'};
          return http.Response.bytes(utf8.encode(jsonEncode(s)), seriesStatus, headers: {'content-type': 'application/json; charset=utf-8'});
        }
        if (body == null) throw http.ClientException('offline');
        if (etag != null && request.headers['If-None-Match'] == etag) return http.Response('', 304);
        return http.Response.bytes(utf8.encode(jsonEncode(body)), status, headers: {'content-type': 'application/json; charset=utf-8', 'etag': ?etag});
      });
}

Widget _host(Widget child, {Locale locale = const Locale('en'), double textScale = 1}) => MaterialApp(
      theme: AppTheme.theme,
      locale: locale,
      supportedLocales: AppLocalizations.supportedLocales,
      localizationsDelegates: const [
        AppLocalizations.delegate,
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      home: MediaQuery(
        data: MediaQueryData(size: const Size(390, 844), textScaler: TextScaler.linear(textScale)),
        child: Scaffold(body: SingleChildScrollView(child: child)),
      ),
    );

Future<void> _settle(WidgetTester tester) async {
  for (var i = 0; i < 5; i++) {
    await tester.runAsync(() => Future<void>.delayed(const Duration(milliseconds: 30)));
    await tester.pump();
  }
}

Map<String, dynamic> _seriesBody() => {
      'success': true,
      'data': {
        'schema': 1, 'key': 'ICO_COMPOSITE', 'name': 'ICO Composite Indicator Price', 'short_name': 'ICO composite', 'range': '1M',
        'ranges': ['1W', '1M', '3M', '1Y', '5Y'], 'unit': {'key': 'usc_lb', 'label': 'US¢/lb'},
        'units': [{'key': 'usc_lb', 'label': 'US¢/lb'}, {'key': 'usd_kg', 'label': 'USD/kg'}],
        'fx': null, 'decimals': 2, 'status': 'daily_close', 'status_label': 'Daily close · 2026-11-26', 'as_of': '2026-11-26T00:00:00+00:00',
        'source': {'name': 'International Coffee Organization', 'attribution': 'International Coffee Organization (ICO)'},
        'price': 249.09, 'change': 1.84, 'change_pct': 0.74, 'direction': 'up',
        'points': [for (var i = 0; i < 5; i++) {'t': '2026-11-2${2 + i}T00:00:00+00:00', 'v': 245.0 + i}],
        'stats': {'prev_close': 247.25, 'high_52w': 260.10, 'low_52w': 198.40, 'avg_20d': 244.50},
        'disclaimer': 'Market information only, not trading advice.',
      },
    };

MarketWatchCard _card(_Server server, {bool homeOnly = true}) =>
    MarketWatchCard(provider: MarketWatchProvider(service: MarketWatchService(client: server.client)), homeOnly: homeOnly);

void main() {
  setUp(() => SharedPreferences.setMockInitialValues({}));

  group('model', () {
    test('reads schema 1 and keeps the home order', () {
      final watch = MarketWatch.fromJson(_watch(homeKeys: ['ETB_USD', 'ICO_COMPOSITE']));
      expect(watch.homeVisible, isTrue);
      expect(watch.homeRows.map((r) => r.key), ['ETB_USD', 'ICO_COMPOSITE']);
      expect(watch.sourcesShort, ['ICO', 'NBE']);
      final ico = watch.rows.first;
      expect([ico.price, ico.prevClose, ico.change, ico.changePct], [249.09, 247.25, 1.84, 0.74]);
      expect(ico.direction, MarketDirection.up);
      expect(ico.status, MarketWatchStatus.dailyClose);
      expect(ico.sourceShort, 'ICO');
      expect(ico.sparkline.length, 5);
    });

    test('an unavailable row keeps no number even if one was sent', () {
      final tampered = _row('ICO_COMPOSITE')..['status'] = 'unavailable';
      final row = MarketWatchRow.fromJson(tampered);
      expect(row.price, isNull);
      expect(row.change, isNull);
      expect(row.sparkline, isEmpty);
      expect(row.direction, MarketDirection.none);
    });

    test('the official ECTA schedule status is understood', () {
      final row = MarketWatchRow.fromJson(_row('ECTA_X', status: 'schedule', label: 'Official schedule · effective 2026-11-10'));
      expect(row.status, MarketWatchStatus.schedule);
    });

    test('a newer schema is refused rather than guessed', () {
      expect(() => MarketWatch.fromJson(_watch(schema: 2)), throwsFormatException);
    });
  });

  group('sparkline geometry (same rules as the website)', () {
    const box = Size(96, 34);
    test('fewer than two values draw nothing', () {
      expect(SparklineGeometry.compute(const [], null, box), isNull);
      expect(SparklineGeometry.compute(const [249.09], null, box), isNull);
    });
    test('highest value at the top, line spans the box', () {
      final g = SparklineGeometry.compute(const [10, 20, 30], null, box)!;
      expect(g.points, const [Offset(3, 31), Offset(48, 17), Offset(93, 3)]);
      expect(g.refY, isNull);
    });
    test('previous close outside the range widens the scale', () {
      final g = SparklineGeometry.compute(const [20, 30], 10, box)!;
      expect(g.refY, 31);
      expect(g.points, const [Offset(3, 17), Offset(93, 3)]);
    });
    test('flat series sits in the middle', () {
      final g = SparklineGeometry.compute(const [5, 5, 5], 5, box)!;
      expect(g.points.map((p) => p.dy).toSet(), {17});
      expect(g.refY, 17);
    });
  });

  group('service', () {
    test('sends no sign-in token, asks in the app language, and reuses the cache on 304', () async {
      SharedPreferences.setMockInitialValues({'language_code': 'am', 'auth_token': 'secret'});
      final server = _Server(_watch());
      final service = MarketWatchService(client: server.client);

      final first = await service.fetchWatch();
      expect(first.fromCache, isFalse);
      expect(server.requests.single.headers['Authorization'], isNull);
      expect(server.requests.single.headers['Accept-Language'], 'am');
      expect(server.requests.single.url.path, endsWith('/market/watch'));

      final second = await service.fetchWatch();
      expect(server.requests.last.headers['If-None-Match'], '"v1"');
      expect(second.fromCache, isFalse, reason: '304 confirms the cached data is current');
      expect(second.watch.rows.first.price, 249.09);
    });

    test('offline: the last answer with its real time; nothing cached: unavailable', () async {
      final server = _Server(_watch());
      final service = MarketWatchService(client: server.client);
      final fresh = await service.fetchWatch();

      server.body = null;
      final offline = await service.fetchWatch();
      expect(offline.fromCache, isTrue);
      expect(offline.receivedAt, fresh.receivedAt);

      SharedPreferences.setMockInitialValues({});
      expect(() => MarketWatchService(client: _Server(null).client).fetchWatch(), throwsA(isA<MarketUnavailableException>()));
    });

    test('a server error is never turned into data', () async {
      final service = MarketWatchService(client: _Server(_watch(), status: 500, etag: null).client);
      expect(service.fetchWatch(), throwsA(isA<MarketUnavailableException>()));
    });
  });

  group('provider', () {
    test('a failed refresh marks what is on screen as offline, with its time', () async {
      final server = _Server(_watch(), etag: null);
      final provider = MarketWatchProvider(service: MarketWatchService(client: server.client));
      expect(await provider.load(), isTrue);
      SharedPreferences.setMockInitialValues({}); // the disk cache is gone too
      server.body = null;
      expect(await provider.load(), isFalse);
      expect(provider.snapshot!.fromCache, isTrue);
      expect(provider.failed, isFalse, reason: 'there is still something true to show');
    });
  });

  group('card', () {
    testWidgets('shows the Home rows with price, unit, change, status and source', (tester) async {
      await tester.pumpWidget(_host(_card(_Server(_watch()))));
      await _settle(tester);

      expect(find.text('Coffee market'), findsOneWidget);
      expect(find.text('ICO composite'), findsOneWidget);
      expect(find.text('249.09'), findsOneWidget);
      expect(find.text('US¢/lb'), findsOneWidget);
      expect(find.text('+1.84 (+0.74%)'), findsOneWidget);
      expect(find.text('Daily close · 2026-11-26 · ICO'), findsOneWidget);
      expect(find.text('ICO_ROBUSTAS'), findsNothing, reason: 'not a Home row');
      expect(find.text('Sources: ICO, NBE'), findsOneWidget);
      expect(find.text('Market information only, not trading advice.'), findsOneWidget);
      expect(find.text('See all markets'), findsOneWidget);
      expect(find.byType(CustomPaint), findsWidgets);
    });

    testWidgets('an unavailable row shows the word and no number', (tester) async {
      await tester.pumpWidget(_host(_card(_Server(_watch(rows: [_row('ICO_COMPOSITE'), _row('ETB_USD', price: null)])))));
      await _settle(tester);

      expect(find.text('Unavailable'), findsOneWidget);
      expect(find.text('Unavailable · ICO'), findsOneWidget);
      expect(find.textContaining(RegExp(r'^\d')), findsOneWidget, reason: 'only the ICO price is a number');
    });

    testWidgets('stays hidden on Home until a Home row has a price', (tester) async {
      await tester.pumpWidget(_host(_card(_Server(_watch(visible: false)))));
      await _settle(tester);

      expect(find.text('Coffee market'), findsNothing);
      expect(find.textContaining('249.09'), findsNothing);
    });

    testWidgets('each row is one sentence for screen readers', (tester) async {
      final handle = tester.ensureSemantics();
      await tester.pumpWidget(_host(_card(_Server(_watch()))));
      await _settle(tester);

      expect(find.bySemanticsLabel('ICO composite, 249.09 US cents per pound, up 1.84, 0.74 percent, Daily close · 2026-11-26.'), findsOneWidget);
      expect(find.bySemanticsLabel('ETB_USD, unavailable.'), findsOneWidget);
      expect(find.byTooltip('Refresh market prices'), findsOneWidget);
      handle.dispose();
    });

    testWidgets('offline: banner with the time of the last answer; a delayed price shows when it was from', (tester) async {
      final server = _Server(_watch(rows: [_row('KC_ACTIVE', status: 'delayed', label: 'Delayed 20 min')], homeKeys: ['KC_ACTIVE']), etag: null);
      await tester.runAsync(() => MarketWatchService(client: server.client).fetchWatch());
      server.body = null;

      await tester.pumpWidget(_host(_card(server)));
      await _settle(tester);

      expect(find.textContaining('Offline · last updated'), findsOneWidget);
      expect(find.textContaining('Last price 2026-11-'), findsOneWidget); // local time, any time zone
      expect(find.textContaining('Delayed 20 min'), findsNothing, reason: 'no longer true while offline');
      expect(find.text('249.09'), findsOneWidget);
    });

    testWidgets('error with nothing cached: Try again loads again', (tester) async {
      final server = _Server(null);
      await tester.pumpWidget(_host(_card(server)));
      await _settle(tester);

      expect(find.text('Market prices could not be loaded.'), findsOneWidget);
      expect(find.textContaining(RegExp(r'\d+\.\d\d')), findsNothing);

      server.body = _watch();
      await tester.tap(find.text('Try again'));
      await _settle(tester);
      expect(find.text('249.09'), findsOneWidget);
    });

    testWidgets('loading shows a skeleton, not numbers', (tester) async {
      await tester.pumpWidget(_host(_card(_Server(_watch()))));
      await tester.pump();
      expect(find.bySemanticsLabel('Loading coffee prices'), findsOneWidget);
      expect(find.text('249.09'), findsNothing);
      await _settle(tester);
    });

    testWidgets('Amharic labels, Latin digits', (tester) async {
      await tester.pumpWidget(_host(_card(_Server(_watch(rows: [_row('ICO_COMPOSITE'), _row('ETB_USD', price: null)]))), locale: const Locale('am')));
      await _settle(tester);

      expect(find.text(kLangAm['marketCardTitle']!), findsOneWidget);
      expect(find.text(kLangAm['marketWatchRowUnavailable']!), findsOneWidget);
      expect(find.text(kLangAm['marketWatchSeeAll']!), findsOneWidget);
      expect(find.text('249.09'), findsOneWidget);
    });

    testWidgets('200% text fits without overflow', (tester) async {
      await tester.pumpWidget(_host(_card(_Server(_watch())), textScale: 2));
      await _settle(tester);

      expect(tester.takeException(), isNull);
      expect(find.text('249.09'), findsOneWidget);
    });
  });

  group('detail', () {
    test('reads the series schema, stats and units', () {
      final d = MarketSeriesDetail.fromJson(_seriesBody());
      expect(d.points.length, 5);
      expect([d.prevClose, d.high52w, d.low52w, d.avg20d], [247.25, 260.10, 198.40, 244.50]);
      expect(d.units.map((u) => u.key), ['usc_lb', 'usd_kg']);
      expect(d.ranges, isNot(contains('1D')));
      expect(() => MarketSeriesDetail.fromJson({'data': {'schema': 2}}), throwsFormatException);
    });

    testWidgets('tapping a row opens the sheet; ranges, units and scrubbing work', (tester) async {
      tester.view.physicalSize = const Size(1170, 4800);
      tester.view.devicePixelRatio = 3;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      final server = _Server(_watch(), series: _seriesBody());
      await tester.pumpWidget(_host(_card(server)));
      await _settle(tester);

      await tester.tap(find.text('ICO composite'));
      await _settle(tester);
      await tester.pump(const Duration(milliseconds: 500)); // sheet slide-in
      expect(find.text('ICO Composite Indicator Price'), findsOneWidget);
      expect(find.text('Statistics'), findsOneWidget);
      expect(find.text('260.10'), findsOneWidget);
      expect(find.text('5Y'), findsOneWidget);
      expect(find.text('1D'), findsNothing);
      final seriesRequest = server.requests.lastWhere((r) => r.url.path.contains('/instruments/'));
      expect(seriesRequest.url.path, endsWith('/market/instruments/ICO_COMPOSITE/series'));
      expect(seriesRequest.headers['Authorization'], isNull);

      await tester.tap(find.text('1Y'));
      await _settle(tester);
      expect(server.requests.last.url.queryParameters['range'], '1Y');

      await tester.tap(find.text('USD/kg').last);
      await _settle(tester);
      expect(server.requests.last.url.queryParameters['unit'], 'usd_kg');

      final chart = find.byType(MarketDetailChart);
      await tester.longPressAt(tester.getCenter(chart));
      await tester.pump();
      final gesture = await tester.startGesture(tester.getTopLeft(chart) + const Offset(12, 100));
      await tester.pump(const Duration(milliseconds: 600));
      expect(find.textContaining(' · 245.00'), findsOneWidget, reason: 'tooltip shows the first point while held');
      await gesture.up();
      await tester.pump();
      expect(find.textContaining(' · 245.00'), findsNothing, reason: 'releasing returns to the latest value');
    });

    testWidgets('a failed range keeps what is shown and offers Try again', (tester) async {
      tester.view.physicalSize = const Size(1170, 4800);
      tester.view.devicePixelRatio = 3;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      final server = _Server(_watch(), series: _seriesBody());
      await tester.pumpWidget(_host(_card(server)));
      await _settle(tester);
      await tester.tap(find.text('ICO composite'));
      await _settle(tester);
      await tester.pump(const Duration(milliseconds: 500)); // sheet slide-in

      server.seriesStatus = 500;
      await tester.tap(find.text('3M'));
      await _settle(tester);
      expect(find.text('Could not load this range.'), findsOneWidget);
      expect(find.text('260.10'), findsOneWidget);
    });
  });
}
