import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/rendering.dart';

import '../../theme/app_theme.dart';
import '../../l10n/app_localizations.dart';
import '../../widgets/bottom_nav_bar.dart';
import '../../widgets/fira_bottom_dock.dart';
import '../../services/home_service.dart';
import '../../services/course_service.dart';
import '../../models/home_data.dart';
import '../../models/course.dart';
import '../../models/bundle.dart';
import '../courses/courses_screen.dart';
import '../bundles/bundles_screen.dart';
import '../bundles/bundle_detail_screen.dart';
import '../../widgets/home_skeletons.dart';
import 'widgets/home_design_constants.dart';
import 'widgets/home_header.dart';
import 'widgets/home_search_bar.dart';
import 'widgets/continue_learning_card.dart';
import 'widgets/market_strip.dart';
import 'widgets/toolkit_bento.dart';
import 'widgets/topic_chips.dart';
import 'widgets/featured_course_card.dart';
import 'models/market_data.dart';
import 'models/continue_learning_data.dart';
import 'repositories/market_data_repository.dart';
import 'repositories/continue_learning_repository.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  final ScrollController _scrollController = ScrollController();
  final TextEditingController _searchController = TextEditingController();
  final HomeService _homeService = HomeService();
  final CourseService _courseService = CourseService();
  final MarketDataRepository _marketRepo = const MockMarketDataRepository();
  final ContinueLearningRepository _continueLearningRepo = const MockContinueLearningRepository();

  HomeData? _homeData;
  List<Course> _recentCourses = [];
  List<Bundle> _bundles = [];
  List<MarketChipItem> _marketItems = [];
  ContinueLearningCourse? _inProgressCourse;

  bool _isLoading = true;
  String? _error;
  String _selectedTopic = 'All';

  double _savedScrollPosition = 0.0;
  double _lastScrollOffset = 0.0;

  @override
  void initState() {
    super.initState();
    _loadAllData();
  }

  @override
  void dispose() {
    _scrollController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadAllData() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      // 1. Fetch Market strip data (Mock/Live repository with TODO)
      final marketFuture = _marketRepo.getMarketStripItems();

      // 2. Fetch in-progress course (Mock/Live repository with TODO)
      final continueLearningFuture = _continueLearningRepo.getActiveInProgressCourse();

      // 3. Fetch Home data
      final homeResponse = await _homeService.fetchHomeData();

      final marketResult = await marketFuture;
      final continueLearningResult = await continueLearningFuture;

      if (homeResponse.statusCode == 200) {
        final jsonResponse = jsonDecode(homeResponse.body);
        if (jsonResponse['success'] == true) {
          if (!mounted) return;
          setState(() {
            _homeData = HomeData.fromJson(jsonResponse);
            _recentCourses = _homeData?.recentCourses ?? [];
            _marketItems = marketResult;
            _inProgressCourse = continueLearningResult;
          });

          // Fetch bundles asynchronously
          try {
            final bundleResponse = await _courseService.fetchBundles();
            if (bundleResponse.statusCode == 200) {
              final bData = jsonDecode(bundleResponse.body);
              if (bData['success'] == true && bData['data'] != null && mounted) {
                setState(() {
                  _bundles = (bData['data'] as List)
                      .map((b) => Bundle.fromJson(b))
                      .toList();
                });
              }
            }
          } catch (_) {
            // Non-critical bundle fetch error ignored
          }

          if (mounted) {
            setState(() {
              _isLoading = false;
            });
          }
        } else {
          _handleError('Failed to load platform data');
        }
      } else {
        _handleError('Server responded with code: ${homeResponse.statusCode}');
      }
    } catch (e) {
      _handleError('Connection error: $e');
    }
  }

  void _handleError(String message) {
    if (!mounted) return;
    setState(() {
      _error = message;
      _isLoading = false;
    });
  }

  Future<void> _onRefresh() async {
    if (_scrollController.hasClients) {
      _savedScrollPosition = _scrollController.offset;
    }

    await _loadAllData();

    if (mounted && _scrollController.hasClients) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted && _scrollController.hasClients && _savedScrollPosition > 0) {
          _scrollController.jumpTo(
            _savedScrollPosition.clamp(0.0, _scrollController.position.maxScrollExtent),
          );
        }
      });
    }
  }

  void _handleSearch(String query) {
    if (query.trim().isEmpty) return;
    Navigator.of(context).push(
      PageRouteBuilder(
        pageBuilder: (context, animation, secondaryAnimation) =>
            CoursesScreen(initialSearchQuery: query),
        transitionsBuilder: (context, animation, secondaryAnimation, child) {
          return FadeTransition(opacity: animation, child: child);
        },
        transitionDuration: const Duration(milliseconds: 200),
      ),
    );
  }

  void _handleTopicSelected(String topic) {
    setState(() {
      _selectedTopic = topic;
    });
  }

  List<Course> _getFilteredCourses(List<Course> allCourses) {
    if (_selectedTopic == 'All') return allCourses;

    final String topic = _selectedTopic.toLowerCase();
    final filtered = allCourses.where((c) {
      final String title = c.title.toLowerCase();
      final String desc = (c.description ?? '').toLowerCase();
      final String cat = (c.categoryName ?? '').toLowerCase();

      switch (_selectedTopic) {
        case 'Sourcing':
          return title.contains('sourc') || title.contains('origin') || title.contains('green') || cat.contains('sourc') || cat.contains('coffee');
        case 'Cupping & quality':
          return title.contains('cup') || title.contains('qualit') || title.contains('grad') || title.contains('sca') || desc.contains('cup');
        case 'Export docs':
          return title.contains('doc') || title.contains('custom') || title.contains('complian') || title.contains('contract') || title.contains('export');
        case 'Logistics':
          return title.contains('logist') || title.contains('freight') || title.contains('supply') || title.contains('ship') || title.contains('transport');
        case 'Finance':
          return title.contains('financ') || title.contains('fob') || title.contains('cif') || title.contains('price') || title.contains('forex');
        case 'Buyers':
          return title.contains('buyer') || title.contains('roaster') || title.contains('trade') || title.contains('market');
        default:
          return title.contains(topic) || cat.contains(topic);
      }
    }).toList();

    return filtered.isNotEmpty ? filtered : allCourses;
  }

  bool _handleScrollNotification(UserScrollNotification notification) {
    final double currentOffset = _scrollController.hasClients ? _scrollController.offset : 0.0;

    // Always show dock at the very top
    if (currentOffset <= 80.0) {
      if (!firaDockVisibilityNotifier.value) {
        firaDockVisibilityNotifier.value = true;
      }
      _lastScrollOffset = currentOffset;
      return false;
    }

    final double delta = currentOffset - _lastScrollOffset;

    // Scrolling down by more than 8px past 80px -> hide dock
    if (notification.direction == ScrollDirection.reverse && delta > 8.0) {
      if (firaDockVisibilityNotifier.value) {
        firaDockVisibilityNotifier.value = false;
      }
    }
    // Scrolling up -> show dock
    else if (notification.direction == ScrollDirection.forward && delta < -8.0) {
      if (!firaDockVisibilityNotifier.value) {
        firaDockVisibilityNotifier.value = true;
      }
    }

    _lastScrollOffset = currentOffset;
    return false;
  }

  @override
  Widget build(BuildContext context) {
    final bool isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      backgroundColor: AppTheme.getBackgroundColor(context),
      extendBody: true, // Allows content to flow smoothly under floating dock
      body: Container(
        decoration: isDark
            ? null
            : const BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [
                    Color(0xFFFCFBFE), // Subtle top-to-bottom tint in light mode
                    Color(0xFFF9F7FA),
                  ],
                ),
              ),
        child: SafeArea(
          bottom: false,
          child: NotificationListener<UserScrollNotification>(
            onNotification: _handleScrollNotification,
            child: RefreshIndicator(
              onRefresh: _onRefresh,
              color: HomeDesignConstants.brandPrimary(context),
              displacement: 40,
              child: _isLoading
                  ? const HomeSkeletons()
                  : _error != null
                      ? _buildErrorState()
                      : _buildMainContent(),
            ),
          ),
        ),
      ),
      bottomNavigationBar: const AppBottomNavBar(currentTab: BottomNavTab.home),
    );
  }

  Widget _buildErrorState() {
    return SingleChildScrollView(
      physics: const AlwaysScrollableScrollPhysics(),
      child: Container(
        height: MediaQuery.of(context).size.height * 0.6,
        alignment: Alignment.center,
        padding: const EdgeInsets.symmetric(horizontal: 40),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: <Widget>[
            Icon(
              Icons.error_outline_rounded,
              size: 48,
              color: Colors.red.withValues(alpha: 0.7),
            ),
            const SizedBox(height: 16),
            Text(
              'Unable to load trade content',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w700,
                color: HomeDesignConstants.text(context),
              ),
            ),
            const SizedBox(height: 8),
            Text(
              _error ?? 'An unexpected error occurred.',
              style: TextStyle(
                fontSize: 13,
                color: HomeDesignConstants.mutedText(context),
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 20),
            ElevatedButton(
              onPressed: _onRefresh,
              style: ElevatedButton.styleFrom(
                backgroundColor: HomeDesignConstants.brandPrimary(context),
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
              child: const Text('Try Again'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildMainContent() {
    final List<Course> allFeatured = _homeData?.popularCourses.isNotEmpty == true
        ? _homeData!.popularCourses
        : _recentCourses;
    final List<Course> filteredFeatured = _getFilteredCourses(allFeatured);

    return SingleChildScrollView(
      controller: _scrollController,
      physics: const AlwaysScrollableScrollPhysics(
        parent: BouncingScrollPhysics(),
      ),
      padding: const EdgeInsets.only(
        top: 10,
        bottom: HomeDesignConstants.bottomDockClearance, // Clear 140dp floating dock
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          // 1. Compact Header (Single row)
          const HomeHeader(),
          const SizedBox(height: 16),

          // 2. Search Bar (Radius 20, soft purple shadow, circular button)
          HomeSearchBar(
            controller: _searchController,
            onSearch: _handleSearch,
          ),
          const SizedBox(height: 16),

          // 3. Continue Learning Card (Shown only if in progress)
          if (_inProgressCourse != null) ...[
            ContinueLearningCard(course: _inProgressCourse),
            const SizedBox(height: 16),
          ],

          // 4. Market Strip (Horizontal scroll chips with ICE Arabica, USD/ETB, etc.)
          MarketStrip(items: _marketItems),
          const SizedBox(height: 18),

          // 5. Export Toolkit (Bento Grid: Hero with unchanged PRO badge + 4 tiles)
          const ToolkitBento(),
          const SizedBox(height: 24),

          // 6. Topic Chips (All, Sourcing, Cupping, Export docs, Logistics, Finance, Buyers)
          TopicChips(
            selectedTopic: _selectedTopic,
            onTopicSelected: _handleTopicSelected,
          ),
          const SizedBox(height: 16),

          // 7. Featured Training Course Cards (Horizontal list, snap scrolling)
          Padding(
            padding: const EdgeInsets.symmetric(
              horizontal: HomeDesignConstants.horizontalPadding,
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Featured Courses',
                  style: TextStyle(
                    color: HomeDesignConstants.text(context),
                    fontSize: 18,
                    fontWeight: FontWeight.w800,
                    letterSpacing: -0.3,
                  ),
                ),
                InkWell(
                  onTap: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => const CoursesScreen(),
                      ),
                    );
                  },
                  child: Text(
                    context.l10n.seeAll,
                    style: TextStyle(
                      color: HomeDesignConstants.brandPrimary(context),
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),

          // Horizontal Snap Scroll List
          SizedBox(
            height: 295,
            child: ListView.separated(
              padding: const EdgeInsets.symmetric(
                horizontal: HomeDesignConstants.horizontalPadding,
              ),
              scrollDirection: Axis.horizontal,
              physics: const BouncingScrollPhysics(),
              itemCount: filteredFeatured.length,
              separatorBuilder: (_, _) => const SizedBox(width: 14),
              itemBuilder: (context, index) {
                final course = filteredFeatured[index];
                return FeaturedCourseCard(
                  course: course,
                  width: 236.0,
                );
              },
            ),
          ),

          // Course Bundles Section (if available)
          if (_bundles.isNotEmpty) ...[
            const SizedBox(height: 28),
            Padding(
              padding: const EdgeInsets.symmetric(
                horizontal: HomeDesignConstants.horizontalPadding,
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Curated Master Bundles',
                    style: TextStyle(
                      color: HomeDesignConstants.text(context),
                      fontSize: 18,
                      fontWeight: FontWeight.w800,
                      letterSpacing: -0.3,
                    ),
                  ),
                  InkWell(
                    onTap: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) => const BundlesScreen(),
                        ),
                      );
                    },
                    child: Text(
                      context.l10n.seeAll,
                      style: TextStyle(
                        color: HomeDesignConstants.brandPrimary(context),
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            SizedBox(
              height: 190,
              child: ListView.separated(
                padding: const EdgeInsets.symmetric(
                  horizontal: HomeDesignConstants.horizontalPadding,
                ),
                scrollDirection: Axis.horizontal,
                physics: const BouncingScrollPhysics(),
                itemCount: _bundles.length,
                separatorBuilder: (_, _) => const SizedBox(width: 14),
                itemBuilder: (context, index) {
                  final bundle = _bundles[index];
                  return _buildBundleCard(bundle);
                },
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildBundleCard(Bundle bundle) {
    final int courseCount = bundle.courses?.length ?? 0;
    final Color cardBg = HomeDesignConstants.surface(context);
    final Color textColor = HomeDesignConstants.text(context);
    final Color primaryColor = HomeDesignConstants.brandPrimary(context);

    return InkWell(
      onTap: () {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) => BundleDetailScreen(bundle: bundle),
          ),
        );
      },
      borderRadius: BorderRadius.circular(HomeDesignConstants.radiusTile),
      child: Container(
        width: 210,
        decoration: BoxDecoration(
          color: cardBg,
          borderRadius: BorderRadius.circular(HomeDesignConstants.radiusTile),
          boxShadow: HomeDesignConstants.softShadow(context),
          border: Border.all(
            color: HomeDesignConstants.brandTint(context),
            width: 1.0,
          ),
        ),
        clipBehavior: Clip.antiAlias,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Stack(
              children: [
                bundle.image != null
                    ? Image.network(
                        bundle.image!,
                        height: 105,
                        width: double.infinity,
                        fit: BoxFit.cover,
                        errorBuilder: (_, _, _) => Container(
                          height: 105,
                          color: HomeDesignConstants.brandTint(context),
                          child: Icon(Icons.layers_outlined, color: primaryColor, size: 28),
                        ),
                      )
                    : Container(
                        height: 105,
                        width: double.infinity,
                        color: HomeDesignConstants.brandTint(context),
                        child: Icon(Icons.layers_outlined, color: primaryColor, size: 28),
                      ),
                if (courseCount > 0)
                  Positioned(
                    top: 8,
                    left: 8,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: primaryColor,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        '$courseCount Courses',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 9.5,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ),
              ],
            ),
            Padding(
              padding: const EdgeInsets.all(10),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    bundle.title,
                    style: TextStyle(
                      color: textColor,
                      fontSize: 12.5,
                      fontWeight: FontWeight.w700,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 2),
                  Text(
                    'Full specialization pack',
                    style: TextStyle(
                      color: HomeDesignConstants.mutedText(context),
                      fontSize: 10.5,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
