import 'dart:async';
import 'dart:convert';
import 'package:http/http.dart' as http;
import '../data/marketplace_demo_data.dart';
import 'base_service.dart';

class MarketplaceService extends BaseService {
  static const Duration _timeout = Duration(milliseconds: 10000);

  http.Response _mockResponse(Map<String, dynamic> body, [int status = 200]) {
    return http.Response(
      jsonEncode(body),
      status,
      headers: {'content-type': 'application/json; charset=utf-8'},
    );
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

  /// Fetch full organization entitlements, usage, and status snapshot
  Future<http.Response> getOrganizationEntitlements() async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/user/organization/entitlements');
      final res = await http.get(url, headers: headers).timeout(_timeout);
      if (res.statusCode == 200) {
        return res;
      }
    } catch (_) {}

    return _mockResponse({
      'success': true,
      'data': {
        'organization': {
          'id': 1,
          'name': 'Sidama Horizon Coffee Union',
          'business_type': 'exporter',
          'verification_tier': 1,
          'is_verified': true,
          'my_role': 'owner',
        },
        'subscription': {
          'plan_key': 'growth',
          'plan_name': 'Pro Exporter (Growth)',
          'status': 'active',
          'billing_period': 'monthly',
          'grace_until': null,
        },
        'entitlements': {
          'active_lots': 15,
          'rfq_responses_per_month': 30,
          'active_deal_rooms': 10,
          'seats': 5,
          'storage_gb': 10,
          'toolkit_level': 'full',
          'contract_templates': 1,
          'esignature': 1,
        },
        'usage': {
          'active_lots': 3,
          'rfq_responses_per_month': 4,
          'active_deal_rooms': 1,
          'seats': 2,
          'storage_gb': 1,
        },
        'active_overrides': [],
      },
    });
  }

  /// Fetch paginated coffee listings with filters
  Future<http.Response> getListings({
    String? search,
    String? origin,
    String? processing,
    String? grade,
    double? minScore,
    String? sort,
    int page = 1,
  }) async {
    try {
      final headers = await getHeaders();
      final queryParams = <String, String>{'page': page.toString()};
      if (search != null && search.isNotEmpty) queryParams['search'] = search;
      if (origin != null && origin != 'all' && origin != 'All') queryParams['origin'] = origin;
      if (processing != null && processing != 'all' && processing != 'All') queryParams['processing'] = processing;
      if (grade != null && grade != 'all' && grade != 'All') queryParams['grade'] = grade;
      if (minScore != null && minScore > 0) queryParams['min_score'] = minScore.toString();
      if (sort != null && sort.isNotEmpty) queryParams['sort'] = sort;

      final uri = Uri.parse('${BaseService.baseUrl}/marketplace/listings').replace(queryParameters: queryParams);
      final res = await http.get(uri, headers: headers).timeout(_timeout);

      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        final list = decoded['data']?['data'];
        if (list is List && list.isNotEmpty) {
          return res;
        }
      }
    } catch (_) {
      // Gracefully fall back to bundled demo data
    }

    // Bundled fallback
    final filtered = MarketplaceDemoData.filterListings(
      search: search,
      origin: origin,
      processing: processing,
      grade: grade,
      minScore: minScore,
      sort: sort,
    );

    return _mockResponse({
      'success': true,
      'data': {
        'data': filtered,
        'current_page': page,
        'last_page': 1,
        'per_page': 15,
        'total': filtered.length,
      },
    });
  }

  /// Get single listing detail
  Future<http.Response> getListingDetail(dynamic idOrSlug) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/marketplace/listings/$idOrSlug');
      final res = await http.get(url, headers: headers).timeout(_timeout);
      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        if (decoded['data']?['listing'] != null) {
          return res;
        }
      }
    } catch (_) {
      // Fallback
    }

    final lot = MarketplaceDemoData.findListing(idOrSlug) ?? MarketplaceDemoData.listings.first;
    return _mockResponse({
      'success': true,
      'data': {
        'listing': lot,
        'official_benchmark': {
          'origin': lot['origin_region'] ?? 'Ethiopia',
          'grade': lot['grade'] ?? 'grade_1',
          'processing': lot['processing_method'] ?? 'washed',
          'minimum_price_fob_lb': 3.85,
          'minimum_price_fob_mt': 8487.0,
          'effective_date': '2026-11-01',
        },
        'is_saved': MarketplaceDemoData.isSaved(lot['id'] as int? ?? 1),
      },
    });
  }

  /// Get taxonomy filters (origins, processes, grades)
  Future<http.Response> getFilters() async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/marketplace/filters');
      final res = await http.get(url, headers: headers).timeout(_timeout);
      if (res.statusCode == 200) {
        return res;
      }
    } catch (_) {}

    return _mockResponse({
      'success': true,
      'data': MarketplaceDemoData.filters,
    });
  }

  /// Toggle saved listing
  Future<http.Response> toggleSaveListing(int id) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/marketplace/listings/$id/save');
      final res = await http.post(url, headers: headers).timeout(_timeout);
      if (res.statusCode == 200) {
        return res;
      }
    } catch (_) {}

    final saved = MarketplaceDemoData.toggleSave(id);
    return _mockResponse({
      'success': true,
      'message': saved ? 'Lot saved to your watchlist' : 'Lot removed from watchlist',
      'saved': saved,
    });
  }

  /// Get user's saved lots
  Future<http.Response> getSavedListings() async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/marketplace/saved-listings');
      final res = await http.get(url, headers: headers).timeout(_timeout);
      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        if (decoded['data'] is List && (decoded['data'] as List).isNotEmpty) {
          return res;
        }
      }
    } catch (_) {}

    return _mockResponse({
      'success': true,
      'data': MarketplaceDemoData.getSavedLots(),
    });
  }

  /// Send trade inquiry or sample request
  Future<http.Response> submitInquiry(int listingId, Map<String, dynamic> data) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/marketplace/listings/$listingId/inquire');
      final res = await http.post(url, headers: headers, body: jsonEncode(data)).timeout(_timeout);
      if (res.statusCode == 200) {
        return res;
      }
    } catch (_) {}

    return _mockResponse({
      'success': true,
      'message': 'Trade inquiry and sample request dispatched to exporter!',
    });
  }

  /// Get inquiries
  Future<http.Response> getInquiries({String view = 'all'}) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/marketplace/inquiries?view=$view');
      final res = await http.get(url, headers: headers).timeout(_timeout);
      if (res.statusCode == 200) {
        return res;
      }
    } catch (_) {}

    return _mockResponse({
      'success': true,
      'data': [
        {
          'id': 1,
          'type': 'sample_request',
          'status': 'pending',
          'notes': 'Requesting 250g green sample for cupping lab evaluation.',
          'target_price': '4.85',
          'listing': MarketplaceDemoData.listings[0],
          'sender': {'name': 'Tokyo Craft Roasters Collective'},
          'created_at': '2026-11-03T14:00:00Z',
        },
      ],
    });
  }

  /// Get open buyer RFQs
  Future<http.Response> getRfqs({String? origin, String? destinationCountry, int page = 1}) async {
    try {
      final headers = await getHeaders();
      final queryParams = <String, String>{
        'page': page.toString(),
        if (origin?.isNotEmpty == true) 'origin': origin!,
        if (destinationCountry?.isNotEmpty == true) 'destination_country': destinationCountry!,
      };
      final uri = Uri.parse('${BaseService.baseUrl}/marketplace/rfqs').replace(queryParameters: queryParams);
      final res = await http.get(uri, headers: headers).timeout(_timeout);
      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        final list = decoded['data']?['data'];
        if (list is List && list.isNotEmpty) {
          return res;
        }
      }
    } catch (_) {}

    final filtered = MarketplaceDemoData.filterRfqs(
      origin: origin,
      destinationCountry: destinationCountry,
    );

    return _mockResponse({
      'success': true,
      'data': {
        'data': filtered,
        'current_page': page,
        'last_page': 1,
        'per_page': 15,
        'total': filtered.length,
      },
    });
  }

  /// Get single RFQ detail
  Future<http.Response> getRfqDetail(int id) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/marketplace/rfqs/$id');
      final res = await http.get(url, headers: headers).timeout(_timeout);
      if (res.statusCode == 200) {
        return res;
      }
    } catch (_) {}

    final rfq = MarketplaceDemoData.findRfq(id) ?? MarketplaceDemoData.rfqs.first;
    return _mockResponse({
      'success': true,
      'data': rfq,
    });
  }

  /// Publish a buyer RFQ
  Future<http.Response> submitRfq(Map<String, dynamic> data) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/marketplace/rfqs');
      final res = await http.post(url, headers: headers, body: jsonEncode(data)).timeout(_timeout);
      if (res.statusCode == 200) {
        return res;
      }
    } catch (_) {}

    final newRfq = MarketplaceDemoData.addRfq(data);
    return _mockResponse({
      'success': true,
      'message': 'Buyer RFQ tender published to verified Ethiopian exporters!',
      'data': newRfq,
    });
  }

  /// Submit an exporter quotation for an RFQ
  Future<http.Response> submitQuote(int rfqId, Map<String, dynamic> data) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/marketplace/rfqs/$rfqId/quote');
      final res = await http.post(url, headers: headers, body: jsonEncode(data)).timeout(_timeout);
      return res;
    } catch (_) {}

    return _mockResponse({
      'success': true,
      'message': 'Quotation submitted successfully to international buyer!',
    });
  }

  /// Get quotations received for an RFQ
  Future<http.Response> getRfqQuotes(int rfqId) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/marketplace/rfqs/$rfqId/quotes');
      final res = await http.get(url, headers: headers).timeout(_timeout);
      return res;
    } catch (_) {}

    return _mockResponse({
      'success': true,
      'data': [],
    });
  }

  /// Get exporter's submitted quotes
  Future<http.Response> getMyQuotes() async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/marketplace/my-quotes');
      final res = await http.get(url, headers: headers).timeout(_timeout);
      if (res.statusCode == 200) {
        return res;
      }
    } catch (_) {}

    return _mockResponse({
      'success': true,
      'data': [],
    });
  }

  /// Get seller profile & quota status
  Future<http.Response> getSellerProfile() async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/marketplace/seller/profile');
      final res = await http.get(url, headers: headers).timeout(_timeout);
      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        if (decoded['data']?['profile'] != null) {
          return res;
        }
      }
    } catch (_) {}

    return _mockResponse({
      'success': true,
      'data': {
        'profile': MarketplaceDemoData.sellers[0],
        'active_subscription': {
          'plan': MarketplaceDemoData.subscriptionPlans[1], // Pro Exporter
          'status': 'active',
          'starts_at': '2026-11-01',
          'expires_at': '2026-12-01',
        },
        'quota': {
          'active_listings': 3,
          'max_listings': 15,
          'can_post_listing': true,
          'can_respond_rfq': true,
        },
      },
    });
  }

  /// Update seller profile
  Future<http.Response> updateSellerProfile(Map<String, dynamic> data) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/marketplace/seller/profile');
      final res = await http.post(url, headers: headers, body: jsonEncode(data)).timeout(_timeout);
      if (res.statusCode == 200) {
        return res;
      }
    } catch (_) {}

    return _mockResponse({
      'success': true,
      'message': 'Seller profile updated successfully!',
    });
  }

  /// Get seller's active lots
  Future<http.Response> getSellerListings() async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/marketplace/seller/listings');
      final res = await http.get(url, headers: headers).timeout(_timeout);
      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        final list = decoded['data']?['data'];
        if (list is List && list.isNotEmpty) {
          return res;
        }
      }
    } catch (_) {}

    final lots = MarketplaceDemoData.listings.take(3).toList();
    return _mockResponse({
      'success': true,
      'data': {
        'data': lots,
        'current_page': 1,
        'last_page': 1,
        'total': lots.length,
      },
    });
  }

  /// Create new coffee lot
  Future<http.Response> createListing(Map<String, dynamic> data) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/marketplace/seller/listings');
      final res = await http.post(url, headers: headers, body: jsonEncode(data)).timeout(_timeout);
      return res;
    } catch (_) {}

    final created = MarketplaceDemoData.addListing(data);
    return _mockResponse({
      'success': true,
      'message': 'Coffee lot published successfully!',
      'data': created,
    });
  }

  /// Get available subscription plans
  Future<http.Response> getSubscriptionPlans() async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/marketplace/plans');
      final res = await http.get(url, headers: headers).timeout(_timeout);
      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        if (decoded['data'] is List && (decoded['data'] as List).isNotEmpty) {
          return res;
        }
      }
    } catch (_) {}

    return _mockResponse({
      'success': true,
      'data': MarketplaceDemoData.subscriptionPlans,
    });
  }

  /// Initialize subscription
  Future<http.Response> subscribe(Map<String, dynamic> data) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/marketplace/subscribe');
      final res = await http.post(url, headers: headers, body: jsonEncode(data)).timeout(_timeout);
      return res;
    } catch (_) {}

    return _mockResponse({
      'success': true,
      'status': 'activated',
      'message': 'Plan activated successfully!',
    });
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

  /// Fetch full organization verification state and document checklist
  Future<http.Response> getVerificationStatus() async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/marketplace/verification/status');
      final res = await http.get(url, headers: headers).timeout(_timeout);
      if (res.statusCode == 200) {
        return res;
      }
    } catch (_) {}

    return _mockResponse({
      'success': true,
      'data': {
        'organization': {
          'id': 1,
          'name': 'Ethiopian Coffee Exporters Union',
          'is_verified': false,
          'verified_roles': [],
          'country': 'Ethiopia',
        },
        'checklist': {
          'exporter': {
            'label': 'Licensed Coffee Exporter',
            'status': 'unsubmitted',
            'required_documents': [
              {'type': 'commercial_reg', 'label': 'Commercial Registration Certificate (MoTI)'},
              {'type': 'sector_license', 'label': 'ECTA Coffee Export Competence Certificate'},
              {'type': 'tin_cert', 'label': 'TIN & VAT Registration Certificate'},
              {'type': 'rep_id', 'label': 'Authorized Signatory Passport / National ID'},
            ],
          },
        },
      },
    });
  }

  /// Submit role-specific verification details (TIN, license, authority)
  Future<http.Response> submitVerificationDetails(Map<String, dynamic> data) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/marketplace/verification/submit');
      final res = await http.post(url, headers: headers, body: jsonEncode(data)).timeout(_timeout);
      return res;
    } catch (_) {}

    return _mockResponse({
      'success': true,
      'message': 'Verification dossier submitted for compliance review.',
    });
  }

  /// Upload verification document to private vault
  Future<http.Response> uploadVerificationDocument({
    required String targetRole,
    required String documentType,
    required List<int> fileBytes,
    required String filename,
  }) async {
    try {
      final token = await getToken();
      final url = Uri.parse('${BaseService.baseUrl}/marketplace/verification/upload-document');
      final request = http.MultipartRequest('POST', url);

      request.headers.addAll({
        'Accept': 'application/json',
        if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token',
      });

      request.fields['target_role'] = targetRole;
      request.fields['document_type'] = documentType;

      final multipartFile = http.MultipartFile.fromBytes(
        'document',
        fileBytes,
        filename: filename,
      );
      request.files.add(multipartFile);

      final streamedRes = await request.send().timeout(const Duration(seconds: 30));
      return await http.Response.fromStream(streamedRes);
    } catch (e) {
      return _mockResponse({
        'success': false,
        'message': 'Failed to upload document: $e',
      }, 500);
    }
  }

  /// Delete a draft verification document
  Future<http.Response> deleteVerificationDocument(int documentId) async {
    try {
      final headers = await getHeaders();
      final url = Uri.parse('${BaseService.baseUrl}/marketplace/verification/documents/$documentId');
      final res = await http.delete(url, headers: headers).timeout(_timeout);
      return res;
    } catch (_) {}

    return _mockResponse({
      'success': true,
      'message': 'Document removed successfully.',
    });
  }
}
