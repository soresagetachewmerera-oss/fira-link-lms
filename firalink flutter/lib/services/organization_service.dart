import 'dart:async';
import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/organization_model.dart';
import 'api_exception.dart';
import 'base_service.dart';

/// Workspaces (slice 2). Every call throws [ApiException] on failure; nothing is faked.
class OrganizationService extends BaseService {
  static const Duration _timeout = Duration(milliseconds: 10000);

  Map<String, dynamic> _data(http.Response res) {
    final decoded = jsonDecode(requireSuccess(res).body);
    final data = decoded is Map ? decoded['data'] : null;
    return data is Map<String, dynamic> ? data : <String, dynamic>{};
  }

  /// GET /workspaces/options — entity types, roles and countries for Business setup.
  Future<WorkspaceOptions> getOptions() async {
    final res = await guardedRequest(() async {
      final headers = await getHeaders();
      return http.get(Uri.parse('${BaseService.baseUrl}/workspaces/options'), headers: headers).timeout(_timeout);
    });
    return WorkspaceOptions.fromJson(_data(res));
  }

  /// GET /user/organizations — Personal plus every business the user is an active member of.
  Future<WorkspaceSnapshot> getUserOrganizations() async {
    final res = await guardedRequest(() async {
      final headers = await getHeaders();
      return http.get(Uri.parse('${BaseService.baseUrl}/user/organizations'), headers: headers).timeout(_timeout);
    });
    final data = _data(res);
    final current = data['current_organization'];
    final limits = data['limits'] is Map ? data['limits'] as Map : const {};
    return WorkspaceSnapshot(
      current: current is Map<String, dynamic> ? OrganizationModel.fromJson(current) : null,
      organizations: (data['organizations'] as List? ?? [])
          .map((o) => OrganizationModel.fromJson(o as Map<String, dynamic>))
          .toList(),
      intents: (data['onboarding_intents'] as List? ?? []).map((e) => e.toString()).toList(),
      maxOwnedBusinesses: (limits['max_owned_businesses'] as num?)?.toInt() ?? 3,
      ownedBusinesses: (limits['owned_businesses'] as num?)?.toInt() ?? 0,
    );
  }

  /// POST /user/organizations — Business setup. Throws WORKSPACE_LIMIT at the account limit.
  Future<OrganizationModel> createOrganization({
    required String name,
    required String businessRole,
    required String entityType,
    required String country,
    String? tinNumber,
    String? billingEmail,
  }) async {
    final res = await guardedRequest(() async {
      final headers = await getHeaders();
      return http
          .post(
            Uri.parse('${BaseService.baseUrl}/user/organizations'),
            headers: headers,
            body: jsonEncode({
              'name': name,
              'business_role': businessRole,
              'entity_type': entityType,
              'country': country,
              if (tinNumber != null && tinNumber.isNotEmpty) 'tin_number': tinNumber,
              if (billingEmail != null && billingEmail.isNotEmpty) 'billing_email': billingEmail,
            }),
          )
          .timeout(_timeout);
    });
    return OrganizationModel.fromJson(_data(res));
  }

  /// POST /user/organizations/switch — null switches to Personal.
  Future<void> switchWorkspace(int? organizationId) async {
    final res = await guardedRequest(() async {
      final headers = await getHeaders();
      return http
          .post(
            Uri.parse('${BaseService.baseUrl}/user/organizations/switch'),
            headers: headers,
            body: jsonEncode({'organization_id': organizationId}),
          )
          .timeout(_timeout);
    });
    requireSuccess(res);
  }

  /// GET /organizations/{id}/members — read-only for members; the server says who can remove.
  Future<TeamRoster> getMembers(int organizationId) async {
    final res = await guardedRequest(() async {
      final headers = await getHeaders();
      return http
          .get(Uri.parse('${BaseService.baseUrl}/organizations/$organizationId/members'), headers: headers)
          .timeout(_timeout);
    });
    return TeamRoster.fromJson(_data(res));
  }

  /// DELETE /organizations/{id}/members/{userId} — owner only.
  Future<void> removeMember(int organizationId, int userId) async {
    final res = await guardedRequest(() async {
      final headers = await getHeaders();
      return http
          .delete(Uri.parse('${BaseService.baseUrl}/organizations/$organizationId/members/$userId'), headers: headers)
          .timeout(_timeout);
    });
    requireSuccess(res);
  }

  /// POST /user/onboarding-intent — multi-select; an empty list means skipped.
  /// Returns whether Business setup should follow (the server decides).
  Future<bool> saveOnboardingIntents(List<String> intents) async {
    final res = await guardedRequest(() async {
      final headers = await getHeaders();
      return http
          .post(
            Uri.parse('${BaseService.baseUrl}/user/onboarding-intent'),
            headers: headers,
            body: jsonEncode({'intents': intents}),
          )
          .timeout(_timeout);
    });
    return _data(res)['needs_business_setup'] == true;
  }
}

class WorkspaceSnapshot {
  final OrganizationModel? current;
  final List<OrganizationModel> organizations;
  final List<String> intents;
  final int maxOwnedBusinesses;
  final int ownedBusinesses;

  const WorkspaceSnapshot({
    required this.current,
    required this.organizations,
    required this.intents,
    required this.maxOwnedBusinesses,
    required this.ownedBusinesses,
  });
}
