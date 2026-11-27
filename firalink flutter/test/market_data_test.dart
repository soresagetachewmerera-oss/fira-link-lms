import 'dart:convert';

import 'package:eduex_app/l10n/app_localizations.dart';
import 'package:eduex_app/l10n/lang_am.dart';
import 'package:eduex_app/l10n/lang_en.dart';
import 'package:eduex_app/models/market_overview.dart';
import 'package:eduex_app/services/market_data_service.dart';
import 'package:eduex_app/widgets/market/market_ui.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Coffee market card and screen: only server values, with source and time; Stale and Unavailable
/// states; no number appears when the server sent none.

Map<String, dynamic> _row({
  String symbol = 'ICO_COMPOSITE',
  String name = 'ICO Composite Indicator Price',
  String? value = '249.09000000',
  String status = 'daily_close',
  String label = 'Daily close · 2026-11-12',
  String trend = 'sideways',
  bool hero = false,
  String priceType = 'daily_close',
}) =>
    {
      'symbol': symbol,
      'name': name,
      'price_type': priceType,
      'value': value,
      'currency': 'USX',
      'unit': 'lb',
      'is_hero': hero,
      'source': {'name': 'International Coffee Organization', 'url': 'https://data.ico.org', 'attribution': 'International Coffee Organization (ICO)'},
      'source_time': value == null ? null : '2026-11-12T00:00:00+00:00',
      'fetched_at': null,
      'freshness': {'status': status, 'delay_minutes': 30, 'label': label},
      'trend': {'label': trend, 'computed_at': null, 'sample_size': 25, 'inputs': null},
    };

Map<String, dynamic> _overview(List<Map<String, dynamic>> rows, {Map<String, dynamic>? hero}) => {
      'success': true,
      'data': {
        'hero': hero ?? rows.first,
        'benchmarks': rows,
        'summary': {'text': 'ICO Composite Indicator Price is 249.09 USX/lb.', 'source_rows': ['ICO_COMPOSITE']},
        'disclaimer': 'Prices are indicative and are not trading advice.',
        'generated_at': '2026-11-16T09:00:00+00:00',
      },
    };

final _kcUnavailable = _row(symbol: 'KC_ACTIVE', name: 'ICE Coffee C active contract', value: null, status: 'unavailable', label: 'Unavailable', trend: 'not_enough_data', hero: true, priceType: 'delayed_futures');

Map<String, dynamic> _series(List<double> values) => {
      'success': true,
      'data': {
        'series': {'symbol': 'ICO_COMPOSITE'},
        'range': '1M',
        'points': [
          for (var i = 0; i < values.length; i++)
            {'value': values[i].toStringAsFixed(8), 'source_time': DateTime.utc(2026, 11, 1 + i).toIso8601String()},
        ],
      },
    };

MockClient _client({required Map<String, dynamic>? overview, Map<String, dynamic>? series, int overviewStatus = 200, List<String>? log}) =>
    MockClient((request) async {
      log?.add(request.url.path);
      if (request.url.path.endsWith('/market/overview')) {
        if (overview == null) return http.Response('{"message":"down"}', overviewStatus == 200 ? 503 : overviewStatus);
        return http.Response(jsonEncode(overview), overviewStatus, headers: {'content-type': 'application/json; charset=utf-8'});
      }
      if (request.url.path.endsWith('/market/series')) {
        return http.Response(jsonEncode(series ?? _series(const [])), 200, headers: {'content-type': 'application/json; charset=utf-8'});
      }
      return http.Response('not found', 404);
    });

void main() {
  setUp(() => SharedPreferences.setMockInitialValues({}));

  group('model', () {
    test('a row the server marks unavailable never has a value', () {
      final row = MarketBenchmark.fromJson(_row(value: '249.09', status: 'unavailable'));
      expect(row.hasValue, isFalse);
    });

    test('cached rows become stale, unavailable rows stay unavailable', () {
      final o = MarketOverview.fromJson(_overview([_row(), _kcUnavailable]));
      final cached = o.asCachedStale();
      expect(cached.benchmarks[0].freshness, MarketFreshness.stale);
      expect(cached.benchmarks[0].value, '249.09000000');
      expect(cached.benchmarks[1].freshness, MarketFreshness.unavailable);
    });

    test('featured row: the hero if it has a value, else the first row with one', () {
      final o = MarketOverview.fromJson(_overview([_kcUnavailable, _row()], hero: _kcUnavailable));
      expect(featuredBenchmark(o)!.symbol, 'ICO_COMPOSITE');
      final none = MarketOverview.fromJson(_overview([_kcUnavailable], hero: _kcUnavailable));
      expect(featuredBenchmark(none), isNull);
    });

    test('units and values are shown as sent, rounded to 2 decimals only for display', () {
      expect(formatMarketValue('249.09000000'), '249.09');
      final l10n = AppLocalizations(const Locale('en'));
      expect(marketUnitLabel(l10n, 'USX', 'lb'), 'US¢/lb');
      expect(marketUnitLabel(l10n, 'ETB', 'feresula_17kg'), 'ETB per feresula (17 kg)');
    });
  });

  group('service', () {
    test('a failed request without cache throws unavailable, it never returns a fake overview', () async {
      final service = MarketDataService(client: _client(overview: null));
      await expectLater(service.fetchOverview(), throwsA(isA<MarketUnavailableException>()));
    });

    test('a failed request after a success returns the cached response as stale with its real time', () async {
      final ok = MarketDataService(client: _client(overview: _overview([_row()])));
      final first = await ok.fetchOverview();
      expect(first.fromCache, isFalse);

      final down = MarketDataService(client: _client(overview: null));
      final cached = await down.fetchOverview();
      expect(cached.fromCache, isTrue);
      expect(cached.receivedAt, first.receivedAt);
      expect(cached.overview.benchmarks.first.freshness, MarketFreshness.stale);
    });

    test('refresher waits longer after failures, up to 10x', () {
      final r = MarketRefresher(onTick: () async => true, isVisible: () => true);
      expect(r.nextDelay, const Duration(seconds: 60));
      r.reportResult(false);
      expect(r.nextDelay, const Duration(seconds: 120));
      for (var i = 0; i < 6; i++) {
        r.reportResult(false);
      }
      expect(r.nextDelay, const Duration(seconds: 600));
      r.reportResult(true);
      expect(r.nextDelay, const Duration(seconds: 60));
    });
  });


  test('every market key exists in English and Amharic', () {
    final keys = kLangEn.keys.where((k) => k.startsWith('market') && (k.contains('Trend') || k.contains('Fresh') || k.contains('Card') || k.contains('Unavailable') || k.contains('Source') || k.contains('Attribution')));
    expect(keys, isNotEmpty);
    for (final k in keys) {
      expect(kLangAm[k], isNotNull, reason: k);
      expect(kLangAm[k], isNot(equals(kLangEn[k])), reason: k);
    }
  });
}
