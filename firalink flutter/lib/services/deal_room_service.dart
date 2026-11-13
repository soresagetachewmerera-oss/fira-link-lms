import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import '../models/deal_room_model.dart';
import 'base_service.dart';

class DealRoomService extends BaseService {
  static const Duration _timeout = Duration(milliseconds: 15000);

  /// List user's deal rooms
  Future<List<DealRoomModel>> getDealRooms() async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/deal-rooms');
      final res = await http.get(url, headers: headers).timeout(_timeout);

      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        final list = (decoded['data'] as List? ?? [])
            .map((item) => DealRoomModel.fromJson(item as Map<String, dynamic>))
            .toList();
        return list;
      }
    } catch (_) {}
    return [];
  }

  /// Get single deal room details
  Future<DealRoomModel?> getDealRoom(int id) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/deal-rooms/$id');
      final res = await http.get(url, headers: headers).timeout(_timeout);

      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        if (decoded['data'] != null) {
          return DealRoomModel.fromJson(decoded['data']);
        }
      }
    } catch (_) {}
    return null;
  }

  /// Open new deal room
  Future<Map<String, dynamic>> openDealRoom({
    int? listingId,
    int? quotationId,
    required String title,
    required double targetValueUsd,
    String incoterm = 'FOB Djibouti',
  }) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/deal-rooms');
      final res = await http
          .post(
            url,
            headers: headers,
            body: jsonEncode({
              'listing_id': listingId,
              'quotation_id': quotationId,
              'title': title,
              'target_value_usd': targetValueUsd,
              'incoterm': incoterm,
            }),
          )
          .timeout(_timeout);

      final decoded = jsonDecode(res.body);
      if (res.statusCode == 201 && decoded['success'] == true) {
        return {
          'success': true,
          'message': decoded['message'],
          'deal_room': DealRoomModel.fromJson(decoded['data']),
        };
      }
      return {
        'success': false,
        'message': decoded['message'] ?? 'Failed to open deal room',
      };
    } catch (e) {
      return {'success': false, 'message': e.toString()};
    }
  }

  /// Sign bilateral export contract
  Future<Map<String, dynamic>> signContract({
    required int dealRoomId,
    required String signature,
    File? contractDocument,
  }) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/deal-rooms/$dealRoomId/sign-contract');

      if (contractDocument != null) {
        final request = http.MultipartRequest('POST', url);
        request.headers.addAll(headers);
        request.fields['signature'] = signature;
        request.files.add(await http.MultipartFile.fromPath('contract_document', contractDocument.path));
        final streamed = await request.send();
        final res = await http.Response.fromStream(streamed);
        final decoded = jsonDecode(res.body);
        return {
          'success': res.statusCode == 200 && decoded['success'] == true,
          'message': decoded['message'] ?? 'Contract signed',
        };
      } else {
        final res = await http
            .post(
              url,
              headers: headers,
              body: jsonEncode({'signature': signature}),
            )
            .timeout(_timeout);
        final decoded = jsonDecode(res.body);
        return {
          'success': res.statusCode == 200 && decoded['success'] == true,
          'message': decoded['message'] ?? 'Contract signed',
        };
      }
    } catch (e) {
      return {'success': false, 'message': e.toString()};
    }
  }

  /// Advance milestone status & upload inspection/logistics proof
  Future<Map<String, dynamic>> advanceMilestone({
    required int dealRoomId,
    required int milestoneId,
    required String status,
    String? notes,
    File? document,
  }) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/deal-rooms/$dealRoomId/milestones/$milestoneId/advance');

      if (document != null) {
        final request = http.MultipartRequest('POST', url);
        request.headers.addAll(headers);
        request.fields['status'] = status;
        if (notes != null) request.fields['notes'] = notes;
        request.files.add(await http.MultipartFile.fromPath('document', document.path));
        final streamed = await request.send();
        final res = await http.Response.fromStream(streamed);
        final decoded = jsonDecode(res.body);
        return {
          'success': res.statusCode == 200 && decoded['success'] == true,
          'message': decoded['message'] ?? 'Milestone updated',
        };
      } else {
        final res = await http
            .post(
              url,
              headers: headers,
              body: jsonEncode({'status': status, 'notes': notes}),
            )
            .timeout(_timeout);
        final decoded = jsonDecode(res.body);
        return {
          'success': res.statusCode == 200 && decoded['success'] == true,
          'message': decoded['message'] ?? 'Milestone updated',
        };
      }
    } catch (e) {
      return {'success': false, 'message': e.toString()};
    }
  }

  /// Raise dispute
  Future<Map<String, dynamic>> raiseDispute({
    required int dealRoomId,
    required String reason,
    required String details,
    File? evidence,
  }) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/deal-rooms/$dealRoomId/dispute');

      if (evidence != null) {
        final request = http.MultipartRequest('POST', url);
        request.headers.addAll(headers);
        request.fields['reason'] = reason;
        request.fields['details'] = details;
        request.files.add(await http.MultipartFile.fromPath('evidence', evidence.path));
        final streamed = await request.send();
        final res = await http.Response.fromStream(streamed);
        final decoded = jsonDecode(res.body);
        return {
          'success': res.statusCode == 200 && decoded['success'] == true,
          'message': decoded['message'] ?? 'Dispute registered',
        };
      } else {
        final res = await http
            .post(
              url,
              headers: headers,
              body: jsonEncode({'reason': reason, 'details': details}),
            )
            .timeout(_timeout);
        final decoded = jsonDecode(res.body);
        return {
          'success': res.statusCode == 200 && decoded['success'] == true,
          'message': decoded['message'] ?? 'Dispute registered',
        };
      }
    } catch (e) {
      return {'success': false, 'message': e.toString()};
    }
  }

  /// Complete deal room
  Future<bool> completeDeal(int dealRoomId) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/deal-rooms/$dealRoomId/complete');
      final res = await http.post(url, headers: headers).timeout(_timeout);
      return res.statusCode == 200;
    } catch (_) {
      return false;
    }
  }
}

