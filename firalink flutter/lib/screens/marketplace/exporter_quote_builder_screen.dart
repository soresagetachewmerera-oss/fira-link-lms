import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import '../../services/api_exception.dart';
import '../../services/marketplace_service.dart';
import '../../widgets/fira_error_state.dart';

class ExporterQuoteBuilderScreen extends StatefulWidget {
  final int rfqId;
  final String rfqTitle;

  const ExporterQuoteBuilderScreen({
    super.key,
    required this.rfqId,
    required this.rfqTitle,
  });

  @override
  State<ExporterQuoteBuilderScreen> createState() =>
      _ExporterQuoteBuilderScreenState();
}

class _ExporterQuoteBuilderScreenState extends State<ExporterQuoteBuilderScreen> {
  final MarketplaceService _service = MarketplaceService();
  final _formKey = GlobalKey<FormState>();

  final _priceController = TextEditingController();
  final _notesController = TextEditingController();
  String _incoterm = 'FOB Djibouti';
  String _packaging = 'GrainPro + Jute 60kg';
  int _validityDays = 14;
  bool _isSubmitting = false;

  @override
  void dispose() {
    _priceController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _submitQuote() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isSubmitting = true);
    final pricePerLb = double.tryParse(_priceController.text.trim()) ?? 0.0;

    final http.Response res;
    try {
      res = await _service.submitQuote(
        widget.rfqId,
        {
          'price_per_lb': pricePerLb,
          'incoterm': _incoterm,
          'packaging': _packaging,
          'validity_days': _validityDays,
          'notes': _notesController.text.trim(),
        },
      );
    } catch (e) {
      if (!mounted) return;
      setState(() => _isSubmitting = false);
      showFiraActionError(context, e);
      return;
    }

    if (!mounted) return;
    setState(() => _isSubmitting = false);

    if (res.statusCode == 200 || res.statusCode == 201) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Commercial quote submitted successfully!'),
          backgroundColor: Colors.green,
        ),
      );
      Navigator.pop(context);
    } else {
      showFiraActionError(context, ApiException.fromResponse(res));
    }
  }

  @override
  Widget build(BuildContext context) {
    const primaryColor = Color(0xFF6D1A95);

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text(
          'Submit Quote',
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
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  'RFQ: ${widget.rfqTitle}',
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF1E293B)),
                ),
                const SizedBox(height: 6),
                const Text(
                  'Submit your binding commercial quotation. If accepted by the buyer, a Deal Room will be opened.',
                  style: TextStyle(fontSize: 12, color: Colors.black54),
                ),
                const SizedBox(height: 24),

                // Price
                const Text('FOB Price (USD / lb) *', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                TextFormField(
                  controller: _priceController,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  decoration: InputDecoration(
                    hintText: 'e.g. 3.45',
                    prefixText: '\$ ',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  validator: (v) => v == null || v.trim().isEmpty ? 'Price is required' : null,
                ),
                const SizedBox(height: 18),

                // Incoterm
                const Text('Incoterm *', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                DropdownButtonFormField<String>(
                  value: _incoterm,
                  decoration: InputDecoration(
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  items: const [
                    DropdownMenuItem(value: 'FOB Djibouti', child: Text('FOB Djibouti')),
                    DropdownMenuItem(value: 'CIF European Port', child: Text('CIF European Port')),
                    DropdownMenuItem(value: 'CFR Destination', child: Text('CFR Destination')),
                    DropdownMenuItem(value: 'FCA Addis Ababa', child: Text('FCA Addis Ababa')),
                  ],
                  onChanged: (val) {
                    if (val != null) setState(() => _incoterm = val);
                  },
                ),
                const SizedBox(height: 18),

                // Packaging
                const Text('Packaging Specification', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                DropdownButtonFormField<String>(
                  value: _packaging,
                  decoration: InputDecoration(
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  items: const [
                    DropdownMenuItem(value: 'GrainPro + Jute 60kg', child: Text('GrainPro + Jute 60kg')),
                    DropdownMenuItem(value: 'Ecotact + Jute 60kg', child: Text('Ecotact + Jute 60kg')),
                    DropdownMenuItem(value: 'Vacuum Box 30kg', child: Text('Vacuum Box 30kg (Microlot)')),
                    DropdownMenuItem(value: 'Standard Jute 60kg', child: Text('Standard Jute 60kg')),
                  ],
                  onChanged: (val) {
                    if (val != null) setState(() => _packaging = val);
                  },
                ),
                const SizedBox(height: 18),

                // Validity
                const Text('Quote Validity Period', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                DropdownButtonFormField<int>(
                  value: _validityDays,
                  decoration: InputDecoration(
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  items: const [
                    DropdownMenuItem(value: 7, child: Text('7 Days')),
                    DropdownMenuItem(value: 14, child: Text('14 Days (Standard)')),
                    DropdownMenuItem(value: 30, child: Text('30 Days')),
                  ],
                  onChanged: (val) {
                    if (val != null) setState(() => _validityDays = val);
                  },
                ),
                const SizedBox(height: 18),

                // Notes
                const Text('Terms & Remarks', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                TextFormField(
                  controller: _notesController,
                  maxLines: 3,
                  decoration: InputDecoration(
                    hintText: 'e.g. 100% Irrevocable LC at sight, pre-shipment sample dispatch within 3 business days.',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                ),
                const SizedBox(height: 32),

                ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: primaryColor,
                    foregroundColor: Colors.white,
                    minimumSize: const Size.fromHeight(52),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  ),
                  onPressed: _isSubmitting ? null : _submitQuote,
                  child: _isSubmitting
                      ? const SizedBox(
                          width: 22,
                          height: 22,
                          child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                        )
                      : const Text('Send Formal Quotation', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

