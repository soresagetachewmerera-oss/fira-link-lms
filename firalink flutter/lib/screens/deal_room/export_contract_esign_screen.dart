import 'package:flutter/material.dart';
import '../../models/deal_room_model.dart';
import '../../services/deal_room_service.dart';
import '../../services/upload_file.dart';
import '../../widgets/fira_error_state.dart';

class ExportContractEsignScreen extends StatefulWidget {
  final DealRoomModel dealRoom;

  const ExportContractEsignScreen({super.key, required this.dealRoom});

  @override
  State<ExportContractEsignScreen> createState() =>
      _ExportContractEsignScreenState();
}

class _ExportContractEsignScreenState extends State<ExportContractEsignScreen> {
  final DealRoomService _service = DealRoomService();
  final _signatureController = TextEditingController();
  UploadFile? _contractFile;
  bool _isSigning = false;
  bool _agreedToTerms = false;

  @override
  void dispose() {
    _signatureController.dispose();
    super.dispose();
  }

  Future<void> _pickContractFile() async {
    final file = await UploadFile.pickDocument(extensions: const ['pdf']);
    if (file == null || !mounted) return;
    final rejection = file.localRejection;
    if (rejection != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(FiraErrorText.fileRejection(context, rejection))),
      );
      return;
    }
    setState(() => _contractFile = file);
  }

  Future<void> _submitSignature() async {
    final sig = _signatureController.text.trim();
    if (sig.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please enter your full legal name as electronic signature')),
      );
      return;
    }

    if (!_agreedToTerms) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please agree to the Ethiopian Coffee Export Regulatory Terms')),
      );
      return;
    }

    setState(() => _isSigning = true);
    final res = await _service.signContract(
      dealRoomId: widget.dealRoom.id,
      signature: sig,
      contractDocument: _contractFile,
    );

    if (!mounted) return;
    setState(() => _isSigning = false);

    if (res['success'] == true) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Electronic signature recorded successfully!'), backgroundColor: Colors.green),
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
          'E-Sign Export Contract',
          style: TextStyle(color: Color(0xFF1E293B), fontWeight: FontWeight.bold, fontSize: 16),
        ),
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
            tooltip: MaterialLocalizations.of(context).backButtonTooltip,
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
              // Contract Summary
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: Colors.grey.shade200),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      widget.dealRoom.title,
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'Value: \$${widget.dealRoom.targetValueUsd.toStringAsFixed(2)} USD • Incoterm: ${widget.dealRoom.incoterm}',
                      style: TextStyle(fontSize: 13, color: Colors.grey.shade700),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        _buildSignatoryBadge('Seller', widget.dealRoom.sellerSignedAt != null),
                        const SizedBox(width: 8),
                        _buildSignatoryBadge('Buyer', widget.dealRoom.buyerSignedAt != null),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),

              // Legal Terms Box
              const Text('Standard Export Agreement Terms', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
              const SizedBox(height: 8),
              Container(
                height: 140,
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: Colors.grey.shade50,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.grey.shade300),
                ),
                child: const SingleChildScrollView(
                  child: Text(
                    '1. Applicable Law: Contract governed by Ethiopian Commercial Code & ECTA Direct Coffee Export Directives.\n'
                    '2. Quality & Moisture: Max 12.0% moisture content, water activity <= 0.60, packed in GrainPro hermetic liners.\n'
                    '3. Payment Terms: Irrevocable Documentary Letter of Credit (LC) at sight or milestone settlement deposit.\n'
                    '4. Inspection: Origin quality and weight determined by ECTA Coffee Quality Inspection Center prior to container stuffing.\n'
                    '5. Retained Sample: Sealed retained twin sample is held by exporter for any quality dispute resolution.\n'
                    '6. Force Majeure: Standard ICC force majeure clause applies.',
                    style: TextStyle(fontSize: 12, height: 1.4, color: Colors.black87),
                  ),
                ),
              ),
              const SizedBox(height: 20),

              // Signatory input
              const Text('Electronic Signature (Legal Full Name) *', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
              const SizedBox(height: 6),
              TextFormField(
                controller: _signatureController,
                decoration: InputDecoration(
                  hintText: 'e.g. Abebe Bekele (Managing Director)',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 18),

              // Optional PDF upload
              const Text('Attach Executed PDF (Optional)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
              const SizedBox(height: 6),
              OutlinedButton.icon(
                style: OutlinedButton.styleFrom(
                  minimumSize: const Size.fromHeight(48),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                icon: const Icon(Icons.picture_as_pdf_outlined),
                label: Text(
                  _contractFile != null
                      ? _contractFile!.name
                      : 'Upload Scanned Contract PDF',
                  overflow: TextOverflow.ellipsis,
                ),
                onPressed: _pickContractFile,
              ),
              const SizedBox(height: 16),

              // Agreement Checkbox
              CheckboxListTile(
                contentPadding: EdgeInsets.zero,
                value: _agreedToTerms,
                activeColor: primaryColor,
                title: const Text(
                  'I declare that I am an authorized signatory and agree to the bilateral export terms.',
                  style: TextStyle(fontSize: 12),
                ),
                onChanged: (val) => setState(() => _agreedToTerms = val ?? false),
              ),
              const SizedBox(height: 24),

              ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: primaryColor,
                  foregroundColor: Colors.white,
                  minimumSize: const Size.fromHeight(50),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
                onPressed: _isSigning ? null : _submitSignature,
                child: _isSigning
                    ? const SizedBox(
                        width: 22,
                        height: 22,
                        child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                      )
                    : const Text('Execute & Apply E-Signature', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildSignatoryBadge(String label, bool signed) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: signed ? Colors.green.shade50 : Colors.amber.shade50,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(signed ? Icons.check_circle : Icons.pending, size: 14, color: signed ? Colors.green : Colors.amber.shade800),
          const SizedBox(width: 4),
          Text(
            '$label: ${signed ? 'Signed' : 'Awaiting'}',
            style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: signed ? Colors.green.shade900 : Colors.amber.shade900),
          ),
        ],
      ),
    );
  }
}

