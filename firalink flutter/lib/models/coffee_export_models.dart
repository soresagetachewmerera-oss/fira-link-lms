class CoffeeCalculationResult {
  final int quantityBags;
  final double bagWeightKg;
  final double netWeightKg;
  final double netWeightLbs;
  final double sellingPriceUsdPerLb;
  final double exchangeRate;
  final String incoterm;
  final String? destinationPort;
  final Map<String, dynamic> costBreakdownEtb;
  final Map<String, dynamic> costBreakdownUsd;
  final double grossRevenueUsd;
  final double grossRevenueEtb;
  final double netProfitEtb;
  final double netProfitUsd;
  final double marginPercentage;
  final double breakEvenPriceUsdPerLb;
  final double profitPerBagEtb;
  final double profitPerKgEtb;
  final bool isProfitable;

  CoffeeCalculationResult({
    required this.quantityBags,
    required this.bagWeightKg,
    required this.netWeightKg,
    required this.netWeightLbs,
    required this.sellingPriceUsdPerLb,
    required this.exchangeRate,
    required this.incoterm,
    this.destinationPort,
    required this.costBreakdownEtb,
    required this.costBreakdownUsd,
    required this.grossRevenueUsd,
    required this.grossRevenueEtb,
    required this.netProfitEtb,
    required this.netProfitUsd,
    required this.marginPercentage,
    required this.breakEvenPriceUsdPerLb,
    required this.profitPerBagEtb,
    required this.profitPerKgEtb,
    required this.isProfitable,
  });

  factory CoffeeCalculationResult.fromJson(Map<String, dynamic> json) {
    return CoffeeCalculationResult(
      quantityBags: (json['quantity_bags'] as num?)?.toInt() ?? 320,
      bagWeightKg: (json['bag_weight_kg'] as num?)?.toDouble() ?? 60.0,
      netWeightKg: (json['net_weight_kg'] as num?)?.toDouble() ?? 19200.0,
      netWeightLbs: (json['net_weight_lbs'] as num?)?.toDouble() ?? 42328.7,
      sellingPriceUsdPerLb:
          (json['selling_price_usd_per_lb'] as num?)?.toDouble() ?? 3.10,
      exchangeRate: (json['exchange_rate'] as num?)?.toDouble() ?? 140.0,
      incoterm: json['incoterm'] ?? 'FOB',
      destinationPort: json['destination_port'],
      costBreakdownEtb: Map<String, dynamic>.from(json['cost_breakdown_etb'] ?? {}),
      costBreakdownUsd: Map<String, dynamic>.from(json['cost_breakdown_usd'] ?? {}),
      grossRevenueUsd: (json['gross_revenue_usd'] as num?)?.toDouble() ?? 0.0,
      grossRevenueEtb: (json['gross_revenue_etb'] as num?)?.toDouble() ?? 0.0,
      netProfitEtb: (json['net_profit_etb'] as num?)?.toDouble() ?? 0.0,
      netProfitUsd: (json['net_profit_usd'] as num?)?.toDouble() ?? 0.0,
      marginPercentage: (json['margin_percentage'] as num?)?.toDouble() ?? 0.0,
      breakEvenPriceUsdPerLb:
          (json['break_even_price_usd_per_lb'] as num?)?.toDouble() ?? 0.0,
      profitPerBagEtb: (json['profit_per_bag_etb'] as num?)?.toDouble() ?? 0.0,
      profitPerKgEtb: (json['profit_per_kg_etb'] as num?)?.toDouble() ?? 0.0,
      isProfitable: json['is_profitable'] ?? false,
    );
  }
}

class SavedCoffeeCalculation {
  final int id;
  final String dealTitle;
  final String coffeeType;
  final int quantityBags;
  final double netWeightKg;
  final double sellingPriceUsdPerLb;
  final double exchangeRate;
  final double netProfitEtb;
  final double marginPercentage;
  final DateTime createdAt;

  SavedCoffeeCalculation({
    required this.id,
    required this.dealTitle,
    required this.coffeeType,
    required this.quantityBags,
    required this.netWeightKg,
    required this.sellingPriceUsdPerLb,
    required this.exchangeRate,
    required this.netProfitEtb,
    required this.marginPercentage,
    required this.createdAt,
  });

