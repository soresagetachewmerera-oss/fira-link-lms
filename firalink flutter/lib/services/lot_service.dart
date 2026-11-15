import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;

import '../models/lot_model.dart';
import 'api_exception.dart';
import 'base_service.dart';
import 'upload_file.dart';

/// Seller lots and lot evidence (slice 4). Every call throws [ApiException] on failure;
/// nothing is faked. Lots belong to the active business on the server.
class LotService extends BaseService {
  static const Duration _timeout = Duration(milliseconds: 12000);
  static const Duration _uploadTimeout = Duration(seconds: 90);

  String get _base => '${BaseService.baseUrl}/marketplace/seller/lots';

  Map<String, dynamic> _data(http.Response res) {
    final decoded = jsonDecode(requireSuccess(res).body);
    final data = decoded is Map ? decoded['data'] : null;
    return data is Map<String, dynamic> ? data : <String, dynamic>{};
  }

  Future<http.Response> _send(String method, String url, [Map<String, dynamic>? body]) {
    return guardedRequest(() async {
      final headers = await getHeaders();
      final uri = Uri.parse(url);
      final encoded = body == null ? null : jsonEncode(body);
      switch (method) {
        case 'POST':
          return http.post(uri, headers: headers, body: encoded).timeout(_timeout);
        case 'PUT':
          return http.put(uri, headers: headers, body: encoded).timeout(_timeout);
        case 'DELETE':
          return http.delete(uri, headers: headers).timeout(_timeout);
        default:
          return http.get(uri, headers: headers).timeout(_timeout);
      }
    });
  }

  Future<SellerLots> getLots() async => SellerLots.fromJson(_data(await _send('GET', _base)));

  /// Seller home: counts and the to-do list of the active seller business.
  Future<Map<String, dynamic>> home() async => _data(await _send('GET', '${BaseService.baseUrl}/marketplace/seller/home'));

  Future<SellerLot> getLot(int id) async => SellerLot.fromJson(_data(await _send('GET', '$_base/$id')));

  Future<SellerLot> createDraft(Map<String, dynamic> fields) async => SellerLot.fromJson(_data(await _send('POST', _base, fields)));

  Future<SellerLot> update(int id, Map<String, dynamic> fields) async => SellerLot.fromJson(_data(await _send('PUT', '$_base/$id', fields)));

  /// Throws ApiException (VERIFICATION_REQUIRED below Tier 1, limit_reached at the plan's lot count).
  Future<SellerLot> publish(int id) async => SellerLot.fromJson(_data(await _send('POST', '$_base/$id/publish')));

  Future<SellerLot> pause(int id) async => SellerLot.fromJson(_data(await _send('POST', '$_base/$id/pause')));

  Future<void> delete(int id) async => requireSuccess(await _send('DELETE', '$_base/$id'));

  Future<void> withdrawEvidence(int lotId, int evidenceId) async =>
      requireSuccess(await _send('DELETE', '$_base/$lotId/evidence/$evidenceId'));

  Future<http.Response> _multipart(String url, Map<String, String> fields, List<http.MultipartFile> Function() files) {
    return guardedRequest(() async {
      final token = await getToken();
      final request = http.MultipartRequest('POST', Uri.parse(url));
      request.headers.addAll({
        'Accept': 'application/json',
        if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token',
      });
      request.fields.addAll(fields);
      request.files.addAll(files());
      final streamed = await request.send().timeout(_uploadTimeout);
      return http.Response.fromStream(streamed);
    });
  }

  /// An in-app photo with the time and (if allowed) the place it was taken.
  Future<void> uploadPhoto({
    required int lotId,
    required String kind,
    required UploadFile photo,
    required DateTime capturedAt,
    double? latitude,
    double? longitude,
    int? accuracyMeters,
  }) async {
    requireSuccess(await _multipart('$_base/$lotId/evidence/photos', {
      'kind': kind,
      'captured_at': capturedAt.toUtc().toIso8601String(),
      if (latitude != null && longitude != null) 'latitude': latitude.toString(),
      if (latitude != null && longitude != null) 'longitude': longitude.toString(),
      if (accuracyMeters != null) 'accuracy_m': accuracyMeters.toString(),
    }, () => [photo.toMultipart('photo')]));
  }

  /// A lab or cupping report. Returns the raw response for DocumentCaptureScreen.
  Future<http.Response> uploadLabReport({
    required int lotId,
    required List<UploadFile> pages,
    required String labName,
    String? reportNumber,
    DateTime? issuedAt,
  }) {
    return _multipart('$_base/$lotId/evidence/lab-report', {
      'lab_name': labName,
      if (reportNumber != null && reportNumber.isNotEmpty) 'report_number': reportNumber,
      if (issuedAt != null)
        'issued_at': '${issuedAt.year.toString().padLeft(4, '0')}-${issuedAt.month.toString().padLeft(2, '0')}-${issuedAt.day.toString().padLeft(2, '0')}',
    }, () => [for (final p in pages) p.toMultipart('pages[]')]);
  }
}
