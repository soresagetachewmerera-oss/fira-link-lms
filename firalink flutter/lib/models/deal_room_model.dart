import 'trade_model.dart';

double? _d(dynamic v) => v == null ? null : double.tryParse(v.toString());
int? _i(dynamic v) => v == null ? null : int.tryParse(v.toString().split('.').first);
DateTime? _dt(dynamic v) => v == null ? null : DateTime.tryParse(v.toString())?.toLocal();

/// A deal room as the server sends it (slice 7): both sides are businesses only, six steps in order,
/// the deposit and balance amounts, and the seller's masked payout account under "Pay to".
class DealRoomModel {
  DealRoomModel({
    required this.id,
    required this.dealReference,
    required this.title,
    required this.targetValueUsd,
    this.incoterm = 'FOB',
    required this.status,
    this.depositPercent = 30,
    this.depositUsd = 0,
    this.balanceUsd = 0,
    this.paymentMethod,
    this.paused = false,
    this.pauseReason,
    this.mySide,
    this.buyer,
    this.seller,
    this.lot,
    this.buyerSigned = false,
    this.sellerSigned = false,
    this.stepNumber = 1,
    this.stepCount = 6,
    this.currentStep,
    this.milestones = const [],
    this.documents,
    this.payTo,
    this.conversationId,
    this.activity = const [],
    this.createdAt,
  });

  final int id;
  final String dealReference;
  final String title;
  final double targetValueUsd;
  final String incoterm;

  /// active, under_contract, completed, cancelled, disputed
  final String status;
  final int depositPercent;
  final double depositUsd;
  final double balanceUsd;
  final String? paymentMethod;
  final bool paused;
  final String? pauseReason;

  /// buyer, seller, or null (admin viewing).
  final String? mySide;
  final BusinessSummary? buyer;
  final BusinessSummary? seller;
  final DealLot? lot;
  final bool buyerSigned;
  final bool sellerSigned;
  final int stepNumber;
  final int stepCount;
  final String? currentStep;
  final List<DealMilestoneModel> milestones;
  final DealDocuments? documents;
  final PayTo? payTo;
  final int? conversationId;
  final List<DealActivity> activity;
  final DateTime? createdAt;

  bool get isUnderContract => status == 'under_contract';
  bool get isCompleted => status == 'completed';
  bool get isDisputed => status == 'disputed';
  bool get isFullySigned => buyerSigned && sellerSigned;
  bool get isBuyer => mySide == 'buyer';
  bool get isSeller => mySide == 'seller';
  bool get iSigned => isBuyer ? buyerSigned : isSeller && sellerSigned;

  /// Kept for older screens that check signatures by null.
  String? get buyerSignedAt => buyerSigned ? 'signed' : null;
  String? get sellerSignedAt => sellerSigned ? 'signed' : null;

  DealMilestoneModel? get current => milestones.where((m) => m.key == currentStep).firstOrNull;

  DealMilestoneModel? step(String key) => milestones.where((m) => m.key == key).firstOrNull;

  factory DealRoomModel.fromJson(Map<String, dynamic> j) => DealRoomModel(
        id: _i(j['id']) ?? 0,
        dealReference: j['deal_reference']?.toString() ?? '',
        title: j['title']?.toString() ?? '',
        targetValueUsd: _d(j['target_value_usd']) ?? 0,
        incoterm: j['incoterm']?.toString() ?? 'FOB',
        status: j['status']?.toString() ?? 'active',
        depositPercent: _i(j['deposit_percent']) ?? 30,
        depositUsd: _d(j['deposit_usd']) ?? 0,
        balanceUsd: _d(j['balance_usd']) ?? 0,
        paymentMethod: j['payment_method']?.toString(),
        paused: j['paused'] == true,
        pauseReason: j['pause_reason']?.toString(),
        mySide: j['my_side']?.toString(),
        buyer: BusinessSummary.fromJson(j['buyer']),
        seller: BusinessSummary.fromJson(j['seller']),
        lot: j['lot'] is Map ? DealLot.fromJson(Map<String, dynamic>.from(j['lot'] as Map)) : null,
        buyerSigned: j['buyer_signed'] == true,
        sellerSigned: j['seller_signed'] == true,
        stepNumber: _i(j['step_number']) ?? 1,
        stepCount: _i(j['step_count']) ?? 6,
        currentStep: j['current_step']?.toString(),
        milestones: (j['milestones'] as List? ?? [])
            .whereType<Map>()
            .map((m) => DealMilestoneModel.fromJson(Map<String, dynamic>.from(m)))
            .toList(),
        documents: j['documents'] is Map ? DealDocuments.fromJson(Map<String, dynamic>.from(j['documents'] as Map)) : null,
        payTo: j['pay_to'] is Map ? PayTo.fromJson(Map<String, dynamic>.from(j['pay_to'] as Map)) : null,
        conversationId: _i(j['conversation_id']),
        activity: (j['activity'] as List? ?? []).whereType<Map>().map((a) => DealActivity.fromJson(Map<String, dynamic>.from(a))).toList(),
        createdAt: _dt(j['created_at']),
      );
}

