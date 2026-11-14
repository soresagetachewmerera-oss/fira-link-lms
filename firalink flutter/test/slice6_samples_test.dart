import 'dart:typed_data';

import 'package:eduex_app/l10n/app_localizations.dart';
import 'package:eduex_app/l10n/lang_am.dart';
import 'package:eduex_app/l10n/lang_en.dart';
import 'package:eduex_app/models/sample_model.dart';
import 'package:eduex_app/screens/marketplace/request_sample_screen.dart';
import 'package:eduex_app/screens/marketplace/sample_tracking_screen.dart';
import 'package:eduex_app/screens/marketplace/samples_screen.dart';
import 'package:eduex_app/screens/marketplace/ship_sample_screen.dart';
import 'package:eduex_app/services/api_exception.dart';
import 'package:eduex_app/services/sample_service.dart';
import 'package:eduex_app/services/upload_file.dart';
import 'package:eduex_app/theme/app_theme.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

Map<String, dynamic> _sample({String status = 'fee_paid', String side = 'buyer', String payment = 'waived', double fee = 0, bool shipping = false}) => {
      'id': 3,
      'reference': 'SMP-ABC1234',
      'status': status,
      'side': side,
      'quantity_grams': 250,
      'fee_usd': fee,
      'payment_status': payment,
      'requested_at': '2026-11-14T08:00:00Z',
      'paid_at': payment == 'unpaid' ? null : '2026-11-14T08:00:00Z',
      'ship_by': '2026-11-21',
      'seal_id': status == 'fee_paid' || status == 'requested' ? null : 'FL-0042',
      'sealed_at': status == 'fee_paid' || status == 'requested' ? null : '2026-11-15T08:00:00Z',
      'courier': 'dhl',
      'tracking_number': status == 'fee_paid' || status == 'requested' ? null : '1234567890',
      'tracking_url': status == 'fee_paid' || status == 'requested' ? null : 'https://www.dhl.com/track?id=1234567890',
      'seller_business': {'id': 1, 'name': 'Abyssinia Green PLC', 'business_role': 'exporter', 'verification_tier': 1},
      'buyer_business': {'id': 2, 'name': 'Hamburg Roasters GmbH', 'business_role': 'buyer', 'verification_tier': 1},
      'lot': {'id': 7, 'title': 'Guji Natural G1', 'lot_code': 'L-ABC234', 'evidence_level': 'documented'},
      if (shipping)
        'shipping': {
          'company': 'Hamburg Roasters GmbH',
          'address': 'Speicherstadt 7, 20457 Hamburg',
          'country': 'Germany',
          'phone': '+49 40 1234567',
          'courier': 'dhl',
          'courier_account': '963123456',
        },
    };

class _FakeSamples extends SampleService {
  _FakeSamples({required this.current, this.payError});

  Map<String, dynamic> current;
  final ApiException? payError;
  final List<String> calls = [];
  Map<String, dynamic>? requested;
  Map<String, Object?>? dispatched;

  @override
  Future<SampleList> list({String side = 'buyer', String? tab}) async {
    calls.add('list:$side:$tab');
    return SampleList.fromJson({
      'side': side,
      'counts': side == 'seller' ? {'to_send': 2, 'sent': 1, 'done': 0, 'awaiting_payment': 0} : null,
      'samples': [_sample(side: side)],
    });
  }

  @override
  Future<SampleView> get(int id, {bool withShipping = false}) async {
    calls.add('get:$withShipping');
    return SampleView.fromJson({...current, if (withShipping) 'shipping': _sample(shipping: true)['shipping']});
  }

  @override
  Future<SampleView> request(Map<String, dynamic> fields) async {
    requested = fields;
    return SampleView.fromJson(current);
  }

  @override
  Future<SampleView> pay(int id) async {
    calls.add('pay');
    if (payError != null) throw payError!;
    return SampleView.fromJson(current);
  }

  @override
  Future<SampleView> cancel(int id) async {
    calls.add('cancel');
    return SampleView.fromJson(current);
  }

  @override
  Future<SampleView> confirmDelivery(int id, {required bool sealIntact}) async {
    calls.add('delivered:$sealIntact');
    return SampleView.fromJson(current);
  }

