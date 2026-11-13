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

  Future<String?> getToken() async {
    try {
      // 1. Read from encrypted secure hardware storage (Android Keystore / iOS Keychain)
      final secureToken = await _secureStorage.read(key: _tokenKey);
      if (secureToken != null && secureToken.isNotEmpty) {
        return secureToken;
      }

      // 2. Backward compatibility: check legacy SharedPreferences and migrate securely
      final prefs = await SharedPreferences.getInstance();
      final legacyToken = prefs.getString(_tokenKey);
      if (legacyToken != null && legacyToken.isNotEmpty) {
        await _secureStorage.write(key: _tokenKey, value: legacyToken);
        await prefs.remove(_tokenKey); // Purge unencrypted plaintext copy
        return legacyToken;
      }
    } catch (_) {
      // Fallback if secure hardware storage is unavailable on non-standard device
      final prefs = await SharedPreferences.getInstance();
      return prefs.getString(_tokenKey);
    }
    return null;
  }

  Future<void> saveToken(String token) async {
    try {
      await _secureStorage.write(key: _tokenKey, value: token);
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove(_tokenKey); // Purge plaintext legacy storage
    } catch (_) {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_tokenKey, token);
    }
  }

  Future<void> clearToken() async {
    try {
      await _secureStorage.delete(key: _tokenKey);
    } catch (_) {}
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
