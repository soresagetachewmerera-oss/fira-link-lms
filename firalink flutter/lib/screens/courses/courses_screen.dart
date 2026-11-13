import '../home/widgets/topic_chips.dart';
import '../../theme/fira_colors.dart';
import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:hugeicons/hugeicons.dart';
import 'package:provider/provider.dart';

import '../../providers/settings_provider.dart';
import '../../theme/app_theme.dart';
import '../../l10n/app_localizations.dart';
import '../../models/course.dart';
import '../../models/category.dart';
import '../../services/course_service.dart';
import '../course_detail/course_detail_screen.dart';
import '../../widgets/course_thumbnail_image.dart';
import 'courses_skeletons.dart';

class CoursesScreen extends StatefulWidget {
  final String? initialSearchQuery;
  const CoursesScreen({super.key, this.initialSearchQuery});

  @override
  State<CoursesScreen> createState() => _CoursesScreenState();
}

class _CoursesScreenState extends State<CoursesScreen> {
  final ScrollController _scrollController = ScrollController();
  final TextEditingController _searchController = TextEditingController();
  final CourseService _courseService = CourseService();
  Timer? _debounce;

  // Data
  List<Course> _courses = [];
  List<Category> _categories = [];
  bool _isLoading = false;
  bool _isMoreLoading = false;
  int _currentPage = 1;
  bool _hasMore = true;

  // Filters State
  String _searchQuery = '';
  String _selectedTopic = 'All';
  Set<int> _selectedCategoryIds = {};
  RangeValues _priceRange = const RangeValues(0, 500);
  Set<String> _selectedDurationRanges = {}; // '3-8', '8-14'

  @override
  void initState() {
    super.initState();
    _searchQuery = widget.initialSearchQuery ?? '';
    _searchController.text = _searchQuery;
    _fetchCategories();
    _fetchCourses();

    _scrollController.addListener(() {
      if (_scrollController.position.pixels >=
              _scrollController.position.maxScrollExtent - 200 &&
          !_isMoreLoading &&
          _hasMore) {
        _loadMoreCourses();
      }
    });

    _searchController.addListener(_onSearchChanged);
  }

  @override
  void dispose() {
    _scrollController.dispose();
    _searchController.dispose();
    _debounce?.cancel();
    super.dispose();
  }

  void _onSearchChanged() {
    if (_debounce?.isActive ?? false) _debounce!.cancel();
    _debounce = Timer(const Duration(milliseconds: 500), () {
      if (_searchQuery != _searchController.text) {
        setState(() {
          _searchQuery = _searchController.text;
          _resetAndFetch();
        });
      }
    });
  }