  factory SavedCoffeeCalculation.fromJson(Map<String, dynamic> json) {
    return SavedCoffeeCalculation(
      id: (json['id'] as num?)?.toInt() ?? 0,
      dealTitle: json['deal_title'] ?? 'Export Deal',
      coffeeType: json['coffee_type'] ?? 'Sidama Washed',
      quantityBags: (json['quantity_bags'] as num?)?.toInt() ?? 320,
      netWeightKg: (json['net_weight_kg'] as num?)?.toDouble() ?? 19200.0,
      sellingPriceUsdPerLb:
          (json['selling_price_usd_per_lb'] as num?)?.toDouble() ?? 3.10,
      exchangeRate: (json['exchange_rate'] as num?)?.toDouble() ?? 140.0,
      netProfitEtb: (json['net_profit_etb'] as num?)?.toDouble() ?? 0.0,
      marginPercentage: (json['margin_percentage'] as num?)?.toDouble() ?? 0.0,
      createdAt: json['created_at'] != null
          ? DateTime.parse(json['created_at'])
          : DateTime.now(),
    );
  }
}

class CoffeeOffer {
  final int id;
  final String referenceNo;
  final String exporterName;
  final String originRegion;
  final String? subRegion;
  final String? washingStation;
  final String grade;
  final String processingMethod;
  final double? cuppingScore;
  final int quantityBags;
  final double priceUsdPerLb;
  final String incoterm;
  final String paymentTerms;
  final DateTime validityDate;
  final String? tastingNotes;

  CoffeeOffer({
    required this.id,
    required this.referenceNo,
    required this.exporterName,
    required this.originRegion,
    this.subRegion,
    this.washingStation,
    required this.grade,
    required this.processingMethod,
    this.cuppingScore,
    required this.quantityBags,
    required this.priceUsdPerLb,
    required this.incoterm,
    required this.paymentTerms,
    required this.validityDate,
    this.tastingNotes,
  });

  factory CoffeeOffer.fromJson(Map<String, dynamic> json) {
    return CoffeeOffer(
      id: (json['id'] as num?)?.toInt() ?? 0,
      referenceNo: json['reference_no'] ?? '',
      exporterName: json['exporter_name'] ?? '',
      originRegion: json['origin_region'] ?? 'Sidama',
      subRegion: json['sub_region'],
      washingStation: json['washing_station'],
      grade: json['grade'] ?? 'Grade 2',
      processingMethod: json['processing_method'] ?? 'Washed',
      cuppingScore: (json['cupping_score'] as num?)?.toDouble(),
      quantityBags: (json['quantity_bags'] as num?)?.toInt() ?? 320,
      priceUsdPerLb: (json['price_usd_per_lb'] as num?)?.toDouble() ?? 3.20,
      incoterm: json['incoterm'] ?? 'FOB Djibouti',
      paymentTerms: json['payment_terms'] ?? '100% LC at Sight',
      validityDate: json['validity_date'] != null
          ? DateTime.parse(json['validity_date'])
          : DateTime.now(),
      tastingNotes: json['tasting_notes'],
    );
  }
}

class CoffeeMarketBenchmarkModel {
  final int id;
  final String benchmarkName;
  final String? gradeDescription;
  final double arabicaCCentsLb;
  final double differentialCentsLb;
  final double indicativeFobUsdLb;
  final double? ecxReferenceEtbPerKg;
  final String trend;
  final String source;

  CoffeeMarketBenchmarkModel({
    required this.id,
    required this.benchmarkName,
    this.gradeDescription,
    required this.arabicaCCentsLb,
    required this.differentialCentsLb,
    required this.indicativeFobUsdLb,
    this.ecxReferenceEtbPerKg,
    required this.trend,
    required this.source,
  });

  factory CoffeeMarketBenchmarkModel.fromJson(Map<String, dynamic> json) {
    return CoffeeMarketBenchmarkModel(
      id: (json['id'] as num?)?.toInt() ?? 0,
      benchmarkName: json['benchmark_name'] ?? '',
      gradeDescription: json['grade_description'],
      arabicaCCentsLb: (json['arabica_c_cents_lb'] as num?)?.toDouble() ?? 0.0,
      differentialCentsLb:
          (json['differential_cents_lb'] as num?)?.toDouble() ?? 0.0,
      indicativeFobUsdLb:
          (json['indicative_fob_usd_lb'] as num?)?.toDouble() ?? 0.0,
      ecxReferenceEtbPerKg:
          (json['ecx_reference_etb_per_kg'] as num?)?.toDouble(),
      trend: json['trend'] ?? 'neutral',
      source: json['source'] ?? 'ECTA Index',
    );
  }
}

class ActionCardModel {
  final String type;
  final String title;
  final String description;
  final String actionLabel;
  final String? url;
  final String? route;

  ActionCardModel({
    required this.type,
    required this.title,
    required this.description,
    required this.actionLabel,
    this.url,
    this.route,
  });

