import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import '../../services/tour_service.dart';
import '../../theme/app_theme.dart';
import 'my_tour_bookings_screen.dart';

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
        setState(() {
          _ticketData = data['ticket'];
          _qrCodeSvg = data['qr_code'];
          _isLoading = false;
        });
      } else {
        setState(() => _isLoading = false);
      }
    } catch (_) {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final primaryColor = AppTheme.getPrimaryColor(context);

    return Scaffold(
      backgroundColor: AppTheme.getBackgroundColor(context),
      appBar: AppBar(
        title: const Text('Boarding Pass', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            // Success icon
            Container(
              width: 64,
              height: 64,
              decoration: BoxDecoration(
                color: Colors.green.withValues(alpha: 0.1),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.check_circle, color: Colors.green, size: 40),
            ),
            const SizedBox(height: 12),
            const Text(
              'Reservation Received!',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 4),
            Text(
              'Your seats are registered with reference below.',
              style: TextStyle(fontSize: 12.5, color: Colors.grey.shade600),
            ),
            const SizedBox(height: 24),

            // Boarding Pass Card
            Container(
              decoration: BoxDecoration(
                color: AppTheme.getCardColor(context),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: Colors.grey.shade300),
                boxShadow: [
                  BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 10),
                ],
              ),
              clipBehavior: Clip.antiAlias,
              child: Column(
                children: [
                  // Card Header
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                    color: primaryColor,
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'FIRA LINK EXPEDITIONS',
                          style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w800, letterSpacing: 1),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.2),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(
                            widget.reference,
                            style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w700),
                          ),
                        ),
                      ],
                    ),
                  ),

                  Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          widget.tourTitle,
                          style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700),
                        ),
                        const SizedBox(height: 12),

                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text('PASSENGERS', style: TextStyle(fontSize: 10, color: Colors.grey, fontWeight: FontWeight.w600)),
                                Text('${widget.participants} Registered', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700)),
                              ],
                            ),
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.end,
                              children: [
                                const Text('TOTAL AMOUNT', style: TextStyle(fontSize: 10, color: Colors.grey, fontWeight: FontWeight.w600)),
                                Text(
                                  '${widget.currency} ${widget.totalAmount.toStringAsFixed(2)}',
                                  style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: primaryColor),
                                ),
                              ],
                            ),
                          ],
                        ),
                        if (_ticketData?['passenger_name'] != null) ...[
                          const SizedBox(height: 8),
                          Text(
                            'Passenger: ${_ticketData!["passenger_name"]}',
                            style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
                          ),
                        ],
                        if (_ticketData?['meeting_point'] != null) ...[
                          const SizedBox(height: 4),
                          Text(
                            'Meeting: ${_ticketData!["meeting_point"]}',
                            style: TextStyle(fontSize: 11.5, color: Colors.grey.shade600),
                          ),
                        ],
                        const SizedBox(height: 16),

                        // QR Code Section
                        if (_isLoading)
                          const Center(child: Padding(padding: EdgeInsets.all(16), child: CircularProgressIndicator()))
                        else if (_qrCodeSvg != null && _qrCodeSvg!.startsWith('data:image/svg+xml;base64,'))
                          Center(
                            child: Column(
                              children: [
                                SvgPicture.memory(
                                  base64Decode(_qrCodeSvg!.split(',')[1]),
                                  width: 140,
                                  height: 140,
                                ),
                                const SizedBox(height: 6),
                                Text(
                                  'Scan at departure terminal checkpoint',
                                  style: TextStyle(fontSize: 10.5, color: Colors.grey.shade600),
                                ),
                              ],
                            ),
                          ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 28),

            // Action Buttons
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: () => Navigator.of(context).pop(),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    child: const Text('Back to Tours'),
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
                      backgroundColor: primaryColor,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    child: const Text('My Bookings', style: TextStyle(color: Colors.white)),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
