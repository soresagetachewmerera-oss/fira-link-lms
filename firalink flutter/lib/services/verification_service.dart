import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;

import '../models/verification_model.dart';
import 'api_exception.dart';
import 'base_service.dart';
import 'upload_file.dart';

/// Verification dossier and the public seller check (slice 3).
/// Every call throws [ApiException] on failure; nothing is faked.
class VerificationService extends BaseService {
  static const Duration _timeout = Duration(milliseconds: 12000);
  static const Duration _uploadTimeout = Duration(seconds: 90);

  Map<String, dynamic> _data(http.Response res) {
    final decoded = jsonDecode(requireSuccess(res).body);
    final data = decoded is Map ? decoded['data'] : null;
    return data is Map<String, dynamic> ? data : <String, dynamic>{};
  }

  Future<VerificationDossier> getDossier() async {
    final res = await guardedRequest(() async {
      final headers = await getHeaders();
      return http.get(Uri.parse('${BaseService.baseUrl}/marketplace/verification/status'), headers: headers).timeout(_timeout);
    });
    return VerificationDossier.fromJson(_data(res));
  }

  /// POST /marketplace/verification/documents (multipart). Returns the raw response so the
  /// caller can run it through FreshAuthSheet (the server may ask for a fresh sign-in).
  Future<http.Response> uploadDocument({
    required String documentType,
    required List<UploadFile> pages,
    String? documentNumber,
    DateTime? issuedAt,
    DateTime? expiresAt,
    Map<String, String> details = const {},
  }) {
    return guardedRequest(() async {
      final token = await getToken();
      final request = http.MultipartRequest('POST', Uri.parse('${BaseService.baseUrl}/marketplace/verification/documents'));
      request.headers.addAll({
        'Accept': 'application/json',
        if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token',
      });
      request.fields['document_type'] = documentType;
      if (documentNumber != null && documentNumber.isNotEmpty) request.fields['document_number'] = documentNumber;
      if (issuedAt != null) request.fields['issued_at'] = _date(issuedAt);
      if (expiresAt != null) request.fields['expires_at'] = _date(expiresAt);
      details.forEach((k, v) => request.fields['details[$k]'] = v);
      for (final page in pages) {
        request.files.add(page.toMultipart('pages[]'));
      }
      final streamed = await request.send().timeout(_uploadTimeout);
      return http.Response.fromStream(streamed);
    });
  }

  /// GET /sellers/{firaId}. Unknown IDs throw ApiException with code NOT_FOUND.
  Future<PublicSeller> getPublicSeller(String firaId) async {
    final res = await guardedRequest(() async {
      final headers = await getHeaders();
      return http
          .get(Uri.parse('${BaseService.baseUrl}/sellers/${Uri.encodeComponent(firaId.trim())}'), headers: headers)
          .timeout(_timeout);
    });
    return PublicSeller.fromJson(_data(res));
  }

  static String _date(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';
}
