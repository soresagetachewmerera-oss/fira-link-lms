class OrganizationModel {
  final int id;
  final String name;
  final String slug;
  final String businessType;
  final bool isVerified;
  final List<String> verifiedRoles;
  final String country;
  final String? role; // Current user's role in this workspace (owner, manager, staff)
  final bool isActiveWorkspace;
  final String? joinedAt;

  OrganizationModel({
    required this.id,
    required this.name,
    required this.slug,
    required this.businessType,
    this.isVerified = false,
    this.verifiedRoles = const [],
    this.country = 'Ethiopia',
    this.role,
    this.isActiveWorkspace = false,
    this.joinedAt,
  });

  bool get isExporter => verifiedRoles.contains('exporter');
  bool get isBuyer => verifiedRoles.contains('buyer');
  bool get isSupplier => verifiedRoles.contains('supplier');
  bool get isBroker => verifiedRoles.contains('broker');

  factory OrganizationModel.fromJson(Map<String, dynamic> json) {
    List<String> roles = [];
    if (json['verified_roles'] != null) {
      if (json['verified_roles'] is List) {
        roles = (json['verified_roles'] as List)
            .map((e) => e.toString())
            .toList();
      }
    }

    return OrganizationModel(
      id: (json['id'] as num?)?.toInt() ?? 0,
      name: json['name']?.toString() ?? '',
      slug: json['slug']?.toString() ?? '',
      businessType: json['business_type']?.toString() ?? 'exporter',
      isVerified: json['is_verified'] == true || json['is_verified'] == 1,
      verifiedRoles: roles,
      country: json['country']?.toString() ?? 'Ethiopia',
      role: json['role']?.toString(),
      isActiveWorkspace: json['is_active_workspace'] == true,
      joinedAt: json['joined_at']?.toString(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'slug': slug,
      'business_type': businessType,
      'is_verified': isVerified,
      'verified_roles': verifiedRoles,
      'country': country,
      'role': role,
      'is_active_workspace': isActiveWorkspace,
      'joined_at': joinedAt,
    };
  }
}

class OrganizationMemberModel {
  final int id;
  final int userId;
  final String name;
  final String email;
  final String? phone;
  final String role; // owner, manager, staff
  final String? joinedAt;

  OrganizationMemberModel({
    required this.id,
    required this.userId,
    required this.name,
    required this.email,
    this.phone,
    required this.role,
    this.joinedAt,
  });

  factory OrganizationMemberModel.fromJson(Map<String, dynamic> json) {
    return OrganizationMemberModel(
      id: (json['id'] as num?)?.toInt() ?? 0,
      userId: (json['user_id'] as num?)?.toInt() ?? 0,
      name: json['name']?.toString() ?? '',
      email: json['email']?.toString() ?? '',
      phone: json['phone']?.toString(),
      role: json['role']?.toString() ?? 'staff',
      joinedAt: json['joined_at']?.toString(),
    );
  }
}

