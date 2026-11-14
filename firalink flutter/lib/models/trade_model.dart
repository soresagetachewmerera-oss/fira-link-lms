/// Slice 5: requests (RFQs), quotes, exporters and market filters. All values come from the server.
library;

double? _d(dynamic v) => v == null ? null : double.tryParse(v.toString());
int? _i(dynamic v) => v == null ? null : int.tryParse(v.toString().split('.').first);

/// The other trading party, as a business only (never a person or contact details).
class BusinessSummary {
  const BusinessSummary({required this.id, required this.name, required this.businessRole, required this.tier, this.firaId});

  final int id;
  final String name;
  final String businessRole;
  final int tier;
  final String? firaId;

  bool get verified => tier >= 1;

  static BusinessSummary? fromJson(dynamic j) {
    if (j is! Map) return null;
    return BusinessSummary(
      id: _i(j['id']) ?? 0,
      name: j['name']?.toString() ?? '',
      businessRole: j['business_role']?.toString() ?? '',
      tier: _i(j['verification_tier']) ?? 0,
      firaId: j['fira_id']?.toString(),
    );
  }
}

class Rfq {
  const Rfq({
    required this.id,
    required this.title,
    required this.volumeValue,
    required this.volumeUnit,
    required this.destination,
    required this.incoterm,
    required this.status,
    required this.quotesCount,
    this.originRegion,
    this.grade,
    this.processingMethod,
    this.targetPrice,
    this.neededBy,
    this.closingDate,
    this.sampleFirst = false,
    this.notes,
    this.buyer,
  });

  final int id;
  final String title;
  final double volumeValue;

  /// bags (60 kg), kg, mt
  final String volumeUnit;
  final String destination;
  final String incoterm;

  /// open, closed, fulfilled, expired
  final String status;
  final int quotesCount;
  final String? originRegion;
  final String? grade;
  final String? processingMethod;
  final double? targetPrice;
  final DateTime? neededBy;
  final DateTime? closingDate;
  final bool sampleFirst;
  final String? notes;
  final BusinessSummary? buyer;

  bool get isOpen => status == 'open';

  factory Rfq.fromJson(Map<String, dynamic> j) => Rfq(
        id: _i(j['id']) ?? 0,
        title: j['title']?.toString() ?? '',
        volumeValue: _d(j['volume_value']) ?? 0,
        volumeUnit: j['volume_unit']?.toString() ?? 'bags',
        destination: j['destination']?.toString() ?? '',
        incoterm: j['incoterm']?.toString() ?? 'FOB',
        status: j['status']?.toString() ?? 'open',
        quotesCount: _i(j['quotes_count']) ?? 0,
        originRegion: j['origin_region']?.toString(),
        grade: j['grade']?.toString(),
        processingMethod: j['processing_method']?.toString(),
        targetPrice: _d(j['target_price_usd_per_lb']),
        neededBy: DateTime.tryParse(j['needed_by']?.toString() ?? ''),
        closingDate: DateTime.tryParse(j['closing_date']?.toString() ?? ''),
        sampleFirst: j['sample_first'] == true,
        notes: j['notes']?.toString(),
        buyer: BusinessSummary.fromJson(j['buyer_business']),
      );
}

class QuoteLot {
  const QuoteLot({required this.id, required this.title, required this.lotCode, required this.evidenceLevel, this.slug, this.originRegion, this.imageUrl});

  final int id;
  final String title;
  final String lotCode;
  final String evidenceLevel;
  final String? slug;
  final String? originRegion;
  final String? imageUrl;

  static QuoteLot? fromJson(dynamic j) {
    if (j is! Map) return null;
    return QuoteLot(
      id: _i(j['id']) ?? 0,
      title: j['title']?.toString() ?? '',
      lotCode: j['lot_code']?.toString() ?? '',
      evidenceLevel: j['evidence_level']?.toString() ?? 'claimed',
      slug: j['slug']?.toString(),
      originRegion: j['origin_region']?.toString(),
      imageUrl: j['primary_image_url']?.toString(),
    );
  }
}

class ReferencePrice {
  const ReferencePrice({required this.minimum, required this.source, required this.effectiveDate});

  final double minimum;
  final String source;
  final DateTime? effectiveDate;

  static ReferencePrice? fromJson(dynamic j) {
    if (j is! Map) return null;
    return ReferencePrice(
      minimum: _d(j['minimum_usd_per_lb']) ?? 0,
      source: j['source']?.toString() ?? '',
      effectiveDate: DateTime.tryParse(j['effective_date']?.toString() ?? ''),
    );
  }
}

/// A quote as the buyer compares it, or as the seller sees their own.
class TradeQuote {
  const TradeQuote({
    required this.id,
    required this.rfqId,
    required this.status,
    required this.pricePerLb,
    required this.volumeBags,
    required this.totalValue,
    required this.canAccept,
    required this.belowMinimum,
    this.dispatchDays,
    this.readyToShip,
    this.validUntil,
    this.note,
    this.seller,
    this.lot,
    this.reference,
    this.blocker,
    this.rfq,
  });

  final int id;
  final int rfqId;

  /// submitted, accepted, not_selected, rejected, expired
  final String status;
  final double pricePerLb;
  final int volumeBags;
  final double totalValue;
  final bool canAccept;
  final bool belowMinimum;
  final int? dispatchDays;
  final DateTime? readyToShip;
  final DateTime? validUntil;
  final String? note;
  final BusinessSummary? seller;
  final QuoteLot? lot;
  final ReferencePrice? reference;

