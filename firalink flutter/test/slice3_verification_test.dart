import 'dart:convert';
import 'dart:typed_data';

import 'package:cunning_document_scanner/cunning_document_scanner.dart';
import 'package:eduex_app/l10n/app_localizations.dart';
import 'package:eduex_app/l10n/lang_am.dart';
import 'package:eduex_app/l10n/lang_en.dart';
import 'package:eduex_app/models/verification_model.dart';
import 'package:eduex_app/screens/marketplace/document_capture_screen.dart';
import 'package:eduex_app/screens/marketplace/organization_verification_screen.dart';
import 'package:eduex_app/screens/marketplace/public_seller_verification_screen.dart';
import 'package:eduex_app/services/api_exception.dart';
import 'package:eduex_app/services/upload_file.dart';
import 'package:eduex_app/services/verification_service.dart';
import 'package:eduex_app/theme/app_theme.dart';
import 'package:eduex_app/theme/fira_colors.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

Map<String, dynamic> _doc(String type, String state, {int tier = 1, Map<String, dynamic> extra = const {}}) => {
      'type': type,
      'tier': tier,
      'state': state,
      'requires_number': true,
      'requires_expiry': false,
      'fields': <String>[],
      'min_pages': 1,
      'max_pages': 10,
      ...extra,
    };

Map<String, dynamic> _dossierJson({bool publicPage = false, bool canManage = true}) => {
      'business': {'name': 'Abyssinia Green PLC', 'business_role': 'exporter', 'status': 'needs_changes'},
      'tier': 0,
      'max_tier': 3,
      'expected_review_business_days': 3,
      'counts': {'approved': 1, 'in_review': 1, 'needs_changes': 1, 'not_uploaded': 1},
      'can_manage': canManage,
      if (publicPage) ...{'fira_id': 'FL-ABCD-EFGH', 'public_url': 'https://firalink.test/verify/FL-ABCD-EFGH'},
      'tiers': [
        {
          'tier': 1,
          'documents': [
            _doc('trade_license', 'approved', extra: {'expires_at': '2027-11-14'}),
            _doc('tin_certificate', 'in_review', extra: {'submitted_at': '2026-11-12T10:00:00Z'}),
            _doc('owner_id', 'needs_changes', extra: {'reason_code': 'blurry_photo', 'reason': 'Photo is blurry. Retake it.'}),
            _doc('bank_letter', 'not_uploaded'),
          ],
        },
        {
          'tier': 2,
          'being_confirmed': true,
          'documents': [_doc('warehouse', 'not_uploaded', tier: 2, extra: {'camera_only': true, 'min_pages': 2})],
        },
        {'tier': 3, 'requires_tier': 2, 'documents': <dynamic>[]},
      ],
    };

class _FakeVerificationService extends VerificationService {
  _FakeVerificationService({this.dossier, this.seller, this.sellerError});

  final Map<String, dynamic>? dossier;
  final Map<String, dynamic>? seller;
  final ApiException? sellerError;
  final List<Map<String, Object?>> uploads = [];

  @override
  Future<VerificationDossier> getDossier() async => VerificationDossier.fromJson(dossier!);

  @override
  Future<http.Response> uploadDocument({
    required String documentType,
    required List<UploadFile> pages,
    String? documentNumber,
    DateTime? issuedAt,
    DateTime? expiresAt,
    Map<String, String> details = const {},
  }) async {
    uploads.add({'type': documentType, 'pages': pages.length, 'number': documentNumber, 'details': details});
    return http.Response(jsonEncode({'success': true, 'data': {'document_id': 1, 'state': 'in_review'}}), 201);
  }

