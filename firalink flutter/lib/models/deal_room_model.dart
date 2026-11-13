import 'coffee_export_models.dart';
import 'organization_model.dart';
import 'user_model.dart';

class DealRoomModel {
  final int id;
  final String dealReference;
  final int organizationId;
  final int buyerId;
  final int? listingId;
  final int? rfqId;
  final String title;
  final double targetValueUsd;
  final String incoterm;
  final String status; // active, under_contract, completed, cancelled, disputed
  final String? contractDocumentPath;
  final String? sellerSignedAt;
  final String? buyerSignedAt;
  final String? sellerSignature;
  final String? buyerSignature;
  final String? disputeStatus;
  final String? disputeReason;
  final String? escrowProofPath;
  final String? createdAt;
  final OrganizationModel? organization;
  final User? buyer;
  final CoffeeListingModel? listing;
  final List<DealMilestoneModel> milestones;

  DealRoomModel({
    required this.id,
    required this.dealReference,
    required this.organizationId,
    required this.buyerId,
    this.listingId,
    this.rfqId,
    required this.title,
    required this.targetValueUsd,
    this.incoterm = 'FOB Djibouti',
    required this.status,
    this.contractDocumentPath,
    this.sellerSignedAt,
    this.buyerSignedAt,
    this.sellerSignature,
    this.buyerSignature,
    this.disputeStatus,
    this.disputeReason,
    this.escrowProofPath,
    this.createdAt,
    this.organization,
    this.buyer,
    this.listing,
    this.milestones = const [],
  });

  bool get isUnderContract => status == 'under_contract';
  bool get isCompleted => status == 'completed';
  bool get isDisputed => status == 'disputed' || disputeStatus != null;
  bool get isFullySigned => sellerSignedAt != null && buyerSignedAt != null;

  factory DealRoomModel.fromJson(Map<String, dynamic> json) {
    List<DealMilestoneModel> milestonesList = [];
    if (json['milestones'] != null && json['milestones'] is List) {
      milestonesList = (json['milestones'] as List)
          .map((m) => DealMilestoneModel.fromJson(m as Map<String, dynamic>))
          .toList();
    }

    return DealRoomModel(
      id: (json['id'] as num?)?.toInt() ?? 0,
      dealReference: json['deal_reference']?.toString() ?? '',
      organizationId: (json['organization_id'] as num?)?.toInt() ?? 0,
      buyerId: (json['buyer_id'] as num?)?.toInt() ?? 0,
      listingId: (json['listing_id'] as num?)?.toInt(),
      rfqId: (json['rfq_id'] as num?)?.toInt(),
      title: json['title']?.toString() ?? '',
      targetValueUsd: (json['target_value_usd'] as num?)?.toDouble() ?? 0.0,
      incoterm: json['incoterm']?.toString() ?? 'FOB Djibouti',
      status: json['status']?.toString() ?? 'active',
      contractDocumentPath: json['contract_document_path']?.toString(),
      sellerSignedAt: json['seller_signed_at']?.toString(),
      buyerSignedAt: json['buyer_signed_at']?.toString(),
      sellerSignature: json['seller_signature']?.toString(),
      buyerSignature: json['buyer_signature']?.toString(),
      disputeStatus: json['dispute_status']?.toString(),
      disputeReason: json['dispute_reason']?.toString(),
      escrowProofPath: json['escrow_proof_path']?.toString(),
      createdAt: json['created_at']?.toString(),
      organization: json['organization'] != null
          ? OrganizationModel.fromJson(json['organization'])
          : null,
      buyer: json['buyer'] != null ? User.fromJson(json['buyer']) : null,
      listing: json['listing'] != null
          ? CoffeeListingModel.fromJson(json['listing'])
          : null,
      milestones: milestonesList,
    );
  }
}

class DealMilestoneModel {
  final int id;
  final int dealRoomId;
  final String title;
  final String? milestoneKey;
  final String? description;
  final String? documentPath;
  final String? notes;
  final String status; // pending, in_progress, achieved, waived
  final String? dueDate;
  final String? completedAt;

  DealMilestoneModel({
    required this.id,
    required this.dealRoomId,
    required this.title,
    this.milestoneKey,
    this.description,
    this.documentPath,
    this.notes,
    required this.status,
    this.dueDate,
    this.completedAt,
  });

  bool get isAchieved => status == 'achieved';
  bool get isInProgress => status == 'in_progress';
  bool get isPending => status == 'pending';

  factory DealMilestoneModel.fromJson(Map<String, dynamic> json) {
    return DealMilestoneModel(
      id: (json['id'] as num?)?.toInt() ?? 0,
      dealRoomId: (json['deal_room_id'] as num?)?.toInt() ?? 0,
      title: json['title']?.toString() ?? '',
      milestoneKey: json['milestone_key']?.toString(),
      description: json['description']?.toString(),
      documentPath: json['document_path']?.toString(),
      notes: json['notes']?.toString(),
      status: json['status']?.toString() ?? 'pending',
      dueDate: json['due_date']?.toString(),
      completedAt: json['completed_at']?.toString(),
    );
  }
}

