import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import '../models/market_overview.dart';
import 'base_service.dart';

/// Thrown when there is no current market data and nothing cached: the screen shows Unavailable.
class MarketUnavailableException implements Exception {
  const MarketUnavailableException(this.reason);

  final String reason;

  @override
  String toString() => 'MarketUnavailableException: $reason';
}

/// Reads coffee market data from the Laravel API (public, works for guests).
/// On failure it returns the last response it received, marked Stale with its real time,
/// or throws [MarketUnavailableException]. It never invents or estimates a value.
class MarketDataService extends BaseService {
  MarketDataService({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;

  static const ranges = ['1D', '1W', '1M', '3M', '1Y'];
  static const _cacheKey = 'market_overview_cache_v1';
  static const _timeout = Duration(seconds: 15);

  Future<MarketSnapshot> fetchOverview() async {
    final headers = await getHeaders();
    final lang = headers['Accept-Language'] ?? 'en';
    try {
      final response = await _client
          .get(Uri.parse('${BaseService.baseUrl}/market/overview'), headers: headers)
          .timeout(_timeout);
      if (response.statusCode != 200) {
        throw MarketUnavailableException('HTTP ${response.statusCode}');
      }
      final body = jsonDecode(response.body) as Map<String, dynamic>;
      final overview = MarketOverview.fromJson(body);
      final now = DateTime.now();
      await _saveCache(lang, response.body, now);
      return MarketSnapshot(overview: overview, fromCache: false, receivedAt: now);
    } catch (error) {
      final cached = await _readCache(lang);
      if (cached != null) return cached;
      throw MarketUnavailableException(error.toString());
    }
  }

  Future<MarketSeriesData> fetchSeries(String symbol, String range) async {
    if (!ranges.contains(range)) {
      throw ArgumentError.value(range, 'range');
    }
    final uri = Uri.parse('${BaseService.baseUrl}/market/series')
        .replace(queryParameters: {'symbol': symbol, 'range': range});
    final response = await _client.get(uri, headers: await getHeaders()).timeout(_timeout);
    if (response.statusCode != 200) {
      throw MarketUnavailableException('HTTP ${response.statusCode}');
    }
    return MarketSeriesData.fromJson(jsonDecode(response.body) as Map<String, dynamic>);
  }

  Future<void> _saveCache(String lang, String body, DateTime at) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('$_cacheKey.$lang', jsonEncode({'received_at': at.toIso8601String(), 'body': body}));
    } catch (_) {
      // Cache is a convenience; failing to write it must not hide fresh data.
    }
  }

  Future<MarketSnapshot?> _readCache(String lang) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final raw = prefs.getString('$_cacheKey.$lang');
      if (raw == null) return null;
      final stored = jsonDecode(raw) as Map<String, dynamic>;
      final receivedAt = DateTime.tryParse(stored['received_at']?.toString() ?? '');
      if (receivedAt == null) return null;
      final overview = MarketOverview.fromJson(jsonDecode(stored['body'] as String) as Map<String, dynamic>);
      return MarketSnapshot(overview: overview.asCachedStale(), fromCache: true, receivedAt: receivedAt);
    } catch (_) {
      return null;
    }
  }
}
