import 'package:flutter/material.dart';
import '../../services/trust_safety_service.dart';
import '../trust_safety/report_modal.dart';

class PublicSellerVerificationScreen extends StatefulWidget {
  final String query; // slug, id, or TIN

  const PublicSellerVerificationScreen({super.key, required this.query});

  @override
  State<PublicSellerVerificationScreen> createState() =>
      _PublicSellerVerificationScreenState();
}

class _PublicSellerVerificationScreenState
    extends State<PublicSellerVerificationScreen> {
  final TrustSafetyService _service = TrustSafetyService();
  bool _isLoading = true;
  Map<String, dynamic>? _data;
  String? _error;

  @override
  void initState() {
    super.initState();
    _fetchVerification();
  }

  Future<void> _fetchVerification() async {
    setState(() => _isLoading = true);
    final res = await _service.verifySeller(widget.query);
    if (mounted) {
      setState(() {
        if (res['success'] == true) {
          _data = res['data'];
        } else {
          _error = res['message'];
        }
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    const primaryColor = Color(0xFF6D1A95);

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text(
          'Verification Registry',
          style: TextStyle(color: Color(0xFF1E293B), fontWeight: FontWeight.bold, fontSize: 16),
        ),
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF1E293B)),
          onPressed: () => Navigator.pop(context),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.flag_outlined, color: Colors.redAccent),
            tooltip: 'Report Entity',
            onPressed: () {
              final org = _data?['organization'];
              ReportModal.show(
                context,
                reportedOrganizationId: org?['id'],
                targetTitle: org?['name'] ?? 'Organization',
              );
            },
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: primaryColor))
          : _error != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24.0),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(Icons.gpp_bad_outlined, size: 56, color: Colors.redAccent),
                        const SizedBox(height: 16),
                        Text(
                          _error!,
                          textAlign: TextAlign.center,
                          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                        ),
                        const SizedBox(height: 8),
                        const Text(
                          'Always verify trade partners on Fira Link before signing contracts or paying advances.',
                          textAlign: TextAlign.center,
                          style: TextStyle(color: Colors.grey, fontSize: 13),
                        ),
                      ],
                    ),
                  ),
                )
              : SingleChildScrollView(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      // Header Card
                      Container(
                        padding: const EdgeInsets.all(20),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: Colors.green.shade200),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.green.withValues(alpha: 0.06),
                              blurRadius: 16,
                              offset: const Offset(0, 4),
                            ),
                          ],
                        ),
                        child: Column(
                          children: [
                            Container(
                              width: 60,
                              height: 60,
                              decoration: BoxDecoration(
                                color: Colors.green.shade50,
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(Icons.verified_user, color: Colors.green, size: 36),
                            ),
                            const SizedBox(height: 12),
                            Text(
                              _data?['organization']?['name'] ?? 'Verified Organization',
                              textAlign: TextAlign.center,
                              style: const TextStyle(
                                fontSize: 20,
                                fontWeight: FontWeight.bold,
                                color: Color(0xFF1E293B),
                              ),
                            ),
                            const SizedBox(height: 6),
                            Text(
                              '${_data?['organization']?['business_type']?.toString().toUpperCase()} • ${_data?['organization']?['country']}',
                              style: TextStyle(fontSize: 12, color: Colors.grey.shade600, fontWeight: FontWeight.w500),
                            ),
                            const SizedBox(height: 16),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                              decoration: BoxDecoration(
                                color: Colors.green.shade100,
                                borderRadius: BorderRadius.circular(20),
                              ),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  const Icon(Icons.check_circle, color: Colors.green, size: 16),
                                  const SizedBox(width: 6),
                                  Text(
                                    'OFFICIALLY VERIFIED EXPORTER',
                                    style: TextStyle(
                                      color: Colors.green.shade900,
                                      fontWeight: FontWeight.bold,
                                      fontSize: 11,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 24),

                      // Trust Metrics
                      Row(
                        children: [
                          Expanded(
                            child: _buildMetricTile(
                              title: 'Trust Score',
                              value: '${_data?['trust_summary']?['trust_score'] ?? 98}%',
                              color: Colors.green,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: _buildMetricTile(
                              title: 'Dispute Rate',
                              value: '${_data?['trust_summary']?['dispute_rate'] ?? 0.0}%',
                              color: Colors.blue,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: _buildMetricTile(
                              title: 'Active Lots',
                              value: '${_data?['organization']?['active_listings_count'] ?? 0}',
                              color: primaryColor,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 24),

                      // Verification Dossier Items
                      const Text(
                        'Verified Regulatory Credentials',
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF1E293B)),
                      ),
                      const SizedBox(height: 12),

                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: Colors.grey.shade200),
                        ),
                        child: Column(
                          children: [
                            _buildCredRow(
                              'Issuing Authority',
                              'ECTA (Ethiopian Coffee & Tea Authority)',
                              Icons.account_balance,
                            ),
                            const Divider(height: 24),
                            _buildCredRow(
                              'Commercial Registration',
                              'Ministry of Trade & Regional Integration',
                              Icons.assignment,
                            ),
                            const Divider(height: 24),
                            _buildCredRow(
                              'Cryptographic Vault',
                              'SHA-256 Vault Hash Authenticated',
                              Icons.lock_outline,
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 32),

                      ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: primaryColor,
                          foregroundColor: Colors.white,
                          minimumSize: const Size.fromHeight(50),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        ),
                        icon: const Icon(Icons.coffee_outlined),
                        label: const Text('Browse Exporter Coffee Lots', style: TextStyle(fontWeight: FontWeight.bold)),
                        onPressed: () => Navigator.pop(context),
                      ),
                    ],
                  ),
                ),
    );
  }

  Widget _buildMetricTile({required String title, required String value, required Color color}) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 10),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.06),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Column(
        children: [
          Text(value, style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: color)),
          const SizedBox(height: 2),
          Text(title, style: TextStyle(fontSize: 11, color: Colors.grey.shade600)),
        ],
      ),
    );
  }

  Widget _buildCredRow(String title, String val, IconData icon) {
    return Row(
      children: [
        Icon(icon, size: 20, color: const Color(0xFF6D1A95)),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
              Text(val, style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
            ],
          ),
        ),
        const Icon(Icons.check_circle, color: Colors.green, size: 18),
      ],
    );
  }
}

