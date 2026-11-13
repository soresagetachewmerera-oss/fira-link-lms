import 'dart:convert';
import 'package:flutter/material.dart';
import '../../services/marketplace_service.dart';
import '../../theme/app_theme.dart';
import 'widgets/quota_limit_sheet.dart';
import 'widgets/verification_prompt_dialog.dart';

class CreateListingScreen extends StatefulWidget {
  const CreateListingScreen({super.key});

  @override
  State<CreateListingScreen> createState() => _CreateListingScreenState();
}

class _CreateListingScreenState extends State<CreateListingScreen> {
  final MarketplaceService _marketplaceService = MarketplaceService();
  final _formKey = GlobalKey<FormState>();

  final _titleCtrl = TextEditingController();
  final _originCtrl = TextEditingController(text: 'Sidama');
  final _subRegionCtrl = TextEditingController();
  final _washingStationCtrl = TextEditingController();
  final _varietyCtrl = TextEditingController(text: '74110 / 74112 Heirloom');
  final _scoreCtrl = TextEditingController(text: '88.5');
  final _bagsCtrl = TextEditingController(text: '150');
  final _priceCtrl = TextEditingController(text: '4.20');
  final _notesCtrl = TextEditingController(text: 'Peach, Jasmine, Citrus, Honey');
  final _descCtrl = TextEditingController();

  String _processing = 'washed';
  String _grade = 'grade_1';
  bool _isSubmitting = false;

