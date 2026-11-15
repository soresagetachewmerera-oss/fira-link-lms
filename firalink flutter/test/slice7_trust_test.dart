import 'dart:typed_data';

import 'package:eduex_app/l10n/app_localizations.dart';
import 'package:eduex_app/l10n/lang_am.dart';
import 'package:eduex_app/l10n/lang_en.dart';
import 'package:eduex_app/models/deal_room_model.dart';
import 'package:eduex_app/models/trade_chat_model.dart';
import 'package:eduex_app/screens/chat/trade_chat_screen.dart';
import 'package:eduex_app/screens/deal_room/deal_room_hub_screen.dart';
import 'package:eduex_app/screens/deal_room/payment_milestones_screen.dart';
import 'package:eduex_app/screens/marketplace/payout_account_screen.dart';
import 'package:eduex_app/screens/trust_safety/report_modal.dart';
import 'package:eduex_app/services/api_exception.dart';
import 'package:eduex_app/services/deal_room_service.dart';
import 'package:eduex_app/services/payout_account_service.dart';
import 'package:eduex_app/services/trade_chat_service.dart';
import 'package:eduex_app/services/trust_safety_service.dart';
import 'package:eduex_app/services/upload_file.dart';
import 'package:eduex_app/theme/app_theme.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

const _seller = {'id': 1, 'name': 'Abyssinia Green PLC', 'business_role': 'exporter', 'verification_tier': 1, 'fira_id': 'FL-AB12-3456'};
const _buyer = {'id': 2, 'name': 'Hamburg Roasters GmbH', 'business_role': 'buyer', 'verification_tier': 1};

Map<String, dynamic> _deal({String side = 'buyer', String current = 'payment_escrow', String payState = 'ready', bool paused = false, String depositStatus = 'pending'}) {
  const keys = ['contract_signed', 'payment_escrow', 'preshipment_inspection', 'shipped', 'bill_of_lading_released', 'final_settlement'];
  final ci = keys.indexOf(current);
  return {
    'id': 9,
    'deal_reference': 'DR-ABCD1234',
    'title': 'Guji G1 for Hamburg',
    'incoterm': 'FOB',
    'target_value_usd': 100000,
    'deposit_percent': 30,
    'deposit_usd': 30000,
    'balance_usd': 70000,
    'status': 'under_contract',
    'paused': paused,
    'my_side': side,
    'buyer': _buyer,
    'seller': _seller,
    'lot': {'id': 7, 'title': 'Guji Natural G1', 'lot_code': 'L-ABC234', 'price_per_lb': 5.2},
    'buyer_signed': true,
    'seller_signed': true,
    'step_number': ci + 1,
    'step_count': 6,
    'current_step': current,
    'milestones': [
      for (var i = 0; i < keys.length; i++)
        {
          'id': 100 + i,
          'key': keys[i],
          'title': keys[i],
          'status': i < ci ? 'achieved' : (i == ci && keys[i] == 'payment_escrow' ? depositStatus : 'pending'),
          'side': const ['both', 'buyer', 'seller', 'seller', 'buyer', 'buyer'][i],
          'money': keys[i] == 'payment_escrow' ? 'deposit' : (keys[i] == 'bill_of_lading_released' ? 'balance' : null),
          'amount_usd': keys[i] == 'payment_escrow' ? 30000 : (keys[i] == 'bill_of_lading_released' ? 70000 : null),
          'locked': i > ci,
        },
    ],
    'documents': {'contract_signed': true, 'has_contract_file': false, 'sample_seal_id': 'FL-0042'},
    'pay_to': {
      'state': payState,
      'business': 'Abyssinia Green PLC',
      'bank_name': 'Commercial Bank of Ethiopia',
      'account_last4': '1234',
      'matches_verified': payState == 'ready',
      'paused_until': payState == 'paused' ? '2026-11-17T10:00:00Z' : null,
    },
    'conversation_id': null,
    'activity': [
      {'action': 'deal_contract_signed', 'at': '2026-11-14T10:00:00Z'},
      {'action': 'deal_room_opened', 'at': '2026-11-14T09:00:00Z'},
    ],
  };
}

