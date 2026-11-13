import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;
import 'package:http_parser/http_parser.dart';

import '../models/document_analysis_result.dart';
import 'base_service.dart';

class DocumentProcessingException implements Exception {
  const DocumentProcessingException(this.message);
  final String message;

  @override
  String toString() => message;
}

class DocumentScannerService extends BaseService {
  static const _mimeTypes = <String, String>{
    'pdf': 'application/pdf',
    'jpg': 'image/jpeg',
    'jpeg': 'image/jpeg',
    'png': 'image/png',
    'webp': 'image/webp',
    'txt': 'text/plain',
  };

  Future<DocumentAnalysisResult> analyzeDocument({
    required File file,
    required String fileName,
  }) async {
    final extension = fileName.split('.').last.toLowerCase();
    final mimeType = _mimeTypes[extension];
    if (mimeType == null) {
      throw const DocumentProcessingException(
        'Unsupported file type. Use PDF, JPG, PNG, WEBP, or TXT.',
      );
    }

    final request = http.MultipartRequest(
      'POST',
      Uri.parse('${BaseService.baseUrl}/fira-ai/analyze-document'),
    );
    request.headers['Accept'] = 'application/json';
    final token = await getToken();
    if (token != null && token.isNotEmpty) {
      request.headers['Authorization'] = 'Bearer $token';
    }
    request.files.add(
      await http.MultipartFile.fromPath(
        'document_file',
        file.path,
        filename: fileName,
        contentType: MediaType.parse(mimeType),
      ),
    );

    try {
      final streamed = await request.send().timeout(
        const Duration(seconds: 90),
      );
      final response = await http.Response.fromStream(streamed);
      Map<String, dynamic>? body;
      try {
        body = jsonDecode(response.body) as Map<String, dynamic>;
      } catch (_) {}

      if (response.statusCode == 200 && body?['success'] == true) {
        final result = DocumentAnalysisResult.fromJson(
          Map<String, dynamic>.from(body!['data'] as Map),
        );
        if (result.extractedText.isEmpty) {
          throw const DocumentProcessingException(
            'No readable content could be extracted from this document.',
          );
        }
        return result;
      }

      throw DocumentProcessingException(
        body?['message']?.toString() ??
            'The document could not be processed. Please try again.',
      );
    } on DocumentProcessingException {
      rethrow;
    } on SocketException {
      throw const DocumentProcessingException(
        'No network connection. Connect to the internet and try again.',
      );
    } on http.ClientException {
      throw const DocumentProcessingException(
        'The document service could not be reached. Please try again.',
      );
    } on TimeoutException {
      throw const DocumentProcessingException(
        'Processing took too long. Try a smaller or clearer document.',
      );
    } catch (error) {
      throw const DocumentProcessingException(
        'The document could not be processed. Please try again.',
      );
    }
  }
}
