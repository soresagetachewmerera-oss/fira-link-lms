import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../models/deal_room_model.dart';
import '../../services/deal_room_service.dart';
import '../../services/upload_file.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';

class DisputeResolutionScreen extends StatefulWidget {
  final DealRoomModel dealRoom;
  final DealRoomService? service;

  const DisputeResolutionScreen({
    super.key,
    required this.dealRoom,
    this.service,
  });

  @override
  State<DisputeResolutionScreen> createState() => _DisputeResolutionScreenState();
}

class _DisputeResolutionScreenState extends State<DisputeResolutionScreen> {
  late final DealRoomService _service = widget.service ?? DealRoomService();
  final _reasonController = TextEditingController();
  final _detailsController = TextEditingController();
  UploadFile? _evidenceFile;
  bool _isSubmitting = false;

  @override
  void dispose() {
    _reasonController.dispose();
    _detailsController.dispose();
    super.dispose();
  }

  Future<void> _pickEvidence() async {
    final file = await UploadFile.pickDocument();
    if (file == null || !mounted) return;
    final rejection = file.localRejection;
    if (rejection != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(FiraErrorText.fileRejection(context, rejection))),
      );
      return;
    }
    setState(() => _evidenceFile = file);
  }

  Future<void> _submitDispute() async {
    final l10n = context.l10n;
    final reason = _reasonController.text.trim();
    final details = _detailsController.text.trim();

    if (reason.isEmpty || details.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(l10n.dealDisputeFieldsRequired)),
      );
      return;
    }

    setState(() => _isSubmitting = true);
    final res = await _service.raiseDispute(
      dealRoomId: widget.dealRoom.id,
      reason: reason,
      details: details,
      evidence: _evidenceFile,
    );

    if (!mounted) return;
    setState(() => _isSubmitting = false);

    if (res['success'] == true) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(l10n.dealDisputeRegistered),
          backgroundColor: context.fira.danger,
        ),
      );
      Navigator.pop(context, true);
    } else {
      showFiraActionError(context, res['error'] ?? Exception());
    }
  }

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final l10n = context.l10n;

    return Scaffold(
      backgroundColor: fira.surface,
      appBar: AppBar(
        title: Text(
          l10n.dealDisputeTitle,
          style: TextStyle(color: fira.onSurface, fontWeight: FontWeight.bold, fontSize: 16),
        ),
        backgroundColor: fira.surface,
        elevation: 0,
        leading: IconButton(
          tooltip: MaterialLocalizations.of(context).backButtonTooltip,
          icon: Icon(Icons.arrow_back, color: fira.onSurface),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: fira.brandTint,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: fira.danger.withValues(alpha: 0.3)),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Icon(Icons.gavel_rounded, color: fira.danger, size: 28),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            l10n.dealDisputeNoticeTitle,
                            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: fira.danger),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            l10n.dealDisputeNoticeBody,
                            style: TextStyle(fontSize: 12, color: fira.onSurface, height: 1.3),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),
              Text(
                l10n.dealDisputeReasonLabel,
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: fira.onSurface),
              ),
              const SizedBox(height: 6),
              TextField(
                key: const Key('dispute-reason-input'),
                controller: _reasonController,
                decoration: InputDecoration(
                  hintText: l10n.dealDisputeReasonHint,
                  hintStyle: TextStyle(color: fira.mutedText, fontSize: 13),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(16)),
                ),
              ),
              const SizedBox(height: 18),
              Text(
                l10n.dealDisputeDetailsLabel,
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: fira.onSurface),
              ),
              const SizedBox(height: 6),
              TextField(
                key: const Key('dispute-details-input'),
                controller: _detailsController,
                maxLines: 4,
                decoration: InputDecoration(
                  hintText: l10n.dealDisputeDetailsHint,
                  hintStyle: TextStyle(color: fira.mutedText, fontSize: 13),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(16)),
                ),
              ),
              const SizedBox(height: 18),
              Text(
                l10n.dealDisputeEvidenceLabel,
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: fira.onSurface),
              ),
              const SizedBox(height: 6),
              OutlinedButton.icon(
                key: const Key('dispute-attach-evidence'),
                style: OutlinedButton.styleFrom(
                  minimumSize: const Size.fromHeight(48),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                ),
                icon: Icon(Icons.attach_file, color: fira.brandPrimary),
                label: Text(
                  _evidenceFile != null ? _evidenceFile!.name : l10n.dealDisputeEvidenceAttach,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(color: fira.onSurface),
                ),
                onPressed: _pickEvidence,
              ),
              const SizedBox(height: 32),
              FilledButton(
                key: const Key('dispute-submit-btn'),
                style: FilledButton.styleFrom(
                  backgroundColor: fira.danger,
                  foregroundColor: Colors.white,
                  minimumSize: const Size.fromHeight(50),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                ),
                onPressed: _isSubmitting ? null : _submitDispute,
                child: _isSubmitting
                    ? const SizedBox(
                        width: 22,
                        height: 22,
                        child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                      )
                    : Text(l10n.dealDisputeSubmit, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
