import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;

import '../models/trade_model.dart';
import 'api_exception.dart';
import 'base_service.dart';
import 'upload_file.dart';

/// Reports and blocks (slice 7). Throws [ApiException] on failure; nothing is reported as sent
/// unless the server said so. The server works out the reported business from what is reported.
class TrustSafetyService extends BaseService {
  static const Duration _timeout = Duration(milliseconds: 15000);
  static const Duration _uploadTimeout = Duration(seconds: 60);

  /// Reason codes in the order the report sheet shows them.
  static const reasons = ['fake_photos', 'pay_outside', 'fake_documents', 'impersonation', 'not_as_described', 'other'];

  /// Returns whether the business was blocked as part of the report.
  Future<bool> submitReport({
    required String reasonCode,
    String? details,
    int? reportedOrganizationId,
    int? reportedListingId,
    int? reportedMessageId,
    int? reportedDealRoomId,
    int? reportedSampleId,
    String? reportedFiraId,
    bool block = false,
    UploadFile? evidence,
  }) async {
    final fields = <String, String>{
      'reason_code': reasonCode,
      if (details != null && details.trim().isNotEmpty) 'details': details.trim(),
      if (reportedOrganizationId != null) 'reported_organization_id': '$reportedOrganizationId',
      if (reportedListingId != null) 'reported_listing_id': '$reportedListingId',
      if (reportedMessageId != null) 'reported_message_id': '$reportedMessageId',
      if (reportedDealRoomId != null) 'reported_deal_room_id': '$reportedDealRoomId',
      if (reportedSampleId != null) 'reported_sample_id': '$reportedSampleId',
      'reported_fira_id': ?reportedFiraId,
      'block': block ? '1' : '0',
    };
    final url = Uri.parse('${BaseService.baseUrl}/reports');
    final res = requireSuccess(await guardedRequest(() async {
      if (evidence != null) {
        final token = await getToken();
        final request = http.MultipartRequest('POST', url)
          ..headers.addAll({'Accept': 'application/json', if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token'})
          ..fields.addAll(fields)
          ..files.add(evidence.toMultipart('evidence'));
        return http.Response.fromStream(await request.send().timeout(_uploadTimeout));
      }
      final headers = await getHeaders();
      return http.post(url, headers: headers, body: jsonEncode(fields)).timeout(_timeout);
    }));
    final data = (jsonDecode(res.body) as Map)['data'];
    return data is Map && data['blocked'] == true;
  }

  Future<List<BusinessSummary>> blocked() async {
    final res = requireSuccess(await guardedRequest(() async {
      final headers = await getHeaders();
      return http.get(Uri.parse('${BaseService.baseUrl}/business-blocks'), headers: headers).timeout(_timeout);
    }));
    final list = (jsonDecode(res.body) as Map)['data'] as List? ?? [];
    return list.whereType<Map>().map((b) => BusinessSummary.fromJson(b['business'])).whereType<BusinessSummary>().toList();
  }

  Future<void> block(int organizationId) async {
    requireSuccess(await guardedRequest(() async {
      final headers = await getHeaders();
      return http
          .post(Uri.parse('${BaseService.baseUrl}/business-blocks'), headers: headers, body: jsonEncode({'organization_id': organizationId}))
          .timeout(_timeout);
    }));
  }

  Future<void> unblock(int organizationId) async {
    requireSuccess(await guardedRequest(() async {
      final headers = await getHeaders();
      return http.delete(Uri.parse('${BaseService.baseUrl}/business-blocks/$organizationId'), headers: headers).timeout(_timeout);
    }));
  }
}