  @override
  Future<SampleView> evaluate(int id, {required bool approve, required double score, String? notes}) async {
    calls.add('evaluate:$approve:$score');
    return SampleView.fromJson(current);
  }

  @override
  Future<SampleView> dispatch(int id, {required String sealId, required UploadFile sealedPhoto, required UploadFile retainedPhoto, required String trackingNumber}) async {
    dispatched = {'seal': sealId, 'tracking': trackingNumber, 'sealed': sealedPhoto.bytes.length, 'retained': retainedPhoto.bytes.length};
    return SampleView.fromJson(current);
  }

  @override
  Future<Map<String, String>> imageHeaders() async => const {};
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

Future<UploadFile?> _photo() async => UploadFile(name: 'seal.jpg', bytes: Uint8List.fromList(List.filled(30 * 1024, 5)));

void main() {
  setUp(() => SharedPreferences.setMockInitialValues({}));

  testWidgets('request a sample: size, delivery, own courier account, no fee to a person', (tester) async {
    _tall(tester);
    final svc = _FakeSamples(current: _sample());
    await tester.pumpWidget(_host(RequestSampleScreen(listingId: 7, lotTitle: 'Guji Natural G1', lotCode: 'L-ABC234', sellerName: 'Abyssinia Green PLC', service: svc)));
    await tester.pumpAndSettle();

    expect(find.text('Abyssinia Green PLC · Verified · Lot code L-ABC234'), findsOneWidget);
    expect(find.textContaining('keeps a sealed copy of this sample under lot code L-ABC234'), findsOneWidget);
    expect(find.text('On your courier account'), findsOneWidget);
    expect(find.text('Free'), findsNWidgets(2));

    await tester.tap(find.byKey(const Key('sample-send')));
    await tester.pumpAndSettle();
    expect(find.text('Required'), findsWidgets);
    expect(svc.requested, isNull);

    await tester.tap(find.byKey(const Key('size-500')));
    await tester.tap(find.byKey(const Key('carrier-fedex')));
    await tester.enterText(find.byKey(const Key('sample-company')), 'Hamburg Roasters GmbH');
    await tester.enterText(find.byKey(const Key('sample-address')), 'Speicherstadt 7, 20457 Hamburg');
    await tester.enterText(find.byKey(const Key('sample-country')), 'Germany');
    await tester.enterText(find.byKey(const Key('sample-phone')), '+49 40 1234567');
    await tester.enterText(find.byKey(const Key('sample-account')), '963123456');
    await tester.tap(find.byKey(const Key('sample-send')));
    await tester.pumpAndSettle();

    expect(svc.requested!['listing_id'], 7);
    expect(svc.requested!['quantity_grams'], 500);
    expect(svc.requested!['courier_carrier'], 'fedex');
    expect(find.byType(SampleTrackingScreen), findsOneWidget);
  });

  testWidgets('a paid sample waits for payment; payments off says so plainly', (tester) async {
    _tall(tester);
    final svc = _FakeSamples(
      current: _sample(status: 'requested', payment: 'unpaid', fee: 25),
      payError: const ApiException(statusCode: 503, code: 'PAYMENTS_NOT_AVAILABLE'),
    );
    await tester.pumpWidget(_host(SampleTrackingScreen(sampleId: 3, service: svc)));
    await tester.pumpAndSettle();

    expect(find.text('Waiting for payment'), findsWidgets);
    await tester.tap(find.byKey(const Key('sample-pay')));
    await tester.pumpAndSettle();
    expect(svc.calls, contains('pay'));
    expect(find.text('Payments are not available yet. Try again later.'), findsOneWidget);
    expect(find.byKey(const Key('sample-cancel')), findsOneWidget);
  });

  testWidgets('in transit: the buyer confirms the seal, with a link to courier tracking', (tester) async {
    _tall(tester);
    final svc = _FakeSamples(current: _sample(status: 'dispatched'));
    await tester.pumpWidget(_host(SampleTrackingScreen(sampleId: 3, service: svc)));
    await tester.pumpAndSettle();

    expect(find.textContaining('Seal ID FL-0042'), findsWidgets);
    expect(find.text('DHL · Tracking 1234567890'), findsOneWidget);
    expect(find.text('Open courier tracking'), findsOneWidget);
    await tester.tap(find.byKey(const Key('seal-intact')));
    await tester.pumpAndSettle();
    expect(svc.calls, contains('delivered:true'));
  });

  testWidgets('delivered: cupping needs a score from 60 to 100', (tester) async {
    _tall(tester);
    final svc = _FakeSamples(current: _sample(status: 'delivered'));
    await tester.pumpWidget(_host(SampleTrackingScreen(sampleId: 3, service: svc)));
    await tester.pumpAndSettle();

    await tester.enterText(find.byKey(const Key('cupping-score')), '120');
    await tester.tap(find.byKey(const Key('cupping-approve')));
    await tester.pumpAndSettle();
    expect(svc.calls.where((c) => c.startsWith('evaluate')), isEmpty);

    await tester.enterText(find.byKey(const Key('cupping-score')), '87.5');
    await tester.tap(find.byKey(const Key('cupping-approve')));
    await tester.pumpAndSettle();
    expect(svc.calls, contains('evaluate:true:87.5'));
  });

  testWidgets('seal and ship: contact loaded here only, both photos, seal ID and tracking', (tester) async {
    _tall(tester);
    final svc = _FakeSamples(current: _sample(side: 'seller'));
    await tester.pumpWidget(_host(ShipSampleScreen(sampleId: 3, service: svc, takePhoto: _photo)));
    await tester.pumpAndSettle();

    expect(svc.calls, contains('get:true'));
    expect(find.byKey(const Key('ship-to')), findsOneWidget);
    expect(find.textContaining('Speicherstadt 7'), findsOneWidget);
    expect(find.text("Ship on the buyer's DHL account 963123456."), findsOneWidget);
    expect(find.text('Prepare 250 g from lot L-ABC234'), findsOneWidget);

    await tester.enterText(find.byKey(const Key('seal-id')), 'FL-0042');
    await tester.enterText(find.byKey(const Key('tracking-number')), '1234567890');
    await tester.tap(find.byKey(const Key('mark-handed')));
    await tester.pumpAndSettle();
    expect(find.byKey(const Key('photos-required')), findsOneWidget);
    expect(svc.dispatched, isNull);

    await tester.tap(find.byKey(const Key('photo-sealed')));
    await tester.pumpAndSettle();
    await tester.tap(find.byKey(const Key('photo-retained')));
    await tester.pumpAndSettle();
    await tester.tap(find.byKey(const Key('mark-handed')));
    await tester.pumpAndSettle();
    expect(svc.dispatched!['seal'], 'FL-0042');
    expect(svc.dispatched!['tracking'], '1234567890');
    expect(svc.dispatched!['sealed'], 30 * 1024);
  });

  testWidgets('seller inbox: to send, sent, done with counts', (tester) async {
    _tall(tester);
    final svc = _FakeSamples(current: _sample());
    await tester.pumpWidget(_host(SamplesScreen(service: svc, initialTab: 1)));
    await tester.pumpAndSettle();

    expect(find.text('To send 2'), findsOneWidget);
    expect(find.text('Sent 1'), findsOneWidget);
    expect(find.text('Hamburg Roasters GmbH'), findsOneWidget);
    expect(find.textContaining('Free sample. Ship by Nov 21, 2026.'), findsOneWidget);
    await tester.tap(find.byKey(const Key('tab-sent')));
    await tester.pumpAndSettle();
    expect(svc.calls, contains('list:seller:sent'));
  });

  test('every slice 6 key exists in English and Amharic', () {
    const prefixes = ['sample', 'step', 'seal', 'cupping', 'retained', 'inbox', 'ship', 'track', 'markHanded', 'seeWhatBuyer', 'photosRequired', 'compareSample', 'compareNoSample', 'compareAskSample', 'lotRequestSample', 'openCourier'];
    final keys = kLangEn.keys.where((k) => prefixes.any(k.startsWith)).toList();
    expect(keys.length, greaterThan(80));
    for (final k in keys) {
      expect(kLangAm[k], isNotNull, reason: 'missing Amharic for $k');
      expect(kLangAm[k], isNotEmpty);
    }
  });
}
