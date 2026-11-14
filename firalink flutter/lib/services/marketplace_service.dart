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

  /// Values may be a String or a list of Strings (sent as repeated `key[]` parameters).
  Future<http.Response> _get(String path, [Map<String, dynamic>? query]) {
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
    int? perPage,
    Map<String, dynamic> filters = const {},
  }) {
    final queryParams = <String, dynamic>{'page': page.toString(), ...filters};
    if (perPage != null) queryParams['per_page'] = perPage.toString();
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

  /// Get seller profile & quota status
  Future<http.Response> getSellerProfile() => _get('/marketplace/seller/profile');

  /// Update seller profile
  Future<http.Response> updateSellerProfile(Map<String, dynamic> data) => _post('/marketplace/seller/profile', data);

  /// Get available subscription plans
  Future<http.Response> getSubscriptionPlans() => _get('/marketplace/plans');

  /// Initialize subscription
  Future<http.Response> subscribe(Map<String, dynamic> data) => _post('/marketplace/subscribe', data);

}
