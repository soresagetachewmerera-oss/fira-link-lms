import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/auth_provider.dart';
import '../../services/marketplace_service.dart';
import '../../theme/app_theme.dart';
import '../../widgets/fira_error_state.dart';
import 'widgets/verification_prompt_dialog.dart';

class CreateRfqScreen extends StatefulWidget {
  const CreateRfqScreen({super.key});

  @override
  State<CreateRfqScreen> createState() => _CreateRfqScreenState();
}

class _CreateRfqScreenState extends State<CreateRfqScreen> {
  final MarketplaceService _marketplaceService = MarketplaceService();
  final _formKey = GlobalKey<FormState>();

  final _titleCtrl = TextEditingController();
  final _originCtrl = TextEditingController();
  final _volumeCtrl = TextEditingController();
  final _destCountryCtrl = TextEditingController();
  final _destPortCtrl = TextEditingController();
  final _minScoreCtrl = TextEditingController();
  final _targetPriceCtrl = TextEditingController();
  final _specsCtrl = TextEditingController();

  String _incoterm = 'FOB';
  bool _isSubmitting = false;

  Future<void> _submitRfq() async {
    final auth = context.read<AuthProvider>();
    if (!auth.isAuthenticated) {
      VerificationPromptDialog.showAuthRequired(
        context,
        actionDescription: 'publish a coffee procurement tender',
      );
      return;
    }

    if (!_formKey.currentState!.validate()) return;

    setState(() => _isSubmitting = true);
    try {
      final res = await _marketplaceService.submitRfq({
        'title': _titleCtrl.text.trim(),
        'target_origin_region': _originCtrl.text.trim().isEmpty ? null : _originCtrl.text.trim(),
        'volume_bags': int.parse(_volumeCtrl.text.trim()),
        'destination_country': _destCountryCtrl.text.trim(),
        'destination_port': _destPortCtrl.text.trim(),
        'preferred_incoterm': _incoterm,
        'target_cupping_score_min': double.tryParse(_minScoreCtrl.text.trim()),
        'target_price_usd_per_lb': double.tryParse(_targetPriceCtrl.text.trim()),
        'specifications': _specsCtrl.text.trim(),
      });

      setState(() => _isSubmitting = false);

      if (res.statusCode == 200 || res.statusCode == 201) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Buyer RFQ published successfully!'), backgroundColor: Colors.green),
          );
          Navigator.pop(context);
        }
      } else if (res.statusCode == 401) {
        if (mounted) {
          VerificationPromptDialog.showAuthRequired(
            context,
            actionDescription: 'publish a coffee procurement tender',
          );
        }
      } else if (res.statusCode == 403) {
        if (mounted) {
          try {
            final data = jsonDecode(res.body);
            VerificationPromptDialog.showVerificationRequired(
              context,
              role: 'buyer',
              status: data['verification_status'],
              message: data['message'],
            );
          } catch (_) {
            VerificationPromptDialog.showVerificationRequired(
              context,
              role: 'buyer',
            );
          }
        }
      } else {
        if (mounted) {
          String msg = 'Failed to publish RFQ. Please check fields.';
          try {
            final err = jsonDecode(res.body);
            if (err['message'] != null) msg = err['message'];
          } catch (_) {}
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(msg), backgroundColor: Colors.red),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isSubmitting = false);
        showFiraActionError(context, e);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Post Buyer RFQ', style: TextStyle(fontWeight: FontWeight.bold)),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('Publish Procurement Tender', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800)),
              const SizedBox(height: 4),
              const Text('Verified Ethiopian exporters will review and submit binding quotes.',
                  style: TextStyle(fontSize: 12, color: Colors.grey)),
              const SizedBox(height: 20),

              TextFormField(
                controller: _titleCtrl,
                decoration: InputDecoration(
                  labelText: 'Tender Title *',
                  hintText: 'e.g. Sourcing 300 Bags Guji / Sidama Natural G1',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
                validator: (val) => val == null || val.trim().isEmpty ? 'Title is required' : null,
              ),

              const SizedBox(height: 14),

              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _originCtrl,
                      decoration: InputDecoration(
                        labelText: 'Target Origin',
                        hintText: 'e.g. Guji, Sidama',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: TextFormField(
                      controller: _volumeCtrl,
                      keyboardType: TextInputType.number,
                      decoration: InputDecoration(
                        labelText: 'Volume (60kg Bags) *',
                        hintText: 'e.g. 200',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      validator: (val) => val == null || val.trim().isEmpty ? 'Volume required' : null,
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 14),

              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _destCountryCtrl,
                      decoration: InputDecoration(
                        labelText: 'Destination Country *',
                        hintText: 'e.g. Germany',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      validator: (val) => val == null || val.trim().isEmpty ? 'Required' : null,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: TextFormField(
                      controller: _destPortCtrl,
                      decoration: InputDecoration(
                        labelText: 'Destination Port *',
                        hintText: 'e.g. Hamburg',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      validator: (val) => val == null || val.trim().isEmpty ? 'Required' : null,
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 14),

              Row(
                children: [
                  Expanded(
                    child: DropdownButtonFormField<String>(
                      initialValue: _incoterm,
                      isExpanded: true,
                      decoration: InputDecoration(
                        labelText: 'Incoterm',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 12),
                      ),
                      items: const [
                        DropdownMenuItem(value: 'FOB', child: Text('FOB (Djibouti)', overflow: TextOverflow.ellipsis)),
                        DropdownMenuItem(value: 'CIF', child: Text('CIF', overflow: TextOverflow.ellipsis)),
                        DropdownMenuItem(value: 'CFR', child: Text('CFR', overflow: TextOverflow.ellipsis)),
                      ],
                      onChanged: (val) => setState(() => _incoterm = val!),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: TextFormField(
                      controller: _minScoreCtrl,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      decoration: InputDecoration(
                        labelText: 'Min SCA Score',
                        hintText: 'e.g. 87.5',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 12),
                      ),
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 14),

              TextFormField(
                controller: _targetPriceCtrl,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                decoration: InputDecoration(
                  labelText: 'Target FOB Price (USD / lb)',
                  hintText: 'e.g. 4.15',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),

              const SizedBox(height: 14),

              TextFormField(
                controller: _specsCtrl,
                maxLines: 4,
                decoration: InputDecoration(
                  labelText: 'Quality & Shipping Specifications',
                  hintText: 'Enter moisture ceilings, sample requirements, sensory preferences...',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),

              const SizedBox(height: 24),

              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _isSubmitting ? null : _submitRfq,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppTheme.primary,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  ),
                  child: _isSubmitting
                      ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                      : const Text('Publish RFQ Tender', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
