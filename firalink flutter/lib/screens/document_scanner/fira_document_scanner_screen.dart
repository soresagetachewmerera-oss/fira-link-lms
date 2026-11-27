import 'dart:typed_data';

import 'package:file_picker/file_picker.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';
import 'package:permission_handler/permission_handler.dart';

import '../../l10n/app_localizations.dart';
import '../../models/document_analysis_result.dart';
import '../../services/document_scanner_service.dart';
import '../../theme/fira_colors.dart';

class FiraDocumentScannerScreen extends StatefulWidget {
  const FiraDocumentScannerScreen({super.key, this.initialDocumentText});

  // Kept for route compatibility. Analysis only accepts a real document.
  final String? initialDocumentText;

  @override
  State<FiraDocumentScannerScreen> createState() =>
      _FiraDocumentScannerScreenState();
}

class _FiraDocumentScannerScreenState extends State<FiraDocumentScannerScreen> {
  static const _maxBytes = 20 * 1024 * 1024;

  final _imagePicker = ImagePicker();
  final _service = DocumentScannerService();

  Uint8List? _fileBytes;
  String? _fileName;
  int? _fileSize;
  bool _processing = false;
  String? _error;
  DocumentAnalysisResult? _result;

  bool _isImage(String? name) {
    if (name == null) return false;
    final ext = name.split('.').last.toLowerCase();
    return ['jpg', 'jpeg', 'png', 'webp'].contains(ext);
  }

  Future<void> _chooseSource() async {
    final l10n = context.l10n;
    final fira = context.fira;

    final source = await showModalBottomSheet<String>(
      context: context,
      showDragHandle: true,
      backgroundColor: fira.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
      ),
      builder: (sheetContext) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                l10n.docScannerScanButton,
                style: Theme.of(sheetContext).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.w700,
                      color: fira.onSurface,
                    ),
              ),
              const SizedBox(height: 16),
              FilledButton.icon(
                onPressed: () => Navigator.pop(sheetContext, 'camera'),
                icon: const Icon(Icons.document_scanner_outlined),
                label: Text(l10n.docScannerCamera),
                style: FilledButton.styleFrom(
                  backgroundColor: fira.brandPrimary,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16),
                  ),
                  padding: const EdgeInsets.symmetric(vertical: 14),
                ),
              ),
              const SizedBox(height: 10),
              OutlinedButton.icon(
                onPressed: () => Navigator.pop(sheetContext, 'upload'),
                icon: const Icon(Icons.upload_file_outlined),
                label: Text(l10n.docScannerUpload),
                style: OutlinedButton.styleFrom(
                  foregroundColor: fira.brandPrimary,
                  side: BorderSide(color: fira.brandPrimary),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16),
                  ),
                  padding: const EdgeInsets.symmetric(vertical: 14),
                ),
              ),
            ],
          ),
        ),
      ),
    );

    if (source == 'camera') await _scanWithCamera();
    if (source == 'upload') await _uploadDocument();
  }

  Future<void> _scanWithCamera() async {
    try {
      if (!kIsWeb) {
        final permission = await Permission.camera.request();
        if (!permission.isGranted) {
          if (!mounted) return;
          setState(
            () => _error = permission.isPermanentlyDenied
                ? context.l10n.docScannerCameraDisabled
                : context.l10n.docScannerCameraDenied,
          );
          return;
        }
      }

      final image = await _imagePicker.pickImage(
        source: ImageSource.camera,
        imageQuality: 92,
        maxWidth: 2400,
        maxHeight: 3200,
      );

      if (image != null) {
        final bytes = await image.readAsBytes();
        await _selectBytes(bytes, image.name);
      }
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = 'Could not access camera: $e');
    }
  }

  Future<void> _uploadDocument() async {
    try {
      final picked = await FilePicker.platform.pickFiles(
        type: FileType.custom,
        allowedExtensions: const ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'txt'],
        allowMultiple: false,
        withData: true,
      );

      final item = picked?.files.single;
      if (item != null) {
        Uint8List? bytes = item.bytes;
        if (bytes == null && item.path != null) {
          final xfile = XFile(item.path!);
          bytes = await xfile.readAsBytes();
        }

        if (bytes != null) {
          await _selectBytes(bytes, item.name);
        }
      }
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = 'Failed to select file: $e');
    }
  }

  Future<void> _selectBytes(Uint8List bytes, String name) async {
    final size = bytes.lengthInBytes;
    if (!mounted) return;

    if (size <= 0 || size > _maxBytes) {
      setState(
        () => _error = size > _maxBytes
            ? context.l10n.docScannerFileTooLarge
            : context.l10n.docScannerFileEmpty,
      );
      return;
    }

    setState(() {
      _fileBytes = bytes;
      _fileName = name;
      _fileSize = size;
      _result = null;
      _error = null;
    });

    await _process();
  }

  Future<void> _process() async {
    if (_fileBytes == null || _fileName == null) return;
    setState(() {
      _processing = true;
      _error = null;
    });

    try {
      final result = await _service.analyzeDocument(
        bytes: _fileBytes!,
        fileName: _fileName!,
      );
      if (!mounted) return;
      setState(() => _result = result);
    } on DocumentProcessingException catch (error) {
      if (!mounted) return;
      setState(() => _error = error.message);
    } finally {
      if (mounted) setState(() => _processing = false);
    }
  }

  void _reset() {
    setState(() {
      _fileBytes = null;
      _fileName = null;
      _fileSize = null;
      _result = null;
      _error = null;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(context.l10n.docScannerAppBarTitle),
        actions: [
          if (_fileBytes != null)
            TextButton(
              onPressed: _processing ? null : _reset,
              child: const Text('New'),
            ),
        ],
      ),
      body: SafeArea(
        child: _processing
            ? _ProcessingView(
                fileName: _fileName ?? 'document',
                fileBytes: _fileBytes,
                isImage: _isImage(_fileName),
              )
            : _result != null
                ? _ResultsView(
                    result: _result!,
                    fileBytes: _fileBytes,
                    isImage: _isImage(_fileName),
                    onAnother: _reset,
                  )
                : _StartView(
                    fileName: _fileName,
                    fileSize: _fileSize,
                    fileBytes: _fileBytes,
                    isImage: _isImage(_fileName),
                    error: _error,
                    onChoose: _chooseSource,
                    onRetry: _fileBytes == null ? null : _process,
                  ),
      ),
    );
  }
}

