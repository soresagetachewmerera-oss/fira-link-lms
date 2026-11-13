class DocumentAnalysisResult {
  const DocumentAnalysisResult({
    required this.fileName,
    required this.documentType,
    required this.extractedText,
    required this.summary,
    required this.analysis,
    required this.keyFindings,
    required this.analyzedAt,
  });

  final String fileName;
  final String documentType;
  final String extractedText;
  final String summary;
  final String analysis;
  final List<String> keyFindings;
  final DateTime analyzedAt;

  factory DocumentAnalysisResult.fromJson(Map<String, dynamic> json) {
    return DocumentAnalysisResult(
      fileName: json['file_name']?.toString() ?? '',
      documentType: json['document_type']?.toString() ?? 'Document',
      extractedText: json['extracted_text']?.toString().trim() ?? '',
      summary: json['summary']?.toString().trim() ?? '',
      analysis: json['analysis']?.toString().trim() ?? '',
      keyFindings: (json['key_findings'] as List<dynamic>? ?? const [])
          .map((item) => item.toString().trim())
          .where((item) => item.isNotEmpty)
          .toList(),
      analyzedAt:
          DateTime.tryParse(json['analyzed_at']?.toString() ?? '') ??
          DateTime.now(),
    );
  }
}
