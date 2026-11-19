import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:hugeicons/hugeicons.dart';

import '../../models/tour_booking.dart';
import '../../services/tour_service.dart';
import '../../theme/fira_colors.dart';
import '../../utils/currency_formatter.dart';
import 'tour_booking_confirmation_screen.dart';
import '../../l10n/app_localizations.dart';
import '../../widgets/fira_error_state.dart';

class MyTourBookingsScreen extends StatefulWidget {
  const MyTourBookingsScreen({super.key});

  @override
  State<MyTourBookingsScreen> createState() => _MyTourBookingsScreenState();
}

class _MyTourBookingsScreenState extends State<MyTourBookingsScreen> {
  final TourService _tourService = TourService();
  List<TourBooking> _bookings = [];
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _fetchBookings();
  }

  Future<void> _fetchBookings() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final res = await _tourService.getMyTourBookings();
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        final List<dynamic> list = data['data'] ?? [];
        if (!mounted) return;
        setState(() {
          _bookings = list.map((item) => TourBooking.fromJson(item as Map<String, dynamic>)).toList();
          _isLoading = false;
        });
      } else {
        if (!mounted) return;
        setState(() {
          _error = 'Failed to load bookings.';
          _isLoading = false;
        });
      }
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  Future<void> _cancelBooking(TourBooking booking) async {
    final fira = context.fira;
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: fira.card,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Text(
          context.l10n.cancelReservation,
          style: TextStyle(color: fira.onSurface, fontWeight: FontWeight.bold, fontSize: 17),
        ),
        content: Text(
          'Are you sure you want to cancel booking ${booking.reference}?',
          style: TextStyle(color: fira.mutedText, fontSize: 13.5),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: Text(context.l10n.cancel, style: TextStyle(color: fira.mutedText)),
          ),
          OutlinedButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            style: OutlinedButton.styleFrom(
              side: BorderSide(color: fira.danger.withValues(alpha: 0.6)),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            child: Text(context.l10n.confirm, style: TextStyle(color: fira.danger)),
          ),
        ],
      ),
    );

    if (confirm == true) {
      try {
        final res = await _tourService.cancelTourBooking(booking.id);
        if (res.statusCode == 200 && mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(context.l10n.bookingCancelled),
              backgroundColor: fira.brandDeep,
            ),
          );
          _fetchBookings();
        }
      } catch (e) {
        if (mounted) {
          showFiraActionError(context, e);
        }
      }
    }
  }

  bool _isCancelled(TourBooking b) => b.status.toLowerCase() == 'cancelled';

  bool _isPast(TourBooking b) {
    if (_isCancelled(b)) return false;
    return b.status.toLowerCase() == 'completed';
  }

  bool _isUpcoming(TourBooking b) => !_isCancelled(b) && !_isPast(b);

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;

    final upcoming = _bookings.where(_isUpcoming).toList();
    final past = _bookings.where(_isPast).toList();
    final cancelled = _bookings.where(_isCancelled).toList();

    return DefaultTabController(
      length: 3,
      child: Scaffold(
        backgroundColor: fira.surface,
        appBar: AppBar(
          backgroundColor: fira.surface,
          elevation: 0,
          leading: IconButton(
              tooltip: MaterialLocalizations.of(context).backButtonTooltip,
              icon: Icon(Icons.arrow_back_ios_new, size: 18, color: fira.onSurface),
            onPressed: () => Navigator.of(context).pop(),
          ),
          title: Text(
            context.l10n.myTourBookings,
            style: TextStyle(
              fontSize: 17,
              fontWeight: FontWeight.w700,
              color: fira.onSurface,
            ),
          ),
          bottom: TabBar(
            indicatorColor: fira.brandPrimary,
            indicatorWeight: 3,
            labelColor: fira.brandPrimary,
            unselectedLabelColor: fira.mutedText,
            labelStyle: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13),
            unselectedLabelStyle: const TextStyle(fontWeight: FontWeight.w500, fontSize: 13),
            tabs: [
              Tab(text: 'Upcoming (${upcoming.length})'),
              Tab(text: 'Past (${past.length})'),
              Tab(text: 'Cancelled (${cancelled.length})'),
            ],
          ),
        ),
        body: _isLoading
            ? Center(child: CircularProgressIndicator(color: fira.brandPrimary))
            : _error != null
                ? Center(
                    child: Padding(
                      padding: const EdgeInsets.all(24.0),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.error_outline_rounded, size: 48, color: fira.danger),
                          const SizedBox(height: 12),
                          Text(_error!, style: TextStyle(color: fira.onSurface, fontSize: 14)),
                          const SizedBox(height: 16),
                          ElevatedButton(
                            onPressed: _fetchBookings,
                            style: ElevatedButton.styleFrom(
                              backgroundColor: fira.brandPrimary,
                              foregroundColor: Colors.white,
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                            ),
                            child: const Text('Try Again'),
                          ),
                        ],
                      ),
                    ),
                  )
                : TabBarView(
                    children: [
                      _buildBookingsList(upcoming, emptyMessage: context.l10n.noTourBookings, emptySubtitle: context.l10n.noTourBookingsSubtitle),
                      _buildBookingsList(past, emptyMessage: 'No past tour expeditions', emptySubtitle: 'Your completed tour records will appear here.'),
                      _buildBookingsList(cancelled, emptyMessage: 'No cancelled bookings', emptySubtitle: 'You have no cancelled tour reservations.'),
                    ],
                  ),
      ),
    );
  }

  Widget _buildBookingsList(List<TourBooking> items, {required String emptyMessage, required String emptySubtitle}) {
    final fira = context.fira;

    if (items.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 32),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                width: 72,
                height: 72,
                decoration: BoxDecoration(
                  color: fira.brandTint,
                  shape: BoxShape.circle,
                ),
                child: Center(
                  child: HugeIcon(
                    icon: HugeIcons.strokeRoundedRoute01,
                    size: 32,
                    color: fira.brandPrimary,
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Text(
                emptyMessage,
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700, color: fira.onSurface),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 6),
              Text(
                emptySubtitle,
                style: TextStyle(color: fira.mutedText, fontSize: 12.5),
                textAlign: TextAlign.center,
              ),
            ],
          ),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _fetchBookings,
      color: fira.brandPrimary,
      child: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: items.length,
        separatorBuilder: (context, index) => const SizedBox(height: 14),
        itemBuilder: (context, index) {
          final b = items[index];
          return _buildBookingCard(b);
        },
      ),
    );
  }

  Widget _buildBookingCard(TourBooking b) {
    final fira = context.fira;
    final isCancelled = _isCancelled(b);
    final isPast = _isPast(b);

    Color statusBg;
    Color statusText;
    final String statusUpper = b.status.toUpperCase();

    if (isCancelled) {
      statusBg = fira.danger.withValues(alpha: 0.12);
      statusText = fira.danger;
    } else if (b.status.toLowerCase() == 'confirmed') {
      statusBg = fira.success.withValues(alpha: 0.12);
      statusText = fira.success;
    } else {
      // Pending or other: use brandTint + brandPrimary (NEVER amber or green for pending)
      statusBg = fira.brandTint;
      statusText = fira.brandPrimary;
    }

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: fira.card,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: fira.brandTint.withValues(alpha: 0.5)),
        boxShadow: [
          BoxShadow(
            color: fira.shadowColor,
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header with reference & status chip
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                b.reference,
                style: TextStyle(
                  fontWeight: FontWeight.w800,
                  fontSize: 13,
                  color: fira.brandPrimary,
                  letterSpacing: 0.5,
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3.5),
                decoration: BoxDecoration(
                  color: statusBg,
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  statusUpper,
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w800,
                    color: statusText,
                    letterSpacing: 0.5,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),

          // Thumbnail + Title + Info
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Tour thumbnail
              ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: Container(
                  width: 68,
                  height: 68,
                  color: fira.brandTint,
                  child: b.featuredImage != null
                      ? Image.network(
                          b.featuredImage!,
                          fit: BoxFit.cover,
                          errorBuilder: (context, error, stackTrace) => Icon(
                            Icons.landscape_rounded,
                            color: fira.brandPrimary,
                            size: 28,
                          ),
                        )
                      : Icon(
                          Icons.landscape_rounded,
                          color: fira.brandPrimary,
                          size: 28,
                        ),
                ),
              ),
              const SizedBox(width: 12),

              // Title + destination + passengers
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      b.tourTitle,
                      style: TextStyle(
                        fontWeight: FontWeight.w700,
                        fontSize: 14.5,
                        color: fira.onSurface,
                        height: 1.25,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 6),
                    Row(
                      children: [
                        HugeIcon(
                          icon: HugeIcons.strokeRoundedCalendar03,
                          size: 13,
                          color: fira.mutedText,
                        ),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            b.scheduleDateFormatted ?? 'Date TBA',
                            style: TextStyle(fontSize: 11.5, color: fira.mutedText),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        HugeIcon(
                          icon: HugeIcons.strokeRoundedUserGroup,
                          size: 13,
                          color: fira.mutedText,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          '${b.participants} Passengers',
                          style: TextStyle(fontSize: 11.5, color: fira.mutedText),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),

          // Divider
          Divider(color: fira.brandTint.withValues(alpha: 0.5), height: 1),
          const SizedBox(height: 12),

          // Price & Actions
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'TOTAL AMOUNT',
                    style: TextStyle(
                      fontSize: 9.5,
                      fontWeight: FontWeight.w700,
                      color: fira.mutedText,
                      letterSpacing: 0.5,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    FiraCurrencyFormatter.format(b.totalAmount, currency: b.currency),
                    style: TextStyle(
                      fontWeight: FontWeight.w800,
                      fontSize: 14.5,
                      color: fira.brandPrimary,
                    ),
                  ),
                ],
              ),
              Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  if (!isCancelled && !isPast) ...[
                    OutlinedButton(
                      onPressed: () => _cancelBooking(b),
                      style: OutlinedButton.styleFrom(
                        side: BorderSide(color: fira.danger.withValues(alpha: 0.45)),
                        foregroundColor: fira.danger,
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                      child: const Text('Cancel', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                    ),
                    const SizedBox(width: 8),
                  ],
                  ElevatedButton(
                    onPressed: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (context) => TourBookingConfirmationScreen(
                            bookingId: b.id,
                            reference: b.reference,
                            tourTitle: b.tourTitle,
                            participants: b.participants,
                            totalAmount: b.totalAmount,
                            currency: b.currency,
                          ),
                        ),
                      );
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: fira.brandPrimary,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      elevation: 0,
                    ),
                    child: const Text('View Pass', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                  ),
                ],
              ),
            ],
          ),
        ],
      ),
    );
  }
}
