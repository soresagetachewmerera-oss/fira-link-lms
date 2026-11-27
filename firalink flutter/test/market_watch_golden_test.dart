import 'dart:convert';

import 'package:eduex_app/l10n/app_localizations.dart';
import 'package:eduex_app/providers/market_watch_provider.dart';
import 'package:eduex_app/services/market_watch_service.dart';
import 'package:eduex_app/theme/app_theme.dart';
import 'package:eduex_app/widgets/market/market_watch_card.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Golden images of the market watch card (light, dark, Amharic). Update with
/// `flutter test --update-goldens test/market_watch_golden_test.dart` after an intended visual change.
Map<String, dynamic> _row(String key, String short, double? price, {String dir = 'up', String status = 'daily_close', String label = 'Daily close · 2026-11-26'}) => {
      'key': key, 'name': short, 'short_name': short, 'kind': 'daily_close', 'currency': 'USX', 'unit': 'lb', 'unit_label': 'US¢/lb', 'decimals': 2,
      'price': price, 'prev_close': price == null ? null : price - 1.5, 'change': price == null ? null : (dir == 'up' ? 1.5 : -1.5),
      'change_pct': price == null ? null : (dir == 'up' ? 0.61 : -0.61), 'direction': price == null ? null : dir, 'trend': null,
      'status': price == null ? 'unavailable' : status, 'status_label': price == null ? 'Unavailable' : label,
      'as_of': price == null ? null : '2026-11-26T00:00:00+00:00',
      'source': {'name': 'International Coffee Organization', 'attribution': 'International Coffee Organization (ICO)'},
      'sparkline': price == null ? [] : [for (var i = 0; i < 20; i++) price - 4 + (i % 5) * (dir == 'up' ? .6 : -.4) + i * .1],
      'spark_ref': price == null ? null : price - 1.5, 'on_home': true, 'ranges': ['1W', '1M'], 'units': [], 'a11y_label': short,
    };

Future<void> _golden(WidgetTester tester, String name, {Locale locale = const Locale('en'), bool dark = false}) async {
  SharedPreferences.setMockInitialValues({'language_code': locale.languageCode});
  final body = {
    'data': {
      'schema': 1, 'generated_at': '2026-11-27T09:00:00+00:00', 'status': {'status': 'daily_close', 'label': 'Daily close · 2026-11-26'},
      'sources': ['International Coffee Organization (ICO)'], 'disclaimer': 'Market information only, not trading advice.',
      'home': {'visible': true, 'keys': ['A', 'B', 'C', 'D']},
      'rows': [_row('A', 'ICO composite', 246.70), _row('B', 'ECTA Sidama G2', 3.95, status: 'schedule', label: 'Official schedule · effective 2026-11-10'), _row('C', 'Other milds', 320.00, dir: 'down'), _row('D', 'Coffee C', null)],
    },
  };
  final client = MockClient((_) async => http.Response.bytes(utf8.encode(jsonEncode(body)), 200, headers: {'content-type': 'application/json; charset=utf-8'}));
  tester.view.physicalSize = const Size(390 * 2, 700 * 2);
  tester.view.devicePixelRatio = 2;
  addTearDown(tester.view.resetPhysicalSize);
  addTearDown(tester.view.resetDevicePixelRatio);
  await tester.pumpWidget(MaterialApp(
    debugShowCheckedModeBanner: false,
    theme: dark ? AppTheme.darkTheme : AppTheme.theme,
    locale: locale,
    supportedLocales: AppLocalizations.supportedLocales,
    localizationsDelegates: const [AppLocalizations.delegate, GlobalMaterialLocalizations.delegate, GlobalWidgetsLocalizations.delegate, GlobalCupertinoLocalizations.delegate],
    home: Scaffold(body: Center(child: RepaintBoundary(child: MarketWatchCard(provider: MarketWatchProvider(service: MarketWatchService(client: client)))))),
  ));
  for (var i = 0; i < 5; i++) {
    await tester.runAsync(() => Future<void>.delayed(const Duration(milliseconds: 30)));
    await tester.pump();
  }
  await expectLater(find.byType(MarketWatchCard), matchesGoldenFile('goldens/market_watch_card_$name.png'));
}

void main() {
  testWidgets('light', (t) => _golden(t, 'light'));
  testWidgets('dark', (t) => _golden(t, 'dark', dark: true));
  testWidgets('amharic', (t) => _golden(t, 'amharic', locale: const Locale('am')));
}
