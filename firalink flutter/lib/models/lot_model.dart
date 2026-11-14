import 'organization_model.dart';

/// One evidence slot of a lot (a photo kind or the lab report), with its review state from the server.
class LotEvidenceItem {
  const LotEvidenceItem({
    required this.kind,
    required this.state,
    this.evidenceId,
    this.reason,
    this.reasonCode,
    this.capturedAt,
    this.submittedAt,
    this.expiresAt,
    this.approvedCopyCounts = false,
    this.optional = false,
  });

  /// photo_bags, photo_beans, photo_warehouse, lab_report
  final String kind;

  /// not_uploaded, in_review, approved, needs_changes, rejected, expired
  final String state;
  final int? evidenceId;
  final String? reason;
  final String? reasonCode;
  final DateTime? capturedAt;
  final DateTime? submittedAt;
  final DateTime? expiresAt;
  final bool approvedCopyCounts;
  final bool optional;

  bool get isPhoto => kind != 'lab_report';

  factory LotEvidenceItem.fromJson(Map<String, dynamic> j) => LotEvidenceItem(
        kind: j['kind']?.toString() ?? '',
        state: j['state']?.toString() ?? 'not_uploaded',
        evidenceId: (j['evidence_id'] as num?)?.toInt(),
        reason: j['reason']?.toString(),
        reasonCode: j['reason_code']?.toString(),
        capturedAt: DateTime.tryParse(j['captured_at']?.toString() ?? ''),
        submittedAt: DateTime.tryParse(j['submitted_at']?.toString() ?? ''),
        expiresAt: DateTime.tryParse(j['expires_at']?.toString() ?? ''),
        approvedCopyCounts: j['approved_copy_counts'] == true,
        optional: j['optional'] == true,
      );
}

/// A lot as its own business sees it. Level and status come from the server only.
class SellerLot {
  const SellerLot({
    required this.id,
    required this.lotCode,
    required this.title,
    required this.originRegion,
    required this.status,
    required this.evidenceLevel,
    required this.visibleToBuyers,
    this.cropYear,
    this.processingMethod,
    this.grade,
    this.totalQuantityBags,
    this.minimumOrderBags,
    this.pricePerLb,
    this.evidenceValidUntil,
    this.primaryImageUrl,
    this.slug,
    this.evidence = const [],
  });

  final int id;
  final String lotCode;
  final String title;
  final String originRegion;

  /// draft, active (published), paused, archived, sold_out
  final String status;

  /// claimed, documented, lab_backed, inspected
  final String evidenceLevel;
  final bool visibleToBuyers;
  final String? cropYear;
  final String? processingMethod;
  final String? grade;
  final int? totalQuantityBags;
  final int? minimumOrderBags;
  final double? pricePerLb;
  final DateTime? evidenceValidUntil;
  final String? primaryImageUrl;
  final String? slug;
  final List<LotEvidenceItem> evidence;

  bool get isDraft => status == 'draft';

  /// Days until the evidence (and so the level) expires, or null.
  int? daysUntilEvidenceExpires(DateTime now) {
    final until = evidenceValidUntil;
    if (until == null) return null;
    return DateTime(until.year, until.month, until.day).difference(DateTime(now.year, now.month, now.day)).inDays;
  }

  static double? _num(dynamic v) => v == null ? null : double.tryParse(v.toString());

  factory SellerLot.fromJson(Map<String, dynamic> j) => SellerLot(
        id: (j['id'] as num).toInt(),
        lotCode: j['lot_code']?.toString() ?? '',
        title: j['title']?.toString() ?? '',
        originRegion: j['origin_region']?.toString() ?? '',
        status: j['status']?.toString() ?? 'draft',
        evidenceLevel: j['evidence_level']?.toString() ?? 'claimed',
        visibleToBuyers: j['visible_to_buyers'] == true,
        cropYear: j['crop_year']?.toString(),
        processingMethod: j['processing_method']?.toString(),
        grade: j['grade']?.toString(),
        totalQuantityBags: (j['total_quantity_bags'] as num?)?.toInt(),
        minimumOrderBags: (j['minimum_order_bags'] as num?)?.toInt(),
        pricePerLb: _num(j['indicative_price_usd_per_lb']),
        evidenceValidUntil: DateTime.tryParse(j['evidence_valid_until']?.toString() ?? ''),
        primaryImageUrl: j['primary_image_url']?.toString(),
        slug: j['slug']?.toString(),
        evidence: (j['evidence'] as List? ?? []).map((e) => LotEvidenceItem.fromJson(e as Map<String, dynamic>)).toList(),
      );
}

/// GET /marketplace/seller/lots: the active business and its lots.
class SellerLots {
  const SellerLots({
    required this.businessName,
    required this.businessStatus,
    required this.visibleToBuyers,
    required this.canManage,
    required this.lots,
  });

  final String businessName;
  final WorkspaceStatus businessStatus;
  final bool visibleToBuyers;
  final bool canManage;
  final List<SellerLot> lots;

  factory SellerLots.fromJson(Map<String, dynamic> j) {
    final business = j['business'] is Map<String, dynamic> ? j['business'] as Map<String, dynamic> : const <String, dynamic>{};
    return SellerLots(
      businessName: business['name']?.toString() ?? '',
      businessStatus: WorkspaceStatus.fromApi(business['status']?.toString()),
      visibleToBuyers: business['visible_to_buyers'] == true,
      canManage: j['can_manage'] == true,
      lots: (j['lots'] as List? ?? []).map((l) => SellerLot.fromJson(l as Map<String, dynamic>)).toList(),
    );
  }
}