  @override
  Future<PublicSeller> getPublicSeller(String firaId) async {
    if (sellerError != null) throw sellerError!;
    return PublicSeller.fromJson(seller!);
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
      home: child,
    );

void _tall(WidgetTester tester) {
  tester.view.physicalSize = const Size(1170, 6000);
  tester.view.devicePixelRatio = 3;
  addTearDown(tester.view.reset);
}

UploadFile _page(String name) => UploadFile(name: name, bytes: Uint8List.fromList(List.filled(40 * 1024, 7)));

void main() {
  setUp(() => SharedPreferences.setMockInitialValues({}));

  group('Dossier', () {
    testWidgets('shows server states per document, the expected review time and a fix button', (tester) async {
      _tall(tester);
      await tester.pumpWidget(_host(OrganizationVerificationScreen(service: _FakeVerificationService(dossier: _dossierJson()))));
      await tester.pumpAndSettle();

      expect(find.text('Tier 1 of 3 in progress'), findsOneWidget);
      expect(find.text('1 approved, 1 in review, 1 needs changes, 1 not uploaded'), findsOneWidget);
      expect(find.text('Our team reviews documents in about 3 business days.'), findsOneWidget);
      expect(find.text('Tier 1 · Legal identity'), findsOneWidget);
      expect(find.text('Tier 2 · Export operations'), findsOneWidget);
      expect(find.text('Required documents are being confirmed with ECTA.'), findsOneWidget);
      expect(find.text('Tier 3 · Vetted by Fira Link'), findsOneWidget);
      expect(find.text('Photo is blurry. Retake it.'), findsOneWidget);
      expect(find.text('Account name must match your business'), findsOneWidget);
      expect(find.text('Fix Owner ID'), findsOneWidget);
      expect(find.textContaining('Paid plans never speed up or buy verification.'), findsOneWidget);
      expect(find.textContaining('trust', findRichText: true), findsNothing);
      // No public page yet: nothing to share.
      expect(find.byKey(const Key('share-verification-link')), findsNothing);
    });

    testWidgets('in review is purple on lavender, never amber or green', (tester) async {
      _tall(tester);
      await tester.pumpWidget(_host(OrganizationVerificationScreen(service: _FakeVerificationService(dossier: _dossierJson()))));
      await tester.pumpAndSettle();

      final context = tester.element(find.byType(OrganizationVerificationScreen));
      final fira = context.fira;
      final pill = find.descendant(of: find.byKey(const Key('doc-tin_certificate')), matching: find.text('In review'));
      final text = tester.widget<Text>(pill);
      expect(text.style?.color, fira.pending);
      final box = tester.widget<Container>(find.ancestor(of: pill, matching: find.byType(Container)).first);
      expect((box.decoration as BoxDecoration).color, fira.brandTint);
    });

    testWidgets('a business with a public page can share its verification link', (tester) async {
      _tall(tester);
      await tester.pumpWidget(_host(OrganizationVerificationScreen(service: _FakeVerificationService(dossier: _dossierJson(publicPage: true)))));
      await tester.pumpAndSettle();

      expect(find.text('FL-ABCD-EFGH'), findsOneWidget);
      expect(find.byKey(const Key('share-verification-link')), findsOneWidget);
      expect(find.text('Share verification link'), findsOneWidget);
    });

    testWidgets('staff members cannot open uploads', (tester) async {
      _tall(tester);
      await tester.pumpWidget(_host(OrganizationVerificationScreen(service: _FakeVerificationService(dossier: _dossierJson(canManage: false)))));
      await tester.pumpAndSettle();

      expect(find.text('Only the owner or a manager can upload documents.'), findsOneWidget);
      expect(find.byKey(const Key('fix-document')), findsNothing);
      final tile = tester.widget<ListTile>(find.byKey(const Key('doc-bank_letter')));
      expect(tile.onTap, isNull);
    });

    testWidgets('Amharic dossier uses Amharic labels', (tester) async {
      _tall(tester);
      await tester.pumpWidget(_host(OrganizationVerificationScreen(service: _FakeVerificationService(dossier: _dossierJson())), locale: const Locale('am')));
      await tester.pumpAndSettle();

      expect(find.text(kLangAm['verifyTier1Title']!), findsOneWidget);
      expect(find.text(kLangAm['docTypeBankLetter']!), findsOneWidget);
      expect(find.text(kLangAm['reasonBlurryPhoto']!), findsOneWidget);
    });
  });

  group('Document capture', () {
    testWidgets('needs pages and the number before sending, then sends every page', (tester) async {
      _tall(tester);
      final service = _FakeVerificationService();
      final doc = DossierDocument.fromJson(_doc('tin_certificate', 'not_uploaded'));
      await tester.pumpWidget(_host(DocumentCaptureScreen(
        document: doc,
        expectedReviewDays: 3,
        service: service,
        scanPages: (max) async => [_page('scan-1.jpg'), _page('scan-2.jpg')],
        pickFile: () async => _page('tin.pdf'),
      )));
      await tester.pumpAndSettle();

      expect(find.text('Your file is stored encrypted and only our reviewers can open it.'), findsOneWidget);
      await tester.tap(find.byKey(const Key('capture-send')));
      await tester.pumpAndSettle();
      expect(find.text('Add at least 1 pages.'), findsOneWidget);
      expect(find.text('Required'), findsOneWidget);
      expect(service.uploads, isEmpty);

      await tester.tap(find.text('Files'));
      await tester.pumpAndSettle();
      expect(find.text('Pages (1)'), findsOneWidget);
      await tester.enterText(find.byKey(const Key('capture-number')), 'TIN-0012345');
      await tester.tap(find.byKey(const Key('capture-send')));
      await tester.pumpAndSettle();

      expect(service.uploads, hasLength(1));
      expect(service.uploads.single['type'], 'tin_certificate');
      expect(service.uploads.single['pages'], 1);
      expect(service.uploads.single['number'], 'TIN-0012345');
    });

    testWidgets('camera-only documents hide the file picker and ask for the detail fields', (tester) async {
      _tall(tester);
      final service = _FakeVerificationService();
      final doc = DossierDocument.fromJson(_doc('warehouse', 'not_uploaded', tier: 2, extra: {
        'requires_number': false,
        'camera_only': true,
        'min_pages': 2,
        'fields': ['warehouse_address'],
      }));
      await tester.pumpWidget(_host(DocumentCaptureScreen(
        document: doc,
        expectedReviewDays: 3,
        service: service,
        scanPages: (max) async => [_page('w1.jpg'), _page('w2.jpg')],
      )));
      await tester.pumpAndSettle();

      expect(find.text('Files'), findsNothing);
      expect(find.text('Take these photos in the app.'), findsOneWidget);

      await tester.tap(find.text('Scan with camera'));
      await tester.pumpAndSettle();
      await tester.enterText(find.byKey(const Key('capture-field-warehouse_address')), 'Addis Ababa, Kality');
      await tester.tap(find.byKey(const Key('capture-send')));
      await tester.pumpAndSettle();

      expect(service.uploads.single['pages'], 2);
      expect(service.uploads.single['details'], {'warehouse_address': 'Addis Ababa, Kality'});
    });
  });

  testWidgets('camera permission denied explains how to allow it and adds no page', (tester) async {
    _tall(tester);
    final doc = DossierDocument.fromJson(_doc('warehouse', 'not_uploaded', tier: 2, extra: {'camera_only': true, 'min_pages': 2}));
    await tester.pumpWidget(_host(DocumentCaptureScreen(
      document: doc,
      expectedReviewDays: 3,
      service: _FakeVerificationService(),
      scanPages: (max) async => throw const CunningDocumentScannerException.permissionDenied(),
    )));
    await tester.pumpAndSettle();

    await tester.tap(find.text('Scan with camera'));
    await tester.pumpAndSettle();
    expect(find.text(kLangEn['captureCameraDenied']!), findsOneWidget);
    expect(find.text('Pages (0)'), findsOneWidget);
  });

  group('Public seller check', () {
    Map<String, dynamic> seller(String status) => {
          'fira_id': 'FL-ABCD-EFGH',
          'name': 'Abyssinia Green PLC',
          'business_role': 'exporter',
          'city': 'Addis Ababa',
          'country': 'Ethiopia',
          'status': status,
          'tier': status == 'verified' ? 1 : null,
          'reviewed_at': '2026-11-10',
          'checked': status == 'verified'
              ? [
                  {'document_type': 'trade_license', 'checked_at': '2026-11-10', 'valid_until': '2027-11-10'},
                  {'document_type': 'bank_letter', 'check': 'account_name_matches', 'checked_at': '2026-11-10'},
                ]
              : <dynamic>[],
          'confirmed_deals': 0,
          'member_since': 2026,
          'public_url': 'https://firalink.test/verify/FL-ABCD-EFGH',
          'qr_svg_url': '',
        };

    testWidgets('shows what was checked, the role and no bare zero for deals', (tester) async {
      _tall(tester);
      await tester.pumpWidget(_host(PublicSellerVerificationScreen(firaId: 'FL-ABCD-EFGH', service: _FakeVerificationService(seller: seller('verified')))));
      await tester.pumpAndSettle();

      expect(find.text('Abyssinia Green PLC'), findsOneWidget);
      expect(find.text('Exporter · Addis Ababa · Ethiopia'), findsOneWidget);
      expect(find.text('Verified · Tier 1'), findsOneWidget);
      expect(find.text('Business license'), findsOneWidget);
      expect(find.text('Bank account name matches business'), findsOneWidget);
      expect(find.text('What we did not check'), findsOneWidget);
      expect(find.byKey(const Key('seller-no-deals')), findsOneWidget);
      expect(find.text('0'), findsNothing);
      expect(find.text('Never pay outside Fira Link.'), findsOneWidget);
      expect(find.textContaining('dispute', findRichText: true), findsNothing);
    });

    testWidgets('suspended and expired businesses say so and list nothing as checked', (tester) async {
      _tall(tester);
      await tester.pumpWidget(_host(PublicSellerVerificationScreen(firaId: 'FL-ABCD-EFGH', service: _FakeVerificationService(seller: seller('suspended')))));
      await tester.pumpAndSettle();
      expect(find.text('Suspended by Fira Link'), findsOneWidget);
      expect(find.text('What we checked'), findsNothing);

      await tester.pumpWidget(_host(PublicSellerVerificationScreen(key: UniqueKey(), firaId: 'FL-ABCD-EFGH', service: _FakeVerificationService(seller: seller('expired')))));
      await tester.pumpAndSettle();
      expect(find.text('Verification expired'), findsOneWidget);
    });

    testWidgets('unknown IDs show not found, never a fake result', (tester) async {
      _tall(tester);
      await tester.pumpWidget(_host(PublicSellerVerificationScreen(
        firaId: 'FL-2222-2222',
        service: _FakeVerificationService(sellerError: const ApiException(statusCode: 404, code: 'NOT_FOUND')),
      )));
      await tester.pumpAndSettle();

      expect(find.byKey(const Key('seller-not-found')), findsOneWidget);
      expect(find.text('Verified · Tier 1'), findsNothing);
    });
  });

  test('every slice 3 key exists in English and Amharic', () {
    const prefixes = ['verify', 'docState', 'docType', 'reason', 'capture', 'seller', 'checkAccount'];
    final keys = kLangEn.keys.where((k) => prefixes.any(k.startsWith)).toList();
    expect(keys.length, greaterThan(90));
    for (final k in keys) {
      expect(kLangAm[k], isNotNull, reason: 'missing Amharic for $k');
      expect(kLangAm[k], isNotEmpty);
    }
  });
}
