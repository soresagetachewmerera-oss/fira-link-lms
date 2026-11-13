import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'base_service.dart';

class TrustSafetyService extends BaseService {
  static const Duration _timeout = Duration(milliseconds: 12000);

  /// Public registry lookup for seller verification by slug, ID, or TIN
  Future<Map<String, dynamic>> verifySeller(String query) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/verify-seller/$query');
      final res = await http.get(url, headers: headers).timeout(_timeout);

      final decoded = jsonDecode(res.body);
      if (res.statusCode == 200 && decoded['success'] == true) {
        return {
          'success': true,
          'data': decoded['data'],
        };
      }
      return {
        'success': false,
        'message': decoded['message'] ?? 'Seller not verified or not found',
      };
    } catch (e) {
      return {'success': false, 'message': e.toString()};
    }
  }

  /// Submit Trust & Safety incident or scam report
  Future<Map<String, dynamic>> submitReport({
    required String reason,
    required String details,
    int? reportedUserId,
    int? reportedOrganizationId,
    int? reportedListingId,
    File? evidence,
  }) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/reports');

      if (evidence != null) {
        final request = http.MultipartRequest('POST', url);
        request.headers.addAll(headers);
        request.fields['reason'] = reason;
        request.fields['details'] = details;
        if (reportedUserId != null) request.fields['reported_user_id'] = reportedUserId.toString();
        if (reportedOrganizationId != null) request.fields['reported_organization_id'] = reportedOrganizationId.toString();
        if (reportedListingId != null) request.fields['reported_listing_id'] = reportedListingId.toString();
        request.files.add(await http.MultipartFile.fromPath('evidence', evidence.path));
        final streamed = await request.send();
        final res = await http.Response.fromStream(streamed);
        final decoded = jsonDecode(res.body);
        return {
          'success': res.statusCode == 201 && decoded['success'] == true,
          'message': decoded['message'] ?? 'Report submitted successfully',
        };
      } else {
        final res = await http
            .post(
              url,
              headers: headers,
              body: jsonEncode({
                'reason': reason,
                'details': details,
                'reported_user_id': reportedUserId,
                'reported_organization_id': reportedOrganizationId,
                'reported_listing_id': reportedListingId,
              }),
            )
            .timeout(_timeout);

        final decoded = jsonDecode(res.body);
        return {
          'success': res.statusCode == 201 && decoded['success'] == true,
          'message': decoded['message'] ?? 'Report submitted successfully',
        };
      }
    } catch (e) {
      return {'success': false, 'message': e.toString()};
    }
  }
}

