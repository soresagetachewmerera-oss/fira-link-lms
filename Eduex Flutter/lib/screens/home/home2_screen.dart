import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:hugeicons/hugeicons.dart';
import 'package:provider/provider.dart';

import '../../config/config.dart';
import '../../models/bundle.dart';
import '../../models/category.dart';
import '../../models/course.dart';
import '../../models/home_data.dart';
import '../../providers/auth_provider.dart';
import '../../providers/chat_provider.dart';
import '../../services/course_service.dart';
import '../../services/home_service.dart';
import '../../theme/app_theme.dart';
import '../../widgets/bottom_nav_bar.dart';
import '../../widgets/home_skeletons.dart';
import '../../widgets/live_class_banner.dart';
import '../ai_learning_path/ai_learning_path_screen.dart';
import '../ai_suggestions/ai_suggestions_screen.dart';
import '../auth/auth_screen.dart';
import '../bundles/bundle_detail_screen.dart';
import '../bundles/bundles_screen.dart';
import '../categories/categories_screen.dart';
import '../category_detail/category_detail_screen.dart';
import '../course_detail/course_detail_screen.dart';
import '../courses/courses_screen.dart';
import '../dashboard/dashboard_screen.dart';
import '../notifications/notifications_screen.dart';
import '../wishlist/wishlist_screen.dart';
import '../coffee_toolkit/coffee_toolkit_hub_screen.dart';

class Home2Screen extends StatefulWidget {
  const Home2Screen({super.key});

  @override
  State<Home2Screen> createState() => _Home2ScreenState();
}

class _Home2ScreenState extends State<Home2Screen> {
  final PageController _featuredController = PageController();
  final ScrollController _scrollController = ScrollController();
  final TextEditingController _searchController = TextEditingController();
  final HomeService _homeService = HomeService();
  final CourseService _courseService = CourseService();

  int _currentFeaturedIndex = 0;
  HomeData? _homeData;
  List<Course> _recentCourses = [];
  List<Bundle> _bundles = [];
  bool _isLoading = true;
  String? _error;

  int _recentPage = 1;
  bool _isMoreRecentLoading = false;
  bool _hasMoreRecent = true;
  double _savedScrollPosition = 0.0;

