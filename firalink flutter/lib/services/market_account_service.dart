import 'dart:convert';
import 'dart:typed_data';

import 'package:http/http.dart' as http;

import 'base_service.dart';
import 'market_data_service.dart' show MarketUnavailableException;

/// A price alert as the server returns it.
class MarketAlert {
  const MarketAlert({required this.id, required this.key, required this.direction, required this.threshold, required this.unitLabel, required this.channels});

  final int id;
  final String key;
  final String direction; // above | below
  final double threshold;
  final String unitLabel;
  final List<String> channels;

  factory MarketAlert.fromJson(Map<String, dynamic> j) => MarketAlert(
    id: (j['id'] as num).toInt(),
    key: j['key'].toString(),
    direction: j['direction'].toString(),
    threshold: (j['threshold'] as num).toDouble(),
    unitLabel: j['unit_label']?.toString() ?? '',
    channels: ((j['channels'] as List?) ?? const []).map((e) => e.toString()).toList(),
  );
}

/// Signed-in market features (watchlist, price alerts, CSV). Every call needs the sign-in token;
/// the server refuses it otherwise. Errors are thrown, never turned into success.
class MarketAccountService extends BaseService {
  MarketAccountService({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;
  static const _timeout = Duration(seconds: 15);

  Uri _uri(String path, [Map<String, String>? query]) => Uri.parse('${BaseService.baseUrl}/market/$path').replace(queryParameters: query);

  Future<Map<String, dynamic>> _json(http.Response r, {int ok = 200}) {
    if (r.statusCode != ok) throw MarketUnavailableException('HTTP ${r.statusCode}');
    return Future.value(jsonDecode(utf8.decode(r.bodyBytes)) as Map<String, dynamic>);
  }

  Future<Set<String>> watchlist() async {
    final body = await _json(await _client.get(_uri('watchlist'), headers: await getHeaders()).timeout(_timeout));
    return {for (final k in (body['data']['keys'] as List? ?? const [])) k.toString()};
  }

  Future<Set<String>> setWatched(String key, bool watched) async {
    final uri = _uri('watchlist/${Uri.encodeComponent(key)}');
    final headers = await getHeaders();
    final r = watched ? await _client.put(uri, headers: headers).timeout(_timeout) : await _client.delete(uri, headers: headers).timeout(_timeout);
    final body = await _json(r);
    return {for (final k in (body['data']['keys'] as List? ?? const [])) k.toString()};
  }

  Future<List<MarketAlert>> alerts() async {
    final body = await _json(await _client.get(_uri('alerts'), headers: await getHeaders()).timeout(_timeout));
    return [for (final a in (body['data']['alerts'] as List? ?? const [])) MarketAlert.fromJson((a as Map).cast<String, dynamic>())];
  }

  Future<MarketAlert> createAlert({required String key, required String direction, required double threshold, required List<String> channels}) async {
    final r = await _client
        .post(_uri('alerts'), headers: await getHeaders(), body: jsonEncode({'key': key, 'direction': direction, 'threshold': threshold, 'channels': channels}))
        .timeout(_timeout);
    final body = await _json(r, ok: 201);
    return MarketAlert.fromJson((body['data'] as Map).cast<String, dynamic>());
  }

  Future<void> deleteAlert(int id) async {
    final r = await _client.delete(_uri('alerts/$id'), headers: await getHeaders()).timeout(_timeout);
    if (r.statusCode != 204) throw MarketUnavailableException('HTTP ${r.statusCode}');
  }

  Future<Uint8List> exportCsv(String key, {String range = '1Y'}) async {
    final headers = await getHeaders()
      ..['Accept'] = 'text/csv';
    final r = await _client.get(_uri('instruments/${Uri.encodeComponent(key)}/export.csv', {'range': range}), headers: headers).timeout(_timeout);
    if (r.statusCode != 200) throw MarketUnavailableException('HTTP ${r.statusCode}');
    return r.bodyBytes;
  }
}
