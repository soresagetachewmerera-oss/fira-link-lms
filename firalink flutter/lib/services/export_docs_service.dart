import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'base_service.dart';
import 'session_reset.dart';
import '../config/config.dart';

class ExportDocsService extends BaseService {
  /// Get all 30 export templates with optional category filter
  Future<http.Response> getTemplates({String? category, String? search}) async {
    final headers = await getHeaders();
    final queryParams = <String, String>{};
    if (category != null && category.isNotEmpty && category != 'all') {
      queryParams['category'] = category;
    }
    if (search != null && search.isNotEmpty) {
      queryParams['search'] = search;
    }

    final uri = Uri.parse('${BaseService.baseUrl}/export-docs/templates')
        .replace(queryParameters: queryParams.isNotEmpty ? queryParams : null);

    return await http.get(uri, headers: headers);
  }

  /// Get single template schema and default sample data
  Future<http.Response> getTemplate(String code) async {
    final headers = await getHeaders();
    final uri = Uri.parse('${BaseService.baseUrl}/export-docs/templates/$code');
    return await http.get(uri, headers: headers);
  }

  /// Get canonical export shipments
  Future<http.Response> getShipments() async {
    final headers = await getHeaders();
    final uri = Uri.parse('${BaseService.baseUrl}/export-docs/shipments');
    return await http.get(uri, headers: headers);
  }

  /// Create canonical master shipment ("Enter Shipment Once")
  Future<http.Response> createShipment(Map<String, dynamic> params) async {
    final headers = await getHeaders();
    final uri = Uri.parse('${BaseService.baseUrl}/export-docs/shipments');
    return await http.post(uri, headers: headers, body: jsonEncode(params));
  }

  /// 1-Click Generate complete export document bundle from shipment
  Future<http.Response> generateAllDocuments(int shipmentId, {List<String>? codes}) async {
    final headers = await getHeaders();
    final uri = Uri.parse('${BaseService.baseUrl}/export-docs/shipments/$shipmentId/generate-all');
    return await http.post(
      uri,
      headers: headers,
      body: jsonEncode({
        if (codes != null && codes.isNotEmpty) 'template_codes': codes,
      }),
    );
  }

  /// Get generated documents for organization
  Future<http.Response> getDocuments() async {
    final headers = await getHeaders();
    final uri = Uri.parse('${BaseService.baseUrl}/export-docs/documents');
    return await http.get(uri, headers: headers);
  }

  /// Get single document details with payload and summary
  Future<http.Response> getDocument(int id) async {
    final headers = await getHeaders();
    final uri = Uri.parse('${BaseService.baseUrl}/export-docs/documents/$id');
    return await http.get(uri, headers: headers);
  }

  /// Update document payload and title
  Future<http.Response> updateDocument(int id, {
    required Map<String, dynamic> payload,
    String? title,
    String? changeSummary,
  }) async {
    final headers = await getHeaders();
    final uri = Uri.parse('${BaseService.baseUrl}/export-docs/documents/$id');
    return await http.put(
      uri,
      headers: headers,
      body: jsonEncode({
        if (title != null) 'title': title,
        'data_payload': payload,
        if (changeSummary != null) 'change_summary': changeSummary,
      }),
    );
  }

  /// Finalize document (locks and produces verified PDF/Excel)
  Future<http.Response> finalizeDocument(int id) async {
    final headers = await getHeaders();
    final uri = Uri.parse('${BaseService.baseUrl}/export-docs/documents/$id/finalize');
    return await http.post(uri, headers: headers);
  }

  /// Create a draft document from template
  Future<http.Response> createDocument(String templateCode, {Map<String, dynamic>? payload, int? shipmentId}) async {
    final headers = await getHeaders();
    final uri = Uri.parse('${BaseService.baseUrl}/export-docs/documents');
    return await http.post(
      uri,
      headers: headers,
      body: jsonEncode({
        'template_code': templateCode,
        if (payload != null) 'data_payload': payload,
        if (shipmentId != null) 'export_shipment_id': shipmentId,
      }),
    );
  }

  /// Stateless deterministic trade calculation
  Future<http.Response> calculate(Map<String, dynamic> params) async {
    final headers = await getHeaders();
    final uri = Uri.parse('${BaseService.baseUrl}/export-docs/calculate');
    return await http.post(uri, headers: headers, body: jsonEncode(params));
  }

  /// Get download URL for PDF or Excel
  String getDownloadUrl(int documentId, String format) {
    return '${BaseService.baseUrl}/export-docs/documents/$documentId/download/$format';
  }

  /// Download file bytes and save to local cache directory for instant opening
  Future<String?> downloadFile(int documentId, String format, String filename) async {
    try {
      final headers = await getHeaders();
      final url = getDownloadUrl(documentId, format);
      final response = await http.get(Uri.parse(url), headers: headers);
      if (response.statusCode == 200) {
        final dir = await SessionReset.exportDocsDirectory(); // deleted on sign-out
        final file = File('${dir.path}/$filename');
        await file.writeAsBytes(response.bodyBytes);
        return file.path;
      }
    } catch (_) {}
    return null;
  }

  /// Get full web studio URL
  String getWebStudioUrl([int? documentId]) {
    if (documentId != null) {
      return '${AppConfig.baseUrl}/export-docs/studio/$documentId';
    }
    return '${AppConfig.baseUrl}/export-docs';
  }

  /// Contextual Fira AI Assistant with trade guardrails
  Future<http.Response> aiAssist({
    required String action,
    required String templateCode,
    required Map<String, dynamic> payload,
    String? userPrompt,
  }) async {
    final headers = await getHeaders();
    final uri = Uri.parse('${BaseService.baseUrl}/export-docs/ai/assist');
    return await http.post(
      uri,
      headers: headers,
      body: jsonEncode({
        'action': action,
        'template_code': templateCode,
        'data_payload': payload,
        if (userPrompt != null) 'user_prompt': userPrompt,
      }),
    );
  }
}
