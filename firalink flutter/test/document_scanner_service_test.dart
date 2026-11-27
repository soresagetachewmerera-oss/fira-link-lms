import 'dart:typed_data';

import 'package:flutter_test/flutter_test.dart';
import 'package:eduex_app/models/document_analysis_result.dart';
import 'package:eduex_app/services/document_scanner_service.dart';

void main() {
  group('DocumentScannerService Unit Tests', () {
    late DocumentScannerService service;

    setUp(() {
      service = DocumentScannerService();
    });

    test('rejects unsupported file extensions before making network calls', () async {
      expect(
        () => service.analyzeDocument(
          bytes: Uint8List.fromList([1, 2, 3]),
          fileName: 'contract.exe',
        ),
        throwsA(
          isA<DocumentProcessingException>().having(
            (e) => e.message,
            'message',
            contains('Unsupported file type'),
          ),
        ),
      );
    });

    test('rejects empty byte arrays', () async {
      expect(
        () => service.analyzeDocument(
          bytes: Uint8List(0),
          fileName: 'contract.pdf',
        ),
        throwsA(
          isA<DocumentProcessingException>().having(
            (e) => e.message,
            'message',
            contains('empty'),
          ),
        ),
      );
    });

    test('DocumentAnalysisResult accurately deserializes JSON payload', () {
      final json = {
        'file_name': 'export_contract_082.pdf',
        'document_type': 'Coffee Export Contract',
        'extracted_text': 'EXPORT CONTRACT NO: ECX-2026-88\nSELLER: Yirgacheffe Farmers Union\nQUANTITY: 19.2 MT Grade 1 Washed Coffee',
        'summary': 'Coffee export contract between Ethiopian exporter and European buyer.',
        'analysis': 'Valid FOB Djibouti terms with Standard LC payment terms.',
        'key_findings': [
          'Quantity: 19.2 Metric Tons',
          'Grade: Yirgacheffe Grade 1 Washed',
          'Price: \$3.85 / lb FOB Djibouti',
          'Delivery: February 2027',
        ],
        'analyzed_at': '2026-11-27T12:00:00Z',
      };

      final result = DocumentAnalysisResult.fromJson(json);

      expect(result.fileName, equals('export_contract_082.pdf'));
      expect(result.documentType, equals('Coffee Export Contract'));
      expect(result.extractedText, contains('EXPORT CONTRACT NO: ECX-2026-88'));
      expect(result.summary, contains('Coffee export contract'));
      expect(result.analysis, contains('FOB Djibouti'));
      expect(result.keyFindings.length, equals(4));
      expect(result.keyFindings[0], contains('19.2 Metric Tons'));
      expect(result.keyFindings[2], contains('\$3.85'));
    });
  });
}
