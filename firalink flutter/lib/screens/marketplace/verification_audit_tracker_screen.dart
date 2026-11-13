import 'dart:convert';
import 'package:flutter/material.dart';
import '../../services/api_exception.dart';
import '../../services/marketplace_service.dart';
import '../../widgets/fira_error_state.dart';

class VerificationAuditTrackerScreen extends StatefulWidget {
  final String role; // 'exporter', 'supplier', 'buyer', 'broker'

  const VerificationAuditTrackerScreen({super.key, required this.role});

  @override
  State<VerificationAuditTrackerScreen> createState() =>
      _VerificationAuditTrackerScreenState();
}

class _VerificationAuditTrackerScreenState
    extends State<VerificationAuditTrackerScreen> {
  final MarketplaceService _marketplaceService = MarketplaceService();
  bool _isLoading = true;
  Object? _loadError;

  /// Real status for this role from the API (unsubmitted, draft, pending_review, approved, ...).
  String _status = 'unsubmitted';
  int _documentCount = 0;

  @override
  void initState() {
    super.initState();
    _fetchStatus();
  }

  Future<void> _fetchStatus() async {
    setState(() {
      _isLoading = true;
      _loadError = null;
    });
    try {
      final res = requireSuccess(await _marketplaceService.getVerificationStatus());
      final decoded = jsonDecode(res.body);
      final roleData = decoded['data']?['checklist']?[widget.role];
      final documents = roleData?['data']?['documents'];
      if (!mounted) return;
      setState(() {
        _status = roleData?['status']?.toString() ?? 'unsubmitted';
        _documentCount = documents is List ? documents.length : 0;
        _isLoading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loadError = e;
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    const primaryColor = Color(0xFF6D1A95);

    // Each step is done only when the server says so. Approval comes only from an admin review.
    final submitted = !['unsubmitted', 'draft'].contains(_status);
    final approved = _status == 'approved';
    final steps = [
      {'title': 'Dossier Submitted', 'desc': 'Legal credentials & certificates uploaded', 'done': submitted},
      {'title': 'Document Integrity Check', 'desc': 'SHA-256 cryptographic vault hash verified', 'done': _documentCount > 0},
      {'title': 'ECTA / MoTI Registry Match', 'desc': 'Cross-referenced against regulatory registry', 'done': approved},
      {'title': 'Signatory Authorization', 'desc': 'National ID / passport verified', 'done': approved},
      {'title': 'Verified Badge Grant', 'desc': 'Public verification seal awarded', 'done': approved},
    ];

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text(
          'Verification Audit Tracker',
          style: TextStyle(color: Color(0xFF1E293B), fontWeight: FontWeight.bold, fontSize: 16),
        ),
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF1E293B)),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: primaryColor))
          : _loadError != null
          ? FiraErrorState(error: _loadError, onRetry: _fetchStatus)
          : Padding(
              padding: const EdgeInsets.all(20.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Container(
                    padding: const EdgeInsets.all(18),
                    decoration: BoxDecoration(
                      color: primaryColor.withValues(alpha: 0.05),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: primaryColor.withValues(alpha: 0.2)),
                    ),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: primaryColor.withValues(alpha: 0.1),
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(Icons.shield_outlined, color: primaryColor, size: 28),
                        ),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                '${widget.role.toUpperCase()} AUDIT IN PROGRESS',
                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: primaryColor),
                              ),
                              const SizedBox(height: 2),
                              const Text(
                                'Trust is Never for Sale: Every credential undergoes manual & automated regulatory vetting.',
                                style: TextStyle(fontSize: 11, color: Colors.black54),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 24),
                  const Text(
                    'Audit Pipeline Stages',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF1E293B)),
                  ),
                  const SizedBox(height: 16),

                  Expanded(
                    child: ListView.builder(
                      itemCount: steps.length,
                      itemBuilder: (context, index) {
                        final step = steps[index];
                        final isDone = step['done'] == true;
                        final isLast = index == steps.length - 1;

                        return Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Column(
                              children: [
                                Container(
                                  width: 28,
                                  height: 28,
                                  decoration: BoxDecoration(
                                    color: isDone ? Colors.green : Colors.grey.shade200,
                                    shape: BoxShape.circle,
                                  ),
                                  child: Icon(
                                    isDone ? Icons.check : Icons.hourglass_empty,
                                    size: 16,
                                    color: isDone ? Colors.white : Colors.grey,
                                  ),
                                ),
                                if (!isLast)
                                  Container(
                                    width: 2,
                                    height: 48,
                                    color: isDone ? Colors.green.shade300 : Colors.grey.shade200,
                                  ),
                              ],
                            ),
                            const SizedBox(width: 16),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    step['title'] as String,
                                    style: TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 14,
                                      color: isDone ? const Color(0xFF1E293B) : Colors.grey.shade600,
                                    ),
                                  ),
                                  const SizedBox(height: 2),
                                  Text(
                                    step['desc'] as String,
                                    style: TextStyle(fontSize: 12, color: Colors.grey.shade500),
                                  ),
                                  const SizedBox(height: 24),
                                ],
                              ),
                            ),
                          ],
                        );
                      },
                    ),
                  ),
                ],
              ),
            ),
    );
  }
}

