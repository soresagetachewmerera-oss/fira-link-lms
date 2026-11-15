import 'dart:typed_data';

import 'package:eduex_app/l10n/app_localizations.dart';
import 'package:eduex_app/l10n/lang_am.dart';
import 'package:eduex_app/l10n/lang_en.dart';
import 'package:eduex_app/models/booking_flow_model.dart';
import 'package:eduex_app/models/event.dart';
import 'package:eduex_app/models/tour.dart';
import 'package:eduex_app/providers/auth_provider.dart';
import 'package:eduex_app/screens/course_access/widgets/course_progress_strip.dart';
import 'package:eduex_app/screens/events/ticket_booking_screen.dart';
import 'package:eduex_app/screens/events/widgets/event_flow_sections.dart';
import 'package:eduex_app/screens/tours/tour_booking_screen.dart';
import 'package:eduex_app/screens/tours/widgets/tour_flow_sections.dart';
import 'package:eduex_app/screens/tours/widgets/tour_payment_panel.dart';
import 'package:eduex_app/services/booking_service.dart';
import 'package:eduex_app/services/upload_file.dart';
import 'package:eduex_app/theme/app_theme.dart';
import 'package:eduex_app/widgets/booking_flow_widgets.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

String _iso(DateTime d) => d.toIso8601String().substring(0, 10);

final _far = DateTime.now().add(const Duration(days: 40));
final _soon = DateTime.now().add(const Duration(days: 5));

Tour _tour() => Tour.fromJson({
      'id': 4,
      'title': 'Guji harvest',
      'destination': 'Guji',
      'duration_days': 3,
      'total_seats': 4,
      'remaining_seats': 4,
      'price': 10000,
      'currency': 'ETB',
      'difficulty_level': 'Moderate',
      'exclusions': ['Flights', 'Insurance'],
      'cancellation_policy': 'Full refund up to 14 days before.',
      'region': 'Guji',
      'host': {'type': 'business', 'name': 'Guji Highland Coop', 'verified': true, 'since': 2019},
      'safety_notes': 'Emergency +251911000000. Bring boots.',
      'deposit_percent': 30,
      'balance_due_days': 14,
      'next_dates': [
        {'date': _iso(_soon), 'places_left': 0},
        {'date': _iso(_far), 'places_left': 3},
      ],
    });

Event _event({bool member = false}) => Event.fromJson({
      'id': 7,
      'title': 'Addis Cupping Day',
      'description': '',
      'start_date': _iso(_far),
      'location': 'Skylight Hotel',
      'price': 500,
      'is_published': true,
      'currency': 'ETB',
      'flow': {
        'category': 'cupping',
        'agenda': [
          {'time': '09:00', 'title': 'Welcome'},
          {'time': '10:30', 'title': 'Washed Guji table'},
        ],
        'tickets': [
          {'type': 'general', 'price': 500, 'eligible': true},
          {'type': 'member', 'price': 300, 'eligible': member},
        ],
        'service_fee': 25,
        'refund_policy': 'Full refund up to 7 days before.',
        'seats_left': 3,
        'organizer': {'type': 'business', 'name': 'Abyssinia Green PLC', 'verified': true, 'past_events': 4},
      },
    });

class _FakeBookings extends BookingService {
  Map<String, dynamic>? fields;
  UploadFile? slip;
  final List<String> calls = [];

  @override
  Future<Map<String, dynamic>> bookTour(int tourId, Map<String, dynamic> fields, {UploadFile? slip}) async {
    calls.add('tour');
    this.fields = fields;
    this.slip = slip;
    return {'booking_id': 11, 'booking': {'id': 11, 'reference': 'TR-1'}, 'payment_required': false};
  }

  @override
  Future<Map<String, dynamic>> bookEvent(int eventId, Map<String, dynamic> fields, {UploadFile? slip}) async {
    calls.add('event');
    this.fields = fields;
    this.slip = slip;
    return {'booking_id': 12, 'payment_required': false};
  }

