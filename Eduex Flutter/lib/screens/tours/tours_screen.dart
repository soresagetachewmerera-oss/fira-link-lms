import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:hugeicons/hugeicons.dart';

import '../../models/tour.dart';
import '../../services/tour_service.dart';
import '../../theme/app_theme.dart';
import '../../widgets/bottom_nav_bar.dart';
import 'tour_detail_screen.dart';
import 'my_tour_bookings_screen.dart';

class ToursScreen extends StatefulWidget {
  const ToursScreen({super.key});

  @override
  State<ToursScreen> createState() => _ToursScreenState();
}

class _ToursScreenState extends State<ToursScreen> {
  final TourService _tourService = TourService();
  final TextEditingController _searchController = TextEditingController();

  List<Tour> _tours = [];
  bool _isLoading = true;
  String? _selectedCategory;
  String? _errorMessage;

  final List<String> _categories = [
    'All',
    'Origin Expedition',
    'Heritage & Ecology',
    'Export & Logistics',
  ];

  @override
  void initState() {
    super.initState();
    _fetchTours();
  }

  Future<void> _fetchTours() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final categoryFilter = (_selectedCategory == 'All' || _selectedCategory == null)
          ? null
          : _selectedCategory;
      final response = await _tourService.fetchTours(
        search: _searchController.text.trim(),
        category: categoryFilter,
      );

      if (response.statusCode == 200) {
        final Map<String, dynamic> data = jsonDecode(response.body);
        final List<dynamic> list = data['data'] ?? [];
        setState(() {
          _tours = list.map((item) => Tour.fromJson(item as Map<String, dynamic>)).toList();
          _isLoading = false;
        });
      } else {
        setState(() {
          _errorMessage = 'Failed to load tours.';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _errorMessage = 'Network error: $e';
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final primaryColor = AppTheme.getPrimaryColor(context);

    return Scaffold(
      backgroundColor: AppTheme.getBackgroundColor(context),
      appBar: AppBar(
        title: const Text(
          'Tours & Expeditions',
          style: TextStyle(fontWeight: FontWeight.w700, fontSize: 17),
        ),
        centerTitle: false,
        actions: [
          IconButton(
            icon: const HugeIcon(icon: HugeIcons.strokeRoundedTicket01, size: 22, color: Colors.black87),
            tooltip: 'My Bookings',
            onPressed: () {
              Navigator.of(context).push(
                MaterialPageRoute(builder: (context) => const MyTourBookingsScreen()),
              );
            },
          ),
        ],
        elevation: 0,
        backgroundColor: AppTheme.getCardColor(context),
      ),
      bottomNavigationBar: const AppBottomNavBar(currentTab: BottomNavTab.tours),
      body: Column(
        children: [
          // Search & Filter Header
          Container(
            color: AppTheme.getCardColor(context),
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
            child: Column(
              children: [
                // Search Field
                TextField(
                  controller: _searchController,
                  onSubmitted: (_) => _fetchTours(),
                  decoration: InputDecoration(
                    hintText: 'Search destinations, tours...',
                    hintStyle: TextStyle(fontSize: 13, color: Colors.grey.shade500),
                    prefixIcon: const Icon(Icons.search, size: 20),
                    suffixIcon: _searchController.text.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear, size: 18),
                            onPressed: () {
                              _searchController.clear();
                              _fetchTours();
                            },
                          )
                        : null,
                    filled: true,
                    fillColor: Theme.of(context).brightness == Brightness.dark
                        ? Colors.grey.shade900
                        : Colors.grey.shade100,
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide.none,
                    ),
                  ),
                ),
                const SizedBox(height: 10),

                // Category Chips
                SizedBox(
                  height: 34,
                  child: ListView.separated(
                    scrollDirection: Axis.horizontal,
                    itemCount: _categories.length,
                    separatorBuilder: (_, __) => const SizedBox(width: 8),
                    itemBuilder: (context, index) {
                      final cat = _categories[index];
                      final isSelected = (_selectedCategory == null && cat == 'All') ||
                          _selectedCategory == cat;

                      return GestureDetector(
                        onTap: () {
                          setState(() {
                            _selectedCategory = cat == 'All' ? null : cat;
                          });
                          _fetchTours();
                        },
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                          decoration: BoxDecoration(
                            color: isSelected ? primaryColor : Colors.transparent,
                            borderRadius: BorderRadius.circular(20),
                            border: Border.all(
                              color: isSelected ? primaryColor : Colors.grey.shade300,
                            ),
                          ),
                          child: Text(
                            cat,
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: isSelected ? FontWeight.w600 : FontWeight.w500,
                              color: isSelected ? Colors.white : AppTheme.getTextColor(context),
                            ),
                          ),
                        ),
                      );
                    },
                  ),
                ),
              ],
            ),
          ),

          // Content List
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _errorMessage != null
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Text(_errorMessage!, style: const TextStyle(color: Colors.red)),
                            const SizedBox(height: 12),
                            ElevatedButton(
                              onPressed: _fetchTours,
                              child: const Text('Try Again'),
                            ),
                          ],
                        ),
                      )
                    : _tours.isEmpty
                        ? Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                HugeIcon(icon: HugeIcons.strokeRoundedRoute01, size: 48, color: primaryColor),
                                const SizedBox(height: 12),
                                const Text(
                                  'No Expeditions Found',
                                  style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16),
                                ),
                                const SizedBox(height: 4),
                                const Text(
                                  'Check back soon or adjust search filters.',
                                  style: TextStyle(color: Colors.grey, fontSize: 13),
                                ),
                              ],
                            ),
                          )
                        : RefreshIndicator(
                            onRefresh: _fetchTours,
                            child: ListView.separated(
                              padding: const EdgeInsets.all(16),
                              itemCount: _tours.length,
                              separatorBuilder: (_, __) => const SizedBox(height: 16),
                              itemBuilder: (context, index) {
                                final tour = _tours[index];
                                return _TourCard(tour: tour);
                              },
                            ),
                          ),
          ),
        ],
      ),
    );
  }
}

