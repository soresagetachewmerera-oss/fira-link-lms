import 'package:flutter/material.dart';
import 'package:hugeicons/hugeicons.dart';
import 'package:provider/provider.dart';

import '../../providers/coffee_toolkit_provider.dart';
import '../../theme/app_theme.dart';

class CoffeeOfferGeneratorScreen extends StatefulWidget {
  const CoffeeOfferGeneratorScreen({super.key});

  @override
  State<CoffeeOfferGeneratorScreen> createState() =>
      _CoffeeOfferGeneratorScreenState();
}

class _CoffeeOfferGeneratorScreenState
    extends State<CoffeeOfferGeneratorScreen> {
  final TextEditingController _exporterNameController =
      TextEditingController(text: 'Fira Link Verified Exporter PLC');
  final TextEditingController _exporterContactController =
      TextEditingController(text: 'exports@firalink.com');
  final TextEditingController _subRegionController =
      TextEditingController(text: 'Bensa / Shantawene');
  final TextEditingController _washingStationController =
      TextEditingController(text: 'Bombe Washing Station');
  final TextEditingController _varietyController =
      TextEditingController(text: '74158 / Indigenous Heirloom');
  final TextEditingController _scoreController =
      TextEditingController(text: '85.5');
  final TextEditingController _bagsController =
      TextEditingController(text: '320');
  final TextEditingController _priceController =
      TextEditingController(text: '3.25');
  final TextEditingController _notesController = TextEditingController(
      text: 'Floral bergamot, lemon zest, sweet peach, clean jasmine finish');

  String _selectedRegion = 'Sidama';
  String _selectedGrade = 'Grade 2';
  String _selectedProcess = 'Fully Washed';
  String _selectedIncoterm = 'FOB Djibouti';

  @override
  void dispose() {
    _exporterNameController.dispose();
    _exporterContactController.dispose();
    _subRegionController.dispose();
    _washingStationController.dispose();
    _varietyController.dispose();
    _scoreController.dispose();
    _bagsController.dispose();
    _priceController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _handleGenerateOffer() async {
    final provider = context.read<CoffeeToolkitProvider>();
    final params = {
      'exporter_name': _exporterNameController.text,
      'exporter_contact': _exporterContactController.text,
      'origin_region': _selectedRegion,
      'sub_region': _subRegionController.text,
      'washing_station': _washingStationController.text,
      'grade': _selectedGrade,
      'variety': _varietyController.text,
      'processing_method': _selectedProcess,
      'cupping_score': double.tryParse(_scoreController.text) ?? 85.0,
      'crop_year': '2025/2026',
      'quantity_bags': int.tryParse(_bagsController.text) ?? 320,
      'price_usd_per_lb': double.tryParse(_priceController.text) ?? 3.25,
      'incoterm': _selectedIncoterm,
      'validity_date':
          DateTime.now().add(const Duration(days: 14)).toIso8601String(),
      'tasting_notes': _notesController.text,
    };

    final success = await provider.createOffer(params);
    if (!mounted) return;

    if (success) {
      final generatedRef = provider.recentOffers.isNotEmpty
          ? provider.recentOffers.first.referenceNo
          : 'FL-EXP-CONFIRMED';

      showDialog(
        context: context,
        builder: (context) => AlertDialog(
          shape:
              RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          title: const Row(
            children: [
              Icon(Icons.check_circle, color: Colors.green),
              SizedBox(width: 8),
              Text('Offer Generated'),
            ],
          ),
          content: Text(
            'Official Coffee Export Offer Sheet generated successfully with Reference:\n\n$generatedRef\n\nSaved to your trade workspace.',
          ),
          actions: [
            TextButton(
              onPressed: () {
                Navigator.of(context).pop();
                Navigator.of(context).pop();
              },
              child: const Text('Back to Toolkit'),
            ),
          ],
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(provider.error ?? 'Failed to generate offer sheet'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.getBackgroundColor(context),
      appBar: AppBar(
        backgroundColor: AppTheme.getBackgroundColor(context),
        elevation: 0,
        leading: IconButton(
          icon: HugeIcon(
            icon: HugeIcons.strokeRoundedArrowLeft01,
            size: 20,
            color: AppTheme.getTextColor(context),
          ),
          onPressed: () => Navigator.of(context).pop(),
        ),
        title: Text(
          'Offer Generator',
          style: TextStyle(
            color: AppTheme.getTextColor(context),
            fontSize: 18,
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildSectionContainer(
              title: '1. Exporter & Origin Profile',
              children: [
                TextField(
                  controller: _exporterNameController,
                  style: TextStyle(
                      color: AppTheme.getTextColor(context), fontSize: 13),
                  decoration: _inputDecoration('Exporter Company Name'),
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<String>(
                  initialValue: _selectedRegion,
                  decoration: _inputDecoration('Origin Region'),
                  style: TextStyle(
                      color: AppTheme.getTextColor(context), fontSize: 13),
                  items: const [
                    DropdownMenuItem(value: 'Sidama', child: Text('Sidama')),
                    DropdownMenuItem(
                        value: 'Yirgacheffe', child: Text('Yirgacheffe')),
                    DropdownMenuItem(value: 'Guji', child: Text('Guji')),
                    DropdownMenuItem(value: 'Limu', child: Text('Limu')),
                    DropdownMenuItem(value: 'Jimma', child: Text('Jimma')),
                    DropdownMenuItem(value: 'Harrar', child: Text('Harrar')),
                  ],
                  onChanged: (val) {
                    if (val != null) setState(() => _selectedRegion = val);
                  },
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _subRegionController,
                  style: TextStyle(
                      color: AppTheme.getTextColor(context), fontSize: 13),
                  decoration: _inputDecoration('Sub-Region / Woreda'),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _washingStationController,
                  style: TextStyle(
                      color: AppTheme.getTextColor(context), fontSize: 13),
                  decoration: _inputDecoration('Washing Station / Mill'),
                ),
              ],
            ),
            const SizedBox(height: 16),
            _buildSectionContainer(
              title: '2. Quality & Physical Specifications',
              children: [
                DropdownButtonFormField<String>(
                  initialValue: _selectedProcess,
                  decoration: _inputDecoration('Processing Method'),
                  style: TextStyle(
                      color: AppTheme.getTextColor(context), fontSize: 13),
                  items: const [
                    DropdownMenuItem(
                        value: 'Fully Washed', child: Text('Fully Washed')),
                    DropdownMenuItem(
                        value: 'Natural / Sun-Dried',
                        child: Text('Natural / Sun-Dried')),
                    DropdownMenuItem(
                        value: 'Anaerobic Fermentation',
                        child: Text('Anaerobic Fermentation')),
                  ],
                  onChanged: (val) {
                    if (val != null) setState(() => _selectedProcess = val);
                  },
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: DropdownButtonFormField<String>(
                        initialValue: _selectedGrade,
                        decoration: _inputDecoration('Grade'),
                        style: TextStyle(
                            color: AppTheme.getTextColor(context), fontSize: 13),
                        items: const [
                          DropdownMenuItem(
                              value: 'Grade 1', child: Text('Grade 1')),
                          DropdownMenuItem(
                              value: 'Grade 2', child: Text('Grade 2')),
                          DropdownMenuItem(
                              value: 'Grade 3', child: Text('Grade 3')),
                        ],
                        onChanged: (val) {
                          if (val != null) setState(() => _selectedGrade = val);
                        },
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: TextField(
                        controller: _scoreController,
                        keyboardType: const TextInputType.numberWithOptions(
                            decimal: true),
                        style: TextStyle(
                            color: AppTheme.getTextColor(context), fontSize: 13),
                        decoration: _inputDecoration('Cupping Score (SCAA)'),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _notesController,
                  maxLines: 2,
                  style: TextStyle(
                      color: AppTheme.getTextColor(context), fontSize: 13),
                  decoration: _inputDecoration('Sensory Tasting Notes'),
                ),
              ],
            ),
            const SizedBox(height: 16),
            _buildSectionContainer(
              title: '3. Commercial & Delivery Terms',
              children: [
                Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _bagsController,
                        keyboardType: TextInputType.number,
                        style: TextStyle(
                            color: AppTheme.getTextColor(context), fontSize: 13),
                        decoration: _inputDecoration('Bags (60kg)'),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: TextField(
                        controller: _priceController,
                        keyboardType: const TextInputType.numberWithOptions(
                            decimal: true),
                        style: TextStyle(
                            color: AppTheme.getTextColor(context), fontSize: 13),
                        decoration: _inputDecoration('Price (\$ / lb)'),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<String>(
                  initialValue: _selectedIncoterm,
                  decoration: _inputDecoration('Incoterm 2020'),
                  style: TextStyle(
                      color: AppTheme.getTextColor(context), fontSize: 13),
                  items: const [
                    DropdownMenuItem(
                        value: 'FOB Djibouti', child: Text('FOB Port of Djibouti')),
                    DropdownMenuItem(
                        value: 'CIF Hamburg', child: Text('CIF Hamburg')),
                    DropdownMenuItem(
                        value: 'CIF Rotterdam', child: Text('CIF Rotterdam')),
                    DropdownMenuItem(
                        value: 'CIF New York', child: Text('CIF New York')),
                  ],
                  onChanged: (val) {
                    if (val != null) setState(() => _selectedIncoterm = val);
                  },
                ),
              ],
            ),
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton(
                onPressed: _handleGenerateOffer,
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF6D1A95),
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(24),
                  ),
                  elevation: 0,
                ),
                child: const Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    HugeIcon(
                      icon: HugeIcons.strokeRoundedFile01,
                      size: 18,
                      color: Colors.white,
                    ),
                    SizedBox(width: 8),
                    Text(
                      'Generate & Save Official Offer',
                      style:
                          TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 30),
          ],
        ),
      ),
    );
  }

  Widget _buildSectionContainer({
    required String title,
    required List<Widget> children,
  }) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppTheme.getCardColor(context),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: AppTheme.getTextColor(context).withValues(alpha: 0.08),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              color: Color(0xFF6D1A95),
              fontSize: 13,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 12),
          ...children,
        ],
      ),
    );
  }

  InputDecoration _inputDecoration(String label) {
    return InputDecoration(
      labelText: label,
      labelStyle: TextStyle(
        color: AppTheme.getTextColor(context).withValues(alpha: 0.6),
        fontSize: 12,
      ),
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(
          color: AppTheme.getTextColor(context).withValues(alpha: 0.15),
        ),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(
          color: AppTheme.getTextColor(context).withValues(alpha: 0.15),
        ),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: Color(0xFF6D1A95), width: 1.5),
      ),
    );
  }
}
