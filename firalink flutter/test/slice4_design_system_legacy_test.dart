import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:eduex_app/l10n/app_localizations.dart';
import 'package:eduex_app/screens/search/search_screen.dart';
import 'package:eduex_app/services/api_exception.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  group('Slice 4: Design System Unification & Legacy Cleanup Tests', () {
    test('UI-012: /home2 route in AppRouter redirects to MainShellScreen', () {
      final file = File('lib/router/app_router.dart');
      expect(file.existsSync(), isTrue);
      final content = file.readAsStringSync();
      expect(content.contains("case '/home2':"), isTrue);
      expect(content.contains("return const MainShellScreen(initialTab: BottomNavTab.home);"), isTrue);
    });

    test('UI-007: coffee_market_screen.dart has 0 raw 0xFF6D1A95 literals', () {
      final file = File('lib/screens/coffee_toolkit/coffee_market_screen.dart');
      expect(file.existsSync(), isTrue);
      final content = file.readAsStringSync();
      expect(content.contains('0xFF6D1A95'), isFalse, reason: 'All raw hex colors should be replaced with fira.brandPrimary');
    });

    test('UI-009: AppRouter defines search route and resolves to SearchScreen', () {
      final file = File('lib/router/app_router.dart');
      final content = file.readAsStringSync();
      expect(content.contains('static const String search = \'/search\';'), isTrue);
      expect(content.contains('case search:'), isTrue);
    });

    testWidgets('UI-009: SearchScreen shows real chips, saved recent searches and no invented results', (tester) async {
      SharedPreferences.setMockInitialValues({'recent_searches': ['guji natural']});
      await tester.pumpWidget(_searchApp(const SearchScreen()));
      await tester.pumpAndSettle();

      expect(find.byType(TextField), findsOneWidget);
      expect(find.text('Cancel'), findsOneWidget);
      for (final chip in ['All', 'Lots', 'Exporters', 'Courses']) {
        expect(find.text(chip), findsOneWidget);
      }
      // Recent searches are the ones saved on this device, never a fixed list.
      expect(find.text('Recent searches'), findsOneWidget);
      expect(find.text('guji natural'), findsOneWidget);
      expect(find.text('yirgacheffe grade 1'), findsNothing);
      // GAP-002: the hard-coded lots, prices and "Verified • Tier 2" exporter are gone.
      final source = File('lib/screens/search/search_screen.dart').readAsStringSync();
      for (final fake in ['Sidama Bensa', 'Oromia Coffee', 'Tier 2', r'$3.45']) {
        expect(source.contains(fake), isFalse, reason: 'fake search content "$fake" must not come back');
      }
    });

    testWidgets('UI-009: SearchScreen shows results from the backend, guest exporter hint and no-results text', (tester) async {
      SharedPreferences.setMockInitialValues({});
      await tester.pumpWidget(_searchApp(SearchScreen(initialQuery: 'sidama', backend: _FakeSearch())));
      await tester.pumpAndSettle();

      expect(find.text('Real lot from API'), findsOneWidget);
      expect(find.text('Sign in to search verified exporters.'), findsOneWidget);
      expect(find.text('Coffee export basics'), findsOneWidget);

      await tester.enterText(find.byType(TextField), 'zz');
      await tester.pump(const Duration(milliseconds: 450));
      await tester.pumpAndSettle();
      expect(find.text('No results for “zz”.'), findsOneWidget);
    });

    test('UI-020: Web dynamic-styles.blade.php unifies --fira-primary with --theme', () {
      final file = File('../firalink web/firalink/resources/views/partials/dynamic-styles.blade.php');
      expect(file.existsSync(), isTrue);
      final content = file.readAsStringSync();
      expect(content.contains(r'--fira-primary: {{ $primaryColor }};'), isTrue);
      expect(content.contains('--theme: var(--fira-primary);'), isTrue);
    });
  });
}

Widget _searchApp(Widget home) => MaterialApp(
      localizationsDelegates: const [
        AppLocalizations.delegate,
        DefaultMaterialLocalizations.delegate,
        DefaultWidgetsLocalizations.delegate,
      ],
      supportedLocales: AppLocalizations.supportedLocales,
      home: home,
    );

class _FakeSearch extends SearchBackend {
  @override
  Future<List<SearchHit>> lots(String query) async =>
      query == 'sidama' ? const [SearchHit(id: 1, title: 'Real lot from API', subtitle: 'Sidama')] : const [];

  @override
  Future<List<SearchHit>> exporters(String query, AppLocalizations l10n) async =>
      query == 'sidama' ? throw const ApiException(statusCode: 401) : const [];

  @override
  Future<List<SearchHit>> courses(String query) async =>
      query == 'sidama' ? const [SearchHit(id: 2, title: 'Coffee export basics')] : const [];
}
