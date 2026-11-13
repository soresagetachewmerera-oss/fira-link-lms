import 'dart:typed_data';

import 'package:file_picker/file_picker.dart';
import 'package:http/http.dart' as http;

/// A picked document held in memory, so uploads work the same on Android, iOS and web.
/// (No dart:io File or file path, which do not exist on Flutter web.)
class UploadFile {
  const UploadFile({required this.name, required this.bytes});

  /// Same limit the server enforces.
  static const int maxBytes = 10 * 1024 * 1024;

  static const List<String> documentExtensions = ['pdf', 'jpg', 'jpeg', 'png'];

  final String name;
  final Uint8List bytes;

  bool get isEmpty => bytes.isEmpty;
  bool get isTooLarge => bytes.length > maxBytes;

  /// A rejection reason the client can detect before uploading, using the same
  /// codes as the server (`empty`, `too_large`), or null if it may be sent.
  String? get localRejection {
    if (isEmpty) return 'empty';
    if (isTooLarge) return 'too_large';
    return null;
  }

  http.MultipartFile toMultipart(String field) =>
      http.MultipartFile.fromBytes(field, bytes, filename: name);

  /// Opens the system file picker for a PDF, JPG or PNG and reads it into memory.
  /// Returns null if the user cancels or the platform returns no data.
  static Future<UploadFile?> pickDocument({List<String> extensions = documentExtensions}) async {
    final result = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: extensions,
      withData: true,
    );
    final picked = result?.files.single;
    final bytes = picked?.bytes;
    if (picked == null || bytes == null) {
      return null;
    }
    return UploadFile(name: picked.name, bytes: bytes);
  }
}
