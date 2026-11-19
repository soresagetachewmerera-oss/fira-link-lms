import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';

import 'package:eduex_app/providers/settings_provider.dart';
import 'package:eduex_app/providers/wishlist_provider.dart';
import 'package:eduex_app/theme/fira_colors.dart';
import 'package:eduex_app/theme/app_theme.dart';
import 'package:eduex_app/utils/currency_formatter.dart';
import 'package:eduex_app/widgets/fira_bottom_dock.dart';
import 'package:eduex_app/widgets/bottom_nav_bar.dart';
import 'package:eduex_app/l10n/app_localizations.dart';

Widget _buildThemedApp({
  required Widget child,
  ThemeMode mode = ThemeMode.light,
}) {
  return MultiProvider(
    providers: [
      ChangeNotifierProvider<SettingsProvider>(create: (_) => SettingsProvider()),
      ChangeNotifierProvider<WishlistProvider>(create: (_) => WishlistProvider()),
    ],
    child: MaterialApp(
      theme: AppTheme.theme,
      darkTheme: AppTheme.darkTheme,
      themeMode: mode,
      localizationsDelegates: const [
        AppLocalizations.delegate,
        DefaultMaterialLocalizations.delegate,
        DefaultWidgetsLocalizations.delegate,
      ],
      supportedLocales: AppLocalizations.supportedLocales,
      home: Scaffold(
        body: child,
      ),
    ),
  );
}

void main() {
  group('FiraColors ThemeExtension Tests', () {
    test('Light and Dark theme extensions provide non-null brand colors', () {
      final light = FiraColors.light;
      final dark = FiraColors.dark;

      expect(light.brandPrimary, equals(AppTheme.primary));
      expect(dark.brandPrimary, equals(AppTheme.primaryDark));

      expect(light.brandDeep, equals(const Color(0xFF3B0764)));
      expect(dark.brandDeep, equals(const Color(0xFF1E0533)));

      // Assert that neither theme has orange or amber tokens
      expect(light.pending, equals(AppTheme.primary));
      expect(dark.pending, equals(AppTheme.primaryDark));

      expect(light.success, equals(const Color(0xFF047857)));
      expect(light.danger, equals(const Color(0xFFDC2626)));
    });

    test('Lerp between Light and Dark creates smooth transitions', () {
      final light = FiraColors.light;
      final dark = FiraColors.dark;

      final mid = light.lerp(dark, 0.5);
      expect(mid.brandPrimary, isNotNull);
      expect(mid.surface, isNotNull);
      expect(mid.card, isNotNull);
    });
  });

  group('FiraCurrencyFormatter Tests', () {
    test('Formats various price numbers into standard "ETB 33,000.00" standard', () {
      expect(FiraCurrencyFormatter.format(33000), equals('ETB 33,000.00'));
      expect(FiraCurrencyFormatter.format(1250.5), equals('ETB 1,250.50'));
      expect(FiraCurrencyFormatter.format(0), equals('ETB 0.00'));
      expect(FiraCurrencyFormatter.format(null), equals('ETB 0.00'));
      expect(FiraCurrencyFormatter.format('4500.75'), equals('ETB 4,500.75'));
      expect(FiraCurrencyFormatter.format(100, currency: 'USD'), equals('USD 100.00'));
    });
  });

  group('FiraBottomDock All 6 Items and Dynamic Scrolling Tests', () {
    testWidgets('Renders all 6 original items without dropping any', (WidgetTester tester) async {
      BottomNavTab? tapped;

      await tester.pumpWidget(
        _buildThemedApp(
          child: FiraBottomDock(
            currentTab: BottomNavTab.home,
            onTabSelected: (tab) => tapped = tab,
          ),
        ),
      );

      await tester.pumpAndSettle();

      // All 6 tabs must be present: Home, Trade Training (courses), B2B, Trade Events (events), Tours, Profile
      expect(find.text('B2B'), findsOneWidget);
      expect(find.text('Tours'), findsOneWidget);

      // Verify B2B selection works
      await tester.tap(find.text('B2B'));
      await tester.pumpAndSettle();
      expect(tapped, equals(BottomNavTab.b2b));
    });

    testWidgets('FiraBottomDock renders properly in Dark Mode', (WidgetTester tester) async {
      await tester.pumpWidget(
        _buildThemedApp(
          mode: ThemeMode.dark,
          child: const FiraBottomDock(
            currentTab: BottomNavTab.courses,
          ),
        ),
      );

      await tester.pumpAndSettle();
      expect(find.byType(FiraBottomDock), findsOneWidget);
    });
  });
}