  factory ActionCardModel.fromJson(Map<String, dynamic> json) {
    return ActionCardModel(
      type: json['type'] ?? '',
      title: json['title'] ?? '',
      description: json['description'] ?? '',
      actionLabel: json['action_label'] ?? 'Open',
      url: json['url'],
      route: json['route'],
    );
  }
}

class CoffeePriceRecordModel {
  final int id;
  final String coffeeType;
  final String origin;
  final String grade;
  final String processingMethod;
  final double minimumPrice;
  final String currency;
  final String priceUnit;
  final double? priceChange;
  final String priceChangeDirection;
  final String exporterType;

  CoffeePriceRecordModel({
    required this.id,
    required this.coffeeType,
    required this.origin,
    required this.grade,
    required this.processingMethod,
    required this.minimumPrice,
    this.currency = 'USD',
    this.priceUnit = 'USD/lb',
    this.priceChange,
    this.priceChangeDirection = 'stable',
    this.exporterType = 'Commercial Exporter',
  });

  factory CoffeePriceRecordModel.fromJson(Map<String, dynamic> json) {
    return CoffeePriceRecordModel(
      id: (json['id'] as num?)?.toInt() ?? 0,
      coffeeType: json['coffee_type'] ?? 'Ethiopian Arabica',
      origin: json['origin'] ?? 'Ethiopia',
      grade: json['grade'] ?? 'G2',
      processingMethod: json['processing_method'] ?? 'Washed',
      minimumPrice: (json['minimum_price'] as num?)?.toDouble() ?? 0.0,
      currency: json['currency'] ?? 'USD',
      priceUnit: json['price_unit'] ?? 'USD/lb',
      priceChange: (json['price_change'] as num?)?.toDouble(),
      priceChangeDirection: json['price_change_direction'] ?? 'stable',
      exporterType: json['exporter_type'] ?? 'Commercial Exporter',
    );
  }
}

class CoffeeDatasetSnapshotModel {
  final int recordsAnalyzed;
  final double highestPrice;
  final String highestSpec;
  final String highestCoffee;
  final double lowestPrice;
  final String lowestSpec;
  final String lowestCoffee;
  final double averagePrice;
  final double medianPrice;
  final double priceSpread;
  final String currency;
  final String unit;
  final String effectiveDate;
  final String latestUpdate;

  CoffeeDatasetSnapshotModel({
    required this.recordsAnalyzed,
    required this.highestPrice,
    required this.highestSpec,
    required this.highestCoffee,
    required this.lowestPrice,
    required this.lowestSpec,
    required this.lowestCoffee,
    required this.averagePrice,
    required this.medianPrice,
    required this.priceSpread,
    required this.currency,
    required this.unit,
    required this.effectiveDate,
    required this.latestUpdate,
  });

  factory CoffeeDatasetSnapshotModel.fromJson(Map<String, dynamic> json) {
    return CoffeeDatasetSnapshotModel(
      recordsAnalyzed: (json['records_analyzed'] as num?)?.toInt() ?? 0,
      highestPrice: (json['highest_price'] as num?)?.toDouble() ?? 0.0,
      highestSpec: json['highest_spec'] ?? 'N/A',
      highestCoffee: json['highest_coffee'] ?? 'N/A',
      lowestPrice: (json['lowest_price'] as num?)?.toDouble() ?? 0.0,
      lowestSpec: json['lowest_spec'] ?? 'N/A',
      lowestCoffee: json['lowest_coffee'] ?? 'N/A',
      averagePrice: (json['average_price'] as num?)?.toDouble() ?? 0.0,
      medianPrice: (json['median_price'] as num?)?.toDouble() ?? 0.0,
      priceSpread: (json['price_spread'] as num?)?.toDouble() ?? 0.0,
      currency: json['currency'] ?? 'USD',
      unit: json['unit'] ?? 'USD/lb',
      effectiveDate: json['effective_date'] ?? '',
      latestUpdate: json['latest_update'] ?? '',
    );
  }
}

class MarketSynthesisModel {
  final String corridor;
  final String regulatoryStatus;
  final String mandatoryRule;
  final String executiveSummary;
  final Map<String, dynamic>? priceLeader;
  final Map<String, dynamic>? lowestReference;
  final Map<String, dynamic>? largestMovement;
  final Map<String, dynamic>? regionalSpread;
  final List<String> opportunitySignals;
  final List<String> riskSignals;

