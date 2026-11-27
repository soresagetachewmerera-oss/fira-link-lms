import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:flutter/rendering.dart';

import '../../theme/app_theme.dart';
import '../../l10n/app_localizations.dart';
import '../../widgets/fira_bottom_dock.dart';
import '../../services/home_service.dart';
import '../../services/course_service.dart';
import '../../models/home_data.dart';
import '../../models/course.dart';
import '../../models/bundle.dart';
import '../courses/courses_screen.dart';
import '../bundles/bundles_screen.dart';
import '../bundles/bundle_detail_screen.dart';
import '../search/search_screen.dart';
import '../../widgets/home_skeletons.dart';
import '../../widgets/live_class_banner.dart';
import '../../router/app_router.dart';
import 'widgets/home_design_constants.dart';
import 'widgets/home_header.dart';
import 'widgets/home_search_bar.dart';
import 'widgets/continue_learning_card.dart';
import 'widgets/toolkit_bento.dart';
import '../../providers/market_watch_provider.dart';
import '../../widgets/market/market_watch_card.dart';
import 'widgets/topic_chips.dart';
import 'widgets/featured_course_card.dart';
import 'widgets/home_primary_action.dart';
import 'models/continue_learning_data.dart';

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

  HomeData? _homeData;
  List<Course> _recentCourses = [];
  List<Bundle> _bundles = [];
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
      // Fetch the consolidated home payload. Market and progress modules are
      // omitted by the API when no real data is available.
      final homeResponse = await _homeService.fetchHomeData();

      if (homeResponse.statusCode == 200) {
        final jsonResponse = jsonDecode(homeResponse.body);
        if (jsonResponse['success'] == true) {
          if (!mounted) return;
          setState(() {
            _homeData = HomeData.fromJson(jsonResponse);
            _recentCourses = _homeData?.recentCourses ?? [];
            _inProgressCourse = _homeData?.continueLearning;
          });

          // Fetch bundles asynchronously
          try {
            final bundleResponse = await _courseService.fetchBundles();
            if (bundleResponse.statusCode == 200) {
              final bData = jsonDecode(bundleResponse.body);
              if (bData['success'] == true &&
                  bData['data'] != null &&
                  mounted) {
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

  Future<void> _refreshMarket() async {
    try {
      await context.read<MarketWatchProvider>().load();
    } on ProviderNotFoundException {
      // No app-wide provider (tests): the card refreshes itself.
    }
  }

  Future<void> _onRefresh() async {
    if (_scrollController.hasClients) {
      _savedScrollPosition = _scrollController.offset;
    }

    await Future.wait([_loadAllData(), _refreshMarket()]);

    if (mounted && _scrollController.hasClients) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted &&
            _scrollController.hasClients &&
            _savedScrollPosition > 0) {
          _scrollController.jumpTo(
            _savedScrollPosition.clamp(
              0.0,
              _scrollController.position.maxScrollExtent,
            ),
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
            SearchScreen(initialQuery: query),
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
          return title.contains('sourc') ||
              title.contains('origin') ||
              title.contains('green') ||
              cat.contains('sourc') ||
              cat.contains('coffee');
        case 'Cupping & quality':
          return title.contains('cup') ||
              title.contains('qualit') ||
              title.contains('grad') ||
              title.contains('sca') ||
              desc.contains('cup');
        case 'Export docs':
          return title.contains('doc') ||
              title.contains('custom') ||
              title.contains('complian') ||
              title.contains('contract') ||
              title.contains('export');
        case 'Logistics':
          return title.contains('logist') ||
              title.contains('freight') ||
              title.contains('supply') ||
              title.contains('ship') ||
              title.contains('transport');
        case 'Finance':
          return title.contains('financ') ||
              title.contains('fob') ||
              title.contains('cif') ||
              title.contains('price') ||
              title.contains('forex');
        case 'Buyers':
          return title.contains('buyer') ||
              title.contains('roaster') ||
              title.contains('trade') ||
              title.contains('market');
        default:
          return title.contains(topic) || cat.contains(topic);
      }
    }).toList();

    return filtered.isNotEmpty ? filtered : allCourses;
  }

  bool _handleScrollNotification(UserScrollNotification notification) {
    final double currentOffset = _scrollController.hasClients
        ? _scrollController.offset
        : 0.0;

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
    else if (notification.direction == ScrollDirection.forward &&
        delta < -8.0) {
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
                    Color(
                      0xFFFCFBFE,
                    ), // Subtle top-to-bottom tint in light mode
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
    final List<Course> allFeatured =
        _homeData?.popularCourses.isNotEmpty == true
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
        bottom: HomeDesignConstants
            .bottomDockClearance, // Clear 140dp floating dock
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          // 1. Compact Header (Single row)
          const HomeHeader(),
          const SizedBox(height: 20),

          // One clear next step based on real user state.
          if (_inProgressCourse != null) ...[
            ContinueLearningCard(course: _inProgressCourse),
          ] else ...[
            _buildRolePrimaryAction(),
          ],
          const SizedBox(height: 18),

          if (_homeData?.upcomingLiveClass != null) ...[
            _buildSectionLabel('Coming up'),
            LiveClassBanner(
              liveClass: _homeData!.upcomingLiveClass!,
              margin: const EdgeInsets.fromLTRB(20, 10, 20, 0),
            ),
            const SizedBox(height: 20),
          ],

          // Market watch (replaces Calculate profit and Market pulse). Hidden until a Home row has a price.
          const MarketWatchCard(),
          const SizedBox(height: 20),
          const ToolkitBento(),
          const SizedBox(height: 28),

          _buildSectionLabel('Find your next course'),
          const SizedBox(height: 10),
          HomeSearchBar(controller: _searchController, onSearch: _handleSearch),
          const SizedBox(height: 16),

          TopicChips(
            selectedTopic: _selectedTopic,
            onTopicSelected: _handleTopicSelected,
          ),
          const SizedBox(height: 16),

          Padding(
            padding: const EdgeInsets.symmetric(
              horizontal: HomeDesignConstants.horizontalPadding,
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Recommended training',
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
                      MaterialPageRoute(builder: (_) => const CoursesScreen()),
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
                return FeaturedCourseCard(course: course, width: 236.0);
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

  Widget _buildRolePrimaryAction() {
    final contextData = _homeData?.userContext;
    final role = contextData?.persona.toLowerCase() ?? 'visitor';

    if (role == 'admin') {
      return HomePrimaryAction(
        eyebrow: 'Platform operations',
        title: 'Review the platform dashboard',
        description:
            'Continue to approvals, moderation, payments, and operational reporting.',
        actionLabel: 'Open dashboard',
        icon: Icons.dashboard_outlined,
        onPressed: () => Navigator.of(context).pushNamed(AppRouter.dashboard),
      );
    }

    if (role == 'instructor') {
      return HomePrimaryAction(
        eyebrow: 'Teaching workspace',
        title: 'Support your learners',
        description:
            'Review course activity, learner submissions, and upcoming teaching work.',
        actionLabel: 'Open dashboard',
        icon: Icons.school_outlined,
        onPressed: () => Navigator.of(context).pushNamed(AppRouter.dashboard),
      );
    }

    if (contextData?.can('sell_coffee') == true) {
      return HomePrimaryAction(
        eyebrow: 'Coffee marketplace',
        title: 'Move your next deal forward',
        description:
            'Manage active coffee lots, buyer inquiries, and seller opportunities.',
        actionLabel: 'Open seller portal',
        icon: Icons.storefront_outlined,
        onPressed: () =>
            Navigator.of(context).pushNamed(AppRouter.marketplaceSellerPortal),
      );
    }

    if (role == 'buyer') {
      return HomePrimaryAction(
        eyebrow: 'Coffee sourcing',
        title: 'Find the right Ethiopian coffee',
        description:
            'Browse verified lots or publish an RFQ for your exact sourcing needs.',
        actionLabel: 'Create an RFQ',
        icon: Icons.manage_search_outlined,
        onPressed: () =>
            Navigator.of(context).pushNamed(AppRouter.marketplaceCreateRfq),
      );
    }

    final authenticated = contextData?.isAuthenticated == true;
    return HomePrimaryAction(
      eyebrow: authenticated ? 'Your learning path' : 'Start with Fira Link',
      title: authenticated
          ? 'Choose your next practical skill'
          : 'Learn coffee export from source to market',
      description: authenticated
          ? 'Explore training built around real coffee trade and export workflows.'
          : 'Build practical skills, use export tools, and connect learning to real opportunities.',
      actionLabel: 'Explore courses',
      icon: Icons.arrow_forward_rounded,
      onPressed: () => Navigator.of(
        context,
      ).push(MaterialPageRoute(builder: (_) => const CoursesScreen())),
    );
  }

  Widget _buildSectionLabel(String title, {String? trailing}) {
    return Padding(
      padding: const EdgeInsets.symmetric(
        horizontal: HomeDesignConstants.horizontalPadding,
      ),
      child: Row(
        children: [
          Expanded(
            child: Text(
              title,
              style: TextStyle(
                color: HomeDesignConstants.text(context),
                fontSize: 17,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
          if (trailing != null)
            Text(
              trailing,
              style: TextStyle(
                color: HomeDesignConstants.mutedText(context),
                fontSize: 11,
                fontWeight: FontWeight.w600,
              ),
            ),
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
          MaterialPageRoute(builder: (_) => BundleDetailScreen(bundle: bundle)),
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
                          child: Icon(
                            Icons.layers_outlined,
                            color: primaryColor,
                            size: 28,
                          ),
                        ),
                      )
                    : Container(
                        height: 105,
                        width: double.infinity,
                        color: HomeDesignConstants.brandTint(context),
                        child: Icon(
                          Icons.layers_outlined,
                          color: primaryColor,
                          size: 28,
                        ),
                      ),
                if (courseCount > 0)
                  Positioned(
                    top: 8,
                    left: 8,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 3,
                      ),
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
