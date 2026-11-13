import 'dart:io';

import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:permission_handler/permission_handler.dart';

import '../../models/document_analysis_result.dart';
import '../../services/document_scanner_service.dart';

class FiraDocumentScannerScreen extends StatefulWidget {
  const FiraDocumentScannerScreen({super.key, this.initialDocumentText});

  // Kept for route compatibility. Analysis only accepts a real document.
  final String? initialDocumentText;

  @override
  State<FiraDocumentScannerScreen> createState() =>
      _FiraDocumentScannerScreenState();
}

class _FiraDocumentScannerScreenState extends State<FiraDocumentScannerScreen> {
  static const _brand = Color(0xFF6D1A95);
  static const _maxBytes = 20 * 1024 * 1024;

  final _imagePicker = ImagePicker();
  final _service = DocumentScannerService();

  File? _file;
  String? _fileName;
  int? _fileSize;
  bool _processing = false;
  String? _error;
  DocumentAnalysisResult? _result;

  Future<void> _chooseSource() async {
    final source = await showModalBottomSheet<String>(
      context: context,
      showDragHandle: true,
      builder: (context) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                'Add a document',
                style: Theme.of(
                  context,
                ).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: 16),
              FilledButton.icon(
                onPressed: () => Navigator.pop(context, 'camera'),
                icon: const Icon(Icons.document_scanner_outlined),
                label: const Text('Scan with camera'),
              ),
              const SizedBox(height: 10),
              OutlinedButton.icon(
                onPressed: () => Navigator.pop(context, 'upload'),
                icon: const Icon(Icons.upload_file_outlined),
                label: const Text('Upload document'),
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
    final permission = await Permission.camera.request();
    if (!permission.isGranted) {
      if (!mounted) return;
      setState(
        () => _error = permission.isPermanentlyDenied
            ? 'Camera permission is disabled. Enable it in Settings to scan.'
            : 'Camera permission is required to scan a document.',
      );
      return;
    }
    final image = await _imagePicker.pickImage(
      source: ImageSource.camera,
      imageQuality: 92,
      maxWidth: 2400,
      maxHeight: 3200,
    );
    if (image != null) {
      await _selectFile(File(image.path), image.name);
    }
  }

  Future<void> _uploadDocument() async {
    final picked = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: const ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'txt'],
      allowMultiple: false,
    );
    final item = picked?.files.single;
    if (item?.path != null) {
      await _selectFile(File(item!.path!), item.name);
    }
  }

  Future<void> _selectFile(File file, String name) async {
    final size = await file.length();
    if (!mounted) return;
    if (size <= 0 || size > _maxBytes) {
      setState(
        () => _error = size > _maxBytes
            ? 'This document is larger than 20 MB. Choose a smaller file.'
            : 'This document is empty and cannot be processed.',
      );
      return;
    }
    setState(() {
      _file = file;
      _fileName = name;
      _fileSize = size;
      _result = null;
      _error = null;
    });
    await _process();
  }

  Future<void> _process() async {
    if (_file == null || _fileName == null) return;
    setState(() {
      _processing = true;
      _error = null;
    });
    try {
      final result = await _service.analyzeDocument(
        file: _file!,
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
      _file = null;
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
        title: const Text('Document Scanner'),
        actions: [
          if (_file != null)
            TextButton(
              onPressed: _processing ? null : _reset,
              child: const Text('New'),
            ),
        ],
      ),
      body: SafeArea(
        child: _processing
            ? _ProcessingView(fileName: _fileName ?? 'document')
            : _result != null
            ? _ResultsView(result: _result!, onAnother: _reset)
            : _StartView(
                fileName: _fileName,
                fileSize: _fileSize,
                error: _error,
                onChoose: _chooseSource,
                onRetry: _file == null ? null : _process,
              ),
      ),
    );
  }
}

class _StartView extends StatelessWidget {
  const _StartView({
    required this.fileName,
    required this.fileSize,
    required this.error,
    required this.onChoose,
    required this.onRetry,
  });