  MarketSynthesisModel({
    required this.corridor,
    required this.regulatoryStatus,
    required this.mandatoryRule,
    required this.executiveSummary,
    this.priceLeader,
    this.lowestReference,
    this.largestMovement,
    this.regionalSpread,
    required this.opportunitySignals,
    required this.riskSignals,
  });

  factory MarketSynthesisModel.fromJson(Map<String, dynamic> json) {
    final insights = json['insights'] as Map<String, dynamic>? ?? {};

    List<String> parseSignals(dynamic raw) {
      if (raw is List) {
        return raw.map((item) {
          if (item is Map && item['title'] != null) {
            return '${item['title']}: ${item['description'] ?? ''}';
          }
          return item.toString();
        }).toList();
      }
      return [];
    }

    return MarketSynthesisModel(
      // Only what the server sends: no invented price corridor or ECTA claims (gap analysis GAP-042).
      corridor: json['corridor']?.toString() ?? '',
      regulatoryStatus: json['regulatory_status']?.toString() ?? '',
      mandatoryRule: json['mandatory_rule']?.toString() ?? '',
      executiveSummary: (insights['executive_summary'] ?? json['executive_summary'])?.toString() ?? '',
      priceLeader: insights['price_leader'] as Map<String, dynamic>?,
      lowestReference: insights['lowest_reference'] as Map<String, dynamic>?,
      largestMovement: insights['largest_movement'] as Map<String, dynamic>?,
      regionalSpread: insights['regional_spread'] as Map<String, dynamic>?,
      opportunitySignals: parseSignals(json['opportunity_signals']),
      riskSignals: parseSignals(json['risk_signals']),
    );
  }
}

class CoffeeMarketIntelligenceResponse {
  final bool success;
  final Map<String, dynamic>? datasetMeta;
  final CoffeeDatasetSnapshotModel? snapshot;
  final MarketSynthesisModel? synthesis;
  final Map<String, dynamic>? macro;
  final List<String> origins;
  final List<CoffeePriceRecordModel> records;
  final List<CoffeeMarketBenchmarkModel> benchmarks;

  CoffeeMarketIntelligenceResponse({
    required this.success,
    this.datasetMeta,
    this.snapshot,
    this.synthesis,
    this.macro,
    required this.origins,
    required this.records,
    required this.benchmarks,
  });

  factory CoffeeMarketIntelligenceResponse.fromJson(Map<String, dynamic> json) {
    CoffeeDatasetSnapshotModel? snap;
    if (json['snapshot'] != null && json['snapshot'] is Map<String, dynamic>) {
      snap = CoffeeDatasetSnapshotModel.fromJson(json['snapshot']);
    }

    MarketSynthesisModel? synth;
    if (json['synthesis'] != null && json['synthesis'] is Map<String, dynamic>) {
      synth = MarketSynthesisModel.fromJson(json['synthesis']);
    }

    List<String> origs = [];
    if (json['origins'] != null && json['origins'] is List) {
      origs = (json['origins'] as List).map((e) => e.toString()).toList();
    }

    List<CoffeePriceRecordModel> recs = [];
    if (json['records'] != null && json['records'] is List) {
      recs = (json['records'] as List)
          .map((r) => CoffeePriceRecordModel.fromJson(r as Map<String, dynamic>))
          .toList();
    }

    List<CoffeeMarketBenchmarkModel> benchs = [];
    if (json['data'] != null && json['data'] is List) {
      benchs = (json['data'] as List)
          .map((b) => CoffeeMarketBenchmarkModel.fromJson(b as Map<String, dynamic>))
          .toList();
    }

    return CoffeeMarketIntelligenceResponse(
      success: json['success'] == true,
      datasetMeta: json['dataset'] as Map<String, dynamic>?,
      snapshot: snap,
      synthesis: synth,
      macro: json['macro'] as Map<String, dynamic>?,
      origins: origs,
      records: recs,
      benchmarks: benchs,
    );
  }
}

class CoffeeListingModel {
  final int id;
  final int sellerId;
  final int? organizationId;
  final String title;
  final String slug;
  final String originRegion;
  final String? subRegion;
  final String? washingStation;
  final String coffeeType;
  final String? variety;
  final String processingMethod;
  final String grade;
  final String? screenSize;
  final double? cuppingScore;
  final String evidenceLevel; // claimed, documented, lab_backed, inspected
  final String? evidenceDocumentPath;
  final double? moistureContent;
  final double? waterActivity;
  final String cropYear;
  final int totalQuantityBags;
  final int availableQuantityBags;
  final int minimumOrderBags;
  final String bagType;
  final String packagingType;
  final double bagWeightKg;
  final double indicativePriceUsdPerLb;
  final double indicativePriceUsdPerMt;
  final String incoterm;
  final String portOfLoading;
  final bool sampleAvailable;
  final double sampleCostUsd;
  final String? description;
  final String status;
  final String? expiresAt;
  final String? primaryImageUrl;

