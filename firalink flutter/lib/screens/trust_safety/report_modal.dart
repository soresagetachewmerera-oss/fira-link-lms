import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../services/trust_safety_service.dart';
import '../../services/upload_file.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';

typedef PickScreenshot = Future<UploadFile?> Function();

/// Report a problem (slice 7). The server works out the reported business from what is reported
/// (message, deal room, sample, lot, business or Fira ID) and never tells them who reported.
/// Nothing is paused automatically; Fira Link decides.
class ReportModal extends StatefulWidget {
  const ReportModal({
    super.key,
    this.reportedOrganizationId,
    this.reportedListingId,
    this.reportedMessageId,
    this.reportedDealRoomId,
    this.reportedSampleId,
    this.reportedFiraId,
    this.initialReason,
    required this.targetTitle,
    this.service,
    this.pickScreenshot,
  });

  final int? reportedOrganizationId;
  final int? reportedListingId;
  final int? reportedMessageId;
  final int? reportedDealRoomId;
  final int? reportedSampleId;
  final String? reportedFiraId;
  final String? initialReason;
  final String targetTitle;
  final TrustSafetyService? service;
  final PickScreenshot? pickScreenshot;

  /// Returns true when the report was sent.
  static Future<bool?> show(
    BuildContext context, {
    int? reportedOrganizationId,
    int? reportedListingId,
    int? reportedMessageId,
    int? reportedDealRoomId,
    int? reportedSampleId,
    String? reportedFiraId,
    String? initialReason,
    required String targetTitle,
    TrustSafetyService? service,
    PickScreenshot? pickScreenshot,
  }) {
    return showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      builder: (_) => ReportModal(
        reportedOrganizationId: reportedOrganizationId,
        reportedListingId: reportedListingId,
        reportedMessageId: reportedMessageId,
        reportedDealRoomId: reportedDealRoomId,
        reportedSampleId: reportedSampleId,
        reportedFiraId: reportedFiraId,
        initialReason: initialReason,
        targetTitle: targetTitle,
        service: service,
        pickScreenshot: pickScreenshot,
      ),
    );
  }

  @override
  State<ReportModal> createState() => _ReportModalState();
}

class _ReportModalState extends State<ReportModal> {
  late final TrustSafetyService _service = widget.service ?? TrustSafetyService();
  final _details = TextEditingController();
  late String? _reason = widget.initialReason;
  UploadFile? _screenshot;
  bool _block = false;
  bool _tried = false;
  bool _sending = false;

  @override
  void dispose() {
    _details.dispose();
    super.dispose();
  }

  bool get _detailsMissing => _reason == 'other' && _details.text.trim().isEmpty;

  Future<void> _pick() async {
    final file = await (widget.pickScreenshot ?? () => UploadFile.pickDocument(extensions: const ['jpg', 'jpeg', 'png']))();
    if (file == null || !mounted) return;
    final rejection = file.localRejection;
    if (rejection != null) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(FiraErrorText.fileRejection(context, rejection))));
      return;
    }
    setState(() => _screenshot = file);
  }

  Future<void> _send() async {
    setState(() => _tried = true);
    if (_reason == null || _detailsMissing) return;
    final l10n = context.l10n;
    final messenger = ScaffoldMessenger.of(context);
    final navigator = Navigator.of(context);
    setState(() => _sending = true);
    try {
      final blocked = await _service.submitReport(
        reasonCode: _reason!,
        details: _details.text,
        reportedOrganizationId: widget.reportedOrganizationId,
        reportedListingId: widget.reportedListingId,
        reportedMessageId: widget.reportedMessageId,
        reportedDealRoomId: widget.reportedDealRoomId,
        reportedSampleId: widget.reportedSampleId,
        reportedFiraId: widget.reportedFiraId,
        block: _block,
        evidence: _screenshot,
      );
      navigator.pop(true);
      messenger
        ..hideCurrentSnackBar()
        ..showSnackBar(SnackBar(content: Text(blocked ? l10n.reportSentBlocked : l10n.reportSent)));
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

    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(l10n.reportTitle, style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700)),
            const SizedBox(height: 4),
            Text(l10n.translate('reportAbout', {'target': widget.targetTitle}), style: TextStyle(color: fira.mutedText)),
            const SizedBox(height: 16),
            Text(l10n.reportWhatHappened, style: const TextStyle(fontWeight: FontWeight.w700)),
            RadioGroup<String>(
              groupValue: _reason,
              onChanged: (v) => setState(() => _reason = v),
              child: Column(children: [
                for (final code in TrustSafetyService.reasons)
                  RadioListTile<String>(
                    key: Key('reason-$code'),
                    value: code,
                    contentPadding: EdgeInsets.zero,
                    title: Text(l10n.translate('reportReason_$code')),
                  ),
              ]),
            ),
            if (_tried && _reason == null)
              Text(l10n.reportChooseReason, key: const Key('reason-required'), style: TextStyle(color: fira.danger)),
            const SizedBox(height: 8),
            TextField(
              key: const Key('report-details'),
              controller: _details,
              maxLines: 4,
              maxLength: 5000,
              onChanged: (_) => setState(() {}),
              decoration: InputDecoration(
                labelText: l10n.reportDetails,
                hintText: l10n.reportDetailsHint,
                errorText: _tried && _detailsMissing ? l10n.reportDetailsRequired : null,
              ),
            ),
            OutlinedButton.icon(
              key: const Key('report-screenshot'),
              onPressed: _sending ? null : _pick,
              icon: Icon(_screenshot == null ? Icons.add_photo_alternate_outlined : Icons.check_circle, color: _screenshot == null ? null : fira.success),
              label: Text(_screenshot == null ? l10n.reportAddScreenshot : l10n.reportScreenshotAdded),
            ),
            const SizedBox(height: 8),
            CheckboxListTile(
              key: const Key('report-block'),
              value: _block,
              contentPadding: EdgeInsets.zero,
              controlAffinity: ListTileControlAffinity.leading,
              onChanged: (v) => setState(() => _block = v ?? false),
              title: Text(l10n.reportAlsoBlock),
              subtitle: Text(l10n.reportAlsoBlockBody),
            ),
            Card(
              color: fira.brandTint,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Text(l10n.reportNote, style: TextStyle(color: fira.onSurface)),
              ),
            ),
            const SizedBox(height: 12),
            FilledButton(
              key: const Key('report-send'),
              onPressed: _sending ? null : _send,
              child: _sending ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2)) : Text(l10n.reportSend),
            ),
          ],
        ),
      ),
    );
  }
}
