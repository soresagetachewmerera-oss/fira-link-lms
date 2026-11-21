import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:eduex_app/l10n/app_localizations.dart';
import 'package:eduex_app/providers/auth_provider.dart';
import 'package:eduex_app/screens/auth/auth_screen.dart';
import 'package:eduex_app/theme/app_theme.dart';
import 'package:eduex_app/theme/fira_colors.dart';

Widget makeTestableAuthScreen() {
  return MultiProvider(
    providers: [
      ChangeNotifierProvider<AuthProvider>(create: (_) => AuthProvider()),
    ],
    child: MaterialApp(
      theme: AppTheme.theme,
      locale: const Locale('en'),
      supportedLocales: AppLocalizations.supportedLocales,
      localizationsDelegates: const [
        AppLocalizations.delegate,
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      home: const AuthScreen(),
    ),
  );
}

void main() {
  setUp(() {
    SharedPreferences.setMockInitialValues({});
  });

  group('AuthScreen UI and Branding Tests', () {
    testWidgets('renders login form with Fira Link branding and clean hierarchy', (tester) async {
      await tester.binding.setSurfaceSize(const Size(390, 844));
      addTearDown(() => tester.binding.setSurfaceSize(null));

      await tester.pumpWidget(makeTestableAuthScreen());
      await tester.pumpAndSettle();

      // Verify tabs exist
      expect(find.text('Login'), findsWidgets);
      expect(find.text('Sign up'), findsOneWidget);

      // Verify email and password fields exist
      expect(find.widgetWithText(TextField, 'Email'), findsOneWidget);
      expect(find.widgetWithText(TextField, 'Password'), findsOneWidget);

      // Verify the ugly rectangular text button 'Show' is replaced by an IconButton
      expect(find.byIcon(Icons.visibility_outlined), findsOneWidget);

      // Verify the 'or' divider exists
      expect(find.text('or'), findsOneWidget);

      // Verify the phone sign-in button exists as an OutlinedButton with phone icon
      final phoneButton = find.widgetWithText(OutlinedButton, 'Use my phone number instead');
      expect(phoneButton, findsOneWidget);
      expect(find.byIcon(Icons.phone_android_rounded), findsOneWidget);

      // Verify switch to sign up link and guest link
      expect(find.text('New to Fira Link? Create an account'), findsOneWidget);
      expect(find.text('Explore as guest'), findsOneWidget);

      // Verify that the redundant duplicate link from the old messy list is removed from the login tab
      // In the old screen, "No Ethiopian number? Create an account with email" was stacked under login
      expect(find.text('No Ethiopian number? Create an account with email'), findsNothing);
    });

    testWidgets('switching tab to Sign up renders professional registration form', (tester) async {
      await tester.binding.setSurfaceSize(const Size(390, 844));
      addTearDown(() => tester.binding.setSurfaceSize(null));

      await tester.pumpWidget(makeTestableAuthScreen());
      await tester.pumpAndSettle();

      // Tap 'New to Fira Link? Create an account'
      final createAccountLink = find.text('New to Fira Link? Create an account');
      await tester.tap(createAccountLink);
      await tester.pumpAndSettle();

      // Verify Sign up fields are visible
      expect(find.widgetWithText(TextField, 'First Name'), findsOneWidget);
      expect(find.widgetWithText(TextField, 'Last Name'), findsOneWidget);
      expect(find.text('+251'), findsOneWidget);
      expect(find.text('Phone Number (Required for SMS OTP) *'), findsOneWidget);
      expect(find.text('Back to sign in'), findsOneWidget);
      expect(find.text('No Ethiopian number? Create an account with email'), findsOneWidget);

      // Tap 'Back to sign in' switches back to Login tab
      await tester.tap(find.text('Back to sign in'));
      await tester.pumpAndSettle();

      expect(find.text('Use my phone number instead'), findsOneWidget);
    });
  });
}