  @override
  void initState() {
    super.initState();
    SystemChrome.setSystemUIOverlayStyle(
      const SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.light,
        statusBarBrightness: Brightness.dark,
      ),
    );
    _fetchData();
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _featuredController.dispose();
    _scrollController.dispose();
    _searchController.dispose();
    SystemChrome.setSystemUIOverlayStyle(SystemUiOverlayStyle.dark);
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >=
        _scrollController.position.maxScrollExtent - 200) {
      _loadMoreRecentCourses();
    }
  }

  Future<void> _fetchData() async {
    try {
      final response = await _homeService.fetchHomeData();
      if (response.statusCode == 200) {
        final jsonResponse = jsonDecode(response.body);
        if (jsonResponse['success'] == true) {
          if (mounted) {
            setState(() {
              _homeData = HomeData.fromJson(jsonResponse);
              _recentCourses = _homeData?.recentCourses ?? [];
              _error = null;
            });

            // Fetch bundles in parallel
            try {
              final bundleResponse = await _courseService.fetchBundles();
              if (bundleResponse.statusCode == 200) {
                final bData = jsonDecode(bundleResponse.body);
                if (bData['success'] == true && bData['data'] != null) {
                  setState(() {
                    _bundles = (bData['data'] as List)
                        .map((b) => Bundle.fromJson(b))
                        .toList();
                  });
                }
              }
            } catch (e) {
              // Ignore bundle fetch error
            }

            setState(() {
              _isLoading = false;
            });
          }
        } else {
          _handleError('Failed to load data');
        }
      } else {
        _handleError('Server error: ${response.statusCode}');
      }
    } catch (e) {
      _handleError('Connection error: $e');
    }
  }

  Future<void> _loadMoreRecentCourses() async {
    if (_isMoreRecentLoading || !_hasMoreRecent) return;

    setState(() {
      _isMoreRecentLoading = true;
    });

    try {
      final response = await _courseService.fetchRecentCourses(
        page: _recentPage + 1,
      );

      if (response.statusCode == 200) {
        final jsonResponse = jsonDecode(response.body);
        if (jsonResponse['success'] == true) {
          final data = jsonResponse['data'];
          final List<dynamic> newCoursesJson =
              (data is Map && data.containsKey('data')) ? data['data'] : data;

          if (mounted) {
            setState(() {
              if (newCoursesJson.isNotEmpty) {
                _recentCourses.addAll(
                  newCoursesJson.map((json) => Course.fromJson(json)).toList(),
                );
                _recentPage++;
              } else {
                _hasMoreRecent = false;
              }
            });
          }
        }
      }
    } catch (e) {
      debugPrint('Error loading more courses: $e');
    } finally {
      if (mounted) {
        setState(() {
          _isMoreRecentLoading = false;
        });
      }
    }
  }

  void _handleError(String message) {
    if (mounted) {
      setState(() {
        _error = message;
        _isLoading = false;
      });
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(message)));
    }
  }

  Future<void> _onRefresh() async {
    setState(() {
      _isLoading = true;
    });

    if (_scrollController.hasClients) {
      _savedScrollPosition = _scrollController.offset;
    }

    await _fetchData();

    if (mounted && _scrollController.hasClients) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted && _scrollController.hasClients) {
          if (_savedScrollPosition > 0 &&
              _savedScrollPosition <=
                  _scrollController.position.maxScrollExtent) {
            _scrollController.jumpTo(_savedScrollPosition);
          }
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.getBackgroundColor(context),
      body: Column(
        children: <Widget>[
          _buildHeader(context),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _onRefresh,
              color: AppTheme.primary,
              child: _isLoading
                  ? const HomeSkeletons()
                  : _error != null
                  ? _buildErrorState()
                  : _buildMainContent(context),
            ),
          ),
        ],
      ),
      bottomNavigationBar: const AppBottomNavBar(currentTab: BottomNavTab.home),
      floatingActionButton: FloatingActionButton(
        onPressed: () {
          Navigator.of(context).push(
            MaterialPageRoute(
              builder: (context) => const AiLearningPathScreen(),
            ),
          );
        },
        backgroundColor: AppTheme.getPrimaryColor(context),
        elevation: 0,
        child: const Icon(Icons.auto_awesome, color: Colors.white, size: 26),
      ),
      floatingActionButtonLocation: FloatingActionButtonLocation.endFloat,
    );
  }

  Widget _buildHeader(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: AppTheme.primary,
        borderRadius: const BorderRadius.only(
          bottomLeft: Radius.circular(24),
          bottomRight: Radius.circular(24),
        ),
      ),
      child: SafeArea(
        bottom: false,
        child: Column(
          children: <Widget>[
            const SizedBox(height: 8),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: Consumer<AuthProvider>(
                builder: (context, auth, _) {
                  final user = auth.user;
                  final bool isAuthenticated = auth.isAuthenticated;

                  return Row(
                    children: <Widget>[
                      GestureDetector(
                        onTap: () {
                          if (isAuthenticated) {
                            Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (context) => const DashboardScreen(),
                              ),
                            );
                          } else {
                            Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (context) => const AuthScreen(),
                              ),
                            );
                          }
                        },
                        child: Container(
                          width: 40,
                          height: 40,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            border: Border.all(color: Colors.white, width: 2),
                            image: DecorationImage(
                              image: (isAuthenticated && user?.profilePhotoUrl != null)
                                  ? NetworkImage(user!.profilePhotoUrl!)
                                  : const AssetImage('assets/img/default-profile.png')
                                      as ImageProvider,
                              fit: BoxFit.cover,
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: <Widget>[
                            Text(
                              'Hi ${isAuthenticated ? (user?.name ?? 'User') : 'Guest'}',
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 18,
                                fontWeight: FontWeight.w700,
                                letterSpacing: 0.2,
                                fontFamily: 'Montserrat',
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                            const SizedBox(height: 2),
                            Text(
                              "Let's start learning!",
                              style: TextStyle(
                                color: Colors.white.withValues(alpha: 0.9),
                                fontSize: 13,
                                fontWeight: FontWeight.w500,
                                letterSpacing: 0.1,
                                fontFamily: 'Montserrat',
                              ),
                            ),
                          ],
                        ),
                      ),
                      GestureDetector(
                        onTap: () {
                          Navigator.of(context).push(
                            MaterialPageRoute(
                              builder: (context) => const WishlistScreen(),
                            ),
                          );
                        },
                        child: Container(
                          width: 36,
                          height: 36,
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.15),
                            shape: BoxShape.circle,
                          ),
                          child: const Center(
                            child: Icon(
                              Icons.favorite_border,
                              color: Colors.white,
                              size: 18,
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 10),
                      GestureDetector(
                        onTap: () {
                          Navigator.of(context).push(
                            MaterialPageRoute(
                              builder: (context) => const NotificationsScreen(),
                            ),
                          );
                        },
                        child: Consumer<ChatProvider>(
                          builder: (context, chatProvider, _) {
                            final unread = chatProvider.unreadCount;
                            return Stack(
                              alignment: Alignment.center,
                              children: <Widget>[
                                Container(
                                  width: 36,
                                  height: 36,
                                  decoration: BoxDecoration(
                                    color: Colors.white.withValues(alpha: 0.15),
                                    shape: BoxShape.circle,
                                  ),
                                  child: const Icon(
                                    Icons.notifications_outlined,
                                    size: 18,
                                    color: Colors.white,
                                  ),
                                ),
                                if (unread > 0)
                                  Positioned(
                                    right: 2,
                                    top: 2,
                                    child: Container(
                                      padding: const EdgeInsets.all(2),
                                      decoration: BoxDecoration(
                                        color: Colors.red,
                                        shape: BoxShape.circle,
                                        border: Border.all(
                                          color: AppTheme.primary,
                                          width: 1.5,
                                        ),
                                      ),
                                      constraints: const BoxConstraints(
                                        minWidth: 14,
                                        minHeight: 14,
                                      ),
                                      child: Text(
                                        unread > 99 ? '99+' : unread.toString(),
                                        style: const TextStyle(
                                          color: Colors.white,
                                          fontSize: 8,
                                          fontWeight: FontWeight.w700,
                                        ),
                                        textAlign: TextAlign.center,
                                      ),
                                    ),
                                  ),
                              ],
                            );
                          },
                        ),
                      ),
                    ],
                  );
                },
              ),
            ),
            const SizedBox(height: 16),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: Container(
                height: 48,
                decoration: BoxDecoration(
                  color: AppTheme.getCardColor(context),
                  borderRadius: BorderRadius.circular(24),
                ),
                child: TextField(
                  controller: _searchController,
                  onSubmitted: _handleSearch,
                  textInputAction: TextInputAction.search,
                  decoration: InputDecoration(
                    hintText: 'What are you going to find?',
                    hintStyle: TextStyle(
                      color: AppTheme.getTextColor(context).withValues(alpha: 0.5),
                      fontSize: 14,
                      fontWeight: FontWeight.w500,
                      fontFamily: 'Montserrat',
                    ),
                    prefixIcon: Padding(
                      padding: const EdgeInsets.all(12),
                      child: HugeIcon(
                        icon: HugeIcons.strokeRoundedSearch01,
                        size: 20,
                        color: AppTheme.getTextColor(context).withValues(alpha: 0.6),
                      ),
                    ),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(24),
                      borderSide: BorderSide.none,
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(24),
                      borderSide: BorderSide.none,
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(24),
                      borderSide: BorderSide.none,
                    ),
                    contentPadding: const EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 14,
                    ),
                  ),
                  style: TextStyle(
                    color: AppTheme.getTextColor(context),
                    fontSize: 14,
                    fontWeight: FontWeight.w500,
                    fontFamily: 'Montserrat',
                  ),
                ),
              ),
            ),
            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  Widget _buildErrorState() {
    return SingleChildScrollView(
      physics: const AlwaysScrollableScrollPhysics(),
      child: Container(
        height: 400,
        alignment: Alignment.center,
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.error_outline, size: 48, color: Colors.red[300]),
            const SizedBox(height: 16),
            Text(
              _error ?? 'Failed to load data',
              textAlign: TextAlign.center,
              style: TextStyle(
                color: AppTheme.getTextColor(context),
                fontSize: 16,
              ),
            ),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: () {
                setState(() {
                  _isLoading = true;
                  _error = null;
                });
                _fetchData();
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.primary,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
              child: const Text('Retry'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildMainContent(BuildContext context) {
    final popularCourses = _homeData?.popularCourses ?? [];
    final categories = _homeData?.categories ?? [];
    final aiSuggestions = _homeData?.aiSuggestions ?? [];
    final recentCourses = _homeData?.recentCourses ?? [];
    final upcomingLiveClass = _homeData?.upcomingLiveClass;

    return SingleChildScrollView(
      controller: _scrollController,
      physics: const BouncingScrollPhysics(parent: AlwaysScrollableScrollPhysics()),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          const SizedBox(height: 20),
          if (popularCourses.isNotEmpty) ...[
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: Text(
                'Featured Courses',
                style: TextStyle(
                  color: AppTheme.getTextColor(context),
                  fontSize: 15.5,
                  fontWeight: FontWeight.w700,
                  letterSpacing: 0.1,
                  fontFamily: 'Montserrat',
                ),
              ),
            ),
            const SizedBox(height: 14),
            SizedBox(
              height: 280,
              child: PageView.builder(
                controller: _featuredController,
                onPageChanged: (int index) {
                  setState(() {
                    _currentFeaturedIndex = index;
                  });
                },
                itemCount: popularCourses.length,
                itemBuilder: (BuildContext context, int index) {
                  final course = popularCourses[index];
                  return Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 20),
                    child: GestureDetector(
                      onTap: () {
                        Navigator.of(context).push(
                          MaterialPageRoute(
                            builder: (context) => CourseDetailScreen(
                              courseId: course.id,
                            ),
                          ),
                        );
                      },
                      child: _FeaturedCourseCard(course: course),
                    ),
                  );
                },
              ),
            ),
            const SizedBox(height: 12),
            if (popularCourses.length > 1)
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: List<Widget>.generate(popularCourses.length, (int index) {
                  final bool isActive = index == _currentFeaturedIndex;
                  return AnimatedContainer(
                    duration: const Duration(milliseconds: 250),
                    margin: const EdgeInsets.symmetric(horizontal: 4),
                    width: isActive ? 20 : 8,
                    height: 8,
                    decoration: BoxDecoration(
                      color: isActive
                          ? AppTheme.getPrimaryColor(context)
                          : AppTheme.getPrimaryColor(context).withValues(alpha: 0.3),
                      borderRadius: BorderRadius.circular(4),
                    ),
                  );
                }),
              ),
            const SizedBox(height: 24),
          ],
          if (categories.isNotEmpty) ...[
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: Row(
                children: <Widget>[
                  Text(
                    'Top Categories',
                    style: TextStyle(
                      color: AppTheme.getTextColor(context),
                      fontSize: 18,
                      fontWeight: FontWeight.w700,
                      letterSpacing: 0.2,
                      fontFamily: 'Montserrat',
                    ),
                  ),
                  const Spacer(),
                  GestureDetector(
                    onTap: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (context) => const CategoriesScreen(),
                        ),
                      );
                    },
                    child: Text(
                      'View All',
                      style: TextStyle(
                        color: AppTheme.getTextColor(context).withValues(alpha: 0.6),
                        fontSize: 13,
                        fontWeight: FontWeight.w500,
                        letterSpacing: 0.1,
                        fontFamily: 'Montserrat',
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),
            _TopCategoriesRow(categories: categories),
            const SizedBox(height: 20),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: GestureDetector(
                onTap: () {
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (context) => const CoffeeToolkitHubScreen(),
                    ),
                  );
                },
                child: Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      colors: [Color(0xFF4A0E66), Color(0xFF6D1A95)],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    borderRadius: BorderRadius.circular(18),
                    boxShadow: [
                      BoxShadow(
                        color: const Color(0xFF6D1A95).withValues(alpha: 0.25),
                        blurRadius: 10,
                        offset: const Offset(0, 4),
                      ),
                    ],
                  ),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.2),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: const HugeIcon(
                          icon: HugeIcons.strokeRoundedCoffee01,
                          size: 24,
                          color: Colors.white,
                        ),
                      ),
                      const SizedBox(width: 14),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Row(
                              children: [
                                Text(
                                  'Coffee Export Toolkit',
                                  style: TextStyle(
                                    color: Colors.white,
                                    fontSize: 15,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                                SizedBox(width: 8),
                                Badge(
                                  label: Text(
                                    'PRO',
                                    style: TextStyle(
                                      fontSize: 9,
                                      fontWeight: FontWeight.bold,
                                      color: Colors.black,
                                    ),
                                  ),
                                  backgroundColor: Colors.amber,
                                ),
                              ],
                            ),
                            const SizedBox(height: 2),
                            Text(
                              'Profit calculator, offer sheets & Fira AI advisor',
                              style: TextStyle(
                                color: Colors.white.withValues(alpha: 0.8),
                                fontSize: 11,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const Icon(
                        Icons.arrow_forward_ios,
                        color: Colors.white,
                        size: 14,
                      ),
                    ],
                  ),
                ),
              ),
            ),
            const SizedBox(height: 24),
          ],
          if (upcomingLiveClass != null) ...[
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: LiveClassBanner(liveClass: upcomingLiveClass),
            ),
            const SizedBox(height: 24),
          ],
          if (recentCourses.isNotEmpty) ...[
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: Row(
                children: <Widget>[
                  Text(
                    'Newest Courses',
                    style: TextStyle(
                      color: AppTheme.getTextColor(context),
                      fontSize: 18,
                      fontWeight: FontWeight.w700,
                      letterSpacing: 0.2,
                      fontFamily: 'Montserrat',
                    ),
                  ),
                  const Spacer(),
                  GestureDetector(
                    onTap: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (context) => const CoursesScreen(),
                        ),
                      );
                    },
                    child: Text(
                      'View All',
                      style: TextStyle(
                        color: AppTheme.getTextColor(context).withValues(alpha: 0.6),
                        fontSize: 13,
                        fontWeight: FontWeight.w500,
                        letterSpacing: 0.1,
                        fontFamily: 'Montserrat',
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
            SizedBox(
              height: 255,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 20),
                itemCount: recentCourses.length,
                separatorBuilder: (_, _) => const SizedBox(width: 16),
                itemBuilder: (BuildContext context, int index) {
                  final course = recentCourses[index];
                  return GestureDetector(
                    onTap: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (context) => CourseDetailScreen(
                            courseId: course.id,
                          ),
                        ),
                      );
                    },
                    child: _CourseCard2(course: course),
                  );
                },
              ),
            ),
            const SizedBox(height: 24),
          ],
          if (aiSuggestions.isNotEmpty) ...[
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: Row(
                children: <Widget>[
                  Text(
                    'AI Suggestions',
                    style: TextStyle(
                      color: AppTheme.getTextColor(context),
                      fontSize: 18,
                      fontWeight: FontWeight.w700,
                      letterSpacing: 0.2,
                      fontFamily: 'Montserrat',
                    ),
                  ),
                  const Spacer(),
                  GestureDetector(
                    onTap: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (context) => const AiSuggestionsScreen(),
                        ),
                      );
                    },
                    child: Text(
                      'View All',
                      style: TextStyle(
                        color: AppTheme.getTextColor(context).withValues(alpha: 0.6),
                        fontSize: 13,
                        fontWeight: FontWeight.w500,
                        letterSpacing: 0.1,
                        fontFamily: 'Montserrat',
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
            SizedBox(
              height: 255,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 20),
                itemCount: aiSuggestions.length,
                separatorBuilder: (_, _) => const SizedBox(width: 16),
                itemBuilder: (BuildContext context, int index) {
                  final course = aiSuggestions[index];
                  return GestureDetector(
                    onTap: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (context) => CourseDetailScreen(
                            courseId: course.id,
                          ),
                        ),
                      );
                    },
                    child: _CourseCard2(course: course),
                  );
                },
              ),
            ),
            const SizedBox(height: 24),
          ],
          if (popularCourses.isNotEmpty) ...[
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: Row(
                children: <Widget>[
                  Text(
                    'Best Rated',
                    style: TextStyle(
                      color: AppTheme.getTextColor(context),
                      fontSize: 18,
                      fontWeight: FontWeight.w700,
                      letterSpacing: 0.2,
                      fontFamily: 'Montserrat',
                    ),
                  ),
                  const Spacer(),
                  GestureDetector(
                    onTap: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (context) => const CoursesScreen(),
                        ),
                      );
                    },
                    child: Text(
                      'View All',
                      style: TextStyle(
                        color: AppTheme.getTextColor(context).withValues(alpha: 0.6),
                        fontSize: 13,
                        fontWeight: FontWeight.w500,
                        letterSpacing: 0.1,
                        fontFamily: 'Montserrat',
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
            SizedBox(
              height: 255,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 20),
                itemCount: popularCourses.length,
                separatorBuilder: (_, _) => const SizedBox(width: 16),
                itemBuilder: (BuildContext context, int index) {
                  final course = popularCourses[index];
                  return GestureDetector(
                    onTap: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (context) => CourseDetailScreen(
                            courseId: course.id,
                          ),
                        ),
                      );
                    },
                    child: _CourseCard2(course: course),
                  );
                },
              ),
            ),
            const SizedBox(height: 24),
          ],
          if (_bundles.isNotEmpty) ...[
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: Row(
                children: <Widget>[
                  Text(
                    'Course Bundles',
                    style: TextStyle(
                      color: AppTheme.getTextColor(context),
                      fontSize: 18,
                      fontWeight: FontWeight.w700,
                      letterSpacing: 0.2,
                      fontFamily: 'Montserrat',
                    ),
                  ),
                  const Spacer(),
                  GestureDetector(
                    onTap: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (context) => const BundlesScreen(),
                        ),
                      );
                    },
                    child: Text(
                      'View All',
                      style: TextStyle(
                        color: AppTheme.getTextColor(context).withValues(alpha: 0.6),
                        fontSize: 13,
                        fontWeight: FontWeight.w500,
                        letterSpacing: 0.1,
                        fontFamily: 'Montserrat',
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
            SizedBox(
              height: 255,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 20),
                itemCount: _bundles.length,
                separatorBuilder: (_, _) => const SizedBox(width: 16),
                itemBuilder: (BuildContext context, int index) {
                  final bundle = _bundles[index];
                  return GestureDetector(
                    onTap: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (context) => BundleDetailScreen(
                            bundle: bundle,
                          ),
                        ),
                      );
                    },
                    child: _BundleCard(bundle: bundle),
                  );
                },
              ),
            ),
            const SizedBox(height: 24),
          ],
          if (_recentCourses.isNotEmpty) ...[
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: Row(
                children: <Widget>[
                  Text(
                    'All Courses',
                    style: TextStyle(
                      color: AppTheme.getTextColor(context),
                      fontSize: 18,
                      fontWeight: FontWeight.w700,
                      letterSpacing: 0.2,
                      fontFamily: 'Montserrat',
                    ),
                  ),
                  const Spacer(),
                  GestureDetector(
                    onTap: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (context) => const CoursesScreen(),
                        ),
                      );
                    },
                    child: Text(
                      'View All',
                      style: TextStyle(
                        color: AppTheme.getTextColor(context).withValues(alpha: 0.6),
                        fontSize: 13,
                        fontWeight: FontWeight.w500,
                        letterSpacing: 0.1,
                        fontFamily: 'Montserrat',
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
            SizedBox(
              height: 255,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 20),
                itemCount: _recentCourses.length,
                separatorBuilder: (_, _) => const SizedBox(width: 16),
                itemBuilder: (BuildContext context, int index) {
                  final course = _recentCourses[index];
                  return GestureDetector(
                    onTap: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (context) => CourseDetailScreen(
                            courseId: course.id,
                          ),
                        ),
                      );
                    },
                    child: _CourseCard2(course: course),
                  );
                },
              ),
            ),
            if (_isMoreRecentLoading)
              const Padding(
                padding: EdgeInsets.all(16.0),
                child: Center(
                  child: CircularProgressIndicator(strokeWidth: 2),
                ),
              ),
            const SizedBox(height: 24),
          ],
        ],
      ),
    );
  }
}

