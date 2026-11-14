import 'package:cunning_document_scanner/cunning_document_scanner.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart' show XFile;

import '../../l10n/app_localizations.dart';
import '../../models/verification_model.dart';
import '../../services/api_exception.dart';
import '../../services/upload_file.dart';
import '../../services/verification_service.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/auth/fresh_auth_sheet.dart';
import '../../widgets/fira_error_state.dart';
import '../../widgets/verification_labels.dart';

typedef ScanPages = Future<List<UploadFile>?> Function(int maxPages);
typedef PickFile = Future<UploadFile?> Function();

/// Sends the captured document. Defaults to the verification dossier upload; lot lab reports
/// pass their own.
typedef DocumentUpload = Future<http.Response> Function({
  required List<UploadFile> pages,
  String? documentNumber,
  DateTime? issuedAt,
  DateTime? expiresAt,
  Map<String, String> details,
});

/// Capture one verification document: camera scanner (edges detected) or a PDF/image file,
/// then the number, dates and details the document type needs. Returns true when sent.
class DocumentCaptureScreen extends StatefulWidget {
  const DocumentCaptureScreen({
    super.key,
    required this.document,
    required this.expectedReviewDays,
    this.service,
    this.scanPages,
    this.pickFile,
    this.upload,
  });

  final DossierDocument document;
  final int expectedReviewDays;
  final VerificationService? service;
  final ScanPages? scanPages;
  final PickFile? pickFile;
  final DocumentUpload? upload;

  @override
  State<DocumentCaptureScreen> createState() => _DocumentCaptureScreenState();
}

class _DocumentCaptureScreenState extends State<DocumentCaptureScreen> {
  late final VerificationService _service = widget.service ?? VerificationService();
  final _formKey = GlobalKey<FormState>();
  final _number = TextEditingController();
  late final Map<String, TextEditingController> _fields = {
    for (final f in widget.document.fields) f: TextEditingController(),
  };
  final List<UploadFile> _pages = [];
  DateTime? _issuedAt;
  DateTime? _expiresAt;
  bool _sending = false;
  String? _pageError;

  DossierDocument get _doc => widget.document;
  bool get _canScan => !kIsWeb;

  @override
  void dispose() {
    _number.dispose();
    for (final c in _fields.values) {
      c.dispose();
    }
    super.dispose();
  }

  /// Pages are read into memory at once and the scanner's files are deleted from the phone,
  /// whether the user finished, cancelled or the read failed.
  static Future<List<UploadFile>?> _defaultScan(int maxPages) async {
    try {
      final result = await CunningDocumentScanner.scanDocuments(
        noOfPages: maxPages,
        scannerSource: ScannerSource.camera,
        output: ScanOutput.images,
      );
      if (result == null) return null;
      final pages = <UploadFile>[];
      for (final path in result.images) {
        final file = XFile(path);
        pages.add(UploadFile(name: file.name, bytes: await file.readAsBytes()));
      }
      return pages;
    } finally {
      try {
        await CunningDocumentScanner.cleanCache();
      } catch (_) {
        // Nothing was written, or the cache is already gone.
      }
    }
  }

  /// file_picker keeps a cached copy of the picked file; it is deleted once the bytes are in memory.
  static Future<UploadFile?> _defaultPick() async {
    try {
      return await UploadFile.pickDocument();
    } finally {
      try {
        await FilePicker.platform.clearTemporaryFiles();
      } catch (_) {
        // Not supported on this platform (web keeps nothing on disk).
      }
    }
  }

