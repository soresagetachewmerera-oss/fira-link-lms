import 'dart:io';

import 'package:flutter/painting.dart';
import 'package:path_provider/path_provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Removes everything on the device that belongs to the person who was signed in (auth gating
/// 2026-11-20, M-4). Called on sign-out, when the server rejects the token, and before a new
/// sign-in. In-memory provider state is reset by `SessionWatcher`; this handles device storage.
///
/// Kept: language, dark mode, onboarding seen, market data cache (public).
class SessionReset {
  SessionReset._();

  /// SharedPreferences keys that hold personal data.
  static const userPrefKeys = <String>[
    'auth_token', // legacy plaintext copy from older versions
    'wishlist_items',
    'recent_searches',
  ];

  /// Sub-folders the app writes personal files into (receipts, generated export documents).
  static const receiptsFolder = 'receipts';
  static const exportDocsFolder = 'fira_docs';

  static Future<Directory> receiptsDirectory() async {
    final dir = Directory('${(await getApplicationDocumentsDirectory()).path}/$receiptsFolder');
    if (!await dir.exists()) await dir.create(recursive: true);
    return dir;
  }

  static Future<Directory> exportDocsDirectory() async {
    final dir = Directory('${(await getTemporaryDirectory()).path}/$exportDocsFolder');
    if (!await dir.exists()) await dir.create(recursive: true);
    return dir;
  }

  static Future<void> wipeDevice() async {
    final prefs = await SharedPreferences.getInstance();
    for (final key in userPrefKeys) {
      await prefs.remove(key);
    }

    for (final dir in <Future<Directory> Function()>[receiptsDirectory, exportDocsDirectory]) {
      try {
        final d = await dir();
        if (await d.exists()) await d.delete(recursive: true);
      } catch (_) {
        // No file system access (tests, web): nothing stored there.
      }
    }

    // Profile photos, lot photos and document previews already decoded in memory.
    final cache = PaintingBinding.instance.imageCache;
    cache.clear();
    cache.clearLiveImages();
  }
}