  CoffeeListingModel({
    required this.id,
    required this.sellerId,
    this.organizationId,
    required this.title,
    required this.slug,
    required this.originRegion,
    this.subRegion,
    this.washingStation,
    this.coffeeType = 'arabica',
    this.variety,
    required this.processingMethod,
    required this.grade,
    this.screenSize,
    this.cuppingScore,
    this.evidenceLevel = 'claimed',
    this.evidenceDocumentPath,
    this.moistureContent,
    this.waterActivity,
    this.cropYear = '2025/2026',
    required this.totalQuantityBags,
    required this.availableQuantityBags,
    this.minimumOrderBags = 10,
    this.bagType = 'jute_with_grainpro',
    this.packagingType = 'GrainPro + Jute',
    this.bagWeightKg = 60.0,
    required this.indicativePriceUsdPerLb,
    required this.indicativePriceUsdPerMt,
    this.incoterm = 'FOB Djibouti',
    this.portOfLoading = 'Djibouti',
    this.sampleAvailable = true,
    this.sampleCostUsd = 0.0,
    this.description,
    this.status = 'active',
    this.expiresAt,
    this.primaryImageUrl,
  });

  bool get isLabBacked => evidenceLevel == 'lab_backed';
  bool get isInspected => evidenceLevel == 'inspected';
  bool get isDocumented => evidenceLevel == 'documented';

  factory CoffeeListingModel.fromJson(Map<String, dynamic> json) {
    String? imgUrl;
    if (json['primary_image'] != null && json['primary_image']['file_path'] != null) {
      imgUrl = json['primary_image']['file_path'].toString();
    }

    return CoffeeListingModel(
      id: (json['id'] as num?)?.toInt() ?? 0,
      sellerId: (json['seller_id'] as num?)?.toInt() ?? 0,
      organizationId: (json['organization_id'] as num?)?.toInt(),
      title: json['title']?.toString() ?? '',
      slug: json['slug']?.toString() ?? '',
      originRegion: json['origin_region']?.toString() ?? '',
      subRegion: json['sub_region']?.toString(),
      washingStation: json['washing_station']?.toString(),
      coffeeType: json['coffee_type']?.toString() ?? 'arabica',
      variety: json['variety']?.toString(),
      processingMethod: json['processing_method']?.toString() ?? 'washed',
      grade: json['grade']?.toString() ?? 'grade_2',
      screenSize: json['screen_size']?.toString(),
      cuppingScore: (json['cupping_score'] as num?)?.toDouble(),
      evidenceLevel: json['evidence_level']?.toString() ?? 'claimed',
      evidenceDocumentPath: json['evidence_document_path']?.toString(),
      moistureContent: (json['moisture_content'] as num?)?.toDouble(),
      waterActivity: (json['water_activity'] as num?)?.toDouble(),
      cropYear: json['crop_year']?.toString() ?? '2025/2026',
      totalQuantityBags: (json['total_quantity_bags'] as num?)?.toInt() ?? 320,
      availableQuantityBags: (json['available_quantity_bags'] as num?)?.toInt() ?? 320,
      minimumOrderBags: (json['minimum_order_bags'] as num?)?.toInt() ?? 10,
      bagType: json['bag_type']?.toString() ?? 'jute_with_grainpro',
      packagingType: json['packaging_type']?.toString() ?? 'GrainPro + Jute',
      bagWeightKg: (json['bag_weight_kg'] as num?)?.toDouble() ?? 60.0,
      indicativePriceUsdPerLb: (json['indicative_price_usd_per_lb'] as num?)?.toDouble() ?? 3.20,
      indicativePriceUsdPerMt: (json['indicative_price_usd_per_mt'] as num?)?.toDouble() ?? 7054.79,
      incoterm: json['incoterm']?.toString() ?? 'FOB Djibouti',
      portOfLoading: json['port_of_loading']?.toString() ?? 'Djibouti',
      sampleAvailable: json['sample_available'] == true || json['sample_available'] == 1,
      sampleCostUsd: (json['sample_cost_usd'] as num?)?.toDouble() ?? 0.0,
      description: json['description']?.toString(),
      status: json['status']?.toString() ?? 'active',
      expiresAt: json['expires_at']?.toString(),
      primaryImageUrl: imgUrl,
    );
  }
}