class _FakeDeals extends DealRoomService {
  _FakeDeals(this.json, {this.advanceError});

  Map<String, dynamic> json;
  final ApiException? advanceError;
  final List<Map<String, Object?>> advanced = [];

  @override
  Future<DealRoomModel> getDealRoom(int id) async => DealRoomModel.fromJson(json);

  @override
  Future<Map<String, dynamic>> advanceMilestone({required int dealRoomId, required int milestoneId, String? notes, UploadFile? document, String? paymentMethod}) async {
    advanced.add({'milestone': milestoneId, 'method': paymentMethod, 'file': document?.name});
    if (advanceError != null) return {'success': false, 'error': advanceError};
    return {'success': true};
  }
}

class _FakeChat extends TradeChatService {
  _FakeChat({this.flaggedIncoming = true, this.blocked = false});

  final bool flaggedIncoming;
  bool blocked;
  final List<String> sent = [];

  Map<String, dynamic> get _conv => {
        'id': 5,
        'my_side': 'buyer',
        'other': _seller,
        'deal_room': {'id': 9, 'deal_reference': 'DR-ABCD1234', 'title': 'Guji'},
        'blocked': blocked,
        'blocked_by_me': blocked,
        'unread': 1,
        'last_message': 'Faster on WhatsApp',
      };

  @override
  Future<TradeConversation> forDealRoom(int dealRoomId) async => TradeConversation.fromJson(_conv);

  @override
  Future<List<TradeConversation>> inbox() async => [TradeConversation.fromJson(_conv)];

  @override
  Future<TradeThread> thread(int id) async => TradeThread(
        conversation: TradeConversation.fromJson(_conv),
        messages: [
          TradeMessage.fromJson({'id': 1, 'mine': true, 'body': 'Is the Guji still available?', 'created_at': '2026-11-14T09:00:00Z'}),
          TradeMessage.fromJson({
            'id': 2,
            'mine': false,
            'body': 'Yes. Faster on WhatsApp: +251 911 234 567',
            'flagged': flaggedIncoming,
            'flag_kinds': ['messenger', 'phone'],
            'show_warning': flaggedIncoming,
            'created_at': '2026-11-14T09:05:00Z',
          }),
        ],
      );

  @override
  Future<TradeMessage> send(int id, String body) async {
    sent.add(body);
    return TradeMessage.fromJson({'id': 3, 'mine': true, 'body': body, 'flagged': body.contains('@')});
  }
}

class _FakeTrust extends TrustSafetyService {
  final List<Map<String, Object?>> reports = [];
  final List<String> blocks = [];

  @override
  Future<bool> submitReport({
    required String reasonCode,
    String? details,
    int? reportedOrganizationId,
    int? reportedListingId,
    int? reportedMessageId,
    int? reportedDealRoomId,
    int? reportedSampleId,
    String? reportedFiraId,
    bool block = false,
    UploadFile? evidence,
  }) async {
    reports.add({'reason': reasonCode, 'details': details, 'message': reportedMessageId, 'deal': reportedDealRoomId, 'block': block, 'evidence': evidence?.name});
    return block;
  }

  @override
  Future<void> block(int organizationId) async => blocks.add('block:$organizationId');

  @override
  Future<void> unblock(int organizationId) async => blocks.add('unblock:$organizationId');
}

class _FakePayout extends PayoutAccountService {
  _FakePayout({this.account, this.canChange = true});

  Map<String, dynamic>? account;
  final bool canChange;
  final List<Map<String, String>> saved = [];

  @override
  Future<PayoutAccountState> get() async => PayoutAccountState(account: PayoutAccountView.fromJson(account), canChange: canChange);