class _TourCard extends StatelessWidget {
  const _TourCard({required this.tour});

  final Tour tour;

  @override
  Widget build(BuildContext context) {
    final primaryColor = AppTheme.getPrimaryColor(context);

    return GestureDetector(
      onTap: () {
        Navigator.of(context).push(
          MaterialPageRoute(builder: (context) => TourDetailScreen(tourId: tour.id)),
        );
      },
      child: Container(
        decoration: BoxDecoration(
          color: AppTheme.getCardColor(context),
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.04),
              blurRadius: 10,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        clipBehavior: Clip.antiAlias,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Cover Image with Badges
            Stack(
              children: [
                Container(
                  height: 160,
                  width: double.infinity,
                  color: Colors.grey.shade200,
                  child: tour.featuredImage != null
                      ? Image.network(
                          tour.featuredImage!,
                          fit: BoxFit.cover,
                          errorBuilder: (_, __, ___) => const Center(
                            child: Icon(Icons.broken_image, color: Colors.grey),
                          ),
                        )
                      : const Center(child: Icon(Icons.image, color: Colors.grey)),
                ),
                // Difficulty Badge
                Positioned(
                  top: 12,
                  right: 12,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: Colors.black.withValues(alpha: 0.7),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      tour.difficultyLevel,
                      style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w600),
                    ),
                  ),
                ),
                // Duration Pill
                Positioned(
                  bottom: 12,
                  left: 12,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(12),
                      boxShadow: [
                        BoxShadow(color: Colors.black.withValues(alpha: 0.1), blurRadius: 4),
                      ],
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.schedule, size: 12, color: Colors.grey),
                        const SizedBox(width: 4),
                        Text(
                          '${tour.durationDays} ${tour.durationDays > 1 ? "Days" : "Day"}',
                          style: const TextStyle(color: Colors.black87, fontSize: 11, fontWeight: FontWeight.w700),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),

            // Card Body
            Padding(
              padding: const EdgeInsets.all(14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Destination
                  Row(
                    children: [
                      Icon(Icons.location_on, size: 13, color: primaryColor),
                      const SizedBox(width: 4),
                      Expanded(
                        child: Text(
                          tour.destination,
                          style: TextStyle(
                            fontSize: 11.5,
                            fontWeight: FontWeight.w600,
                            color: Colors.grey.shade600,
                          ),
                          maxLines: 1,
                          overflow: TextResolver.ellipsisText,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),

                  // Title (Compact & Balanced)
                  Text(
                    tour.title,
                    style: const TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w700,
                      height: 1.3,
                      letterSpacing: -0.2,
                    ),
                    maxLines: 2,
                    overflow: TextResolver.ellipsisText,
                  ),
                  const SizedBox(height: 10),

                  // Footer: Price & Seats
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Per person', style: TextStyle(fontSize: 10.5, color: Colors.grey.shade500)),
                          Text(
                            tour.price > 0
                                ? '${tour.currency} ${tour.price.toStringAsFixed(2)}'
                                : 'Free Entry',
                            style: TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w700,
                              color: primaryColor,
                            ),
                          ),
                        ],
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                        decoration: BoxDecoration(
                          color: tour.remainingSeats > 0 ? Colors.green.withValues(alpha: 0.1) : Colors.red.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          tour.remainingSeats > 0 ? '${tour.remainingSeats} seats left' : 'Sold Out',
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                            color: tour.remainingSeats > 0 ? Colors.green.shade700 : Colors.red.shade700,
                          ),
                        ),
                      ),
                    ],
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

class TextResolver {
  static const TextOverflow ellipsisText = TextOverflow.ellipsis;
}
