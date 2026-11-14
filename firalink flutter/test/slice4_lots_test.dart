import 'dart:typed_data';

import 'package:eduex_app/l10n/app_localizations.dart';
import 'package:eduex_app/l10n/lang_am.dart';
import 'package:eduex_app/l10n/lang_en.dart';
import 'package:eduex_app/models/lot_model.dart';
import 'package:eduex_app/screens/marketplace/create_lot_screen.dart';
import 'package:eduex_app/screens/marketplace/lot_evidence_screen.dart';
import 'package:eduex_app/screens/marketplace/widgets/lot_evidence_summary_card.dart';
import 'package:eduex_app/screens/marketplace/widgets/seller_lots_section.dart';
import 'package:eduex_app/services/api_exception.dart';
import 'package:eduex_app/services/lot_service.dart';
import 'package:eduex_app/services/upload_file.dart';
import 'package:eduex_app/theme/app_theme.dart';
import 'package:eduex_app/theme/fira_colors.dart';
import 'package:eduex_app/widgets/verification_labels.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

Map<String, dynamic> _evidence(String kind, String state, {Map<String, dynamic> extra = const {}}) =>
    {'kind': kind, 'state': state, 'optional': kind == 'lab_report', ...extra};

Map<String, dynamic> _lotJson({
  int id = 7,
  String status = 'draft',
  String level = 'claimed',
  String? validUntil,
  List<Map<String, dynamic>>? evidence,
}) =>
    {
      'id': id,
      'lot_code': 'L-ABC234',
      'title': 'Bensa Washed G1',
      'origin_region': 'Sidama',
      'crop_year': '2025/2026',
      'processing_method': 'washed',
      'grade': 'grade_1',
      'total_quantity_bags': 120,
      'minimum_order_bags': 10,
      'indicative_price_usd_per_lb': '4.85',
      'status': status,
      'evidence_level': level,
      'evidence_valid_until': validUntil,
      'visible_to_buyers': false,
      'evidence': evidence ??
          [
            _evidence('photo_bags', 'not_uploaded'),
            _evidence('photo_beans', 'in_review', extra: {'evidence_id': 31, 'submitted_at': '2026-11-14T08:00:00Z'}),
            _evidence('photo_warehouse', 'needs_changes', extra: {'evidence_id': 32, 'reason_code': 'lot_code_not_visible', 'reason': 'x'}),
            _evidence('lab_report', 'not_uploaded'),
          ],
    };

class _FakeLotService extends LotService {
  _FakeLotService({this.lots, this.publishError});

  final SellerLots? lots;
  final ApiException? publishError;
  final List<String> calls = [];
  final List<Map<String, dynamic>> saved = [];
  final List<Map<String, Object?>> photos = [];

  @override
  Future<SellerLots> getLots() async => lots!;

  @override
  Future<SellerLot> getLot(int id) async => SellerLot.fromJson(_lotJson(id: id));

  @override
  Future<SellerLot> createDraft(Map<String, dynamic> fields) async {
    calls.add('create');
    saved.add(fields);
    return SellerLot.fromJson({..._lotJson(), ...fields});
  }

  @override
  Future<SellerLot> update(int id, Map<String, dynamic> fields) async {
    calls.add('update');
    saved.add(fields);
    return SellerLot.fromJson({..._lotJson(id: id), ...fields});
  }

  @override
  Future<SellerLot> publish(int id) async {
    calls.add('publish');
    if (publishError != null) throw publishError!;
    return SellerLot.fromJson(_lotJson(id: id, status: 'active'));
  }

  @override
  Future<void> uploadPhoto({
    required int lotId,
    required String kind,
    required UploadFile photo,
    required DateTime capturedAt,
    double? latitude,
    double? longitude,
    int? accuracyMeters,
  }) async {
    photos.add({'lot': lotId, 'kind': kind, 'lat': latitude, 'lng': longitude, 'bytes': photo.bytes.length});
  }

  @override
  Future<void> withdrawEvidence(int lotId, int evidenceId) async => calls.add('withdraw:$evidenceId');

  @override
  Future<http.Response> uploadLabReport({required int lotId, required List<UploadFile> pages, required String labName, String? reportNumber, DateTime? issuedAt}) async =>
      http.Response('{"success":true}', 201);
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
  tester.view.physicalSize = const Size(1170, 7000);
  tester.view.devicePixelRatio = 3;
  addTearDown(tester.view.reset);
}

Future<CapturedLotPhoto?> _fakeShot() async => CapturedLotPhoto(
      file: UploadFile(name: 'shot.jpg', bytes: Uint8List.fromList(List.filled(40 * 1024, 9))),
      capturedAt: DateTime(2026, 11, 14, 9),
      latitude: 6.76,
      longitude: 38.41,
      accuracyMeters: 10,
    );

