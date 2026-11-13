import 'package:flutter/material.dart';
import '../../services/trust_safety_service.dart';

class ReportModal extends StatefulWidget {
  final int? reportedUserId;
  final int? reportedOrganizationId;
  final int? reportedListingId;
  final String targetTitle;

  const ReportModal({
    super.key,
    this.reportedUserId,
    this.reportedOrganizationId,
    this.reportedListingId,
    required this.targetTitle,
  });

  static Future<void> show(
    BuildContext context, {
    int? reportedUserId,
    int? reportedOrganizationId,
    int? reportedListingId,
    required String targetTitle,
  }) {
    return showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => ReportModal(
        reportedUserId: reportedUserId,
        reportedOrganizationId: reportedOrganizationId,
        reportedListingId: reportedListingId,
        targetTitle: targetTitle,
      ),
    );
  }

  @override
  State<ReportModal> createState() => _ReportModalState();
}

class _ReportModalState extends State<ReportModal> {
  final TrustSafetyService _service = TrustSafetyService();
  String _selectedReason = 'Off-platform payment solicitation';
  final _detailsController = TextEditingController();
  bool _isSubmitting = false;

  final List<String> _reasons = [
    'Off-platform payment solicitation (Wire/Cash request outside platform)',
    'Fraudulent / Misrepresented origin or cupping score',
    'Unlicensed entity claiming ECTA export authorization',
    'Harassment or commercial spam',
    'Refusal to honor sample or deal contract terms',
    'Other Trust & Safety violation',
  ];

  @override
  void dispose() {
    _detailsController.dispose();
    super.dispose();
  }

  Future<void> _submitReport() async {
    final details = _detailsController.text.trim();
    if (details.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please provide specific details for the report')),
      );
      return;
    }

    setState(() => _isSubmitting = true);
    final res = await _service.submitReport(
      reason: _selectedReason,
      details: details,
      reportedUserId: widget.reportedUserId,
      reportedOrganizationId: widget.reportedOrganizationId,
      reportedListingId: widget.reportedListingId,
    );

    if (!mounted) return;
    setState(() => _isSubmitting = false);

    if (res['success'] == true) {
      Navigator.pop(context);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Report submitted. Our Trust & Safety team has been notified.'),
          backgroundColor: Colors.green,
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message'] ?? 'Failed to submit report')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: EdgeInsets.only(
        left: 20,
        right: 20,
        top: 20,
        bottom: MediaQuery.of(context).viewInsets.bottom + 20,
      ),
      child: SafeArea(
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: Colors.grey.shade300,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: 18),
              Row(
                children: [
                  const Icon(Icons.shield_outlined, color: Colors.redAccent),
                  const SizedBox(width: 8),
                  const Text(
                    'Report to Trust & Safety',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF1E293B)),
                  ),
                ],
              ),
              const SizedBox(height: 4),
              Text(
                'Reporting: ${widget.targetTitle}',
                style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
              ),
              const SizedBox(height: 18),

              const Text('Violation Category *', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
              const SizedBox(height: 6),
              DropdownButtonFormField<String>(
                value: _selectedReason,
                isExpanded: true,
                decoration: InputDecoration(
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                ),
                items: _reasons
                    .map((r) => DropdownMenuItem(
                          value: r,
                          child: Text(r, style: const TextStyle(fontSize: 12), overflow: TextOverflow.ellipsis),
                        ))
                    .toList(),
                onChanged: (val) {
                  if (val != null) setState(() => _selectedReason = val);
                },
              ),
              const SizedBox(height: 16),

              const Text('Explanation & Evidence *', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
              const SizedBox(height: 6),
              TextField(
                controller: _detailsController,
                maxLines: 4,
                decoration: InputDecoration(
                  hintText: 'Please detail dates, message quotes, or specifics of the interaction...',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 24),

              ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.redAccent,
                  foregroundColor: Colors.white,
                  minimumSize: const Size.fromHeight(50),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
                onPressed: _isSubmitting ? null : _submitReport,
                child: _isSubmitting
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                      )
                    : const Text('Submit Confidential Report', style: TextStyle(fontWeight: FontWeight.bold)),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

