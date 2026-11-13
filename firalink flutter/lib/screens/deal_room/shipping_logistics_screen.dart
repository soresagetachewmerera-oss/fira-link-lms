import 'dart:io';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import '../../models/deal_room_model.dart';
import '../../services/deal_room_service.dart';

class ShippingLogisticsScreen extends StatefulWidget {
  final DealRoomModel dealRoom;
  final DealMilestoneModel milestone;

  const ShippingLogisticsScreen({
    super.key,
    required this.dealRoom,
    required this.milestone,
  });

  @override
  State<ShippingLogisticsScreen> createState() =>
      _ShippingLogisticsScreenState();
}

class _ShippingLogisticsScreenState extends State<ShippingLogisticsScreen> {
  final DealRoomService _service = DealRoomService();
  final _carrierController = TextEditingController(text: 'Ethiopian Shipping and Logistics (ESLSE)');
  final _blNumberController = TextEditingController();
  File? _blFile;
  bool _isUpdating = false;

  @override
  void dispose() {
    _carrierController.dispose();
    _blNumberController.dispose();
    super.dispose();
  }

  Future<void> _pickBlFile() async {
    final res = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: ['pdf', 'jpg', 'jpeg', 'png'],
    );
    if (res != null && res.files.single.path != null) {
      setState(() {
        _blFile = File(res.files.single.path!);
      });
    }
  }

  Future<void> _submitMilestone() async {
    setState(() => _isUpdating = true);
    final combinedNotes = 'Carrier: ${_carrierController.text.trim()} | B/L No: ${_blNumberController.text.trim()}';

    final res = await _service.advanceMilestone(
      dealRoomId: widget.dealRoom.id,
      milestoneId: widget.milestone.id,
      status: 'achieved',
      notes: combinedNotes,
      document: _blFile,
    );

    if (!mounted) return;
    setState(() => _isUpdating = false);

    if (res['success'] == true) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Bill of Lading released & verified!'), backgroundColor: Colors.green),
      );
      Navigator.pop(context);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message'] ?? 'Failed to update logistics')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    const primaryColor = Color(0xFF6D1A95);

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text(
          'Shipping & Bill of Lading (B/L)',
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
                  color: Colors.blue.shade50,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: Colors.blue.shade200),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Port of Djibouti Export Release',
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Colors.blue.shade900),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'Upload the negotiated ocean Bill of Lading, Phytosanitary Certificate, and Certificate of Origin.',
                      style: TextStyle(fontSize: 12, color: Colors.blue.shade800, height: 1.3),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),

              const Text('Ocean Carrier / Line *', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
              const SizedBox(height: 6),
              TextField(
                controller: _carrierController,
                decoration: InputDecoration(
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 18),

              const Text('Bill of Lading (B/L) Reference No *', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
              const SizedBox(height: 6),
              TextField(
                controller: _blNumberController,
                decoration: InputDecoration(
                  hintText: 'e.g. ESL-DJIB-2026-8819',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 18),

              const Text('Bill of Lading & Phyto Dossier (PDF)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
              const SizedBox(height: 6),
              OutlinedButton.icon(
                style: OutlinedButton.styleFrom(
                  minimumSize: const Size.fromHeight(48),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                icon: const Icon(Icons.directions_boat_outlined),
                label: Text(
                  _blFile != null
                      ? _blFile!.path.split(Platform.pathSeparator).last
                      : 'Attach Bill of Lading PDF',
                  overflow: TextOverflow.ellipsis,
                ),
                onPressed: _pickBlFile,
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
                    : const Text('Release B/L & Advance', style: TextStyle(fontWeight: FontWeight.bold)),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