  Future<void> _scan() async {
    final remaining = _doc.maxPages - _pages.length;
    if (remaining <= 0) return;
    try {
      final scanned = await (widget.scanPages ?? _defaultScan)(remaining);
      if (scanned == null || !mounted) return;
      _addPages(scanned);
    } on CunningDocumentScannerException catch (e) {
      if (!mounted) return;
      final l10n = context.l10n;
      final text = e.code == 'permission_denied' ? l10n.captureCameraDenied : l10n.captureScanUnavailable;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(text)));
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(context.l10n.captureScanUnavailable)));
    }
  }

  Future<void> _pickFile() async {
    final file = await (widget.pickFile ?? _defaultPick)();
    if (file == null || !mounted) return;
    _addPages([file]);
  }

  void _addPages(List<UploadFile> files) {
    for (final f in files) {
      final rejection = f.localRejection;
      if (rejection != null) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(FiraErrorText.fileRejection(context, rejection))));
        return;
      }
    }
    setState(() {
      _pages.addAll(files.take(_doc.maxPages - _pages.length));
      _pageError = null;
    });
  }

  Future<void> _chooseDate({required bool expiry}) async {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final picked = await showDatePicker(
      context: context,
      initialDate: expiry ? (_expiresAt ?? today.add(const Duration(days: 365))) : (_issuedAt ?? today),
      firstDate: expiry ? today.add(const Duration(days: 1)) : DateTime(1970),
      lastDate: expiry ? DateTime(now.year + 30) : today,
    );
    if (picked == null) return;
    setState(() => expiry ? _expiresAt = picked : _issuedAt = picked);
  }

  Future<void> _send() async {
    final l10n = context.l10n;
    final formOk = _formKey.currentState?.validate() ?? false;
    String? pageError;
    if (_pages.length < _doc.minPages) {
      pageError = l10n.translate('captureMinPages', {'n': _doc.minPages});
    }
    setState(() => _pageError = pageError);
    if (!formOk || pageError != null) return;

    setState(() => _sending = true);
    try {
      final res = await FreshAuthSheet.run(
        context,
        () {
          final details = {for (final e in _fields.entries) e.key: e.value.text.trim()};
          final number = _doc.requiresNumber ? _number.text.trim() : null;
          final expires = _doc.requiresExpiry ? _expiresAt : null;
          final upload = widget.upload;
          return upload != null
              ? upload(pages: _pages, documentNumber: number, issuedAt: _issuedAt, expiresAt: expires, details: details)
              : _service.uploadDocument(
                  documentType: _doc.type,
                  pages: _pages,
                  documentNumber: number,
                  issuedAt: _issuedAt,
                  expiresAt: expires,
                  details: details,
                );
        },
      );
      requireSuccess(res);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(l10n.translate('captureSent', {'days': widget.expectedReviewDays}))),
      );
      Navigator.of(context).pop(true);
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;
    final title = VerificationLabels.docType(l10n, _doc.type);
    final canAddMore = _pages.length < _doc.maxPages;

    return Scaffold(
      appBar: AppBar(title: Text(l10n.captureTitle)),
      body: SafeArea(
        child: Form(
          key: _formKey,
          child: ListView(
            padding: const EdgeInsets.all(20),
            children: [
              Text(title, style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700)),
              const SizedBox(height: 16),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(l10n.captureInstructionsTitle, style: const TextStyle(fontWeight: FontWeight.w700)),
                      const SizedBox(height: 6),
                      Text(l10n.captureInstructions, style: TextStyle(color: fira.mutedText)),
                      if (_doc.cameraOnly) ...[
                        const SizedBox(height: 8),
                        Text(l10n.captureCameraOnly, style: TextStyle(color: fira.pending, fontWeight: FontWeight.w600)),
                      ],
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Wrap(
                spacing: 12,
                runSpacing: 12,
                children: [
                  if (_canScan)
                    FilledButton.icon(
                      onPressed: canAddMore && !_sending ? _scan : null,
                      icon: const Icon(Icons.document_scanner_outlined),
                      label: Text(l10n.captureScan),
                    ),
                  if (!_doc.cameraOnly)
                    OutlinedButton.icon(
                      onPressed: canAddMore && !_sending ? _pickFile : null,
                      icon: const Icon(Icons.folder_open_outlined),
                      label: Text(l10n.captureFiles),
                    ),
                ],
              ),
              const SizedBox(height: 16),
              Text(l10n.translate('capturePages', {'count': _pages.length}), style: const TextStyle(fontWeight: FontWeight.w700)),
              for (var i = 0; i < _pages.length; i++)
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: Icon(_pages[i].name.toLowerCase().endsWith('.pdf') ? Icons.picture_as_pdf_outlined : Icons.image_outlined, color: fira.brandPrimary),
                  title: Text(l10n.translate('capturePage', {'n': i + 1})),
                  subtitle: Text(_pages[i].name, maxLines: 1, overflow: TextOverflow.ellipsis),
                  trailing: IconButton(
                    tooltip: l10n.captureRemovePage,
                    icon: const Icon(Icons.close),
                    onPressed: _sending ? null : () => setState(() => _pages.removeAt(i)),
                  ),
                ),
              if (!canAddMore)
                Text(l10n.translate('captureMaxPages', {'n': _doc.maxPages}), style: TextStyle(color: fira.mutedText)),
              if (_pageError != null)
                Padding(
                  padding: const EdgeInsets.only(top: 4),
                  child: Text(_pageError!, style: TextStyle(color: fira.danger)),
                ),
              const SizedBox(height: 16),
              if (_doc.requiresNumber) ...[
                TextFormField(
                  key: const Key('capture-number'),
                  controller: _number,
                  decoration: InputDecoration(labelText: l10n.captureNumber),
                  maxLength: 80,
                  textInputAction: TextInputAction.next,
                  validator: (v) => (v == null || v.trim().isEmpty) ? l10n.captureRequired : null,
                ),
                const SizedBox(height: 8),
              ],
              for (final e in _fields.entries) ...[
                TextFormField(
                  key: Key('capture-field-${e.key}'),
                  controller: e.value,
                  decoration: InputDecoration(labelText: VerificationLabels.field(l10n, e.key)),
                  maxLength: 255,
                  validator: (v) => (v == null || v.trim().isEmpty) ? l10n.captureRequired : null,
                ),
                const SizedBox(height: 8),
              ],
              _DateField(
                label: l10n.captureIssued,
                value: _issuedAt,
                onTap: _sending ? null : () => _chooseDate(expiry: false),
              ),
              if (_doc.requiresExpiry) ...[
                const SizedBox(height: 12),
                FormField<DateTime>(
                  validator: (_) => _expiresAt == null ? l10n.captureExpiryFuture : null,
                  builder: (state) => _DateField(
                    key: const Key('capture-expiry'),
                    label: l10n.captureExpires,
                    value: _expiresAt,
                    errorText: state.errorText,
                    onTap: _sending ? null : () => _chooseDate(expiry: true).then((_) => state.didChange(_expiresAt)),
                  ),
                ),
              ],
              const SizedBox(height: 20),
              Text(l10n.capturePrivacy, style: TextStyle(color: fira.mutedText)),
              const SizedBox(height: 16),
              FilledButton(
                key: const Key('capture-send'),
                onPressed: _sending ? null : _send,
                child: _sending
                    ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2))
                    : Text(l10n.captureSubmit),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _DateField extends StatelessWidget {
  const _DateField({super.key, required this.label, required this.value, required this.onTap, this.errorText});

  final String label;
  final DateTime? value;
  final VoidCallback? onTap;
  final String? errorText;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      child: InputDecorator(
        decoration: InputDecoration(
          labelText: label,
          errorText: errorText,
          suffixIcon: const Icon(Icons.calendar_today_outlined),
        ),
        child: Text(value == null ? context.l10n.captureChooseDate : VerificationLabels.date(context, value)),
      ),
    );
  }
}
