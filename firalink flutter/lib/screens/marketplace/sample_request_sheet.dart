import 'package:flutter/material.dart';
import '../../services/sample_service.dart';

class SampleRequestSheet extends StatefulWidget {
  final int listingId;
  final String lotTitle;
  final double sampleCostUsd;

  const SampleRequestSheet({
    super.key,
    required this.listingId,
    required this.lotTitle,
    this.sampleCostUsd = 0.0,
  });

  static Future<bool?> show(
    BuildContext context, {
    required int listingId,
    required String lotTitle,
    double sampleCostUsd = 0.0,
  }) {
    return showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => SampleRequestSheet(
        listingId: listingId,
        lotTitle: lotTitle,
        sampleCostUsd: sampleCostUsd,
      ),
    );
  }

  @override
  State<SampleRequestSheet> createState() => _SampleRequestSheetState();
}

class _SampleRequestSheetState extends State<SampleRequestSheet> {
  final SampleService _service = SampleService();
  final _addressController = TextEditingController();
  int _grams = 250;
  bool _isSubmitting = false;

  @override
  void dispose() {
    _addressController.dispose();
    super.dispose();
  }

  Future<void> _submitRequest() async {
    final addr = _addressController.text.trim();
    if (addr.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please enter courier destination address')),
      );
      return;
    }

    setState(() => _isSubmitting = true);
    final res = await _service.requestSample(
      listingId: widget.listingId,
      shippingAddress: addr,
      quantityGrams: _grams,
    );

    if (!mounted) return;
    setState(() => _isSubmitting = false);

    if (res['success'] == true) {
      Navigator.pop(context, true);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(res['message'] ?? 'Failed to request sample'),
          backgroundColor: Colors.redAccent,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    const primaryColor = Color(0xFF6D1A95);

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
              const Text(
                'Request Green Coffee Sample',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF1E293B)),
              ),
              const SizedBox(height: 4),
              Text(
                widget.lotTitle,
                style: TextStyle(fontSize: 13, color: Colors.grey.shade600),
              ),
              const SizedBox(height: 20),

              // Sample weight picker
              const Text('Sample Quantity', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
              const SizedBox(height: 8),
              Row(
                children: [250, 500, 1000].map((g) {
                  final isSel = _grams == g;
                  return Expanded(
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 4.0),
                      child: ChoiceChip(
                        label: Center(child: Text('${g}g')),
                        selected: isSel,
                        selectedColor: primaryColor.withValues(alpha: 0.15),
                        labelStyle: TextStyle(
                          color: isSel ? primaryColor : Colors.black87,
                          fontWeight: isSel ? FontWeight.bold : FontWeight.normal,
                        ),
                        onSelected: (_) => setState(() => _grams = g),
                      ),
                    ),
                  );
                }).toList(),
              ),
              const SizedBox(height: 18),

              // Shipping destination address
              const Text('Courier Shipping Address *', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
              const SizedBox(height: 6),
              TextField(
                controller: _addressController,
                maxLines: 3,
                decoration: InputDecoration(
                  hintText: 'Company Name, Street, City, Postal Code, Country, Contact Phone for Courier Delivery',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 16),

              // Trust notice
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.blue.shade50,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.verified_outlined, color: Colors.blue, size: 20),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        'Retained Sample Protocol: Exporter keeps a twin sample with matching cryptographic seal number for arbitration comparison.',
                        style: TextStyle(fontSize: 11, color: Colors.blue.shade900),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),

              ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: primaryColor,
                  foregroundColor: Colors.white,
                  minimumSize: const Size.fromHeight(50),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
                onPressed: _isSubmitting ? null : _submitRequest,
                child: _isSubmitting
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                      )
                    : Text(
                        widget.sampleCostUsd > 0
                            ? 'Request Sample (\$${widget.sampleCostUsd.toStringAsFixed(2)})'
                            : 'Request Complimentary Sample',
                        style: const TextStyle(fontWeight: FontWeight.bold),
                      ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

