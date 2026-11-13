import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import '../models/sample_order_model.dart';
import 'base_service.dart';

class SampleService extends BaseService {
  static const Duration _timeout = Duration(milliseconds: 15000);

  /// Fetch samples for current user / organization
  Future<List<SampleOrderModel>> getSamples() async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/marketplace/samples');
      final res = await http.get(url, headers: headers).timeout(_timeout);

      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        final list = (decoded['data'] as List? ?? [])
            .map((item) => SampleOrderModel.fromJson(item as Map<String, dynamic>))
            .toList();
        return list;
      }
    } catch (_) {}
    return [];
  }

  /// Get sample details
  Future<SampleOrderModel?> getSample(int id) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/marketplace/samples/$id');
      final res = await http.get(url, headers: headers).timeout(_timeout);

      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        if (decoded['data'] != null) {
          return SampleOrderModel.fromJson(decoded['data']);
        }
      }
    } catch (_) {}
    return null;
  }

  /// Request a green coffee sample
  Future<Map<String, dynamic>> requestSample({
    required int listingId,
    required String shippingAddress,
    int quantityGrams = 250,
  }) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/marketplace/samples');
      final res = await http
          .post(
            url,
            headers: headers,
            body: jsonEncode({
              'listing_id': listingId,
              'shipping_address': shippingAddress,
              'quantity_grams': quantityGrams,
            }),
          )
          .timeout(_timeout);

      final decoded = jsonDecode(res.body);
      if (res.statusCode == 201 && decoded['success'] == true) {
        return {
          'success': true,
          'message': decoded['message'],
          'sample': SampleOrderModel.fromJson(decoded['data']),
        };
      }
      return {
        'success': false,
        'message': decoded['message'] ?? 'Failed to request sample',
      };
    } catch (e) {
      return {'success': false, 'message': e.toString()};
    }
  }

  /// Exporter dispatches sample with waybill & tamper-evident retained seal
  Future<Map<String, dynamic>> dispatchSample({
    required int sampleId,
    required String courierName,
    required String waybillNumber,
    required String sealNo,
    String? dispatchDate,
  }) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/marketplace/samples/$sampleId/dispatch');
      final res = await http
          .post(
            url,
            headers: headers,
            body: jsonEncode({
              'courier_name': courierName,
              'waybill_number': waybillNumber,
              'retained_sample_seal_no': sealNo,
              'dispatch_date': dispatchDate,
            }),
          )
          .timeout(_timeout);

      final decoded = jsonDecode(res.body);
      return {
        'success': res.statusCode == 200 && decoded['success'] == true,
        'message': decoded['message'] ?? 'Sample dispatched successfully',
      };
    } catch (e) {
      return {'success': false, 'message': e.toString()};
    }
  }

  /// Mark sample delivered
  Future<bool> markDelivered(int sampleId) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/marketplace/samples/$sampleId/delivered');
      final res = await http.post(url, headers: headers).timeout(_timeout);
      return res.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  /// Buyer cups and evaluates sample (approved/rejected)
  Future<Map<String, dynamic>> evaluateSample({
    required int sampleId,
    required String decision, // approved, rejected
    required double cuppingScore,
    String? notes,
    File? labReport,
  }) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/marketplace/samples/$sampleId/evaluate');

      if (labReport != null) {
        final request = http.MultipartRequest('POST', url);
        request.headers.addAll(headers);
        request.fields['decision'] = decision;
        request.fields['cupping_score'] = cuppingScore.toString();
        if (notes != null) request.fields['cupping_notes'] = notes;
        request.files.add(await http.MultipartFile.fromPath('lab_report', labReport.path));
        final streamed = await request.send();
        final res = await http.Response.fromStream(streamed);
        final decoded = jsonDecode(res.body);
        return {
          'success': res.statusCode == 200 && decoded['success'] == true,
          'message': decoded['message'] ?? 'Evaluation submitted',
        };
      } else {
        final res = await http
            .post(
              url,
              headers: headers,
              body: jsonEncode({
                'decision': decision,
                'cupping_score': cuppingScore,
                'cupping_notes': notes,
              }),
            )
            .timeout(_timeout);
        final decoded = jsonDecode(res.body);
        return {
          'success': res.statusCode == 200 && decoded['success'] == true,
          'message': decoded['message'] ?? 'Evaluation submitted',
        };
      }
    } catch (e) {
      return {'success': false, 'message': e.toString()};
    }
  }
}

