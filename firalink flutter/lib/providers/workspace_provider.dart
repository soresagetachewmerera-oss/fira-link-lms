import 'package:flutter/foundation.dart';
import '../models/organization_model.dart';
import '../services/organization_service.dart';

class WorkspaceProvider with ChangeNotifier {
  final OrganizationService _service = OrganizationService();

  OrganizationModel? _currentOrganization;
  List<OrganizationModel> _organizations = [];
  bool _isLoading = false;
  String? _onboardingIntent;
  String? _errorMessage;

  OrganizationModel? get currentOrganization => _currentOrganization;
  List<OrganizationModel> get organizations => _organizations;
  bool get isLoading => _isLoading;
  String? get onboardingIntent => _onboardingIntent;
  String? get errorMessage => _errorMessage;

  bool get isVerifiedExporter =>
      _currentOrganization?.isVerified == true &&
      _currentOrganization?.verifiedRoles.contains('exporter') == true;

  bool get isVerifiedBuyer =>
      _currentOrganization?.isVerified == true &&
      _currentOrganization?.verifiedRoles.contains('buyer') == true;

  bool get isVerifiedSupplier =>
      _currentOrganization?.isVerified == true &&
      _currentOrganization?.verifiedRoles.contains('supplier') == true;

  bool get isVerifiedBroker =>
      _currentOrganization?.isVerified == true &&
      _currentOrganization?.verifiedRoles.contains('broker') == true;

  Future<void> loadWorkspaces() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final res = await _service.getUserOrganizations();
      if (res['success'] == true) {
        _currentOrganization = res['current_organization'] as OrganizationModel?;
        _organizations = res['organizations'] as List<OrganizationModel>;
        _onboardingIntent = res['onboarding_intent'] as String?;
      } else {
        _errorMessage = res['message'];
      }
    } catch (e) {
      _errorMessage = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> switchWorkspace(int organizationId) async {
    _isLoading = true;
    notifyListeners();

    try {
      final res = await _service.switchWorkspace(organizationId);
      if (res['success'] == true) {
        _currentOrganization = res['organization'] as OrganizationModel?;
        await loadWorkspaces();
        return true;
      } else {
        _errorMessage = res['message'];
      }
    } catch (e) {
      _errorMessage = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
    return false;
  }

  Future<bool> createWorkspace({
    required String name,
    required String businessType,
    String? country,
    String? billingEmail,
  }) async {
    _isLoading = true;
    notifyListeners();

    try {
      final res = await _service.createOrganization(
        name: name,
        businessType: businessType,
        country: country,
        billingEmail: billingEmail,
      );

      if (res['success'] == true) {
        _currentOrganization = res['organization'] as OrganizationModel?;
        await loadWorkspaces();
        return true;
      } else {
        _errorMessage = res['message'];
      }
    } catch (e) {
      _errorMessage = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
    return false;
  }

  Future<bool> saveIntent(String intent) async {
    _onboardingIntent = intent;
    notifyListeners();
    return await _service.saveOnboardingIntent(intent);
  }
}