void main() {
  setUp(() => SharedPreferences.setMockInitialValues({}));

  group('Seller home lots', () {
    testWidgets('hidden-until-Tier-1 banner, drafts, levels and the renew to-do', (tester) async {
      _tall(tester);
      final lots = SellerLots.fromJson({
        'business': {'name': 'Abyssinia Green PLC', 'status': 'in_review', 'visible_to_buyers': false},
        'can_manage': true,
        'lots': [
          _lotJson(id: 1, status: 'draft'),
          _lotJson(id: 2, status: 'active', level: 'documented', validUntil: '2026-11-17'),
        ],
      });
      await tester.pumpWidget(_host(Scaffold(
        body: SingleChildScrollView(child: SellerLotsSection(service: _FakeLotService(lots: lots), now: () => DateTime(2026, 11, 14))),
      )));
      await tester.pumpAndSettle();

      expect(find.byKey(const Key('lots-hidden-banner')), findsOneWidget);
      expect(find.text('Your lots are hidden from buyers.'), findsOneWidget);
      expect(find.text('They appear after Tier 1 is approved.'), findsOneWidget);
      expect(find.text('In review'), findsOneWidget);
      expect(find.text('Draft · not published'), findsOneWidget);
      expect(find.descendant(of: find.byKey(const Key('lot-2')), matching: find.text('Documented')), findsOneWidget);
      expect(find.descendant(of: find.byKey(const Key('lot-1')), matching: find.text('Claimed')), findsOneWidget);
      expect(find.text('Renew evidence for Bensa Washed G1'), findsOneWidget);
      expect(find.text('Expires in 3 days'), findsOneWidget);
      expect(find.byKey(const Key('add-lot')), findsOneWidget);
    });

    testWidgets('staff see lots but cannot add or open them', (tester) async {
      _tall(tester);
      final lots = SellerLots.fromJson({
        'business': {'name': 'B', 'status': 'verified', 'visible_to_buyers': true},
        'can_manage': false,
        'lots': [_lotJson(id: 1, status: 'active')],
      });
      await tester.pumpWidget(_host(Scaffold(body: SingleChildScrollView(child: SellerLotsSection(service: _FakeLotService(lots: lots))))));
      await tester.pumpAndSettle();

      expect(find.byKey(const Key('lots-hidden-banner')), findsNothing);
      expect(find.byKey(const Key('add-lot')), findsNothing);
      final tile = tester.widget<ListTile>(find.descendant(of: find.byKey(const Key('lot-1')), matching: find.byType(ListTile)));
      expect(tile.onTap, isNull);
    });
  });

  group('Create a lot', () {
    testWidgets('details are required, Next saves a draft, photos go through the camera, publish below Tier 1 stays a draft', (tester) async {
      _tall(tester);
      final service = _FakeLotService(publishError: const ApiException(statusCode: 403, code: 'VERIFICATION_REQUIRED'));
      await tester.pumpWidget(_host(Builder(
        builder: (context) => Scaffold(
          body: Center(
            child: TextButton(
              onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => CreateLotScreen(service: service, takePhoto: _fakeShot))),
              child: const Text('open'),
            ),
          ),
        ),
      )));
      await tester.pumpAndSettle();
      await tester.tap(find.text('open'));
      await tester.pumpAndSettle();

      // Details: required fields.
      await tester.tap(find.byKey(const Key('lot-next')));
      await tester.pumpAndSettle();
      expect(find.text('Required'), findsWidgets);
      expect(service.calls, isEmpty);

      await tester.enterText(find.byKey(const Key('lot-name')), 'Bensa Washed G1');
      await tester.enterText(find.byKey(const Key('lot-region')), 'Sidama');
      await tester.enterText(find.byKey(const Key('lot-quantity')), '120');
      await tester.tap(find.text('Natural'));
      await tester.tap(find.byKey(const Key('lot-next')));
      await tester.pumpAndSettle();

      expect(service.calls, ['create']);
      expect(service.saved.single['title'], 'Bensa Washed G1');
      expect(service.saved.single['processing_method'], 'natural');
      expect(service.saved.single['total_quantity_bags'], 120);
      expect(service.saved.single.containsKey('evidence_level'), isFalse);
      expect(service.saved.single.containsKey('organization_id'), isFalse);

      // Evidence step: the panel with the photo checklist.
      expect(find.text(kLangEn['evidenceWithThreePhotos']!), findsOneWidget);
      expect(find.text('Write lot code L-ABC234 on the bags so it shows in the photo.'), findsOneWidget);
      await tester.tap(find.byKey(const Key('evidence-action-photo_bags')));
      await tester.pumpAndSettle();
      expect(service.photos.single['kind'], 'photo_bags');
      expect(service.photos.single['lat'], 6.76);

      await tester.tap(find.byKey(const Key('lot-next')));
      await tester.pumpAndSettle();
      await tester.enterText(find.byKey(const Key('lot-price')), '4.85');
      await tester.tap(find.byKey(const Key('lot-next')));
      await tester.pumpAndSettle();
      expect(find.byKey(const Key('lot-publish')), findsOneWidget);

      await tester.tap(find.byKey(const Key('lot-publish')));
      await tester.pumpAndSettle();
      expect(service.calls.last, 'publish');
      expect(find.text(kLangEn['createLotNeedsTier']!), findsOneWidget);
      expect(find.byType(CreateLotScreen), findsNothing);
    });
  });

  group('Lot evidence', () {
    testWidgets('states come from the server, reasons are localized and in-review items can be withdrawn', (tester) async {
      _tall(tester);
      final service = _FakeLotService();
      await tester.pumpWidget(_host(LotEvidenceScreen(lotId: 7, service: service, takePhoto: _fakeShot)));
      await tester.pumpAndSettle();

      expect(find.byKey(const Key('current-level')), findsOneWidget);
      expect(find.text('Current level: Claimed'), findsOneWidget);
      expect(find.text('The lot code is not visible on the bags.'), findsOneWidget);
      expect(find.text(kLangEn['evidenceInspectedBody']!), findsOneWidget);

      final pending = find.descendant(of: find.byKey(const Key('evidence-photo_beans')), matching: find.text('In review'));
      final fira = tester.element(pending).fira;
      expect(tester.widget<Text>(pending).style?.color, fira.pending);

      await tester.tap(find.descendant(of: find.byKey(const Key('evidence-photo_beans')), matching: find.text('Withdraw')));
      await tester.pumpAndSettle();
      expect(service.calls, contains('withdraw:31'));
    });

    testWidgets('Amharic evidence screen', (tester) async {
      _tall(tester);
      await tester.pumpWidget(_host(LotEvidenceScreen(lotId: 7, service: _FakeLotService(), takePhoto: _fakeShot), locale: const Locale('am')));
      await tester.pumpAndSettle();
      expect(find.text(kLangAm['evidenceTitle']!), findsOneWidget);
      expect(find.text(kLangAm['kindPhotoBags']!), findsOneWidget);
    });
  });

  group('Lot detail and card', () {
    testWidgets('evidence card shows level, photo count, lot code and no location', (tester) async {
      _tall(tester);
      await tester.pumpWidget(_host(const Scaffold(
        body: LotEvidenceSummaryCard(evidence: {
          'level': 'documented',
          'valid_until': '2026-12-14',
          'lot_code': 'L-ABC234',
          'photos': [
            {'kind': 'photo_bags', 'url': 'https://example.test/a.jpg', 'taken_on': '2026-11-14'},
            {'kind': 'photo_beans', 'url': 'https://example.test/b.jpg', 'taken_on': '2026-11-14'},
            {'kind': 'photo_warehouse', 'url': 'https://example.test/c.jpg', 'taken_on': '2026-11-14'},
          ],
          'lab_report_checked': false,
        }),
      )));
      await tester.pump();

      expect(find.text('Evidence · Documented'), findsOneWidget);
      expect(find.text('3 photos taken in the app on Nov 14, 2026'), findsOneWidget);
      expect(find.text('Lot code L-ABC234 visible on the bags'), findsOneWidget);
      expect(find.text('Expires Dec 14, 2026'), findsOneWidget);
      expect(find.text('What does this level mean?'), findsOneWidget);
    });

    testWidgets('claimed is gray, higher levels use the brand tint', (tester) async {
      await tester.pumpWidget(_host(const Scaffold(body: Column(children: [LotLevelChip(level: 'claimed'), LotLevelChip(level: 'lab_backed')]))));
      await tester.pumpAndSettle();
      final fira = tester.element(find.text('Claimed')).fira;
      expect(tester.widget<Text>(find.text('Claimed')).style?.color, fira.mutedText);
      expect(tester.widget<Text>(find.text('Lab-backed')).style?.color, fira.brandPrimary);
    });
  });

  test('every slice 4 key exists in English and Amharic', () {
    const prefixes = ['lots', 'lotStatus', 'level', 'createLot', 'process', 'grade', 'evidence', 'kind', 'lot', 'verifyWithdraw'];
    final keys = kLangEn.keys.where((k) => prefixes.any(k.startsWith)).toList();
    expect(keys.length, greaterThan(70));
    for (final k in keys) {
      expect(kLangAm[k], isNotNull, reason: 'missing Amharic for $k');
      expect(kLangAm[k], isNotEmpty);
    }
  });
}
