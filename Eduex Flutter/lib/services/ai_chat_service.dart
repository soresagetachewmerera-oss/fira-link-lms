import 'dart:convert';
import 'package:http/http.dart' as http;
import 'base_service.dart';

class AiChatService extends BaseService {
  Future<http.Response> getChatHistory() async {
    final token = await getToken();
    final url = Uri.parse('${BaseService.baseUrl}/ai-chat');
    final headers = <String, String>{
      'Accept': 'application/json',
    };
    if (token != null && token.isNotEmpty) {
      headers['Authorization'] = 'Bearer $token';
    }
    return await http.get(url, headers: headers);
  }

  Future<http.Response> sendMessage(String message) async {
    final token = await getToken();
    final url = Uri.parse('${BaseService.baseUrl}/ai-chat/send');
    final headers = <String, String>{
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    if (token != null && token.isNotEmpty) {
      headers['Authorization'] = 'Bearer $token';
    }
    return await http.post(
      url,
      headers: headers,
      body: jsonEncode({'message': message}),
    );
  }
}
