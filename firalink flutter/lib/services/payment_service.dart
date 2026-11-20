import 'dart:io';
import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/config.dart';
import '../models/payment.dart';
import 'api_exception.dart';
import 'auth_service.dart';
import 'session_reset.dart';

class PaymentService {
  /// Throws [ApiException] when the list cannot be loaded (signed out, offline, server error), so
  /// the screen shows an error with Retry instead of "no payments".
  Future<List<Payment>> getMyPayments({int page = 1}) async {
    final token = await AuthService().getToken();
    if (token == null) throw const ApiException(statusCode: 401);

    final response = requireSuccess(await guardedRequest(() => http.get(
          Uri.parse('${AppConfig.baseUrl}/api/user/payments?page=$page'),
          headers: {'Authorization': 'Bearer $token', 'Accept': 'application/json'},
        )));

    final data = json.decode(response.body);
    if (data is! Map || data['success'] != true) throw ApiException.fromResponse(response);
    final List<dynamic> paymentsData = data['data']['data'];
    return paymentsData.map((json) => Payment.fromJson(json)).toList();
  }

  Future<String?> downloadReceipt(int paymentId, String fileName) async {
    final token = await AuthService().getToken();
    if (token == null) return null;

    try {
      final response = await http.get(
        Uri.parse('${AppConfig.baseUrl}/api/user/payments/$paymentId/receipt'),
        headers: {'Authorization': 'Bearer $token'},
      );

      if (response.statusCode == 200) {
        // Personal file: in the receipts folder, which sign-out deletes (SessionReset).
        final directory = await SessionReset.receiptsDirectory();
        final filePath = '${directory.path}/$fileName';
        final file = File(filePath);
        await file.writeAsBytes(response.bodyBytes);
        return filePath;
      } else {
        return null;
      }
    } catch (e) {
      // debugPrint('Download error: $e');
      return null;
    }
  }
}
