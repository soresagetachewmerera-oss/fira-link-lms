import 'package:eduex_app/l10n/app_localizations.dart';
import 'package:eduex_app/l10n/lang_am.dart';
import 'package:eduex_app/l10n/lang_en.dart';
import 'package:eduex_app/models/verification_model.dart';
import 'package:eduex_app/screens/marketplace/organization_verification_screen.dart';
import 'package:eduex_app/screens/profile/business_verification_tile.dart';
import 'package:eduex_app/services/verification_service.dart';
import 'package:eduex_app/theme/app_theme.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

Map<String, dynamic> _doc(String type, String state, {int tier = 1}) => {
      'type': type,
      'tier': tier,
      'state': state,
      'pages': 0,
      'fields': <String>[],
      'min_pages': 1,
      'max_pages': 10,
    };

Map<String, dynamic> _dossier({List<String> tier1 = const ['approved', 'in_review', 'not_uploaded'], int tier = 0, String status = 'in_review'}) => {
      'business': {'name': 'Abebe Coffee', 'business_role': 'exporter', 'status': status},
      'tier': tier,
      'max_tier': 3,
      'expected_review_business_days': 3,
      'counts': {'approved': 1, 'in_review': 1, 'needs_changes': 0, 'not_uploaded': 1},
      'can_manage': true,
      'tiers': [
        {
          'tier': 1,
          'documents': [for (var i = 0; i < tier1.length; i++) _doc(['trade_license', 'tin_certificate', 'owner_id'][i], tier1[i])],
        },
        {
          'tier': 2,
          'documents': [_doc('warehouse', 'not_uploaded', tier: 2)],
        },
      ],
    };

class _FakeVerification extends VerificationService {
  _FakeVerification(this.json);

  Map<String, dynamic> json;
  int calls = 0;

  @override
  Future<VerificationDossier> getDossier() async {
    calls++;
    return VerificationDossier.fromJson(json);
  }
}

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
      home: Scaffold(body: Padding(padding: const EdgeInsets.all(20), child: child)),
    );

void main() {
  setUp(() => SharedPreferences.setMockInitialValues({}));

  testWidgets('profile verification row: "Tier 1 · 2 documents left" opens the verification documents', (tester) async {
    tester.view.physicalSize = const Size(1170, 2532);
    tester.view.devicePixelRatio = 3;
    addTearDown(tester.view.reset);
    final svc = _FakeVerification(_dossier());

    await tester.pumpWidget(_host(BusinessVerificationTile(organizationId: 3, service: svc)));
    await tester.pumpAndSettle();
    expect(find.text('Verification'), findsOneWidget);
    expect(find.text('Tier 1 · 2 documents left'), findsOneWidget);

    await tester.tap(find.byKey(const Key('profile-verification')));
    await tester.pumpAndSettle();
    expect(find.byType(OrganizationVerificationScreen), findsOneWidget);
  });

  testWidgets('a verified business shows its tier; Amharic renders', (tester) async {
    final svc = _FakeVerification(_dossier(tier1: const ['approved', 'approved', 'approved'], tier: 1, status: 'verified'));
    await tester.pumpWidget(_host(BusinessVerificationTile(organizationId: 3, service: svc), locale: const Locale('am')));
    await tester.pumpAndSettle();
    expect(find.text(kLangAm['verifyDocumentsLeft']!.replaceAll('{tier}', '2').replaceAll('{n}', '1')), findsOneWidget);
    expect(tester.takeException(), isNull);
  });

  test('new strings exist in English and Amharic', () {
    for (final k in ['verifyDocumentsLeft', 'verifyOpenDocuments', 'verifyStart']) {
      expect(kLangEn[k], isNotNull, reason: k);
      expect(kLangAm[k], isNotNull, reason: k);
    }
  });
}
