import 'dart:convert';

import 'package:eduex_app/l10n/app_localizations.dart';
import 'package:eduex_app/l10n/lang_en.dart';
import 'package:eduex_app/screens/auth/email_sign_up_screen.dart';
import 'package:eduex_app/screens/auth/phone_code_screen.dart';
import 'package:eduex_app/services/auth_service.dart';
import 'package:eduex_app/theme/app_theme.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class _FakeAuth extends AuthService {
  final List<String> calls = [];

  @override
  Future<http.Response> emailSignUpStart({
    required String fullName,
    required String email,
    required String password,
    required String confirmation,
  }) async {
    calls.add('start:$fullName:$email');
    return http.Response(jsonEncode({'success': true, 'cooldown_seconds': 60, 'expiry_minutes': 10}), 200);
  }
}

Widget _host(Widget child) => MaterialApp(
      theme: AppTheme.theme,
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

  testWidgets('email sign-up needs valid fields, the password rules and the terms, then opens the code step',
      (tester) async {
    // A tall phone, so the whole form (button included) is built without scrolling.
    tester.view.physicalSize = const Size(1170, 5000);
    tester.view.devicePixelRatio = 3;
    addTearDown(tester.view.reset);

    final fake = _FakeAuth();
    await tester.pumpWidget(_host(EmailSignUpScreen(authService: fake)));
    await tester.pumpAndSettle();

    final fields = find.byType(TextField);
    await tester.enterText(fields.at(0), 'Anna Schmidt');
    await tester.enterText(fields.at(1), 'anna@roastery.example');
    await tester.enterText(fields.at(2), 'weak');
    await tester.enterText(fields.at(3), 'weak');
    await tester.pump();

    final button = find.widgetWithText(FilledButton, kLangEn['sendCode']!);
    expect(tester.widget<FilledButton>(button).onPressed, isNull, reason: 'weak password');

    await tester.enterText(fields.at(2), 'Hamburg2026');
    await tester.enterText(fields.at(3), 'Hamburg2026');
    await tester.pump();

    // Terms not accepted yet: nothing is sent.
    await tester.tap(button);
    await tester.pumpAndSettle();
    expect(fake.calls, isEmpty);
    expect(find.text(kLangEn['acceptTermsRequired']!), findsOneWidget);

    await tester.tap(find.byType(Checkbox));
    await tester.tap(button);
    await tester.pumpAndSettle();

    expect(fake.calls, ['start:Anna Schmidt:anna@roastery.example']);
    expect(find.byType(PhoneCodeScreen), findsOneWidget);
    await tester.pump(const Duration(seconds: 61));
  });
}