  @override
  Future<Map<String, dynamic>> payTourBalance(int bookingId, {required String method, UploadFile? slip, String? bankReference}) async {
    calls.add('balance:$method:${slip?.name}');
    return {'payment_required': false};
  }
}

Future<UploadFile?> _slip() async => UploadFile(name: 'slip.png', bytes: Uint8List.fromList(List.filled(30 * 1024, 7)));

Widget _host(Widget child, {Locale locale = const Locale('en')}) => ChangeNotifierProvider(
      create: (_) => AuthProvider(),
      child: MaterialApp(
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
      ),
    );

void _tall(WidgetTester tester) {
  tester.view.physicalSize = const Size(1170, 7000);
  tester.view.devicePixelRatio = 3;
  addTearDown(tester.view.reset);
}

void main() {
  setUp(() => SharedPreferences.setMockInitialValues({}));

  test('the deposit split matches the server rule', () {
    final flow = _tour().flow;
    final far = flow.split(10000, 2, _far);
    expect(far.deposit, 6000);
    expect(far.balance, 14000);
    expect(_iso(far.balanceDue!), _iso(DateTime(_far.year, _far.month, _far.day).subtract(const Duration(days: 14))));
    final soon = flow.split(10000, 1, _soon);
    expect(soon.balance, 0);
    expect(soon.deposit, 10000);
  });

  testWidgets('book a tour: full dates are disabled, guests capped by places, deposit now and balance later, slip required', (tester) async {
    _tall(tester);
    final svc = _FakeBookings();
    await tester.pumpWidget(_host(TourBookingScreen(tour: _tour(), service: svc, pickSlip: _slip)));
    await tester.pumpAndSettle();

    // The full date is not selectable; the open one is selected.
    expect(tester.widget<ChoiceChip>(find.byKey(Key('tour-date-${_iso(_soon)}'))).onSelected, isNull);
    expect(tester.widget<ChoiceChip>(find.byKey(Key('tour-date-${_iso(_far)}'))).selected, isTrue);
    expect(find.text('3 places left'), findsOneWidget);

    for (var i = 0; i < 5; i++) {
      await tester.tap(find.byKey(const Key('guests-plus')));
      await tester.pump();
    }
    expect(find.text('3'), findsWidgets); // capped at the places left
    await tester.tap(find.byKey(const Key('guests-minus')));
    await tester.pump();
    expect(find.text('Pay now (30%)'), findsOneWidget);
    expect(find.text('ETB 6000.00'), findsOneWidget);
    expect(find.text('ETB 14000.00'), findsOneWidget);
    expect(find.text(kLangEn['tourNeverPayHost']!), findsOneWidget);

    await tester.enterText(find.byKey(const Key('tour-first')), 'Sara');
    await tester.enterText(find.byKey(const Key('tour-last')), 'Ali');
    await tester.enterText(find.byKey(const Key('tour-phone')), '+251911000001');
    await tester.enterText(find.byKey(const Key('tour-requests')), 'Vegetarian');
    await tester.tap(find.byKey(const Key('method-bank_transfer')));
    await tester.pump();
    await tester.tap(find.byKey(const Key('tour-confirm')));
    await tester.pump();
    expect(find.byKey(const Key('booking-slip-required')), findsOneWidget);
    expect(svc.calls, isEmpty);

    await tester.tap(find.byKey(const Key('booking-slip')));
    await tester.pumpAndSettle();
    await tester.tap(find.byKey(const Key('tour-confirm')));
    await tester.pump();
    expect(svc.calls, ['tour']);
    expect(svc.fields!['schedule_date'], _iso(_far));
    expect(svc.fields!['participants'], 2);
    expect(svc.fields!['payment_method'], 'bank_transfer');
    expect(svc.fields!['special_requests'], 'Vegetarian');
    expect(svc.slip?.name, 'slip.png');
  });

  testWidgets('register for an event: member ticket only for members, service fee in the total, refund policy, bank slip', (tester) async {
    _tall(tester);
    final svc = _FakeBookings();
    await tester.pumpWidget(_host(TicketBookingScreen(event: _event(), service: svc, pickSlip: _slip)));
    await tester.pumpAndSettle();

    expect(tester.widget<RadioListTile<String>>(find.byKey(const Key('ticket-member'))).enabled, isFalse);
    expect(find.text(kLangEn['eventTicketMemberOnly']!), findsOneWidget);
    expect(find.text('Service fee'), findsOneWidget);
    expect(find.text('ETB 525.00'), findsOneWidget); // 500 + 25
    expect(find.byKey(const Key('event-refunds')), findsOneWidget);

    await tester.tap(find.byKey(const Key('qty-plus')));
    await tester.pump();
    expect(find.text('ETB 1050.00'), findsOneWidget);

    await tester.enterText(find.byKey(const Key('ev-first')), 'Abebe');
    await tester.enterText(find.byKey(const Key('ev-last')), 'Kebede');
    await tester.enterText(find.byKey(const Key('ev-phone')), '+251911000000');
    await tester.enterText(find.byKey(const Key('ev-company')), 'Abyssinia Green');
    await tester.tap(find.byKey(const Key('method-bank_transfer')));
    await tester.pump();
    await tester.tap(find.byKey(const Key('booking-slip')));
    await tester.pumpAndSettle();
    await tester.tap(find.byKey(const Key('event-pay')));
    await tester.pump();
    expect(svc.fields!['ticket_type'], 'general');
    expect(svc.fields!['seats'], 2);
    expect(svc.fields!['company'], 'Abyssinia Green');
    expect(svc.fields!['payment_method'], 'bank_transfer');
    expect(svc.slip?.name, 'slip.png');
  });

  testWidgets('a member sees the member ticket price', (tester) async {
    _tall(tester);
    await tester.pumpWidget(_host(TicketBookingScreen(event: _event(member: true), service: _FakeBookings())));
    await tester.pumpAndSettle();
    await tester.tap(find.byKey(const Key('ticket-member')));
    await tester.pump();
    expect(find.text('ETB 325.00'), findsOneWidget); // 300 + 25
  });

  testWidgets('event detail sections: organizer verified, seats left, agenda, tickets', (tester) async {
    _tall(tester);
    final e = _event();
    await tester.pumpWidget(_host(Scaffold(body: ListView(children: [
      BookingBusinessCard(business: e.flow!.organizer!),
      EventFlowSections(flow: e.flow!, currency: 'ETB'),
    ]))));
    await tester.pumpAndSettle();
    expect(find.text('Organizer verified'), findsOneWidget);
    expect(find.text('4 past events on Fira Link'), findsOneWidget);
    expect(find.text('3 seats left'), findsOneWidget);
    expect(find.text('Washed Guji table'), findsOneWidget);
    expect(find.text('ETB 300.00'), findsOneWidget);
  });

  testWidgets('an unverified organizer is not shown as verified', (tester) async {
    await tester.pumpWidget(_host(const Scaffold(body: VerifiedBusinessLine(business: BookingBusiness(type: 'business', name: 'X', verified: false)))));
    await tester.pumpAndSettle();
    expect(find.text('Organizer verified'), findsNothing);
    expect(find.text('Not verified'), findsOneWidget);
  });

  testWidgets('tour detail sections: host, not included, cancellation, safety and never pay the host, dates', (tester) async {
    _tall(tester);
    await tester.pumpWidget(_host(Scaffold(body: ListView(children: [TourFlowSections(tour: _tour())]))));
    await tester.pumpAndSettle();
    expect(find.text('Guji Highland Coop'), findsOneWidget);
    expect(find.text('Host verified'), findsOneWidget);
    expect(find.text('Hosting since 2019'), findsOneWidget);
    expect(find.text('Insurance'), findsOneWidget);
    expect(find.textContaining('Never pay the host directly'), findsOneWidget);
    expect(find.textContaining('Full'), findsWidgets);
    expect(find.textContaining('Pay 30% now'), findsOneWidget);
  });

  testWidgets('booking confirmed panel: paid, balance due, pay the balance with a slip, pay only inside Fira Link', (tester) async {
    _tall(tester);
    final svc = _FakeBookings();
    var reloaded = 0;
    await tester.pumpWidget(_host(Scaffold(
      body: ListView(children: [
        TourPaymentPanel(
          bookingId: 11,
          service: svc,
          pickSlip: _slip,
          onChanged: () => reloaded++,
          ticket: {
            'currency': 'ETB',
            'tour_name': 'Guji harvest',
            'booking_id': 'TR-1',
            'schedule_date_iso': _iso(_far),
            'deposit_amount': 6000,
            'balance_amount': 14000,
            'balance_due_date': _iso(_far.subtract(const Duration(days: 14))),
            'deposit_paid': true,
            'balance_paid': false,
            'payment_status': 'deposit_paid',
            'host': {'type': 'business', 'name': 'Guji Highland Coop', 'verified': true},
          },
        ),
      ]),
    )));
    await tester.pumpAndSettle();
    expect(find.text('Paid'), findsOneWidget);
    expect(find.text('ETB 6000.00'), findsOneWidget);
    expect(find.text('ETB 14000.00'), findsOneWidget);
    expect(find.text(kLangEn['tourPayOnlyHere']!), findsOneWidget);
    expect(find.text(kLangEn['tourWhatNext']!), findsOneWidget);

    await tester.tap(find.byKey(const Key('tour-pay-balance')));
    await tester.pumpAndSettle();
    await tester.tap(find.byKey(const Key('method-bank_transfer')).last);
    await tester.pump();
    await tester.tap(find.byKey(const Key('booking-slip')).last);
    await tester.pumpAndSettle();
    await tester.tap(find.byKey(const Key('balance-send')));
    await tester.pumpAndSettle();
    expect(svc.calls, ['balance:bank_transfer:slip.png']);
    expect(reloaded, 1);
  });

  testWidgets('a slip waiting for finance shows In review in purple and no pay button', (tester) async {
    _tall(tester);
    await tester.pumpWidget(_host(Scaffold(
      body: ListView(children: [
        TourPaymentPanel(bookingId: 1, onChanged: () {}, ticket: const {'currency': 'ETB', 'deposit_amount': 6000, 'balance_amount': 14000, 'deposit_paid': false, 'payment_status': 'in_review'}),
      ]),
    )));
    await tester.pumpAndSettle();
    expect(find.byKey(const Key('in-review-pill')), findsOneWidget);
    expect(find.byKey(const Key('tour-pay-balance')), findsNothing);
  });

  testWidgets('course progress: lesson n of m, percent, up next, ask Fira AI', (tester) async {
    var asked = false;
    await tester.pumpWidget(_host(Scaffold(
      body: CourseProgressStrip(
        progress: const {'percent': 33, 'up_next': {'title': 'Grading green coffee'}},
        position: const {'number': 2, 'total': 6},
        onAskAi: () => asked = true,
      ),
    )));
    await tester.pumpAndSettle();
    expect(find.text('Lesson 2 of 6'), findsOneWidget);
    expect(find.text('33%'), findsOneWidget);
    expect(find.text('Up next: Grading green coffee'), findsOneWidget);
    await tester.tap(find.byKey(const Key('course-ask-ai')));
    expect(asked, isTrue);
  });

  testWidgets('Amharic: tour booking and register render at phone width', (tester) async {
    _tall(tester);
    await tester.pumpWidget(_host(TourBookingScreen(tour: _tour(), service: _FakeBookings()), locale: const Locale('am')));
    await tester.pumpAndSettle();
    expect(find.text(kLangAm['tourBookTitle']!), findsOneWidget);
    expect(tester.takeException(), isNull);

    await tester.pumpWidget(_host(TicketBookingScreen(event: _event(), service: _FakeBookings()), locale: const Locale('am')));
    await tester.pumpAndSettle();
    expect(find.text(kLangAm['eventPayAndGetTicket']!), findsOneWidget);
    expect(tester.takeException(), isNull);
  });
}
