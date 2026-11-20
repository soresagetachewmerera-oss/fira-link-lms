import 'dart:convert';
import 'package:http/http.dart' as http;
import '../services/api_exception.dart';
import '../services/base_service.dart';

class ProfileService extends BaseService {
  /// Name, bio and a first phone number. Email and an existing phone are not sent: changing them
  /// needs a code to the new address or number (server: PHONE_CHANGE_NEEDS_CODE, EMAIL_CHANGE_NEEDS_CODE).
  /// Throws [ApiException] with the server's code on failure.
  Future<Map<String, dynamic>> updateProfile({
    required String name,
    String? phone,
    String? bio,
  }) async {
    final token = await getToken();
    final response = await http.post(
      Uri.parse('${BaseService.baseUrl}/user/profile'),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token',
      },
      body: jsonEncode({
        'name': name,
        if (phone != null && phone.isNotEmpty) 'phone': phone,
        'bio': bio,
      }),
    );

    return jsonDecode(requireSuccess(response).body) as Map<String, dynamic>;
  }

  Future<void> updatePassword({
    required String currentPassword,
    required String newPassword,
    required String newPasswordConfirmation,
  }) async {
    final token = await getToken();
    final response = await http.post(
      Uri.parse('${BaseService.baseUrl}/user/password'),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token',
      },
      body: jsonEncode({
        'current_password': currentPassword,
        'new_password': newPassword,
        'new_password_confirmation': newPasswordConfirmation,
      }),
    );

    if (response.statusCode != 200) {
      final body = jsonDecode(response.body);
      throw Exception(body['message'] ?? 'Failed to update password');
    }
  }

  /// The raw answer, so the caller can run it through FreshAuthSheet: the server needs a fresh
  /// code or password for this (403 FRESH_AUTH_REQUIRED). Throws [ApiException.network] offline.
  Future<http.Response> submitDeleteRequest() async {
    final headers = await getHeaders();
    return guardedRequest(() => http.post(
          Uri.parse('${BaseService.baseUrl}/user/delete-request'),
          headers: headers,
        ));
  }

  Future<Map<String, dynamic>?> getDeleteRequestStatus() async {
    final headers = await getHeaders();
    final response = await http.get(
      Uri.parse('${BaseService.baseUrl}/user/delete-request'),
      headers: headers,
    );

    if (response.statusCode == 200) {
      final body = jsonDecode(response.body);
      return body['data'];
    } else {
      return null;
    }
  }

  Future<String> uploadProfilePhotoBytes({
    required List<int> bytes,
    required String filename,
  }) async {
    final token = await getToken();
    var request = http.MultipartRequest(
      'POST',
      Uri.parse('${BaseService.baseUrl}/user/profile-photo'),
    );
    if (token != null && token.isNotEmpty) {
      request.headers['Authorization'] = 'Bearer $token';
    }
    request.headers['Accept'] = 'application/json';
    request.files.add(http.MultipartFile.fromBytes(
      'photo',
      bytes,
      filename: filename,
    ));

    var streamedResponse = await request.send();
    var response = await http.Response.fromStream(streamedResponse);

    if (response.statusCode == 200) {
      final body = jsonDecode(response.body);
      return body['photo_url'] ?? '';
    } else {
      try {
        final body = jsonDecode(response.body);
        throw Exception(body['message'] ?? 'Failed to upload photo');
      } catch (_) {
        throw Exception('Failed to upload photo: ${response.statusCode}');
      }
    }
  }

  Future<String> uploadProfilePhoto(String filePath) async {
    final token = await getToken();
    var request = http.MultipartRequest(
      'POST',
      Uri.parse('${BaseService.baseUrl}/user/profile-photo'),
    );
    if (token != null && token.isNotEmpty) {
      request.headers['Authorization'] = 'Bearer $token';
    }
    request.headers['Accept'] = 'application/json';
    request.files.add(await http.MultipartFile.fromPath('photo', filePath));

    var streamedResponse = await request.send();
    var response = await http.Response.fromStream(streamedResponse);

    if (response.statusCode == 200) {
      final body = jsonDecode(response.body);
      return body['photo_url'] ?? '';
    } else {
      try {
        final body = jsonDecode(response.body);
        throw Exception(body['message'] ?? 'Failed to upload photo');
      } catch (_) {
        throw Exception('Failed to upload photo: ${response.statusCode}');
      }
    }
  }
}