  Future<void> _submitListing() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isSubmitting = true);
    try {
      final tastingList = _notesCtrl.text
          .split(',')
          .map((s) => s.trim())
          .where((s) => s.isNotEmpty)
          .toList();

      final res = await _marketplaceService.createListing({
        'title': _titleCtrl.text.trim(),
        'origin_region': _originCtrl.text.trim(),
        'sub_region': _subRegionCtrl.text.trim().isEmpty ? null : _subRegionCtrl.text.trim(),
        'washing_station': _washingStationCtrl.text.trim().isEmpty ? null : _washingStationCtrl.text.trim(),
        'variety': _varietyCtrl.text.trim(),
        'processing_method': _processing,
        'grade': _grade,
        'cupping_score': double.tryParse(_scoreCtrl.text.trim()),
        'total_quantity_bags': int.parse(_bagsCtrl.text.trim()),
        'indicative_price_usd_per_lb': double.parse(_priceCtrl.text.trim()),
        'tasting_notes': tastingList,
        'description': _descCtrl.text.trim(),
      });

      setState(() => _isSubmitting = false);

      if (res.statusCode == 200 || res.statusCode == 201) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Coffee lot published successfully!'), backgroundColor: Colors.green),
          );
          Navigator.pop(context);
        }
      } else if (res.statusCode == 401) {
        if (mounted) {
          VerificationPromptDialog.showAuthRequired(
            context,
            actionDescription: 'publish live coffee export lots',
          );
        }
      } else if (res.statusCode == 403) {
        if (mounted) {
          try {
            final decoded = jsonDecode(res.body);
            if (decoded['code'] == 'VERIFICATION_REQUIRED') {
              VerificationPromptDialog.showVerificationRequired(
                context,
                role: 'exporter',
                status: decoded['verification_status'],
                message: decoded['message'],
              );
              return;
            }
          } catch (_) {}

          if (_marketplaceService.isLimitReached(res)) {
            final limitInfo = _marketplaceService.parseLimitError(res) ?? {
              'feature': 'active_lots',
              'message': 'You have reached your active lots limit. Please upgrade your plan for additional capacity.',
            };
            QuotaLimitSheet.show(context, limitInfo);
            return;
          }

          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Action restricted. Exporter verification required.'), backgroundColor: Colors.red),
          );
        }
      } else if (_marketplaceService.isLimitReached(res)) {
        if (mounted) {
          final limitInfo = _marketplaceService.parseLimitError(res) ?? {
            'feature': 'active_lots',
            'message': 'You have reached your active lots limit. Please upgrade your plan for additional capacity.',
          };
          QuotaLimitSheet.show(context, limitInfo);
        }
      } else {
        if (mounted) {
          String errMsg = 'Failed to publish lot. Verify quota.';
          try {
            final decoded = jsonDecode(res.body);
            if (decoded['message'] != null) errMsg = decoded['message'];
          } catch (_) {}
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(errMsg), backgroundColor: Colors.red),
          );
        }
      }
    } catch (_) {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Add Coffee Export Lot', style: TextStyle(fontWeight: FontWeight.bold)),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('Publish Green Coffee Lot', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800)),
              const SizedBox(height: 4),
              const Text('Present your specialty harvest lot to international green coffee buyers.',
                  style: TextStyle(fontSize: 12, color: Colors.grey)),
              const SizedBox(height: 20),

              TextFormField(
                controller: _titleCtrl,
                decoration: InputDecoration(
                  labelText: 'Lot Title *',
                  hintText: 'e.g. Sidama Bensa Hamasho Natural G1',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
                validator: (val) => val == null || val.trim().isEmpty ? 'Title required' : null,
              ),

              const SizedBox(height: 14),

              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _originCtrl,
                      decoration: InputDecoration(
                        labelText: 'Origin Region *',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      validator: (val) => val == null || val.trim().isEmpty ? 'Required' : null,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: TextFormField(
                      controller: _subRegionCtrl,
                      decoration: InputDecoration(
                        labelText: 'Sub-Region / Woreda',
                        hintText: 'e.g. Bensa',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 14),

              TextFormField(
                controller: _washingStationCtrl,
                decoration: InputDecoration(
                  labelText: 'Washing Station / Wet Mill',
                  hintText: 'e.g. Kokose Wet Mill',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),

              const SizedBox(height: 14),

              Row(
                children: [
                  Expanded(
                    child: DropdownButtonFormField<String>(
                      initialValue: _processing,
                      isExpanded: true,
                      decoration: InputDecoration(
                        labelText: 'Processing',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 12),
                      ),
                      items: const [
                        DropdownMenuItem(value: 'washed', child: Text('Fully Washed', overflow: TextOverflow.ellipsis)),
                        DropdownMenuItem(value: 'natural', child: Text('Natural / Dry', overflow: TextOverflow.ellipsis)),
                        DropdownMenuItem(value: 'anaerobic', child: Text('Anaerobic', overflow: TextOverflow.ellipsis)),
                        DropdownMenuItem(value: 'honey', child: Text('Honey Processed', overflow: TextOverflow.ellipsis)),
                      ],
                      onChanged: (val) => setState(() => _processing = val!),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: DropdownButtonFormField<String>(
                      initialValue: _grade,
                      isExpanded: true,
                      decoration: InputDecoration(
                        labelText: 'Grade',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 12),
                      ),
                      items: const [
                        DropdownMenuItem(value: 'grade_1', child: Text('G1 Specialty', overflow: TextOverflow.ellipsis)),
                        DropdownMenuItem(value: 'grade_2', child: Text('G2 Premium', overflow: TextOverflow.ellipsis)),
                        DropdownMenuItem(value: 'grade_3', child: Text('Grade 3', overflow: TextOverflow.ellipsis)),
                        DropdownMenuItem(value: 'grade_4', child: Text('Grade 4', overflow: TextOverflow.ellipsis)),
                      ],
                      onChanged: (val) => setState(() => _grade = val!),
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 14),

              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _scoreCtrl,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      decoration: InputDecoration(
                        labelText: 'SCA Cupping Score',
                        hintText: 'e.g. 88.5',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: TextFormField(
                      controller: _priceCtrl,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      decoration: InputDecoration(
                        labelText: 'FOB Price (USD/lb) *',
                        hintText: 'e.g. 4.25',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      validator: (val) => val == null || val.trim().isEmpty ? 'Required' : null,
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 14),

              TextFormField(
                controller: _bagsCtrl,
                keyboardType: TextInputType.number,
                decoration: InputDecoration(
                  labelText: 'Total Quantity (60kg Bags) *',
                  hintText: 'e.g. 150',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
                validator: (val) => val == null || val.trim().isEmpty ? 'Required' : null,
              ),

              const SizedBox(height: 14),

              TextFormField(
                controller: _notesCtrl,
                decoration: InputDecoration(
                  labelText: 'Tasting Notes (comma-separated)',
                  hintText: 'e.g. Peach, Jasmine, Bergamot, Honey',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),

              const SizedBox(height: 14),

              TextFormField(
                controller: _descCtrl,
                maxLines: 3,
                decoration: InputDecoration(
                  labelText: 'Lot Background / Fermentation Notes',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),

              const SizedBox(height: 24),

              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _isSubmitting ? null : _submitListing,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppTheme.primary,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  ),
                  child: _isSubmitting
                      ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                      : const Text('Publish Coffee Lot', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