class _StartView extends StatelessWidget {
  const _StartView({
    required this.fileName,
    required this.fileSize,
    required this.fileBytes,
    required this.isImage,
    required this.error,
    required this.onChoose,
    required this.onRetry,
  });

  final String? fileName;
  final int? fileSize;
  final Uint8List? fileBytes;
  final bool isImage;
  final String? error;
  final VoidCallback onChoose;
  final VoidCallback? onRetry;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;

    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 580),
          child: Column(
            children: [
              Container(
                width: 76,
                height: 76,
                decoration: BoxDecoration(
                  color: fira.brandTint,
                  borderRadius: BorderRadius.circular(22),
                ),
                child: Icon(
                  Icons.document_scanner_outlined,
                  size: 38,
                  color: fira.brandPrimary,
                ),
              ),
              const SizedBox(height: 24),
              Text(
                l10n.docScannerTitle,
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                      fontWeight: FontWeight.w800,
                      color: fira.onSurface,
                    ),
              ),
              const SizedBox(height: 10),
              Text(
                l10n.docScannerSubtitle,
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                      color: fira.mutedText,
                    ),
              ),
              if (fileName != null && fileBytes != null) ...[
                const SizedBox(height: 24),
                Container(
                  decoration: BoxDecoration(
                    color: fira.card,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: fira.brandTint),
                  ),
                  clipBehavior: Clip.antiAlias,
                  child: Column(
                    children: [
                      if (isImage)
                        ConstrainedBox(
                          constraints: const BoxConstraints(maxHeight: 200),
                          child: Image.memory(
                            fileBytes!,
                            width: double.infinity,
                            fit: BoxFit.cover,
                          ),
                        ),
                      ListTile(
                        leading: Icon(
                          isImage ? Icons.image_outlined : Icons.description_outlined,
                          color: fira.brandPrimary,
                        ),
                        title: Text(
                          fileName!,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            fontWeight: FontWeight.w600,
                            color: fira.onSurface,
                          ),
                        ),
                        subtitle: Text(
                          '${((fileSize ?? 0) / 1024 / 1024).toStringAsFixed(2)} MB',
                          style: TextStyle(color: fira.mutedText),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
              if (error != null) ...[
                const SizedBox(height: 16),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: Theme.of(context).colorScheme.errorContainer,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Row(
                    children: [
                      Icon(
                        Icons.error_outline,
                        color: Theme.of(context).colorScheme.onErrorContainer,
                        size: 20,
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          error!,
                          style: TextStyle(
                            color: Theme.of(context).colorScheme.onErrorContainer,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
              const SizedBox(height: 28),
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  onPressed: onChoose,
                  icon: const Icon(Icons.add_a_photo_outlined),
                  label: Text(l10n.docScannerScanButton),
                  style: FilledButton.styleFrom(
                    backgroundColor: fira.brandPrimary,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(16),
                    ),
                  ),
                ),
              ),
              if (onRetry != null) ...[
                const SizedBox(height: 8),
                TextButton(
                  onPressed: onRetry,
                  child: Text(
                    'Try again',
                    style: TextStyle(color: fira.brandPrimary),
                  ),
                ),
              ],
              const SizedBox(height: 12),
              Text(
                l10n.docScannerSupportedFormats,
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: fira.mutedText,
                    ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ProcessingView extends StatelessWidget {
  const _ProcessingView({
    required this.fileName,
    this.fileBytes,
    required this.isImage,
  });

  final String fileName;
  final Uint8List? fileBytes;
  final bool isImage;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;

    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(32),
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 480),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              if (isImage && fileBytes != null) ...[
                Container(
                  height: 180,
                  width: double.infinity,
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: fira.brandTint, width: 2),
                  ),
                  clipBehavior: Clip.antiAlias,
                  child: Stack(
                    fit: StackFit.expand,
                    children: [
                      Image.memory(fileBytes!, fit: BoxFit.cover),
                      Container(
                        color: fira.brandDeep.withValues(alpha: 0.35),
                      ),
                      Center(
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                          decoration: BoxDecoration(
                            color: Colors.black.withValues(alpha: 0.65),
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              SizedBox(
                                width: 14,
                                height: 14,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  color: fira.brandTint,
                                ),
                              ),
                              const SizedBox(width: 8),
                              const Text(
                                'AI OCR Vision Running',
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 12,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 24),
              ] else ...[
                CircularProgressIndicator(color: fira.brandPrimary),
                const SizedBox(height: 24),
              ],
              Text(
                l10n.docScannerReading,
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.w700,
                      color: fira.onSurface,
                    ),
              ),
              const SizedBox(height: 8),
              Text(
                fileName,
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontWeight: FontWeight.w600,
                  color: fira.brandPrimary,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                l10n.docScannerExtracting,
                textAlign: TextAlign.center,
                style: TextStyle(color: fira.mutedText),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ResultsView extends StatelessWidget {
  const _ResultsView({
    required this.result,
    this.fileBytes,
    required this.isImage,
    required this.onAnother,
  });

  final DocumentAnalysisResult result;
  final Uint8List? fileBytes;
  final bool isImage;
  final VoidCallback onAnother;

  void _copyToClipboard(BuildContext context, String text) {
    Clipboard.setData(ClipboardData(text: text));
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(context.l10n.docScannerCopied),
        backgroundColor: context.fira.brandPrimary,
        duration: const Duration(seconds: 2),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;

    return ListView(
      padding: const EdgeInsets.all(20),
      children: [
        // Document Type Badge
        Row(
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
              decoration: BoxDecoration(
                color: fira.brandTint,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(
                    Icons.verified_outlined,
                    size: 18,
                    color: fira.brandPrimary,
                  ),
                  const SizedBox(width: 8),
                  Text(
                    result.documentType,
                    style: TextStyle(
                      color: fira.brandPrimary,
                      fontWeight: FontWeight.w700,
                      fontSize: 14,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
        if (result.fileName.isNotEmpty) ...[
          const SizedBox(height: 8),
          Text(
            result.fileName,
            style: TextStyle(
              fontSize: 13,
              color: fira.mutedText,
            ),
          ),
        ],
        const SizedBox(height: 20),

        // Scanned Image Thumbnail if available
        if (isImage && fileBytes != null) ...[
          Container(
            height: 160,
            width: double.infinity,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: fira.brandTint),
            ),
            clipBehavior: Clip.antiAlias,
            child: Image.memory(fileBytes!, fit: BoxFit.cover),
          ),
          const SizedBox(height: 16),
        ],

        // Summary Card
        _ResultSection(
          title: l10n.docScannerSummary,
          body: result.summary,
        ),
        const SizedBox(height: 14),

        // Key Findings
        if (result.keyFindings.isNotEmpty)
          _ResultSection(
            title: l10n.docScannerKeyFindings,
            child: Column(
              children: result.keyFindings
                  .map(
                    (finding) => Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Padding(
                            padding: const EdgeInsets.only(top: 7),
                            child: CircleAvatar(
                              radius: 3.5,
                              backgroundColor: fira.brandPrimary,
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              finding,
                              style: TextStyle(
                                color: fira.onSurface,
                                height: 1.4,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  )
                  .toList(),
            ),
          ),
        if (result.keyFindings.isNotEmpty) const SizedBox(height: 14),

        // Trade Analysis
        _ResultSection(
          title: l10n.docScannerAnalysis,
          body: result.analysis,
        ),
        const SizedBox(height: 14),

        // Extracted OCR Text with Copy Action
        Card(
          elevation: 0,
          color: fira.card,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(22),
            side: BorderSide(color: fira.brandTint),
          ),
          clipBehavior: Clip.antiAlias,
          child: ExpansionTile(
            initiallyExpanded: true,
            title: Row(
              children: [
                Expanded(
                  child: Text(
                    l10n.docScannerExtractedText,
                    style: TextStyle(
                      fontWeight: FontWeight.w800,
                      color: fira.onSurface,
                    ),
                  ),
                ),
                TextButton.icon(
                  onPressed: () => _copyToClipboard(context, result.extractedText),
                  icon: const Icon(Icons.copy_rounded, size: 16),
                  label: Text(l10n.docScannerCopyText),
                  style: TextButton.styleFrom(
                    foregroundColor: fira.brandPrimary,
                    padding: const EdgeInsets.symmetric(horizontal: 8),
                  ),
                ),
              ],
            ),
            childrenPadding: const EdgeInsets.fromLTRB(18, 0, 18, 18),
            children: [
              Align(
                alignment: Alignment.centerLeft,
                child: Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: fira.brandTint.withValues(alpha: 0.35),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: SelectableText(
                    result.extractedText,
                    style: TextStyle(
                      fontFamily: 'monospace',
                      fontSize: 13,
                      height: 1.5,
                      color: fira.onSurface,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 24),

        // Bottom CTA
        OutlinedButton.icon(
          onPressed: onAnother,
          icon: const Icon(Icons.refresh_rounded),
          label: Text(l10n.docScannerScanAnother),
          style: OutlinedButton.styleFrom(
            foregroundColor: fira.brandPrimary,
            side: BorderSide(color: fira.brandPrimary),
            padding: const EdgeInsets.symmetric(vertical: 14),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(16),
            ),
          ),
        ),
        const SizedBox(height: 20),
      ],
    );
  }
}

class _ResultSection extends StatelessWidget {
  const _ResultSection({
    required this.title,
    this.body,
    this.child,
    this.initiallyExpanded = true,
  });

  final String title;
  final String? body;
  final Widget? child;
  final bool initiallyExpanded;

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final content = child ??
        SelectableText(
          body ?? '',
          style: TextStyle(
            color: fira.onSurface,
            height: 1.5,
          ),
        );

    return Card(
      elevation: 0,
      color: fira.card,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(22),
        side: BorderSide(color: fira.brandTint),
      ),
      clipBehavior: Clip.antiAlias,
      child: initiallyExpanded
          ? Padding(
              padding: const EdgeInsets.all(18),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.w800,
                          color: fira.onSurface,
                        ),
                  ),
                  const SizedBox(height: 12),
                  content,
                ],
              ),
            )
          : ExpansionTile(
              title: Text(
                title,
                style: TextStyle(
                  fontWeight: FontWeight.w800,
                  color: fira.onSurface,
                ),
              ),
              childrenPadding: const EdgeInsets.fromLTRB(18, 0, 18, 18),
              children: [
                Align(alignment: Alignment.centerLeft, child: content),
              ],
            ),
    );
  }
}
