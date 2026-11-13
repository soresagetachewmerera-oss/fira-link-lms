import 'dart:async';
import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/organization_model.dart';
import 'base_service.dart';

class OrganizationService extends BaseService {
  static const Duration _timeout = Duration(milliseconds: 10000);

  /// Fetch all organizations user belongs to, plus current active workspace
  Future<Map<String, dynamic>> getUserOrganizations() async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/user/organizations');
      final res = await http.get(url, headers: headers).timeout(_timeout);

      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        final data = decoded['data'] ?? {};
        final currentOrgJson = data['current_organization'];
        final orgsList = (data['organizations'] as List? ?? [])
            .map((o) => OrganizationModel.fromJson(o as Map<String, dynamic>))
            .toList();

        return {
          'success': true,
          'current_organization': currentOrgJson != null
              ? OrganizationModel.fromJson(currentOrgJson as Map<String, dynamic>)
              : null,
          'organizations': orgsList,
          'onboarding_intent': data['onboarding_intent']?.toString(),
        };
      }
      return {'success': false, 'message': 'Failed to load workspaces'};
    } catch (e) {
      return {'success': false, 'message': e.toString()};
    }
  }

  /// Create a new business workspace
  Future<Map<String, dynamic>> createOrganization({
    required String name,
    required String businessType,
    String? country,
    String? billingEmail,
  }) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/user/organizations');
      final res = await http
          .post(
            url,
            headers: headers,
            body: jsonEncode({
              'name': name,
              'business_type': businessType,
              'country': country ?? 'Ethiopia',
              'billing_email': billingEmail,
            }),
          )
          .timeout(_timeout);

      final decoded = jsonDecode(res.body);
      if (res.statusCode == 201 && decoded['success'] == true) {
        return {
          'success': true,
          'message': decoded['message'],
          'organization': OrganizationModel.fromJson(decoded['data']),
        };
      }
      return {
        'success': false,
        'message': decoded['message'] ?? 'Failed to create workspace',
      };
    } catch (e) {
      return {'success': false, 'message': e.toString()};
    }
  }

  /// Switch active workspace
  Future<Map<String, dynamic>> switchWorkspace(int organizationId) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/user/organizations/switch');
      final res = await http
          .post(
            url,
            headers: headers,
            body: jsonEncode({'organization_id': organizationId}),
          )
          .timeout(_timeout);

      final decoded = jsonDecode(res.body);
      if (res.statusCode == 200 && decoded['success'] == true) {
        return {
          'success': true,
          'message': decoded['message'],
          'organization': OrganizationModel.fromJson(decoded['data']),
        };
      }
      return {
        'success': false,
        'message': decoded['message'] ?? 'Failed to switch workspace',
      };
    } catch (e) {
      return {'success': false, 'message': e.toString()};
    }
  }

  /// Get members of an organization
  Future<List<OrganizationMemberModel>> getMembers(int organizationId) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/organizations/$organizationId/members');
      final res = await http.get(url, headers: headers).timeout(_timeout);

      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        final list = (decoded['data']?['members'] as List? ?? [])
            .map((m) => OrganizationMemberModel.fromJson(m as Map<String, dynamic>))
            .toList();
        return list;
      }
    } catch (_) {}
    return [];
  }

  /// Add / invite member
  Future<Map<String, dynamic>> addMember(int organizationId, String email, String role) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/organizations/$organizationId/members');
      final res = await http
          .post(
            url,
            headers: headers,
            body: jsonEncode({'email': email, 'role': role}),
          )
          .timeout(_timeout);

      final decoded = jsonDecode(res.body);
      return {
        'success': res.statusCode == 200 && decoded['success'] == true,
        'message': decoded['message'] ?? 'Operation completed',
      };
    } catch (e) {
      return {'success': false, 'message': e.toString()};
    }
  }

  /// Remove member
  Future<bool> removeMember(int organizationId, int userId) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/organizations/$organizationId/members/$userId');
      final res = await http.delete(url, headers: headers).timeout(_timeout);
      return res.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  /// Save onboarding intent (export, buy, learn, events)
  Future<bool> saveOnboardingIntent(String intent) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/user/onboarding-intent');
      final res = await http
          .post(
            url,
            headers: headers,
            body: jsonEncode({'intent': intent}),
          )
          .timeout(_timeout);
      return res.statusCode == 200;
    } catch (_) {
      return false;
    }
  }
}

