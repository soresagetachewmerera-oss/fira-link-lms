import 'dart:convert';
import 'package:flutter/material.dart';
import '../../models/tour.dart';
import '../../services/tour_service.dart';
import '../../theme/app_theme.dart';
import 'tour_booking_confirmation_screen.dart';

class TourBookingScreen extends StatefulWidget {
  const TourBookingScreen({required this.tour, super.key});

  final Tour tour;

  @override
  State<TourBookingScreen> createState() => _TourBookingScreenState();
}

class _TourBookingScreenState extends State<TourBookingScreen> {
  final _formKey = GlobalKey<FormState>();
  final TourService _tourService = TourService();

  final TextEditingController _firstNameController = TextEditingController();
  final TextEditingController _lastNameController = TextEditingController();
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _phoneController = TextEditingController();
  final TextEditingController _requestsController = TextEditingController();

  int _participants = 1;
  String _selectedPaymentMethod = 'chapa';
  String? _selectedDate;
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    if (widget.tour.availableSchedules != null && widget.tour.availableSchedules!.isNotEmpty) {
      _selectedDate = widget.tour.availableSchedules![0]['date']?.toString();
    } else {
      _selectedDate = widget.tour.startDate;
    }
  }

  double get _totalAmount => widget.tour.price * _participants;

  Future<void> _submitBooking() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    try {
      final data = {
        'first_name': _firstNameController.text.trim(),
        'last_name': _lastNameController.text.trim(),
        'email': _emailController.text.trim(),
        'phone': _phoneController.text.trim(),
        'participants': _participants,
        'schedule_date': _selectedDate,
        'special_requests': _requestsController.text.trim(),
        'payment_method': _selectedPaymentMethod,
      };

      final response = await _tourService.bookTour(
        tourId: widget.tour.id,
        data: data,
      );

      final resBody = jsonDecode(response.body);

      if ((response.statusCode == 200 || response.statusCode == 201) && mounted) {
        final bookingData = resBody['booking'] as Map<String, dynamic>;
        final String reference = bookingData['reference'] ?? 'TR-SUCCESS';
        final int bookingId = bookingData['id'] as int;

        Navigator.of(context).pushReplacement(
          MaterialPageRoute(
            builder: (context) => TourBookingConfirmationScreen(
              bookingId: bookingId,
              reference: reference,
              tourTitle: widget.tour.title,
              participants: _participants,
              totalAmount: _totalAmount,
              currency: widget.tour.currency,
            ),
          ),
        );
      } else if (mounted) {
        final errorMsg = resBody['message'] ?? 'Failed to complete booking.';
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(errorMsg)));
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final primaryColor = AppTheme.getPrimaryColor(context);

    return Scaffold(
      backgroundColor: AppTheme.getBackgroundColor(context),
      appBar: AppBar(
        title: const Text('Confirm Reservation', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Tour Summary Header Card
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppTheme.getCardColor(context),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.grey.shade200),
                ),
                child: Row(
                  children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(8),
                      child: Container(
                        width: 70,
                        height: 55,
                        color: Colors.grey.shade200,
                        child: widget.tour.featuredImage != null
                            ? Image.network(widget.tour.featuredImage!, fit: BoxFit.cover)
                            : const Icon(Icons.image, size: 24),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            widget.tour.title,
                            style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                          const SizedBox(height: 2),
                          Text(
                            '${widget.tour.currency} ${widget.tour.price.toStringAsFixed(2)} per person',
                            style: TextStyle(fontSize: 11.5, color: primaryColor, fontWeight: FontWeight.w600),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),

              const Text('Lead Passenger Details', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700)),
              const SizedBox(height: 12),

              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _firstNameController,
                      decoration: const InputDecoration(labelText: 'First Name *', border: OutlineInputBorder()),
                      validator: (v) => v == null || v.isEmpty ? 'Required' : null,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: TextFormField(
                      controller: _lastNameController,
                      decoration: const InputDecoration(labelText: 'Last Name *', border: OutlineInputBorder()),
                      validator: (v) => v == null || v.isEmpty ? 'Required' : null,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),

              TextFormField(
                controller: _emailController,
                keyboardType: TextInputType.emailAddress,
                decoration: const InputDecoration(labelText: 'Email Address *', border: OutlineInputBorder()),
                validator: (v) => v == null || !v.contains('@') ? 'Valid email required' : null,
              ),
              const SizedBox(height: 12),

              TextFormField(
                controller: _phoneController,
                keyboardType: TextInputType.phone,
                decoration: const InputDecoration(labelText: 'Phone Number *', hintText: '+251 9...', border: OutlineInputBorder()),
                validator: (v) => v == null || v.isEmpty ? 'Phone required' : null,
              ),
              const SizedBox(height: 20),

              // Participants Counter
              const Text('Number of Participants', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700)),
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                decoration: BoxDecoration(
                  color: AppTheme.getCardColor(context),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: Colors.grey.shade300),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('Passengers', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
                    Row(
                      children: [
                        IconButton(
                          icon: const Icon(Icons.remove_circle_outline),
                          onPressed: _participants > 1
                              ? () => setState(() => _participants--)
                              : null,
                        ),
                        Text('$_participants', style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
                        IconButton(
                          icon: const Icon(Icons.add_circle_outline),
                          onPressed: _participants < widget.tour.remainingSeats
                              ? () => setState(() => _participants++)
                              : null,
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),

              // Special Requests
              TextFormField(
                controller: _requestsController,
                maxLines: 2,
                decoration: const InputDecoration(
                  labelText: 'Special Requests / Dietary Needs (Optional)',
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 20),

              // Payment Method Choice
              if (widget.tour.price > 0) ...[
                const Text('Payment Method', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700)),
                RadioListTile<String>(
                  value: 'chapa',
                  groupValue: _selectedPaymentMethod,
                  onChanged: (v) => setState(() => _selectedPaymentMethod = 'chapa'),
                  title: const Text('Chapa (Telebirr, CBE Birr & Cards)', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
                  subtitle: const Text('Instant secure payment in ETB (Ethiopian Birr)', style: TextStyle(fontSize: 11)),
                ),
                const SizedBox(height: 16),
              ],

              // Total Summary Card
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: primaryColor.withValues(alpha: 0.08),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: primaryColor.withValues(alpha: 0.2)),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('Total Payable:', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700)),
                    Text(
                      _totalAmount > 0 ? '${widget.tour.currency} ${_totalAmount.toStringAsFixed(2)}' : 'Free Entry',
                      style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: primaryColor),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),

              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _isLoading ? null : _submitBooking,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: primaryColor,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: _isLoading
                      ? const CircularProgressIndicator(color: Colors.white)
                      : const Text('Confirm & Reserve Seats', style: TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.w700)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
