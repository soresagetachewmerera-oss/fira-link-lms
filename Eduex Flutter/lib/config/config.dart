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

  /// Base URL of the backend (auto-detected on Web/LAN, 10.0.2.2 on Android emulator)
  static String get baseUrl {
    if (kIsWeb) {
      final host = Uri.base.host;
      if (host.contains('fira-link.com')) {
        return 'https://lms.fira-link.com';
      }
      if (host.isNotEmpty) {
        final scheme = Uri.base.scheme.isNotEmpty ? Uri.base.scheme : 'http';
        return '$scheme://$host:8000';
      }
      return 'http://127.0.0.1:8000';
    }
    try {
      if (Platform.isAndroid) return 'http://10.0.2.2:8000';
    } catch (_) {}
    return 'http://192.168.0.111:8000';
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
    if (path == null || path.trim().isEmpty) return '';
    final trimmed = path.trim();
    // Normalize any port 8000 or loopback URL (localhost, 127.0.0.1, or LAN IP) to current baseUrl
    final uri = Uri.tryParse(trimmed);
    if (uri != null && (uri.host == '127.0.0.1' || uri.host == 'localhost' || (uri.hasPort && uri.port == 8000))) {
      return '$baseUrl${uri.path}';
    }
    if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
      return trimmed;
    }
    final clean = trimmed.startsWith('/') ? trimmed.substring(1) : trimmed;
    if (clean.startsWith('storage/')) {
      return '$baseUrl/$clean';
    }
    return '$baseUrl/storage/$clean';
  }

  /// Helper to get video URL
  static String getVideoUrl(String? path) {
    if (path == null || path.trim().isEmpty) return '';
    final trimmed = path.trim();
    final uri = Uri.tryParse(trimmed);
    if (uri != null && uri.hasPort && uri.port == 8000) {
      return '$baseUrl${uri.path}${uri.hasQuery ? '?${uri.query}' : ''}';
    }
    if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
      return trimmed;
    }
    final clean = trimmed.startsWith('/') ? trimmed.substring(1) : trimmed;
    return '$baseUrl/api/video/stream?path=${Uri.encodeComponent(clean)}';
  }
}

