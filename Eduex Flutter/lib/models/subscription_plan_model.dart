class SubscriptionPlanModel {
  final int id;
  final String name;
  final String? slug;
  final String? description;
  final double price;
  final double? discountPrice;
  final double effectivePrice;
  final String formattedPrice;
  final String currencyCode;
  final String currencySymbol;
  final String billingPeriod;
  final int durationDays;
  final int? courseLimit;
  final List<String> features;
  final bool isFeatured;
  final int includedCoursesCount;
  final int includedBundlesCount;

  SubscriptionPlanModel({
    required this.id,
    required this.name,
    this.slug,
    this.description,
    required this.price,
    this.discountPrice,
    required this.effectivePrice,
    required this.formattedPrice,
    required this.currencyCode,
    required this.currencySymbol,
    required this.billingPeriod,
    required this.durationDays,
    this.courseLimit,
    required this.features,
    required this.isFeatured,
    required this.includedCoursesCount,
    required this.includedBundlesCount,
  });

  factory SubscriptionPlanModel.fromJson(Map<String, dynamic> json) {
    List<String> parsedFeatures = [];
    if (json['features'] != null) {
      if (json['features'] is List) {
        parsedFeatures = (json['features'] as List).map((e) => e.toString()).toList();
      } else if (json['features'] is String) {
        parsedFeatures = [json['features'].toString()];
      }
    }

    return SubscriptionPlanModel(
      id: json['id'] is int ? json['id'] : int.parse(json['id'].toString()),
      name: json['name'] ?? '',
      slug: json['slug'],
      description: json['description'],
      price: (json['price'] ?? 0).toDouble(),
      discountPrice: json['discount_price'] != null ? (json['discount_price']).toDouble() : null,
      effectivePrice: (json['effective_price'] ?? 0).toDouble(),
      formattedPrice: json['formatted_price'] ?? '',
      currencyCode: json['currency_code'] ?? 'USD',
      currencySymbol: json['currency_symbol'] ?? '\$',
      billingPeriod: json['billing_period'] ?? 'monthly',
      durationDays: json['duration_days'] is int ? json['duration_days'] : int.parse((json['duration_days'] ?? 30).toString()),
      courseLimit: json['course_limit'] != null ? int.tryParse(json['course_limit'].toString()) : null,
      features: parsedFeatures,
      isFeatured: json['is_featured'] == true || json['is_featured'] == 1,
      includedCoursesCount: json['included_courses_count'] ?? 0,
      includedBundlesCount: json['included_bundles_count'] ?? 0,
    );
  }
}
