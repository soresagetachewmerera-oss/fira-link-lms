import 'dart:convert';
import 'package:http/http.dart' as http;
import 'base_service.dart';

class TourService extends BaseService {
  Future<http.Response> fetchTours({
    String? search,
    String? category,
    String? difficulty,
    String? sort,
    int page = 1,
  }) async {
    final queryParams = <String, String>{
      'page': page.toString(),
    };
    if (search != null && search.isNotEmpty) queryParams['search'] = search;
    if (category != null && category.isNotEmpty) queryParams['category'] = category;
    if (difficulty != null && difficulty.isNotEmpty) queryParams['difficulty'] = difficulty;
    if (sort != null && sort.isNotEmpty) queryParams['sort'] = sort;

    final uri = Uri.parse('${BaseService.baseUrl}/tours').replace(queryParameters: queryParams);
    try {
      final response = await http.get(uri, headers: await getHeaders());
      return response;
    } catch (e) {
      rethrow;
    }
  }

  Future<http.Response> fetchTourDetails(dynamic id) async {
    final url = Uri.parse('${BaseService.baseUrl}/tours/$id');
    try {
      final response = await http.get(url, headers: await getHeaders());
      return response;
    } catch (e) {
      rethrow;
    }
  }

  Future<http.Response> bookTour({
    required int tourId,
    required Map<String, dynamic> data,
  }) async {
    final url = Uri.parse('${BaseService.baseUrl}/tours/$tourId/book');
    try {
      final response = await http.post(
        url,
        headers: await getHeaders(),
        body: jsonEncode(data),
      );
      return response;
    } catch (e) {
      rethrow;
    }
  }

  Future<http.Response> getMyTourBookings({int page = 1}) async {
    final url = Uri.parse('${BaseService.baseUrl}/user/tour-bookings?page=$page');
    try {
      final response = await http.get(url, headers: await getHeaders());
      return response;
    } catch (e) {
      rethrow;
    }
  }

  Future<http.Response> getTourBookingDetails(int id) async {
    final url = Uri.parse('${BaseService.baseUrl}/user/tour-bookings/$id');
    try {
      final response = await http.get(url, headers: await getHeaders());
      return response;
    } catch (e) {
      rethrow;
    }
  }

  Future<http.Response> cancelTourBooking(int id) async {
    final url = Uri.parse('${BaseService.baseUrl}/user/tour-bookings/$id/cancel');
    try {
      final response = await http.post(url, headers: await getHeaders());
      return response;
    } catch (e) {
      rethrow;
    }
  }
}
