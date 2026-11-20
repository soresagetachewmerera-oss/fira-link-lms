import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:eduex_app/screens/splash/splash_screen.dart';
import 'package:eduex_app/theme/app_theme.dart';

void main() {
  testWidgets('SplashScreen renders centered Fira Link logo, title, and ecosystem pillars', (WidgetTester tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: AppTheme.theme,
        darkTheme: AppTheme.darkTheme,
        themeMode: ThemeMode.dark,
        home: const SplashScreen(),
      ),
    );

    // Initial pump
    await tester.pump();

    // Verify brand wordmark
    expect(find.text('FIRA LINK'), findsOneWidget);

    // Verify brand tagline
    expect(find.text('The Gateway of African Trade & Excellence'), findsOneWidget);

    // Verify progress indicator
    expect(find.byType(LinearProgressIndicator), findsOneWidget);

    // Verify center emblem image
    final imageFinder = find.byType(Image);
    expect(imageFinder, findsOneWidget);
    final imageWidget = tester.widget<Image>(imageFinder);
    expect((imageWidget.image as AssetImage).assetName, equals('assets/img/fira_emblem_white.png'));

    // Verify 4 ecosystem pillars
    expect(find.text('B2B Trade'), findsOneWidget);
    expect(find.text('Fira AI'), findsOneWidget);
    expect(find.text('Safaris'), findsOneWidget);
    expect(find.text('Summits'), findsOneWidget);

    // Advance animation
    await tester.pump(const Duration(milliseconds: 1000));
  });
}