  final String? fileName;
  final int? fileSize;
  final String? error;
  final VoidCallback onChoose;
  final VoidCallback? onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 560),
          child: Column(
            children: [
              Container(
                width: 76,
                height: 76,
                decoration: BoxDecoration(
                  color: _FiraDocumentScannerScreenState._brand.withValues(
                    alpha: .1,
                  ),
                  borderRadius: BorderRadius.circular(22),
                ),
                child: const Icon(
                  Icons.document_scanner_outlined,
                  size: 38,
                  color: _FiraDocumentScannerScreenState._brand,
                ),
              ),
              const SizedBox(height: 24),
              Text(
                'Scan and understand any document',
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                  fontWeight: FontWeight.w800,
                ),
              ),
              const SizedBox(height: 10),
              Text(
                'Fira AI extracts the real content, analyzes it, and gives you a concise summary.',
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                ),
              ),
              if (fileName != null) ...[
                const SizedBox(height: 24),
                ListTile(
                  shape: RoundedRectangleBorder(
                    side: BorderSide(color: Theme.of(context).dividerColor),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  leading: const Icon(Icons.description_outlined),
                  title: Text(
                    fileName!,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  subtitle: Text(
                    '${((fileSize ?? 0) / 1024 / 1024).toStringAsFixed(1)} MB',
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
                  child: Text(
                    error!,
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.onErrorContainer,
                    ),
                  ),
                ),
              ],
              const SizedBox(height: 28),
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  onPressed: onChoose,
                  icon: const Icon(Icons.add_a_photo_outlined),
                  label: const Text('Scan Document / Upload Document'),
                  style: FilledButton.styleFrom(
                    backgroundColor: _FiraDocumentScannerScreenState._brand,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                  ),
                ),
              ),
              if (onRetry != null) ...[
                const SizedBox(height: 8),
                TextButton(onPressed: onRetry, child: const Text('Try again')),
              ],
              const SizedBox(height: 12),
              Text(
                'PDF, JPG, PNG, WEBP, or TXT · Maximum 20 MB',
                style: Theme.of(context).textTheme.bodySmall,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ProcessingView extends StatelessWidget {
  const _ProcessingView({required this.fileName});
  final String fileName;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const CircularProgressIndicator(
              color: _FiraDocumentScannerScreenState._brand,
            ),
            const SizedBox(height: 24),
            Text(
              'Reading your document…',
              style: Theme.of(
                context,
              ).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 8),
            Text(fileName, textAlign: TextAlign.center),
            const SizedBox(height: 8),
            Text(
              'Extracting content and preparing an accurate analysis.',
              textAlign: TextAlign.center,
              style: TextStyle(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ResultsView extends StatelessWidget {
  const _ResultsView({required this.result, required this.onAnother});
  final DocumentAnalysisResult result;
  final VoidCallback onAnother;

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(20),
      children: [
        Text(
          result.documentType,
          style: Theme.of(
            context,
          ).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w800),
        ),
        if (result.fileName.isNotEmpty) ...[
          const SizedBox(height: 4),
          Text(
            result.fileName,
            style: TextStyle(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
            ),
          ),
        ],
        const SizedBox(height: 20),
        _ResultSection(title: 'Summary', body: result.summary),
        const SizedBox(height: 14),
        if (result.keyFindings.isNotEmpty)
          _ResultSection(
            title: 'Key details and findings',
            child: Column(
              children: result.keyFindings
                  .map(
                    (finding) => Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Padding(
                            padding: EdgeInsets.only(top: 7),
                            child: CircleAvatar(
                              radius: 3,
                              backgroundColor:
                                  _FiraDocumentScannerScreenState._brand,
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(child: Text(finding)),
                        ],
                      ),
                    ),
                  )
                  .toList(),
            ),
          ),
        if (result.keyFindings.isNotEmpty) const SizedBox(height: 14),
        _ResultSection(title: 'Analysis', body: result.analysis),
        const SizedBox(height: 14),
        _ResultSection(
          title: 'Extracted content',
          body: result.extractedText,
          initiallyExpanded: false,
        ),
        const SizedBox(height: 24),
        OutlinedButton.icon(
          onPressed: onAnother,
          icon: const Icon(Icons.refresh),
          label: const Text('Scan or upload another document'),
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
    final content = child ?? SelectableText(body ?? '');
    return Card(
      elevation: 0,
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
                style: const TextStyle(fontWeight: FontWeight.w800),
              ),
              childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 18),
              children: [
                Align(alignment: Alignment.centerLeft, child: content),
              ],
            ),
    );
  }
}