class _LazyNetworkImage extends StatelessWidget {
  const _LazyNetworkImage(this.path, {this.width, this.height});

  final String? path;
  final double? width;
  final double? height;

  @override
  Widget build(BuildContext context) {
    if (path == null || path!.isEmpty) {
      return Container(
        width: width,
        height: height,
        color: Colors.grey[200],
        child: const Icon(Icons.image, color: Colors.grey, size: 28),
      );
    }

    final String url = AppConfig.getImageUrl(path);

    return Image.network(
      url,
      width: width,
      height: height,
      fit: BoxFit.cover,
      frameBuilder: (context, child, frame, wasSynchronouslyLoaded) {
        if (wasSynchronouslyLoaded) return child;
        return AnimatedOpacity(
          opacity: frame == null ? 0 : 1,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
          child: child,
        );
      },
      loadingBuilder: (context, child, loadingProgress) {
        if (loadingProgress == null) return child;
        return Container(
          width: width,
          height: height,
          color: Colors.grey[200],
          child: const Center(
            child: Icon(Icons.image, color: Colors.grey, size: 24),
          ),
        );
      },
      errorBuilder: (context, error, stackTrace) => Container(
        width: width,
        height: height,
        color: Colors.grey[200],
        child: const Icon(Icons.broken_image, color: Colors.grey, size: 28),
      ),
    );
  }
}

