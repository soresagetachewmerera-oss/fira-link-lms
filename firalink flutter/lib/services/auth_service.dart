import 'dart:async';
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'api_exception.dart';
import 'base_service.dart';

class AuthService extends BaseService {
  static const Duration _timeout = Duration(seconds: 20);

  Future<http.Response> _post(String path, Map<String, dynamic> body) {
    return guardedRequest(() async {
      return http
          .post(
            Uri.parse('${BaseService.baseUrl}$path'),
            headers: await getHeaders(),
            body: jsonEncode(body),
          )
          .timeout(_timeout);
    });
  }

  // ── Phone sign-in (one step for new and returning users) ──────────────────

  /// Sends a 6-digit code by SMS. Throws [ApiException.network] when offline.
  Future<http.Response> phoneStart(String phone, {String countryCode = '+251'}) =>
      _post('/auth/phone/start', {'phone': phone, 'country_code': countryCode});

  /// SMS failed: ask for the code by email (same answer whether or not an account exists).
  Future<http.Response> phoneEmailFallback(String phone) =>
      _post('/auth/phone/email-fallback', {'phone': phone});

  Future<http.Response> phoneVerify(String phone, String code) =>
      _post('/auth/phone/verify', {'phone': phone, 'code': code});

  Future<http.Response> phoneComplete(String signupTicket, String fullName) =>
      _post('/auth/phone/complete', {'signup_ticket': signupTicket, 'full_name': fullName});

  // ── Email and password sign-up (no phone needed) ──────────────────────────

  Future<http.Response> emailSignUpStart({
    required String fullName,
    required String email,
    required String password,
    required String confirmation,
  }) =>
      _post('/auth/email/register/start', {
        'full_name': fullName,
        'email': email,
        'password': password,
        'password_confirmation': confirmation,
        'terms': true,
      });

  Future<http.Response> emailSignUpVerify(String email, String code) =>
      _post('/auth/email/register/verify', {'email': email, 'code': code});

  // ── Forgot password by phone or email ─────────────────────────────────────

  Future<http.Response> passwordForgot({required String channel, required String value}) =>
      _post('/auth/password/forgot', {'channel': channel, channel: value});

  Future<http.Response> passwordVerifyCode({required String channel, required String value, required String code}) =>
      _post('/auth/password/verify-code', {'channel': channel, channel: value, 'code': code});

  Future<http.Response> passwordReset({required String ticket, required String password, required String confirmation}) =>
      _post('/auth/password/reset', {'reset_ticket': ticket, 'password': password, 'password_confirmation': confirmation});

  // ── Fresh authentication for sensitive actions ────────────────────────────

  Future<http.Response> stepUpSendCode() => _post('/auth/step-up/code', {});

  Future<http.Response> stepUpVerify({String? code, String? password}) => _post('/auth/step-up/verify', {
        'code': ?code,
        'password': ?password,
      });

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
