import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;

import '../models/sample_model.dart';
import 'api_exception.dart';
import 'base_service.dart';
import 'upload_file.dart';

/// Samples (slice 6). Every call throws [ApiException] on failure; nothing is faked here.
/// (Payment is mocked on the server behind a flag until Chapa is live.)
class SampleService extends BaseService {
  static const Duration _timeout = Duration(milliseconds: 15000);
  static const Duration _uploadTimeout = Duration(seconds: 90);

  String get _base => '${BaseService.baseUrl}/marketplace/samples';

  Map<String, dynamic> _data(http.Response res) {
    final decoded = jsonDecode(requireSuccess(res).body);
    final data = decoded is Map ? decoded['data'] : null;
    return data is Map<String, dynamic> ? data : <String, dynamic>{};
  }

  Future<http.Response> _send(String method, String url, {Map<String, dynamic>? body, Map<String, dynamic>? query}) {
    return guardedRequest(() async {
      final headers = await getHeaders();
      final uri = Uri.parse(url).replace(queryParameters: query);
      return method == 'POST'
          ? http.post(uri, headers: headers, body: body == null ? null : jsonEncode(body)).timeout(_timeout)
          : http.get(uri, headers: headers).timeout(_timeout);
    });
  }

  /// side: buyer (samples my business requested) or seller (requests for my lots); tab for the seller.
  Future<SampleList> list({String side = 'buyer', String? tab}) async =>
      SampleList.fromJson(_data(await _send('GET', _base, query: {'side': side, 'tab': ?tab})));

  /// [withShipping]: the seller's shipping screen asks for the buyer's delivery contact (audited).
  Future<SampleView> get(int id, {bool withShipping = false}) async =>
      SampleView.fromJson(_data(await _send('GET', '$_base/$id', query: withShipping ? {'shipping': '1'} : null)));

  Future<SampleView> request(Map<String, dynamic> fields) async => SampleView.fromJson(_data(await _send('POST', _base, body: fields)));

  Future<SampleView> pay(int id) async => SampleView.fromJson(_data(await _send('POST', '$_base/$id/pay')));

  Future<SampleView> cancel(int id) async => SampleView.fromJson(_data(await _send('POST', '$_base/$id/cancel')));

  Future<SampleView> confirmDelivery(int id, {required bool sealIntact}) async =>
      SampleView.fromJson(_data(await _send('POST', '$_base/$id/delivered', body: {'seal_intact': sealIntact})));

  Future<SampleView> evaluate(int id, {required bool approve, required double score, String? notes}) async => SampleView.fromJson(_data(
      await _send('POST', '$_base/$id/evaluate', body: {'decision': approve ? 'approved' : 'rejected', 'cupping_score': score, 'cupping_notes': ?notes})));

  /// Seal and hand to the courier: seal ID, photos of the sealed sample and the retained copy, tracking.
  Future<SampleView> dispatch(int id, {required String sealId, required UploadFile sealedPhoto, required UploadFile retainedPhoto, required String trackingNumber}) async {
    final res = await guardedRequest(() async {
      final token = await getToken();
      final request = http.MultipartRequest('POST', Uri.parse('$_base/$id/dispatch'));
      request.headers.addAll({
        'Accept': 'application/json',
        if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token',
      });
      request.fields['seal_id'] = sealId;
      request.fields['tracking_number'] = trackingNumber;
      request.files.add(sealedPhoto.toMultipart('sealed_photo'));
      request.files.add(retainedPhoto.toMultipart('retained_photo'));
      return http.Response.fromStream(await request.send().timeout(_uploadTimeout));
    });
    return SampleView.fromJson(_data(res));
  }

  /// Headers for loading a seal photo (signed URL, still needs the signed-in user).
  Future<Map<String, String>> imageHeaders() async {
    final token = await getToken();
    return {if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token'};
  }
}
