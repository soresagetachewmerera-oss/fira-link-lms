import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import '../models/market_watch.dart';
import 'base_service.dart';
import 'market_data_service.dart' show MarketUnavailableException;

/// Reads `GET /api/market/watch` (public; no sign-in token is sent). Keeps the last good response per
/// language with its ETag and the time it was received. When the server cannot be reached, returns that
/// cached response marked [MarketWatchSnapshot.fromCache]; with nothing cached it throws
/// [MarketUnavailableException]. It never invents, estimates or fills in a value.
class MarketWatchService extends BaseService {
  MarketWatchService({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;

  static const _cacheKey = 'market_watch_cache_v1';
  static const _timeout = Duration(seconds: 15);

  Future<String> _language() async {
    final prefs = await SharedPreferences.getInstance();
    final lang = prefs.getString('language_code') ?? 'en';
    return lang == 'am' ? 'am' : 'en';
  }

  Future<MarketWatchSnapshot> fetchWatch() async {
    final lang = await _language();
    final cached = await _readCache(lang);
    try {
      final response = await _client.get(
        Uri.parse('${BaseService.baseUrl}/market/watch'),
        headers: {
          'Accept': 'application/json',
          'Accept-Language': lang,
          if (cached?.etag != null) 'If-None-Match': cached!.etag!,
        },
      ).timeout(_timeout);

      final now = DateTime.now();
      if (response.statusCode == 304 && cached != null) {
        // Unchanged on the server: the cached body is current again.
        await _saveCache(lang, cached.body, cached.etag, now);
        return MarketWatchSnapshot(watch: cached.watch, fromCache: false, receivedAt: now);
      }
      if (response.statusCode != 200) {
        throw MarketUnavailableException('HTTP ${response.statusCode}');
      }
      final body = utf8.decode(response.bodyBytes);
      final watch = MarketWatch.fromJson(jsonDecode(body) as Map<String, dynamic>);
      await _saveCache(lang, body, response.headers['etag'], now);
      return MarketWatchSnapshot(watch: watch, fromCache: false, receivedAt: now);
    } catch (error) {
      if (cached != null) {
        return MarketWatchSnapshot(watch: cached.watch, fromCache: true, receivedAt: cached.receivedAt);
      }
      throw MarketUnavailableException(error.toString());
    }
  }

  Future<void> _saveCache(String lang, String body, String? etag, DateTime at) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('$_cacheKey.$lang', jsonEncode({'received_at': at.toIso8601String(), 'etag': etag, 'body': body}));
    } catch (_) {
      // The cache is a convenience; failing to write it must not hide fresh data.
    }
  }

  Future<_CachedWatch?> _readCache(String lang) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final raw = prefs.getString('$_cacheKey.$lang');
      if (raw == null) return null;
      final stored = jsonDecode(raw) as Map<String, dynamic>;
      final receivedAt = DateTime.tryParse(stored['received_at']?.toString() ?? '');
      final body = stored['body'] as String?;
      if (receivedAt == null || body == null) return null;
      final watch = MarketWatch.fromJson(jsonDecode(body) as Map<String, dynamic>);
      return _CachedWatch(watch, body, stored['etag'] as String?, receivedAt);
    } catch (_) {
      return null; // unreadable or an older schema: as if nothing was cached
    }
  }
}

class _CachedWatch {
  const _CachedWatch(this.watch, this.body, this.etag, this.receivedAt);

  final MarketWatch watch;
  final String body;
  final String? etag;
  final DateTime receivedAt;
}