class _FeaturedCourseCard extends StatelessWidget {
  const _FeaturedCourseCard({required this.course});

  final Course course;

  @override
  Widget build(BuildContext context) {
    final priceText = (course.price != null && course.price! > 0)
        ? '\$${course.price!.toStringAsFixed(2)}'
        : 'Free';

    final instructorName = course.instructorName ?? course.instructor?.name ?? 'Instructor';
    final instructorAvatar = course.instructor?.image;
    final rating = course.rating ?? 5.0;

    return Container(
      decoration: BoxDecoration(borderRadius: BorderRadius.circular(20)),
      clipBehavior: Clip.antiAlias,
      child: Stack(
        fit: StackFit.expand,
        children: <Widget>[
          _LazyNetworkImage(
            course.thumbnail,
            width: double.infinity,
            height: double.infinity,
          ),
          Container(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: <Color>[
                  Colors.transparent,
                  Colors.black.withValues(alpha: 0.75),
                ],
                stops: const <double>[0.35, 1.0],
              ),
            ),
          ),
          Positioned(
            top: 14,
            right: 14,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
              decoration: BoxDecoration(
                color: AppTheme.mint100,
                borderRadius: BorderRadius.circular(20),
              ),
              child: Text(
                priceText,
                style: const TextStyle(
                  color: AppTheme.primary,
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                  fontFamily: 'Montserrat',
                ),
              ),
            ),
          ),
          Positioned(
            bottom: 0,
            left: 0,
            right: 0,
            child: Container(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: <Widget>[
                  Text(
                    course.title,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 18,
                      fontWeight: FontWeight.w700,
                      letterSpacing: 0.2,
                      fontFamily: 'Montserrat',
                      shadows: <Shadow>[
                        Shadow(
                          offset: Offset(0, 1),
                          blurRadius: 3,
                          color: Colors.black26,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 10),
                  Row(
                    children: <Widget>[
                      Row(
                        children: List<Widget>.generate(
                          5,
                          (int index) => Icon(
                            index < rating.floor() ? Icons.star : Icons.star_half,
                            color: const Color(0xFFFFD700),
                            size: 16,
                          ),
                        ),
                      ),
                      const SizedBox(width: 6),
                      Text(
                        rating.toStringAsFixed(1),
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                          fontFamily: 'Montserrat',
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Row(
                    children: <Widget>[
                      Container(
                        width: 28,
                        height: 28,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          border: Border.all(color: Colors.white, width: 1.5),
                        ),
                        child: ClipOval(
                          child: _LazyNetworkImage(
                            instructorAvatar,
                            width: 28,
                            height: 28,
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          instructorName,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                            fontFamily: 'Montserrat',
                            shadows: <Shadow>[
                              Shadow(
                                offset: Offset(0, 1),
                                blurRadius: 3,
                                color: Colors.black26,
                              ),
                            ],
                          ),
                        ),
                      ),
                      if (course.lessonsCount > 0) ...[
                        const SizedBox(width: 12),
                        HugeIcon(
                          icon: HugeIcons.strokeRoundedBook01,
                          size: 14,
                          color: Colors.white.withValues(alpha: 0.9),
                        ),
                        const SizedBox(width: 4),
                        Text(
                          '${course.lessonsCount} Lessons',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 12,
                            fontWeight: FontWeight.w500,
                            fontFamily: 'Montserrat',
                            shadows: <Shadow>[
                              Shadow(
                                offset: Offset(0, 1),
                                blurRadius: 3,
                                color: Colors.black26,
                              ),
                            ],
                          ),
                        ),
                      ],
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _CourseCard2 extends StatelessWidget {
  const _CourseCard2({required this.course});

  final Course course;

  @override
  Widget build(BuildContext context) {
    final priceText = (course.price != null && course.price! > 0)
        ? '\$${course.price!.toStringAsFixed(2)}'
        : 'Free';

    final instructorName = course.instructorName ?? course.instructor?.name ?? 'Instructor';
    final rating = course.rating ?? 5.0;

    return Container(
      width: 200,
      decoration: BoxDecoration(
        color: AppTheme.getCardColor(context),
        borderRadius: BorderRadius.circular(16),
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: <Widget>[
          Stack(
            children: <Widget>[
              SizedBox(
                height: 115,
                width: double.infinity,
                child: _LazyNetworkImage(course.thumbnail),
              ),
              Positioned(
                top: 10,
                left: 10,
                child: Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: Colors.black.withValues(alpha: 0.6),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: <Widget>[
                      const Icon(
                        Icons.star,
                        color: Color(0xFFFFD700),
                        size: 13,
                      ),
                      const SizedBox(width: 4),
                      Text(
                        rating.toStringAsFixed(1),
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                          fontFamily: 'Montserrat',
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              if (course.categoryName != null && course.categoryName!.isNotEmpty)
                Positioned(
                  bottom: 10,
                  right: 10,
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: AppTheme.mint100,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      course.categoryName!,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: AppTheme.primary,
                        fontSize: 10,
                        fontWeight: FontWeight.w600,
                        fontFamily: 'Montserrat',
                      ),
                    ),
                  ),
                ),
            ],
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(12, 12, 12, 10),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: <Widget>[
                Text(
                  course.title,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    color: AppTheme.getTextColor(context),
                    fontSize: 12.5,
                    fontWeight: FontWeight.w600,
                    letterSpacing: -0.1,
                    fontFamily: 'Montserrat',
                    height: 1.3,
                  ),
                ),
                const SizedBox(height: 5),
                Row(
                  children: <Widget>[
                    Icon(
                      Icons.person_outline,
                      size: 12,
                      color: AppTheme.getTextColor(context).withValues(alpha: 0.6),
                    ),
                    const SizedBox(width: 4),
                    Expanded(
                      child: Text(
                        instructorName,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: AppTheme.getTextColor(context).withValues(alpha: 0.7),
                          fontSize: 10.5,
                          fontWeight: FontWeight.w500,
                          fontFamily: 'Montserrat',
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                if (course.lessonsCount > 0)
                  Row(
                    children: <Widget>[
                      HugeIcon(
                        icon: HugeIcons.strokeRoundedBook01,
                        size: 12,
                        color: AppTheme.getTextColor(context).withValues(alpha: 0.6),
                      ),
                      const SizedBox(width: 4),
                      Text(
                        '${course.lessonsCount} Lessons',
                        style: TextStyle(
                          color: AppTheme.getTextColor(context).withValues(alpha: 0.7),
                          fontSize: 10.5,
                          fontWeight: FontWeight.w500,
                          fontFamily: 'Montserrat',
                        ),
                      ),
                    ],
                  ),
                const SizedBox(height: 6),
                Text(
                  priceText,
                  style: TextStyle(
                    color: AppTheme.getPrimaryColor(context),
                    fontSize: 13.5,
                    fontWeight: FontWeight.w700,
                    letterSpacing: -0.2,
                    fontFamily: 'Montserrat',
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _BundleCard extends StatelessWidget {
  const _BundleCard({required this.bundle});

  final Bundle bundle;

  @override
  Widget build(BuildContext context) {
    final priceText = (bundle.price != null && bundle.price! > 0)
        ? '\$${bundle.price!.toStringAsFixed(2)}'
        : 'Free';

    final coursesCount = bundle.courses?.length ?? 0;

    return Container(
      width: 200,
      decoration: BoxDecoration(
        color: AppTheme.getCardColor(context),
        borderRadius: BorderRadius.circular(16),
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: <Widget>[
          Stack(
            children: <Widget>[
              SizedBox(
                height: 115,
                width: double.infinity,
                child: _LazyNetworkImage(bundle.image),
              ),
              Positioned(
                top: 10,
                right: 10,
                child: Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: AppTheme.primary,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Text(
                    'Bundle',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 10,
                      fontWeight: FontWeight.w600,
                      fontFamily: 'Montserrat',
                    ),
                  ),
                ),
              ),
            ],
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(12, 12, 12, 10),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: <Widget>[
                Text(
                  bundle.title,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    color: AppTheme.getTextColor(context),
                    fontSize: 13,
                    fontWeight: FontWeight.w700,
                    letterSpacing: 0.1,
                    fontFamily: 'Montserrat',
                    height: 1.25,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  '$coursesCount Courses',
                  style: TextStyle(
                    color: AppTheme.getTextColor(context).withValues(alpha: 0.7),
                    fontSize: 11,
                    fontWeight: FontWeight.w500,
                    fontFamily: 'Montserrat',
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  priceText,
                  style: TextStyle(
                    color: AppTheme.getPrimaryColor(context),
                    fontSize: 15,
                    fontWeight: FontWeight.w700,
                    fontFamily: 'Montserrat',
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _TopCategoriesRow extends StatelessWidget {
  const _TopCategoriesRow({required this.categories});

  final List<Category> categories;

  static const List<Color> _bgColors = [
    Color.fromARGB(255, 173, 236, 210),
    Color.fromARGB(255, 198, 224, 236),
    Color.fromARGB(255, 240, 194, 151),
    Color.fromARGB(255, 204, 211, 255),
    Color.fromARGB(255, 255, 214, 229),
    Color.fromARGB(255, 214, 231, 255),
    Color.fromARGB(255, 255, 244, 214),
    Color.fromARGB(255, 224, 240, 224),
  ];

  IconData _getCategoryIcon(String? iconName) {
    if (iconName == null) return Icons.grid_view_rounded;
    switch (iconName) {
      case 'fas fa-code':
        return Icons.code_rounded;
      case 'fas fa-pencil-ruler':
        return Icons.design_services_rounded;
      case 'fas fa-briefcase':
        return Icons.business_center_rounded;
      case 'fas fa-chalkboard-teacher':
        return Icons.school_rounded;
      case 'fas fa-laptop-code':
        return Icons.computer_rounded;
      case 'fas fa-bullhorn':
        return Icons.campaign_rounded;
      case 'fas fa-camera':
        return Icons.camera_alt_rounded;
      case 'fas fa-music':
        return Icons.music_note_rounded;
      case 'fas fa-chart-line':
        return Icons.show_chart_rounded;
      case 'fas fa-utensils':
        return Icons.restaurant_rounded;
      case 'fas fa-heartbeat':
        return Icons.favorite_rounded;
      case 'fas fa-language':
        return Icons.language_rounded;
      default:
        return Icons.grid_view_rounded;
    }
  }

  @override
  Widget build(BuildContext context) {
    if (categories.isEmpty) return const SizedBox.shrink();

    return SizedBox(
      height: 100,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 20),
        itemCount: categories.length,
        separatorBuilder: (_, _) => const SizedBox(width: 12),
        itemBuilder: (BuildContext context, int index) {
          final category = categories[index];
          final background = _bgColors[index % _bgColors.length];

          return GestureDetector(
            onTap: () {
              Navigator.of(context).push(
                MaterialPageRoute(
                  builder: (context) => CategoryDetailScreen(
                    categoryId: category.id,
                    categoryName: category.name,
                  ),
                ),
              );
            },
            child: SizedBox(
              width: 100,
              child: Stack(
                clipBehavior: Clip.none,
                children: <Widget>[
                  Positioned.fill(
                    top: 20,
                    child: Container(
                      decoration: BoxDecoration(
                        color: background,
                        borderRadius: BorderRadius.circular(18),
                      ),
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 12,
                      ),
                      child: Align(
                        alignment: Alignment.bottomCenter,
                        child: Text(
                          category.name,
                          textAlign: TextAlign.center,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: AppTheme.getPrimaryColor(context),
                            fontSize: 11,
                            fontWeight: FontWeight.w600,
                            height: 1.2,
                            fontFamily: 'Montserrat',
                          ),
                        ),
                      ),
                    ),
                  ),
                  Positioned(
                    top: 2,
                    left: 0,
                    right: 0,
                    child: Center(
                      child: Container(
                        width: 50,
                        height: 50,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: background,
                          border: Border.all(
                            color: AppTheme.getCardColor(context),
                            width: 2,
                          ),
                        ),
                        child: Center(
                          child: Icon(
                            _getCategoryIcon(category.icon),
                            size: 24,
                            color: AppTheme.getPrimaryColor(context),
                          ),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}