  Future<void> _fetchCategories() async {
    try {
      final response = await _courseService.fetchCategories();
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          setState(() {
            _categories = (data['data'] as List)
                .map((e) => Category.fromJson(e))
                .toList();
          });
        }
      }
    } catch (e) {
      debugPrint('Error fetching categories: $e');
    }
  }

  Future<void> _fetchCourses() async {
    if (_isLoading) return;
    setState(() {
      _isLoading = true;
    });

    try {
      // Convert duration ranges to min/max if needed, or pass strings and handle in service?
      // Service expects minDuration/maxDuration ints.
      // But UI allows multiple ranges. Backend supports min/max total.
      // For simplicity, we'll take the global min and max from selected ranges.
      int? minDur, maxDur;
      if (_selectedDurationRanges.isNotEmpty) {
        // Example ranges: '3-8', '8-14'
        int globalMin = 1000;
        int globalMax = 0;
        for (var range in _selectedDurationRanges) {
          final parts = range.split('-');
          if (parts.length == 2) {
            int min = int.tryParse(parts[0]) ?? 0;
            int max = int.tryParse(parts[1]) ?? 0;
            if (min < globalMin) globalMin = min;
            if (max > globalMax) globalMax = max;
          }
        }
        if (globalMin != 1000) minDur = globalMin;
        if (globalMax != 0) maxDur = globalMax;
      }

      final response = await _courseService.fetchCourses(
        page: _currentPage,
        search: _searchQuery,
        categoryIds: _selectedCategoryIds.toList(),
        minPrice: _priceRange.start > 0 ? _priceRange.start : null,
        maxPrice: _priceRange.end < 500 ? _priceRange.end : null,
        minDuration: minDur,
        maxDuration: maxDur,
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        // Pagination data is usually at root or under 'data' if using Resources, but simple paginate returns root
        // data['data'] is list, data['next_page_url'] etc.
        final List<dynamic> items = data['data'] ?? [];
        final List<Course> newCourses = items
            .map((e) => Course.fromJson(e))
            .toList();

        setState(() {
          if (_currentPage == 1) {
            _courses = newCourses;
          } else {
            _courses.addAll(newCourses);
          }
          _hasMore = data['next_page_url'] != null;
        });
      }
    } catch (e) {
      debugPrint('Error fetching courses: $e');
    } finally {
      setState(() {
        _isLoading = false;
        _isMoreLoading = false;
      });
    }
  }

  Future<void> _loadMoreCourses() async {
    if (_isMoreLoading) return;
    setState(() {
      _isMoreLoading = true;
      _currentPage++;
    });
    await _fetchCourses();
  }

  void _resetAndFetch() {
    setState(() {
      _currentPage = 1;
      _hasMore = true;
      _courses.clear();
    });
    _fetchCourses();
  }

  void _showFilterSheet(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (BuildContext context) => _FilterSheet(
        categories: _categories,
        selectedCategoryIds: _selectedCategoryIds,
        priceRange: _priceRange,
        selectedDurationRanges: _selectedDurationRanges,
        onApply: (selectedCats, price, durationRanges) {
          setState(() {
            _selectedCategoryIds = selectedCats;
            _priceRange = price;
            _selectedDurationRanges = durationRanges;
            _resetAndFetch();
          });
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.getBackgroundColor(context),
      body: SafeArea(
        bottom: false,
        child: Column(
          children: <Widget>[
            // Sticky Search Bar
            Container(
              color: AppTheme.getBackgroundColor(context),
              padding: const EdgeInsets.fromLTRB(20, 12, 20, 8),
              child: _SearchBar(
                controller: _searchController,
                onFilterTap: () => _showFilterSheet(context),
                filterActive:
                    _selectedCategoryIds.isNotEmpty ||
                    _selectedDurationRanges.isNotEmpty ||
                    _priceRange.start > 0 ||
                    _priceRange.end < 500,
              ),
            ),
            // Topic Chips (Part B3)
            Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: TopicChips(
                selectedTopic: _selectedTopic,
                onTopicSelected: (topic) {
                  setState(() => _selectedTopic = topic);
                },
              ),
            ),
            // Courses Grid
            Expanded(
              child: _isLoading && _courses.isEmpty
                  ? const CoursesSkeleton()
                  : _courses.isEmpty
                  ? Center(child: Text(context.l10n.noCoursesFound))
                  : CustomScrollView(
                      controller: _scrollController,
                      physics: const BouncingScrollPhysics(),
                      slivers: <Widget>[
                        SliverPadding(
                          padding: const EdgeInsets.fromLTRB(20, 0, 20, 140),
                          sliver: SliverGrid(
                            gridDelegate:
                                const SliverGridDelegateWithFixedCrossAxisCount(
                                  crossAxisCount: 2,
                                  crossAxisSpacing: 16,
                                  mainAxisSpacing: 20,
                                  childAspectRatio: 0.70,
                                ),
                            delegate: SliverChildBuilderDelegate((
                              BuildContext context,
                              int index,
                            ) {
                              final Course course = _courses[index];
                              return _CourseCard(course: course);
                            }, childCount: _courses.length),
                          ),
                        ),
                        if (_isMoreLoading)
                          const SliverToBoxAdapter(
                            child: Padding(
                              padding: EdgeInsets.all(16.0),
                              child: Center(child: CircularProgressIndicator()),
                            ),
                          ),
                      ],
                    ),
            ),
          ],
        ),
      ),
      extendBody: true,
    );
  }
}

class _SearchBar extends StatelessWidget {
  const _SearchBar({
    required this.onFilterTap,
    required this.controller,
    this.filterActive = false,
  });

