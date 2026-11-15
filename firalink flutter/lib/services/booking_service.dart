import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;

import 'api_exception.dart';
import 'base_service.dart';
import 'upload_file.dart';

/// Event tickets and tour bookings with Chapa or a bank slip (slice 8). Throws [ApiException] on
/// failure. A bank slip makes the booking "In review" until Fira Link finance confirms it.
class BookingService extends BaseService {
  static const Duration _timeout = Duration(seconds: 30);
  static const Duration _uploadTimeout = Duration(seconds: 90);

  Future<Map<String, dynamic>> _post(String path, Map<String, dynamic> fields, UploadFile? slip) async {
    final url = Uri.parse('${BaseService.baseUrl}$path');
    final res = requireSuccess(await guardedRequest(() async {
      if (slip != null) {
        final token = await getToken();
        final request = http.MultipartRequest('POST', url)
          ..headers.addAll({'Accept': 'application/json', if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token'})
          ..fields.addAll(fields.map((k, v) => MapEntry(k, '$v'))..removeWhere((_, v) => v == 'null' || v.isEmpty))
          ..files.add(slip.toMultipart('slip'));
        return http.Response.fromStream(await request.send().timeout(_uploadTimeout));
      }
      return http.post(url, headers: await getHeaders(), body: jsonEncode(fields)).timeout(_timeout);
    }));
    final decoded = jsonDecode(res.body);
    return decoded is Map<String, dynamic> ? decoded : <String, dynamic>{};
  }

  /// fields: first_name, last_name, phone, company, seats, ticket_type, payment_method, bank_reference.
  Future<Map<String, dynamic>> bookEvent(int eventId, Map<String, dynamic> fields, {UploadFile? slip}) => _post('/events/$eventId/book', fields, slip);

  /// fields: first_name, last_name, phone, participants, schedule_date, special_requests, payment_method, bank_reference.
  Future<Map<String, dynamic>> bookTour(int tourId, Map<String, dynamic> fields, {UploadFile? slip}) => _post('/tours/$tourId/book', fields, slip);

  Future<Map<String, dynamic>> payTourBalance(int bookingId, {required String method, UploadFile? slip, String? bankReference}) =>
      _post('/user/tour-bookings/$bookingId/pay-balance', {'payment_method': method, 'bank_reference': ?bankReference}, slip);

  Future<Map<String, dynamic>> verifyChapa({required bool tour, required String txRef, required int bookingId}) =>
      _post(tour ? '/tours/payments/chapa/verify' : '/events/payments/chapa/verify', {'tx_ref': txRef, 'booking_id': bookingId}, null);
}
