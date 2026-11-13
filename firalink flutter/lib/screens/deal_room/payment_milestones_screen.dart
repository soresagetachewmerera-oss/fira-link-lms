import 'package:flutter/material.dart';
import '../../l10n/app_localizations.dart';
import '../../models/deal_room_model.dart';
import '../../services/deal_room_service.dart';
import '../../services/upload_file.dart';
import '../../widgets/fira_error_state.dart';

class PaymentMilestonesScreen extends StatefulWidget {
  final DealRoomModel dealRoom;
  final DealMilestoneModel milestone;

  const PaymentMilestonesScreen({
    super.key,
    required this.dealRoom,
    required this.milestone,
  });

  @override
  State<PaymentMilestonesScreen> createState() =>
      _PaymentMilestonesScreenState();
}

class _PaymentMilestonesScreenState extends State<PaymentMilestonesScreen> {
  final DealRoomService _service = DealRoomService();
  final _notesController = TextEditingController();
  UploadFile? _proofFile;
  bool _isUpdating = false;

  @override
  void dispose() {
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _pickProof() async {
    final file = await UploadFile.pickDocument();
    if (file == null || !mounted) return;
    final rejection = file.localRejection;
    if (rejection != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(FiraErrorText.fileRejection(context, rejection))),
      );
      return;
    }
    setState(() => _proofFile = file);
  }

  /// Uploads the bank slip or SWIFT copy. This does not complete the step:
  /// it counts only after Fira Link confirms the payment with the bank.
  Future<void> _submitMilestone() async {
    setState(() => _isUpdating = true);
    final res = await _service.advanceMilestone(
      dealRoomId: widget.dealRoom.id,
      milestoneId: widget.milestone.id,
      notes: _notesController.text.trim(),
      document: _proofFile,
    );

    if (!mounted) return;
    setState(() => _isUpdating = false);

    if (res['success'] == true) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppLocalizations.of(context).proofSubmittedPendingBank)),
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
          'Letter of Credit / Escrow',
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
                  color: primaryColor.withValues(alpha: 0.05),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: primaryColor.withValues(alpha: 0.2)),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Documentary LC / Escrow Verification',
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: primaryColor),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'Attach swift MT700 copy, bank payment guarantee, or Chapa escrow milestone reference.',
                      style: TextStyle(fontSize: 12, color: Colors.grey.shade700, height: 1.3),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),

              const Text('Swift / Bank Reference Notes', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
              const SizedBox(height: 6),
              TextField(
                controller: _notesController,
                maxLines: 3,
                decoration: InputDecoration(
                  hintText: 'e.g. Irrevocable LC #ETB-2026-9042 advised via Commercial Bank of Ethiopia (CBE)',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 18),

              const Text('Payment Proof / Swift Copy (PDF/Image)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
              const SizedBox(height: 6),
              OutlinedButton.icon(
                style: OutlinedButton.styleFrom(
                  minimumSize: const Size.fromHeight(48),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                icon: const Icon(Icons.upload_file),
                label: Text(
                  _proofFile != null
                      ? _proofFile!.name
                      : 'Attach Swift Copy / Escrow Slip',
                  overflow: TextOverflow.ellipsis,
                ),
                onPressed: _pickProof,
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
                    : const Text('Confirm & Achieve Milestone', style: TextStyle(fontWeight: FontWeight.bold)),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

