import 'dart:io';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import '../../services/deal_room_service.dart';
import '../../services/sample_service.dart';
import '../deal_room/deal_room_hub_screen.dart';

class SampleCuppingApprovalScreen extends StatefulWidget {
  final int sampleId;

  const SampleCuppingApprovalScreen({super.key, required this.sampleId});

  @override
  State<SampleCuppingApprovalScreen> createState() =>
      _SampleCuppingApprovalScreenState();
}

class _SampleCuppingApprovalScreenState
    extends State<SampleCuppingApprovalScreen> {
  final SampleService _sampleService = SampleService();
  final DealRoomService _dealRoomService = DealRoomService();

  String _decision = 'approved';
  final _scoreController = TextEditingController(text: '87.5');
  final _notesController = TextEditingController();
  File? _labReportFile;
  bool _isSubmitting = false;

  @override
  void dispose() {
    _scoreController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _pickReport() async {
    final result = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: ['pdf', 'jpg', 'jpeg', 'png'],
    );
    if (result != null && result.files.single.path != null) {
      setState(() {
        _labReportFile = File(result.files.single.path!);
      });
    }
  }

  Future<void> _submitEvaluation() async {
    final score = double.tryParse(_scoreController.text.trim()) ?? 80.0;
    setState(() => _isSubmitting = true);

    final res = await _sampleService.evaluateSample(
      sampleId: widget.sampleId,
      decision: _decision,
      cuppingScore: score,
      notes: _notesController.text.trim(),
      labReport: _labReportFile,
    );

    if (!mounted) return;
    setState(() => _isSubmitting = false);

    if (res['success'] == true) {
      if (_decision == 'approved') {
        final openDeal = await showDialog<bool>(
          context: context,
          builder: (ctx) => AlertDialog(
            title: const Text('Sample Approved!'),
            content: const Text('Would you like to open a Deal Room for this lot now?'),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(ctx, false),
                child: const Text('Later'),
              ),
              ElevatedButton(
                style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF6D1A95)),
                onPressed: () => Navigator.pop(ctx, true),
                child: const Text('Open Deal Room'),
              ),
            ],
          ),
        );

        if (openDeal == true && mounted) {
          final dealRes = await _dealRoomService.openDealRoom(
            title: 'Deal Room: Approved Sample',
            targetValueUsd: 65000.0,
          );
          if (dealRes['success'] == true && mounted) {
            Navigator.pushReplacement(
              context,
              MaterialPageRoute(
                builder: (_) => DealRoomHubScreen(dealRoomId: dealRes['deal_room'].id),
              ),
            );
            return;
          }
        }
      }

      Navigator.pop(context);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message'] ?? 'Evaluation submission failed')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text(
          'Sample Cupping Evaluation',
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
              // Decision selector
              const Text('Evaluation Decision *', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
              const SizedBox(height: 8),
              Row(
                children: [
                  Expanded(
                    child: InkWell(
                      onTap: () => setState(() => _decision = 'approved'),
                      borderRadius: BorderRadius.circular(12),
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        decoration: BoxDecoration(
                          border: Border.all(
                            color: _decision == 'approved' ? Colors.green : Colors.grey.shade300,
                            width: _decision == 'approved' ? 2 : 1,
                          ),
                          borderRadius: BorderRadius.circular(12),
                          color: _decision == 'approved' ? Colors.green.shade50 : Colors.white,
                        ),
                        child: Column(
                          children: [
                            Icon(Icons.check_circle, color: _decision == 'approved' ? Colors.green : Colors.grey),
                            const SizedBox(height: 4),
                            Text(
                              'APPROVE',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                color: _decision == 'approved' ? Colors.green.shade900 : Colors.grey,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: InkWell(
                      onTap: () => setState(() => _decision = 'rejected'),
                      borderRadius: BorderRadius.circular(12),
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        decoration: BoxDecoration(
                          border: Border.all(
                            color: _decision == 'rejected' ? Colors.red : Colors.grey.shade300,
                            width: _decision == 'rejected' ? 2 : 1,
                          ),
                          borderRadius: BorderRadius.circular(12),
                          color: _decision == 'rejected' ? Colors.red.shade50 : Colors.white,
                        ),
                        child: Column(
                          children: [
                            Icon(Icons.cancel, color: _decision == 'rejected' ? Colors.red : Colors.grey),
                            const SizedBox(height: 4),
                            Text(
                              'REJECT',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                color: _decision == 'rejected' ? Colors.red.shade900 : Colors.grey,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),

              // Cupping score
              const Text('SCA Cupping Score (60.0 - 100.0) *', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
              const SizedBox(height: 6),
              TextFormField(
                controller: _scoreController,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                decoration: InputDecoration(
                  hintText: 'e.g. 88.25',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 18),

              // Cupping notes
              const Text('Sensory & Cupping Notes', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
              const SizedBox(height: 6),
              TextFormField(
                controller: _notesController,
                maxLines: 3,
                decoration: InputDecoration(
                  hintText: 'Describe jasmine aroma, bright citric acidity, peach notes, clean uniform cup...',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 18),

              // Attach Lab Report
              const Text('Accredited Lab Cupping Sheet (Optional)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
              const SizedBox(height: 6),
              OutlinedButton.icon(
                style: OutlinedButton.styleFrom(
                  minimumSize: const Size.fromHeight(48),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                icon: const Icon(Icons.attach_file),
                label: Text(
                  _labReportFile != null
                      ? _labReportFile!.path.split(Platform.pathSeparator).last
                      : 'Attach Lab Certificate / PDF',
                  overflow: TextOverflow.ellipsis,
                ),
                onPressed: _pickReport,
              ),
              const SizedBox(height: 32),

              ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: _decision == 'approved' ? Colors.green : Colors.redAccent,
                  foregroundColor: Colors.white,
                  minimumSize: const Size.fromHeight(52),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
                onPressed: _isSubmitting ? null : _submitEvaluation,
                child: _isSubmitting
                    ? const SizedBox(
                        width: 22,
                        height: 22,
                        child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                      )
                    : Text(
                        _decision == 'approved' ? 'Submit Approval & Proceed' : 'Submit Rejection Feedback',
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                      ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

