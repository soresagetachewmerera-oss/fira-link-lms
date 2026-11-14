import 'trade_model.dart';

/// A sample between a buyer business and a seller business (slice 6). All values come from the server.
class SampleShipping {
  const SampleShipping({this.company, this.address, this.country, this.phone, this.courier, this.courierAccount});

  final String? company;
  final String? address;
  final String? country;
  final String? phone;
  final String? courier;
  final String? courierAccount;

  static SampleShipping? fromJson(dynamic j) {
    if (j is! Map) return null;
    return SampleShipping(
      company: j['company']?.toString(),
      address: j['address']?.toString(),
      country: j['country']?.toString(),
      phone: j['phone']?.toString(),
      courier: j['courier']?.toString(),
      courierAccount: j['courier_account']?.toString(),
    );
  }
}

class SampleView {
  const SampleView({
    required this.id,
    required this.reference,
    required this.status,
    required this.side,
    required this.grams,
    required this.feeUsd,
    required this.paymentStatus,
    this.paymentMocked = false,
    this.requestedAt,
    this.paidAt,
    this.shipBy,
    this.sealedAt,
    this.sealId,
    this.courier,
    this.trackingNumber,
    this.trackingUrl,
    this.dispatchedAt,
    this.deliveredAt,
    this.sealIntact,
    this.cuppingScore,
    this.cuppingNotes,
    this.seller,
    this.buyer,
    this.lot,
    this.shipping,
    this.sealedPhotoUrl,
    this.retainedPhotoUrl,
  });

  final int id;
  final String reference;

  /// requested, fee_paid, dispatched, delivered, cupped_approved, cupped_rejected, cancelled
  final String status;

  /// buyer or seller: which side the signed-in user is on
  final String side;
  final int grams;
  final double feeUsd;

  /// unpaid, paid, waived
  final String paymentStatus;
  final bool paymentMocked;
  final DateTime? requestedAt;
  final DateTime? paidAt;
  final DateTime? shipBy;
  final DateTime? sealedAt;
  final String? sealId;
  final String? courier;
  final String? trackingNumber;
  final String? trackingUrl;
  final DateTime? dispatchedAt;
  final DateTime? deliveredAt;
  final bool? sealIntact;
  final double? cuppingScore;
  final String? cuppingNotes;
  final BusinessSummary? seller;
  final BusinessSummary? buyer;
  final QuoteLot? lot;
  final SampleShipping? shipping;
  final String? sealedPhotoUrl;
  final String? retainedPhotoUrl;

  bool get isSeller => side == 'seller';

  static DateTime? _date(dynamic v) => DateTime.tryParse(v?.toString() ?? '');

  factory SampleView.fromJson(Map<String, dynamic> j) {
    final photos = j['photos'] is Map ? j['photos'] as Map : const {};
    return SampleView(
      id: (j['id'] as num?)?.toInt() ?? 0,
      reference: j['reference']?.toString() ?? '',
      status: j['status']?.toString() ?? 'requested',
      side: j['side']?.toString() ?? 'buyer',
      grams: (j['quantity_grams'] as num?)?.toInt() ?? 250,
      feeUsd: double.tryParse('${j['fee_usd'] ?? 0}') ?? 0,
      paymentStatus: j['payment_status']?.toString() ?? 'unpaid',
      paymentMocked: j['payment_mocked'] == true,
      requestedAt: _date(j['requested_at']),
      paidAt: _date(j['paid_at']),
      shipBy: _date(j['ship_by']),
      sealedAt: _date(j['sealed_at']),
      sealId: j['seal_id']?.toString(),
      courier: j['courier']?.toString(),
      trackingNumber: j['tracking_number']?.toString(),
      trackingUrl: j['tracking_url']?.toString(),
      dispatchedAt: _date(j['dispatched_at']),
      deliveredAt: _date(j['delivered_at']),
      sealIntact: j['seal_intact'] is bool ? j['seal_intact'] as bool : null,
      cuppingScore: j['cupping_score'] == null ? null : double.tryParse('${j['cupping_score']}'),
      cuppingNotes: j['cupping_notes']?.toString(),
      seller: BusinessSummary.fromJson(j['seller_business']),
      buyer: BusinessSummary.fromJson(j['buyer_business']),
      lot: QuoteLot.fromJson(j['lot']),
      shipping: SampleShipping.fromJson(j['shipping']),
      sealedPhotoUrl: photos['sealed']?.toString(),
      retainedPhotoUrl: photos['retained']?.toString(),
    );
  }
}

/// GET /marketplace/samples: a list plus, for the seller, counts per tab.
class SampleList {
  const SampleList({required this.side, required this.samples, this.counts = const {}});

  final String side;
  final List<SampleView> samples;
  final Map<String, int> counts;

  factory SampleList.fromJson(Map<String, dynamic> j) {
    final counts = j['counts'] is Map ? j['counts'] as Map : const {};
    return SampleList(
      side: j['side']?.toString() ?? 'buyer',
      samples: (j['samples'] as List? ?? []).whereType<Map<String, dynamic>>().map(SampleView.fromJson).toList(),
      counts: {for (final e in counts.entries) e.key.toString(): (e.value as num?)?.toInt() ?? 0},
    );
  }
}