  @override
  Future<PayoutAccountView?> save({required String bankName, required String accountName, required String accountNumber}) async {
    saved.add({'bank': bankName, 'name': accountName, 'number': accountNumber});
    account = {'id': 2, 'bank_name': bankName, 'account_name': accountName, 'account_last4': accountNumber.substring(accountNumber.length - 4), 'status': 'pending', 'is_change': true, 'active_from': '2099-01-01T00:00:00Z'};
    return PayoutAccountView.fromJson(account);
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
  tester.view.physicalSize = const Size(1170, 7000);
  tester.view.devicePixelRatio = 3;
  addTearDown(tester.view.reset);
}

Future<UploadFile?> _slip() async => UploadFile(name: 'slip.png', bytes: Uint8List.fromList(List.filled(30 * 1024, 7)));

void main() {
  setUp(() => SharedPreferences.setMockInitialValues({}));

  testWidgets('deal room: both businesses, step 2 of 6, six steps, documents with the seal, activity', (tester) async {
    _tall(tester);
    await tester.pumpWidget(_host(DealRoomHubScreen(dealRoomId: 9, service: _FakeDeals(_deal()), chat: _FakeChat(), trust: _FakeTrust())));
    await tester.pumpAndSettle();

    expect(find.text('Step 2 of 6 · Waiting for deposit'), findsOneWidget);
    expect(find.text('Hamburg Roasters GmbH'), findsOneWidget);
    expect(find.text('Abyssinia Green PLC'), findsOneWidget);
    expect(find.text('Buyer · you'), findsOneWidget);
    for (final step in ['Contract signed', 'Payment secured', 'Pre-shipment inspection', 'Shipped', 'Documents released', 'Delivered and confirmed']) {
      expect(find.textContaining(step), findsWidgets);
    }
    expect(find.text('Retained sample record · Seal ID FL-0042'), findsOneWidget);
    expect(find.text('Linked'), findsOneWidget);
    expect(find.text('Deal room opened'), findsOneWidget);
    expect(find.byKey(const Key('deal-open-chat')), findsOneWidget);

    // A locked step does not open; it explains why.
    await tester.tap(find.byKey(const Key('step-shipped')));
    await tester.pump();
    expect(find.text(kLangEn['dealStepLockedHint']!), findsOneWidget);
  });

  testWidgets('paused deal shows the hold message', (tester) async {
    _tall(tester);
    await tester.pumpWidget(_host(DealRoomHubScreen(dealRoomId: 9, service: _FakeDeals(_deal(paused: true)), chat: _FakeChat(), trust: _FakeTrust())));
    await tester.pumpAndSettle();
    expect(find.byKey(const Key('deal-paused')), findsOneWidget);
  });

  testWidgets('payment step: masked account that matches, method, slip upload moves to submitted', (tester) async {
    _tall(tester);
    final deals = _FakeDeals(_deal());
    final d = DealRoomModel.fromJson(_deal());
    await tester.pumpWidget(_host(PaymentMilestonesScreen(dealRoom: d, milestone: d.step('payment_escrow')!, service: deals, trust: _FakeTrust(), pickSlip: _slip)));
    await tester.pumpAndSettle();

    expect(find.text('Deposit 30% now'), findsOneWidget);
    expect(find.text('Balance 70% when documents are released'), findsOneWidget);
    expect(find.text('Commercial Bank of Ethiopia · account ending 1234'), findsOneWidget);
    expect(find.text("Matches the seller's verified bank account"), findsOneWidget);
    expect(find.byKey(const Key('pay-wrong-account')), findsOneWidget);

    await tester.tap(find.byKey(const Key('method-letter_of_credit')));
    await tester.pump();
    deals.json = _deal(depositStatus: 'proof_submitted');
    await tester.tap(find.byKey(const Key('pay-upload')));
    await tester.pumpAndSettle();
    expect(deals.advanced.single, {'milestone': 101, 'method': 'letter_of_credit', 'file': 'slip.png'});
    expect(find.text(kLangEn['paySubmitted']!), findsOneWidget);
    expect(find.byKey(const Key('pay-upload')), findsNothing);
  });

  testWidgets('payment step: a changed bank account pauses paying; no upload button works', (tester) async {
    _tall(tester);
    final deals = _FakeDeals(_deal(payState: 'paused'));
    final d = DealRoomModel.fromJson(_deal(payState: 'paused'));
    await tester.pumpWidget(_host(PaymentMilestonesScreen(dealRoom: d, milestone: d.step('payment_escrow')!, service: deals, pickSlip: _slip)));
    await tester.pumpAndSettle();

    expect(find.byKey(const Key('pay-to-paused')), findsOneWidget);
    expect(find.textContaining('Payments are paused until'), findsOneWidget);
    expect(find.text("Matches the seller's verified bank account"), findsNothing);
    expect(tester.widget<ButtonStyleButton>(find.byKey(const Key('pay-upload'))).onPressed, isNull);
  });

  testWidgets('payment step: the seller only waits for the buyer', (tester) async {
    _tall(tester);
    final d = DealRoomModel.fromJson(_deal(side: 'seller'));
    await tester.pumpWidget(_host(PaymentMilestonesScreen(dealRoom: d, milestone: d.step('payment_escrow')!, service: _FakeDeals(_deal(side: 'seller')))));
    await tester.pumpAndSettle();
    expect(find.byKey(const Key('pay-upload')), findsNothing);
    expect(find.text(kLangEn['paySellerWaits']!), findsOneWidget);
  });

  testWidgets('chat: flagged message shows the warning; keep chatting hides it; report this message', (tester) async {
    _tall(tester);
    final chat = _FakeChat();
    final trust = _FakeTrust();
    await tester.pumpWidget(_host(TradeChatScreen(conversationId: 5, service: chat, trust: trust)));
    await tester.pumpAndSettle();

    expect(find.text('Abyssinia Green PLC'), findsOneWidget);
    expect(find.byKey(const Key('chat-warning-2')), findsOneWidget);
    expect(find.text(kLangEn['chatWarnPay']!), findsOneWidget);
    expect(find.text(kLangEn['chatWarnMove']!), findsOneWidget);
    expect(find.text(kLangEn['chatWarnCheck']!), findsOneWidget);
    // My own message never shows a warning.
    expect(find.byKey(const Key('chat-warning-1')), findsNothing);

    await tester.enterText(find.byKey(const Key('chat-input')), 'Can you send the lab report here?');
    await tester.tap(find.byKey(const Key('chat-send')));
    await tester.pumpAndSettle();
    expect(chat.sent, ['Can you send the lab report here?']);

    await tester.tap(find.byKey(const Key('report-message-2')));
    await tester.pumpAndSettle();
    expect(find.text('Report a problem'), findsOneWidget);
    await tester.tap(find.byKey(const Key('report-send')));
    await tester.pumpAndSettle();
    expect(trust.reports.single['message'], 2);
    expect(trust.reports.single['reason'], 'pay_outside');

    await tester.tap(find.byKey(const Key('keep-chatting-2')));
    await tester.pump();
    expect(find.byKey(const Key('chat-warning-2')), findsNothing);

  });

  testWidgets('chat: blocked thread has no composer and can be unblocked', (tester) async {
    _tall(tester);
    final chat = _FakeChat(blocked: true);
    final trust = _FakeTrust();
    await tester.pumpWidget(_host(TradeChatScreen(conversationId: 5, service: chat, trust: trust)));
    await tester.pumpAndSettle();
    expect(find.byKey(const Key('chat-blocked')), findsOneWidget);
    expect(find.byKey(const Key('chat-input')), findsNothing);
    await tester.tap(find.descendant(of: find.byKey(const Key('chat-blocked')), matching: find.text('Unblock')));
    await tester.pumpAndSettle();
    expect(trust.blocks, ['unblock:1']);
  });

  testWidgets('report sheet: six reasons, details needed for something else, screenshot, also block', (tester) async {
    _tall(tester);
    final trust = _FakeTrust();
    await tester.pumpWidget(_host(Scaffold(
      body: Builder(
        builder: (ctx) => TextButton(
          onPressed: () => ReportModal.show(ctx, reportedDealRoomId: 9, targetTitle: 'DR-ABCD1234', service: trust, pickScreenshot: _slip),
          child: const Text('open'),
        ),
      ),
    )));
    await tester.pumpAndSettle();
    await tester.tap(find.text('open'));
    await tester.pumpAndSettle();

    for (final code in TrustSafetyService.reasons) {
      expect(find.byKey(Key('reason-$code')), findsOneWidget);
    }
    expect(find.text(kLangEn['reportNote']!), findsOneWidget);

    await tester.tap(find.byKey(const Key('report-send')));
    await tester.pump();
    expect(find.byKey(const Key('reason-required')), findsOneWidget);

    await tester.tap(find.byKey(const Key('reason-other')));
    await tester.pump();
    await tester.tap(find.byKey(const Key('report-send')));
    await tester.pump();
    expect(find.text(kLangEn['reportDetailsRequired']!), findsOneWidget);
    expect(trust.reports, isEmpty);

    await tester.enterText(find.byKey(const Key('report-details')), 'Sent a different bank account by phone');
    await tester.tap(find.byKey(const Key('report-screenshot')));
    await tester.pumpAndSettle();
    await tester.tap(find.byKey(const Key('report-block')));
    await tester.tap(find.byKey(const Key('report-send')));
    await tester.pumpAndSettle();
    expect(trust.reports.single, {'reason': 'other', 'details': 'Sent a different bank account by phone', 'message': null, 'deal': 9, 'block': true, 'evidence': 'slip.png'});
    expect(find.text(kLangEn['reportSentBlocked']!), findsOneWidget);
  });

  testWidgets('payout account: owner adds it, change warning, masked; manager sees owner-only', (tester) async {
    _tall(tester);
    final payout = _FakePayout(account: {'id': 1, 'bank_name': 'CBE', 'account_name': 'Abyssinia Green PLC', 'account_last4': '6789', 'status': 'confirmed'});
    await tester.pumpWidget(_host(PayoutAccountScreen(service: payout)));
    await tester.pumpAndSettle();
    expect(find.byKey(const Key('payout-confirmed')), findsOneWidget);
    expect(find.text('CBE · ending 6789'), findsOneWidget);

    await tester.tap(find.byKey(const Key('payout-change')));
    await tester.pump();
    expect(find.byKey(const Key('payout-change-warning')), findsOneWidget);
    await tester.enterText(find.byKey(const Key('payout-bank')), 'Awash Bank');
    await tester.enterText(find.byKey(const Key('payout-name')), 'Abyssinia Green PLC');
    await tester.enterText(find.byKey(const Key('payout-number')), '12a');
    await tester.tap(find.byKey(const Key('payout-save')));
    await tester.pump();
    expect(find.text(kLangEn['payoutAccountNumberInvalid']!), findsOneWidget);
    await tester.enterText(find.byKey(const Key('payout-number')), '01320099887766');
    await tester.tap(find.byKey(const Key('payout-save')));
    await tester.pumpAndSettle();
    expect(payout.saved.single['number'], '01320099887766');
    expect(find.byKey(const Key('payout-pending')), findsOneWidget);
    expect(find.textContaining('Payments paused until'), findsOneWidget);
    expect(find.textContaining('01320099887766'), findsNothing);

    await tester.pumpWidget(_host(PayoutAccountScreen(key: const Key('manager'), service: _FakePayout(canChange: false))));
    await tester.pumpAndSettle();
    expect(find.byKey(const Key('payout-owner-only')), findsOneWidget);
    expect(find.byKey(const Key('payout-save')), findsNothing);
  });

  testWidgets('Amharic: deal room and chat warning render at phone width', (tester) async {
    _tall(tester);
    await tester.pumpWidget(_host(DealRoomHubScreen(dealRoomId: 9, service: _FakeDeals(_deal()), chat: _FakeChat(), trust: _FakeTrust()), locale: const Locale('am')));
    await tester.pumpAndSettle();
    expect(find.text(kLangAm['dealDocSealLinked']!), findsOneWidget);
    expect(tester.takeException(), isNull);

    await tester.pumpWidget(_host(TradeChatScreen(conversationId: 5, service: _FakeChat(), trust: _FakeTrust()), locale: const Locale('am')));
    await tester.pumpAndSettle();
    expect(find.text(kLangAm['chatKeepHere']!), findsOneWidget);
    expect(tester.takeException(), isNull);
  });

  test('every slice 7 key exists in English and Amharic', () {
    for (final k in kLangEn.keys.where((k) => RegExp(r'^(report|chat|deal|pay|payout|messageSeller)').hasMatch(k))) {
      expect(kLangAm.containsKey(k), isTrue, reason: k);
    }
  });
}
