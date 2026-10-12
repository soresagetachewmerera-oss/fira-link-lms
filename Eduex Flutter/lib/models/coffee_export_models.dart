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