class DealLot {
  const DealLot({required this.id, required this.title, this.lotCode, this.pricePerLb});

  final int id;
  final String title;
  final String? lotCode;
  final double? pricePerLb;

  factory DealLot.fromJson(Map<String, dynamic> j) =>
      DealLot(id: _i(j['id']) ?? 0, title: j['title']?.toString() ?? '', lotCode: j['lot_code']?.toString(), pricePerLb: _d(j['price_per_lb']));
}

/// One step. [side]: buyer, seller or both. [money]: deposit, balance or null.
class DealMilestoneModel {
  DealMilestoneModel({
    required this.id,
    required this.key,
    required this.title,
    required this.status,
    this.side = 'both',
    this.money,
    this.amountUsd,
    this.hasProof = false,
    this.submittedAt,
    this.confirmedAt,
    this.locked = false,
  });

  final int id;
  final String key;
  final String title;

  /// pending, in_progress, proof_submitted, achieved, waived
  final String status;
  final String side;
  final String? money;
  final double? amountUsd;
  final bool hasProof;
  final DateTime? submittedAt;
  final DateTime? confirmedAt;
  final bool locked;

  String? get milestoneKey => key;
  bool get isDone => status == 'achieved' || status == 'waived';
  bool get isSubmitted => status == 'proof_submitted';
  bool get isAchieved => isDone;
  bool get isPending => status == 'pending';

  factory DealMilestoneModel.fromJson(Map<String, dynamic> j) => DealMilestoneModel(
        id: _i(j['id']) ?? 0,
        key: (j['key'] ?? j['milestone_key'])?.toString() ?? '',
        title: j['title']?.toString() ?? '',
        status: j['status']?.toString() ?? 'pending',
        side: j['side']?.toString() ?? 'both',
        money: j['money']?.toString(),
        amountUsd: _d(j['amount_usd']),
        hasProof: j['has_proof'] == true,
        submittedAt: _dt(j['submitted_at']),
        confirmedAt: _dt(j['confirmed_at']),
        locked: j['locked'] == true,
      );
}

class DealDocuments {
  const DealDocuments({this.contractSigned = false, this.hasContractFile = false, this.sampleSealId});

  final bool contractSigned;
  final bool hasContractFile;
  final String? sampleSealId;

  factory DealDocuments.fromJson(Map<String, dynamic> j) => DealDocuments(
        contractSigned: j['contract_signed'] == true,
        hasContractFile: j['has_contract_file'] == true,
        sampleSealId: j['sample_seal_id']?.toString(),
      );
}

/// What the buyer pays to. [state]: ready, paused (bank details changed, 48 hours),
/// pending_review, missing. Never the full account number.
class PayTo {
  const PayTo({required this.state, this.business, this.bankName, this.last4, this.matchesVerified = false, this.pausedUntil});

  final String state;
  final String? business;
  final String? bankName;
  final String? last4;
  final bool matchesVerified;
  final DateTime? pausedUntil;

  bool get ready => state == 'ready';

  factory PayTo.fromJson(Map<String, dynamic> j) => PayTo(
        state: j['state']?.toString() ?? 'missing',
        business: j['business']?.toString(),
        bankName: j['bank_name']?.toString(),
        last4: j['account_last4']?.toString(),
        matchesVerified: j['matches_verified'] == true,
        pausedUntil: _dt(j['paused_until']),
      );
}

class DealActivity {
  const DealActivity({required this.action, this.step, this.status, this.at});

  final String action;
  final String? step;
  final String? status;
  final DateTime? at;

  factory DealActivity.fromJson(Map<String, dynamic> j) =>
      DealActivity(action: j['action']?.toString() ?? '', step: j['step']?.toString(), status: j['status']?.toString(), at: _dt(j['at']));
}

/// The seller owner's own payout account view (masked).
class PayoutAccountView {
  const PayoutAccountView({
    required this.id,
    required this.bankName,
    required this.accountName,
    required this.last4,
    required this.status,
    this.isChange = false,
    this.activeFrom,
    this.reviewNote,
  });

  final int id;
  final String bankName;
  final String accountName;
  final String last4;

  /// pending, confirmed, rejected
  final String status;
  final bool isChange;
  final DateTime? activeFrom;
  final String? reviewNote;

  bool get inPause => activeFrom != null && activeFrom!.isAfter(DateTime.now());

  static PayoutAccountView? fromJson(dynamic j) {
    if (j is! Map) return null;
    return PayoutAccountView(
      id: _i(j['id']) ?? 0,
      bankName: j['bank_name']?.toString() ?? '',
      accountName: j['account_name']?.toString() ?? '',
      last4: j['account_last4']?.toString() ?? '',
      status: j['status']?.toString() ?? 'pending',
      isChange: j['is_change'] == true,
      activeFrom: _dt(j['active_from']),
      reviewNote: j['review_note']?.toString(),
    );
  }
}
