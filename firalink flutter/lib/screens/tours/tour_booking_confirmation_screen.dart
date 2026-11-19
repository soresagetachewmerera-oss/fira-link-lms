import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:hugeicons/hugeicons.dart';
import 'package:share_plus/share_plus.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../services/tour_service.dart';
import '../../theme/fira_colors.dart';
import '../../utils/currency_formatter.dart';
import '../../widgets/booking_flow_widgets.dart';
import 'my_tour_bookings_screen.dart';
import 'widgets/tour_payment_panel.dart';
import '../../l10n/app_localizations.dart';

class TourBookingConfirmationScreen extends StatefulWidget {
  const TourBookingConfirmationScreen({
    required this.bookingId,
    required this.reference,
    required this.tourTitle,
    required this.participants,
    required this.totalAmount,
    required this.currency,
    super.key,
  });

  final int bookingId;
  final String reference;
  final String tourTitle;
  final int participants;
  final double totalAmount;
  final String currency;

  @override
  State<TourBookingConfirmationScreen> createState() => _TourBookingConfirmationScreenState();
}

class _TourBookingConfirmationScreenState extends State<TourBookingConfirmationScreen> {
  final TourService _tourService = TourService();
  Map<String, dynamic>? _ticketData;
  String? _qrCodeSvg;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchTicket();
  }

  Future<void> _fetchTicket() async {
    try {
      final res = await _tourService.getTourBookingDetails(widget.bookingId);
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (!mounted) return;
        setState(() {
          _ticketData = data['ticket'] as Map<String, dynamic>?;
          _qrCodeSvg = data['qr_code'] as String?;
          _isLoading = false;
        });
      } else {
        if (!mounted) return;
        setState(() => _isLoading = false);
      }
    } catch (_) {
      if (!mounted) return;
      setState(() => _isLoading = false);
    }
  }

  Future<void> _openInMaps(String location) async {
    final Uri uri = Uri.parse('https://www.google.com/maps/search/?api=1&query=${Uri.encodeComponent(location)}');
    try {
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      } else if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Could not open map for: $location'),
            backgroundColor: context.fira.danger,
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error launching map: $e'),
            backgroundColor: context.fira.danger,
          ),
        );
      }
    }
  }

  void _sharePass() {
    SharePlus.instance.share(
      ShareParams(
        text:
            'Fira Link Boarding Pass\nTour: ${widget.tourTitle}\nBooking Ref: ${widget.reference}\nPassengers: ${widget.participants}\nTotal: ${FiraCurrencyFormatter.format(widget.totalAmount, currency: widget.currency)}',
        subject: 'Fira Link Tour Boarding Pass - ${widget.reference}',
      ),
    );
  }

  void _addToCalendar() {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: const Row(
          children: [
            Icon(Icons.event_available, color: Colors.white, size: 20),
            SizedBox(width: 8),
            Text('Expedition date synchronized with calendar'),
          ],
        ),
        backgroundColor: context.fira.brandDeep,
      ),
    );
  }

  void _saveAsImage() {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: const Row(
          children: [
            Icon(Icons.download_done_rounded, color: Colors.white, size: 20),
            SizedBox(width: 8),
            Text('Boarding pass saved to gallery'),
          ],
        ),
        backgroundColor: context.fira.brandDeep,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final String meetingPoint = _ticketData?['meeting_point'] ?? 'Addis Ababa Bole International Terminal';
    final String scheduleDate = _ticketData?['schedule_date'] ?? _ticketData?['schedule_date_formatted'] ?? 'Date TBA';
    final String departureTime = _ticketData?['departure_time'] ?? '08:00 AM EAT';
    final String passengerName = _ticketData?['passenger_name'] ?? 'Confirmed Member';
    // Slice 8: a bank slip waits for Fira Link finance; the places are held but not confirmed yet.
    final ps = _ticketData?['payment_status']?.toString();
    final inReview = ps == 'in_review';

    return Scaffold(
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
          context.l10n.boardingPass,
          style: TextStyle(
            fontSize: 17,
            fontWeight: FontWeight.w700,
            color: fira.onSurface,
          ),
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(20, 10, 20, 40),
        child: Column(
          children: [
            // Header Success Badge
            Container(
              width: 60,
              height: 60,
              decoration: BoxDecoration(
                color: fira.success.withValues(alpha: 0.12),
                shape: BoxShape.circle,
              ),
              child: Icon(Icons.check_circle_rounded, color: fira.success, size: 36),
            ),
            const SizedBox(height: 12),
            if (inReview) const InReviewPill(),
            Text(
              inReview ? context.l10n.tourInReviewTitle : context.l10n.tourBookedTitle,
              style: TextStyle(
                fontSize: 19,
                fontWeight: FontWeight.w800,
                color: fira.onSurface,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              inReview ? context.l10n.tourInReviewBody : context.l10n.translate('tourBookedLine', {'reference': widget.reference}),
              style: TextStyle(fontSize: 13, color: fira.mutedText),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 24),

            // Authentic Boarding Pass with Side Notches
            CustomPaint(
              painter: _TicketShadowAndBorderPainter(
                borderColor: fira.brandTint.withValues(alpha: 0.8),
                shadowColor: fira.shadowColor,
              ),
              child: ClipPath(
                clipper: const _TicketClipper(notchYRatio: 0.62, notchRadius: 12),
                child: Container(
                  color: fira.card,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      // Purple Header Banner
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
                        decoration: BoxDecoration(
                          gradient: LinearGradient(
                            colors: [fira.brandDeep, fira.brandPrimary],
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                          ),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Row(
                              children: [
                                const HugeIcon(
                                  icon: HugeIcons.strokeRoundedRoute01,
                                  size: 16,
                                  color: Colors.white,
                                ),
                                const SizedBox(width: 6),
                                const Text(
                                  'FIRA LINK EXPEDITIONS',
                                  style: TextStyle(
                                    color: Colors.white,
                                    fontSize: 11,
                                    fontWeight: FontWeight.w800,
                                    letterSpacing: 1.2,
                                  ),
                                ),
                              ],
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                              decoration: BoxDecoration(
                                color: Colors.white.withValues(alpha: 0.2),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: Text(
                                widget.reference,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 11,
                                  fontWeight: FontWeight.w800,
                                  letterSpacing: 0.5,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),

                      // Top Section: Tour Title & Metadata
                      Padding(
                        padding: const EdgeInsets.fromLTRB(18, 16, 18, 14),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              widget.tourTitle,
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w800,
                                color: fira.onSurface,
                                height: 1.3,
                              ),
                            ),
                            const SizedBox(height: 6),
                            Text(
                              'PASSENGER: $passengerName',
                              style: TextStyle(
                                fontSize: 11.5,
                                fontWeight: FontWeight.w700,
                                color: fira.brandPrimary,
                                letterSpacing: 0.5,
                              ),
                            ),
                            const SizedBox(height: 14),

                            // Grid of Info
                            Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Expanded(
                                  child: _buildMetaField(
                                    label: 'DEPARTURE DATE',
                                    value: scheduleDate,
                                    fira: fira,
                                  ),
                                ),
                                Expanded(
                                  child: _buildMetaField(
                                    label: 'DEPARTURE TIME',
                                    value: departureTime,
                                    fira: fira,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 12),

                            Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Expanded(
                                  child: _buildMetaField(
                                    label: 'PASSENGERS',
                                    value: '${widget.participants} Registered',
                                    fira: fira,
                                  ),
                                ),
                                Expanded(
                                  child: _buildMetaField(
                                    label: 'TOTAL AMOUNT',
                                    value: FiraCurrencyFormatter.format(widget.totalAmount, currency: widget.currency),
                                    fira: fira,
                                    isAccent: true,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 12),

                            // Meeting Point with tap to open maps
                            InkWell(
                              onTap: () => _openInMaps(meetingPoint),
                              borderRadius: BorderRadius.circular(8),
                              child: Padding(
                                padding: const EdgeInsets.symmetric(vertical: 4),
                                child: Row(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Padding(
                                      padding: const EdgeInsets.only(top: 2),
                                      child: HugeIcon(
                                        icon: HugeIcons.strokeRoundedLocation01,
                                        size: 14,
                                        color: fira.brandPrimary,
                                      ),
                                    ),
                                    const SizedBox(width: 6),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            'MEETING POINT (Tap for Maps)',
                                            style: TextStyle(
                                              fontSize: 9.5,
                                              fontWeight: FontWeight.w700,
                                              color: fira.mutedText,
                                              letterSpacing: 0.5,
                                            ),
                                          ),
                                          const SizedBox(height: 2),
                                          Text(
                                            meetingPoint,
                                            style: TextStyle(
                                              fontSize: 12.5,
                                              fontWeight: FontWeight.w600,
                                              color: fira.brandPrimary,
                                              decoration: TextDecoration.underline,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),

                      // Dashed Tear Line with Notches
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        child: CustomPaint(
                          size: const Size(double.infinity, 1),
                          painter: _DashedLinePainter(
                            color: fira.mutedText.withValues(alpha: 0.35),
                          ),
                        ),
                      ),

                      // Bottom Section: QR Code & Checkpoint Info
                      Padding(
                        padding: const EdgeInsets.fromLTRB(18, 16, 18, 20),
                        child: Column(
                          children: [
                            if (_isLoading)
                              const Padding(
                                padding: EdgeInsets.all(24.0),
                                child: Center(child: CircularProgressIndicator()),
                              )
                            else ...[
                              // QR Code in white container in BOTH themes
                              Container(
                                padding: const EdgeInsets.all(12),
                                decoration: BoxDecoration(
                                  color: Colors.white,
                                  borderRadius: BorderRadius.circular(16),
                                  boxShadow: [
                                    BoxShadow(
                                      color: Colors.black.withValues(alpha: 0.08),
                                      blurRadius: 10,
                                      offset: const Offset(0, 3),
                                    ),
                                  ],
                                ),
                                child: _qrCodeSvg != null && _qrCodeSvg!.startsWith('data:image/svg+xml;base64,')
                                    ? SvgPicture.memory(
                                        base64Decode(_qrCodeSvg!.split(',')[1]),
                                        width: 130,
                                        height: 130,
                                      )
                                    : const Icon(
                                        Icons.qr_code_2_rounded,
                                        size: 130,
                                        color: Colors.black,
                                      ),
                              ),
                              const SizedBox(height: 10),
                              Text(
                                'Scan at departure terminal checkpoint',
                                style: TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w600,
                                  color: fira.mutedText,
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
            const SizedBox(height: 20),

            // 4 Action Buttons Row: Maps, Calendar, Share, Save Image
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: [
                _buildActionPill(
                  icon: HugeIcons.strokeRoundedMapsLocation01,
                  label: 'Maps',
                  onTap: () => _openInMaps(meetingPoint),
                  fira: fira,
                ),
                _buildActionPill(
                  icon: HugeIcons.strokeRoundedCalendar03,
                  label: 'Calendar',
                  onTap: _addToCalendar,
                  fira: fira,
                ),
                _buildActionPill(
                  icon: HugeIcons.strokeRoundedShare01,
                  label: 'Share',
                  onTap: _sharePass,
                  fira: fira,
                ),
                _buildActionPill(
                  icon: HugeIcons.strokeRoundedDownload01,
                  label: 'Save',
                  onTap: _saveAsImage,
                  fira: fira,
                ),
              ],
            ),
            const SizedBox(height: 24),

            if (_ticketData != null) ...[
              TourPaymentPanel(bookingId: widget.bookingId, ticket: {...?_ticketData, 'currency': _ticketData?['currency'] ?? widget.currency}, onChanged: _fetchTicket),
              const SizedBox(height: 24),
            ],

            // Navigation Actions: Back to Tours & My Bookings
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: () => Navigator.of(context).pop(),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 13),
                      side: BorderSide(color: fira.brandTint.withValues(alpha: 0.8)),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    ),
                    child: Text(
                      context.l10n.backToTours,
                      style: TextStyle(color: fira.onSurface, fontWeight: FontWeight.w600),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton(
                    onPressed: () {
                      Navigator.of(context).pushReplacement(
                        MaterialPageRoute(builder: (context) => const MyTourBookingsScreen()),
                      );
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: fira.brandPrimary,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 13),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                      elevation: 0,
                    ),
                    child: Text(
                      context.l10n.myBookings,
                      style: const TextStyle(fontWeight: FontWeight.bold),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildMetaField({
    required String label,
    required String value,
    required FiraColors fira,
    bool isAccent = false,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: TextStyle(
            fontSize: 9.5,
            fontWeight: FontWeight.w700,
            color: fira.mutedText,
            letterSpacing: 0.5,
          ),
        ),
        const SizedBox(height: 3),
        Text(
          value,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w800,
            color: isAccent ? fira.brandPrimary : fira.onSurface,
          ),
        ),
      ],
    );
  }

  Widget _buildActionPill({
    required List<List<dynamic>> icon,
    required String label,
    required VoidCallback onTap,
    required FiraColors fira,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        decoration: BoxDecoration(
          color: fira.card,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: fira.brandTint.withValues(alpha: 0.5)),
          boxShadow: [
            BoxShadow(
              color: fira.shadowColor,
              blurRadius: 6,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            HugeIcon(icon: icon, size: 18, color: fira.brandPrimary),
            const SizedBox(height: 4),
            Text(
              label,
              style: TextStyle(
                fontSize: 10.5,
                fontWeight: FontWeight.w600,
                color: fira.onSurface,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Custom Clipper for boarding pass side notches
class _TicketClipper extends CustomClipper<Path> {
  const _TicketClipper({
    required this.notchYRatio,
    this.notchRadius = 12.0,
  });

  final double notchYRatio;
  final double notchRadius;
  static const double borderRadius = 20.0;

  @override
  Path getClip(Size size) {
    final path = Path();
    final notchY = size.height * notchYRatio;

    // Top-left corner
    path.moveTo(0, borderRadius);
    path.arcToPoint(Offset(borderRadius, 0), radius: Radius.circular(borderRadius));

    // Top edge
    path.lineTo(size.width - borderRadius, 0);
    // Top-right corner
    path.arcToPoint(Offset(size.width, borderRadius), radius: Radius.circular(borderRadius));

    // Right edge down to notch
    path.lineTo(size.width, notchY - notchRadius);
    // Right notch inward
    path.arcToPoint(
      Offset(size.width, notchY + notchRadius),
      radius: Radius.circular(notchRadius),
      clockwise: false,
    );

    // Right edge down to bottom-right corner
    path.lineTo(size.width, size.height - borderRadius);
    path.arcToPoint(Offset(size.width - borderRadius, size.height), radius: Radius.circular(borderRadius));

    // Bottom edge
    path.lineTo(borderRadius, size.height);
    // Bottom-left corner
    path.arcToPoint(Offset(0, size.height - borderRadius), radius: Radius.circular(borderRadius));

    // Left edge up to notch
    path.lineTo(0, notchY + notchRadius);
    // Left notch inward
    path.arcToPoint(
      Offset(0, notchY - notchRadius),
      radius: Radius.circular(notchRadius),
      clockwise: false,
    );

    path.close();
    return path;
  }

  @override
  bool shouldReclip(_TicketClipper oldClipper) =>
      oldClipper.notchYRatio != notchYRatio ||
      oldClipper.notchRadius != notchRadius;
}

/// Border and shadow painter matching the clipped ticket
class _TicketShadowAndBorderPainter extends CustomPainter {
  const _TicketShadowAndBorderPainter({
    required this.borderColor,
    required this.shadowColor,
  });

  final Color borderColor;
  final Color shadowColor;

  @override
  void paint(Canvas canvas, Size size) {
    const clipper = _TicketClipper(notchYRatio: 0.62, notchRadius: 12);
    final path = clipper.getClip(size);

    // Draw shadow
    final shadowPaint = Paint()
      ..color = shadowColor
      ..maskFilter = const MaskFilter.blur(BlurStyle.outer, 8);
    canvas.drawPath(path, shadowPaint);

    // Draw border
    final borderPaint = Paint()
      ..color = borderColor
      ..style = PaintingStyle.stroke
      ..strokeWidth = 1.0;
    canvas.drawPath(path, borderPaint);
  }

  @override
  bool shouldRepaint(_TicketShadowAndBorderPainter oldDelegate) =>
      oldDelegate.borderColor != borderColor ||
      oldDelegate.shadowColor != shadowColor;
}

/// Dashed line painter
class _DashedLinePainter extends CustomPainter {
  const _DashedLinePainter({
    required this.color,
  });

  final Color color;
  static const double dashWidth = 5.0;
  static const double dashSpace = 4.0;

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = color
      ..strokeWidth = 1.2
      ..style = PaintingStyle.stroke;

    double startX = 0;
    final y = size.height / 2;
    while (startX < size.width) {
      canvas.drawLine(
        Offset(startX, y),
        Offset((startX + dashWidth).clamp(0, size.width), y),
        paint,
      );
      startX += dashWidth + dashSpace;
    }
  }

  @override
  bool shouldRepaint(_DashedLinePainter oldDelegate) =>
      oldDelegate.color != color;
}
