import 'package:flutter/material.dart';
import '../../l10n/app_localizations.dart';
import '../../models/deal_room_model.dart';
import '../../services/deal_room_service.dart';
import '../../services/upload_file.dart';
import '../../widgets/fira_error_state.dart';

class PreshipmentInspectionScreen extends StatefulWidget {
  final DealRoomModel dealRoom;
  final DealMilestoneModel milestone;

  const PreshipmentInspectionScreen({
    super.key,
    required this.dealRoom,
    required this.milestone,
  });

  @override
  State<PreshipmentInspectionScreen> createState() =>
      _PreshipmentInspectionScreenState();
}

class _PreshipmentInspectionScreenState
    extends State<PreshipmentInspectionScreen> {
  final DealRoomService _service = DealRoomService();
  final _surveyorController = TextEditingController(text: 'ECTA Coffee Quality Inspection Center');
  final _notesController = TextEditingController();
  UploadFile? _inspectionFile;
  bool _isUpdating = false;

  @override
  void dispose() {
    _surveyorController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _pickInspection() async {
    final file = await UploadFile.pickDocument();
    if (file == null || !mounted) return;
    final rejection = file.localRejection;
    if (rejection != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(FiraErrorText.fileRejection(context, rejection))),
      );
      return;
    }
    setState(() => _inspectionFile = file);
  }

  /// Submits the inspection report. Fira Link confirms the step after review.
  Future<void> _submitMilestone() async {
    setState(() => _isUpdating = true);
    final combinedNotes = 'Surveyor: ${_surveyorController.text.trim()} | Notes: ${_notesController.text.trim()}';

    final res = await _service.advanceMilestone(
      dealRoomId: widget.dealRoom.id,
      milestoneId: widget.milestone.id,
      notes: combinedNotes,
      document: _inspectionFile,
    );

    if (!mounted) return;
    setState(() => _isUpdating = false);

    if (res['success'] == true) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppLocalizations.of(context).proofSubmittedPendingReview)),
      );
      Navigator.pop(context);
    } else {
      showFiraActionError(context, res['error'] ?? Exception());
    }
  }

  @override
  Widget build(BuildContext context) {
    const primaryColor = Color(0xFF6D1A95);

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text(
          'Pre-Shipment Inspection',
          style: TextStyle(color: Color(0xFF1E293B), fontWeight: FontWeight.bold, fontSize: 16),
        ),
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF1E293B)),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.green.shade50,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: Colors.green.shade200),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Origin Quality & Weight Survey',
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Colors.green),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'ECTA grading certificate, moisture verification, and container stuffing report.',
                      style: TextStyle(fontSize: 12, color: Colors.green.shade900, height: 1.3),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),

              const Text('Inspection Body / Surveyor *', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
              const SizedBox(height: 6),
              TextField(
                controller: _surveyorController,
                decoration: InputDecoration(
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 18),

              const Text('Inspection Certificate / Survey Report', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
              const SizedBox(height: 6),
              OutlinedButton.icon(
                style: OutlinedButton.styleFrom(
                  minimumSize: const Size.fromHeight(48),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                icon: const Icon(Icons.verified_outlined),
                label: Text(
                  _inspectionFile != null
                      ? _inspectionFile!.name
                      : 'Attach ECTA / SGS Certificate (PDF)',
                  overflow: TextOverflow.ellipsis,
                ),
                onPressed: _pickInspection,
              ),

              const Spacer(),

              ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: primaryColor,
                  foregroundColor: Colors.white,
                  minimumSize: const Size.fromHeight(50),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
                onPressed: _isUpdating ? null : _submitMilestone,
                child: _isUpdating
                    ? const SizedBox(
                        width: 22,
                        height: 22,
                        child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                      )
                    : const Text('Pass Inspection & Advance', style: TextStyle(fontWeight: FontWeight.bold)),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

