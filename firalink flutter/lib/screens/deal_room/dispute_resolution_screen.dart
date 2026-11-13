import 'dart:io';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import '../../models/deal_room_model.dart';
import '../../services/deal_room_service.dart';

class DisputeResolutionScreen extends StatefulWidget {
  final DealRoomModel dealRoom;

  const DisputeResolutionScreen({super.key, required this.dealRoom});

  @override
  State<DisputeResolutionScreen> createState() =>
      _DisputeResolutionScreenState();
}

class _DisputeResolutionScreenState extends State<DisputeResolutionScreen> {
  final DealRoomService _service = DealRoomService();
  final _reasonController = TextEditingController();
  final _detailsController = TextEditingController();
  File? _evidenceFile;
  bool _isSubmitting = false;

  @override
  void dispose() {
    _reasonController.dispose();
    _detailsController.dispose();
    super.dispose();
  }

  Future<void> _pickEvidence() async {
    final res = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: ['pdf', 'jpg', 'jpeg', 'png'],
    );
    if (res != null && res.files.single.path != null) {
      setState(() {
        _evidenceFile = File(res.files.single.path!);
      });
    }
  }

  Future<void> _submitDispute() async {
    final reason = _reasonController.text.trim();
    final details = _detailsController.text.trim();

    if (reason.isEmpty || details.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please provide both reason and explanation')),
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
        const SnackBar(
          content: Text('Dispute formally registered. Arbitrators & ECTA notified.'),
          backgroundColor: Colors.redAccent,
        ),
      );
      Navigator.pop(context);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message'] ?? 'Failed to register dispute')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text(
          'Raise Formal Dispute',
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
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.red.shade50,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: Colors.red.shade200),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.gavel, color: Colors.redAccent, size: 28),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Arbitration & Retained Sample Protocol',
                            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Colors.red),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            'Disputes freeze milestone disbursements. The sealed twin sample and inspection reports will be submitted for independent cupping verification.',
                            style: TextStyle(fontSize: 11, color: Colors.red.shade900, height: 1.3),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),

              const Text('Dispute Reason / Claim *', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
              const SizedBox(height: 6),
              TextField(
                controller: _reasonController,
                decoration: InputDecoration(
                  hintText: 'e.g. Cupping score mismatch, excess moisture (>12.5%), delayed shipment',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 18),

              const Text('Detailed Factual Explanation *', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
              const SizedBox(height: 6),
              TextField(
                controller: _detailsController,
                maxLines: 4,
                decoration: InputDecoration(
                  hintText: 'Detail container arrival date, seal numbers observed, laboratory test results...',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 18),

              const Text('Evidence & Third-Party Survey Report (PDF/Photo)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
              const SizedBox(height: 6),
              OutlinedButton.icon(
                style: OutlinedButton.styleFrom(
                  minimumSize: const Size.fromHeight(48),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                icon: const Icon(Icons.attach_file),
                label: Text(
                  _evidenceFile != null
                      ? _evidenceFile!.path.split(Platform.pathSeparator).last
                      : 'Attach Lab Test or Survey Proof',
                  overflow: TextOverflow.ellipsis,
                ),
                onPressed: _pickEvidence,
              ),
              const SizedBox(height: 32),

              ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.redAccent,
                  foregroundColor: Colors.white,
                  minimumSize: const Size.fromHeight(50),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
                onPressed: _isSubmitting ? null : _submitDispute,
                child: _isSubmitting
                    ? const SizedBox(
                        width: 22,
                        height: 22,
                        child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                      )
                    : const Text('Submit Formal Dispute', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

