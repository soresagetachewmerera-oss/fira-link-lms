import 'dart:convert';
import 'package:flutter/material.dart';
import '../models/user_model.dart';
import '../services/auth_service.dart';
import '../services/auth_session.dart';
import '../services/profile_service.dart';
import '../services/push_notification_service.dart';
import '../services/session_reset.dart';

export '../services/auth_session.dart' show AuthStatus;

/// The single auth state of the app (auth gating 2026-11-20). Screens and the route guard read
/// [status]; nothing else decides whether someone is a guest. See [AuthStatus].
class AuthProvider with ChangeNotifier {
  AuthProvider({AuthService? authService}) : _authService = authService ?? AuthService() {
    AuthSession.setStatus(AuthStatus.unknown);
    AuthSession.onTokenRejected = handleTokenRejected;
  }

  final AuthService _authService;

  User? _user;
  bool _isLoading = false;
  Map<String, dynamic>? _deletionRequestStatus;
  AuthStatus _status = AuthStatus.unknown;
  int _sessionEpoch = 0;

  User? get user => _user;
  bool get isLoading => _isLoading;
  Map<String, dynamic>? get deletionRequestStatus => _deletionRequestStatus;
  AuthStatus get status => _status;

  /// A confirmed, signed-in session. Guests, expired and unconfirmed (unknown, offline) sessions are not.
  bool get isAuthenticated => _status == AuthStatus.signedIn && _user != null;

  /// Guest or expired: only public content and sign-in prompts.
  bool get isGuest => _status == AuthStatus.guest || _status == AuthStatus.expired;

  /// Changes every time a session ends or a new one starts. SessionWatcher resets the other
  /// providers (workspace, chats, toolkit, ...) when it changes.
  int get sessionEpoch => _sessionEpoch;

  void _setStatus(AuthStatus status) {
    _status = status;
    AuthSession.setStatus(status);
  }

  /// Starts a session from a sign-in response: wipes what the previous person left on the device,
  /// stores the token, then marks the session signed in.
  Future<void> _beginSession(String token, Map<String, dynamic> userJson) async {
    await SessionReset.wipeDevice();
    await _authService.saveToken(token);
    _user = User.fromJson(userJson);
    _deletionRequestStatus = null;
    _sessionEpoch++;
    _setStatus(AuthStatus.signedIn);
    try {
      PushNotificationService().syncTokenWithServer();
    } catch (_) {}
  }

  /// Ends the session on this device: token, user, personal files and caches. [reason] is
  /// [AuthStatus.guest] for a sign-out and [AuthStatus.expired] when the server rejected the token.
  Future<void> _endSession(AuthStatus reason) async {
    _sessionEpoch++;
    _setStatus(reason); // first, so no request sends the token from here on
    _user = null;
    _deletionRequestStatus = null;
    notifyListeners();
    await _authService.clearToken();
    await SessionReset.wipeDevice();
  }

  /// Stores the token and user from a successful sign-in response (phone sign-in, sign-up).
  Future<void> completeSignIn(Map<String, dynamic> data) async {
    final token = data['token']?.toString() ?? '';
    final userRaw = data['user'];
    if (token.isEmpty || userRaw is! Map<String, dynamic>) {
      throw StateError('Sign-in response without token or user');
    }
    await _beginSession(token, userRaw);
    notifyListeners();
  }

  /// Checks the stored token with the server. No token: guest. 200: signed in. 401: the session
  /// ended (wiped, expired). Anything else (offline, 5xx, 429, 403): the session is kept but not
  /// confirmed ([AuthStatus.offline]); protected screens offer Retry. Never "guest with a token".
  Future<void> loadUser() async {
    final token = await _authService.getToken();
    if (token == null || token.isEmpty) {
      _user = null;
      _setStatus(AuthStatus.guest);
      notifyListeners();
      return;
    }

    if (_status != AuthStatus.signedIn) _setStatus(AuthStatus.unknown);
    try {
      final response = await _authService.getUser();
      if (response.statusCode == 200) {
        final raw = jsonDecode(response.body);
        if (raw is Map<String, dynamic>) {
          _user = User.fromJson(raw);
          _setStatus(AuthStatus.signedIn);
          await checkDeletionRequestStatus();
          try {
            PushNotificationService().syncTokenWithServer();
          } catch (_) {}
        } else {
          _setStatus(AuthStatus.offline);
        }
      } else if (response.statusCode == 401) {
        await _endSession(AuthStatus.expired);
        return;
      } else {
        // 403 (for example phone not verified yet), 429, 5xx: the token is not rejected.
        _setStatus(AuthStatus.offline);
      }
    } catch (e) {
      debugPrint('Error loading user: $e');
      _setStatus(AuthStatus.offline);
    }
    notifyListeners();
  }

  /// The API answered 401 to a request that carried our token (revoked, expired, password changed
  /// on another device). Called by FiraHttpClient.
  void handleTokenRejected() {
    if (_status == AuthStatus.guest || _status == AuthStatus.expired) return;
    _endSession(AuthStatus.expired);
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
        final userJson = userRaw is Map<String, dynamic> ? userRaw : data;
        if (token.isEmpty) {
          return {'success': false, 'message': data['message'] ?? 'Invalid login credentials'};
        }
        await _beginSession(token, userJson);

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
          'code': data['code'],
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
        if (token.isEmpty || userRaw is! Map<String, dynamic>) {
          return {'success': false, 'message': data['message'] ?? 'Invalid OTP'};
        }
        await _beginSession(token, userRaw);
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
        if (token != null && data['user'] is Map<String, dynamic>) {
          await _beginSession(token.toString(), data['user'] as Map<String, dynamic>);
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
        if (token != null && data['user'] is Map<String, dynamic>) {
          await _beginSession(token.toString(), data['user'] as Map<String, dynamic>);
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
    if (!isAuthenticated) return;
    try {
      _deletionRequestStatus = await ProfileService().getDeleteRequestStatus();
      notifyListeners();
    } catch (e) {
      debugPrint('Error checking deletion status: $e');
    }
  }

  /// Signs out: removes the push token and revokes the token on the server (best effort), then
  /// wipes the session on this device whatever the network does.
  Future<void> logout() async {
    try {
      await PushNotificationService().deleteToken();
    } catch (_) {}
    try {
      await _authService.logout();
    } catch (e) {
      // Ignore network errors on logout
    }
    await _endSession(AuthStatus.guest);
  }
}
