import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import '../models/fira_voice_models.dart';
import 'base_service.dart';

class FiraVoiceService extends BaseService {
  /// Start a voice session and obtain server-minted locked ephemeral token.
  Future<Map<String, dynamic>> startSession({
    String language = 'en',
    bool storeTranscripts = false,
  }) async {
    // Client-side language gate: Afan Oromo triggers immediate fallback notice
    if (language == 'om') {
      return {
        'success': false,
        'error_code': 'afan_oromo_fallback',
        'fallback_message':
            'Afan Oromo voice mode is currently in preview. Text chat available.',
        'allowed_languages': ['en', 'am'],
      };
    }

    final token = await getToken();
    final url = Uri.parse('${BaseService.baseUrl}/ai/voice/session');

    final headers = <String, String>{
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    if (token != null && token.isNotEmpty) {
      headers['Authorization'] = 'Bearer $token';
    }

    try {
      final response = await http.post(
        url,
        headers: headers,
        body: jsonEncode({
          'language': language,
          'consent': true,
          'store_transcripts': storeTranscripts,
        }),
      );

      if (response.statusCode == 401) {
        return {
          'success': false,
          'error_code': 'unauthenticated',
          'message': 'Sign in to access Fira AI Voice.',
        };
      }

      final body = jsonDecode(response.body) as Map<String, dynamic>;

      if (response.statusCode == 422 &&
          (body['error_code'] == 'language_not_supported_for_voice' ||
              language == 'om')) {
        return {
          'success': false,
          'error_code': 'afan_oromo_fallback',
          'fallback_message': body['fallback_message'] ??
              'Afan Oromo voice mode is currently in preview. Text chat available.',
          'allowed_languages': body['allowed_languages'] ?? ['en', 'am'],
        };
      }

      if (response.statusCode != 200 && response.statusCode != 201) {
        return {
          'success': false,
          'error_code': body['error_code'] ?? 'session_start_failed',
          'message': body['message'] ?? 'Could not start voice session.',
        };
      }

      return {
        'success': true,
        'credentials': VoiceSessionCredentials.fromJson(body['data'] as Map<String, dynamic>),
      };
    } catch (e) {
      return {
        'success': false,
        'error_code': 'network_error',
        'message': e.toString(),
      };
    }
  }

  /// Execute server-authorized tool call on behalf of the voice model.
  Future<Map<String, dynamic>> executeTool({
    required String sessionId,
    required String toolName,
    Map<String, dynamic>? args,
  }) async {
    final token = await getToken();
    final url = Uri.parse('${BaseService.baseUrl}/ai/voice/tool');

    final headers = <String, String>{
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    if (token != null && token.isNotEmpty) {
      headers['Authorization'] = 'Bearer $token';
    }

    try {
      final response = await http.post(
        url,
        headers: headers,
        body: jsonEncode({
          'session_id': sessionId,
          'tool_name': toolName,
          'args': args ?? {},
        }),
      );

      final body = jsonDecode(response.body) as Map<String, dynamic>;
      return body;
    } catch (e) {
      return {
        'success': false,
        'message': e.toString(),
      };
    }
  }

  /// Conclude voice session and record usage metrics.
  Future<bool> endSession({
    required String sessionId,
    required int durationSeconds,
    List<String> toolsUsed = const [],
    String? errorCode,
  }) async {
    final token = await getToken();
    final url = Uri.parse('${BaseService.baseUrl}/ai/voice/end');

    final headers = <String, String>{
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    if (token != null && token.isNotEmpty) {
      headers['Authorization'] = 'Bearer $token';
    }

    try {
      final response = await http.post(
        url,
        headers: headers,
        body: jsonEncode({
          'session_id': sessionId,
          'duration_seconds': durationSeconds,
          'tools_used': toolsUsed,
          'error_code': errorCode,
        }),
      );

      return response.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  /// Get current user voice quota status.
  Future<FiraVoiceQuota?> getQuotaStatus() async {
    final token = await getToken();
    final url = Uri.parse('${BaseService.baseUrl}/ai/voice/status');

    final headers = <String, String>{
      'Accept': 'application/json',
    };
    if (token != null && token.isNotEmpty) {
      headers['Authorization'] = 'Bearer $token';
    }

    try {
      final response = await http.get(url, headers: headers);
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body) as Map<String, dynamic>;
        if (body['success'] == true && body['data'] != null) {
          return FiraVoiceQuota.fromJson(body['data'] as Map<String, dynamic>);
        }
      }
      return null;
    } catch (_) {
      return null;
    }
  }
}
