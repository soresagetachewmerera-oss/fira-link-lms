import 'package:shared_preferences/shared_preferences.dart';
import '../config/config.dart';

class BaseService {
  // Use AppConfig for base URL
  static String get baseUrl => AppConfig.apiBaseUrl;

  static const String _tokenKey = 'auth_token';

  Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_tokenKey);
  }

  Future<void> saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
  }

  Future<void> clearToken() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
  }

  Future<Map<String, String>> getHeaders() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString(_tokenKey);
    final lang = prefs.getString('language_code') ?? 'en';
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Accept-Language': lang,
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }
}
 