  final VoidCallback onFilterTap;
  final TextEditingController controller;
  final bool filterActive;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 48,
      child: Row(
        children: <Widget>[
          Expanded(
            child: TextField(
              controller: controller,
              decoration: InputDecoration(
                filled: true,
                fillColor: AppTheme.getCardColor(context),
                hintText: context.l10n.search,
                hintStyle: TextStyle(
                  color: AppTheme.getTextColor(context).withValues(alpha: 0.65),
                  fontSize: 14,
                ),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(25),
                  borderSide: BorderSide.none,
                ),
                contentPadding: const EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 0,
                ).copyWith(right: 52),
                suffixIcon: Padding(
                  padding: const EdgeInsets.only(right: 6),
                  child: Container(
                    width: 36,
                    height: 36,
                    margin: const EdgeInsets.all(6),
                    decoration: const BoxDecoration(
                      color: AppTheme.primary,
                      shape: BoxShape.circle,
                    ),
                    child: const Center(
                      child: HugeIcon(
                        icon: HugeIcons.strokeRoundedSearch01,
                        size: 18,
                        color: Colors.white,
                      ),
                    ),
                  ),
                ),
                suffixIconConstraints: const BoxConstraints(
                  minWidth: 48,
                  minHeight: 48,
                ),
              ),
              style: TextStyle(
                color: AppTheme.getTextColor(context),
                fontSize: 14,
              ),
            ),
          ),
          const SizedBox(width: 12),
          GestureDetector(
            onTap: onFilterTap,
            child: Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(
                color: filterActive
                    ? AppTheme.primary.withValues(alpha: 0.2)
                    : AppTheme.primary.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(12),
                border: filterActive
                    ? Border.all(color: AppTheme.primary, width: 1.5)
                    : null,
              ),
              child: Center(
                child: HugeIcon(
                  icon: HugeIcons.strokeRoundedFilterHorizontal,
                  size: 20,
                  color: AppTheme.getTextColor(context),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _CourseCard extends StatelessWidget {
  const _CourseCard({required this.course});

  final Course course;

  @override
  Widget build(BuildContext context) {
    final settingsProvider = Provider.of<SettingsProvider>(context);
    final fira = context.fira;

    final double regularPrice = course.price ?? 0.0;
    final double? discounted = course.discountedPrice;
    final bool isFree = regularPrice == 0.0;
    final bool hasDiscount = !isFree && discounted != null && discounted < regularPrice;

    int discountPercent = 0;
    if (hasDiscount && regularPrice > 0) {
      discountPercent = (((regularPrice - discounted) / regularPrice) * 100).round();
    }

    final bool hasReviews = course.reviewsCount > 0 && (course.rating ?? 0.0) > 0;
    final String ratingText = hasReviews
        ? '${(course.rating!).toStringAsFixed(1)} (${course.reviewsCount})'
        : 'New course';

    final String level = (course.difficulty != null && course.difficulty!.isNotEmpty)
        ? course.difficulty!
        : 'All Levels';

    return GestureDetector(
      onTap: () {
        Navigator.of(context).push(
          MaterialPageRoute(
            builder: (context) => CourseDetailScreen(courseId: course.id),
          ),
        );
      },
      child: Container(
        decoration: BoxDecoration(
          color: fira.card,
          borderRadius: BorderRadius.circular(20.0),
          border: Border.all(color: fira.brandTint.withValues(alpha: 0.5)),
          boxShadow: [
            BoxShadow(
              color: fira.shadowColor,
              blurRadius: 10,
              offset: const Offset(0, 3),
            ),
          ],
        ),
        clipBehavior: Clip.antiAlias,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            // 16:10 Aspect Ratio Image Header with frosted tags
            AspectRatio(
              aspectRatio: 16 / 10,
              child: Stack(
                fit: StackFit.expand,
                children: <Widget>[
                  CourseThumbnailImage(
                    imageUrl: course.thumbnail,
                    courseId: course.id,
                    fit: BoxFit.cover,
                  ),

                  // Soft bottom gradient for legibility
                  Positioned.fill(
                    child: DecoratedBox(
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.topCenter,
                          end: Alignment.bottomCenter,
                          colors: [
                            Colors.transparent,
                            Colors.black.withValues(alpha: 0.45),
                          ],
                        ),
                      ),
                    ),
                  ),

                  // Level pill (frosted)
                  Positioned(
                    left: 8,
                    top: 8,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                      decoration: BoxDecoration(
                        color: Colors.black.withValues(alpha: 0.55),
                        borderRadius: BorderRadius.circular(999),
                        border: Border.all(color: Colors.white.withValues(alpha: 0.2)),
                      ),
                      child: Text(
                        level,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 9.0,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ),

                  // Discount Pill (Primary purple)
                  if (hasDiscount)
                    Positioned(
                      left: 8,
                      bottom: 8,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(
                          color: fira.brandPrimary,
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          '-$discountPercent%',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 9.0,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                    ),
                ],
              ),
            ),

            // Content details
            Expanded(
              child: Padding(
                padding: const EdgeInsets.all(10),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          course.title,
                          style: TextStyle(
                            color: fira.onSurface,
                            fontSize: 13,
                            fontWeight: FontWeight.w700,
                            height: 1.25,
                          ),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(height: 5),
                        Row(
                          children: [
                            HugeIcon(
                              icon: HugeIcons.strokeRoundedPlay,
                              size: 11,
                              color: fira.mutedText,
                            ),
                            const SizedBox(width: 4),
                            Text(
                              '${course.lessonsCount} lessons',
                              style: TextStyle(color: fira.mutedText, fontSize: 10),
                            ),
                            const SizedBox(width: 6),
                            Icon(Icons.star_rounded, size: 12, color: fira.brandPrimary),
                            const SizedBox(width: 2),
                            Text(
                              ratingText,
                              style: TextStyle(color: fira.mutedText, fontSize: 10),
                            ),
                          ],
                        ),
                      ],
                    ),

                    // Price Section with FittedBox to prevent any overflow
                    SizedBox(
                      height: 24,
                      child: FittedBox(
                        fit: BoxFit.scaleDown,
                        alignment: Alignment.centerLeft,
                        child: isFree
                            ? Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                decoration: BoxDecoration(
                                  color: fira.brandTint,
                                  borderRadius: BorderRadius.circular(6),
                                ),
                                child: Text(
                                  'Free',
                                  style: TextStyle(
                                    color: fira.brandPrimary,
                                    fontSize: 12,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              )
                            : Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Text(
                                    settingsProvider.formatPrice(hasDiscount ? discounted : regularPrice),
                                    style: TextStyle(
                                      color: fira.brandPrimary,
                                      fontSize: 14,
                                      fontWeight: FontWeight.w800,
                                    ),
                                  ),
                                  if (hasDiscount) ...[
                                    const SizedBox(width: 4),
                                    Text(
                                      settingsProvider.formatPrice(regularPrice),
                                      style: TextStyle(
                                        color: fira.mutedText,
                                        fontSize: 10,
                                        decoration: TextDecoration.lineThrough,
                                      ),
                                    ),
                                  ],
                                ],
                              ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _FilterSheet extends StatefulWidget {
  const _FilterSheet({
    required this.categories,
    required this.selectedCategoryIds,
    required this.priceRange,
    required this.selectedDurationRanges,
    required this.onApply,
  });

  final List<Category> categories;
  final Set<int> selectedCategoryIds;
  final RangeValues priceRange;
  final Set<String> selectedDurationRanges;
  final Function(Set<int>, RangeValues, Set<String>) onApply;

  @override
  State<_FilterSheet> createState() => _FilterSheetState();
}

class _FilterSheetState extends State<_FilterSheet> {
  late Set<int> _selectedCategoryIds;
  late RangeValues _priceRange;
  late Set<String> _selectedDurationRanges;

  @override
  void initState() {
    super.initState();
    _selectedCategoryIds = Set.from(widget.selectedCategoryIds);
    _priceRange = widget.priceRange;
    _selectedDurationRanges = Set.from(widget.selectedDurationRanges);
  }

  void _clearFilters() {
    setState(() {
      _selectedCategoryIds.clear();
      _selectedDurationRanges.clear();
      _priceRange = const RangeValues(0, 500);
    });
  }

  void _applyFilters() {
    widget.onApply(_selectedCategoryIds, _priceRange, _selectedDurationRanges);
    Navigator.of(context).pop();
  }

  @override
  Widget build(BuildContext context) {
    final settingsProvider = Provider.of<SettingsProvider>(context);
    return Container(
      height: MediaQuery.of(context).size.height * 0.85,
      decoration: const BoxDecoration(
        color: AppTheme.primary,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: Column(
        children: <Widget>[
          // Header
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 20, 20, 24),
            child: Row(
              children: <Widget>[
                IconButton(
                  icon: const HugeIcon(
                    icon: HugeIcons.strokeRoundedCancel01,
                    size: 24,
                    color: Colors.white,
                  ),
                  onPressed: () => Navigator.of(context).pop(),
                  padding: EdgeInsets.zero,
                  constraints: const BoxConstraints(),
                ),
                Expanded(
                  child: Text(
                    context.l10n.searchFilter,
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 20,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
                TextButton(
                  onPressed: _clearFilters,
                  child: const Text(
                    'Clear',
                    style: TextStyle(color: Colors.white),
                  ),
                ),
              ],
            ),
          ),
          // Content
          Expanded(
            child: Container(
              decoration: BoxDecoration(
                color: AppTheme.getCardColor(context),
                borderRadius: const BorderRadius.vertical(
                  top: Radius.circular(24),
                ),
              ),
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    // Categories Section
                    Text(
                      context.l10n.categories,
                      style: TextStyle(
                        color: AppTheme.getTextColor(context),
                        fontSize: 18,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 16),
                    if (widget.categories.isEmpty)
                      const Text('No categories available'),
                    Wrap(
                      spacing: 12,
                      runSpacing: 12,
                      children: widget.categories.map((cat) {
                        final isSelected = _selectedCategoryIds.contains(
                          cat.id,
                        );
                        return _FilterChip(
                          label: cat.name,
                          isSelected: isSelected,
                          onTap: () {
                            setState(() {
                              if (isSelected) {
                                _selectedCategoryIds.remove(cat.id);
                              } else {
                                _selectedCategoryIds.add(cat.id);
                              }
                            });
                          },
                        );
                      }).toList(),
                    ),
                    const SizedBox(height: 32),
                    // Price Section
                    Text(
                      context.l10n.price,
                      style: TextStyle(
                        color: AppTheme.getTextColor(context),
                        fontSize: 18,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 16),
                    RangeSlider(
                      values: _priceRange,
                      min: 0,
                      max: 500,
                      divisions: 50,
                      activeColor: AppTheme.getTextColor(context),
                      inactiveColor: AppTheme.getTextColor(
                        context,
                      ).withValues(alpha: 0.2),
                      onChanged: (RangeValues values) {
                        setState(() {
                          _priceRange = values;
                        });
                      },
                    ),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: <Widget>[
                        Text(
                          settingsProvider.formatPrice(
                            _priceRange.start.round(),
                          ),
                          style: TextStyle(
                            color: AppTheme.getTextColor(context),
                            fontSize: 16,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        Text(
                          settingsProvider.formatPrice(_priceRange.end.round()),
                          style: TextStyle(
                            color: AppTheme.getTextColor(context),
                            fontSize: 16,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 32),
                    // Duration Section
                    Text(
                      context.l10n.duration,
                      style: TextStyle(
                        color: AppTheme.getTextColor(context),
                        fontSize: 18,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 16),
                    Wrap(
                      spacing: 12,
                      runSpacing: 12,
                      children: ['3-8', '8-14', '14-20', '20-24', '24-30'].map((
                        range,
                      ) {
                        final isSelected = _selectedDurationRanges.contains(
                          range,
                        );
                        return _FilterChip(
                          label: '$range Hours',
                          isSelected: isSelected,
                          onTap: () {
                            setState(() {
                              if (isSelected) {
                                _selectedDurationRanges.remove(range);
                              } else {
                                _selectedDurationRanges.add(range);
                              }
                            });
                          },
                        );
                      }).toList(),
                    ),
                    const SizedBox(height: 48),
                    // Apply Button
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: _applyFilters,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppTheme.primary,
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(30),
                          ),
                        ),
                        child: const Text(
                          'Apply Filter',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 20),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _FilterChip extends StatelessWidget {
  const _FilterChip({
    required this.label,
    required this.isSelected,
    required this.onTap,
  });

  final String label;
  final bool isSelected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected
              ? AppTheme.primary.withValues(alpha: 0.1)
              : AppTheme.getSoftGray150(context),
          borderRadius: BorderRadius.circular(20),
        ),
        child: Text(
          label,
          style: TextStyle(
            color: isSelected
                ? AppTheme.primary
                : AppTheme.getTextColor(context).withValues(alpha: 0.6),
            fontSize: 14,
            fontWeight: isSelected ? FontWeight.w600 : FontWeight.w500,
          ),
        ),
      ),
    );
  }
}
