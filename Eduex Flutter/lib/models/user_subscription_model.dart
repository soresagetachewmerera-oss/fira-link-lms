class IncludedItemModel {
  final int id;
  final String title;
  final String? thumbnailUrl;
  final bool isEnrolled;

  IncludedItemModel({
    required this.id,
    required this.title,
    this.thumbnailUrl,
    this.isEnrolled = false,
  });

  factory IncludedItemModel.fromJson(Map<String, dynamic> json) {
    return IncludedItemModel(
      id: json['id'] is int ? json['id'] : int.parse(json['id'].toString()),
      title: json['title'] ?? '',
      thumbnailUrl: json['thumbnail_url'],
      isEnrolled: json['is_enrolled'] == true || json['is_enrolled'] == 1,
    );
  }
}

class UserSubscriptionModel {
  final int id;
  final int? planId;
  final String planName;
  final String? billingPeriod;
  final DateTime? startsAt;
  final DateTime? endsAt;
  final int remainingDays;
  final bool isLifetime;
  final String status;
  final List<IncludedItemModel> includedCourses;
  final List<IncludedItemModel> includedBundles;

  UserSubscriptionModel({
    required this.id,
    this.planId,
    required this.planName,
    this.billingPeriod,
    this.startsAt,
    this.endsAt,
    required this.remainingDays,
    required this.isLifetime,
    required this.status,
    required this.includedCourses,
    required this.includedBundles,
  });

  factory UserSubscriptionModel.fromJson(Map<String, dynamic> json) {
    List<IncludedItemModel> courses = [];
    if (json['included_courses'] != null && json['included_courses'] is List) {
      courses = (json['included_courses'] as List)
          .map((e) => IncludedItemModel.fromJson(e))
          .toList();
    }

    List<IncludedItemModel> bundles = [];
    if (json['included_bundles'] != null && json['included_bundles'] is List) {
      bundles = (json['included_bundles'] as List)
          .map((e) => IncludedItemModel.fromJson(e))
          .toList();
    }

    return UserSubscriptionModel(
      id: json['id'] is int ? json['id'] : int.parse(json['id'].toString()),
      planId: json['plan_id'] != null ? int.tryParse(json['plan_id'].toString()) : null,
      planName: json['plan_name'] ?? 'Subscription',
      billingPeriod: json['billing_period'],
      startsAt: json['starts_at'] != null ? DateTime.tryParse(json['starts_at']) : null,
      endsAt: json['ends_at'] != null ? DateTime.tryParse(json['ends_at']) : null,
      remainingDays: json['remaining_days'] ?? 0,
      isLifetime: json['is_lifetime'] == true || json['is_lifetime'] == 1,
      status: json['status'] ?? 'active',
      includedCourses: courses,
      includedBundles: bundles,
    );
  }
}
