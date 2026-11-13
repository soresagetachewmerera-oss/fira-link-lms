import 'dart:convert';

import 'package:eduex_app/l10n/app_localizations.dart';
import 'package:eduex_app/l10n/lang_am.dart';
import 'package:eduex_app/l10n/lang_en.dart';
import 'package:eduex_app/screens/auth/new_password_screen.dart';
import 'package:eduex_app/screens/auth/phone_code_screen.dart';
import 'package:eduex_app/screens/auth/phone_sign_in_screen.dart';
import 'package:eduex_app/screens/splash/startup_route.dart';
import 'package:eduex_app/services/auth_service.dart';
import 'package:eduex_app/theme/app_theme.dart';
import 'package:eduex_app/widgets/auth/fresh_auth_sheet.dart';
import 'package:eduex_app/widgets/auth/password_checks.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

/// Records calls instead of hitting the network.
class FakeAuthService extends AuthService {
  final List<String> calls = [];
  http.Response startResponse = http.Response(
    jsonEncode({'success': true, 'phone_masked': '2519****5678', 'cooldown_seconds': 60, 'expiry_minutes': 10}),
    200,
  );
  http.Response verifyResponse = http.Response(jsonEncode({'success': false, 'code': 'INVALID_CODE', 'attempts_remaining': 3}), 422);

  @override
  Future<http.Response> phoneStart(String phone, {String countryCode = '+251'}) async {
    calls.add('start:$phone:$countryCode');
    return startResponse;
  }

  @override
  Future<http.Response> phoneVerify(String phone, String code) async {
    calls.add('verify:$phone:$code');
    return verifyResponse;
  }

  @override
  Future<http.Response> passwordReset({required String ticket, required String password, required String confirmation}) async {
    calls.add('reset');
    return http.Response(jsonEncode({'success': true}), 200);
  }
}

