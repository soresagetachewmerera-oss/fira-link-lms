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
  final String country;
  final String? role; // Current user's role in this workspace (owner, manager, staff)
  final bool isActiveWorkspace;
  final String? joinedAt;
  final WorkspaceStatus status;
  final bool visibleToBuyers;
  final bool needsManualReview;

  /// Release 2a: the category the business picked (null for businesses created before categories).
  final BusinessCategoryRef? businessCategory;

  /// Activities that count right now (declared, verified, documents approved).
  final List<String> activeCapabilities;

  OrganizationModel({
    required this.id,
    required this.name,
    required this.slug,
    required this.businessType,
    this.entityType,
    this.country = 'Ethiopia',
    this.role,
    this.isActiveWorkspace = false,
    this.joinedAt,
    this.status = WorkspaceStatus.notStarted,
    this.visibleToBuyers = false,
    this.needsManualReview = false,
    this.businessCategory,
    this.activeCapabilities = const [],
  });

  bool get isOwner => role == 'owner';

  factory OrganizationModel.fromJson(Map<String, dynamic> json) {
    return OrganizationModel(
      id: (json['id'] as num?)?.toInt() ?? 0,
      name: json['name']?.toString() ?? '',
      slug: json['slug']?.toString() ?? '',
      businessType: (json['business_role'] ?? json['business_type'])?.toString() ?? 'exporter',
      entityType: json['entity_type']?.toString(),
      country: json['country']?.toString() ?? 'Ethiopia',
      role: (json['membership_role'] ?? json['role'])?.toString(),
      isActiveWorkspace: json['is_active_workspace'] == true,
      joinedAt: json['joined_at']?.toString(),
      status: WorkspaceStatus.fromApi(json['status']?.toString()),
      visibleToBuyers: json['visible_to_buyers'] == true,
      needsManualReview: json['needs_manual_review'] == true,
      businessCategory: json['business_category'] is Map
          ? BusinessCategoryRef.fromJson(Map<String, dynamic>.from(json['business_category'] as Map))
          : null,
      activeCapabilities: (json['active_capabilities'] as List? ?? []).map((e) => e.toString()).toList(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'slug': slug,
      'business_role': businessType,
      'entity_type': entityType,
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
/// A category name in both languages, as the server sends it (admins manage the list).
class BusinessCategoryRef {
  final String code;
  final String labelEn;
  final String labelAm;

  const BusinessCategoryRef({required this.code, required this.labelEn, required this.labelAm});

  factory BusinessCategoryRef.fromJson(Map<String, dynamic> json) => BusinessCategoryRef(
        code: json['code']?.toString() ?? '',
        labelEn: json['label_en']?.toString() ?? '',
        labelAm: json['label_am']?.toString() ?? '',
      );

  String label(String languageCode) => languageCode == 'am' && labelAm.isNotEmpty ? labelAm : labelEn;
}

class BusinessCategoryOption extends BusinessCategoryRef {
  final String descriptionEn;
  final String descriptionAm;
  final String businessRole;
  final List<String> capabilities;

  const BusinessCategoryOption({
    required super.code,
    required super.labelEn,
    required super.labelAm,
    this.descriptionEn = '',
    this.descriptionAm = '',
    required this.businessRole,
    this.capabilities = const [],
  });

  factory BusinessCategoryOption.fromJson(Map<String, dynamic> json) => BusinessCategoryOption(
        code: json['code']?.toString() ?? '',
        labelEn: json['label_en']?.toString() ?? '',
        labelAm: json['label_am']?.toString() ?? '',
        descriptionEn: json['description_en']?.toString() ?? '',
        descriptionAm: json['description_am']?.toString() ?? '',
        businessRole: json['business_role']?.toString() ?? 'exporter',
        capabilities: (json['capabilities'] as List? ?? []).map((e) => e.toString()).toList(),
      );

  String description(String languageCode) =>
      languageCode == 'am' && descriptionAm.isNotEmpty ? descriptionAm : descriptionEn;
}

/// One activity on the Activities screen. The status comes from the server only:
/// off, suspended, needs_verification, needs_documents or active.
class BusinessCapability {
  final String code;
  final bool declared;
  final String status;
  final List<String> missingDocuments;

  const BusinessCapability({required this.code, required this.declared, required this.status, this.missingDocuments = const []});

  factory BusinessCapability.fromJson(Map<String, dynamic> json) => BusinessCapability(
        code: json['code']?.toString() ?? '',
        declared: json['declared'] == true,
        status: json['status']?.toString() ?? 'off',
        missingDocuments: (json['missing_documents'] as List? ?? []).map((e) => e.toString()).toList(),
      );
}

class BusinessActivities {
  final BusinessCategoryOption? category;
  final bool canChangeCategory;
  final bool canEdit;
  final List<BusinessCapability> capabilities;

  const BusinessActivities({this.category, this.canChangeCategory = false, this.canEdit = false, this.capabilities = const []});

  factory BusinessActivities.fromJson(Map<String, dynamic> json) => BusinessActivities(
        category: json['business_category'] is Map
            ? BusinessCategoryOption.fromJson(Map<String, dynamic>.from(json['business_category'] as Map))
            : null,
        canChangeCategory: json['can_change_category'] == true,
        canEdit: json['can_edit'] == true,
        capabilities: (json['capabilities'] as List? ?? [])
            .map((e) => BusinessCapability.fromJson(Map<String, dynamic>.from(e as Map)))
            .toList(),
      );
}

class WorkspaceOptions {
  final List<EntityTypeOption> entityTypes;
  final List<String> businessRoles;
  final List<BusinessCategoryOption> businessCategories;
  final List<String> countries;
  final String defaultCountry;
  final int maxOwnedBusinesses;

  const WorkspaceOptions({
    required this.entityTypes,
    required this.businessRoles,
    this.businessCategories = const [],
    required this.countries,
    required this.defaultCountry,
    required this.maxOwnedBusinesses,
  });

  factory WorkspaceOptions.fromJson(Map<String, dynamic> json) => WorkspaceOptions(
        entityTypes: (json['entity_types'] as List? ?? [])
            .map((e) => EntityTypeOption.fromJson(e as Map<String, dynamic>))
            .toList(),
        businessRoles: (json['business_roles'] as List? ?? []).map((e) => e.toString()).toList(),
        businessCategories: (json['business_categories'] as List? ?? [])
            .map((e) => BusinessCategoryOption.fromJson(Map<String, dynamic>.from(e as Map)))
            .toList(),
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
