import 'dart:convert';
import 'package:http/http.dart' as http;
import 'base_service.dart';

class AuthService extends BaseService {
  Future<http.Response> login(String email, String password) async {
    final url = Uri.parse('${BaseService.baseUrl}/auth/login');
    return await http.post(
      url,
      headers: await getHeaders(),
      body: jsonEncode({'email': email, 'password': password}),
    );
  }

  Future<http.Response> verifyOtp(String email, String code) async {
    final url = Uri.parse('${BaseService.baseUrl}/auth/login/otp/verify');
    return await http.post(
      url,
      headers: await getHeaders(),
      body: jsonEncode({'email': email, 'code': code}),
    );
  }

  Future<http.Response> resendOtp(String email) async {
    final url = Uri.parse('${BaseService.baseUrl}/auth/login/otp/resend');
    return await http.post(
      url,
      headers: await getHeaders(),
      body: jsonEncode({'email': email}),
    );
  }

  Future<http.Response> register(Map<String, dynamic> data) async {
    final url = Uri.parse('${BaseService.baseUrl}/auth/register');
    return await http.post(
      url,
      headers: await getHeaders(),
      body: jsonEncode(data),
    );
  }

  Future<http.Response> verifyRegistrationOtp(String phone, String code) async {
    final url = Uri.parse('${BaseService.baseUrl}/auth/register/verify');
    return await http.post(
      url,
      headers: await getHeaders(),
      body: jsonEncode({'phone': phone, 'code': code}),
    );
  }

  Future<http.Response> resendRegistrationOtp(String phone) async {
    final url = Uri.parse('${BaseService.baseUrl}/auth/register/resend');
    return await http.post(
      url,
      headers: await getHeaders(),
      body: jsonEncode({'phone': phone}),
    );
  }

  Future<http.Response> changeRegistrationPhone(String oldPhone, String newPhone) async {
    final url = Uri.parse('${BaseService.baseUrl}/auth/register/change-phone');
    return await http.post(
      url,
      headers: await getHeaders(),
      body: jsonEncode({'old_phone': oldPhone, 'new_phone': newPhone}),
    );
  }

  Future<http.Response> logout() async {
    final url = Uri.parse('${BaseService.baseUrl}/logout');
    return await http.post(url, headers: await getHeaders());
  }

  Future<http.Response> getUser() async {
    final url = Uri.parse('${BaseService.baseUrl}/user');
    return await http.get(url, headers: await getHeaders());
  }

  Future<http.Response> sendPhoneOtp(String phone) async {
    final url = Uri.parse('${BaseService.baseUrl}/auth/phone/verify/send');
    return await http.post(
      url,
      headers: await getHeaders(),
      body: jsonEncode({'phone': phone}),
    );
  }

  Future<http.Response> verifyPhoneOtp(String phone, String code) async {
    final url = Uri.parse('${BaseService.baseUrl}/auth/phone/verify/check');
    return await http.post(
      url,
      headers: await getHeaders(),
      body: jsonEncode({'phone': phone, 'code': code}),
    );
  }

  Future<http.Response> sendPasswordResetOtp(String phone) async {
    final url = Uri.parse('${BaseService.baseUrl}/auth/password/forgot/otp');
    return await http.post(
      url,
      headers: await getHeaders(),
      body: jsonEncode({'phone': phone}),
    );
  }

  Future<http.Response> resetPasswordWithOtp(
    String phone,
    String code,
    String password,
    String passwordConfirmation,
  ) async {
    final url = Uri.parse('${BaseService.baseUrl}/auth/password/reset/otp');
    return await http.post(
      url,
      headers: await getHeaders(),
      body: jsonEncode({
        'phone': phone,
        'code': code,
        'password': password,
        'password_confirmation': passwordConfirmation,
      }),
    );
  }
}
