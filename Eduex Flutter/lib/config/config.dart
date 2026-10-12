import 'dart:io' show Platform;
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';
import '../screens/home/home_screen.dart';
import '../screens/home/home2_screen.dart';

enum HomePageType { home1, home2 }

class AppConfig {
  const AppConfig._();

  /// Global app title
  static const String appTitle = 'Fira Link Academy';

  /// App version
  static const String appVersion = '2.0.0';

  /// Support email
  static const String supportEmail = 'support@firalink.com';

  /// Support phone number
  static const String supportPhone = '+251 911 000 000';

  /// Base URL of the backend (auto-routes to 10.0.2.2 on Android, 127.0.0.1 on Web/Desktop)
  static String get baseUrl {
    if (kIsWeb) return 'http://127.0.0.1:8000';
    try {
      if (Platform.isAndroid) return 'http://10.0.2.2:8000';
    } catch (_) {}
    return 'http://127.0.0.1:8000';
  }

  /// API Base URL
  static String get apiBaseUrl => '$baseUrl/api';

  /// Default Homepage selection option
  /// Select between [HomePageType.home1] or [HomePageType.home2]

  static const HomePageType defaultHomePage = HomePageType.home1;

  /// Helper to get the configured default homepage widget
  static Widget getHomePage() {
    switch (defaultHomePage) {
      case HomePageType.home2:
        return const Home2Screen();
      case HomePageType.home1:
        return const HomeScreen();
    }
  }

  /// Helper to get full image URL
  static String getImageUrl(String? path) {
    if (path == null || path.isEmpty) return '';
    if (path.startsWith('http')) return path;
    return '$baseUrl/storage/$path';
  }
}
