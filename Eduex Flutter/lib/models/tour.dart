class Tour {
  final int id;
  final String title;
  final String? slug;
  final String? summary;
  final String description;
  final String destination;
  final String? meetingPoint;
  final int durationDays;
  final int? durationHours;
  final String? startDate;
  final String? endDate;
  final String? departureTime;
  final int totalSeats;
  final int remainingSeats;
  final double price;
  final String currency;
  final String? category;
  final String difficultyLevel;
  final String? featuredImage;
  final List<dynamic>? gallery;
  final List<dynamic>? itinerary;
  final List<dynamic>? includedServices;
  final List<dynamic>? exclusions;
  final List<dynamic>? requirements;
  final String? cancellationPolicy;
  final List<dynamic>? availableSchedules;
  final bool isFeatured;

  Tour({
    required this.id,
    required this.title,
    this.slug,
    this.summary,
    required this.description,
    required this.destination,
    this.meetingPoint,
    required this.durationDays,
    this.durationHours,
    this.startDate,
    this.endDate,
    this.departureTime,
    required this.totalSeats,
    required this.remainingSeats,
    required this.price,
    this.currency = 'ETB',
    this.category,
    required this.difficultyLevel,
    this.featuredImage,
    this.gallery,
    this.itinerary,
    this.includedServices,
    this.exclusions,
    this.requirements,
    this.cancellationPolicy,
    this.availableSchedules,
    this.isFeatured = false,
  });

  factory Tour.fromJson(Map<String, dynamic> json) {
    return Tour(
      id: json['id'] as int,
      title: json['title'] ?? '',
      slug: json['slug'],
      summary: json['summary'],
      description: json['description'] ?? '',
      destination: json['destination'] ?? '',
      meetingPoint: json['meeting_point'],
      durationDays: json['duration_days'] != null ? int.tryParse(json['duration_days'].toString()) ?? 1 : 1,
      durationHours: json['duration_hours'] != null ? int.tryParse(json['duration_hours'].toString()) : null,
      startDate: json['start_date'],
      endDate: json['end_date'],
      departureTime: json['departure_time'],
      totalSeats: json['total_seats'] != null ? int.tryParse(json['total_seats'].toString()) ?? 20 : 20,
      remainingSeats: json['remaining_seats'] != null ? int.tryParse(json['remaining_seats'].toString()) ?? 0 : 0,
      price: json['price'] != null ? double.tryParse(json['price'].toString()) ?? 0.0 : 0.0,
      currency: json['currency'] ?? 'ETB',
      category: json['category'],
      difficultyLevel: json['difficulty_level'] ?? 'Moderate',
      featuredImage: json['featured_image'],
      gallery: json['gallery'] as List<dynamic>?,
      itinerary: json['itinerary'] as List<dynamic>?,
      includedServices: json['included_services'] as List<dynamic>?,
      exclusions: json['exclusions'] as List<dynamic>?,
      requirements: json['requirements'] as List<dynamic>?,
      cancellationPolicy: json['cancellation_policy'],
      availableSchedules: json['available_schedules'] as List<dynamic>?,
      isFeatured: json['is_featured'] ?? false,
    );
  }
}
