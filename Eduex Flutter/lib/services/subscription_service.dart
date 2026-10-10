import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/subscription_plan_model.dart';
import '../models/user_subscription_model.dart';
import 'auth_service.dart';
import 'base_service.dart';

class SubscriptionService {
  final AuthService _authService = AuthService();

  Future<List<SubscriptionPlanModel>> getPlans() async {
    final url = Uri.parse('${BaseService.baseUrl}/subscription-plans');
    final response = await http.get(url, headers: {'Accept': 'application/json'});

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      if (json['success'] == true && json['data'] != null) {
        return (json['data'] as List)
            .map((e) => SubscriptionPlanModel.fromJson(e))
            .toList();
      }
    }
    return [];
  }

  Future<UserSubscriptionModel?> getUserSubscription() async {
    final token = await _authService.getToken();
    if (token == null) return null;

    final url = Uri.parse('${BaseService.baseUrl}/user/subscription');
    final response = await http.get(
      url,
      headers: {
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      if (json['success'] == true && json['has_active_subscription'] == true && json['data'] != null) {
        return UserSubscriptionModel.fromJson(json['data']);
      }
    }
    return null;
  }

  Future<Map<String, dynamic>> checkoutSubscription({
    required int planId,
    required String paymentMethod,
    String? transactionId,
    String? notes,
    String? receiptPath,
  }) async {
    final token = await _authService.getToken();
    if (token == null) throw Exception('Please login to continue.');

    final url = Uri.parse('${BaseService.baseUrl}/subscriptions/checkout');

    if (receiptPath != null && receiptPath.isNotEmpty) {
      final request = http.MultipartRequest('POST', url);
      request.headers['Accept'] = 'application/json';
      request.headers['Authorization'] = 'Bearer $token';
      request.fields['plan_id'] = planId.toString();
      request.fields['payment_method'] = paymentMethod;
      if (transactionId != null) request.fields['transaction_id'] = transactionId;
      if (notes != null) request.fields['notes'] = notes;
      request.files.add(await http.MultipartFile.fromPath('receipt_file', receiptPath));

      final streamedResponse = await request.send();
      final response = await http.Response.fromStream(streamedResponse);
      final json = jsonDecode(response.body);
      if (response.statusCode == 200 && json['success'] == true) {
        return json;
      }
      throw Exception(json['message'] ?? 'Subscription checkout failed.');
    }

    final response = await http.post(
      url,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
      body: jsonEncode({
        'plan_id': planId,
        'payment_method': paymentMethod,
        if (transactionId != null) 'transaction_id': transactionId,
        if (notes != null) 'notes': notes,
      }),
    );

    final json = jsonDecode(response.body);
    if (response.statusCode == 200 && json['success'] == true) {
      return json;
    }
    throw Exception(json['message'] ?? 'Subscription checkout failed.');
  }

  Future<bool> verifySubscriptionPayment({
    required int paymentId,
    String? paymentIntentId,
    String? razorpayPaymentId,
  }) async {
    final token = await _authService.getToken();
    if (token == null) return false;

    final url = Uri.parse('${BaseService.baseUrl}/subscriptions/verify');
    final response = await http.post(
      url,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
      body: jsonEncode({
        'payment_id': paymentId,
        if (paymentIntentId != null) 'payment_intent_id': paymentIntentId,
        if (razorpayPaymentId != null) 'razorpay_payment_id': razorpayPaymentId,
      }),
    );

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      return json['success'] == true;
    }
    return false;
  }

  Future<bool> enrollCourseViaSubscription(int courseId) async {
    final token = await _authService.getToken();
    if (token == null) return false;

    final url = Uri.parse('${BaseService.baseUrl}/subscriptions/enroll-course/$courseId');
    final response = await http.post(
      url,
      headers: {
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      return json['success'] == true;
    }
    return false;
  }
}
