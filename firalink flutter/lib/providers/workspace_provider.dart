import 'package:flutter/foundation.dart';
import '../models/organization_model.dart';
import '../services/organization_service.dart';

/// The active workspace: Personal (no business) or one business the user is an active member of.
/// Everything here mirrors the server; status and verification are never decided in the app.
class WorkspaceProvider with ChangeNotifier {
  WorkspaceProvider({OrganizationService? service}) : _service = service ?? OrganizationService();

  final OrganizationService _service;

  OrganizationModel? _currentOrganization;
  List<OrganizationModel> _organizations = [];
  List<String> _intents = [];
  int _maxOwnedBusinesses = 3;
  int _ownedBusinesses = 0;
  bool _isLoading = false;
  Object? _error;

  OrganizationModel? get currentOrganization => _currentOrganization;
  List<OrganizationModel> get organizations => _organizations;
  bool get isPersonal => _currentOrganization == null;
  List<String> get onboardingIntents => _intents;
  String? get onboardingIntent => _intents.isEmpty ? null : _intents.first;
  bool get isLoading => _isLoading;

  /// The last load or switch failure ([ApiException] or other), for an error state with retry.
  Object? get error => _error;

  /// The account limit is not a plan feature; at the limit the app says "ask support".
  bool get reachedBusinessLimit => _ownedBusinesses >= _maxOwnedBusinesses;
  int get maxOwnedBusinesses => _maxOwnedBusinesses;

  Future<void> loadWorkspaces() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final snapshot = await _service.getUserOrganizations();
      _currentOrganization = snapshot.current;
      _organizations = snapshot.organizations;
      _intents = snapshot.intents;
      _maxOwnedBusinesses = snapshot.maxOwnedBusinesses;
      _ownedBusinesses = snapshot.ownedBusinesses;
    } catch (e) {
      _error = e;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Switch to a business, or to Personal with null. Throws on failure.
  Future<void> switchWorkspace(int? organizationId) async {
    await _service.switchWorkspace(organizationId);
    await loadWorkspaces();
  }

  /// Business setup. Throws [ApiException] (validation, WORKSPACE_LIMIT, network).
  Future<OrganizationModel> createWorkspace({
    required String name,
    required String businessRole,
    required String entityType,
    required String country,
    String? tinNumber,
    String? billingEmail,
  }) async {
    final org = await _service.createOrganization(
      name: name,
      businessRole: businessRole,
      entityType: entityType,
      country: country,
      tinNumber: tinNumber,
      billingEmail: billingEmail,
    );
    await loadWorkspaces();
    return org;
  }

  /// Saves the multi-select intent. Returns true when Business setup should follow.
  Future<bool> saveIntents(List<String> intents) async {
    final needsBusiness = await _service.saveOnboardingIntents(intents);
    _intents = List.unmodifiable(intents);
    notifyListeners();
    return needsBusiness;
  }

  /// Entity types, roles and countries for Business setup (one server list).
  Future<WorkspaceOptions> loadOptions() => _service.getOptions();

  Future<TeamRoster> loadTeam(int organizationId) => _service.getMembers(organizationId);

  Future<void> removeMember(int organizationId, int userId) => _service.removeMember(organizationId, userId);

  /// Session ended or a new one started (SessionWatcher): forget the previous person's data.
  void resetSession() {
    _currentOrganization = null;
    _organizations = [];
    _intents = [];
    _maxOwnedBusinesses = 3;
    _ownedBusinesses = 0;
    _isLoading = false;
    _error = null;
    notifyListeners();
  }
}
