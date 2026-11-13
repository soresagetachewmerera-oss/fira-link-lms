class TourBooking {
  final int id;
  final int tourId;
  final String tourTitle;
  final String? tourSlug;
  final String? destination;
  final String? featuredImage;
  final String? bookedAtFormatted;
  final String? scheduleDateFormatted;
  final String? departureTime;
  final int participants;
  final String status;
  final String paymentStatus;
  final String reference;
  final double totalAmount;
  final String currency;

  TourBooking({
    required this.id,
    required this.tourId,
    required this.tourTitle,
    this.tourSlug,
    this.destination,
    this.featuredImage,
    this.bookedAtFormatted,
    this.scheduleDateFormatted,
    this.departureTime,
    required this.participants,
    required this.status,
    required this.paymentStatus,
    required this.reference,
    required this.totalAmount,
    this.currency = 'ETB',
  });

  factory TourBooking.fromJson(Map<String, dynamic> json) {
    return TourBooking(
      id: json['id'] as int,
      tourId: json['tour_id'] as int,
      tourTitle: json['tour_title'] ?? 'Tour Expedition',
      tourSlug: json['tour_slug'],
      destination: json['destination'],
      featuredImage: json['featured_image'],
      bookedAtFormatted: json['booked_at_formatted'],
      scheduleDateFormatted: json['schedule_date_formatted'],
      departureTime: json['departure_time'],
      participants: json['participants'] != null ? int.tryParse(json['participants'].toString()) ?? 1 : 1,
      status: json['status'] ?? 'pending',
      paymentStatus: json['payment_status'] ?? 'pending',
      reference: json['reference'] ?? '',
      totalAmount: json['total_amount'] != null ? double.tryParse(json['total_amount'].toString()) ?? 0.0 : 0.0,
      currency: json['currency'] ?? 'ETB',
    );
  }
}
