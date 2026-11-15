import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;

import '../models/trade_chat_model.dart';
import 'api_exception.dart';
import 'base_service.dart';

/// Business chat (slice 7). Throws [ApiException] on failure.
class TradeChatService extends BaseService {
  static const Duration _timeout = Duration(milliseconds: 15000);

  String get _base => '${BaseService.baseUrl}/trade-chats';

  Future<dynamic> _data(String method, String url, {Map<String, dynamic>? body}) async {
    final res = requireSuccess(await guardedRequest(() async {
      final headers = await getHeaders();
      final uri = Uri.parse(url);
      return method == 'POST'
          ? http.post(uri, headers: headers, body: body == null ? null : jsonEncode(body)).timeout(_timeout)
          : http.get(uri, headers: headers).timeout(_timeout);
    }));
    final decoded = jsonDecode(res.body);
    return decoded is Map ? decoded['data'] : null;
  }

  Future<List<TradeConversation>> inbox() async {
    final list = await _data('GET', _base) as List? ?? [];
    return list.whereType<Map>().map((c) => TradeConversation.fromJson(Map<String, dynamic>.from(c))).toList();
  }

  /// "Message seller" from a lot or an exporter page: one thread per pair of businesses.
  Future<TradeConversation> withSeller(int sellerOrganizationId, {int? listingId}) async => TradeConversation.fromJson(
      Map<String, dynamic>.from(await _data('POST', _base, body: {'seller_organization_id': sellerOrganizationId, 'listing_id': ?listingId}) as Map));

  /// The deal room's thread (created on first open).
  Future<TradeConversation> forDealRoom(int dealRoomId) async =>
      TradeConversation.fromJson(Map<String, dynamic>.from(await _data('POST', '${BaseService.baseUrl}/deal-rooms/$dealRoomId/chat') as Map));

  Future<TradeThread> thread(int id) async {
    final d = Map<String, dynamic>.from(await _data('GET', '$_base/$id') as Map);
    return TradeThread(
      conversation: TradeConversation.fromJson(Map<String, dynamic>.from(d['conversation'] as Map)),
      messages: (d['messages'] as List? ?? []).whereType<Map>().map((m) => TradeMessage.fromJson(Map<String, dynamic>.from(m))).toList(),
    );
  }

  Future<TradeMessage> send(int id, String body) async =>
      TradeMessage.fromJson(Map<String, dynamic>.from(await _data('POST', '$_base/$id/messages', body: {'body': body}) as Map));
}