  /// Why it cannot be accepted (REQUEST_CLOSED, SELLER_NOT_VERIFIED, BELOW_MINIMUM, ...).
  final String? blocker;
  final Rfq? rfq;

  factory TradeQuote.fromJson(Map<String, dynamic> j) => TradeQuote(
        id: _i(j['id']) ?? 0,
        rfqId: _i(j['rfq_id']) ?? 0,
        status: j['status']?.toString() ?? 'submitted',
        pricePerLb: _d(j['price_usd_per_lb']) ?? 0,
        volumeBags: _i(j['volume_bags']) ?? 0,
        totalValue: _d(j['total_value_usd']) ?? 0,
        canAccept: j['can_accept'] == true,
        belowMinimum: j['below_minimum'] == true,
        dispatchDays: _i(j['dispatch_days']),
        readyToShip: DateTime.tryParse(j['ready_to_ship']?.toString() ?? ''),
        validUntil: DateTime.tryParse(j['valid_until']?.toString() ?? ''),
        note: j['note']?.toString(),
        seller: BusinessSummary.fromJson(j['seller_business']),
        lot: QuoteLot.fromJson(j['lot']),
        reference: ReferencePrice.fromJson(j['reference_price']),
        blocker: j['blocker']?.toString(),
        rfq: j['rfq'] is Map<String, dynamic> ? Rfq.fromJson(j['rfq'] as Map<String, dynamic>) : null,
      );
}

class ExporterCard {
  const ExporterCard({
    required this.id,
    required this.name,
    required this.businessRole,
    required this.tier,
    required this.activeLots,
    required this.memberSince,
    this.city,
    this.country,
    this.firaId,
  });

  final int id;
  final String name;
  final String businessRole;
  final int tier;
  final int activeLots;
  final int memberSince;
  final String? city;
  final String? country;
  final String? firaId;

  factory ExporterCard.fromJson(Map<String, dynamic> j) => ExporterCard(
        id: _i(j['id']) ?? 0,
        name: j['name']?.toString() ?? '',
        businessRole: j['business_role']?.toString() ?? '',
        tier: _i(j['verification_tier']) ?? 0,
        activeLots: _i(j['active_lots']) ?? 0,
        memberSince: _i(j['member_since']) ?? 0,
        city: j['city']?.toString(),
        country: j['country']?.toString(),
        firaId: j['fira_id']?.toString(),
      );
}

class ExporterProfile {
  const ExporterProfile({required this.card, required this.confirmedDeals, required this.lots, this.about, this.usualReplyHours});

  final ExporterCard card;
  final int confirmedDeals;
  final String? about;
  final int? usualReplyHours;
  final List<Map<String, dynamic>> lots;

  factory ExporterProfile.fromJson(Map<String, dynamic> j) => ExporterProfile(
        card: ExporterCard.fromJson(j),
        confirmedDeals: _i(j['confirmed_deals']) ?? 0,
        about: j['about']?.toString(),
        usualReplyHours: _i(j['usual_reply_hours']),
        lots: (j['lots'] as List? ?? []).whereType<Map<String, dynamic>>().toList(),
      );
}

/// Market filters (Filters screen). Sent to GET /marketplace/listings.
class MarketFilters {
  const MarketFilters({
    this.regions = const {},
    this.processes = const {},
    this.grades = const {},
    this.minLevel,
    this.minTier,
    this.minPrice,
    this.maxPrice,
  });

  final Set<String> regions;
  final Set<String> processes;

  /// grade_1, grade_2, grade_3, g4_plus
  final Set<String> grades;

  /// documented, lab_backed, inspected
  final String? minLevel;

  /// 2 or 3 (Tier 1 is every visible lot)
  final int? minTier;
  final double? minPrice;
  final double? maxPrice;

  bool get isEmpty =>
      regions.isEmpty && processes.isEmpty && grades.isEmpty && minLevel == null && minTier == null && minPrice == null && maxPrice == null;

  int get count =>
      regions.length + processes.length + grades.length + (minLevel != null ? 1 : 0) + (minTier != null ? 1 : 0) + (minPrice != null || maxPrice != null ? 1 : 0);

  Map<String, dynamic> toQuery() => {
        if (regions.isNotEmpty) 'regions[]': regions.toList(),
        if (processes.isNotEmpty) 'processes[]': processes.toList(),
        if (grades.isNotEmpty) 'grades[]': grades.toList(),
        'min_level': ?minLevel,
        if (minTier != null) 'min_tier': '$minTier',
        if (minPrice != null) 'min_price': minPrice!.toString(),
        if (maxPrice != null) 'max_price': maxPrice!.toString(),
      };

  MarketFilters copyWith({
    Set<String>? regions,
    Set<String>? processes,
    Set<String>? grades,
    String? Function()? minLevel,
    int? Function()? minTier,
    double? Function()? minPrice,
    double? Function()? maxPrice,
  }) =>
      MarketFilters(
        regions: regions ?? this.regions,
        processes: processes ?? this.processes,
        grades: grades ?? this.grades,
        minLevel: minLevel != null ? minLevel() : this.minLevel,
        minTier: minTier != null ? minTier() : this.minTier,
        minPrice: minPrice != null ? minPrice() : this.minPrice,
        maxPrice: maxPrice != null ? maxPrice() : this.maxPrice,
      );
}
