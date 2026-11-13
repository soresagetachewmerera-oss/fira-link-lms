import 'package:flutter/material.dart';
import '../../models/sample_order_model.dart';
import '../../services/sample_service.dart';
import 'sample_cupping_approval_screen.dart';

class SampleTrackingScreen extends StatefulWidget {
  final int sampleId;

  const SampleTrackingScreen({super.key, required this.sampleId});

  @override
  State<SampleTrackingScreen> createState() => _SampleTrackingScreenState();
}

class _SampleTrackingScreenState extends State<SampleTrackingScreen> {
  final SampleService _service = SampleService();
  SampleOrderModel? _sample;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadSample();
  }

  Future<void> _loadSample() async {
    setState(() => _isLoading = true);
    final s = await _service.getSample(widget.sampleId);
    if (mounted) {
      setState(() {
        _sample = s;
        _isLoading = false;
      });
    }
  }

  void _showDispatchDialog() {
    final courierCtrl = TextEditingController(text: 'DHL Express');
    final waybillCtrl = TextEditingController();
    final sealCtrl = TextEditingController(text: 'SEAL-${DateTime.now().millisecondsSinceEpoch.toString().substring(7)}');

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('Dispatch Coffee Sample', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Courier Service'),
            const SizedBox(height: 6),
            TextField(
              controller: courierCtrl,
              decoration: InputDecoration(border: OutlineInputBorder(borderRadius: BorderRadius.circular(10))),
            ),
            const SizedBox(height: 12),
            const Text('Waybill / Tracking Number'),
            const SizedBox(height: 6),
            TextField(
              controller: waybillCtrl,
              decoration: InputDecoration(
                hintText: 'e.g. 9482716352',
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
              ),
            ),
            const SizedBox(height: 12),
            const Text('Retained Twin Seal No (Crypto Hash)'),
            const SizedBox(height: 6),
            TextField(
              controller: sealCtrl,
              decoration: InputDecoration(border: OutlineInputBorder(borderRadius: BorderRadius.circular(10))),
            ),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancel')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF6D1A95), foregroundColor: Colors.white),
            onPressed: () async {
              Navigator.pop(ctx);
              final res = await _service.dispatchSample(
                sampleId: widget.sampleId,
                courierName: courierCtrl.text.trim(),
                waybillNumber: waybillCtrl.text.trim(),
                sealNo: sealCtrl.text.trim(),
              );
              if (mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text(res['message'] ?? 'Dispatched')),
                );
                _loadSample();
              }
            },
            child: const Text('Confirm Dispatch'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    const primaryColor = Color(0xFF6D1A95);

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: Text(
          _sample != null ? 'Sample: ${_sample!.sampleReference}' : 'Sample Tracking',
          style: const TextStyle(color: Color(0xFF1E293B), fontWeight: FontWeight.bold, fontSize: 16),
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
          : _sample == null
              ? const Center(child: Text('Sample not found'))
              : SingleChildScrollView(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      // Status card
                      Container(
                        padding: const EdgeInsets.all(18),
                        decoration: BoxDecoration(
                          color: primaryColor.withValues(alpha: 0.05),
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: primaryColor.withValues(alpha: 0.2)),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  'REFERENCE ${_sample!.sampleReference}',
                                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: primaryColor),
                                ),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                  decoration: BoxDecoration(
                                    color: _sample!.isCuppedApproved
                                        ? Colors.green
                                        : _sample!.isDispatched
                                            ? Colors.blue
                                            : Colors.orange,
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                  child: Text(
                                    _sample!.status.toUpperCase().replaceAll('_', ' '),
                                    style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 8),
                            Text(
                              'Quantity: ${_sample!.quantityGrams}g • Green Coffee Type Sample',
                              style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 20),

                      // Retained Seal & Tamper Evidence
                      const Text(
                        'Cryptographic Retained Seal Protocol',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Color(0xFF1E293B)),
                      ),
                      const SizedBox(height: 10),
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: Colors.grey.shade200),
                        ),
                        child: Column(
                          children: [
                            _buildInfoRow(
                              'Courier Service',
                              _sample!.courierName ?? 'Pending Dispatch',
                              Icons.local_shipping_outlined,
                            ),
                            const Divider(height: 20),
                            _buildInfoRow(
                              'Waybill Tracking',
                              _sample!.waybillNumber ?? 'Awaiting courier consignment',
                              Icons.confirmation_number_outlined,
                            ),
                            const Divider(height: 20),
                            _buildInfoRow(
                              'Retained Twin Seal No',
                              _sample!.retainedSampleSealNo ?? 'Sealed upon dispatch',
                              Icons.security_outlined,
                            ),
                            if (_sample!.retainedSampleSealHash != null) ...[
                              const Divider(height: 20),
                              _buildInfoRow(
                                'Integrity SHA-256 Hash',
                                _sample!.retainedSampleSealHash!.substring(0, 20) + '...',
                                Icons.fingerprint,
                              ),
                            ],
                          ],
                        ),
                      ),
                      const SizedBox(height: 24),

                      // Destination Address
                      const Text(
                        'Shipping Destination',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Color(0xFF1E293B)),
                      ),
                      const SizedBox(height: 10),
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: Colors.grey.shade200),
                        ),
                        child: Text(
                          _sample!.shippingAddress,
                          style: TextStyle(fontSize: 13, color: Colors.grey.shade800, height: 1.4),
                        ),
                      ),
                      const SizedBox(height: 32),

                      // Actions
                      if (!_sample!.isDispatched && !_sample!.isDelivered)
                        ElevatedButton.icon(
                          style: ElevatedButton.styleFrom(
                            backgroundColor: primaryColor,
                            foregroundColor: Colors.white,
                            minimumSize: const Size.fromHeight(50),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                          ),
                          icon: const Icon(Icons.send_outlined),
                          label: const Text('Dispatch Sample (Exporter)', style: TextStyle(fontWeight: FontWeight.bold)),
                          onPressed: _showDispatchDialog,
                        ),

                      if (_sample!.isDispatched && !_sample!.isCuppedApproved && !_sample!.isCuppedRejected)
                        ElevatedButton.icon(
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.green,
                            foregroundColor: Colors.white,
                            minimumSize: const Size.fromHeight(50),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                          ),
                          icon: const Icon(Icons.coffee),
                          label: const Text('Record Cupping & Decision (Buyer)', style: TextStyle(fontWeight: FontWeight.bold)),
                          onPressed: () {
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (_) => SampleCuppingApprovalScreen(sampleId: _sample!.id),
                              ),
                            ).then((_) => _loadSample());
                          },
                        ),
                    ],
                  ),
                ),
    );
  }

  Widget _buildInfoRow(String label, String val, IconData icon) {
    return Row(
      children: [
        Icon(icon, size: 20, color: const Color(0xFF6D1A95)),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
              Text(val, style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
            ],
          ),
        ),
      ],
    );
  }
}

