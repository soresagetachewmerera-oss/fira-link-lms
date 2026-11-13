import 'banner.dart';
import 'category.dart';
import 'course.dart';
import 'live_class.dart';
import '../screens/home/models/continue_learning_data.dart';
import '../screens/home/models/market_data.dart';

class HomeData {
  final List<BannerModel> banners;
  final List<Category> categories;
  final List<Course> popularCourses;
  final List<Course> aiSuggestions;
  final List<Course> recentCourses;
  final LiveClass? upcomingLiveClass;
  final ContinueLearningCourse? continueLearning;
  final List<MarketChipItem> marketItems;
  final HomeUserContext userContext;

  HomeData({
    required this.banners,
    required this.categories,
    required this.popularCourses,
    required this.aiSuggestions,
    required this.recentCourses,
    this.upcomingLiveClass,
    this.continueLearning,
    required this.marketItems,
    required this.userContext,
  });

  factory HomeData.fromJson(Map<String, dynamic> json) {
    final data = json['data'];
    return HomeData(
      banners:
          (data['banners'] as List?)
              ?.map((x) => BannerModel.fromJson(x))
              .toList() ??
          [],
      categories:
          (data['categories'] as List?)
              ?.map((x) => Category.fromJson(x))
              .toList() ??
          [],
      popularCourses:
          (data['popular_courses'] as List?)
              ?.map((x) => Course.fromJson(x))
              .toList() ??
          [],
      aiSuggestions:
          (data['ai_suggestions'] as List?)
              ?.map((x) => Course.fromJson(x))
              .toList() ??
          [],
      recentCourses:
          (data['recent_courses'] as List?)
              ?.map((x) => Course.fromJson(x))
              .toList() ??
          [],
      upcomingLiveClass: data['upcoming_live_class'] != null
          ? LiveClass.fromJson(data['upcoming_live_class'])
          : null,
      continueLearning: data['continue_learning'] is Map<String, dynamic>
          ? ContinueLearningCourse.fromJson(data['continue_learning'])
          : null,
      marketItems: (data['market_items'] as List<dynamic>? ?? const [])
          .whereType<Map<String, dynamic>>()
          .map(MarketChipItem.fromJson)
          .where((item) => item.label.isNotEmpty && item.value.isNotEmpty)
          .toList(),
      userContext: HomeUserContext.fromJson(
        data['user_context'] is Map<String, dynamic>
            ? data['user_context']
            : const <String, dynamic>{},
      ),
    );
  }
}

class HomeUserContext {
  const HomeUserContext({
    required this.isAuthenticated,
    required this.role,
    required this.persona,
    required this.capabilities,
  });

  final bool isAuthenticated;
  final String role;
  final String persona;
  final Map<String, bool> capabilities;

  factory HomeUserContext.fromJson(Map<String, dynamic> json) {
    final rawCapabilities = json['capabilities'];
    return HomeUserContext(
      isAuthenticated: json['authenticated'] == true,
      role: json['role']?.toString() ?? 'visitor',
      persona:
          json['persona']?.toString() ?? json['role']?.toString() ?? 'visitor',
      capabilities: rawCapabilities is Map
          ? rawCapabilities.map(
              (key, value) => MapEntry(key.toString(), value == true),
            )
          : const {},
    );
  }

  bool can(String capability) => capabilities[capability] == true;
}
