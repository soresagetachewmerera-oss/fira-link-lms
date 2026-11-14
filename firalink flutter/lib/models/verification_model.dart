import 'organization_model.dart';

/// One required document in the verification dossier, with its review state from the server.
class DossierDocument {
  const DossierDocument({
    required this.type,
    required this.tier,
    required this.state,
    this.documentId,
    this.reason,
    this.reasonCode,
    this.submittedAt,
    this.reviewedAt,
    this.expiresAt,
    this.pages = 0,
    this.approvedCopyCounts = false,
    this.requiresExpiry = false,
    this.requiresNumber = false,
    this.fields = const [],
    this.cameraOnly = false,
    this.minPages = 1,
    this.maxPages = 10,
  });

  final String type;
  final int tier;

  /// not_uploaded, in_review, approved, needs_changes, rejected, expired
  final String state;
  final int? documentId;
  final String? reason;
  final String? reasonCode;
  final DateTime? submittedAt;
  final DateTime? reviewedAt;
  final DateTime? expiresAt;
  final int pages;
  final bool approvedCopyCounts;
  final bool requiresExpiry;
  final bool requiresNumber;
  final List<String> fields;
  final bool cameraOnly;
  final int minPages;
  final int maxPages;

  bool get needsAction => state == 'needs_changes' || state == 'rejected' || state == 'expired';

  factory DossierDocument.fromJson(Map<String, dynamic> j) => DossierDocument(
        type: j['type']?.toString() ?? '',
        tier: (j['tier'] as num?)?.toInt() ?? 1,
        state: j['state']?.toString() ?? 'not_uploaded',
        documentId: (j['document_id'] as num?)?.toInt(),
        reason: j['reason']?.toString(),
        reasonCode: j['reason_code']?.toString(),
        submittedAt: DateTime.tryParse(j['submitted_at']?.toString() ?? ''),
        reviewedAt: DateTime.tryParse(j['reviewed_at']?.toString() ?? ''),
        expiresAt: DateTime.tryParse(j['expires_at']?.toString() ?? ''),
        pages: (j['pages'] as num?)?.toInt() ?? 0,
        approvedCopyCounts: j['approved_until'] != null,
        requiresExpiry: j['requires_expiry'] == true,
        requiresNumber: j['requires_number'] == true,
        fields: (j['fields'] as List? ?? []).map((e) => e.toString()).toList(),
        cameraOnly: j['camera_only'] == true,
        minPages: (j['min_pages'] as num?)?.toInt() ?? 1,
        maxPages: (j['max_pages'] as num?)?.toInt() ?? 10,
      );
}

class DossierTier {
  const DossierTier({required this.tier, this.beingConfirmed = false, this.documents = const [], this.activeUntil});

  final int tier;
  final bool beingConfirmed;
  final List<DossierDocument> documents;
  final DateTime? activeUntil;

  factory DossierTier.fromJson(Map<String, dynamic> j) => DossierTier(
        tier: (j['tier'] as num?)?.toInt() ?? 1,
        beingConfirmed: j['being_confirmed'] == true,
        documents: (j['documents'] as List? ?? [])
            .map((d) => DossierDocument.fromJson(d as Map<String, dynamic>))
            .toList(),
        activeUntil: DateTime.tryParse(j['active_until']?.toString() ?? ''),
      );
}

/// GET /marketplace/verification/status. The tier is computed by the server from reviewed documents.
class VerificationDossier {
  const VerificationDossier({
    required this.businessName,
    required this.businessRole,
    required this.status,
    required this.tier,
    required this.maxTier,
    required this.tiers,
    required this.counts,
    required this.expectedReviewDays,
    required this.canManage,
    this.reviewedAt,
    this.validUntil,
    this.firaId,
    this.publicUrl,
    this.qrSvgUrl,
  });

  final String businessName;
  final String businessRole;
  final WorkspaceStatus status;
  final int tier;
  final int maxTier;
  final List<DossierTier> tiers;
  final Map<String, int> counts;
  final int expectedReviewDays;
  final bool canManage;
  final DateTime? reviewedAt;
  final DateTime? validUntil;
  final String? firaId;
  final String? publicUrl;
  final String? qrSvgUrl;

  bool get hasPublicPage => firaId != null && publicUrl != null;

