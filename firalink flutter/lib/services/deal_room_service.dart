import 'dart:async';
import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/deal_room_model.dart';
import 'api_exception.dart';
import 'base_service.dart';
import 'upload_file.dart';

/// Deal room API client.
///
/// Action methods return `{'success': bool, 'message': String?, 'error': ApiException?}`.
/// On failure, `error` is set so screens can show a localized message. Nothing is
/// ever reported as successful unless the server said so.
///
/// Uploads use [UploadFile] (bytes in memory), so they work on Android, iOS and web.
class DealRoomService extends BaseService {
  static const Duration _timeout = Duration(milliseconds: 15000);
  static const Duration _uploadTimeout = Duration(seconds: 60);

  Map<String, dynamic> _result(http.Response res, {int okStatus = 200}) {
    Map<String, dynamic>? decoded;
    try {
      final body = jsonDecode(res.body);
      if (body is Map<String, dynamic>) decoded = body;
    } catch (_) {}

    final ok = res.statusCode == okStatus && decoded?['success'] == true;
    return {
      'success': ok,
      'message': decoded?['message'],
      'data': decoded?['data'],
      if (!ok) 'error': ApiException.fromResponse(res),
    };
  }

  Map<String, dynamic> _failure(Object error) => {
        'success': false,
        'message': null,
        'error': error is ApiException ? error : const ApiException.network(),
      };

  Future<http.Response> _multipart(
    Uri url,
    Map<String, String> fields,
    String fileField,
    UploadFile file,
  ) async {
    final headers = await getHeaders();
    headers.remove('Content-Type'); // multipart sets its own boundary
    final request = http.MultipartRequest('POST', url)
      ..headers.addAll(headers)
      ..fields.addAll(fields)
      ..files.add(file.toMultipart(fileField));
    final streamed = await request.send().timeout(_uploadTimeout);
    return http.Response.fromStream(streamed);
  }

  /// List user's deal rooms. Throws [ApiException] on failure.
  Future<List<DealRoomModel>> getDealRooms() async {
    final res = requireSuccess(await guardedRequest(() async {
      final headers = await getHeaders();
      return http.get(Uri.parse('${BaseService.baseUrl}/deal-rooms'), headers: headers).timeout(_timeout);
    }));
    final decoded = jsonDecode(res.body);
    return (decoded['data'] as List? ?? [])
        .map((item) => DealRoomModel.fromJson(item as Map<String, dynamic>))
        .toList();
  }

  /// Get single deal room details. Throws [ApiException] on failure.
  Future<DealRoomModel> getDealRoom(int id) async {
    final res = requireSuccess(await guardedRequest(() async {
      final headers = await getHeaders();
      return http.get(Uri.parse('${BaseService.baseUrl}/deal-rooms/$id'), headers: headers).timeout(_timeout);
    }));
    final decoded = jsonDecode(res.body);
    return DealRoomModel.fromJson(decoded['data'] as Map<String, dynamic>);
  }

  /// Open new deal room (buyer only; the server takes buyer and seller from the quote or lot).
  Future<Map<String, dynamic>> openDealRoom({
    int? listingId,
    int? quotationId,
    required String title,
    required double targetValueUsd,
    String incoterm = 'FOB Djibouti',
  }) async {
    try {
      final res = await guardedRequest(() async {
        final headers = await getHeaders();
        return http
            .post(
              Uri.parse('${BaseService.baseUrl}/deal-rooms'),
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
      });
      // 201: a new deal room; 200: the deal room already opened from this quote.
      final result = _result(res, okStatus: res.statusCode == 200 ? 200 : 201);
      if (result['success'] == true) {
        result['deal_room'] = DealRoomModel.fromJson(result['data'] as Map<String, dynamic>);
      }
      return result;
    } catch (e) {
      return _failure(e);
    }
  }

  /// Sign bilateral export contract, optionally attaching the contract PDF.
  Future<Map<String, dynamic>> signContract({
    required int dealRoomId,
    required String signature,
    UploadFile? contractDocument,
  }) async {
    try {
      final url = Uri.parse('${BaseService.baseUrl}/deal-rooms/$dealRoomId/sign-contract');
      final res = await guardedRequest(() async {
        if (contractDocument != null) {
          return _multipart(url, {'signature': signature}, 'contract_document', contractDocument);
        }
        final headers = await getHeaders();
        return http.post(url, headers: headers, body: jsonEncode({'signature': signature})).timeout(_timeout);
      });
      return _result(res);
    } catch (e) {
      return _failure(e);
    }
  }

  /// Upload proof for a milestone (bank slip, SWIFT copy, inspection report, B/L)
  /// or mark it in progress. The client never marks a milestone done: proof moves it
  /// to "proof_submitted", and Fira Link finance confirms it after the bank does.
  Future<Map<String, dynamic>> advanceMilestone({
    required int dealRoomId,
    required int milestoneId,
    String? notes,
    UploadFile? document,
    String? paymentMethod,
  }) async {
    try {
      final url = Uri.parse('${BaseService.baseUrl}/deal-rooms/$dealRoomId/milestones/$milestoneId/advance');
      final res = await guardedRequest(() async {
        if (document != null) {
          return _multipart(url, {
            if (notes != null && notes.isNotEmpty) 'notes': notes,
            'payment_method': ?paymentMethod,
          }, 'document', document);
        }
        final headers = await getHeaders();
        return http
            .post(url, headers: headers, body: jsonEncode({'status': 'in_progress', 'notes': notes}))
            .timeout(_timeout);
      });
      return _result(res);
    } catch (e) {
      return _failure(e);
    }
  }

  /// Raise dispute, optionally with evidence.
  Future<Map<String, dynamic>> raiseDispute({
    required int dealRoomId,
    required String reason,
    required String details,
    UploadFile? evidence,
  }) async {
    try {
      final url = Uri.parse('${BaseService.baseUrl}/deal-rooms/$dealRoomId/dispute');
      final res = await guardedRequest(() async {
        if (evidence != null) {
          return _multipart(url, {'reason': reason, 'details': details}, 'evidence', evidence);
        }
        final headers = await getHeaders();
        return http
            .post(url, headers: headers, body: jsonEncode({'reason': reason, 'details': details}))
            .timeout(_timeout);
      });
      return _result(res);
    } catch (e) {
      return _failure(e);
    }
  }

  /// Complete deal room (server allows it only after final settlement is confirmed).
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
