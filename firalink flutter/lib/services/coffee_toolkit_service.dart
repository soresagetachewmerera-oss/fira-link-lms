import 'dart:convert';
import 'package:http/http.dart' as http;
import 'base_service.dart';

class CoffeeToolkitService extends BaseService {
  Future<http.Response> getHubSummary() async {
    final headers = await getHeaders();
    final url = Uri.parse('${BaseService.baseUrl}/coffee-toolkit/hub-summary');
    return await http.get(url, headers: headers);
  }

  Future<http.Response> calculateProfit(Map<String, dynamic> params) async {
    final headers = await getHeaders();
    final url = Uri.parse('${BaseService.baseUrl}/coffee-toolkit/calculate');
    return await http.post(url, headers: headers, body: jsonEncode(params));
  }

  Future<http.Response> saveCalculation(Map<String, dynamic> params) async {
    final headers = await getHeaders();
    final url = Uri.parse('${BaseService.baseUrl}/coffee-toolkit/calculations');
    return await http.post(url, headers: headers, body: jsonEncode(params));
  }

  Future<http.Response> getCalculations() async {
    final headers = await getHeaders();
    final url = Uri.parse('${BaseService.baseUrl}/coffee-toolkit/calculations');
    return await http.get(url, headers: headers);
  }

  Future<http.Response> createOffer(Map<String, dynamic> params) async {
    final headers = await getHeaders();
    final url = Uri.parse('${BaseService.baseUrl}/coffee-toolkit/offers');
    return await http.post(url, headers: headers, body: jsonEncode(params));
  }

  Future<http.Response> getOffers() async {
    final headers = await getHeaders();
    final url = Uri.parse('${BaseService.baseUrl}/coffee-toolkit/offers');
    return await http.get(url, headers: headers);
  }

  Future<http.Response> submitAssessment(Map<String, dynamic> answers) async {
    final headers = await getHeaders();
    final url = Uri.parse('${BaseService.baseUrl}/coffee-toolkit/assess');
    return await http.post(url, headers: headers, body: jsonEncode(answers));
  }

  Future<http.Response> getMarketIntelligence({
    String? search,
    String? origin,
    String? grade,
    String? process,
    String? exporterType,
    int? limit,
  }) async {
    final headers = await getHeaders();
    final queryParams = <String, String>{};
    if (search != null && search.isNotEmpty) queryParams['search'] = search;
    if (origin != null && origin.isNotEmpty) queryParams['origin'] = origin;
    if (grade != null && grade.isNotEmpty) queryParams['grade'] = grade;
    if (process != null && process.isNotEmpty) queryParams['process'] = process;
    if (exporterType != null && exporterType.isNotEmpty) {
      queryParams['exporter_type'] = exporterType;
    }
    if (limit != null) queryParams['limit'] = limit.toString();

    final uri = Uri.parse('${BaseService.baseUrl}/coffee-toolkit/market-intelligence')
        .replace(queryParameters: queryParams.isNotEmpty ? queryParams : null);
    return await http.get(uri, headers: headers);
  }
}
