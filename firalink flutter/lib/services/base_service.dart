import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../config/config.dart';

class BaseService {
  // Use AppConfig for base URL
  static String get baseUrl => AppConfig.apiBaseUrl;

  static const String _tokenKey = 'auth_token';

  static const FlutterSecureStorage _secureStorage = FlutterSecureStorage(
    aOptions: AndroidOptions(),
    iOptions: IOSOptions(accessibility: KeychainAccessibility.first_unlock),
  );

  /// The stored token, from secure storage only (Android Keystore / iOS Keychain). Whether it may be
  /// sent is decided by the HTTP client (`FiraHttpClient`), not here.
  ///
  /// An old plaintext copy in SharedPreferences (older app versions) is moved into secure storage
  /// once and deleted. There is no plaintext fallback: if secure storage fails, there is no session.
  Future<String?> getToken() async {
    try {
      final secureToken = await _secureStorage.read(key: _tokenKey);
      if (secureToken != null && secureToken.isNotEmpty) {
        return secureToken;
      }
    } catch (_) {
      await _removeLegacyCopy();
      return null;
    }

    final prefs = await SharedPreferences.getInstance();
    final legacyToken = prefs.getString(_tokenKey);
    if (legacyToken == null || legacyToken.isEmpty) return null;
    try {
      await _secureStorage.write(key: _tokenKey, value: legacyToken);
      await prefs.remove(_tokenKey);
      return legacyToken;
    } catch (_) {
      await prefs.remove(_tokenKey);
      return null;
    }
  }

  /// Throws when secure storage is not available: the sign-in then fails visibly instead of keeping
  /// the token in plain text.
  Future<void> saveToken(String token) async {
    await _secureStorage.write(key: _tokenKey, value: token);
    await _removeLegacyCopy();
  }

  Future<void> clearToken() async {
    try {
      await _secureStorage.delete(key: _tokenKey);
    } catch (_) {}
    await _removeLegacyCopy();
  }

  Future<void> _removeLegacyCopy() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
  }

  Future<Map<String, String>> getHeaders() async {
    final token = await getToken();
    final prefs = await SharedPreferences.getInstance();
    final lang = prefs.getString('language_code') ?? 'en';
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Accept-Language': lang,
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }
}