  /// The first document the owner must fix, if any.
  DossierDocument? get firstToFix {
    for (final t in tiers) {
      for (final d in t.documents) {
        if (d.needsAction) return d;
      }
    }
    return null;
  }

  factory VerificationDossier.fromJson(Map<String, dynamic> j) {
    final business = j['business'] is Map<String, dynamic> ? j['business'] as Map<String, dynamic> : const <String, dynamic>{};
    final counts = j['counts'] is Map ? j['counts'] as Map : const {};
    return VerificationDossier(
      businessName: business['name']?.toString() ?? '',
      businessRole: business['business_role']?.toString() ?? '',
      status: WorkspaceStatus.fromApi(business['status']?.toString()),
      tier: (j['tier'] as num?)?.toInt() ?? 0,
      maxTier: (j['max_tier'] as num?)?.toInt() ?? 1,
      tiers: (j['tiers'] as List? ?? []).map((t) => DossierTier.fromJson(t as Map<String, dynamic>)).toList(),
      counts: {for (final e in counts.entries) e.key.toString(): (e.value as num?)?.toInt() ?? 0},
      expectedReviewDays: (j['expected_review_business_days'] as num?)?.toInt() ?? 3,
      canManage: j['can_manage'] == true,
      reviewedAt: DateTime.tryParse(j['reviewed_at']?.toString() ?? ''),
      validUntil: DateTime.tryParse(j['valid_until']?.toString() ?? ''),
      firaId: j['fira_id']?.toString(),
      publicUrl: j['public_url']?.toString(),
      qrSvgUrl: j['qr_svg_url']?.toString(),
    );
  }
}

class PublicCheckedItem {
  const PublicCheckedItem({required this.documentType, this.check, this.checkedAt, this.validUntil});

  final String documentType;
  final String? check;
  final DateTime? checkedAt;
  final DateTime? validUntil;

  factory PublicCheckedItem.fromJson(Map<String, dynamic> j) => PublicCheckedItem(
        documentType: j['document_type']?.toString() ?? '',
        check: j['check']?.toString(),
        checkedAt: DateTime.tryParse(j['checked_at']?.toString() ?? ''),
        validUntil: DateTime.tryParse(j['valid_until']?.toString() ?? ''),
      );
}

/// GET /sellers/{firaId}: only public fields (never TIN, ID numbers, phone or documents).
class PublicSeller {
  const PublicSeller({
    required this.firaId,
    required this.name,
    required this.businessRole,
    required this.status,
    required this.checked,
    required this.confirmedDeals,
    required this.memberSince,
    required this.publicUrl,
    required this.qrSvgUrl,
    this.city,
    this.country,
    this.tier,
    this.vetted = false,
    this.reviewedAt,
    this.validUntil,
  });

  final String firaId;
  final String name;
  final String businessRole;

  /// verified, expired, suspended
  final String status;
  final List<PublicCheckedItem> checked;
  final int confirmedDeals;
  final int memberSince;
  final String publicUrl;
  final String qrSvgUrl;
  final String? city;
  final String? country;
  final int? tier;
  final bool vetted;
  final DateTime? reviewedAt;
  final DateTime? validUntil;

  factory PublicSeller.fromJson(Map<String, dynamic> j) => PublicSeller(
        firaId: j['fira_id']?.toString() ?? '',
        name: j['name']?.toString() ?? '',
        businessRole: j['business_role']?.toString() ?? '',
        status: j['status']?.toString() ?? 'expired',
        checked: (j['checked'] as List? ?? []).map((c) => PublicCheckedItem.fromJson(c as Map<String, dynamic>)).toList(),
        confirmedDeals: (j['confirmed_deals'] as num?)?.toInt() ?? 0,
        memberSince: (j['member_since'] as num?)?.toInt() ?? 0,
        publicUrl: j['public_url']?.toString() ?? '',
        qrSvgUrl: j['qr_svg_url']?.toString() ?? '',
        city: j['city']?.toString(),
        country: j['country']?.toString(),
        tier: (j['tier'] as num?)?.toInt(),
        vetted: j['vetted'] == true,
        reviewedAt: DateTime.tryParse(j['reviewed_at']?.toString() ?? ''),
        validUntil: DateTime.tryParse(j['valid_until']?.toString() ?? ''),
      );
}
