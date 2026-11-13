import '../config/config.dart';

class User {
  final int id;
  final String name;
  final String email;
  final String role;
  final String? profilePhoto;
  final bool isApproved;
  final String? phone;
  final String? bio;
  final int? activeOrganizationId;
  final String? onboardingIntent;
  final bool isVerifiedSeller;
  final bool isVerifiedExporter;
  final bool isVerifiedBuyer;
  final String? activePlanCode;

  String? get profilePhotoUrl =>
      profilePhoto != null ? AppConfig.getImageUrl(profilePhoto) : null;

  User({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    this.profilePhoto,
    required this.isApproved,
    this.phone,
    this.bio,
    this.activeOrganizationId,
    this.onboardingIntent,
    this.isVerifiedSeller = false,
    this.isVerifiedExporter = false,
    this.isVerifiedBuyer = false,
    this.activePlanCode,
  });

  factory User.fromJson(Map<String, dynamic>? json) {
    if (json == null) {
      return User(
        id: 0,
        name: '',
        email: '',
        role: 'student',
        profilePhoto: null,
        isApproved: true,
        phone: null,
        bio: null,
      );
    }
    return User(
      id: (json['id'] as num?)?.toInt() ?? 0,
      name: json['name']?.toString() ?? '',
      email: json['email']?.toString() ?? '',
      role: json['role']?.toString() ?? 'student',
      profilePhoto: json['profile_photo']?.toString(),
      isApproved: json['is_approved'] == 1 || json['is_approved'] == true,
      phone: json['phone']?.toString(),
      bio: json['bio']?.toString(),
      activeOrganizationId: (json['active_organization_id'] as num?)?.toInt(),
      onboardingIntent: json['onboarding_intent']?.toString(),
      isVerifiedSeller: json['is_verified_seller'] == true || json['is_verified_seller'] == 1,
      isVerifiedExporter: json['is_verified_exporter'] == true || json['is_verified_exporter'] == 1,
      isVerifiedBuyer: json['is_verified_buyer'] == true || json['is_verified_buyer'] == 1,
      activePlanCode: json['active_plan_code']?.toString(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'email': email,
      'role': role,
      'profile_photo': profilePhoto,
      'is_approved': isApproved,
      'phone': phone,
      'bio': bio,
      'active_organization_id': activeOrganizationId,
      'onboarding_intent': onboardingIntent,
      'is_verified_seller': isVerifiedSeller,
      'is_verified_exporter': isVerifiedExporter,
      'is_verified_buyer': isVerifiedBuyer,
      'active_plan_code': activePlanCode,
    };
  }
}
