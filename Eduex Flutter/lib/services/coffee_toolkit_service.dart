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

  Future<http.Response> getMarketIntelligence() async {
    final headers = await getHeaders();
    final url = Uri.parse('${BaseService.baseUrl}/coffee-toolkit/market-intelligence');
    return await http.get(url, headers: headers);
  }
}
