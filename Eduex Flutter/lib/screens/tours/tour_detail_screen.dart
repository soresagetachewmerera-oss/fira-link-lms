import 'dart:convert';
import 'package:flutter/material.dart';
import '../../models/tour.dart';
import '../../services/tour_service.dart';
import '../../theme/app_theme.dart';
import 'tour_booking_screen.dart';

class TourDetailScreen extends StatefulWidget {
  const TourDetailScreen({required this.tourId, super.key});

  final int tourId;

  @override
  State<TourDetailScreen> createState() => _TourDetailScreenState();
}

class _TourDetailScreenState extends State<TourDetailScreen> {
  final TourService _tourService = TourService();
  Tour? _tour;
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _fetchDetails();
  }

  Future<void> _fetchDetails() async {
    try {
      final res = await _tourService.fetchTourDetails(widget.tourId);
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        setState(() {
          _tour = Tour.fromJson(data);
          _isLoading = false;
        });
      } else {
        setState(() {
          _error = 'Failed to load expedition details';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final primaryColor = AppTheme.getPrimaryColor(context);

    if (_isLoading) {
      return Scaffold(
        appBar: AppBar(elevation: 0),
        body: const Center(child: CircularProgressIndicator()),
      );
    }

    if (_error != null || _tour == null) {
      return Scaffold(
        appBar: AppBar(elevation: 0),
        body: Center(child: Text(_error ?? 'Tour not found')),
      );
    }

    final tour = _tour!;

    return Scaffold(
      backgroundColor: AppTheme.getBackgroundColor(context),
      appBar: AppBar(
        title: const Text('Expedition Details', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
        elevation: 0,
      ),
      bottomNavigationBar: Container(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
        decoration: BoxDecoration(
          color: AppTheme.getCardColor(context),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.05),
              blurRadius: 10,
              offset: const Offset(0, -3),
            ),
          ],
        ),
        child: SafeArea(
          child: Row(
            children: [
              Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Price per person', style: TextStyle(fontSize: 10.5, color: Colors.grey)),
                  Text(
                    tour.price > 0 ? '${tour.currency} ${tour.price.toStringAsFixed(2)}' : 'Free Entry',
                    style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700, color: primaryColor),
                  ),
                ],
              ),
              const SizedBox(width: 16),
              Expanded(
                child: ElevatedButton(
                  onPressed: tour.remainingSeats > 0
                      ? () {
                          Navigator.of(context).push(
                            MaterialPageRoute(
                              builder: (context) => TourBookingScreen(tour: tour),
                            ),
                          );
                        }
                      : null,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: primaryColor,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: Text(
                    tour.remainingSeats > 0 ? 'Book Expedition' : 'Fully Booked',
                    style: const TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.w700),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
      body: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Cover Image
            Container(
              height: 220,
              width: double.infinity,
              color: Colors.grey.shade200,
              child: tour.featuredImage != null
                  ? Image.network(
                      tour.featuredImage!,
                      fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => const Icon(Icons.image, size: 50, color: Colors.grey),
                    )
                  : const Icon(Icons.image, size: 50, color: Colors.grey),
            ),

            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Badges
                  Row(
                    children: [
                      if (tour.category != null)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: primaryColor.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            tour.category!,
                            style: TextStyle(color: primaryColor, fontSize: 11, fontWeight: FontWeight.w600),
                          ),
                        ),
                      const SizedBox(width: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.grey.shade200,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          tour.difficultyLevel,
                          style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),

                  // Title (Standardized & Proportionate)
                  Text(
                    tour.title,
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w700,
                      height: 1.3,
                      letterSpacing: -0.2,
                    ),
                  ),
                  const SizedBox(height: 8),

                  // Destination & Schedule
                  Row(
                    children: [
                      Icon(Icons.location_on, size: 14, color: primaryColor),
                      const SizedBox(width: 4),
                      Expanded(
                        child: Text(
                          tour.destination,
                          style: TextStyle(fontSize: 12.5, color: Colors.grey.shade600, fontWeight: FontWeight.w500),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),

                  // Quick Stats Row
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: AppTheme.getCardColor(context),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.grey.shade200),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceAround,
                      children: [
                        _QuickStatItem(icon: Icons.schedule, label: 'Duration', value: '${tour.durationDays} Days'),
                        _QuickStatItem(icon: Icons.group, label: 'Seats Left', value: '${tour.remainingSeats} of ${tour.totalSeats}'),
                        _QuickStatItem(icon: Icons.verified, label: 'Difficulty', value: tour.difficultyLevel),
                      ],
                    ),
                  ),
                  const SizedBox(height: 20),

                  // Overview Narrative
                  const Text('Overview', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 8),
                  Text(
                    tour.description,
                    style: TextStyle(fontSize: 13, height: 1.55, color: Colors.grey.shade700),
                  ),
                  const SizedBox(height: 20),

                  // Itinerary
                  if (tour.itinerary != null && tour.itinerary!.isNotEmpty) ...[
                    const Text('Expedition Itinerary', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
                    const SizedBox(height: 10),
                    ...tour.itinerary!.map((item) {
                      final map = item as Map<String, dynamic>;
                      return Container(
                        margin: const EdgeInsets.only(bottom: 10),
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: AppTheme.getCardColor(context),
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(color: Colors.grey.shade200),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Day ${map["day"] ?? 1}: ${map["title"] ?? ""}',
                              style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700),
                            ),
                            if (map["description"] != null) ...[
                              const SizedBox(height: 4),
                              Text(
                                map["description"]!,
                                style: TextStyle(fontSize: 12, height: 1.45, color: Colors.grey.shade600),
                              ),
                            ],
                          ],
                        ),
                      );
                    }),
                    const SizedBox(height: 16),
                  ],

                  // Inclusions
                  if (tour.includedServices != null && tour.includedServices!.isNotEmpty) ...[
                    const Text('What\'s Included', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
                    const SizedBox(height: 8),
                    ...tour.includedServices!.map((inc) {
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 6),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Icon(Icons.check_circle, size: 14, color: Colors.green),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(inc.toString(), style: TextStyle(fontSize: 12.5, color: Colors.grey.shade700)),
                            ),
                          ],
                        ),
                      );
                    }),
                    const SizedBox(height: 16),
                  ],

                  // Meeting Point
                  if (tour.meetingPoint != null) ...[
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: primaryColor.withValues(alpha: 0.05),
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: primaryColor.withValues(alpha: 0.2)),
                      ),
                      child: Row(
                        children: [
                          Icon(Icons.flag, size: 18, color: primaryColor),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text('Meeting Point', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700)),
                                Text(tour.meetingPoint!, style: TextStyle(fontSize: 12, color: Colors.grey.shade700)),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 24),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _QuickStatItem extends StatelessWidget {
  const _QuickStatItem({required this.icon, required this.label, required this.value});

  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Icon(icon, size: 18, color: AppTheme.getPrimaryColor(context)),
        const SizedBox(height: 4),
        Text(label, style: TextStyle(fontSize: 10, color: Colors.grey.shade500)),
        const SizedBox(height: 2),
        Text(value, style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700)),
      ],
    );
  }
}
