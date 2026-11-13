import 'dart:async';
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'api_exception.dart';
import 'base_service.dart';

/// Marketplace API client.
///
/// Every method makes the real request. There are no fallbacks: a request that
/// cannot reach the server throws [ApiException.network], and the server's real
/// response (including error statuses) is returned so screens can show the true
/// state. Screens must catch [ApiException] and show an error state with retry.
class MarketplaceService extends BaseService {
  static const Duration _timeout = Duration(milliseconds: 10000);
  static const Duration _uploadTimeout = Duration(seconds: 30);

  Future<http.Response> _get(String path, [Map<String, String>? query]) {
    return guardedRequest(() async {
      final headers = await getHeaders();
      final uri = Uri.parse('${BaseService.baseUrl}$path').replace(queryParameters: query);
      return http.get(uri, headers: headers).timeout(_timeout);
    });
  }

  Future<http.Response> _post(String path, [Map<String, dynamic>? body]) {
    return guardedRequest(() async {
      final headers = await getHeaders();
      final uri = Uri.parse('${BaseService.baseUrl}$path');
      return http
          .post(uri, headers: headers, body: body == null ? null : jsonEncode(body))
          .timeout(_timeout);
    });
  }

  Future<http.Response> _delete(String path) {
    return guardedRequest(() async {
      final headers = await getHeaders();
      final uri = Uri.parse('${BaseService.baseUrl}$path');
      return http.delete(uri, headers: headers).timeout(_timeout);
    });
  }

  /// Helper: Detect if server returned a quota limit reached error (403)
  bool isLimitReached(http.Response res) {
    if (res.statusCode == 403) {
      try {
        final decoded = jsonDecode(res.body);
        if (decoded is Map) {
          final code = decoded['code']?.toString();
          final err = decoded['error']?.toString();
          final msg = decoded['message']?.toString().toLowerCase() ?? '';
          return code == 'limit_reached' || err == 'limit_reached' || msg.contains('limit reached') || msg.contains('quota');
        }
      } catch (_) {}
    }
    return false;
  }

  /// Helper: Parse structured limit reached response
  Map<String, dynamic>? parseLimitError(http.Response res) {
    try {
      final decoded = jsonDecode(res.body);
      if (decoded is Map<String, dynamic>) {
        return {
          'code': decoded['code'] ?? 'limit_reached',
          'feature': decoded['feature'] ?? 'quota',
          'limit': decoded['limit'] ?? 0,
          'used': decoded['used'] ?? 0,
          'upgrade_url': decoded['upgrade_url'] ?? '/marketplace/pricing',
          'message': decoded['message'] ?? 'You have reached your plan limit for this feature.',
        };
      }
    } catch (_) {}
    return null;
  }

  /// Helper: Check if response requires verification
  bool isVerificationRequired(http.Response res) {
    if (res.statusCode == 403) {
      try {
        final decoded = jsonDecode(res.body);
        if (decoded is Map) {
          final code = decoded['code']?.toString();
          final msg = decoded['message']?.toString().toLowerCase() ?? '';
          return code == 'VERIFICATION_REQUIRED' || msg.contains('verified') || msg.contains('verification');
        }
      } catch (_) {}
    }
    return false;
  }

  /// Fetch full organization entitlements, usage, and status snapshot
  Future<http.Response> getOrganizationEntitlements() => _get('/user/organization/entitlements');

  /// Fetch paginated coffee listings with filters
  Future<http.Response> getListings({
    String? search,
    String? origin,
    String? processing,
    String? grade,
    double? minScore,
    String? sort,
    int page = 1,
  }) {
    final queryParams = <String, String>{'page': page.toString()};
    if (search != null && search.isNotEmpty) queryParams['search'] = search;
    if (origin != null && origin != 'all' && origin != 'All') queryParams['origin'] = origin;
    if (processing != null && processing != 'all' && processing != 'All') queryParams['processing'] = processing;
    if (grade != null && grade != 'all' && grade != 'All') queryParams['grade'] = grade;
    if (minScore != null && minScore > 0) queryParams['min_score'] = minScore.toString();
    if (sort != null && sort.isNotEmpty) queryParams['sort'] = sort;

    return _get('/marketplace/listings', queryParams);
  }

