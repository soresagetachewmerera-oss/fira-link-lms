import 'coffee_export_models.dart';
import 'organization_model.dart';
import 'user_model.dart';

class SampleOrderModel {
  final int id;
  final String sampleReference;
  final int listingId;
  final int organizationId;
  final int buyerId;
  final String status; // requested, fee_paid, dispatched, delivered, cupped_approved, cupped_rejected, cancelled
  final int quantityGrams;
  final String shippingAddress;
  final String? courierName;
  final String? waybillNumber;
  final String? retainedSampleSealNo;
  final String? retainedSampleSealHash;
  final String? dispatchDate;
  final String? deliveryDate;
  final double? cuppingScore;
  final String? cuppingNotes;
  final String? labReportPath;
  final double feeAmountUsd;
  final String paymentStatus;
  final String? createdAt;
  final CoffeeListingModel? listing;
  final OrganizationModel? organization;
  final User? buyer;

  SampleOrderModel({
    required this.id,
    required this.sampleReference,
    required this.listingId,
    required this.organizationId,
    required this.buyerId,
    required this.status,
    required this.quantityGrams,
    required this.shippingAddress,
    this.courierName,
    this.waybillNumber,
    this.retainedSampleSealNo,
    this.retainedSampleSealHash,
    this.dispatchDate,
    this.deliveryDate,
    this.cuppingScore,
    this.cuppingNotes,
    this.labReportPath,
    this.feeAmountUsd = 0.0,
    this.paymentStatus = 'unpaid',
    this.createdAt,
    this.listing,
    this.organization,
    this.buyer,
  });

  bool get isDispatched => status == 'dispatched';
  bool get isDelivered => status == 'delivered';
  bool get isCuppedApproved => status == 'cupped_approved';
  bool get isCuppedRejected => status == 'cupped_rejected';

  factory SampleOrderModel.fromJson(Map<String, dynamic> json) {
    return SampleOrderModel(
      id: (json['id'] as num?)?.toInt() ?? 0,
      sampleReference: json['sample_reference']?.toString() ?? '',
      listingId: (json['listing_id'] as num?)?.toInt() ?? 0,
      organizationId: (json['organization_id'] as num?)?.toInt() ?? 0,
      buyerId: (json['buyer_id'] as num?)?.toInt() ?? 0,
      status: json['status']?.toString() ?? 'requested',
      quantityGrams: (json['quantity_grams'] as num?)?.toInt() ?? 250,
      shippingAddress: json['shipping_address']?.toString() ?? '',
      courierName: json['courier_name']?.toString(),
      waybillNumber: json['waybill_number']?.toString(),
      retainedSampleSealNo: json['retained_sample_seal_no']?.toString(),
      retainedSampleSealHash: json['retained_sample_seal_hash']?.toString(),
      dispatchDate: json['dispatch_date']?.toString(),
      deliveryDate: json['delivery_date']?.toString(),
      cuppingScore: (json['cupping_score'] as num?)?.toDouble(),
      cuppingNotes: json['cupping_notes']?.toString(),
      labReportPath: json['lab_report_path']?.toString(),
      feeAmountUsd: (json['fee_amount_usd'] as num?)?.toDouble() ?? 0.0,
      paymentStatus: json['payment_status']?.toString() ?? 'unpaid',
      createdAt: json['created_at']?.toString(),
      listing: json['listing'] != null
          ? CoffeeListingModel.fromJson(json['listing'])
          : null,
      organization: json['organization'] != null
          ? OrganizationModel.fromJson(json['organization'])
          : null,
      buyer: json['buyer'] != null ? User.fromJson(json['buyer']) : null,
    );
  }
}

