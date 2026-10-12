import 'package:flutter/material.dart';
import 'package:hugeicons/hugeicons.dart';
import 'package:provider/provider.dart';

import '../../providers/coffee_toolkit_provider.dart';
import '../../theme/app_theme.dart';

class CoffeeProfitCalculatorScreen extends StatefulWidget {
  const CoffeeProfitCalculatorScreen({super.key});

  @override
  State<CoffeeProfitCalculatorScreen> createState() =>
      _CoffeeProfitCalculatorScreenState();
}

class _CoffeeProfitCalculatorScreenState
    extends State<CoffeeProfitCalculatorScreen> {
  final TextEditingController _dealTitleController =
      TextEditingController(text: 'Sidama Washed G2 1-FCL Export');
  final TextEditingController _bagsController =
      TextEditingController(text: '320');
  final TextEditingController _priceController =
      TextEditingController(text: '3.15');
  final TextEditingController _exchangeRateController =
      TextEditingController(text: '142.50');
  final TextEditingController _purchasePriceController =
      TextEditingController(text: '340.00');
  final TextEditingController _processingController =
      TextEditingController(text: '180.00');
  final TextEditingController _packagingController =
      TextEditingController(text: '450.00');
  final TextEditingController _transportController =
      TextEditingController(text: '380000.00');
  final TextEditingController _handlingController =
      TextEditingController(text: '165000.00');

  String _selectedCoffeeType = 'Sidama Washed Grade 2';
  String _selectedIncoterm = 'FOB';

  static const double kgToLbs = 2.20462262;

  // Real-time calculated state
  double _netWeightKg = 19200.0;
  double _netWeightLbs = 42328.7;
  double _grossRevenueUsd = 0.0;
  double _grossRevenueEtb = 0.0;
  double _totalCostEtb = 0.0;
  double _netProfitEtb = 0.0;
  double _netProfitUsd = 0.0;
  double _marginPercentage = 0.0;
  double _breakEvenUsd = 0.0;

  @override
  void initState() {
    super.initState();
    _recalculate();
  }

  @override
  void dispose() {
    _dealTitleController.dispose();
    _bagsController.dispose();
    _priceController.dispose();
    _exchangeRateController.dispose();
    _purchasePriceController.dispose();
    _processingController.dispose();
    _packagingController.dispose();
    _transportController.dispose();
    _handlingController.dispose();
    super.dispose();
  }

  void _recalculate() {
    final bags = double.tryParse(_bagsController.text) ?? 320.0;
    final priceUsd = double.tryParse(_priceController.text) ?? 3.15;
    final rate = double.tryParse(_exchangeRateController.text) ?? 142.50;
    final purchasePerKg =
        double.tryParse(_purchasePriceController.text) ?? 340.0;
    final processingPerBag = double.tryParse(_processingController.text) ?? 180.0;
    final packagingPerBag = double.tryParse(_packagingController.text) ?? 450.0;
    final transport = double.tryParse(_transportController.text) ?? 380000.0;
    final handling = double.tryParse(_handlingController.text) ?? 165000.0;

    _netWeightKg = bags * 60.0;
    _netWeightLbs = _netWeightKg * kgToLbs;

    final purchaseCostEtb = _netWeightKg * purchasePerKg;
    final processingCostEtb = bags * processingPerBag;
    final packagingCostEtb = bags * packagingPerBag;

    _totalCostEtb = purchaseCostEtb +
        processingCostEtb +
        packagingCostEtb +
        transport +
        handling;
    final totalCostUsd = rate > 0 ? (_totalCostEtb / rate) : 0.0;

    _grossRevenueUsd = _netWeightLbs * priceUsd;
    _grossRevenueEtb = _grossRevenueUsd * rate;

    _netProfitEtb = _grossRevenueEtb - _totalCostEtb;
    _netProfitUsd = _grossRevenueUsd - totalCostUsd;
    _marginPercentage =
        _grossRevenueEtb > 0 ? ((_netProfitEtb / _grossRevenueEtb) * 100) : 0.0;
    _breakEvenUsd = (_netWeightLbs > 0 && rate > 0)
        ? (_totalCostEtb / (_netWeightLbs * rate))
        : 0.0;

    setState(() {});
  }

  void _resetDefaults() {
    _bagsController.text = '320';
    _priceController.text = '3.15';
    _exchangeRateController.text = '142.50';
    _purchasePriceController.text = '340.00';
    _processingController.text = '180.00';
    _packagingController.text = '450.00';
    _transportController.text = '380000.00';
    _handlingController.text = '165000.00';
    _selectedIncoterm = 'FOB';
    _recalculate();
  }

  Future<void> _handleSave() async {
    final provider = context.read<CoffeeToolkitProvider>();
    final params = {
      'deal_title': _dealTitleController.text,
      'coffee_type': _selectedCoffeeType,
      'quantity_bags': int.tryParse(_bagsController.text) ?? 320,
      'selling_price_usd_per_lb':
          double.tryParse(_priceController.text) ?? 3.15,
      'exchange_rate':
          double.tryParse(_exchangeRateController.text) ?? 142.50,
      'purchase_price_etb_per_kg':
          double.tryParse(_purchasePriceController.text) ?? 340.0,
      'processing_cost_etb_per_bag':
          double.tryParse(_processingController.text) ?? 180.0,
      'packaging_cost_etb_per_bag':
          double.tryParse(_packagingController.text) ?? 450.0,
      'transport_corridor_etb':
          double.tryParse(_transportController.text) ?? 380000.0,
      'handling_customs_etb':
          double.tryParse(_handlingController.text) ?? 165000.0,
      'incoterm': _selectedIncoterm,
    };

    final success = await provider.saveCalculation(params);
    if (!mounted) return;

    if (success) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Deal calculation saved successfully to workspace!'),
          backgroundColor: Colors.green,
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(provider.error ?? 'Failed to save calculation'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final isProfitable = _netProfitEtb >= 0;

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
          'Profit Calculator',
          style: TextStyle(
            color: AppTheme.getTextColor(context),
            fontSize: 18,
            fontWeight: FontWeight.bold,
          ),
        ),
        actions: [
          IconButton(
            icon: HugeIcon(
              icon: HugeIcons.strokeRoundedRotateLeft01,
              size: 20,
              color: AppTheme.getTextColor(context),
            ),
            tooltip: 'Reset 1 FCL Defaults',
            onPressed: _resetDefaults,
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildExecutiveResultsCard(isProfitable),
            const SizedBox(height: 16),
            _buildDealProfileSection(),
            const SizedBox(height: 16),
            _buildPricingSection(),
            const SizedBox(height: 16),
            _buildLocalCostsSection(),
            const SizedBox(height: 24),
            _buildActionButtons(),
            const SizedBox(height: 30),
          ],
        ),
      ),
    );
  }

  Widget _buildExecutiveResultsCard(bool isProfitable) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: isProfitable
              ? [const Color(0xFF4A0E66), const Color(0xFF6D1A95)]
              : [const Color(0xFF7F1D1D), const Color(0xFF991B1B)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: (isProfitable ? const Color(0xFF6D1A95) : Colors.red)
                .withValues(alpha: 0.25),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'ESTIMATED NET MARGIN',
                style: TextStyle(
                  color: Colors.white.withValues(alpha: 0.7),
                  fontSize: 11,
                  fontWeight: FontWeight.bold,
                  letterSpacing: 0.5,
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: isProfitable
                      ? Colors.green.withValues(alpha: 0.3)
                      : Colors.red.withValues(alpha: 0.4),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  isProfitable ? 'Profitable' : 'Loss Scenario',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 11,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            '${_marginPercentage.toStringAsFixed(2)}%',
            style: const TextStyle(
              color: Colors.white,
              fontSize: 34,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            'Net Profit: ${_netProfitEtb >= 0 ? '+' : ''}${_netProfitEtb.toStringAsFixed(0)} ETB (\$${_netProfitUsd.toStringAsFixed(2)})',
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.9),
              fontSize: 13,
              fontWeight: FontWeight.w500,
            ),
          ),
          const Divider(color: Colors.white24, height: 24),
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Net Weight',
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.7),
                        fontSize: 11,
                      ),
                    ),
                    Text(
                      '${_netWeightKg.toStringAsFixed(0)} kg',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 13,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
              ),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Gross Revenue',
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.7),
                        fontSize: 11,
                      ),
                    ),
                    Text(
                      '\$${_grossRevenueUsd.toStringAsFixed(0)}',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 13,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
              ),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Break-Even FOB',
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.7),
                        fontSize: 11,
                      ),
                    ),
                    Text(
                      '\$${_breakEvenUsd.toStringAsFixed(3)}/lb',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 13,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildDealProfileSection() {
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
          const Text(
            '1. Deal Profile & Volume',
            style: TextStyle(
              color: Color(0xFF6D1A95),
              fontSize: 13,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _dealTitleController,
            style: TextStyle(color: AppTheme.getTextColor(context), fontSize: 13),
            decoration: _inputDecoration('Deal Reference Name'),
          ),
          const SizedBox(height: 12),
          DropdownButtonFormField<String>(
            initialValue: _selectedCoffeeType,
            decoration: _inputDecoration('Coffee Origin & Grade'),
            style: TextStyle(color: AppTheme.getTextColor(context), fontSize: 13),
            items: const [
              DropdownMenuItem(
                value: 'Sidama Washed Grade 2',
                child: Text('Sidama Washed Grade 2'),
              ),
              DropdownMenuItem(
                value: 'Yirgacheffe Washed Grade 1',
                child: Text('Yirgacheffe Washed Grade 1'),
              ),
              DropdownMenuItem(
                value: 'Guji Natural Grade 1',
                child: Text('Guji Natural Grade 1'),
              ),
              DropdownMenuItem(
                value: 'Limu Washed Grade 2',
                child: Text('Limu Washed Grade 2'),
              ),
              DropdownMenuItem(
                value: 'Djimmah Commercial Grade 5',
                child: Text('Djimmah Commercial Grade 5'),
              ),
            ],
            onChanged: (val) {
              if (val != null) {
                setState(() => _selectedCoffeeType = val);
              }
            },
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _bagsController,
            keyboardType: TextInputType.number,
            style: TextStyle(color: AppTheme.getTextColor(context), fontSize: 13),
            decoration: _inputDecoration('Quantity of Bags (60 kg)'),
            onChanged: (_) => _recalculate(),
          ),
        ],
      ),
    );
  }

  Widget _buildPricingSection() {
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
          const Text(
            '2. Commercial Pricing & Exchange Rate',
            style: TextStyle(
              color: Color(0xFF6D1A95),
              fontSize: 13,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _priceController,
                  keyboardType:
                      const TextInputType.numberWithOptions(decimal: true),
                  style: TextStyle(
                      color: AppTheme.getTextColor(context), fontSize: 13),
                  decoration: _inputDecoration('Selling Price (\$ / lb)'),
                  onChanged: (_) => _recalculate(),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: DropdownButtonFormField<String>(
                  initialValue: _selectedIncoterm,
                  decoration: _inputDecoration('Incoterm'),
                  style: TextStyle(
                      color: AppTheme.getTextColor(context), fontSize: 13),
                  items: const [
                    DropdownMenuItem(value: 'FOB', child: Text('FOB Djibouti')),
                    DropdownMenuItem(value: 'CIF', child: Text('CIF Destination')),
                  ],
                  onChanged: (val) {
                    if (val != null) {
                      setState(() => _selectedIncoterm = val);
                      _recalculate();
                    }
                  },
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _exchangeRateController,
            keyboardType:
                const TextInputType.numberWithOptions(decimal: true),
            style: TextStyle(
                color: AppTheme.getTextColor(context), fontSize: 13),
            decoration: _inputDecoration('Commercial Bank Rate (ETB / \$)'),
            onChanged: (_) => _recalculate(),
          ),
        ],
      ),
    );
  }

  Widget _buildLocalCostsSection() {
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
          const Text(
            '3. Ethiopian Local Sourcing & Logistics Costs',
            style: TextStyle(
              color: Color(0xFF6D1A95),
              fontSize: 13,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _purchasePriceController,
            keyboardType:
                const TextInputType.numberWithOptions(decimal: true),
            style: TextStyle(
                color: AppTheme.getTextColor(context), fontSize: 13),
            decoration: _inputDecoration('Green Coffee Purchase (ETB / kg)'),
            onChanged: (_) => _recalculate(),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _processingController,
                  keyboardType: TextInputType.number,
                  style: TextStyle(
                      color: AppTheme.getTextColor(context), fontSize: 13),
                  decoration: _inputDecoration('Milling (ETB/bag)'),
                  onChanged: (_) => _recalculate(),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: TextField(
                  controller: _packagingController,
                  keyboardType: TextInputType.number,
                  style: TextStyle(
                      color: AppTheme.getTextColor(context), fontSize: 13),
                  decoration: _inputDecoration('Packaging (ETB/bag)'),
                  onChanged: (_) => _recalculate(),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _transportController,
            keyboardType: TextInputType.number,
            style: TextStyle(
                color: AppTheme.getTextColor(context), fontSize: 13),
            decoration: _inputDecoration('Corridor Transit Addis-Djibouti (ETB)'),
            onChanged: (_) => _recalculate(),
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _handlingController,
            keyboardType: TextInputType.number,
            style: TextStyle(
                color: AppTheme.getTextColor(context), fontSize: 13),
            decoration: _inputDecoration('Inspection, Port Clearance & Handling (ETB)'),
            onChanged: (_) => _recalculate(),
          ),
        ],
      ),
    );
  }

  Widget _buildActionButtons() {
    return SizedBox(
      width: double.infinity,
      height: 48,
      child: ElevatedButton(
        onPressed: _handleSave,
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
              icon: HugeIcons.strokeRoundedFloppyDisk,
              size: 18,
              color: Colors.white,
            ),
            SizedBox(width: 8),
            Text(
              'Save Deal to Workspace',
              style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
            ),
          ],
        ),
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