Widget host(Widget child, {Locale locale = const Locale('en')}) => MaterialApp(
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

  group('Startup route', () {
    test('language picker only on first launch', () {
      expect(decideStartupRoute(isAuthenticated: false, languageChosen: false, seenOnboarding: false), StartupRoute.languagePicker);
      expect(decideStartupRoute(isAuthenticated: false, languageChosen: true, seenOnboarding: false), StartupRoute.onboarding);
      expect(decideStartupRoute(isAuthenticated: false, languageChosen: true, seenOnboarding: true), StartupRoute.home);
    });

    test('never for signed-in users or people who already finished onboarding (upgrades)', () {
      expect(decideStartupRoute(isAuthenticated: true, languageChosen: false, seenOnboarding: false), StartupRoute.home);
      expect(decideStartupRoute(isAuthenticated: false, languageChosen: false, seenOnboarding: true), StartupRoute.home);
    });
  });

  group('Password rules (same as server)', () {
    test('8 characters, a number, a capital letter', () {
      expect(PasswordRuleResult.of('Abcdefg1').allMet, isTrue);
      expect(PasswordRuleResult.of('Abcdef1').minLength, isFalse);
      expect(PasswordRuleResult.of('Abcdefgh').hasNumber, isFalse);
      expect(PasswordRuleResult.of('abcdefg1').hasCapital, isFalse);
    });
  });

  group('Localization', () {
    test('every English key has an Amharic entry and vice versa', () {
      expect(kLangEn.keys.toSet().difference(kLangAm.keys.toSet()), isEmpty);
      expect(kLangAm.keys.toSet().difference(kLangEn.keys.toSet()), isEmpty);
    });

    test('slice 1 strings are translated, not copied', () {
      const keys = [
        'phoneSignInTitle', 'phoneSignInSubtitle', 'sendCode', 'continueWithEmail', 'exploreAsGuest',
        'neverShareCode', 'codeExpired', 'codeLocked', 'resendCodeIn', 'fullNameTitle', 'forgotTitle',
        'resetCodeSentNotice', 'ruleMinLength', 'ruleNumber', 'ruleCapital', 'passwordUpdatedBody',
        'onboardingTitle1', 'onboardingTitle2', 'onboardingTitle3', 'languagePickerTitle', 'confirmItsYouTitle',
      ];
      for (final k in keys) {
        expect(kLangEn[k], isNotNull, reason: k);
        expect(kLangAm[k], isNot(equals(kLangEn[k])), reason: k);
      }
    });
  });

  group('Phone sign-in screen', () {
    testWidgets('rejects an invalid Ethiopian number without calling the server', (tester) async {
      final fake = FakeAuthService();
      await tester.pumpWidget(host(PhoneSignInScreen(authService: fake)));
      await tester.pumpAndSettle();

      await tester.enterText(find.byType(TextField), '12345');
      await tester.tap(find.text(kLangEn['sendCode']!));
      await tester.pumpAndSettle();

      expect(find.text(kLangEn['invalidPhone']!), findsOneWidget);
      expect(fake.calls, isEmpty);
    });

    testWidgets('a valid number sends +251 and opens the code screen with the warning', (tester) async {
      final fake = FakeAuthService();
      await tester.pumpWidget(host(PhoneSignInScreen(authService: fake)));
      await tester.pumpAndSettle();

      await tester.enterText(find.byType(TextField), '0911 234 567');
      await tester.tap(find.text(kLangEn['sendCode']!));
      await tester.pumpAndSettle();

      expect(fake.calls, ['start:0911234567:+251']);
      expect(find.byType(PhoneCodeScreen), findsOneWidget);
      expect(find.text(kLangEn['neverShareCode']!), findsOneWidget);
      expect(find.textContaining('60s'), findsOneWidget, reason: 'resend countdown from the server');
    });

    testWidgets('a wrong code shows the localized tries-left message', (tester) async {
      final fake = FakeAuthService();
      await tester.pumpWidget(host(PhoneCodeScreen.signIn(
        phone: '0911234567',
        phoneMasked: '2519****4567',
        cooldownSeconds: 60,
        expiryMinutes: 10,
        authService: fake,
      )));
      await tester.pumpAndSettle();

      await tester.enterText(find.byType(TextField), '000000');
      await tester.pumpAndSettle();

      expect(fake.calls, ['verify:0911234567:000000']);
      expect(find.text('That code is not correct. 3 tries left.'), findsOneWidget);
      await tester.pump(const Duration(seconds: 61)); // let the countdown timer finish
    });
  });

  group('New password screen', () {
    testWidgets('save stays disabled until all rules pass and both fields match', (tester) async {
      final fake = FakeAuthService();
      await tester.pumpWidget(host(NewPasswordScreen(resetTicket: 't' * 64, authService: fake)));
      await tester.pumpAndSettle();

      FilledButton saveButton() => tester.widget<FilledButton>(find.byType(FilledButton));
      final fields = find.byType(TextField);

      await tester.enterText(fields.at(0), 'weakpass');
      await tester.enterText(fields.at(1), 'weakpass');
      await tester.pump();
      expect(saveButton().onPressed, isNull);

      await tester.enterText(fields.at(0), 'Strong1Pass');
      await tester.enterText(fields.at(1), 'Strong1Pas');
      await tester.pump();
      expect(saveButton().onPressed, isNull);
      expect(find.text(kLangEn['passwordsDoNotMatch']!), findsOneWidget);

      await tester.enterText(fields.at(1), 'Strong1Pass');
      await tester.pump();
      expect(saveButton().onPressed, isNotNull);

      await tester.tap(find.byType(FilledButton));
      await tester.pumpAndSettle();
      expect(fake.calls, ['reset']);
      expect(find.text(kLangEn['passwordUpdatedTitle']!), findsOneWidget);
    });
  });

  group('Sensitive actions', () {
    test('recognizes the server asking for fresh authentication', () {
      expect(needsFreshAuth(http.Response(jsonEncode({'code': 'FRESH_AUTH_REQUIRED'}), 403)), isTrue);
      expect(needsFreshAuth(http.Response(jsonEncode({'code': 'VERIFICATION_REQUIRED'}), 403)), isFalse);
      expect(needsFreshAuth(http.Response('{}', 200)), isFalse);
    });
  });
}
