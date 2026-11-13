/// Workspace status as the server reports it. It comes from verification records only and
/// never changes because of a plan or a payment.
enum WorkspaceStatus {
  notStarted,
  inReview,
  verified,
  needsChanges,
  expired,
  suspended;

  static WorkspaceStatus fromApi(String? value) {
    switch (value) {
      case 'in_review':
        return WorkspaceStatus.inReview;
      case 'verified':
        return WorkspaceStatus.verified;
      case 'needs_changes':
        return WorkspaceStatus.needsChanges;
      case 'expired':
        return WorkspaceStatus.expired;
      case 'suspended':
        return WorkspaceStatus.suspended;
      default:
        return WorkspaceStatus.notStarted;
    }
  }
}

class OrganizationModel {
  final int id;
  final String name;
  final String slug;

  /// exporter, supplier, broker or buyer (older records may hold a legacy business type).
  final String businessType;
  final String? entityType;
  final bool isVerified;
  final List<String> verifiedRoles;
  final String country;
  final String? role; // Current user's role in this workspace (owner, manager, staff)
  final bool isActiveWorkspace;
  final String? joinedAt;
  final WorkspaceStatus status;
  final bool visibleToBuyers;
  final bool needsManualReview;

  OrganizationModel({
    required this.id,
    required this.name,
    required this.slug,
    required this.businessType,
    this.entityType,
    this.isVerified = false,
    this.verifiedRoles = const [],
    this.country = 'Ethiopia',
    this.role,
    this.isActiveWorkspace = false,
    this.joinedAt,
    this.status = WorkspaceStatus.notStarted,
    this.visibleToBuyers = false,
    this.needsManualReview = false,
  });

  bool get isExporter => verifiedRoles.contains('exporter');
  bool get isBuyer => verifiedRoles.contains('buyer');
  bool get isSupplier => verifiedRoles.contains('supplier');
  bool get isBroker => verifiedRoles.contains('broker');
  bool get isOwner => role == 'owner';

  factory OrganizationModel.fromJson(Map<String, dynamic> json) {
    List<String> roles = [];
    if (json['verified_roles'] is List) {
      roles = (json['verified_roles'] as List).map((e) => e.toString()).toList();
    }

    return OrganizationModel(
      id: (json['id'] as num?)?.toInt() ?? 0,
      name: json['name']?.toString() ?? '',
      slug: json['slug']?.toString() ?? '',
      businessType: (json['business_role'] ?? json['business_type'])?.toString() ?? 'exporter',
      entityType: json['entity_type']?.toString(),
      isVerified: json['is_verified'] == true || json['is_verified'] == 1,
      verifiedRoles: roles,
      country: json['country']?.toString() ?? 'Ethiopia',
      role: (json['membership_role'] ?? json['role'])?.toString(),
      isActiveWorkspace: json['is_active_workspace'] == true,
      joinedAt: json['joined_at']?.toString(),
      status: WorkspaceStatus.fromApi(json['status']?.toString()),
      visibleToBuyers: json['visible_to_buyers'] == true,
      needsManualReview: json['needs_manual_review'] == true,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'slug': slug,
      'business_role': businessType,
      'entity_type': entityType,
      'is_verified': isVerified,
      'verified_roles': verifiedRoles,
      'country': country,
      'membership_role': role,
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

  /// Set by the server: only the owner may remove, and never themselves.
  final bool canRemove;

  OrganizationMemberModel({
    required this.id,
    required this.userId,
    required this.name,
    required this.email,
    this.phone,
    required this.role,
    this.joinedAt,
    this.canRemove = false,
  });

  factory OrganizationMemberModel.fromJson(Map<String, dynamic> json) {
    return OrganizationMemberModel(
      id: (json['id'] as num?)?.toInt() ?? 0,
      userId: (json['user_id'] as num?)?.toInt() ?? 0,
      name: json['name']?.toString() ?? '',
      email: json['email']?.toString() ?? '',
      phone: (json['phone_masked'] ?? json['phone'])?.toString(),
      role: json['role']?.toString() ?? 'staff',
      joinedAt: json['joined_at']?.toString(),
      canRemove: json['can_remove'] == true,
    );
  }
}

/// GET /organizations/{id}/members
class TeamRoster {
  final bool viewerIsOwner;
  final bool canAddMembers;
  final List<OrganizationMemberModel> members;

  const TeamRoster({required this.viewerIsOwner, required this.canAddMembers, required this.members});

  factory TeamRoster.fromJson(Map<String, dynamic> json) => TeamRoster(
        viewerIsOwner: json['viewer_is_owner'] == true,
        canAddMembers: json['can_add_members'] == true,
        members: (json['members'] as List? ?? [])
            .map((m) => OrganizationMemberModel.fromJson(m as Map<String, dynamic>))
            .toList(),
      );
}

/// GET /workspaces/options: the one server list of entity types, roles and countries.
class WorkspaceOptions {
  final List<EntityTypeOption> entityTypes;
  final List<String> businessRoles;
  final List<String> countries;
  final String defaultCountry;
  final int maxOwnedBusinesses;

  const WorkspaceOptions({
    required this.entityTypes,
    required this.businessRoles,
    required this.countries,
    required this.defaultCountry,
    required this.maxOwnedBusinesses,
  });

  factory WorkspaceOptions.fromJson(Map<String, dynamic> json) => WorkspaceOptions(
        entityTypes: (json['entity_types'] as List? ?? [])
            .map((e) => EntityTypeOption.fromJson(e as Map<String, dynamic>))
            .toList(),
        businessRoles: (json['business_roles'] as List? ?? []).map((e) => e.toString()).toList(),
        countries: (json['countries'] as List? ?? []).map((e) => e.toString()).toList(),
        defaultCountry: json['default_country']?.toString() ?? 'Ethiopia',
        maxOwnedBusinesses: (json['max_owned_businesses'] as num?)?.toInt() ?? 3,
      );
}

class EntityTypeOption {
  final String value;
  final String label;
  final bool needsManualReview;

  const EntityTypeOption({required this.value, required this.label, required this.needsManualReview});

  factory EntityTypeOption.fromJson(Map<String, dynamic> json) => EntityTypeOption(
        value: json['value']?.toString() ?? '',
        label: json['label']?.toString() ?? '',
        needsManualReview: json['needs_manual_review'] == true,
      );
}
