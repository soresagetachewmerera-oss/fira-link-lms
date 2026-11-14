import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;

import '../models/trade_model.dart';
import 'api_exception.dart';
import 'base_service.dart';

/// Requests (RFQs), quotes and exporters (slice 5). Every call throws [ApiException] on failure;
/// nothing is faked. Business rules (who may post, see, quote or accept) are on the server.
class TradeService extends BaseService {
  static const Duration _timeout = Duration(milliseconds: 12000);

  String get _base => '${BaseService.baseUrl}/marketplace';

  dynamic _data(http.Response res) {
    final decoded = jsonDecode(requireSuccess(res).body);
    return decoded is Map ? decoded['data'] : null;
  }

  List<Map<String, dynamic>> _page(dynamic data) {
    final list = data is Map ? data['data'] : data;
    return (list is List ? list : const []).whereType<Map<String, dynamic>>().toList();
  }

  Future<http.Response> _send(String method, String path, {Map<String, dynamic>? body, Map<String, dynamic>? query}) {
    return guardedRequest(() async {
      final headers = await getHeaders();
      final uri = Uri.parse('$_base$path').replace(queryParameters: query);
      if (method == 'POST') {
        return http.post(uri, headers: headers, body: body == null ? null : jsonEncode(body)).timeout(_timeout);
      }
      return http.get(uri, headers: headers).timeout(_timeout);
    });
  }

  /// Open requests (verified seller businesses only; others get VERIFIED_SELLER_REQUIRED).
  Future<List<Rfq>> openRequests({String? origin}) async =>
      _page(_data(await _send('GET', '/rfqs', query: {if (origin != null && origin.isNotEmpty) 'origin': origin}))).map(Rfq.fromJson).toList();

  Future<List<Rfq>> myRequests() async => _page(_data(await _send('GET', '/my-rfqs'))).map(Rfq.fromJson).toList();

  /// Returns the request, whether it is the user's own, whether they can quote, and their quote.
  Future<({Rfq rfq, bool isOwn, bool canQuote, TradeQuote? myQuote})> request(int id) async {
    final d = _data(await _send('GET', '/rfqs/$id')) as Map<String, dynamic>;
    return (
      rfq: Rfq.fromJson(d['rfq'] as Map<String, dynamic>),
      isOwn: d['is_own'] == true,
      canQuote: d['can_quote'] == true,
      myQuote: d['my_quote'] is Map<String, dynamic> ? TradeQuote.fromJson(d['my_quote'] as Map<String, dynamic>) : null,
    );
  }

  Future<Map<String, dynamic>?> prefillFromLot(int listingId) async {
    final d = _data(await _send('GET', '/rfqs/prefill', query: {'listing': '$listingId'}));
    return d is Map<String, dynamic> ? d : null;
  }

  Future<Rfq> postRequest(Map<String, dynamic> fields) async => Rfq.fromJson(_data(await _send('POST', '/rfqs', body: fields)) as Map<String, dynamic>);

  Future<Rfq> closeRequest(int id) async => Rfq.fromJson(_data(await _send('POST', '/rfqs/$id/close')) as Map<String, dynamic>);

  Future<({Rfq rfq, bool canDecide, List<TradeQuote> quotes})> quotes(int rfqId, {bool verifiedOnly = true, String? minLevel, String sort = 'price'}) async {
    final d = _data(await _send('GET', '/rfqs/$rfqId/quotes', query: {
      'verified_only': verifiedOnly ? '1' : '0',
      'min_level': ?minLevel,
      'sort': sort,
    })) as Map<String, dynamic>;
    return (
      rfq: Rfq.fromJson(d['rfq'] as Map<String, dynamic>),
      canDecide: d['can_decide'] == true,
      quotes: (d['quotes'] as List? ?? []).whereType<Map<String, dynamic>>().map(TradeQuote.fromJson).toList(),
    );
  }

  Future<TradeQuote> sendQuote(int rfqId, Map<String, dynamic> fields) async =>
      TradeQuote.fromJson(_data(await _send('POST', '/rfqs/$rfqId/quote', body: fields)) as Map<String, dynamic>);

  Future<List<TradeQuote>> myQuotes() async => _page(_data(await _send('GET', '/my-quotes'))).map(TradeQuote.fromJson).toList();

  Future<TradeQuote> accept(int quoteId) async => TradeQuote.fromJson(_data(await _send('POST', '/quotes/$quoteId/accept')) as Map<String, dynamic>);

  Future<TradeQuote> decline(int quoteId) async => TradeQuote.fromJson(_data(await _send('POST', '/quotes/$quoteId/decline')) as Map<String, dynamic>);

  Future<List<ExporterCard>> exporters({String? search, String? region}) async => _page(_data(await _send('GET', '/exporters', query: {
        if (search != null && search.isNotEmpty) 'search': search,
        if (region != null && region.isNotEmpty) 'region': region,
      })))
          .map(ExporterCard.fromJson)
          .toList();

  Future<ExporterProfile> exporter(int id) async => ExporterProfile.fromJson(_data(await _send('GET', '/exporters/$id')) as Map<String, dynamic>);
}