  /// Get single listing detail
  Future<http.Response> getListingDetail(dynamic idOrSlug) => _get('/marketplace/listings/$idOrSlug');

  /// Get taxonomy filters (origins, processes, grades)
  Future<http.Response> getFilters() => _get('/marketplace/filters');

  /// Toggle saved listing
  Future<http.Response> toggleSaveListing(int id) => _post('/marketplace/listings/$id/save');

  /// Get user's saved lots
  Future<http.Response> getSavedListings() => _get('/marketplace/saved-listings');

  /// Send trade inquiry or sample request
  Future<http.Response> submitInquiry(int listingId, Map<String, dynamic> data) =>
      _post('/marketplace/listings/$listingId/inquire', data);

  /// Get inquiries
  Future<http.Response> getInquiries({String view = 'all'}) => _get('/marketplace/inquiries', {'view': view});

  /// Get open buyer RFQs
  Future<http.Response> getRfqs({String? origin, String? destinationCountry, int page = 1}) {
    return _get('/marketplace/rfqs', {
      'page': page.toString(),
      if (origin?.isNotEmpty == true) 'origin': origin!,
      if (destinationCountry?.isNotEmpty == true) 'destination_country': destinationCountry!,
    });
  }

  /// Get single RFQ detail
  Future<http.Response> getRfqDetail(int id) => _get('/marketplace/rfqs/$id');

  /// Publish a buyer RFQ
  Future<http.Response> submitRfq(Map<String, dynamic> data) => _post('/marketplace/rfqs', data);

  /// Submit an exporter quotation for an RFQ
  Future<http.Response> submitQuote(int rfqId, Map<String, dynamic> data) =>
      _post('/marketplace/rfqs/$rfqId/quote', data);

  /// Get quotations received for an RFQ
  Future<http.Response> getRfqQuotes(int rfqId) => _get('/marketplace/rfqs/$rfqId/quotes');

  /// Get exporter's submitted quotes
  Future<http.Response> getMyQuotes() => _get('/marketplace/my-quotes');

  /// Get seller profile & quota status
  Future<http.Response> getSellerProfile() => _get('/marketplace/seller/profile');

  /// Update seller profile
  Future<http.Response> updateSellerProfile(Map<String, dynamic> data) => _post('/marketplace/seller/profile', data);

  /// Get seller's active lots
  Future<http.Response> getSellerListings() => _get('/marketplace/seller/listings');

  /// Create new coffee lot
  Future<http.Response> createListing(Map<String, dynamic> data) => _post('/marketplace/seller/listings', data);

  /// Get available subscription plans
  Future<http.Response> getSubscriptionPlans() => _get('/marketplace/plans');

  /// Initialize subscription
  Future<http.Response> subscribe(Map<String, dynamic> data) => _post('/marketplace/subscribe', data);

  /// Fetch full organization verification state and document checklist
  Future<http.Response> getVerificationStatus() => _get('/marketplace/verification/status');

  /// Submit role-specific verification details (TIN, license, authority)
  Future<http.Response> submitVerificationDetails(Map<String, dynamic> data) =>
      _post('/marketplace/verification/submit', data);

  /// Upload a verification document (real file bytes) to the private vault
  Future<http.Response> uploadVerificationDocument({
    required String targetRole,
    required String documentType,
    required List<int> fileBytes,
    required String filename,
  }) {
    return guardedRequest(() async {
      final token = await getToken();
      final url = Uri.parse('${BaseService.baseUrl}/marketplace/verification/upload-document');
      final request = http.MultipartRequest('POST', url);

      request.headers.addAll({
        'Accept': 'application/json',
        if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token',
      });

      request.fields['target_role'] = targetRole;
      request.fields['document_type'] = documentType;
      request.files.add(http.MultipartFile.fromBytes('document', fileBytes, filename: filename));

      final streamedRes = await request.send().timeout(_uploadTimeout);
      return http.Response.fromStream(streamedRes);
    });
  }

  /// Delete a draft verification document
  Future<http.Response> deleteVerificationDocument(int documentId) =>
      _delete('/marketplace/verification/documents/$documentId');
}
