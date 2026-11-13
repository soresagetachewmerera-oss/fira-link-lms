import 'dart:convert';
import 'package:flutter/material.dart';
import '../models/user_model.dart';
import '../services/auth_service.dart';
import '../services/profile_service.dart';
import '../services/push_notification_service.dart';

class AuthProvider with ChangeNotifier {
  final AuthService _authService = AuthService();

  User? _user;
  bool _isLoading = false;
  Map<String, dynamic>? _deletionRequestStatus;

  User? get user => _user;
  bool get isLoading => _isLoading;
  Map<String, dynamic>? get deletionRequestStatus => _deletionRequestStatus;
  bool get isAuthenticated => _user != null;

  Future<void> loadUser() async {
    final token = await _authService.getToken();
    if (token == null || token.isEmpty) return;

    try {
      final response = await _authService.getUser();
      if (response.statusCode == 200) {
        dynamic raw = jsonDecode(response.body);
        if (raw is Map<String, dynamic>) {
          _user = User.fromJson(raw);
          await checkDeletionRequestStatus();
          try {
            PushNotificationService().syncTokenWithServer();
          } catch (_) {}
        }
      } else {
        await _authService.clearToken();
      }
    } catch (e) {
      debugPrint('Error loading user: $e');
    }
    notifyListeners();
  }

  Future<Map<String, dynamic>> login(String email, String password) async {
    _isLoading = true;
    notifyListeners();

    try {
      final response = await _authService.login(email, password);

      dynamic rawData;
      try {
        rawData = jsonDecode(response.body);
      } catch (jsonErr) {
        return {
          'success': false,
          'message': 'Server error: Invalid response (${response.statusCode})',
        };
      }

      final Map<String, dynamic> data = (rawData is Map<String, dynamic>)
          ? rawData
          : <String, dynamic>{};

      if (response.statusCode == 200) {
        if (data['otp_required'] == true) {
          return {
            'success': true,
            'otp_required': true,
            'message': data['message'] ?? 'OTP sent to email',
            'email': data['email'] ?? email,
          };
        }

        final token = data['token']?.toString() ?? '';
        final userRaw = data['user'];
        if (userRaw != null && userRaw is Map<String, dynamic>) {
          _user = User.fromJson(userRaw);
        } else {
          _user = User.fromJson(data);
        }

        if (token.isNotEmpty) {
          await _authService.saveToken(token);
        }

        try {
          PushNotificationService().syncTokenWithServer();
        } catch (pushErr) {
          debugPrint('Push notification sync skipped: $pushErr');
        }

        return {
          'success': true,
          'otp_required': false,
          'message': data['message'] ?? 'Login successful',
        };
      } else {
        if (data['requires_phone_verification'] == true) {
          return {
            'success': false,
            'requires_phone_verification': true,
            'phone': data['phone'],
            'phone_masked': data['phone_masked'],
            'message': data['message'] ?? 'Please verify your phone number via SMS OTP',
            'cooldown_seconds': data['cooldown_seconds'] ?? 60,
          };
        }
        return {
          'success': false,
          'message': data['message'] ?? 'Invalid login credentials',
          'errors': data['errors'],
        };
      }
    } catch (e) {
      debugPrint('AuthProvider.login error: $e');
      return {'success': false, 'message': 'An error occurred: $e'};
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<Map<String, dynamic>> verifyOtp(String email, String code) async {
    _isLoading = true;
    notifyListeners();

    try {
      final response = await _authService.verifyOtp(email, code);

      dynamic rawData;
      try {
        rawData = jsonDecode(response.body);
      } catch (jsonErr) {
        return {
          'success': false,
          'message': 'Server error: Invalid response (${response.statusCode})',
        };
      }

      final Map<String, dynamic> data = (rawData is Map<String, dynamic>)
          ? rawData
          : <String, dynamic>{};

      if (response.statusCode == 200) {
        final token = data['token']?.toString() ?? '';
        final userRaw = data['user'];
        if (userRaw != null && userRaw is Map<String, dynamic>) {
          _user = User.fromJson(userRaw);
        }
        if (token.isNotEmpty) {
          await _authService.saveToken(token);
        }
        try {
          PushNotificationService().syncTokenWithServer();
        } catch (_) {}
        return {'success': true, 'message': data['message'] ?? 'Verification successful'};
      } else {
        return {'success': false, 'message': data['message'] ?? 'Invalid OTP'};
      }
    } catch (e) {
      return {'success': false, 'message': 'An error occurred: $e'};
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<Map<String, dynamic>> resendOtp(String email) async {
    try {
      final response = await _authService.resendOtp(email);
      final data = jsonDecode(response.body);

      if (response.statusCode == 200) {
        return {'success': true, 'message': data['message']};
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to resend OTP',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  Future<Map<String, dynamic>> register(Map<String, dynamic> userData) async {
    _isLoading = true;
    notifyListeners();

    try {
      final response = await _authService.register(userData);
      final data = jsonDecode(response.body);

      if (response.statusCode == 201) {
        if (data['requires_phone_verification'] == true) {
          return {
            'success': true,
            'requires_phone_verification': true,
            'phone': data['phone'],
            'phone_masked': data['phone_masked'],
            'message': data['message'],
            'cooldown_seconds': data['cooldown_seconds'] ?? 60,
          };
        }

        final token = data['token'];
        if (token != null && data['user'] != null) {
          _user = User.fromJson(data['user']);
          await _authService.saveToken(token);
        }
        return {'success': true, 'message': data['message']};
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Registration failed',
          'errors': data['errors'],
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'An error occurred: $e'};
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<Map<String, dynamic>> verifyRegistrationOtp(String phone, String code) async {
    _isLoading = true;
    notifyListeners();

    try {
      final response = await _authService.verifyRegistrationOtp(phone, code);
      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        final token = data['token'];
        if (token != null && data['user'] != null) {
          _user = User.fromJson(data['user']);
          await _authService.saveToken(token);
        }
        return {'success': true, 'message': data['message']};
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Invalid or expired OTP',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'An error occurred: $e'};
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<Map<String, dynamic>> resendRegistrationOtp(String phone) async {
    try {
      final response = await _authService.resendRegistrationOtp(phone);
      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        return {
          'success': true,
          'message': data['message'],
          'cooldown_seconds': data['cooldown_seconds'] ?? 60,
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to resend OTP',
          'cooldown_seconds': data['cooldown_seconds'],
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  Future<Map<String, dynamic>> changeRegistrationPhone(String oldPhone, String newPhone) async {
    _isLoading = true;
    notifyListeners();

    try {
      final response = await _authService.changeRegistrationPhone(oldPhone, newPhone);
      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        return {
          'success': true,
          'message': data['message'],
          'phone': data['phone'],
          'phone_masked': data['phone_masked'],
          'cooldown_seconds': data['cooldown_seconds'] ?? 60,
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to change phone number',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> checkDeletionRequestStatus() async {
    try {
      _deletionRequestStatus = await ProfileService().getDeleteRequestStatus();
      notifyListeners();
    } catch (e) {
      debugPrint('Error checking deletion status: $e');
    }
  }

  Future<void> logout() async {
    try {
      await _authService.logout();
    } catch (e) {
      // Ignore network errors on logout
    }
    try {
      await PushNotificationService().deleteToken();
    } catch (_) {}
    await _authService.clearToken();
    _user = null;
    notifyListeners();
  }
}
