/// Slice 8 fields for events and tours, from the app flows: who organizes or hosts (verified only
/// through an active Tier 1 of that business), tickets, agenda, tour dates and the deposit split.
library;

double? _d(dynamic v) => v == null ? null : double.tryParse(v.toString());
int? _i(dynamic v) => v == null ? null : int.tryParse(v.toString().split('.').first);
DateTime? _dt(dynamic v) => v == null ? null : DateTime.tryParse(v.toString());

/// Organizer of an event or host of a tour. [type]: business or fira_link.
class BookingBusiness {
  const BookingBusiness({required this.type, required this.name, this.verified = false, this.firaId, this.since, this.pastEvents});

  final String type;
  final String name;
  final bool verified;
  final String? firaId;
  final int? since;
  final int? pastEvents;

  bool get isFiraLink => type == 'fira_link';

  static BookingBusiness? fromJson(dynamic j) {
    if (j is! Map) return null;
    return BookingBusiness(
      type: j['type']?.toString() ?? 'business',
      name: j['name']?.toString() ?? '',
      verified: j['verified'] == true,
      firaId: j['fira_id']?.toString(),
      since: _i(j['since']),
      pastEvents: _i(j['past_events']),
    );
  }
}

class TicketOption {
  const TicketOption({required this.type, required this.price, this.eligible = true});

  /// general or member
  final String type;
  final double price;
  final bool eligible;
}

class AgendaItem {
  const AgendaItem({this.time, required this.title});

  final String? time;
  final String title;
}

class EventFlow {
  const EventFlow({
    this.category,
    this.isOnline = false,
    this.agenda = const [],
    this.tickets = const [],
    this.serviceFee = 0,
    this.refundPolicy,
    this.seatsLeft,
    this.organizer,
  });

  final String? category;
  final bool isOnline;
  final List<AgendaItem> agenda;
  final List<TicketOption> tickets;
  final double serviceFee;
  final String? refundPolicy;
  final int? seatsLeft;
  final BookingBusiness? organizer;

  static EventFlow? fromJson(dynamic j) {
    if (j is! Map) return null;
    return EventFlow(
      category: j['category']?.toString(),
      isOnline: j['is_online'] == true,
      agenda: (j['agenda'] as List? ?? [])
          .whereType<Map>()
          .map((a) => AgendaItem(time: a['time']?.toString(), title: a['title']?.toString() ?? ''))
          .where((a) => a.title.isNotEmpty)
          .toList(),
      tickets: (j['tickets'] as List? ?? [])
          .whereType<Map>()
          .map((t) => TicketOption(type: t['type']?.toString() ?? 'general', price: _d(t['price']) ?? 0, eligible: t['eligible'] != false))
          .toList(),
      serviceFee: _d(j['service_fee']) ?? 0,
      refundPolicy: j['refund_policy']?.toString(),
      seatsLeft: _i(j['seats_left']),
      organizer: BookingBusiness.fromJson(j['organizer']),
    );
  }
}

class TourDate {
  const TourDate({required this.date, required this.placesLeft});

  final DateTime date;
  final int placesLeft;
}

class TourFlow {
  const TourFlow({this.region, this.host, this.safetyNotes, this.depositPercent = 30, this.balanceDueDays = 14, this.nextDates = const []});

  final String? region;
  final BookingBusiness? host;
  final String? safetyNotes;
  final int depositPercent;
  final int balanceDueDays;
  final List<TourDate> nextDates;

  /// Deposit now, balance [balanceDueDays] before the tour; a tour sooner than that is paid in full.
  ({double total, double deposit, double balance, DateTime? balanceDue}) split(double price, int guests, DateTime date) {
    final total = price * guests;
    final due = DateTime(date.year, date.month, date.day).subtract(Duration(days: balanceDueDays));
    final today = DateTime.now();
    if (total <= 0 || depositPercent >= 100 || !due.isAfter(DateTime(today.year, today.month, today.day))) {
      return (total: total, deposit: total, balance: 0, balanceDue: null);
    }
    final deposit = (total * depositPercent / 100 * 100).roundToDouble() / 100;
    return (total: total, deposit: deposit, balance: total - deposit, balanceDue: due);
  }

  static TourFlow fromJson(Map<String, dynamic> j) => TourFlow(
        region: j['region']?.toString(),
        host: BookingBusiness.fromJson(j['host']),
        safetyNotes: j['safety_notes']?.toString(),
        depositPercent: _i(j['deposit_percent']) ?? 30,
        balanceDueDays: _i(j['balance_due_days']) ?? 14,
        nextDates: (j['next_dates'] as List? ?? [])
            .whereType<Map>()
            .map((d) => (_dt(d['date']), _i(d['places_left']) ?? 0))
            .where((d) => d.$1 != null)
            .map((d) => TourDate(date: d.$1!, placesLeft: d.$2))
            .toList(),
      );
}

/// What a tour booking has paid and still owes (from the server).
class TourBookingPayment {
  const TourBookingPayment({this.depositAmount = 0, this.balanceAmount = 0, this.balanceDueDate, this.depositPaid = false, this.balancePaid = false, this.paymentStatus});

  final double depositAmount;
  final double balanceAmount;
  final DateTime? balanceDueDate;
  final bool depositPaid;
  final bool balancePaid;
  final String? paymentStatus;

  bool get inReview => paymentStatus == 'in_review' || paymentStatus == 'balance_in_review';
  bool get canPayBalance => depositPaid && !balancePaid && balanceAmount > 0 && paymentStatus != 'balance_in_review';

  static TourBookingPayment fromJson(Map j) => TourBookingPayment(
        depositAmount: _d(j['deposit_amount']) ?? 0,
        balanceAmount: _d(j['balance_amount']) ?? 0,
        balanceDueDate: _dt(j['balance_due_date']),
        depositPaid: j['deposit_paid'] == true,
        balancePaid: j['balance_paid'] == true,
        paymentStatus: j['payment_status']?.toString(),
      );
}
